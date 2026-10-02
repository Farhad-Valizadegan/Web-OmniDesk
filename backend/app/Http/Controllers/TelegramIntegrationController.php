<?php

namespace App\Http\Controllers;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Models\ProviderConnection;
use App\Services\TelegramAdapterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class TelegramIntegrationController extends Controller
{
    private function connection(Request $request): ProviderConnection
    {
        return ProviderConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', 'telegram')
            ->firstOrFail();
    }

    public function status(Request $request, TelegramAdapterService $adapterService): JsonResponse
    {
        $connection = ProviderConnection::query()->where('user_id', $request->user()->id)->where('provider', 'telegram')->first();

        if ($connection?->status === 'disconnecting') {
            try {
                $adapterService->deleteRuntime($connection);
                $connection->delete();
                $connection = null;
            } catch (Throwable $exception) {
                Log::channel('telegram')->warning('[telegram] disconnect.cleanup_retry_failed', [
                    'connection_id' => $connection->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        if ($connection && $connection->status !== 'connected') {
            try {
                $result = $adapterService->authorize($connection, 'status');
                $connection->status = $result['status'];
                $connection->save();
            } catch (Throwable $exception) {
                Log::channel('telegram')->warning('[telegram] authorization.status_failed', [
                    'connection_id' => $connection->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return response()->json([
            'connected' => $connection?->status === 'connected',
            'status' => $connection?->status ?? 'disconnected',
            'phone' => $this->maskedPhone($connection?->telegram_phone),
        ]);
    }

    public function connect(Request $request, TelegramAdapterService $adapterService): JsonResponse
    {
        $phone = trim((string) $request->input('phone'));
        if (str_starts_with($phone, '00')) {
            $phone = '+'.substr($phone, 2);
        }
        $request->merge(['phone' => $phone]);
        $validated = $request->validate(['phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{6,14}$/']]);
        $disconnecting = ProviderConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', 'telegram')
            ->where('status', 'disconnecting')
            ->first();
        if ($disconnecting) {
            $adapterService->deleteRuntime($disconnecting);
            $disconnecting->delete();
        }
        $connection = ProviderConnection::query()->firstOrCreate(
            ['user_id' => $request->user()->id, 'provider' => 'telegram'],
            ['status' => 'pending', 'telegram_database_key' => bin2hex(random_bytes(32))],
        );
        if (! $connection->telegram_database_key) {
            $connection->telegram_database_key = bin2hex(random_bytes(32));
        }
        $connection->telegram_phone = $validated['phone'];
        $result = $adapterService->authorize($connection, 'phone', $validated['phone']);
        $connection->status = $result['status'];
        $connection->save();

        return response()->json(['status' => $connection->status]);
    }

    public function code(Request $request, TelegramAdapterService $adapterService): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'min:3', 'max:12']]);

        return $this->continueAuthorization($request, $adapterService, 'code', $validated['code']);
    }

    public function password(Request $request, TelegramAdapterService $adapterService): JsonResponse
    {
        $validated = $request->validate(['password' => ['required', 'string', 'max:255']]);

        return $this->continueAuthorization($request, $adapterService, 'password', $validated['password']);
    }

    public function disconnect(Request $request, TelegramAdapterService $adapterService): JsonResponse
    {
        $connection = $this->connection($request);
        $wasConnected = $connection->status === 'connected';
        $connection->status = 'disconnecting';
        $connection->save();

        if ($wasConnected) {
            try {
                $adapterService->authorize($connection, 'logout');
            } catch (Throwable) {
                // Local deletion still removes access even if Telegram logout fails.
            }
        }
        try {
            $adapterService->authorize($connection, 'shutdown');
        } catch (Throwable) {
            // A missing worker does not prevent local session deletion.
        }
        $adapterService->deleteRuntime($connection);
        $connection->delete();

        return response()->json(['message' => 'Telegram disconnected.']);
    }

    public function media(Request $request, TelegramAdapterService $adapterService): BinaryFileResponse|JsonResponse
    {
        $connection = $this->connection($request);
        abort_unless($connection->status === 'connected', 409, 'Connect Telegram first.');
        $validated = $request->validate([
            'chat_id' => ['required', 'integer'],
            'message_id' => ['required', 'integer', 'min:1'],
        ]);
        set_time_limit(max(30, (int) config('telegram.timeout_seconds', 60) + 15));

        try {
            $result = $adapterService->fetchContentSample(
                $connection,
                (int) $validated['chat_id'], 1, null, (int) $validated['message_id'],
            );
        } catch (ProviderException $exception) {
            return response()->json(['message' => 'Media could not be loaded. Previews support files up to 100 MB. Retry or open it in Telegram.'], $exception->httpStatus());
        }

        $path = realpath($result['media_path'] ?? '');
        $root = realpath($adapterService->runtimeDirectory($connection));
        abort_unless($path && $root && is_file($path), 404);
        $comparisonPath = PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
        $comparisonRoot = PHP_OS_FAMILY === 'Windows' ? strtolower($root) : $root;
        abort_unless(str_starts_with($comparisonPath, $comparisonRoot.DIRECTORY_SEPARATOR), 404);
        abort_if(filesize($path) > 100 * 1024 * 1024, 413);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm', 'video/quicktime', 'audio/ogg', 'audio/opus', 'audio/mpeg', 'audio/mp4', 'audio/webm', 'audio/wav'], true), 415);

        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ])->setPrivate();
    }

    public function sample(
        Request $request,
        TelegramAdapterService $adapterService,
    ): JsonResponse {
        $connection = $this->connection($request);
        abort_unless($connection->status === 'connected', 409, 'Connect Telegram first.');

        $validated = $request->validate([
            'chat_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'before_message_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $operation = $this->operationFor($validated);
        $startedAt = microtime(true);

        set_time_limit(
            max(30, (int) config('telegram.timeout_seconds', 60) + 15)
        );

        Log::channel('telegram')->info('[telegram] api.request.start', [
            'operation' => $operation,
            'chat_id' => $validated['chat_id'] ?? null,
            'limit' => $validated['limit'] ?? 20,
            'before_message_id' => $validated['before_message_id'] ?? null,
        ]);

        try {
            $result = $adapterService->fetchContentSample(
                $connection,
                $validated['chat_id'] ?? null,
                $validated['limit'] ?? 20,
                $validated['before_message_id'] ?? null,
            );
        } catch (ProviderException $exception) {
            Log::channel('telegram')->error(
                '[telegram] api.request.error',
                [
                    'operation' => $exception->operation ?? $operation,
                    'error' => $exception->errorCode(),
                    'retryable' => $exception->isRetryable(),
                    'duration_ms' => $this->durationMs($startedAt),
                ],
            );

            return response()->json([
                'message' => $exception->getMessage(),
                'error' => $exception->errorCode(),
                'provider' => 'telegram',
                'operation' => $exception->operation ?? $operation,
                'retryable' => $exception->isRetryable(),
            ], $exception->httpStatus());
        } catch (Throwable $exception) {
            Log::channel('telegram')->error(
                '[telegram] api.request.unexpected_failure',
                [
                    'operation' => $operation,
                    'exception' => $exception::class,
                    'error' => $exception->getMessage(),
                    'duration_ms' => $this->durationMs($startedAt),
                ],
            );

            $providerException = new ProviderException(
                'telegram',
                ProviderErrorType::Temporary,
                'Telegram content could not be loaded.',
                $exception,
                $operation,
            );

            return response()->json([
                'message' => $providerException->getMessage(),
                'error' => $providerException->errorCode(),
                'provider' => 'telegram',
                'operation' => $operation,
                'retryable' => true,
            ], $providerException->httpStatus());
        }

        Log::channel('telegram')->info('[telegram] api.request.complete', [
            'operation' => $operation,
            'duration_ms' => $this->durationMs($startedAt),
            'chat_count' => count($result['chats'] ?? []),
            'message_count' => count($result['messages'] ?? []),
            'has_more' => (bool) ($result['has_more'] ?? false),
        ]);

        return response()->json([
            'message' => 'Telegram content loaded.',
            'data' => $result,
        ]);
    }

    private function operationFor(array $validated): string
    {
        if (($validated['chat_id'] ?? null) === null) {
            return 'chat_discovery';
        }

        return ($validated['before_message_id'] ?? null) !== null
            ? 'history_pagination'
            : 'history_initial';
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function continueAuthorization(Request $request, TelegramAdapterService $adapterService, string $action, string $value): JsonResponse
    {
        $connection = $this->connection($request);
        $result = $adapterService->authorize($connection, $action, $value);
        $connection->status = $result['status'];
        $connection->save();

        return response()->json(['status' => $connection->status]);
    }

    private function maskedPhone(?string $phone): ?string
    {
        if ($phone === null || strlen($phone) < 5) {
            return $phone;
        }

        return substr($phone, 0, 3).str_repeat('•', max(2, strlen($phone) - 6)).substr($phone, -3);
    }
}

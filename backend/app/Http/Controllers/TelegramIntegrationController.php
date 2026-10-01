<?php

namespace App\Http\Controllers;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Services\TelegramAdapterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class TelegramIntegrationController extends Controller
{
    private function ensureOwner(Request $request): void
    {
        $ownerId = (int) config('telegram.sample_owner_user_id', 0);
        abort_unless(app()->environment('local') && $ownerId > 0
            && (int) $request->user()->getAuthIdentifier() === $ownerId, 404);
    }

    public function media(Request $request, TelegramAdapterService $adapterService): BinaryFileResponse|JsonResponse
    {
        $this->ensureOwner($request);
        $validated = $request->validate([
            'chat_id' => ['required', 'integer'],
            'message_id' => ['required', 'integer', 'min:1'],
        ]);
        set_time_limit(max(30, (int) config('telegram.timeout_seconds', 60) + 15));

        try {
            $result = $adapterService->fetchContentSample(
                (int) $validated['chat_id'], 1, null, (int) $validated['message_id'],
            );
        } catch (ProviderException $exception) {
            return response()->json(['message' => 'Media could not be loaded. Previews support files up to 100 MB. Retry or open it in Telegram.'], $exception->httpStatus());
        }

        $path = realpath($result['media_path'] ?? '');
        $root = realpath((string) config('telegram.runtime_directory'));
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
        $this->ensureOwner($request);

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
}

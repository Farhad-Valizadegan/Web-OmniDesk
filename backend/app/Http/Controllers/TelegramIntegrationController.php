<?php

namespace App\Http\Controllers;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Services\TelegramAdapterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramIntegrationController extends Controller
{
    public function sample(
        Request $request,
        TelegramAdapterService $adapterService,
    ): JsonResponse {
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

    /**
     * @param array<string, mixed> $validated
     */
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

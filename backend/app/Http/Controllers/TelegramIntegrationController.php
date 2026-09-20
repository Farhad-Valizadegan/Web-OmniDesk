<?php

namespace App\Http\Controllers;

use App\Services\TelegramAdapterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TelegramIntegrationController extends Controller
{
    public function sample(
        Request $request,
        TelegramAdapterService $adapterService,
    ): JsonResponse {
        set_time_limit(120);

        $validated = $request->validate([
            'chat_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'before_message_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $result = $adapterService->fetchContentSample(
                $validated['chat_id'] ?? null,
                    $validated['limit'] ?? 20,
                    $validated['before_message_id'] ?? null,
                );
        } catch (Throwable) {
            return response()->json([
                'message' => 'Telegram content could not be loaded.',
                'error' => 'telegram_content_load_failed',
            ], 502);
        }

        return response()->json([
            'message' => 'Telegram content loaded.',
            'data' => $result,
        ]);
    }
}

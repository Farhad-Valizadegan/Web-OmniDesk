<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Services\GmailMessageSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

class GmailIntegrationController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $connection = ProviderConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', 'gmail')
            ->first();

        if ($connection === null) {
            return response()->json([
                'connected' => false,
                'connection' => null,
            ]);
        }

        return response()->json([
            'connected' => $connection->status === 'connected',
            'connection' => [
                'id' => $connection->id,
                'provider' => $connection->provider,
                'provider_account_id' =>
                    $connection->provider_account_id,
                'account_email' => $connection->account_email,
                'account_avatar_url' => $connection->account_avatar_url,
                'status' => $connection->status,
                'scopes' => $connection->scopes,
                'token_expires_at' => $connection->token_expires_at,
            ],
        ]);
    }

    public function sync(
        Request $request,
        GmailMessageSyncService $syncService,
    ): JsonResponse {
        $validated = $request->validate([
            'max_results' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page_token' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $result = $syncService->sync(
                $request->user(),
                $validated['max_results'] ?? 20,
                $validated['page_token'] ?? null,
            );
        } catch (Throwable) {
            return response()->json([
                'message' => 'Gmail synchronization could not be completed.',
                'error' => 'gmail_sync_failed',
            ], 502);
        }

        return response()->json([
            'message' => 'Gmail synchronization completed.',
            'sync' => $result,
        ]);
    }

    public function disconnect(Request $request): Response
    {
        $userId = $request->user()->id;

        DB::transaction(function () use ($userId): void {
            $conversationIds = Conversation::query()
                ->where('user_id', $userId)
                ->where('provider', 'gmail')
                ->pluck('id');

            if ($conversationIds->isNotEmpty()) {
                Message::query()
                    ->whereIn('conversation_id', $conversationIds)
                    ->delete();

                Conversation::query()
                    ->whereIn('id', $conversationIds)
                    ->delete();
            }

            ProviderConnection::query()
                ->where('user_id', $userId)
                ->where('provider', 'gmail')
                ->delete();
        });

        return response()->noContent();
    }
}

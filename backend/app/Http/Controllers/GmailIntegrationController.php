<?php

namespace App\Http\Controllers;

use App\Exceptions\ProviderException;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Services\GmailMessageSyncService;
use App\Services\GoogleOAuthService;
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
                'provider_account_id' => $connection->provider_account_id,
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
        set_time_limit(120);

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
        } catch (ProviderException $exception) {
            return $this->providerErrorResponse($exception);
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

    public function syncNextPage(
        Request $request,
        GmailMessageSyncService $syncService,
    ): JsonResponse {
        set_time_limit(120);

        $validated = $request->validate([
            'max_results' => ['nullable', 'integer', 'min:1', 'max:15'],
        ]);

        try {
            $result = $syncService->syncNextPage(
                $request->user(),
                $validated['max_results'] ?? 15,
            );
        } catch (ProviderException $exception) {
            return $this->providerErrorResponse($exception);
        } catch (Throwable) {
            return response()->json([
                'message' => 'Gmail page could not be loaded.',
                'error' => 'gmail_page_sync_failed',
            ], 502);
        }

        return response()->json([
            'message' => 'Gmail page loaded.',
            'sync' => $result,
        ]);
    }

    private function providerErrorResponse(
        ProviderException $exception
    ): JsonResponse {
        return response()->json([
            'message' => $exception->getMessage(),
            'error' => $exception->errorCode(),
            'retryable' => $exception->isRetryable(),
        ], $exception->httpStatus());
    }

    public function disconnect(
        Request $request,
        GoogleOAuthService $googleOAuthService,
    ): Response|JsonResponse {
        $userId = $request->user()->id;

        $connection = ProviderConnection::query()
            ->where('user_id', $userId)
            ->where('provider', 'gmail')
            ->first();

        $revocationFailed = false;

        if ($connection !== null) {
            try {
                $googleOAuthService->revokeAuthorization(
                    $connection->refresh_token,
                    $connection->access_token,
                );
            } catch (Throwable) {
                $revocationFailed = true;
            }
        }

        DB::transaction(function () use ($userId, $connection): void {
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

            if ($connection !== null) {
                ProviderConnection::query()
                    ->where('id', $connection->id)
                    ->where('user_id', $userId)
                    ->where('provider', 'gmail')
                    ->delete();
            }
        });

        if ($revocationFailed) {
            return response()->json([
                'message' => 'Gmail was disconnected locally, but Google authorization could not be revoked. Revoke OmniDesk AI access from your Google Account if it remains active.',
                'error' => 'gmail_revocation_failed',
            ], 202);
        }

        return response()->noContent();
    }
}

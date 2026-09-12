<?php

namespace App\Http\Controllers;

use App\Models\ProviderConnection;
use App\Services\GoogleOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GmailCallbackController extends Controller
{
    public function __construct(
        private readonly GoogleOAuthService $googleOAuthService
    ) {
    }

    public function callback(Request $request): JsonResponse
    {
        $state = $request->string('state')->toString();

        if ($state === '') {
            return response()->json([
                'message' => 'The OAuth state is invalid or expired.',
                'error' => 'oauth_state_invalid',
            ], 422);
        }

        $stateData = Cache::pull(
            GmailAuthorizationController::stateCacheKey($state)
        );

        if (! is_array($stateData) || ! isset($stateData['user_id'])) {
            return response()->json([
                'message' => 'The OAuth state is invalid or expired.',
                'error' => 'oauth_state_invalid',
            ], 422);
        }

        if ($request->filled('error')) {
            return response()->json([
                'message' => 'Gmail authorization was not completed.',
                'error' => 'authorization_denied',
            ], 422);
        }

        if (! $request->filled('code')) {
            return response()->json([
                'message' => 'The OAuth authorization code is missing.',
                'error' => 'authorization_code_missing',
            ], 422);
        }

        try {
            $token = $this->googleOAuthService->exchangeCode(
                $request->string('code')->toString()
            );
        } catch (Throwable) {
            return response()->json([
                'message' => 'Gmail authorization could not be completed.',
                'error' => 'gmail_token_exchange_failed',
            ], 502);
        }

        try {
            $identity = $this->googleOAuthService
                ->accountIdentity($token);
        } catch (Throwable) {
            return response()->json([
                'message' => 'The Gmail account identity could not be retrieved.',
                'error' => 'gmail_account_identity_failed',
            ], 502);
        }

        $connection = ProviderConnection::firstOrNew([
            'user_id' => $stateData['user_id'],
            'provider' => 'gmail',
        ]);

        $connection->provider_account_id =
            $identity['provider_account_id'];

        $connection->account_email =
            $identity['account_email'];

        $connection->access_token = $token['access_token'];

        if (
            isset($token['refresh_token'])
            && is_string($token['refresh_token'])
            && $token['refresh_token'] !== ''
        ) {
            $connection->refresh_token = $token['refresh_token'];
        }

        $expiresIn = isset($token['expires_in'])
            ? max(1, (int) $token['expires_in'])
            : 3600;

        $scopeString = isset($token['scope'])
            && is_string($token['scope'])
            ? trim($token['scope'])
            : '';

        $connection->token_expires_at = now()->addSeconds($expiresIn);
        $connection->scopes = $scopeString === ''
            ? config('services.google.scopes', [])
            : preg_split('/\s+/', $scopeString);
        $connection->status = 'connected';
        $connection->save();

        return response()->json([
            'message' => 'Gmail authorization completed.',
            'connection' => [
                'id' => $connection->id,
                'provider' => $connection->provider,
                'provider_account_id' =>
                    $connection->provider_account_id,
                'account_email' => $connection->account_email,
                'status' => $connection->status,
                'scopes' => $connection->scopes,
                'token_expires_at' => $connection->token_expires_at,
            ],
        ]);
    }
}

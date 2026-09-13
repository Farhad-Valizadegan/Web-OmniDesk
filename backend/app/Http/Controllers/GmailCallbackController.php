<?php

namespace App\Http\Controllers;

use App\Models\ProviderConnection;
use App\Services\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GmailCallbackController extends Controller
{
    public function __construct(
        private readonly GoogleOAuthService $googleOAuthService
    ) {
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = $request->string('state')->toString();

        if ($state === '') {
            return $this->redirectToFrontend('oauth_state_invalid');
        }

        $stateData = Cache::pull(
            GmailAuthorizationController::stateCacheKey($state)
        );

        if (! is_array($stateData) || ! isset($stateData['user_id'])) {
            return $this->redirectToFrontend('oauth_state_invalid');
        }

        if ($request->filled('error')) {
            return $this->redirectToFrontend('authorization_denied');
        }

        if (! $request->filled('code')) {
            return $this->redirectToFrontend('authorization_code_missing');
        }

        try {
            $token = $this->googleOAuthService->exchangeCode(
                $request->string('code')->toString()
            );
        } catch (Throwable) {
            return $this->redirectToFrontend('gmail_token_exchange_failed');
        }

        try {
            $identity = $this->googleOAuthService
                ->accountIdentity($token);
        } catch (Throwable) {
            return $this->redirectToFrontend('gmail_account_identity_failed');
        }

        $connection = ProviderConnection::firstOrNew([
            'user_id' => $stateData['user_id'],
            'provider' => 'gmail',
        ]);

        $connection->provider_account_id =
            $identity['provider_account_id'];

        $connection->account_email =
            $identity['account_email'];

        $connection->account_avatar_url =
            $identity['account_avatar_url'] ?? null;

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

        return $this->redirectToFrontend('connected');
    }

    private function redirectToFrontend(string $result): RedirectResponse
    {
        $frontendUrl = rtrim(
            (string) config('services.frontend.url'),
            '/'
        );

        return redirect()->to($frontendUrl . '/?gmail=' . urlencode($result));
    }
}

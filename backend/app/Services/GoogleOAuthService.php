<?php

namespace App\Services;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;
use RuntimeException;
use Throwable;

class GoogleOAuthService
{
    public function __construct(
        private readonly GmailProviderErrorClassifier $errorClassifier
    ) {}

    public function createClient(): GoogleClient
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');
        $scopes = config('services.google.scopes', []);

        if (! is_string($clientId) || $clientId === '') {
            throw new RuntimeException('Google OAuth client ID is not configured.');
        }

        if (! is_string($clientSecret) || $clientSecret === '') {
            throw new RuntimeException('Google OAuth client secret is not configured.');
        }

        if (! is_string($redirectUri) || $redirectUri === '') {
            throw new RuntimeException('Google OAuth redirect URI is not configured.');
        }

        $client = new GoogleClient;

        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes($scopes);

        return $client;
    }

    public function authorizationUrl(string $state): string
    {
        if ($state === '') {
            throw new RuntimeException('OAuth state cannot be empty.');
        }

        $client = $this->createClient();
        $client->setState($state);

        return $client->createAuthUrl();
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code): array
    {
        if ($code === '') {
            throw new RuntimeException('OAuth authorization code cannot be empty.');
        }

        try {
            $token = $this->createClient()
                ->fetchAccessTokenWithAuthCode($code);
        } catch (Throwable $exception) {
            throw $this->errorClassifier->classify($exception);
        }

        if (
            ! is_array($token)
            || isset($token['error'])
            || ! isset($token['access_token'])
            || ! is_string($token['access_token'])
            || $token['access_token'] === ''
        ) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::Authorization,
                'Gmail authorization is invalid or expired.'
            );
        }

        return $token;
    }

    public function revokeAuthorization(
        ?string $refreshToken,
        ?string $accessToken,
    ): void {
        $token = $this->usableToken($refreshToken)
            ?? $this->usableToken($accessToken);

        if ($token === null) {
            return;
        }

        try {
            $revoked = $this->createClient()->revokeToken($token);
        } catch (Throwable $exception) {
            throw $this->errorClassifier->classify($exception);
        }

        if ($revoked !== true) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::Temporary,
                'Gmail authorization could not be revoked.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $token
     * @return array{
     *     provider_account_id: string,
     *     account_email: string,
     *     account_name: string|null,
     *     account_avatar_url: string|null
     * }
     */
    public function accountIdentity(array $token): array
    {
        if (
            ! isset($token['access_token'])
            || ! is_string($token['access_token'])
            || $token['access_token'] === ''
        ) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::InvalidResponse,
                'Gmail returned an invalid response.'
            );
        }

        $client = $this->createClient();
        $client->setAccessToken($token);

        try {
            $userInfo = (new Oauth2($client))
                ->userinfo
                ->get();
        } catch (Throwable $exception) {
            throw $this->errorClassifier->classify($exception);
        }

        $providerAccountId = $userInfo->getId();
        $accountEmail = $userInfo->getEmail();

        if (
            ! is_string($providerAccountId)
            || $providerAccountId === ''
            || ! is_string($accountEmail)
            || $accountEmail === ''
        ) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::InvalidResponse,
                'Gmail returned an invalid response.'
            );
        }

        $accountName = $userInfo->getName();
        $accountAvatarUrl = $userInfo->getPicture();

        return [
            'provider_account_id' => $providerAccountId,
            'account_email' => $accountEmail,
            'account_name' => is_string($accountName)
                ? $accountName
                : null,
            'account_avatar_url' => is_string($accountAvatarUrl)
                ? $accountAvatarUrl
                : null,
        ];
    }

    private function usableToken(?string $token): ?string
    {
        if (! is_string($token)) {
            return null;
        }

        $token = trim($token);

        return $token === '' ? null : $token;
    }
}

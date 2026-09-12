<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Oauth2;
use RuntimeException;

class GoogleOAuthService
{
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

        $client = new GoogleClient();

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

        $token = $this->createClient()
            ->fetchAccessTokenWithAuthCode($code);

        if (
            ! is_array($token)
            || isset($token['error'])
            || ! isset($token['access_token'])
            || ! is_string($token['access_token'])
            || $token['access_token'] === ''
        ) {
            throw new RuntimeException(
                'Google authorization token exchange failed.'
            );
        }

        return $token;
    }

    /**
     * @param array<string, mixed> $token
     * @return array{
     *     provider_account_id: string,
     *     account_email: string,
     *     account_name: string|null
     * }
     */
    public function accountIdentity(array $token): array
    {
        if (
            ! isset($token['access_token'])
            || ! is_string($token['access_token'])
            || $token['access_token'] === ''
        ) {
            throw new RuntimeException(
                'Google access token is missing.'
            );
        }

        $client = $this->createClient();
        $client->setAccessToken($token);

        $userInfo = (new Oauth2($client))
            ->userinfo
            ->get();

        $providerAccountId = $userInfo->getId();
        $accountEmail = $userInfo->getEmail();

        if (
            ! is_string($providerAccountId)
            || $providerAccountId === ''
            || ! is_string($accountEmail)
            || $accountEmail === ''
        ) {
            throw new RuntimeException(
                'Google account identity is incomplete.'
            );
        }

        $accountName = $userInfo->getName();

        return [
            'provider_account_id' => $providerAccountId,
            'account_email' => $accountEmail,
            'account_name' => is_string($accountName)
                ? $accountName
                : null,
        ];
    }
}

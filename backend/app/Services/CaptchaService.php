<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CaptchaService
{
    public function challenge(): array
    {
        if (! $this->isEnabled()) {
            return [
                'driver' => 'disabled',
                'expires_in' => 0,
            ];
        }

        if (config('captcha.driver') === 'google') {
            return [
                'driver' => 'google',
                'site_key' => config('captcha.google.site_key'),
            ];
        }

        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $token = Str::random(64);

        Cache::put(
            $this->cacheKey($token),
            hash('sha256', (string) ($left + $right)),
            now()->addSeconds((int) config('captcha.ttl_seconds', 300))
        );

        return [
            'driver' => 'internal',
            'token' => $token,
            'question' => "{$left} + {$right} = ?",
            'expires_in' => (int) config('captcha.ttl_seconds', 300),
        ];
    }

    public function verify(Request $request): void
    {
        if (
            app()->environment('testing')
            || ! $this->isEnabled()
        ) {
            return;
        }

        if (config('captcha.driver') === 'google') {
            $this->verifyGoogle(
                $request->string('captcha_response')->toString(),
                $request->ip()
            );

            return;
        }

        $token = $request->string('captcha_token')->toString();
        $answer = $request->string('captcha_answer')->toString();

        if ($token === '' || $answer === '') {
            $this->fail(
                'captcha',
                'A valid CAPTCHA answer is required.'
            );
        }

        $expectedHash = Cache::pull(
            $this->cacheKey($token)
        );

        if (
            ! is_string($expectedHash)
            || ! hash_equals(
                $expectedHash,
                hash('sha256', $answer)
            )
        ) {
            $this->fail(
                'captcha',
                'The CAPTCHA answer is invalid or expired.'
            );
        }
    }

    private function verifyGoogle(
        string $response,
        ?string $ip
    ): void {
        if ($response === '') {
            $this->fail(
                'captcha',
                'Google CAPTCHA verification is required.'
            );
        }

        $verification = Http::asForm()
            ->timeout(5)
            ->post(
                config('captcha.google.verify_url'),
                [
                    'secret' => config('captcha.google.secret_key'),
                    'response' => $response,
                    'remoteip' => $ip,
                ]
            );

        if (
            ! $verification->successful()
            || ! $verification->json('success', false)
        ) {
            $this->fail(
                'captcha',
                'Google CAPTCHA verification failed.'
            );
        }
    }

    private function isEnabled(): bool
    {
        if (! app()->environment('local')) {
            return true;
        }

        return (bool) config('captcha.enabled', true);
    }

    private function cacheKey(string $token): string
    {
        return 'captcha:'.$token;
    }

    private function fail(
        string $field,
        string $message
    ): never {
        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }
}

<?php

namespace App\Services;

use App\Mail\LoginCode;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginCodeService
{
    public function issue(User $user): array
    {
        return Cache::lock('login-code-user:'.$user->id, 30)->block(5, function () use ($user) {
            $previous = Cache::get('login-code-active:'.$user->id);
            $old = $previous ? Cache::get('login-code:'.$previous) : null;
            if ($old && $old['sent_at'] > now()->timestamp - 60) {
                $this->fail('Please wait 60 seconds before requesting another code.');
            }
            $challenge = Str::random(64);
            $data = ['user_id' => $user->id, 'account_fingerprint' => $this->accountFingerprint($user),
                'attempts' => 0, 'expires' => now()->timestamp + 600];
            $data = $this->send($user, $data);
            if ($previous) {
                Cache::forget('login-code:'.$previous);
            }
            Cache::put('login-code:'.$challenge, $data, 600);
            Cache::put('login-code-active:'.$user->id, $challenge, 600);

            return $this->response($challenge, $data);
        });
    }

    public function complete(string $challenge, ?string $code = null): array
    {
        $initial = Cache::get('login-code:'.$challenge);
        if (! $initial) {
            $this->fail('This sign-in code has expired. Please sign in again.');
        }

        return Cache::lock('login-code-user:'.$initial['user_id'], 30)->block(5, function () use ($challenge, $code) {
            $data = Cache::get('login-code:'.$challenge);
            $user = $data ? User::find($data['user_id']) : null;
            if (! $data || ! $user || $data['expires'] <= now()->timestamp || $data['attempts'] >= 5
                || ! hash_equals($data['account_fingerprint'], $this->accountFingerprint($user))
                || Cache::get('login-code-active:'.$user->id) !== $challenge) {
                $this->fail('This sign-in code is no longer valid. Please sign in again.');
            }
            if ($code === null) {
                if ($data['sent_at'] > now()->timestamp - 60) {
                    $this->fail('Please wait 60 seconds before requesting another code.');
                }
                $data = $this->send($user, $data);
                Cache::put('login-code:'.$challenge, $data, $data['expires'] - now()->timestamp);

                return $this->response($challenge, $data);
            }
            if (! Hash::check($code, $data['hash'])) {
                $data['attempts']++;
                Cache::put('login-code:'.$challenge, $data, $data['expires'] - now()->timestamp);
                $this->fail('The code is incorrect. After five attempts, sign in again.');
            }
            Cache::forget('login-code:'.$challenge);
            Cache::forget('login-code-active:'.$user->id);

            return ['message' => 'Login successful.', 'user' => $user,
                'token' => $user->createToken('web')->plainTextToken];
        });
    }

    private function send(User $user, array $data): array
    {
        $transport = config('mail.mailers.'.config('mail.default').'.transport');
        if (! app()->environment('testing') && ! in_array($transport, ['smtp', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true)) {
            $this->fail('Email sign-in is not configured. Configure a delivery mail service before signing in.');
        }
        if ($transport === 'smtp' && (! config('mail.mailers.smtp.username') || ! config('mail.mailers.smtp.password'))) {
            $this->fail('Gmail delivery needs a Google App Password in backend/.env (MAIL_PASSWORD).');
        }
        $code = (string) random_int(100000, 999999);
        try {
            Mail::to($user->email)->send(new LoginCode($code));
        } catch (\Throwable) {
            $this->fail('The sign-in email could not be sent. Please try again later.');
        }
        $data['hash'] = Hash::make($code);
        $data['sent_at'] = now()->timestamp;

        return $data;
    }

    private function response(string $challenge, array $data): array
    {
        return ['message' => 'Check your email for a sign-in code.', 'challenge' => $challenge,
            'expires_in' => max(0, $data['expires'] - now()->timestamp), 'resend_after' => 60];
    }

    private function accountFingerprint(User $user): string
    {
        return hash_hmac('sha256', $user->email."\0".$user->password, (string) config('app.key'));
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['code' => [$message]]);
    }
}

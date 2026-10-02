<?php

namespace Tests\Feature;

use App\Mail\LoginCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'freddie@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'freddie@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Check your email for a sign-in code.')
            ->assertJsonStructure([
                'message',
                'challenge',
                'expires_in',
                'resend_after',
            ]);

        $this->assertArrayNotHasKey('token', $response->json());
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Mail::assertSent(LoginCode::class, fn (LoginCode $mail) => $mail->hasTo($user->email));
    }

    public function test_correct_code_creates_session_and_code_cannot_be_reused(): void
    {
        [$user, $challenge, $code] = $this->beginLogin();

        $verified = $this->postJson('/api/login/verify', compact('challenge', 'code'));
        $verified->assertOk()->assertJsonPath('user.id', $user->id)->assertJsonStructure(['token']);

        $this->postJson('/api/login/verify', compact('challenge', 'code'))->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_incorrect_expired_and_attempt_limited_codes_are_rejected(): void
    {
        [, $challenge] = $this->beginLogin();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login/verify', ['challenge' => $challenge, 'code' => '000000'])
                ->assertUnprocessable();
        }
        $this->postJson('/api/login/verify', ['challenge' => $challenge, 'code' => '000000'])
            ->assertUnprocessable()->assertJsonPath('errors.code.0', 'This sign-in code is no longer valid. Please sign in again.');
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->travel(61)->seconds();
        [, $expiredChallenge, $expiredCode] = $this->beginLogin('other@example.com');
        $this->travel(11)->minutes();
        $this->postJson('/api/login/verify', ['challenge' => $expiredChallenge, 'code' => $expiredCode])
            ->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_resend_replaces_code_and_has_a_cooldown(): void
    {
        [, $challenge, $oldCode] = $this->beginLogin();
        $this->postJson('/api/login/resend', compact('challenge'))->assertUnprocessable();

        $this->travel(61)->seconds();
        $newCode = null;
        Mail::fake();
        $this->postJson('/api/login/resend', compact('challenge'))->assertOk();
        Mail::assertSent(LoginCode::class, function (LoginCode $mail) use (&$newCode) {
            $newCode = $mail->code;

            return true;
        });
        $this->postJson('/api/login/verify', ['challenge' => $challenge, 'code' => $oldCode])->assertUnprocessable();
        $this->postJson('/api/login/verify', ['challenge' => $challenge, 'code' => $newCode])->assertOk();
    }

    public function test_challenge_cannot_be_completed_after_account_password_changes(): void
    {
        [$user, $challenge, $code] = $this->beginLogin();
        $user->update(['password' => Hash::make('a-new-password')]);
        $this->postJson('/api/login/verify', compact('challenge', 'code'))->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_codes_are_isolated_between_users(): void
    {
        [, $firstChallenge] = $this->beginLogin('first@example.com');
        [, , $secondCode] = $this->beginLogin('second@example.com');

        $this->postJson('/api/login/verify', [
            'challenge' => $firstChallenge,
            'code' => $secondCode,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'freddie@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'freddie@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_access_user_endpoint(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_expired_token_is_rejected_as_unauthenticated(): void
    {
        config()->set('sanctum.expiration', 60);

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $accessToken = PersonalAccessToken::findToken($token);

        $this->assertNotNull($accessToken);

        $accessToken->forceFill([
            'created_at' => now()->subMinutes(61),
        ])->save();

        Auth::forgetGuards();

        $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_access_user_endpoint(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test')->plainTextToken;
        $accessToken = PersonalAccessToken::findToken($token);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertNoContent();

        $this->assertNotNull($accessToken);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $accessToken->id,
        ]);

        Auth::forgetGuards();

        $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        User::factory()->create([
            'email' => 'limited@example.com',
            'password' => Hash::make('password123'),
        ]);

        RateLimiter::clear('limited@example.com|127.0.0.1');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'limited@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/login', [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    private function beginLogin(string $email = 'freddie@example.com'): array
    {
        Mail::fake();
        $user = User::factory()->create(['email' => $email, 'password' => Hash::make('password123')]);
        $response = $this->postJson('/api/login', ['email' => $email, 'password' => 'password123'])->assertOk();
        $code = null;
        Mail::assertSent(LoginCode::class, function (LoginCode $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return [$user, $response->json('challenge'), $code];
    }
}

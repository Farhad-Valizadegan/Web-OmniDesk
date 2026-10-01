<?php

namespace Tests\Feature;

use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CaptchaConfigurationTest extends TestCase
{
    public function test_local_development_can_explicitly_disable_captcha(): void
    {
        $this->app->instance('env', 'local');
        config(['captcha.enabled' => false]);

        $service = app(CaptchaService::class);
        $this->assertSame('disabled', $service->challenge()['driver']);
        $service->verify(Request::create('/api/login', 'POST'));
    }

    public function test_production_cannot_disable_captcha_with_local_setting(): void
    {
        $this->app->instance('env', 'production');
        config(['captcha.enabled' => false, 'captcha.driver' => 'internal']);

        $service = app(CaptchaService::class);
        $this->assertSame('internal', $service->challenge()['driver']);
        $this->expectException(ValidationException::class);
        $service->verify(Request::create('/api/login', 'POST'));
    }

    public function test_enabled_local_challenge_accepts_one_correct_answer_only(): void
    {
        $this->app->instance('env', 'local');
        config(['captcha.enabled' => true, 'captcha.driver' => 'internal']);

        $service = app(CaptchaService::class);
        $challenge = $service->challenge();
        preg_match_all('/\d+/', $challenge['question'], $numbers);
        $request = Request::create('/api/login', 'POST', [
            'captcha_token' => $challenge['token'],
            'captcha_answer' => (string) array_sum($numbers[0]),
        ]);

        $service->verify($request);
        $this->expectException(ValidationException::class);
        $service->verify($request);
    }
}

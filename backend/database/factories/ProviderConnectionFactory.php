<?php

namespace Database\Factories;

use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderConnection>
 */
class ProviderConnectionFactory extends Factory
{
    protected $model = ProviderConnection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'gmail',
            'provider_account_id' => fake()->uuid(),
            'account_email' => fake()->unique()->safeEmail(),
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'token_expires_at' => now()->addHour(),
            'scopes' => [
                'openid',
                'email',
                'https://www.googleapis.com/auth/gmail.readonly',
            ],
            'status' => 'connected',
            'gmail_sync_completed' => true,
        ];
    }
}

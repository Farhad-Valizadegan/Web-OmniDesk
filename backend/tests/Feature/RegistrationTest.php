<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Freddie',
            'email' => 'freddie@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('user.email', 'freddie@example.com')
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'freddie@example.com',
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_duplicate_email_registration_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'freddie@example.com',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'freddie@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_confirmation_is_required(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Freddie',
            'email' => 'freddie@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }
}

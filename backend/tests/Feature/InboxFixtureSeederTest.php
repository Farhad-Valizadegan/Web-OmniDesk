<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InboxFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxFixtureSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_fixture_seeder_creates_conversations_and_messages(): void
    {
        User::factory()->create();

        $this->seed(InboxFixtureSeeder::class);

        $this->assertDatabaseCount('conversations', 3);
        $this->assertDatabaseCount('messages', 6);
        $this->assertDatabaseHas('conversations', [
            'provider' => 'fixture',
            'subject' => 'Project Alpha planning',
        ]);
    }

    public function test_inbox_fixture_seeder_is_idempotent(): void
    {
        User::factory()->create();

        $this->seed(InboxFixtureSeeder::class);
        $this->seed(InboxFixtureSeeder::class);

        $this->assertDatabaseCount('conversations', 3);
        $this->assertDatabaseCount('messages', 6);
    }

    public function test_inbox_fixture_seeder_does_not_create_a_user(): void
    {
        $this->seed(InboxFixtureSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('conversations', 0);
        $this->assertDatabaseCount('messages', 0);
    }
}

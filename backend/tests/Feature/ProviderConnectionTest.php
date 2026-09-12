<?php

namespace Tests\Feature;

use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProviderConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_provider_connections(): void
    {
        $user = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($user)
            ->create();

        $this->assertTrue($connection->user->is($user));
        $this->assertCount(1, $user->providerConnections);
        $this->assertSame('gmail', $connection->provider);
        $this->assertSame('connected', $connection->status);
    }

    public function test_provider_credentials_are_encrypted_in_database(): void
    {
        $connection = ProviderConnection::factory()->create([
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
        ]);

        $stored = DB::table('provider_connections')
            ->where('id', $connection->id)
            ->first();

        $this->assertNotNull($stored);
        $this->assertNotSame('test-access-token', $stored->access_token);
        $this->assertNotSame('test-refresh-token', $stored->refresh_token);
        $this->assertSame('test-access-token', $connection->fresh()->access_token);
        $this->assertSame('test-refresh-token', $connection->fresh()->refresh_token);
    }

    public function test_provider_connection_hides_credentials_when_serialized(): void
    {
        $connection = ProviderConnection::factory()->create();

        $serialized = $connection->toArray();

        $this->assertArrayNotHasKey('access_token', $serialized);
        $this->assertArrayNotHasKey('refresh_token', $serialized);
        $this->assertIsArray($serialized['scopes']);
    }
}

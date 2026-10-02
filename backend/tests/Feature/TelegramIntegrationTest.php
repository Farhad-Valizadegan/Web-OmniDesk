<?php

namespace Tests\Feature;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\TelegramAdapterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TelegramIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_their_telegram_connection_status(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        ProviderConnection::factory()->create(['user_id' => $owner->id, 'provider' => 'telegram', 'status' => 'connected', 'telegram_phone' => '+49111111111']);
        ProviderConnection::factory()->create(['user_id' => $other->id, 'provider' => 'telegram', 'status' => 'awaiting_code', 'telegram_phone' => '+49222222222']);

        $this->actingAs($owner, 'sanctum')->getJson('/api/integrations/telegram')
            ->assertOk()->assertJson(['connected' => true, 'status' => 'connected', 'phone' => '+49'.str_repeat('•', 6).'111'])
            ->assertJsonMissing(['phone' => '+49222222222']);
    }

    public function test_pending_connection_status_is_reconciled_with_tdlib(): void
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->create([
            'user_id' => $user->id,
            'provider' => 'telegram',
            'status' => 'pending',
            'telegram_phone' => '+49123456789',
            'telegram_database_key' => bin2hex(random_bytes(32)),
        ]);
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldReceive('authorize')->once()->with(Mockery::on(fn ($candidate) => $candidate->is($connection)), 'status')->andReturn(['status' => 'awaiting_code']);
        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')->getJson('/api/integrations/telegram')
            ->assertOk()->assertJson(['connected' => false, 'status' => 'awaiting_code']);
        $this->assertDatabaseHas('provider_connections', ['id' => $connection->id, 'status' => 'awaiting_code']);
    }

    public function test_connect_starts_authorization_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldReceive('authorize')->once()->with(Mockery::on(fn ($connection) => $connection->user_id === $user->id), 'phone', '+49123456789')->andReturn(['status' => 'awaiting_code']);
        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')->postJson('/api/integrations/telegram/connect', ['phone' => '+49123456789'])
            ->assertOk()->assertJson(['status' => 'awaiting_code']);
        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'telegram', 'status' => 'awaiting_code']);
    }

    public function test_connect_normalizes_a_double_zero_international_phone_number(): void
    {
        $user = User::factory()->create();
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldReceive('authorize')->once()->with(Mockery::on(fn ($connection) => $connection->user_id === $user->id), 'phone', '+989194406997')->andReturn(['status' => 'awaiting_code']);
        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')->postJson('/api/integrations/telegram/connect', ['phone' => '00989194406997'])
            ->assertOk()->assertJson(['status' => 'awaiting_code']);
        $this->assertSame('+989194406997', ProviderConnection::query()->whereBelongsTo($user)->where('provider', 'telegram')->firstOrFail()->telegram_phone);
    }

    public function test_code_and_password_continue_only_the_current_users_connection(): void
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->create(['user_id' => $user->id, 'provider' => 'telegram', 'status' => 'awaiting_code']);
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldReceive('authorize')->once()->with(Mockery::on(fn ($candidate) => $candidate->is($connection)), 'code', '12345')->andReturn(['status' => 'awaiting_password']);
        $adapter->shouldReceive('authorize')->once()->with(Mockery::on(fn ($candidate) => $candidate->is($connection)), 'password', 'secret-pass')->andReturn(['status' => 'connected']);
        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')->postJson('/api/integrations/telegram/code', ['code' => '12345'])->assertOk()->assertJson(['status' => 'awaiting_password']);
        $this->actingAs($user, 'sanctum')->postJson('/api/integrations/telegram/password', ['password' => 'secret-pass'])->assertOk()->assertJson(['status' => 'connected']);
    }

    public function test_connected_user_can_discover_their_telegram_chats(): void
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->create(['user_id' => $user->id, 'provider' => 'telegram', 'status' => 'connected']);
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldReceive('fetchContentSample')->once()->with(Mockery::on(fn ($candidate) => $candidate->is($connection)), null, 20, null)->andReturn([
            'provider' => 'telegram', 'chats' => [['id' => 100, 'type' => 'private', 'tdlib_type' => 'chatTypePrivate', 'title' => 'Own chat', 'unread_count' => 0, 'last_message' => 'Hello', 'last_message_date' => 200]],
            'selected_chat' => ['id' => 100, 'type' => 'private', 'tdlib_type' => 'chatTypePrivate', 'title' => 'Own chat', 'unread_count' => 0, 'last_message' => 'Hello', 'last_message_date' => 200],
            'messages' => [], 'has_more' => false, 'next_before_message_id' => null,
        ]);
        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')->getJson('/api/integrations/telegram/sample')->assertOk()->assertJsonPath('data.chats.0.title', 'Own chat');
    }

    public function test_user_without_connected_telegram_cannot_read_content(): void
    {
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldNotReceive('fetchContentSample');
        $this->app->instance(TelegramAdapterService::class, $adapter);
        $this->actingAs(User::factory()->create(), 'sanctum')->getJson('/api/integrations/telegram/sample')->assertNotFound();
    }

    public function test_provider_errors_are_safe(): void
    {
        $user = User::factory()->create();
        ProviderConnection::factory()->create(['user_id' => $user->id, 'provider' => 'telegram', 'status' => 'connected']);
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldReceive('fetchContentSample')->once()->andThrow(new ProviderException('telegram', ProviderErrorType::Temporary, 'Telegram request timed out.', null, 'history_initial'));
        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')->getJson('/api/integrations/telegram/sample?chat_id=100')->assertStatus(503)->assertJson(['error' => 'telegram_temporary', 'provider' => 'telegram']);
    }

    public function test_disconnect_marks_cleanup_before_removing_the_connection(): void
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->create([
            'user_id' => $user->id,
            'provider' => 'telegram',
            'status' => 'connected',
        ]);
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldReceive('authorize')->once()->with(Mockery::on(fn ($candidate) => $candidate->is($connection)), 'logout')->andReturn(['status' => 'disconnected']);
        $adapter->shouldReceive('authorize')->once()->with(Mockery::on(fn ($candidate) => $candidate->is($connection)), 'shutdown')->andReturn(['status' => 'disconnected']);
        $adapter->shouldReceive('deleteRuntime')->once()->with(Mockery::on(function ($candidate) use ($connection): bool {
            $this->assertSame('disconnecting', $candidate->fresh()->status);

            return $candidate->is($connection);
        }));
        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/integrations/telegram')->assertOk();
        $this->assertDatabaseMissing('provider_connections', ['id' => $connection->id]);
    }

    public function test_status_retries_cleanup_left_by_an_interrupted_disconnect(): void
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->create([
            'user_id' => $user->id,
            'provider' => 'telegram',
            'status' => 'disconnecting',
        ]);
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldReceive('deleteRuntime')->once()->with(Mockery::on(fn ($candidate) => $candidate->is($connection)));
        $adapter->shouldNotReceive('authorize');
        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')->getJson('/api/integrations/telegram')
            ->assertOk()
            ->assertJson(['connected' => false, 'status' => 'disconnected', 'phone' => null]);
        $this->assertDatabaseMissing('provider_connections', ['id' => $connection->id]);
    }

    public function test_runtime_cleanup_removes_only_the_connections_private_directory(): void
    {
        $connection = ProviderConnection::factory()->create([
            'provider' => 'telegram',
            'status' => 'disconnecting',
        ]);
        $root = storage_path('framework/testing/telegram-cleanup-'.bin2hex(random_bytes(6)));
        config(['telegram.runtime_directory' => $root]);
        $service = app(TelegramAdapterService::class);
        $runtime = $service->runtimeDirectory($connection);
        mkdir($runtime.DIRECTORY_SEPARATOR.'auth-ipc'.DIRECTORY_SEPARATOR.'commands', 0700, true);
        file_put_contents($runtime.DIRECTORY_SEPARATOR.'auth-ipc'.DIRECTORY_SEPARATOR.'commands'.DIRECTORY_SEPARATOR.'stale.json', 'sensitive transient value');
        file_put_contents($root.DIRECTORY_SEPARATOR.'keep.txt', 'other runtime data');

        $service->deleteRuntime($connection);

        $this->assertDirectoryDoesNotExist($runtime);
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'keep.txt');
        unlink($root.DIRECTORY_SEPARATOR.'keep.txt');
        rmdir($root);
    }

    public function test_unauthenticated_users_cannot_access_telegram(): void
    {
        $this->getJson('/api/integrations/telegram')->assertUnauthorized();
        $this->postJson('/api/integrations/telegram/connect', ['phone' => '+49123456789'])->assertUnauthorized();
        $this->getJson('/api/integrations/telegram/sample')->assertUnauthorized();
    }
}

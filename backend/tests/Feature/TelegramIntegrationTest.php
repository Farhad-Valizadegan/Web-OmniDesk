<?php

namespace Tests\Feature;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Models\User;
use App\Services\TelegramAdapterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TelegramIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_discover_telegram_chats(): void
    {
        $user = User::factory()->create();
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter
            ->shouldReceive('fetchContentSample')
            ->once()
            ->with(null, 20, null)
            ->andReturn([
                'provider' => 'telegram',
                'chats' => [
                    [
                        'id' => 100,
                        'type' => 'channel',
                        'tdlib_type' => 'chatTypeSupergroup',
                        'title' => 'News channel',
                        'unread_count' => 2,
                        'last_message' => 'Latest news',
                        'last_message_date' => 200,
                    ],
                ],
                'selected_chat' => [
                    'id' => 100,
                    'type' => 'channel',
                    'tdlib_type' => 'chatTypeSupergroup',
                    'title' => 'News channel',
                    'unread_count' => 2,
                    'last_message' => 'Latest news',
                    'last_message_date' => 200,
                ],
                'messages' => [],
                'has_more' => false,
                'next_before_message_id' => null,
            ]);

        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/integrations/telegram/sample')
            ->assertOk()
            ->assertJsonPath('data.provider', 'telegram')
            ->assertJsonPath('data.chats.0.id', 100)
            ->assertJsonPath('data.selected_chat.title', 'News channel')
            ->assertJsonCount(0, 'data.messages');
    }

    public function test_selected_chat_history_passes_only_selected_chat_and_cursor(): void
    {
        $user = User::factory()->create();
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter
            ->shouldReceive('fetchContentSample')
            ->once()
            ->with(100, 20, 55)
            ->andReturn([
                'provider' => 'telegram',
                'chats' => [],
                'selected_chat' => [
                    'id' => 100,
                    'type' => 'channel',
                    'tdlib_type' => 'chatTypeSupergroup',
                    'title' => 'News channel',
                    'unread_count' => 0,
                    'last_message' => 'Older news',
                    'last_message_date' => 150,
                    'messages_count' => 1,
                ],
                'messages' => [
                    [
                        'id' => 54,
                        'chat_id' => 100,
                        'date' => 150,
                        'sender_id' => null,
                        'text' => 'Older news',
                    ],
                ],
                'has_more' => false,
                'next_before_message_id' => null,
            ]);

        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/integrations/telegram/sample', [
                'chat_id' => 100,
                'limit' => 20,
                'before_message_id' => 55,
            ])
            ->assertOk()
            ->assertJsonPath('data.messages.0.chat_id', 100)
            ->assertJsonPath('data.messages.0.id', 54);
    }

    public function test_telegram_provider_error_is_safe_and_operation_specific(): void
    {
        $user = User::factory()->create();
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter
            ->shouldReceive('fetchContentSample')
            ->once()
            ->andThrow(new ProviderException(
                'telegram',
                ProviderErrorType::Temporary,
                'Telegram request timed out.',
                null,
                'history_initial',
            ));

        $this->app->instance(TelegramAdapterService::class, $adapter);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/integrations/telegram/sample', [
                'chat_id' => 100,
            ])
            ->assertStatus(503)
            ->assertJson([
                'error' => 'telegram_temporary_failure',
                'provider' => 'telegram',
                'operation' => 'history_initial',
                'retryable' => true,
            ])
            ->assertJsonMissing([
                'message' => 'Sensitive TDLib traceback',
            ]);
    }

    public function test_unauthenticated_users_cannot_load_telegram(): void
    {
        $this->getJson('/api/integrations/telegram/sample')
            ->assertUnauthorized();
    }
}

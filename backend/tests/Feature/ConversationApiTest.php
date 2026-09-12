<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_owned_conversations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Conversation::factory()
            ->for($user)
            ->create(['subject' => 'Owned conversation']);

        Conversation::factory()
            ->for($otherUser)
            ->create(['subject' => 'Other conversation']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/conversations');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Owned conversation');
    }

    public function test_unauthenticated_user_cannot_list_conversations(): void
    {
        $this->getJson('/api/conversations')
            ->assertUnauthorized();
    }

    public function test_user_can_search_and_filter_owned_conversations(): void
    {
        $user = User::factory()->create();

        Conversation::factory()
            ->for($user)
            ->create([
                'subject' => 'Project Alpha',
                'status' => 'open',
                'is_read' => false,
                'labels' => ['work'],
            ]);

        Conversation::factory()
            ->for($user)
            ->create([
                'subject' => 'Personal note',
                'status' => 'archived',
                'is_read' => true,
                'labels' => ['personal'],
            ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/conversations?search=Project&status=open&is_read=0&label=work');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Project Alpha');
    }

    public function test_user_cannot_access_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::factory()
            ->for($otherUser)
            ->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}")
            ->assertNotFound();
    }

    public function test_user_can_view_owned_conversation_and_messages(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::factory()
            ->for($user)
            ->create();

        Message::factory()
            ->for($conversation)
            ->create([
                'sent_at' => now()->subMinute(),
                'body' => 'Earlier message',
            ]);

        Message::factory()
            ->for($conversation)
            ->create([
                'sent_at' => now(),
                'body' => 'Latest message',
            ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('conversation.id', $conversation->id);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.body', 'Earlier message')
            ->assertJsonPath('data.1.body', 'Latest message');
    }

    public function test_user_cannot_view_another_users_messages(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::factory()
            ->for($otherUser)
            ->create();

        Message::factory()
            ->for($conversation)
            ->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertNotFound();
    }
}

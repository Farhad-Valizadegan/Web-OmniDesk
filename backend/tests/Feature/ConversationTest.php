<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_conversations_and_messages(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::factory()
            ->for($user)
            ->create();

        Message::factory()
            ->count(2)
            ->for($conversation)
            ->create();

        $this->assertTrue($conversation->user->is($user));
        $this->assertCount(1, $user->conversations);
        $this->assertCount(2, $conversation->messages);
    }

    public function test_message_belongs_to_its_conversation(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::factory()
            ->for($user)
            ->create();

        $message = Message::factory()
            ->for($conversation)
            ->create();

        $this->assertTrue($message->conversation->is($conversation));
        $this->assertTrue($conversation->user->is($user));
    }

    public function test_conversation_factory_contains_provider_neutral_data(): void
    {
        $conversation = Conversation::factory()->create();

        $this->assertSame('internal', $conversation->provider);
        $this->assertNotEmpty($conversation->provider_conversation_id);
        $this->assertIsArray($conversation->labels);
        $this->assertIsBool($conversation->is_read);
    }

    public function test_message_factory_contains_provider_neutral_data(): void
    {
        $message = Message::factory()->create();

        $this->assertSame('internal', $message->provider);
        $this->assertNotEmpty($message->provider_message_id);
        $this->assertNotEmpty($message->body);
        $this->assertIsArray($message->metadata);
        $this->assertIsBool($message->is_read);
    }
}

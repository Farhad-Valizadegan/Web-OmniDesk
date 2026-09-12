<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'provider' => 'internal',
            'provider_message_id' => (string) Str::uuid(),
            'sender_name' => fake()->name(),
            'sender_address' => fake()->safeEmail(),
            'recipient_name' => fake()->name(),
            'recipient_address' => fake()->safeEmail(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'sent_at' => now(),
            'is_read' => fake()->boolean(70),
            'metadata' => [],
        ];
    }
}

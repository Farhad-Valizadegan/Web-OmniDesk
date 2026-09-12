<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'internal',
            'provider_conversation_id' => (string) Str::uuid(),
            'subject' => fake()->sentence(4),
            'status' => fake()->randomElement(['open', 'archived']),
            'is_read' => fake()->boolean(70),
            'last_message_at' => now(),
            'labels' => fake()->randomElements(
                ['important', 'work', 'personal'],
                fake()->numberBetween(0, 2),
            ),
        ];
    }
}

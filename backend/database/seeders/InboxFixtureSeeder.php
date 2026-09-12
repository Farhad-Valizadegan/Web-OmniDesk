<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class InboxFixtureSeeder extends Seeder
{
    /**
     * Seed local inbox fixture data for the first existing user.
     */
    public function run(): void
    {
        $user = User::query()->first();

        if ($user === null) {
            $this->command?->warn(
                'No user exists. Register a user before loading inbox fixtures.',
            );

            return;
        }

        if ($user->conversations()->where('provider', 'fixture')->exists()) {
            $this->command?->info(
                'Inbox fixtures already exist for the first user.',
            );

            return;
        }

        $this->createConversation(
            user: $user,
            subject: 'Project Alpha planning',
            labels: ['work', 'important'],
            isRead: false,
            firstBody: 'Can we review the Project Alpha plan?',
            secondBody: 'The updated plan is ready for review.',
        );

        $this->createConversation(
            user: $user,
            subject: 'Welcome to OmniDesk AI',
            labels: ['personal'],
            isRead: true,
            firstBody: 'Welcome to your OmniDesk AI workspace.',
            secondBody: 'Your unified inbox foundation is ready.',
        );

        $this->createConversation(
            user: $user,
            subject: 'Sprint 03 progress',
            labels: ['work'],
            isRead: true,
            firstBody: 'The Sprint 03 data model is complete.',
            secondBody: 'The conversation API is now available.',
        );
    }

    private function createConversation(
        User $user,
        string $subject,
        array $labels,
        bool $isRead,
        string $firstBody,
        string $secondBody,
    ): void {
        $conversation = Conversation::factory()
            ->for($user)
            ->create([
                'provider' => 'fixture',
                'subject' => $subject,
                'labels' => $labels,
                'is_read' => $isRead,
                'status' => 'open',
                'last_message_at' => now(),
            ]);

        Message::factory()
            ->for($conversation)
            ->create([
                'provider' => 'fixture',
                'sent_at' => now()->subMinutes(10),
                'body' => $firstBody,
                'is_read' => true,
            ]);

        Message::factory()
            ->for($conversation)
            ->create([
                'provider' => 'fixture',
                'sent_at' => now(),
                'body' => $secondBody,
                'is_read' => $isRead,
            ]);
    }
}

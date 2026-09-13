<?php

namespace Tests\Feature;

use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\GmailMessageSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GmailSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_synchronizes_connected_gmail_accounts(): void
    {
        $user = User::factory()->create();

        ProviderConnection::factory()
            ->for($user)
            ->create();

        $syncService = Mockery::mock(GmailMessageSyncService::class);
        $syncService
            ->shouldReceive('sync')
            ->once()
            ->with(
                Mockery::on(
                    fn (User $syncedUser): bool => $syncedUser->is($user)
                ),
                20
            )
            ->andReturn([
                'threads_processed' => 2,
                'messages_processed' => 3,
                'next_page_token' => null,
            ]);

        $this->app->instance(GmailMessageSyncService::class, $syncService);

        $this->artisan('gmail:sync')
            ->expectsOutput(
                'User ' . $user->id
                . ': 2 threads and 3 messages synchronized.'
            )
            ->assertExitCode(0);
    }

    public function test_command_succeeds_when_no_gmail_accounts_are_connected(): void
    {
        $this->artisan('gmail:sync')
            ->expectsOutput('No connected Gmail accounts found.')
            ->assertExitCode(0);
    }

    public function test_command_reports_failure_without_exposing_provider_details(): void
    {
        $user = User::factory()->create();

        ProviderConnection::factory()
            ->for($user)
            ->create();

        $syncService = Mockery::mock(GmailMessageSyncService::class);
        $syncService
            ->shouldReceive('sync')
            ->once()
            ->andThrow(new \RuntimeException(
                'Sensitive Gmail provider detail'
            ));

        $this->app->instance(GmailMessageSyncService::class, $syncService);

        $this->artisan('gmail:sync')
            ->expectsOutput(
                'User ' . $user->id . ': synchronization failed.'
            )
            ->doesntExpectOutput('Sensitive Gmail provider detail')
            ->assertExitCode(1);
    }
}

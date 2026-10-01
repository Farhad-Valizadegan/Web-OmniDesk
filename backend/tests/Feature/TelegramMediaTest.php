<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TelegramAdapterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TelegramMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_requires_the_configured_owner(): void
    {
        $owner = User::factory()->create();
        $this->app->instance('env', 'local');
        config(['telegram.sample_owner_user_id' => $owner->id]);
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldNotReceive('fetchContentSample');
        $this->app->instance(TelegramAdapterService::class, $adapter);
        $this->getJson('/api/integrations/telegram/media?chat_id=1&message_id=2')->assertUnauthorized();
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/integrations/telegram/media?chat_id=1&message_id=2')->assertNotFound();
        $this->app->instance('env', 'production');
        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/integrations/telegram/media?chat_id=1&message_id=2')->assertNotFound();
    }

    public function test_media_streams_an_image_but_rejects_paths_outside_runtime(): void
    {
        $owner = User::factory()->create();
        $this->app->instance('env', 'local');
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'telegram-media-'.uniqid();
        mkdir($root);
        $path = $root.DIRECTORY_SEPARATOR.'photo.png';
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j7O8AAAAASUVORK5CYII='));
        config(['telegram.sample_owner_user_id' => $owner->id, 'telegram.runtime_directory' => $root]);
        $adapter = Mockery::mock(TelegramAdapterService::class);
        $adapter->shouldReceive('fetchContentSample')->with(1, 1, null, 2)->twice()
            ->andReturn(['media_path' => $path], ['media_path' => base_path('composer.json')]);
        $this->app->instance(TelegramAdapterService::class, $adapter);
        try {
            $this->actingAs($owner, 'sanctum')->get('/api/integrations/telegram/media?chat_id=1&message_id=2')
                ->assertOk()->assertHeader('Content-Type', 'image/png')
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->get('/api/integrations/telegram/media?chat_id=1&message_id=2')->assertNotFound();
        } finally {
            unlink($path);
            rmdir($root);
        }
    }
}

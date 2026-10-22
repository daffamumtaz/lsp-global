<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_forwarded_https_is_used_for_backend_media_urls(): void
    {
        config(['trustedproxy.proxies' => '*']);
        $response = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->getJson('/api/landing-page')->assertOk();

        $this->assertStringStartsWith('https://', $response->json('data.hero.video_url'));
    }
}

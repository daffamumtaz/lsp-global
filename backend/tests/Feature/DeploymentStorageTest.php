<?php

namespace Tests\Feature;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToSetVisibility;
use Psr\Http\Message\RequestInterface;
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

    public function test_public_upload_and_visibility_do_not_use_object_acls(): void
    {
        $requests = [];
        $disk = Storage::build([
            'driver' => 'supabase',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'ap-southeast-1',
            'bucket' => 'website-media',
            'endpoint' => 'https://storage.example.test/storage/v1/s3',
            'url' => 'https://storage.example.test/storage/v1/object/public/website-media',
            'use_path_style_endpoint' => true,
            'visibility' => 'public',
            'throw' => true,
            'http_handler' => function (RequestInterface $request) use (&$requests) {
                $requests[] = $request;

                return Create::promiseFor(new Response(200, ['ETag' => 'test-etag']));
            },
        ]);

        $this->assertTrue($disk->put('gallery/photo.jpg', 'image', 'public'));
        $this->assertTrue($disk->setVisibility('gallery/photo.jpg', 'public'));
        $this->assertSame('public', $disk->getVisibility('gallery/photo.jpg'));
        $this->assertSame('https://storage.example.test/storage/v1/object/public/website-media/gallery/photo.jpg', $disk->url('gallery/photo.jpg'));
        $this->assertCount(1, $requests);
        $this->assertSame('PUT', $requests[0]->getMethod());
        $this->assertFalse($requests[0]->hasHeader('x-amz-acl'));
        $this->assertStringNotContainsString('acl', $requests[0]->getUri()->getQuery());

        $this->expectException(UnableToSetVisibility::class);
        $disk->setVisibility('gallery/photo.jpg', 'private');
    }
}

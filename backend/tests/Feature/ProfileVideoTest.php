<?php

namespace Tests\Feature;

use App\Filament\Resources\SitePages\Pages\ManageSitePages;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProfileVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_video_is_public_and_cacheable(): void
    {
        $response = $this->get('/media/profile-video');

        $response->assertOk()
            ->assertHeader('Content-Type', 'video/webm')
            ->assertHeader('Content-Length', '30441628')
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeaderContains('Cache-Control', 'public')
            ->assertHeaderContains('Cache-Control', 'max-age=86400')
            ->assertHeader('Last-Modified');
        $this->assertSame(resource_path('media/video-profile-lfRo5LN6.webm'), $response->baseResponse->getFile()->getPathname());
    }

    public function test_head_returns_video_headers_without_a_body(): void
    {
        $response = $this->head('/media/profile-video');

        $response->assertOk()->assertHeader('Content-Type', 'video/webm')
            ->assertHeader('Content-Length', '30441628');
        $this->assertEmpty($response->streamedContent());
    }

    public function test_byte_range_returns_only_requested_video_bytes(): void
    {
        $response = $this->get('/media/profile-video', ['Range' => 'bytes=100-115']);

        $response->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 100-115/30441628')
            ->assertHeader('Content-Length', '16');
        $this->assertSame(file_get_contents(resource_path('media/video-profile-lfRo5LN6.webm'), false, null, 100, 16), $response->streamedContent());
    }

    #[TestWith([null])]
    #[TestWith([''])]
    public function test_api_and_editor_use_bundled_video_when_cms_path_is_empty(?string $path): void
    {
        $page = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $content = $page->landingContent();
        $content['hero']['video_path'] = $path;
        $page->update(['content' => $content]);

        $this->getJson('/api/landing-page')->assertOk()
            ->assertJsonPath('data.hero.video_url', url('/media/profile-video'));

        $this->assertSame(url('/media/profile-video'), (new ManageSitePages)->videoUrl());
    }

    public function test_cms_upload_overrides_bundled_video_in_api_and_editor(): void
    {
        $page = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $content = $page->landingContent();
        $content['hero']['video_path'] = 'landing/hero/custom.webm';
        $page->update(['content' => $content]);

        $this->getJson('/api/landing-page')->assertOk()
            ->assertJsonPath('data.hero.video_url', url('/storage/landing/hero/custom.webm'));

        $this->assertSame(Storage::disk('public')->url('landing/hero/custom.webm'), (new ManageSitePages)->videoUrl());
    }
}

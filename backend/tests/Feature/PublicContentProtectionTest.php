<?php

namespace Tests\Feature;

use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\GalleryItem;
use App\Models\Tuk;
use App\Observers\ContentDirectoryObserver;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicContentProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--no-interaction' => true])->assertSuccessful();
        RefreshDatabaseState::$migrated = false;
    }

    public function test_public_content_shares_a_configurable_per_ip_limit_including_preview(): void
    {
        config(['security.public_content_requests_per_minute' => 2]);
        $this->getJson('/api/content-directory')->assertOk();
        $this->getJson('/api/processes')->assertOk();
        $this->getJson('/api/landing-page')->assertTooManyRequests()->assertHeader('Retry-After');
        $preview = URL::temporarySignedRoute('api.processes.preview', now()->addMinute(), ['slug' => 'missing'], absolute: false);
        $this->getJson($preview)->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])->getJson('/api/content-directory')->assertOk();
    }

    public function test_preview_still_requires_a_valid_signature(): void
    {
        $this->getJson('/api/processes/missing/preview')->assertForbidden();
    }

    public function test_directory_reuses_queries_then_expires_after_thirty_seconds(): void
    {
        $this->freezeTime();
        $this->getJson('/api/content-directory')->assertOk();
        DB::enableQueryLog();
        $this->getJson('/api/content-directory')->assertOk();
        $this->assertSame([], DB::getQueryLog());
        $this->travel(31)->seconds();
        $this->getJson('/api/content-directory')->assertOk();
        $this->assertNotEmpty(DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_database_cache_preserves_the_complete_directory_payload(): void
    {
        Storage::fake('public');
        config(['cache.default' => 'database', 'cache.serializable_classes' => false]);
        Assessor::factory()->create(['name' => 'Cached assessor']);
        CertificationScheme::factory()->create();
        $album = GalleryItem::factory()->create();
        $album->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('gallery');

        $original = $this->getJson('/api/content-directory')->assertOk()->json();
        $this->getJson('/api/content-directory')->assertOk()->assertExactJson($original);
    }

    public static function directoryModels(): array
    {
        return ['tuks' => [Tuk::class, 'name'], 'assessors' => [Assessor::class, 'name'], 'schemes' => [CertificationScheme::class, 'name'], 'gallery' => [GalleryItem::class, 'title']];
    }

    #[DataProvider('directoryModels')]
    public function test_committed_create_update_and_delete_are_immediately_public(string $model, string $field): void
    {
        $this->getJson('/api/content-directory')->assertOk();
        $record = $model::factory()->create([$field => 'Initial directory record']);
        $this->getJson('/api/content-directory')->assertJsonFragment([$field => 'Initial directory record']);
        $record->update([$field => 'Updated directory record']);
        $this->getJson('/api/content-directory')->assertJsonFragment([$field => 'Updated directory record'])->assertJsonMissing([$field => 'Initial directory record']);
        $record->delete();
        $this->getJson('/api/content-directory')->assertJsonMissing([$field => 'Updated directory record']);
    }

    public function test_transactions_bypass_shared_cache_and_rollback_does_not_publish_dirty_data(): void
    {
        $record = Assessor::factory()->create(['name' => 'Committed name']);
        $original = $this->getJson('/api/content-directory')->json();
        $version = Cache::get(ContentDirectoryObserver::CACHE_VERSION_KEY);
        DB::beginTransaction();
        try {
            $record->update(['name' => 'Uncommitted name']);
            $this->getJson('/api/content-directory')->assertJsonFragment(['name' => 'Uncommitted name']);
            $this->assertSame($version, Cache::get(ContentDirectoryObserver::CACHE_VERSION_KEY));
        } finally {
            DB::rollBack();
        }
        $this->assertSame($version, Cache::get(ContentDirectoryObserver::CACHE_VERSION_KEY));
        $this->getJson('/api/content-directory')->assertExactJson($original);
        DB::transaction(fn () => $record->refresh()->update(['name' => 'New committed name']));
        $this->getJson('/api/content-directory')->assertJsonFragment(['name' => 'New committed name']);
    }

    public function test_uncommitted_response_cannot_populate_an_empty_shared_cache(): void
    {
        Cache::flush();
        DB::beginTransaction();
        try {
            Assessor::factory()->create(['name' => 'Rolled back assessor']);
            $this->getJson('/api/content-directory')->assertJsonFragment(['name' => 'Rolled back assessor']);
            $this->assertNull(Cache::get(ContentDirectoryObserver::CACHE_VERSION_KEY));
        } finally {
            DB::rollBack();
        }
        $this->getJson('/api/content-directory')->assertJsonMissing(['name' => 'Rolled back assessor']);
    }

    public function test_gallery_media_writes_invalidate_the_cached_public_media_list(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create();
        $this->getJson('/api/content-directory')->assertJsonCount(0, 'data.gallery.0.media');

        $media = $item->addMedia(UploadedFile::fake()->image('gallery.jpg'))->toMediaCollection('gallery');
        $this->getJson('/api/content-directory')->assertJsonPath('data.gallery.0.media.0.src', $media->getFullUrl());
        $media->update(['file_name' => 'renamed.jpg']);
        $this->getJson('/api/content-directory')->assertJsonPath('data.gallery.0.media.0.src', $media->getFullUrl());
        $media->delete();
        $this->getJson('/api/content-directory')->assertJsonCount(0, 'data.gallery.0.media');
    }
}

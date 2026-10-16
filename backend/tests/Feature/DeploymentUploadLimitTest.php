<?php

namespace Tests\Feature;

use App\Models\GalleryItem;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Tests\TestCase;

class DeploymentUploadLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_uploads_enforce_the_configured_storage_limit(): void
    {
        config(['filesystems.max_upload_size_kb' => 1024]);
        (new AppServiceProvider($this->app))->register();

        $this->assertTrue(Validator::make(['file' => UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4')], ['file' => config('livewire.temporary_file_upload.rules')])->passes());
        $this->assertTrue(Validator::make(['file' => UploadedFile::fake()->create('video.mp4', 1025, 'video/mp4')], ['file' => config('livewire.temporary_file_upload.rules')])->fails());
    }

    public function test_gallery_uploads_cannot_bypass_the_configured_storage_limit(): void
    {
        Storage::fake('public');
        config(['filesystems.max_upload_size_kb' => 1024]);
        (new AppServiceProvider($this->app))->register();
        $album = GalleryItem::factory()->create();
        $file = UploadedFile::fake()->image('large.jpg');
        $stream = fopen($file->getRealPath(), 'c+');
        ftruncate($stream, 1048577);
        fclose($stream);
        clearstatcache();

        $this->expectException(FileIsTooBig::class);
        $album->addMedia($file)->toMediaCollection('gallery');
    }
}

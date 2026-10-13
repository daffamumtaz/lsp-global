<?php

namespace Tests\Feature;

use App\Models\GalleryItem;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Tests\TestCase;

class MediaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_upload_and_reorder_are_audited_with_the_admin_actor(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $item = GalleryItem::factory()->create();
        $media = $item->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('gallery');
        $media->update(['order_column' => 2]);
        $log = Activity::query()->where('subject_type', Media::class)->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame(1, $log->attribute_changes['old']['order_column']);
        $this->assertSame(2, $log->attribute_changes['attributes']['order_column']);
    }

    public function test_rolled_back_upload_removes_new_file_and_media_record(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create();
        DB::beginTransaction();
        $media = $item->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('gallery');
        $path = $media->getPathRelativeToRoot();
        Storage::disk('public')->assertExists($path);
        DB::rollBack();
        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_storage_failure_rolls_back_media_and_activity(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create();
        $filesystem = $this->mock(Filesystem::class);
        $filesystem->shouldReceive('add')->once()->andThrow(UnableToWriteFile::atLocation('gallery'));
        $filesystem->shouldReceive('removeAllFiles')->once();
        try {
            DB::transaction(fn () => $item->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('gallery'));
            $this->fail('A failed storage write was accepted.');
        } catch (UnableToWriteFile) {
            $this->assertDatabaseCount('media', 0);
            $this->assertModelExists($item);
        }
    }

    public function test_gallery_accepts_more_than_twenty_files(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create();
        for ($index = 0; $index < 21; $index++) {
            $item->addMedia(UploadedFile::fake()->image("photo-$index.jpg"))->toMediaCollection('gallery');
        }
        $this->assertSame(21, $item->fresh()->getMedia('gallery')->count());
    }

    public function test_gallery_rejects_a_file_larger_than_one_hundred_megabytes(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create();
        $file = UploadedFile::fake()->image('large.jpg');
        $stream = fopen($file->getRealPath(), 'c+');
        ftruncate($stream, 104857601);
        fclose($stream);
        clearstatcache();
        $this->expectException(FileIsTooBig::class);
        $item->addMedia($file)->toMediaCollection('gallery');
    }

    public function test_gallery_rejects_files_outside_its_mime_allowlist(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create();
        $this->expectException(FileUnacceptableForCollection::class);
        $item->addMedia(UploadedFile::fake()->create('unsafe.html', 1, 'text/html'))->toMediaCollection('gallery');
    }

    public function test_rolled_back_gallery_deletion_preserves_referenced_file(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create();
        $media = $item->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('gallery');
        $path = $media->getPathRelativeToRoot();
        DB::beginTransaction();
        $item->delete();
        DB::rollBack();
        $this->assertModelExists($item);
        $this->assertModelExists($media);
        Storage::disk('public')->assertExists($path);
    }
}

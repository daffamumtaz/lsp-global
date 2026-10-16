<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyncPublicMediaToSupabaseTest extends TestCase
{
    public function test_sync_preserves_paths_and_existing_remote_uploads(): void
    {
        $source = Storage::fake('migration-source');
        $destination = Storage::fake('public');
        config(['filesystems.disks.public.driver' => 'supabase']);
        $source->put('gallery/album/photo.jpg', 'original photo');
        $source->put('identity/logo.png', 'old logo');
        $destination->put('identity/logo.png', 'new logo');

        $this->artisan('media:sync-to-supabase', ['--source' => $source->path('')])->assertSuccessful();

        $this->assertSame('original photo', $destination->get('gallery/album/photo.jpg'));
        $this->assertSame('new logo', $destination->get('identity/logo.png'));
        $source->assertExists('gallery/album/photo.jpg');
    }

    public function test_dry_run_does_not_upload_or_delete_files(): void
    {
        $source = Storage::fake('migration-source');
        $destination = Storage::fake('public');
        config(['filesystems.disks.public.driver' => 'supabase']);
        $source->put('gallery/photo.jpg', 'photo');

        $this->artisan('media:sync-to-supabase', ['--source' => $source->path(''), '--dry-run' => true])->assertSuccessful();

        $destination->assertMissing('gallery/photo.jpg');
        $source->assertExists('gallery/photo.jpg');
    }

    public function test_sync_refuses_a_local_destination(): void
    {
        Storage::fake('public');

        $this->artisan('media:sync-to-supabase')->assertFailed();
    }
}

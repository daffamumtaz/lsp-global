<?php

namespace Tests\Feature;

use App\Models\CertificationScheme;
use App\Models\SitePage;
use App\Models\Tuk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use Tests\TestCase;

class PrepareDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_recovery_resumes_a_failed_import_without_replacing_admin_content(): void
    {
        $storage = Storage::fake('public');
        $disk = Mockery::mock($storage);
        $disk->shouldReceive('put')->andReturnUsing(function (string $path, string $contents) use ($storage): bool {
            if (str_starts_with($path, 'profile/')) {
                throw UnableToWriteFile::atLocation($path, 'Storage unavailable');
            }

            return $storage->put($path, $contents);
        });
        Storage::set('public', $disk);

        try {
            Artisan::call('deployment:prepare', ['--import-content' => true]);
            $this->fail('The unavailable storage did not interrupt the asset import.');
        } catch (UnableToWriteFile $error) {
            $this->assertStringContainsString('Storage unavailable', $error->getMessage());
        }

        $scheme = CertificationScheme::query()->firstOrFail();
        $scheme->update(['name' => 'Skema milik admin']);
        $tuk = Tuk::query()->firstOrFail();
        $tuk->update(['address' => 'Alamat milik admin']);
        $schemeCount = CertificationScheme::query()->count();
        $tukCount = Tuk::query()->count();
        $identity = SitePage::firstOrCreate(['kind' => 'identity'], ['title' => 'Identitas', 'content' => SitePage::identityContent()]);
        $identity->update(['content' => [...$identity->content, 'logo_path' => 'admin/logo.png']]);
        Storage::fake('public');
        Storage::disk('public')->put('admin/logo.png', 'admin logo');

        $this->artisan('deployment:prepare', ['--import-assets' => true])->assertSuccessful();

        $this->assertSame('Skema milik admin', $scheme->refresh()->name);
        $this->assertSame('Alamat milik admin', $tuk->refresh()->address);
        $this->assertDatabaseCount('certification_schemes', $schemeCount);
        $this->assertDatabaseCount('tuks', $tukCount);
        $this->assertSame('admin/logo.png', $identity->refresh()->content['logo_path']);
        $this->assertSame('admin logo', Storage::disk('public')->get('admin/logo.png'));
        Storage::disk('public')->assertExists('landing/hero/profile-video.webm');

        $this->artisan('deployment:prepare', ['--import-assets' => true])->assertSuccessful();
        $this->assertSame('Skema milik admin', $scheme->refresh()->name);
    }

    public function test_import_modes_cannot_be_combined(): void
    {
        Storage::fake('public');

        $this->artisan('deployment:prepare', ['--import-content' => true, '--import-assets' => true])->assertFailed();

        $this->assertDatabaseCount('certification_schemes', 0);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_initial_import_populates_an_empty_directory_and_public_media(): void
    {
        Storage::fake('public');

        $this->artisan('deployment:prepare', ['--import-content' => true])->assertSuccessful();

        $this->assertGreaterThan(0, CertificationScheme::query()->count());
        $this->assertDatabaseHas('site_pages', ['kind' => 'landing']);
        Storage::disk('public')->assertExists('identity/logo.png');
        Storage::disk('public')->assertExists('landing/hero/profile-video.webm');
    }

    public function test_preparation_migrates_without_replacing_existing_content(): void
    {
        $scheme = CertificationScheme::factory()->create(['name' => 'Skema milik admin']);

        $this->artisan('deployment:prepare')->assertSuccessful();

        $this->assertSame('Skema milik admin', $scheme->refresh()->name);
        $this->getJson('/up')->assertOk();
    }

    public function test_initial_import_refuses_to_replace_an_existing_directory(): void
    {
        Storage::fake('public');
        $scheme = CertificationScheme::factory()->create(['name' => 'Skema milik admin']);

        $this->artisan('deployment:prepare', ['--import-content' => true])->assertFailed();

        $this->assertSame('Skema milik admin', $scheme->refresh()->name);
        $this->assertDatabaseCount('certification_schemes', 1);
    }

    public function test_initial_import_preserves_an_admin_edited_seeded_tuk(): void
    {
        Storage::fake('public');
        $tuk = Tuk::query()->where('name', 'Jobhun')->firstOrFail();
        $tuk->update(['address' => 'Alamat milik admin']);

        $this->artisan('deployment:prepare', ['--import-content' => true])->assertFailed();

        $this->assertSame('Alamat milik admin', $tuk->refresh()->address);
        $this->assertDatabaseCount('certification_schemes', 0);
    }
}

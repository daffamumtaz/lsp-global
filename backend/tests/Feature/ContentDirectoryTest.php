<?php

namespace Tests\Feature;

use App\Filament\Resources\Assessors\Pages\ManageAssessors;
use App\Filament\Resources\CertificationSchemes\Pages\ManageCertificationSchemes;
use App\Filament\Resources\GalleryItems\Pages\ManageGalleryItems;
use App\Filament\Resources\Tuks\Pages\ManageTuks;
use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\GalleryItem;
use App\Models\Process;
use App\Models\Tuk;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ContentDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_pages_are_available_and_guests_are_rejected(): void
    {
        foreach (['tuks', 'assessors', 'certification-schemes', 'gallery-items', 'information'] as $path) {
            $this->get('/admin/'.$path)->assertOk()->assertDontSee('Halaman dalam Pengembangan');
        }
        $this->get('/admin/information')->assertSee('TUK')->assertSee('Asesor')->assertSee('Edit alur asesmen daring');
        auth()->logout();
        foreach (['tuks', 'assessors', 'certification-schemes', 'gallery-items', 'information'] as $path) {
            $this->get('/admin/'.$path)->assertRedirect('/admin/login');
        }
    }

    public function test_information_links_directly_to_the_existing_assessment_editor(): void
    {
        $process = Process::factory()->create(['slug' => 'asesmen-sertifikasi-jarak-jauh']);
        $this->get('/admin/information')->assertSee('/admin/processes/'.$process->id.'/edit');
    }

    public function test_tuk_modal_crud_updates_both_public_directories(): void
    {
        Livewire::test(ManageTuks::class)->callAction('create', ['name' => 'TUK Baru', 'website_url' => 'https://example.com', 'code' => 'TUK-001', 'city' => 'Surabaya', 'province' => 'Jawa Timur', 'address' => 'Jalan Uji', 'maps_url' => 'https://www.google.com/maps?q=Surabaya', 'is_active' => true])->assertHasNoActionErrors();
        $tuk = Tuk::query()->where('name', 'TUK Baru')->firstOrFail();
        $this->getJson('/api/content-directory')->assertJsonPath('data.tuks.1.code', 'TUK-001')->assertJsonPath('data.tuks.1.maps_url', 'https://www.google.com/maps?q=Surabaya');
        $this->getJson('/api/landing-page')->assertJsonPath('data.partners.1.name', 'TUK Baru')->assertJsonPath('data.partners.0.logo_url', url('/storage/tuks/jobhun.png'));
        Livewire::test(ManageTuks::class)->mountAction(TestAction::make('edit')->table($tuk))->assertActionMounted(TestAction::make('edit')->table($tuk));
        Livewire::test(ManageTuks::class)->callAction(TestAction::make('edit')->table($tuk), ['name' => 'TUK Baru', 'website_url' => 'https://example.com', 'is_active' => false])->assertHasNoActionErrors();
        $this->getJson('/api/content-directory')->assertJsonCount(1, 'data.tuks');
        $this->getJson('/api/landing-page')->assertJsonCount(1, 'data.partners');
        Livewire::test(ManageTuks::class)->callAction(TestAction::make('delete')->table($tuk))->assertHasNoActionErrors();
        $this->assertModelMissing($tuk);
    }

    public function test_tuk_rejects_unsafe_links_and_missing_name(): void
    {
        Livewire::test(ManageTuks::class)->callAction('create', ['name' => '', 'website_url' => 'javascript:alert(1)', 'maps_url' => 'https://example.com', 'is_active' => true])->assertHasActionErrors(['name' => 'required', 'website_url', 'maps_url']);
        $this->assertDatabaseCount('tuks', 1);
    }

    public function test_coordinates_saved_in_filament_are_public_numbers_and_can_be_cleared(): void
    {
        Livewire::test(ManageTuks::class)->callAction('create', ['name' => 'Peta Uji', 'latitude' => -7.2574719, 'longitude' => 112.7520883, 'is_active' => true])->assertHasNoActionErrors();
        $tuk = Tuk::query()->where('name', 'Peta Uji')->firstOrFail();
        $this->assertSame(-7.2574719, $tuk->latitude);
        $this->getJson('/api/content-directory')->assertJsonPath('data.tuks.1.latitude', -7.2574719)->assertJsonPath('data.tuks.1.longitude', 112.7520883)->assertJsonPath('data.tuks.0.latitude', null);
        if ($fixturePath = getenv('TUK_BROWSER_FIXTURE')) {
            file_put_contents($fixturePath, $this->getJson('/api/content-directory')->getContent());
        }
        Livewire::test(ManageTuks::class)->callAction(TestAction::make('edit')->table($tuk), ['name' => 'Peta Uji', 'latitude' => null, 'longitude' => null, 'is_active' => true])->assertHasNoActionErrors();
        $this->assertNull($tuk->refresh()->latitude);
        $this->assertNull($tuk->longitude);
        $tuk->update(['is_active' => false]);
        $this->getJson('/api/content-directory')->assertJsonCount(1, 'data.tuks');
    }

    public function test_filament_rejects_incomplete_and_out_of_range_coordinates(): void
    {
        foreach ([['latitude' => -7, 'longitude' => null, 'error' => 'longitude'], ['latitude' => null, 'longitude' => 112, 'error' => 'latitude'], ['latitude' => 91, 'longitude' => 112, 'error' => 'latitude'], ['latitude' => -91, 'longitude' => 112, 'error' => 'latitude'], ['latitude' => 0, 'longitude' => 181, 'error' => 'longitude'], ['latitude' => 0, 'longitude' => -181, 'error' => 'longitude']] as $case) {
            Livewire::test(ManageTuks::class)->callAction('create', ['name' => 'Invalid', 'latitude' => $case['latitude'], 'longitude' => $case['longitude'], 'is_active' => true])->assertHasActionErrors([$case['error']]);
        }
        $this->assertDatabaseCount('tuks', 1);
    }

    public function test_admin_can_filter_tuks_without_map_locations(): void
    {
        $located = Tuk::factory()->create(['latitude' => 0, 'longitude' => 0]);
        $missing = Tuk::factory()->create(['latitude' => null, 'longitude' => null]);
        Livewire::test(ManageTuks::class)->filterTable('missing_location')->assertCanSeeTableRecords([$missing])->assertCanNotSeeTableRecords([$located]);
    }

    public function test_model_rejects_invalid_coordinates_and_accepts_zero_and_boundaries(): void
    {
        foreach ([[-90, -180], [90, 180], [0, 0], [null, null]] as [$latitude, $longitude]) {
            $tuk = Tuk::factory()->create(compact('latitude', 'longitude'));
            $this->assertSame($latitude === null ? null : (float) $latitude, $tuk->latitude);
        }
        foreach ([[null, 1], [1, null], [91, 1], [-91, 1], [1, 181], [1, -181], ['invalid', 1]] as [$latitude, $longitude]) {
            try {
                Tuk::factory()->create(compact('latitude', 'longitude'));
                $this->fail('Invalid coordinates were saved.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_assessor_modal_can_create_edit_and_delete(): void
    {
        Livewire::test(ManageAssessors::class)->callAction('create', ['name' => 'Asesor Uji', 'certificate' => 'MET-001', 'field' => 'TIK', 'is_active' => true])->assertHasNoActionErrors();
        $assessor = Assessor::query()->firstOrFail();
        $this->getJson('/api/content-directory')->assertJsonPath('data.assessors.0.certificate', 'MET-001');
        Livewire::test(ManageAssessors::class)->callAction(TestAction::make('edit')->table($assessor), ['name' => 'Asesor Edit', 'certificate' => 'MET-002', 'field' => 'TIK', 'is_active' => false])->assertHasNoActionErrors();
        $this->assertSame('Asesor Edit', $assessor->refresh()->name);
        $this->getJson('/api/content-directory')->assertJsonCount(0, 'data.assessors');
        Livewire::test(ManageAssessors::class)->callAction(TestAction::make('delete')->table($assessor))->assertHasNoActionErrors();
        $this->assertModelMissing($assessor);
    }

    public function test_scheme_modal_preserves_details_and_generates_unique_slug(): void
    {
        $data = ['name' => 'Skema Uji', 'slug' => 'skema-uji', 'category' => 'TIK', 'type' => 'Okupasi', 'level' => 3, 'price' => null, 'methods' => ['luring', 'jarak-jauh'], 'units' => [['code' => 'U.001', 'title' => 'Unit Uji']], 'basic_requirements' => 'Pendidikan TIK', 'administrative_requirements' => 'KTP', 'is_active' => true];
        Livewire::test(ManageCertificationSchemes::class)->callAction('create', $data)->assertHasNoActionErrors();
        $scheme = CertificationScheme::query()->firstOrFail();
        $this->getJson('/api/content-directory')->assertJsonPath('data.schemes.0.units.0.code', 'U.001')->assertJsonPath('data.schemes.0.price', null);
        Livewire::test(ManageCertificationSchemes::class)->mountAction(TestAction::make('edit')->table($scheme))->assertActionMounted(TestAction::make('edit')->table($scheme));
        Livewire::test(ManageCertificationSchemes::class)->callAction('create', $data)->assertHasNoActionErrors();
        $this->assertDatabaseHas('certification_schemes', ['slug' => 'skema-uji-2']);
        CertificationScheme::query()->where('slug', 'skema-uji-2')->firstOrFail()->update(['is_active' => false]);
        Livewire::test(ManageCertificationSchemes::class)->callAction(TestAction::make('edit')->table($scheme), [...$data, 'name' => 'Skema Edit', 'is_active' => false])->assertHasNoActionErrors();
        $this->assertSame('Skema Edit', $scheme->refresh()->name);
        $this->getJson('/api/content-directory')->assertJsonCount(0, 'data.schemes');
        Livewire::test(ManageCertificationSchemes::class)->callAction(TestAction::make('delete')->table($scheme))->assertHasNoActionErrors();
        $this->assertModelMissing($scheme);
    }

    public function test_scheme_creation_loads_default_text_and_can_add_a_category(): void
    {
        $component = Livewire::test(ManageCertificationSchemes::class)->mountAction('create')
            ->assertActionDataSet([
                'basic_requirements' => "Copy Kartu Pelajar atau Copy Buku Raport terakhir SMK jurusan Rekayasa Perangkat Lunak\nCopy sertifikat pelatihan berbasis kompetensi Pemrograman Komputer yang telah diikuti\nSurat Pengalaman Kerja pada jabatan Pemrogram Junior (Junior Coder) minimal 1 (satu) tahun secara berkelanjutan\nCopy Ijazah terakhir minimal D1 bidang Informatika yang dilegalisir oleh Perguruan Tinggi yang bersangkutan",
                'administrative_requirements' => "Pasfoto 3 × 4 dengan latar merah.\nSalinan KTP.",
            ]);
        $component->callAction(TestAction::make('createOption')->schemaComponent('category', 'mountedActionSchema0'), ['name' => 'Kategori Baru'])
            ->assertHasNoActionErrors()->assertActionDataSet(['category' => 'Kategori Baru']);
        $component->callAction(TestAction::make('createOption')->schemaComponent('type', 'mountedActionSchema0'), ['name' => 'Jenis Baru'])
            ->assertHasNoActionErrors()->assertActionDataSet(['type' => 'Jenis Baru']);
        $component->set('mountedActions.0.data.name', 'Skema Baru')->set('mountedActions.0.data.slug', 'skema-baru')
            ->set('mountedActions.0.data.level', 3)->set('mountedActions.0.data.methods', ['luring'])
            ->set('mountedActions.0.data.units', [['code' => 'U.001', 'title' => 'Unit Uji']])
            ->callMountedAction()->assertHasNoActionErrors();
        $scheme = CertificationScheme::query()->where('slug', 'skema-baru')->firstOrFail();
        $this->assertSame('Kategori Baru', $scheme->category);
        $this->assertCount(4, $scheme->basic_requirements);
        $this->assertSame('Jenis Baru', $scheme->type);
        $this->assertSame(['Pasfoto 3 × 4 dengan latar merah.', 'Salinan KTP.'], $scheme->administrative_requirements);
        Livewire::test(ManageCertificationSchemes::class)->mountAction(TestAction::make('edit')->table($scheme))
            ->assertActionDataSet(['administrative_requirements' => "Pasfoto 3 × 4 dengan latar merah.\nSalinan KTP."]);
    }

    public function test_gallery_modal_stores_uploaded_media(): void
    {
        Storage::fake('public');
        Livewire::test(ManageGalleryItems::class)
            ->mountAction('createAlbum', ['media_type' => 'photo'])
            ->set('mountedActions.0.data.title', 'Album Unggahan')
            ->set('mountedActions.0.data.date', '2026-08-12')
            ->set('mountedActions.0.data.location', 'Jakarta')
            ->set('mountedActions.0.data.image', [UploadedFile::fake()->image('unggahan.jpg')])
            ->callMountedAction()
            ->assertHasNoActionErrors();
        $item = GalleryItem::query()->where('title', 'Album Unggahan')->firstOrFail();
        $this->assertCount(1, $item->getMedia('gallery'));
        $this->getJson('/api/content-directory')->assertJsonPath('data.gallery.0.media.0.type', 'photo');
    }

    public function test_gallery_requires_media_and_publishes_uploaded_album(): void
    {
        Livewire::test(ManageGalleryItems::class)->callAction('createAlbum', ['media_type' => 'photo', 'title' => 'Kegiatan', 'date' => '2026-08-12', 'location' => 'Jakarta'])->assertHasActionErrors(['image' => 'required']);
        $item = GalleryItem::factory()->create(['title' => 'Kegiatan', 'year' => '2026']);
        $item->addMedia(UploadedFile::fake()->image('kegiatan.jpg'))->toMediaCollection('gallery');
        $this->getJson('/api/content-directory')->assertJsonPath('data.gallery.0.title', 'Kegiatan')->assertJsonPath('data.gallery.0.media.0.type', 'photo')->assertJsonCount(1, 'data.gallery.0.media');
        Livewire::test(ManageGalleryItems::class)->callAction(TestAction::make('edit')->table($item), ['title' => 'Kegiatan Edit', 'date' => '2025-08-12', 'location' => 'Bandung', 'description' => 'Diperbarui'])->assertHasNoActionErrors();
        $this->assertSame('Kegiatan Edit', $item->refresh()->title);
        Livewire::test(ManageGalleryItems::class)->callAction(TestAction::make('delete')->table($item))->assertHasNoActionErrors();
        $this->assertModelMissing($item);
    }

    public function test_gallery_activity_publishes_its_metadata_and_multiple_media(): void
    {
        Storage::fake('public');
        Livewire::test(ManageGalleryItems::class)
            ->mountAction('createAlbum', ['media_type' => 'photo'])
            ->set('mountedActions.0.data.title', 'Asesmen Jakarta')
            ->set('mountedActions.0.data.location', 'Jakarta')
            ->set('mountedActions.0.data.date', '2025-08-12')
            ->set('mountedActions.0.data.image', [UploadedFile::fake()->image('satu.jpg'), UploadedFile::fake()->image('dua.jpg')])
            ->callMountedAction()->assertHasNoActionErrors();

        $this->getJson('/api/content-directory')->assertOk()
            ->assertJsonPath('data.gallery.0.title', 'Asesmen Jakarta')
            ->assertJsonPath('data.gallery.0.location', 'Jakarta')
            ->assertJsonPath('data.gallery.0.date', '2025-08-12')
            ->assertJsonPath('data.gallery.0.year', 2025)
            ->assertJsonCount(2, 'data.gallery.0.media')
            ->assertJsonPath('data.gallery.0.media.1.type', 'photo');
    }

    public function test_video_album_does_not_publish_a_generic_photo_thumbnail(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create(['media_type' => 'video']);
        $media = $item->addMedia(UploadedFile::fake()->createWithContent('asesmen.webm', file_get_contents(base_path('tests/Fixtures/gallery-video.webm'))))->toMediaCollection('gallery');
        $this->getJson('/api/content-directory')->assertOk()
            ->assertJsonPath('data.gallery.0.media.0.type', 'video')
            ->assertJsonPath('data.gallery.0.media.0.src', $media->getFullUrl())
            ->assertJsonPath('data.gallery.0.media.0.thumbnail', '');
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Pages\Information;
use App\Filament\Pages\Profile;
use App\Filament\Resources\GalleryItems\Pages\ManageGalleryItems;
use App\Filament\Resources\Processes\ProcessResource;
use App\Models\CertificationScheme;
use App\Models\GalleryItem;
use App\Models\Process;
use App\Models\SitePage;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Tests\TestCase;

class WebsiteContentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_information_defaults_and_saved_copy_are_public(): void
    {
        $this->getJson('/api/information-page')->assertOk()->assertJsonPath('data.hero.title', 'Informasi');
        SitePage::factory()->create(['kind' => 'information', 'content' => ['hero' => ['title' => 'Informasi Baru'], 'directory' => ['results' => '{total} peserta']]]);
        $this->getJson('/api/information-page')->assertJsonPath('data.hero.title', 'Informasi Baru')->assertJsonPath('data.directory.results', '{total} peserta')->assertJsonPath('data.tuk.title', 'Tempat Uji Kompetensi (TUK)');
    }

    public function test_information_editor_saves_copy_and_refuses_non_admins(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(Information::class)->fillForm(['hero.title' => 'Dari Admin'], 'form')->call('save')->assertHasNoFormErrors();
        $this->getJson('/api/information-page')->assertJsonPath('data.hero.title', 'Dari Admin')->assertJsonPath('data.directory.results', SitePage::defaultInformationContent()['directory']['results']);
        $this->actingAs(User::factory()->create());
        $this->get('/admin/information')->assertForbidden();
    }

    public function test_scheme_slug_is_generated_unique_and_stable(): void
    {
        $first = CertificationScheme::factory()->create(['name' => 'Pemrogram Junior', 'slug' => null]);
        $second = CertificationScheme::factory()->create(['name' => 'Pemrogram Junior', 'slug' => null]);
        $third = CertificationScheme::factory()->create(['name' => 'Pemrogram Junior', 'slug' => null]);
        $this->assertSame('pemrogram-junior', $first->slug);
        $this->assertSame('pemrogram-junior-2', $second->slug);
        $this->assertSame('pemrogram-junior-3', $third->slug);
        $first->update(['name' => 'Judul Baru', 'slug' => 'ubah-url']);
        $this->assertSame('pemrogram-junior', $first->refresh()->slug);
    }

    public function test_scheme_rejects_title_without_valid_slug(): void
    {
        $this->expectException(ValidationException::class);
        CertificationScheme::factory()->create(['name' => '!!!', 'slug' => null]);
    }

    public function test_gallery_accepts_multiple_photos_but_rejects_mixed_media_and_type_changes(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create(['media_type' => 'photo']);
        $item->addMedia(UploadedFile::fake()->image('one.jpg'))->toMediaCollection('gallery');
        $item->addMedia(UploadedFile::fake()->image('two.jpg'))->toMediaCollection('gallery');
        $this->assertCount(2, $item->fresh()->getMedia('gallery'));
        try {
            $item->addMedia(UploadedFile::fake()->createWithContent('clip.mp4', hex2bin('000000206674797069736f6d0000020069736f6d69736f32617663316d703431')))->toMediaCollection('gallery');
            $this->fail('Mixed media was accepted');
        } catch (FileUnacceptableForCollection) {
            $this->assertCount(2, $item->fresh()->getMedia('gallery'));
        }
        $this->expectException(ValidationException::class);
        $item->update(['media_type' => 'video']);
    }

    public function test_process_resource_refuses_creation_and_deletion(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertFalse(ProcessResource::canCreate());
        $this->assertFalse(ProcessResource::canDelete(Process::factory()->create()));
        $this->get('/admin/processes/create')->assertForbidden();
    }

    public function test_optional_image_descriptions_are_saved_and_normalized_by_api(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(Profile::class)->callAction('editCertificate', ['title' => 'Tanpa Caption', 'description' => null, 'visible' => true], arguments: ['index' => 0])->assertHasNoActionErrors()->call('saveCertificates');
        $this->getJson('/api/profile-page')->assertJsonPath('data.certificates.0.description', '');
        Livewire::test(Profile::class)->set('profileData.description', '<p></p>')->call('saveProfile')->assertHasNoFormErrors();
        $this->getJson('/api/profile-page')->assertJsonPath('data.profile.description', '');
        GalleryItem::factory()->create(['description' => null]);
        $this->getJson('/api/content-directory')->assertJsonPath('data.gallery.0.description', '');
    }

    public function test_gallery_selection_opens_the_selected_album_form(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $editor = Livewire::test(ManageGalleryItems::class)->mountAction('create');
        $this->assertSame('Pilih jenis album', $editor->instance()->getMountedAction()->getModalHeading());
        Livewire::test(ManageGalleryItems::class)->callAction([TestAction::make('create'), TestAction::make('video')])
            ->assertActionMounted('createAlbum')->assertActionDataSet(['media_type' => 'video']);
    }

    public function test_video_album_accepts_multiple_videos_and_rejects_photos(): void
    {
        Storage::fake('public');
        $item = GalleryItem::factory()->create(['media_type' => 'video']);
        for ($index = 0; $index < 2; $index++) {
            $file = UploadedFile::fake()->createWithContent("video-$index.mp4", hex2bin('00000018667479706d703432000000006d70343269736f6d'));
            $item->addMedia($file)->toMediaCollection('gallery');
        }
        $this->assertCount(2, $item->fresh()->getMedia('gallery'));
        $this->expectException(FileUnacceptableForCollection::class);
        $item->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('gallery');
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Pages\Information;
use App\Filament\Pages\SectionHeadings;
use App\Models\SitePage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EditableSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_headings_can_be_saved_and_are_served_to_the_public(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(SectionHeadings::class)->fillForm(['gallery_hero.title' => 'Galeri Baru', 'gallery_hero.subtitle' => 'Dokumentasi kami'], 'form')->call('save')->assertHasNoFormErrors();
        $this->getJson('/api/landing-page')->assertJsonPath('data.sections.gallery_hero.title', 'Galeri Baru')->assertJsonPath('data.sections.gallery_hero.subtitle', 'Dokumentasi kami')->assertJsonPath('data.sections.about_legal.title', 'Lisensi & Legalitas');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/section-headings')->assertForbidden();
    }

    public function test_information_editing_preserves_internal_labels_and_only_changes_headings(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
        SitePage::factory()->create(['kind' => 'information', 'content' => ['directory' => ['results' => 'Total {total}'], 'flow' => ['zoom_in' => 'Zoom asli']]]);
        Livewire::test(Information::class)->fillForm(['hero.title' => 'Judul Baru'], 'form')->set('data.directory.results', 'Tidak boleh berubah')->call('save')->assertHasNoFormErrors();
        $this->getJson('/api/information-page')->assertJsonPath('data.hero.title', 'Judul Baru')->assertJsonPath('data.directory.results', 'Total {total}')->assertJsonPath('data.flow.zoom_in', 'Zoom asli');
    }

    public function test_asset_import_exposes_existing_media_and_preserves_admin_uploads(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile/custom.png', 'uploaded');
        SitePage::factory()->create(['kind' => 'profile', 'content' => ['profile' => ['photo_path' => 'profile/custom.png']]]);
        $this->artisan('content:import-frontend-assets')->assertSuccessful();
        $profile = SitePage::where('kind', 'profile')->first()->profileContent();
        $this->assertSame('profile/custom.png', $profile['profile']['photo_path']);
        foreach (['photo_two_path', 'photo_three_path'] as $field) {
            Storage::disk('public')->assertExists($profile['profile'][$field]);
        }
        foreach ($profile['certificates'] as $certificate) {
            Storage::disk('public')->assertExists($certificate['image_path']);
            $this->assertNull($certificate['fallback_image']);
        }
        $hero = SitePage::where('kind', 'landing')->first()->landingContent()['hero'];
        foreach (['background_path', 'poster_path', 'video_path'] as $field) {
            Storage::disk('public')->assertExists($hero[$field]);
        }
        $this->artisan('content:import-frontend-assets')->assertSuccessful();
        $this->assertSame($profile, SitePage::where('kind', 'profile')->first()->profileContent());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $panel = Filament::getPanel('admin');
        $this->assertTrue($panel->isSidebarFullyCollapsibleOnDesktop());
        $this->assertStringContainsString('identity/admin-favicon.png', $panel->getFavicon());
    }
}

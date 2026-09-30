<?php

namespace Tests\Feature;

use App\Filament\Resources\SitePages\Pages\ManageSitePages;
use App\Models\SitePage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_returns_seeded_content(): void
    {
        $this->getJson('/api/landing-page')
            ->assertOk()
            ->assertJsonPath('data.hero.hook', 'Wujudkan Kompetensi Profesional Anda')
            ->assertJsonCount(3, 'data.why_us.features')
            ->assertJsonPath('data.why_us.features.0.icon', 'award')
            ->assertJsonPath('data.why_us.features.1.icon', 'shield')
            ->assertJsonPath('data.why_us.features.2.icon', 'network')
            ->assertJsonCount(3, 'data.statistics')
            ->assertJsonCount(10, 'data.partners')
            ->assertJsonMissingPath('data.hero.video_path');
    }

    public function test_public_endpoint_excludes_hidden_items_and_preserves_order(): void
    {
        $page = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $content = $page->landingContent();
        $content['statistics'] = [
            ['value' => '20', 'label' => 'Terakhir', 'visible' => true],
            ['value' => '99', 'label' => 'Disembunyikan', 'visible' => false],
            ['value' => '10', 'label' => 'Pertama', 'visible' => true],
        ];
        $page->update(['content' => $content]);

        $this->getJson('/api/landing-page')
            ->assertOk()
            ->assertJsonCount(2, 'data.statistics')
            ->assertJsonPath('data.statistics.0.value', '20')
            ->assertJsonPath('data.statistics.1.value', '10');
    }

    public function test_public_endpoint_returns_absolute_media_urls(): void
    {
        $page = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $content = $page->landingContent();
        $content['hero']['video_path'] = 'landing/hero/profile.mp4';
        $content['hero']['poster_path'] = 'landing/hero/poster.webp';
        $page->update(['content' => $content]);

        $this->getJson('/api/landing-page')
            ->assertOk()
            ->assertJsonPath('data.hero.video_url', url('/storage/landing/hero/profile.mp4'))
            ->assertJsonPath('data.hero.poster_url', url('/storage/landing/hero/poster.webp'))
            ->assertJsonMissingPath('data.hero.video_path');
    }

    public function test_editor_can_save_hero_and_api_immediately_reflects_it(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageSitePages::class)
            ->set('heroData.hook', 'Kompetensi Baru')
            ->call('saveHero')
            ->assertHasNoErrors();

        $this->getJson('/api/landing-page')
            ->assertJsonPath('data.hero.hook', 'Kompetensi Baru');

        $activity = Activity::query()->where('subject_type', SitePage::class)->latest()->firstOrFail();
        $this->assertSame('Mengubah Hero', $activity->description);
        $this->assertSame('hero', $activity->properties->get('section'));
    }

    public function test_editor_rejects_empty_required_hero_text(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageSitePages::class)
            ->set('heroData.hook', '')
            ->call('saveHero')
            ->assertHasErrors(['heroData.hook' => 'required']);

        $this->getJson('/api/landing-page')
            ->assertJsonPath('data.hero.hook', 'Wujudkan Kompetensi Profesional Anda');
    }

    public function test_feature_modal_keeps_changes_as_draft_until_section_is_saved(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $editor = Livewire::test(ManageSitePages::class)->callAction('editFeature', [
            'title' => 'Fitur baru', 'caption' => 'Caption baru', 'icon' => 'shield', 'visible' => true,
        ], arguments: ['index' => 0])->assertHasNoErrors();
        $this->assertSame('Sertifikat Kompetensi Diakui Secara Nasional', SitePage::query()->where('kind', 'landing')->firstOrFail()->landingContent()['why_us']['features'][0]['title']);
        $editor->call('saveWhy')->assertHasNoErrors();

        $this->getJson('/api/landing-page')->assertJsonPath('data.why_us.features.0.title', 'Fitur baru')
            ->assertJsonPath('data.why_us.features.0.icon', 'shield');
    }

    public function test_icon_upgrade_changes_only_unchanged_default_features(): void
    {
        $page = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $content = $page->landingContent();
        $content['why_us']['features'][1]['icon'] = 'award';
        $content['why_us']['features'][2]['title'] = 'Jaringan pilihan admin';
        $content['why_us']['features'][2]['icon'] = 'award';
        $page->update(['content' => $content]);

        $migration = require database_path('migrations/2026_09_30_000001_vary_default_landing_feature_icons.php');
        $migration->up();
        $this->getJson('/api/landing-page')
            ->assertJsonPath('data.why_us.features.1.icon', 'shield')
            ->assertJsonPath('data.why_us.features.2.title', 'Jaringan pilihan admin')
            ->assertJsonPath('data.why_us.features.2.icon', 'award');
    }

    public function test_partner_modal_reorders_and_publishes_items(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageSitePages::class)->callAction('editPartner', [
            'name' => 'Mitra baru', 'url' => 'https://example.com', 'visible' => true,
        ], arguments: ['index' => 0])->assertHasNoErrors()->call('moveItem', 'partners', 0, 1)->call('savePartners')->assertHasNoErrors();

        $this->getJson('/api/landing-page')->assertJsonPath('data.partners.1.name', 'Mitra baru');
    }

    public function test_stats_table_saves_inline_values(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageSitePages::class)->fillForm([
            'items' => [['value' => '42', 'label' => 'Skema', 'visible' => true]],
        ], 'statisticsForm')->call('saveStatistics')->assertHasNoErrors();

        $this->getJson('/api/landing-page')->assertJsonCount(1, 'data.statistics')->assertJsonPath('data.statistics.0.value', '42');
    }
}

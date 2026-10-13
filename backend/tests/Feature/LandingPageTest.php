<?php

namespace Tests\Feature;

use App\Filament\Pages\Identity;
use App\Filament\Resources\SitePages\Pages\ManageSitePages;
use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\SitePage;
use App\Models\Tuk;
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
            ->assertJsonPath('data.hero.hook', 'Bangun Perjalanan Karier Anda dengan Kompetensi yang Diakui')
            ->assertJsonPath('data.about.hook', 'Tentang LSP TIK Global')
            ->assertJsonCount(3, 'data.why_us.features')
            ->assertJsonPath('data.why_us.features.0.icon', 'award')
            ->assertJsonPath('data.why_us.features.1.icon', 'shield')
            ->assertJsonPath('data.why_us.features.2.icon', 'network')
            ->assertJsonCount(3, 'data.statistics')
            ->assertJsonCount(1, 'data.partners')
            ->assertJsonMissingPath('data.hero.video_path');
    }

    public function test_statistics_use_active_database_records_and_ignore_saved_numbers(): void
    {
        CertificationScheme::factory()->count(2)->create();
        CertificationScheme::factory()->create(['is_active' => false]);
        Tuk::factory()->create(['is_active' => true]);
        Tuk::factory()->create(['is_active' => false]);
        Assessor::factory()->count(3)->create();
        Assessor::factory()->create(['is_active' => false]);
        $page = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $content = $page->landingContent();
        $content['statistics'] = [['value' => '999', 'label' => 'Palsu', 'visible' => true]];
        $page->update(['content' => $content]);
        $this->getJson('/api/landing-page')->assertOk()->assertJsonCount(3, 'data.statistics')
            ->assertJsonPath('data.statistics.0.value', '2')
            ->assertJsonPath('data.statistics.1.value', '2')
            ->assertJsonPath('data.statistics.2.value', '3');
        CertificationScheme::query()->where('is_active', true)->firstOrFail()->update(['is_active' => false]);
        $this->getJson('/api/landing-page')->assertJsonPath('data.statistics.0.value', '1');
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
        $this->actingAs(User::factory()->admin()->create());
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

    public function test_editor_can_publish_about_text_without_changing_video(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $video = SitePage::query()->where('kind', 'landing')->firstOrFail()->landingContent()['hero'];
        Livewire::test(ManageSitePages::class)
            ->set('aboutData.description', "Paragraf pertama.\n\nParagraf kedua.")
            ->call('saveAbout')->assertHasNoErrors();
        $this->getJson('/api/landing-page')->assertJsonPath('data.about.description', "Paragraf pertama.\n\nParagraf kedua.");
        $this->assertSame($video, SitePage::query()->where('kind', 'landing')->firstOrFail()->landingContent()['hero']);
    }

    public function test_hero_and_video_forms_publish_independently(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $editor = Livewire::test(ManageSitePages::class);
        $page = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $content = $page->landingContent();
        $content['hero']['video_path'] = 'landing/hero/keep.mp4';
        $page->update(['content' => $content]);
        $editor->fillForm([
            'title' => 'Bersama LSP TIK Global',
            'hook' => 'Bangun Perjalanan Karier Anda dengan Kompetensi yang Diakui',
            'description' => '',
            'strengths' => array_map(static fn (string $title): array => ['title' => $title], ['Lisensi', 'Kompetensi', 'Asesor', 'Jejaring', 'Industri']),
        ], 'heroForm')
            ->call('saveHero')->assertHasNoErrors();
        $this->getJson('/api/landing-page')->assertJsonPath('data.hero.title', 'Bersama LSP TIK Global')
            ->assertJsonPath('data.hero.strengths.0', 'Lisensi')
            ->assertJsonPath('data.hero.video_url', url('/storage/landing/hero/keep.mp4'));
        $editor->call('saveVideo')->assertHasNoErrors();
        $this->getJson('/api/landing-page')->assertJsonPath('data.hero.strengths.0', 'Lisensi');
        $editor->set('heroData.strengths', [['title' => 'Hanya satu']])->call('saveHero')->assertHasErrors();
        $this->get('/admin/site-pages')->assertSee('Video &amp; Tentang', false)->assertSee('Statistik')
            ->assertSeeInOrder(['id="hero"', 'id="about"', 'id="why-us"', 'id="statistics"', 'id="highlights"', 'id="reviews"', 'id="partners"'], false);
    }

    public function test_editor_rejects_empty_required_hero_text(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageSitePages::class)
            ->set('heroData.hook', '')
            ->call('saveHero')
            ->assertHasErrors(['heroData.hook' => 'required']);

        $this->getJson('/api/landing-page')
            ->assertJsonPath('data.hero.hook', 'Bangun Perjalanan Karier Anda dengan Kompetensi yang Diakui');
    }

    public function test_feature_modal_keeps_changes_as_draft_until_section_is_saved(): void
    {
        $this->actingAs(User::factory()->admin()->create());
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

    public function test_landing_editor_links_to_the_shared_tuk_directory(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin/site-pages')->assertSee('Kelola Mitra / TUK')->assertSee('Simpan Mitra')
            ->assertDontSee('Lihat riwayat perubahan')->assertDontSee('Tambah Poin');
    }

    public function test_partner_heading_and_caption_publish_independently_and_validate(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $editor = Livewire::test(ManageSitePages::class)
            ->fillForm(['hook' => 'Jaringan Mitra', 'description' => 'Bersama mitra pendidikan dan industri.'], 'partnersForm')
            ->call('savePartners')->assertHasNoErrors();
        $this->getJson('/api/landing-page')->assertJsonPath('data.partners_section.hook', 'Jaringan Mitra')
            ->assertJsonPath('data.partners_section.description', 'Bersama mitra pendidikan dan industri.')
            ->assertJsonPath('data.hero.title', 'Bersama LSP TIK Global')->assertJsonCount(1, 'data.partners');
        $editor->fillForm(['hook' => '', 'description' => ''], 'partnersForm')->call('savePartners')
            ->assertHasErrors(['partnersData.hook' => 'required']);
        $this->getJson('/api/landing-page')->assertJsonPath('data.partners_section.hook', 'Jaringan Mitra');
    }

    public function test_editor_selects_five_highlights_in_order_and_rejects_inactive_or_excess_schemes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $schemes = CertificationScheme::factory()->count(6)->create();
        $ids = [$schemes[2]->id, $schemes[0]->id, $schemes[1]->id, $schemes[4]->id, $schemes[3]->id];
        $editor = Livewire::test(ManageSitePages::class)->fillForm(['ids' => $ids], 'highlightsForm')->call('saveHighlights')->assertHasNoErrors();
        $this->getJson('/api/landing-page')->assertJsonPath('data.highlighted_scheme_ids', array_map('strval', $ids));
        $editor->fillForm(['ids' => $schemes->pluck('id')->all()], 'highlightsForm')->call('saveHighlights')->assertHasErrors();
        $editor->fillForm(['ids' => [$ids[0], $ids[1]]], 'highlightsForm')->call('saveHighlights')->assertHasErrors();
        $editor->fillForm(['ids' => []], 'highlightsForm')->call('saveHighlights')->assertHasErrors();
        $inactive = CertificationScheme::factory()->create(['is_active' => false]);
        $editor->fillForm(['ids' => [$ids[0], $ids[1], $inactive->id]], 'highlightsForm')->call('saveHighlights')->assertHasErrors();
        $this->get('/admin/site-pages')->assertDontSee('Simpan Statistik')->assertSee('Skema Highlight');
    }

    public function test_feature_destination_is_configurable_and_rejects_unknown_targets(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $editor = Livewire::test(ManageSitePages::class)->callAction('editFeature', [
            'title' => 'Lisensi', 'caption' => 'Lihat lisensi', 'icon' => 'award',
            'href' => '/informasi#tuk', 'visible' => true,
        ], arguments: ['index' => 0])->assertHasNoActionErrors()->call('saveWhy')->assertHasNoErrors();
        $this->getJson('/api/landing-page')->assertJsonPath('data.why_us.features.0.href', '/informasi#tuk');
        $editor->set('whyData.features.0.href', 'javascript:alert(1)')->call('saveWhy')
            ->assertHasErrors(['features.0.href']);
        $this->getJson('/api/landing-page')->assertJsonPath('data.why_us.features.0.href', '/informasi#tuk');
    }

    public function test_social_configuration_is_published_and_rejects_unsafe_links(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $editor = Livewire::test(Identity::class)->set('data.social_links.Instagram', 'https://www.instagram.com/lspglobal/')->call('save')->assertHasNoErrors();
        $this->getJson('/api/landing-page')->assertJsonPath('data.social_links.Instagram', 'https://www.instagram.com/lspglobal/');
        $editor->set('data.social_links.Instagram', 'javascript:alert(1)')->call('save')->assertHasErrors(['data.social_links.Instagram']);
        $this->getJson('/api/landing-page')->assertJsonPath('data.social_links.Instagram', 'https://www.instagram.com/lspglobal/');
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Pages\Profile;
use App\Models\SitePage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_returns_defaults_without_creating_a_record(): void
    {
        $this->getJson('/api/profile-page')->assertOk()
            ->assertJsonCount(13, 'data.organization')
            ->assertJsonCount(3, 'data.certificates')
            ->assertJsonPath('data.organization.0.parent_id', null)
            ->assertJsonMissingPath('data.profile.photo_path');
        $this->assertDatabaseMissing('site_pages', ['kind' => 'profile']);
    }

    public function test_guest_cannot_access_profile_editor(): void
    {
        $this->get('/admin/profile')->assertRedirect('/admin/login');
    }

    public function test_editor_saves_profile_and_logs_the_change_without_changing_other_sections(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Profile::class)->set('profileData.hook', 'Profil Baru')->call('saveProfile')->assertHasNoErrors();

        $this->getJson('/api/profile-page')->assertJsonPath('data.profile.hook', 'Profil Baru')->assertJsonCount(13, 'data.organization');
        $this->assertSame('Mengubah Tentang Kami', Activity::query()->latest('id')->firstOrFail()->description);
    }

    public function test_editor_rejects_empty_profile_title_without_publishing(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Profile::class)->set('profileData.hook', '')->call('saveProfile')->assertHasErrors(['profileData.hook' => 'required']);

        $this->getJson('/api/profile-page')->assertJsonPath('data.profile.hook', 'Lembaga Sertifikasi Profesi Teknologi Informasi dan Komunikasi Global (LSP TIK Global)');
    }

    public function test_organization_modal_applies_a_draft_and_save_publishes_it(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $editor = Livewire::test(Profile::class)->callAction('editOrganization', [
            'title' => 'Koordinator Baru', 'name' => 'Nama Baru', 'parent_id' => 2, 'order' => 1, 'visible' => true,
        ])->assertHasNoErrors();
        $this->assertCount(13, SitePage::query()->where('kind', 'profile')->firstOrFail()->profileContent()['organization']);
        $editor->call('saveOrganization')->assertHasNoErrors();

        $this->getJson('/api/profile-page')->assertJsonCount(14, 'data.organization')
            ->assertJsonPath('data.organization.13.parent_id', 2)->assertJsonPath('data.organization.13.title', 'Koordinator Baru');
    }

    public function test_editor_rejects_a_descendant_as_parent(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Profile::class)->callAction('editOrganization', [
            'title' => 'Dewan Pengarah', 'name' => 'Nama', 'parent_id' => 6, 'order' => 1, 'visible' => true,
        ], arguments: ['id' => 1])->assertHasActionErrors(['parent_id']);

        $this->getJson('/api/profile-page')->assertJsonPath('data.organization.0.parent_id', null);
    }

    public function test_editor_cannot_delete_a_parent_with_children(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Profile::class)->callAction('deleteOrganization', arguments: ['id' => 1])
            ->call('saveOrganization')->assertHasNoErrors();

        $this->getJson('/api/profile-page')->assertJsonCount(13, 'data.organization');
    }

    public function test_hidden_parent_hides_its_branch_and_hidden_certificates_are_excluded(): void
    {
        $content = SitePage::defaultProfileContent();
        $content['organization'][4]['visible'] = false;
        $content['certificates'][1]['visible'] = false;
        SitePage::factory()->create(['kind' => 'profile', 'content' => $content]);

        $this->getJson('/api/profile-page')->assertJsonCount(11, 'data.organization')
            ->assertJsonMissing(['title' => 'Sekretariat'])->assertJsonMissing(['title' => 'Ketua Bid. Administrasi'])
            ->assertJsonCount(2, 'data.certificates');
    }

    public function test_api_sanitizes_rich_text_and_returns_absolute_media_urls(): void
    {
        $content = SitePage::defaultProfileContent();
        $content['profile']['description'] = '<p>Profil aman</p><script>alert(1)</script><img src="x" onerror="alert(1)">';
        $content['profile']['photo_path'] = 'profile/photos/profile.webp';
        SitePage::factory()->create(['kind' => 'profile', 'content' => $content]);

        $response = $this->getJson('/api/profile-page')->assertOk()
            ->assertJsonPath('data.profile.photo_urls.0', url('/storage/profile/photos/profile.webp'));
        $this->assertStringNotContainsString('<script', $response->json('data.profile.description'));
        $this->assertStringNotContainsString('onerror', $response->json('data.profile.description'));
    }

    public function test_certificate_edits_and_reordering_are_published(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Profile::class)->callAction('editCertificate', [
            'title' => 'Lisensi Baru', 'description' => 'Deskripsi baru', 'visible' => true,
        ], arguments: ['index' => 0])->assertHasNoErrors()->call('moveCertificate', 0, 1)->call('saveCertificates')->assertHasNoErrors();

        $this->getJson('/api/profile-page')->assertJsonPath('data.certificates.1.title', 'Lisensi Baru');
    }
}

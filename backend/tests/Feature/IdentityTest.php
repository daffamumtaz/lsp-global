<?php

namespace Tests\Feature;

use App\Filament\Pages\Identity;
use App\Models\SitePage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class IdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_preserves_social_links_and_publishes_branding_and_footer(): void
    {
        Storage::fake('public');
        $landing = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $content = $landing->landingContent();
        $content['social_links']['Instagram'] = 'https://www.instagram.com/existing/';
        $landing->update(['content' => $content]);
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(Identity::class)->assertSet('data.social_links.Instagram', 'https://www.instagram.com/existing/')
            ->set('data.brand_name', 'LSP Uji')->set('data.footer_caption', 'Caption diperbarui')
            ->set('data.phone', '021-12345678')->set('data.email', 'kontak@example.com')
            ->set('data.whatsapp', '0812-3456-7890')->set('data.address', 'Jalan Uji, Jakarta')
            ->set('data.maps_url', 'https://maps.google.com/?q=Jakarta')->set('data.working_hours', 'Senin–Jumat, 09.00–17.00')
            ->set('data.license_number', 'LSP-TEST-001')->set('data.whatsapp_message', 'Halo, ingin bertanya.')
            ->set('data.whatsapp_registration_message', 'Daftar {scheme_name}')
            ->set('data.whatsapp_contact_message', 'Pesan {name}: {message}')
            ->set('data.copyright_text', 'Hak cipta dilindungi.')
            ->set('data.logo_path', [UploadedFile::fake()->image('logo.png')])
            ->set('data.icon_path', [UploadedFile::fake()->image('icon.png')])
            ->set('data.favicon_path', [UploadedFile::fake()->image('favicon.png')])
            ->call('save')->assertHasNoErrors();
        $identity = SitePage::identityContent();
        Storage::disk('public')->assertExists([$identity['logo_path'], $identity['icon_path'], $identity['favicon_path']]);
        $this->getJson('/api/landing-page')->assertJsonPath('data.identity.brand_name', 'LSP Uji')
            ->assertJsonPath('data.identity.phone', '021-12345678')->assertJsonPath('data.identity.email', 'kontak@example.com')
            ->assertJsonPath('data.identity.whatsapp', '0812-3456-7890')->assertJsonPath('data.identity.address', 'Jalan Uji, Jakarta')
            ->assertJsonPath('data.identity.maps_url', 'https://maps.google.com/?q=Jakarta')
            ->assertJsonPath('data.identity.working_hours', 'Senin–Jumat, 09.00–17.00')
            ->assertJsonPath('data.identity.license_number', 'LSP-TEST-001')
            ->assertJsonPath('data.identity.whatsapp_message', 'Halo, ingin bertanya.')
            ->assertJsonPath('data.identity.whatsapp_registration_message', 'Daftar {scheme_name}')
            ->assertJsonPath('data.identity.whatsapp_contact_message', 'Pesan {name}: {message}')
            ->assertJsonPath('data.identity.copyright_text', 'Hak cipta dilindungi.')
            ->assertJsonPath('data.identity.footer_caption', 'Caption diperbarui')
            ->assertJsonPath('data.identity.logo_url', url(Storage::disk('public')->url($identity['logo_path'])))
            ->assertJsonPath('data.identity.icon_url', url(Storage::disk('public')->url($identity['icon_path'])))
            ->assertJsonPath('data.identity.favicon_url', url(Storage::disk('public')->url($identity['favicon_path'])))
            ->assertJsonPath('data.social_links.Instagram', 'https://www.instagram.com/existing/');
    }

    public function test_invalid_contact_details_do_not_save_identity(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(Identity::class)->set('data.email', 'invalid')->set('data.whatsapp', '+62---')
            ->set('data.phone', '123-----')->set('data.maps_url', 'javascript:alert(1)')->call('save')
            ->assertHasErrors(['data.email', 'data.whatsapp', 'data.phone', 'data.maps_url']);
        $this->assertDatabaseMissing('site_pages', ['kind' => 'identity']);
    }
}

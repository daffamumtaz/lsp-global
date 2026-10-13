<?php

namespace Tests\Feature;

use App\Filament\Resources\SitePages\SitePageResource;
use App\Filament\Widgets\CertificationStats;
use App\Filament\Widgets\UnreadMessages;
use App\Models\CertificationScheme;
use App\Models\ContactMessage;
use App\Models\SitePage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_widgets_use_real_counts_and_messages(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        CertificationScheme::factory()->count(2)->create();
        CertificationScheme::factory()->create(['is_active' => false]);
        ContactMessage::factory()->create(['subject' => 'Pertanyaan sertifikasi', 'is_read' => false]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(CertificationStats::class)->assertSee('Jumlah Skema')->assertSee('3')->assertDontSee('Album galeri')->assertDontSee('Asesor kritis');
        Livewire::test(UnreadMessages::class)->assertSee('Pertanyaan sertifikasi');
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_shows_content_widgets(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => 'Admin']))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Tambah skema')
            ->assertSee('Pesan belum dibaca')
            ->assertDontSee('Perubahan terbaru')
            ->assertSee('Skema Sertifikasi')
            ->assertSee('Belum ada pesan yang belum dibaca.')
            ->assertDontSee('Andi Pratama');
    }

    public function test_admin_resources_and_activity_page_render(): void
    {
        $page = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/site-pages')
            ->assertOk()
            ->assertSee('Simpan Hero')
            ->assertDontSee('Tambah Poin')
            ->assertSee('Simpan Mitra')
            ->assertDontSee('Halaman dalam Pengembangan');

        $this->get('/admin/profile')->assertOk()->assertSee('Struktur Organisasi')->assertSee('Simpan Tentang Kami')->assertDontSee('Halaman dalam Pengembangan');

        foreach (['contact-messages', 'users', 'history'] as $path) {
            $this->get("/admin/{$path}")
                ->assertOk()
                ->assertDontSee('Halaman dalam Pengembangan');
        }

        $this->get("/admin/site-pages/{$page->getKey()}/activities")
            ->assertOk()
            ->assertSee('Riwayat Landing Page');

        $this->assertFalse(SitePageResource::canCreate());
        $this->assertTrue(SitePageResource::canEdit($page));
        $this->assertFalse(SitePageResource::canDelete($page));
    }
}

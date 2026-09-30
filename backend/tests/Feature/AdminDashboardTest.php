<?php

namespace Tests\Feature;

use App\Filament\Resources\SitePages\SitePageResource;
use App\Models\SitePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_shows_figma_sections(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Admin']))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Aksi Cepat')
            ->assertSee('Pesan Masuk')
            ->assertSee('Perubahan Terbaru')
            ->assertSee('Skema Sertifikasi')
            ->assertSee('Belum ada pesan.')
            ->assertSee('Belum ada perubahan.')
            ->assertDontSee('Andi Pratama');
    }

    public function test_admin_resources_and_activity_page_render(): void
    {
        $page = SitePage::query()->where('kind', 'landing')->firstOrFail();
        $this->actingAs(User::factory()->create());

        $this->get('/admin/site-pages')
            ->assertOk()
            ->assertSee('Simpan Hero')
            ->assertSee('Tambah Poin')
            ->assertDontSee('Halaman dalam Pengembangan');

        $this->get('/admin/profile')->assertOk()->assertSee('Struktur Organisasi')->assertSee('Simpan Tentang Kami')->assertDontSee('Halaman dalam Pengembangan');

        foreach (['certification-schemes', 'gallery-items', 'contact-messages', 'users', 'history', 'information', 'identity'] as $path) {
            $this->get("/admin/{$path}")
                ->assertOk()
                ->assertSee('Halaman dalam Pengembangan')
                ->assertDontSee('Ekspor Excel');
        }

        $this->get("/admin/site-pages/{$page->getKey()}/activities")
            ->assertOk()
            ->assertSee('Riwayat Landing Page');

        $this->assertFalse(SitePageResource::canCreate());
        $this->assertTrue(SitePageResource::canEdit($page));
        $this->assertFalse(SitePageResource::canDelete($page));
    }
}

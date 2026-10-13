<?php

namespace Tests\Feature;

use App\Filament\Resources\Tuks\Pages\ManageTuks;
use App\Models\Tuk;
use App\Models\User;
use Awcodes\QuickCreate\QuickCreatePlugin;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_preserves_organization_and_includes_identity(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $html = $this->get('/admin/information');
        $html->assertSee('Konten Website')->assertSee('Data Sertifikasi')->assertSee('Administrasi')->assertSee('Struktur Organisasi');
        $html->assertSee('Identitas');
        $this->get('/admin/identity')->assertOk()->assertSee('Simpan Identitas');
    }

    public function test_quick_create_is_limited_to_four_content_resources(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $plugin = QuickCreatePlugin::get();
        $this->assertSame('Tambah', $plugin->getLabel());
        $this->assertCount(4, $plugin->getResources());
    }

    public function test_active_filter_and_deactivate_action_update_public_directory(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $active = Tuk::factory()->create();
        $inactive = Tuk::factory()->create(['is_active' => false]);
        Livewire::test(ManageTuks::class)
            ->filterTable('is_active', true)->assertCanSeeTableRecords([$active])->assertCanNotSeeTableRecords([$inactive])
            ->callAction(TestAction::make('deactivate')->table($active))->assertHasNoActionErrors();
        $this->assertFalse($active->refresh()->is_active);
    }
}

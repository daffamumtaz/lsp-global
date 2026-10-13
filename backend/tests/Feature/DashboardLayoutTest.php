<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JohnRivera7\FilamentWidgetGrid\Models\WidgetGridLayout;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_malformed_stored_layout_falls_back_without_instantiating_unknown_classes(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        WidgetGridLayout::create(['user_id' => $admin->id, 'panel_id' => 'admin', 'is_default' => false, 'items' => ['items' => [['widget' => User::class]]]]);
        $this->assertCount(2, Livewire::test(Dashboard::class)->get('widgetGridLayout'));
        WidgetGridLayout::query()->where('user_id', $admin->id)->update(['items' => ['items' => 'corrupt']]);
        $this->assertCount(2, Livewire::test(Dashboard::class)->get('widgetGridLayout'));
    }

    public function test_personal_layout_is_persistent_and_does_not_change_another_account(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $component = Livewire::test(Dashboard::class);
        $items = $component->get('widgetGridLayout');
        $this->assertCount(2, $items);
        $component->call('syncWidgetGridLayout', array_slice($items, 0, 1))->call('saveWidgetGridLayout');
        $this->assertCount(1, WidgetGridLayout::query()->where('user_id', $admin->id)->firstOrFail()->items['items']);
        $this->assertCount(1, Livewire::test(Dashboard::class)->get('widgetGridLayout'));
        $this->actingAs($other);
        $this->assertCount(2, Livewire::test(Dashboard::class)->get('widgetGridLayout'));
        $this->actingAs($admin);
        Livewire::test(Dashboard::class)->call('resetWidgetGridToDefault');
        $this->assertDatabaseCount('widget_grid_layouts', 0);
    }

    public function test_unknown_widgets_and_unbounded_positions_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(Dashboard::class)->call('syncWidgetGridLayout', [['widget' => User::class, 'x' => -99, 'y' => 99999999, 'w' => 9999, 'h' => 9999]])->assertHasErrors(['widgetGridLayout']);
        $this->assertDatabaseCount('widget_grid_layouts', 0);
    }

    public function test_nonadmin_cannot_customize_dashboard(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(Dashboard::class)->assertForbidden();
    }
}

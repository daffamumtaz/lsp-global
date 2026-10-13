<?php

namespace Tests\Feature;

use App\Filament\Resources\OrganizationPositions\Pages\ManageOrganizationPositions;
use App\Models\OrganizationPosition;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_uses_database_and_hides_inactive_branches(): void
    {
        $root = OrganizationPosition::factory()->create(['title' => 'Root']);
        $hidden = OrganizationPosition::factory()->create(['parent_id' => $root->id, 'is_active' => false]);
        $child = OrganizationPosition::factory()->create(['parent_id' => $hidden->id]);
        $visible = OrganizationPosition::factory()->create(['parent_id' => $root->id, 'person_name' => 'Nama Baru', 'credentials' => 'M.Si']);

        $data = $this->getJson('/api/organization')->assertOk()->json('data');

        $this->assertContains($visible->id, array_column($data, 'id'));
        $this->assertNotContains($hidden->id, array_column($data, 'id'));
        $this->assertNotContains($child->id, array_column($data, 'id'));
        $node = collect($data)->firstWhere('id', $visible->id);
        $this->assertSame('Nama Baru', $node['person_name']);
        $this->assertSame('M.Si', $node['credentials']);
        $this->assertTrue($node['is_active']);
    }

    public function test_seed_places_four_departments_under_dewan_pengarah(): void
    {
        $ketua = OrganizationPosition::query()->where('title', 'Ketua LSP')->firstOrFail();
        $this->assertSame(0, $ketua->children()->count());
        $this->assertSame(4, $ketua->parent->children()->where('title', 'like', 'Ketua Bid%')->count());
    }

    public function test_migration_reparents_existing_department_heads_without_moving_their_members(): void
    {
        $ketua = OrganizationPosition::where('title', 'Ketua LSP')->firstOrFail();
        $heads = OrganizationPosition::where('title', 'like', 'Ketua Bid%')->get();
        $members = OrganizationPosition::whereIn('parent_id', $heads->modelKeys())->pluck('parent_id', 'id')->all();
        OrganizationPosition::whereIn('id', $heads->modelKeys())->update(['parent_id' => $ketua->id]);
        $migration = require database_path('migrations/2026_10_05_091824_move_department_heads_under_advisory_board.php');
        $migration->up();
        $this->assertSame(4, OrganizationPosition::whereIn('id', $heads->modelKeys())->where('parent_id', $ketua->parent_id)->count());
        $this->assertSame($members, OrganizationPosition::whereIn('id', array_keys($members))->pluck('parent_id', 'id')->all());
    }

    public function test_model_rejects_descendant_parent_and_parent_deletion(): void
    {
        $root = OrganizationPosition::factory()->create();
        $child = OrganizationPosition::factory()->create(['parent_id' => $root->id]);
        try {
            $root->update(['parent_id' => $child->id]);
            $this->fail('A cycle was saved');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('parent_id', $error->errors());
        }
        try {
            $root->refresh()->delete();
            $this->fail('A parent was deleted');
        } catch (ValidationException $error) {
            $this->assertModelExists($root);
            $this->assertModelExists($child);
        }
    }

    public function test_guests_cannot_access_editor(): void
    {
        $this->get('/admin/organization-positions')->assertRedirect('/admin/login');
    }

    public function test_cms_creates_edits_and_reorders_only_siblings(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $root = OrganizationPosition::factory()->create();
        $first = OrganizationPosition::factory()->create(['parent_id' => $root->id, 'order' => 1]);
        $second = OrganizationPosition::factory()->create(['parent_id' => $root->id, 'order' => 2]);

        Livewire::test(ManageOrganizationPositions::class)->callAction('create', [
            'title' => 'Koordinator Baru', 'members' => [['person_name' => 'Nama Pejabat', 'credentials' => 'S.Kom']],
            'parent_id' => $root->id, 'order' => 3, 'is_active' => true,
        ])->assertHasNoActionErrors();
        $new = OrganizationPosition::query()->where('title', 'Koordinator Baru')->firstOrFail();
        Livewire::test(ManageOrganizationPositions::class)->callAction(TestAction::make('edit')->table($new), [
            'title' => 'Koordinator Baru', 'members' => [['person_name' => 'Nama Diubah', 'credentials' => 'M.Kom']],
            'parent_id' => $root->id, 'order' => 3, 'is_active' => false,
        ])->assertHasNoActionErrors();
        $this->assertSame('Nama Diubah', $new->refresh()->person_name);
        $this->assertFalse($new->is_active);
        Livewire::test(ManageOrganizationPositions::class)->call('reorderTable', [$second->id, $first->id, $new->id]);
        $this->assertSame(1, $second->refresh()->order);
        $this->assertSame(2, $first->refresh()->order);
        $this->assertSame(3, $new->refresh()->order);
        $this->getJson('/api/organization')->assertJsonMissing(['id' => $new->id]);
    }

    public function test_empty_database_returns_empty_organization_without_fallback(): void
    {
        foreach (OrganizationPosition::query()->orderByDesc('id')->get() as $node) {
            $node->delete();
        }
        $this->getJson('/api/organization')->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_cms_rejects_descendant_parent_and_protects_parent_deletion(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $root = OrganizationPosition::factory()->create();
        $child = OrganizationPosition::factory()->create(['parent_id' => $root->id]);
        Livewire::test(ManageOrganizationPositions::class)->callAction(TestAction::make('edit')->table($root), [
            'title' => $root->title, 'members' => $root->members,
            'parent_id' => $child->id, 'order' => 1, 'is_active' => true,
        ])->assertHasActionErrors(['parent_id']);
        $this->assertNull($root->refresh()->parent_id);
        Livewire::test(ManageOrganizationPositions::class)->callAction(TestAction::make('delete')->table($root));
        $this->assertModelExists($root);
        Livewire::test(ManageOrganizationPositions::class)->callAction(TestAction::make('delete')->table($child));
        $this->assertModelMissing($child);
    }

    public function test_reordering_rejects_partial_sibling_sets_without_changes(): void
    {
        $root = OrganizationPosition::factory()->create();
        $first = OrganizationPosition::factory()->create(['parent_id' => $root->id, 'order' => 1]);
        $second = OrganizationPosition::factory()->create(['parent_id' => $root->id, 'order' => 2]);
        try {
            OrganizationPosition::reorderSiblings([$second->id]);
            $this->fail('Partial siblings were reordered');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('order', $error->errors());
        }
        $this->assertSame(1, $first->refresh()->order);
        $this->assertSame(2, $second->refresh()->order);
    }
}

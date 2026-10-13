<?php

namespace Tests\Feature;

use App\Filament\Resources\OrganizationPositions\Pages\ManageOrganizationPositions;
use App\Models\OrganizationPosition;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_and_layout_are_saved_and_exposed_by_api(): void
    {
        $members = [['person_name' => 'Mahmudin', 'credentials' => 'S.Kom'], ['person_name' => 'Susana', 'credentials' => 'M.Kom']];
        $node = OrganizationPosition::factory()->create(['members' => $members, 'layout_row' => 8, 'layout_column' => 5]);
        $this->assertSame('Mahmudin', $node->person_name);
        $this->getJson('/api/organization')->assertJsonFragment(['members' => $members, 'layout_row' => 8, 'layout_column' => 5]);
        $node->update(['members' => array_reverse($members)]);
        $this->assertSame('Susana', $node->refresh()->person_name);
        $this->assertSame('M.Kom', $node->credentials);
        $node->update(['person_name' => 'Nama Lama Diubah']);
        $this->assertSame('Nama Lama Diubah', $node->refresh()->members[0]['person_name']);
        $this->assertSame('Mahmudin', $node->members[1]['person_name']);
    }

    public function test_rejects_invalid_members_partial_positions_and_duplicate_slots(): void
    {
        $node = OrganizationPosition::factory()->create(['layout_row' => 8, 'layout_column' => 5]);
        foreach ([['members' => []], ['members' => [['person_name' => '']]], ['layout_row' => 9, 'layout_column' => null], ['layout_row' => 0, 'layout_column' => 1], ['layout_row' => 8, 'layout_column' => 10]] as $data) {
            try {
                $node->update($data);
                $this->fail('Invalid organization data was saved');
            } catch (ValidationException $error) {
                $this->assertNotEmpty($error->errors());
                $node->refresh();
            }
        }
        $other = OrganizationPosition::factory()->create();
        $this->expectException(ValidationException::class);
        $other->update(['layout_row' => 8, 'layout_column' => 5]);
    }

    public function test_parent_and_child_position_changes_cannot_invert_hierarchy(): void
    {
        $parent = OrganizationPosition::factory()->create(['layout_row' => 8, 'layout_column' => 1]);
        $child = OrganizationPosition::factory()->create(['parent_id' => $parent->id, 'layout_row' => 10, 'layout_column' => 1]);
        foreach ([[$child, ['layout_row' => 8]], [$parent, ['layout_row' => 10]]] as [$node, $data]) {
            try {
                $node->update($data);
                $this->fail('Inverted hierarchy was saved');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('layout_row', $error->errors());
                $node->refresh();
            }
        }
        $middle = OrganizationPosition::factory()->create(['parent_id' => $parent->id]);
        $this->expectException(ValidationException::class);
        $child->update(['parent_id' => $middle->id, 'layout_row' => 9]);
    }

    public function test_merge_moves_members_and_deletes_only_matching_leaf_siblings(): void
    {
        $parent = OrganizationPosition::factory()->create();
        $first = OrganizationPosition::factory()->create(['title' => 'Anggota', 'parent_id' => $parent->id]);
        $second = OrganizationPosition::factory()->create(['title' => 'Anggota', 'parent_id' => $parent->id]);
        $secondName = $second->person_name;
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $page = Livewire::test(ManageOrganizationPositions::class);
        $revision = $page->get('previewRevision');
        $page->callAction(TestAction::make('merge')->table($first), ['position_ids' => [$second->id]])->assertHasNoActionErrors();
        $this->assertModelMissing($second);
        $page->assertCanNotSeeTableRecords([$second])->assertCanSeeTableRecords([$first]);
        $this->assertSame($secondName, $first->refresh()->members[1]['person_name']);
        $this->assertGreaterThan($revision, $page->get('previewRevision'));
        $this->assertDatabaseHas('activity_log', ['event' => 'merged', 'subject_id' => $first->id]);
    }

    public function test_invalid_merge_does_not_change_any_records(): void
    {
        $parent = OrganizationPosition::factory()->create();
        $first = OrganizationPosition::factory()->create(['title' => 'Anggota', 'parent_id' => $parent->id]);
        $second = OrganizationPosition::factory()->create(['title' => 'Anggota', 'parent_id' => $parent->id]);
        $different = OrganizationPosition::factory()->create(['title' => 'Sekretariat', 'parent_id' => $parent->id]);
        try {
            $first->mergePositions([$second->id, $different->id]);
            $this->fail('Different positions were merged');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('position_ids', $error->errors());
        }
        $this->assertModelExists($second);
        $this->assertCount(1, $first->refresh()->members);
        OrganizationPosition::factory()->create(['parent_id' => $second->id]);
        $this->expectException(ValidationException::class);
        $first->mergePositions([$second->id]);
    }

    public function test_cms_edits_multiple_members_positions_and_refreshes_preview(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $members = [['person_name' => 'Satu', 'credentials' => null], ['person_name' => 'Dua', 'credentials' => 'M.Kom']];
        $page = Livewire::test(ManageOrganizationPositions::class);
        $revision = $page->get('previewRevision');
        $page->callAction('create', ['title' => 'Baru', 'members' => $members, 'parent_id' => null, 'order' => 1, 'is_active' => true, 'layout_row' => 8, 'layout_column' => 5])->assertHasNoActionErrors();
        $node = OrganizationPosition::where('title', 'Baru')->firstOrFail();
        $this->assertCount(2, $node->members);
        $this->assertSame(5, $node->layout_column);
        $this->assertGreaterThan($revision, $page->get('previewRevision'));
        $page->assertSee('Perbarui preview')->assertSee('/admin-preview/organization');
        $page->callAction(TestAction::make('edit')->table($node), ['title' => 'Baru', 'members' => array_reverse($members), 'parent_id' => null, 'order' => 1, 'is_active' => true, 'layout_row' => 9, 'layout_column' => 7])->assertHasNoActionErrors();
        $this->assertSame('Dua', $node->refresh()->person_name);
        $this->assertSame(9, $node->layout_row);
    }

    public function test_preview_refreshes_after_reorder_and_delete(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $parent = OrganizationPosition::factory()->create();
        $first = OrganizationPosition::factory()->create(['parent_id' => $parent->id]);
        $second = OrganizationPosition::factory()->create(['parent_id' => $parent->id, 'order' => 2]);
        $page = Livewire::test(ManageOrganizationPositions::class);
        $revision = $page->get('previewRevision');
        $page->call('reorderTable', [$second->id, $first->id]);
        $this->assertGreaterThan($revision, $page->get('previewRevision'));
        $revision = $page->get('previewRevision');
        $page->callAction(TestAction::make('delete')->table($second))->assertHasNoActionErrors();
        $this->assertGreaterThan($revision, $page->get('previewRevision'));
        $this->assertModelMissing($second);
    }

    public function test_non_admin_cannot_open_the_editor_for_merge(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $parent = OrganizationPosition::factory()->create();
        $first = OrganizationPosition::factory()->create(['title' => 'Anggota', 'parent_id' => $parent->id]);
        $second = OrganizationPosition::factory()->create(['title' => 'Anggota', 'parent_id' => $parent->id]);
        Livewire::test(ManageOrganizationPositions::class)->assertForbidden();
        $this->assertModelExists($second);
        $this->assertCount(1, $first->refresh()->members);
    }

    public function test_reference_groups_certification_members_without_losing_names(): void
    {
        $department = OrganizationPosition::where('title', 'Ketua Bid. Sertifikasi dan Standardisasi')->firstOrFail();
        $members = $department->children()->where('title', 'Anggota')->get();
        $this->assertCount(1, $members);
        $this->assertSame(['Mahmudin', 'Susana Dwi Yulianti'], array_column($members->first()->members, 'person_name'));
        $migration = require database_path('migrations/2026_10_05_092900_consolidate_certification_department_members.php');
        $migration->up();
        $this->assertCount(2, $members->first()->refresh()->members);
    }

    public function test_migration_preserves_custom_content_and_hierarchy(): void
    {
        $count = OrganizationPosition::count();
        $migration = require database_path('migrations/2026_10_05_031751_add_layout_and_members_to_organization_positions_table.php');
        $migration->down();
        DB::table('organization_positions')->where('id', 2)->update(['person_name' => 'Nama khusus']);
        $migration->up();
        $node = OrganizationPosition::findOrFail(2);
        $this->assertSame('Nama khusus', $node->members[0]['person_name']);
        $this->assertSame(1, $node->parent_id);
        $this->assertNull($node->layout_row);
        $this->assertSame($count, OrganizationPosition::count());
    }

    public function test_reference_layout_preserves_custom_sibling_order(): void
    {
        $member = collect(json_decode(file_get_contents(resource_path('data/organization.json')), true))->firstWhere('id', 9);
        DB::table('organization_positions')->insert([...$member, 'created_at' => now(), 'updated_at' => now()]);
        $migration = require database_path('migrations/2026_10_05_031751_add_layout_and_members_to_organization_positions_table.php');
        $migration->down();
        DB::table('organization_positions')->where('id', 2)->update(['order' => 8]);
        $migration->up();
        $node = OrganizationPosition::findOrFail(2);
        $this->assertSame(8, $node->order);
        $this->assertSame(2, $node->layout_row);
        $this->assertSame(3, $node->layout_column);
    }

    public function test_duplicate_slot_error_is_attached_to_the_cms_field(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        OrganizationPosition::factory()->create(['layout_row' => 8, 'layout_column' => 3]);
        Livewire::test(ManageOrganizationPositions::class)
            ->callAction('create', [
                'title' => 'Duplikat', 'members' => [['person_name' => 'Nama', 'credentials' => null]],
                'parent_id' => null, 'order' => 1, 'is_active' => true, 'layout_row' => 8, 'layout_column' => 3,
            ])->assertHasActionErrors(['layout_column']);
        $this->assertDatabaseMissing('organization_positions', ['title' => 'Duplikat']);
    }
}

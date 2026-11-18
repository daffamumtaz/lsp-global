<?php

namespace Tests\Feature;

use App\Filament\Resources\CertificationSchemes\CategoryTable;
use App\Filament\Resources\CertificationSchemes\Pages\ManageCertificationSchemes;
use App\Models\CertificationScheme;
use App\Models\SchemeCategory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SchemeCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_category_inventory_opens_from_scheme_page(): void
    {
        Livewire::test(ManageCertificationSchemes::class)
            ->mountAction('manageCategories')->assertActionMounted('manageCategories');
    }

    public function test_category_inventory_opens_below_category_without_losing_scheme_form(): void
    {
        $action = TestAction::make('manageCategories')->schemaComponent('category', 'mountedActionSchema0');
        $component = Livewire::test(ManageCertificationSchemes::class)->mountAction('create')
            ->set('mountedActions.0.data.name', 'Skema belum disimpan');
        $component->assertActionHasLabel($action, 'Atur Kategori')
            ->mountAction($action)->assertActionMounted($action)
            ->assertSet('mountedActions.0.data.name', 'Skema belum disimpan');
    }

    public function test_new_category_is_available_before_any_scheme_uses_it(): void
    {
        Livewire::test(CategoryTable::class)->callAction(TestAction::make('create')->table(), ['name' => 'Data dan AI'])
            ->assertHasNoActionErrors();
        $this->assertDatabaseHas('scheme_categories', ['name' => 'Data dan AI']);
        $this->assertDatabaseCount('certification_schemes', 0);
    }

    public function test_renaming_category_updates_all_affected_schemes_and_public_content(): void
    {
        $first = CertificationScheme::factory()->create(['name' => 'A Skema', 'category' => 'Software', 'methods' => ['luring']]);
        $second = CertificationScheme::factory()->create(['category' => 'Software', 'is_active' => false]);
        $other = CertificationScheme::factory()->create(['category' => 'Data']);
        $category = SchemeCategory::query()->where('name', 'Software')->firstOrFail();
        $this->getJson('/api/content-directory')->assertJsonPath('data.schemes.0.category', 'Software');

        Livewire::test(CategoryTable::class)->callAction(TestAction::make('edit')->table($category), ['name' => 'Rekayasa Perangkat Lunak'])
            ->assertHasNoActionErrors();

        $this->assertSame('Rekayasa Perangkat Lunak', $first->refresh()->category);
        $this->assertSame('Rekayasa Perangkat Lunak', $second->refresh()->category);
        $this->assertSame('Data', $other->refresh()->category);
        $this->getJson('/api/content-directory')->assertJsonPath('data.schemes.0.category', 'Rekayasa Perangkat Lunak');
    }

    public function test_deleting_category_clears_schemes_without_deleting_them(): void
    {
        $first = CertificationScheme::factory()->create(['name' => 'A Skema', 'category' => 'Software', 'methods' => ['luring']]);
        $second = CertificationScheme::factory()->create(['category' => 'Software', 'is_active' => false]);
        $other = CertificationScheme::factory()->create(['category' => 'Data']);
        $category = SchemeCategory::query()->where('name', 'Software')->firstOrFail();

        Livewire::test(CategoryTable::class)->callAction(TestAction::make('delete')->table($category))->assertHasNoActionErrors();

        $this->assertModelMissing($category);
        $this->assertDatabaseCount('certification_schemes', 3);
        $this->assertNull($first->refresh()->category);
        $this->assertNull($second->refresh()->category);
        $this->assertSame('Data', $other->refresh()->category);
        $this->getJson('/api/content-directory')->assertJsonPath('data.schemes.0.category', '');
        Livewire::test(ManageCertificationSchemes::class)
            ->callAction(TestAction::make('edit')->table($first), ['name' => 'Skema tanpa kategori'])
            ->assertHasNoActionErrors();
    }

    public function test_inventory_rejects_duplicate_and_blank_names(): void
    {
        $category = SchemeCategory::factory()->create(['name' => 'Data']);
        Livewire::test(CategoryTable::class)->callAction(TestAction::make('create')->table(), ['name' => ' Data '])->assertHasActionErrors(['name']);
        Livewire::test(CategoryTable::class)->callAction(TestAction::make('create')->table(), ['name' => '   '])->assertHasActionErrors(['name']);
        $this->assertDatabaseCount('scheme_categories', 1);
        Livewire::test(CategoryTable::class)->callAction(TestAction::make('edit')->table($category), ['name' => 'Data'])->assertHasNoActionErrors();
    }

    public function test_non_admin_cannot_open_category_inventory(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(CategoryTable::class)->assertForbidden();
    }

    public function test_migration_backfills_existing_categories_without_changing_requirements(): void
    {
        $migration = require database_path('migrations/2026_10_26_135540_create_scheme_categories_table.php');
        $migration->down();
        DB::table('certification_schemes')->insert([
            'name' => 'Skema Lama', 'slug' => 'skema-lama', 'category' => 'Kategori Lama',
            'basic_requirements' => json_encode(['Poin lama']),
        ]);

        $migration->up();

        $this->assertDatabaseHas('scheme_categories', ['name' => 'Kategori Lama']);
        $scheme = CertificationScheme::query()->where('slug', 'skema-lama')->firstOrFail();
        $this->assertSame(['Poin lama'], $scheme->basic_requirements);
        SchemeCategory::query()->where('name', 'Kategori Lama')->firstOrFail()->delete();
        $this->assertNull($scheme->refresh()->category);
    }

    public function test_deleted_category_cannot_be_resubmitted_from_an_old_form(): void
    {
        $scheme = CertificationScheme::factory()->create(['methods' => ['luring']]);
        $component = Livewire::test(ManageCertificationSchemes::class)->mountAction(TestAction::make('edit')->table($scheme));
        SchemeCategory::query()->where('name', $scheme->category)->firstOrFail()->delete();

        $component->callMountedAction()->assertHasActionErrors(['category']);
        $this->assertNull($scheme->refresh()->category);
        $this->assertDatabaseCount('scheme_categories', 0);
    }

    public function test_requirements_are_saved_as_individual_points_including_multiline_text(): void
    {
        $scheme = CertificationScheme::factory()->create([
            'methods' => ['luring'],
            'basic_requirements' => ['Pendidikan TIK', 'Pengalaman kerja'],
            'administrative_requirements' => ['Salinan KTP'],
        ]);
        Livewire::test(ManageCertificationSchemes::class)->callAction(TestAction::make('edit')->table($scheme), [
            'basic_requirements' => [['requirement' => 'Pendidikan Informatika']],
            'administrative_requirements' => [['requirement' => "KTP (WNI)/ Paspor dan Kartu Izin Tinggal Terbatas\n(Kitas) (WNA)"], ['requirement' => 'Pas Photo 3x4 background merah']],
        ])->assertHasNoActionErrors();
        $this->assertSame(['Pendidikan Informatika'], $scheme->refresh()->basic_requirements);
        $this->assertSame(["KTP (WNI)/ Paspor dan Kartu Izin Tinggal Terbatas\n(Kitas) (WNA)", 'Pas Photo 3x4 background merah'], $scheme->administrative_requirements);
    }
}

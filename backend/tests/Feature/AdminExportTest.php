<?php

namespace Tests\Feature;

use App\Filament\Exports\ContentExport;
use App\Filament\Resources\Tuks\Pages\ManageTuks;
use App\Models\Tuk;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_uses_table_filters_selection_and_explicit_columns(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $included = Tuk::factory()->create(['name' => '=1+1']);
        Tuk::factory()->create(['is_active' => false]);
        $other = Tuk::factory()->create();
        $component = Livewire::test(ManageTuks::class)->filterTable('is_active', true)->instance();
        $export = ContentExport::make()->hydrate($component);
        $this->assertSame(Tuk::query()->where('is_active', true)->orderBy('id')->pluck('id')->all(), $export->query()->orderBy('id')->pluck('id')->all());
        $selected = ContentExport::make()->hydrate($component, collect([$included]));
        $this->assertSame([$included->id], $selected->query()->pluck('id')->all());
        $this->assertSame(['name', 'code', 'city', 'province', 'address', 'website_url', 'is_active'], array_keys($selected->getColumns()));
        $csv = \Maatwebsite\Excel\Facades\Excel::raw($selected, Excel::CSV);
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringNotContainsString($other->name, $csv);
    }

    public function test_signed_download_still_requires_admin_access(): void
    {
        Storage::fake('filament-excel');
        $path = '00000000-0000-0000-0000-000000000000-private.csv';
        Storage::disk('filament-excel')->put($path, 'private');
        $url = URL::temporarySignedRoute('filament-excel-download', now()->addMinutes(5), ['path' => $path]);
        $this->get($url)->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        Storage::disk('filament-excel')->assertExists($path);
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertDownload('private.csv');
    }

    public function test_expired_export_link_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('filament-excel-download', now()->subMinute(), ['path' => 'missing.csv']);
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertForbidden();
    }

    public function test_export_neutralizes_spreadsheet_formulas_and_preserves_numbers(): void
    {
        $sheet = (new Spreadsheet)->getActiveSheet();
        $export = ContentExport::make();
        foreach (['=1+1', '+SUM(A1)', '-cmd', '@SUM(A1)', "\t=1+1"] as $value) {
            $export->bindValue($sheet->getCell('A1'), $value);
            $this->assertSame("'".$value, $sheet->getCell('A1')->getValue());
            $this->assertSame('s', $sheet->getCell('A1')->getDataType());
        }
        $export->bindValue($sheet->getCell('A1'), 1000000);
        $this->assertSame(1000000, $sheet->getCell('A1')->getValue());
    }
}

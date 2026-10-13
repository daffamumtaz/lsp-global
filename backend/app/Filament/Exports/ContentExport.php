<?php

namespace App\Filament\Exports;

use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\ContactMessage;
use App\Models\Tuk;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ContentExport extends ExcelExport implements WithCustomValueBinder
{
    public function setUp(): void
    {
        $this->useTableQuery()->withColumns(function (): array {
            $columns = match ($this->getModelClass()) {
                CertificationScheme::class => ['name' => 'Skema', 'slug' => 'Slug', 'category' => 'Kategori', 'level' => 'Level KKNI', 'price' => 'Harga', 'is_active' => 'Aktif'],
                Tuk::class => ['name' => 'Nama', 'code' => 'Kode TUK', 'city' => 'Kota', 'province' => 'Provinsi', 'address' => 'Alamat', 'website_url' => 'Website', 'is_active' => 'Aktif'],
                Assessor::class => ['name' => 'Nama', 'certificate' => 'Nomor MET', 'field' => 'Bidang', 'is_active' => 'Aktif'],
                ContactMessage::class => ['sender' => 'Pengirim', 'email' => 'Email', 'subject' => 'Subjek', 'message' => 'Pesan', 'is_read' => 'Dibaca', 'created_at' => 'Tanggal'],
                default => [],
            };

            return collect($columns)->map(fn (string $heading, string $field) => Column::make($field)->heading($heading))->values()->all();
        })->askForWriterType(default: Excel::XLSX, options: [Excel::XLSX => 'XLSX', Excel::CSV => 'CSV'], label: 'Format')
            ->withFilename(fn (): string => 'lsp-'.now()->format('Y-m-d-His'));
    }

    public function export(): mixed
    {
        Gate::authorize('manage-admin');
        $download = parent::export();
        activity()->causedBy(auth()->user())->event('exported')->withProperties(['resource' => $this->getModelClass()])->log('Mengekspor data');

        return $download;
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit(preg_match('/^[\s\x00-\x1f]*[=+\-@]/u', $value) ? "'".$value : $value, DataType::TYPE_STRING);

            return true;
        }

        return (new DefaultValueBinder)->bindValue($cell, $value);
    }
}

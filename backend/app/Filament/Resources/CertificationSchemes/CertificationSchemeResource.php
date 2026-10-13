<?php

namespace App\Filament\Resources\CertificationSchemes;

use App\Filament\Exports\ContentExport;
use App\Filament\Resources\CertificationSchemes\Pages\ManageCertificationSchemes;
use App\Models\CertificationScheme;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CertificationSchemeResource extends Resource
{
    protected static ?string $model = CertificationScheme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Data Sertifikasi';

    protected static ?string $navigationLabel = 'Skema Sertifikasi';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Skema Sertifikasi';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label('Nama Skema')->required()->maxLength(255),
                Select::make('category')->label('Kategori')->required()->searchable()->rules(['string', 'max:255'])
                    ->options(fn (Get $get): array => CertificationScheme::query()->distinct()->orderBy('category')->pluck('category', 'category')->all() + (filled($get('category')) ? [$get('category') => $get('category')] : []))
                    ->createOptionForm([TextInput::make('name')->label('Kategori Baru')->required()->maxLength(255)])
                    ->createOptionUsing(fn (array $data): string => trim($data['name'])),
            ]),
            Section::make('Pelaksanaan & Biaya')->columns(2)->columnSpanFull()->schema([
                Select::make('level')->label('Level KKNI')->options(array_combine(range(1, 9), range(1, 9)))->required()->in(range(1, 9)),
                TextInput::make('price')->label('Harga')->prefix('Rp')->mask(RawJs::make(<<<'JS'
                    $money($input, ',', '.', 0)
                    JS))->stripCharacters('.')
                    ->formatStateUsing(fn ($state): ?string => $state === null ? null : number_format((int) $state, 0, ',', '.'))
                    ->inputMode('numeric')->rules(['nullable', 'integer', 'min:0', 'max:4294967295']),
                Select::make('type')->label('Jenis Skema')->default('Okupasi')->required()->searchable()->rules(['string', 'max:255'])
                    ->options(fn (Get $get): array => ['Okupasi' => 'Okupasi'] + CertificationScheme::query()->distinct()->orderBy('type')->pluck('type', 'type')->all() + (filled($get('type')) ? [$get('type') => $get('type')] : []))
                    ->createOptionForm([TextInput::make('name')->label('Jenis Skema Baru')->required()->maxLength(255)])
                    ->createOptionUsing(fn (array $data): string => trim($data['name'])),
                Select::make('methods')->label('Metode')->multiple()->options(['luring' => 'Luring', 'jarak-jauh' => 'Daring'])->required(),
                FileUpload::make('thumbnail')->label('Gambar')->disk('public')->visibility('public')->directory('schemes')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->helperText('1 file JPEG, PNG, atau WebP, maksimal 5 MB. Disarankan 1200 × 675 px, rasio 16:9.'),
            ]),
            Section::make('Unit Kompetensi')->columnSpanFull()->schema([
                Repeater::make('units')->label('Unit Kompetensi')->schema([TextInput::make('code')->label('Kode')->required()->maxLength(255), TextInput::make('title')->label('Judul')->required()->maxLength(1000), Textarea::make('description')->label('Deskripsi Unit')->maxLength(10000)->columnSpanFull()])->reorderableWithButtons()->columns(2)->columnSpanFull(),
            ]),
            Section::make('Persyaratan')->columnSpanFull()->schema([
                Textarea::make('basic_requirements')->label('Persyaratan Dasar')->rows(4)->maxLength(20000)->default(fn (): string => implode("\n", CertificationScheme::defaultBasicRequirements()))
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode("\n", $state) : ($state ?? ''))
                    ->dehydrateStateUsing(fn (?string $state): array => array_values(array_filter(array_map('trim', preg_split('/\R/', $state ?? '')), fn (string $line): bool => $line !== '')))
                    ->helperText('Satu persyaratan per baris. Teks bawaan dapat diubah.')->columnSpanFull(),
                Textarea::make('administrative_requirements')->label('Persyaratan Administratif')->rows(4)->maxLength(20000)->default("Pasfoto 3 × 4 dengan latar merah.\nSalinan KTP.")
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode("\n", $state) : ($state ?? ''))
                    ->dehydrateStateUsing(fn (?string $state): array => array_values(array_filter(array_map('trim', preg_split('/\R/', $state ?? '')), fn (string $line): bool => $line !== '')))
                    ->helperText('Satu persyaratan per baris. Teks bawaan dapat diubah.')->columnSpanFull(),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Skema')->searchable()->sortable(),
                TextColumn::make('category')->label('Kategori')->searchable(),
                TextColumn::make('level')->label('Level')->sortable()->toggleable(),
                TextColumn::make('price')->label('Harga')->money('IDR')->sortable()->toggleable()->placeholder('Belum ditetapkan'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])

            ->recordAction('edit')->filters([TernaryFilter::make('is_active')->label('Status aktif')])
            ->description('Tahan Shift lalu seret untuk menggeser tabel. Gunakan scrollbar atau tombol panah sebagai alternatif.')
            ->headerActions([ExportAction::make()->label('Ekspor')->exports([ContentExport::make()])->authorize('manage-admin')])
            ->toolbarActions([ExportBulkAction::make()->label('Ekspor terpilih')->exports([ContentExport::make()])->authorize('manage-admin')])
            ->emptyStateHeading('Belum ada data yang sesuai')->emptyStateActions([CreateAction::make()->label('Tambah')->modalWidth('5xl')])
            ->recordActions([Action::make('deactivate')->label('Nonaktifkan')->color('warning')->requiresConfirmation()->visible(fn ($record) => $record->is_active)->authorize('manage-admin')->action(fn ($record) => $record->update(['is_active' => false])), EditAction::make()->label('Edit')->modalWidth('5xl'), DeleteAction::make()->label('Hapus')])
            ->contextMenuActions([EditAction::make()->label('Edit')->modalWidth('5xl')]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCertificationSchemes::route('/')];
    }
}

<?php

namespace App\Filament\Resources\Tuks;

use App\Filament\Exports\ContentExport;
use App\Filament\Resources\Tuks\Pages\ManageTuks;
use App\Models\Tuk;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TukResource extends Resource
{
    protected static ?string $model = Tuk::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|\UnitEnum|null $navigationGroup = 'Data Sertifikasi';

    protected static ?string $navigationLabel = 'Mitra / TUK';

    protected static ?string $modelLabel = 'Mitra / TUK';

    protected static ?string $pluralModelLabel = 'Mitra / TUK';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas & Logo')->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                TextInput::make('kind')->label('Jenis TUK')->maxLength(255),
                TextInput::make('website_url')->label('Link Website')->url()->rules(['nullable', 'url:http,https'])->maxLength(255),
                FileUpload::make('logo_path')->label('Logo')->disk('public')->visibility('public')->directory('tuks')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->helperText('1 file JPEG, PNG, atau WebP, maksimal 5 MB. Disarankan 600 × 600 px, rasio 1:1; rasio logo asli tetap dipertahankan.'),
                TextInput::make('code')->label('Kode TUK')->mutateStateForValidationUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)->maxLength(255)->unique(ignoreRecord: true),
            ]),
            Section::make('Lokasi & Maps')->description('Detail lokasi boleh kosong jika belum tersedia, termasuk untuk Jobhun.')->columns(2)->columnSpanFull()->schema([
                TextInput::make('city')->label('Kabupaten/Kota')->maxLength(255),
                TextInput::make('province')->label('Provinsi')->maxLength(255),
                Textarea::make('address')->label('Alamat')->maxLength(2000),
                TextInput::make('maps_url')->label('Link Google Maps')->url()->rules(['nullable', 'url:https', 'regex:~^https://(?:www\.)?(?:google\.com/maps(?:[/?]|$)|maps\.google\.com/|maps\.app\.goo\.gl/|goo\.gl/maps/)~'])->maxLength(255),
                TextInput::make('latitude')->label('Latitude')->numeric()->step('0.0000001')->minValue(-90)->maxValue(90)->requiredWith('longitude')->validationMessages(['required_with' => 'Isi latitude dan longitude bersama.', 'min' => 'Latitude minimal −90.', 'max' => 'Latitude maksimal 90.']),
                TextInput::make('longitude')->label('Longitude')->numeric()->step('0.0000001')->minValue(-180)->maxValue(180)->requiredWith('latitude')->validationMessages(['required_with' => 'Isi latitude dan longitude bersama.', 'min' => 'Longitude minimal −180.', 'max' => 'Longitude maksimal 180.']),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('logo_path')->label('Logo')->disk('public'),
            TextColumn::make('name')->label('Nama')->searchable()->sortable(),
            TextColumn::make('website_url')->label('Website'),
            TextColumn::make('code')->label('Kode TUK')->searchable(),
            TextColumn::make('city')->label('Kabupaten/Kota')->searchable(),
            TextColumn::make('province')->label('Provinsi')->searchable(),
            TextColumn::make('address')->label('Alamat')->toggleable(isToggledHiddenByDefault: true)->limit(50),
            IconColumn::make('map_location')->label('Lokasi peta tersedia')->state(fn (Tuk $record): bool => $record->latitude !== null && $record->longitude !== null)->boolean(),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->filters([Filter::make('missing_location')->label('Lokasi belum lengkap')->query(fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query->whereNull('latitude')->orWhereNull('longitude'))), TernaryFilter::make('is_active')->label('Status aktif'), SelectFilter::make('province')->label('Provinsi')->options(fn () => Tuk::query()->whereNotNull('province')->where('province', '!=', '')->distinct()->pluck('province', 'province')->all())])
            ->description('Tahan Shift lalu seret untuk menggeser tabel. Gunakan scrollbar atau tombol panah sebagai alternatif.')
            ->headerActions([ExportAction::make()->label('Ekspor')->exports([ContentExport::make()])->authorize('manage-admin')])
            ->toolbarActions([ExportBulkAction::make()->label('Ekspor terpilih')->exports([ContentExport::make()])->authorize('manage-admin')])
            ->emptyStateHeading('Belum ada data yang sesuai')->emptyStateActions([CreateAction::make()->label('Tambah')->modalContentFooter(fn ($livewire) => $livewire->previewView())->modalWidth('5xl')])
            ->recordAction('edit')->recordActions([Action::make('deactivate')->label('Nonaktifkan')->color('warning')->requiresConfirmation()->visible(fn ($record) => $record->is_active)->authorize('manage-admin')->action(fn ($record) => $record->update(['is_active' => false])), EditAction::make()->label('Edit')->modalContentFooter(fn ($livewire) => $livewire->previewView())->modalWidth('5xl'), DeleteAction::make()->label('Hapus')]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTuks::route('/')];
    }
}

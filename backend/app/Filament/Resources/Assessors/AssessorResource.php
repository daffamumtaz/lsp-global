<?php

namespace App\Filament\Resources\Assessors;

use App\Filament\Exports\ContentExport;
use App\Filament\Resources\Assessors\Pages\ManageAssessors;
use App\Models\Assessor;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AssessorResource extends Resource
{
    protected static ?string $model = Assessor::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Data Sertifikasi';

    protected static ?string $navigationLabel = 'Asesor';

    protected static ?string $modelLabel = 'Asesor';

    protected static ?string $pluralModelLabel = 'Asesor';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        $legacyCertificate = fn (?Assessor $record): bool => filled($record?->certificate) && ! preg_match('/\AMET\.000\.([0-9]+) ([0-9]{4})\z/', $record->certificate);
        $certificateValue = function (?string $state, Get $get, ?Assessor $record) use ($legacyCertificate): ?string {
            if (! $legacyCertificate($record) && filled($get('certificate_number'))) {
                return 'MET.000.'.$get('certificate_number').' '.$get('certificate_year');
            }

            return $record && ! $legacyCertificate($record) ? null : (filled($state) ? strtoupper(trim($state)) : null);
        };

        return $schema->components([
            TextInput::make('name')->label('Nama Asesor')->required()->maxLength(255),
            TextInput::make('certificate_number')->label('Nomor Sertifikat (MET)')->prefix('MET.000.')->inputMode('numeric')->rules(['regex:/\A[0-9]+\z/'])->maxLength(242)->saved(false)
                ->hidden($legacyCertificate)
                ->formatStateUsing(function (?string $state, ?Assessor $record): ?string {
                    return preg_match('/\AMET\.000\.([0-9]+) ([0-9]{4})\z/', $record?->certificate ?? '', $parts) ? $parts[1] : $state;
                }),
            TextInput::make('certificate_year')->label('Tahun Sertifikat')->inputMode('numeric')->rules(['regex:/\A[0-9]{4}\z/'])->maxLength(4)->required(fn (Get $get): bool => filled($get('certificate_number')))->saved(false)
                ->hidden($legacyCertificate)
                ->default(fn (): string => now('Asia/Jakarta')->format('Y'))
                ->formatStateUsing(function (?string $state, ?Assessor $record): string {
                    return preg_match('/\AMET\.000\.([0-9]+) ([0-9]{4})\z/', $record?->certificate ?? '', $parts) ? $parts[2] : ($state ?? now('Asia/Jakarta')->format('Y'));
                }),
            TextInput::make('certificate')->label('Nomor Sertifikat (MET)')->visible($legacyCertificate)->dehydratedWhenHidden()
                ->mutateStateForValidationUsing($certificateValue)->dehydrateStateUsing($certificateValue)->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('field')->label('Bidang')->maxLength(255),
            DatePicker::make('expires_on')->label('Masa berlaku sampai')->required(fn (?Assessor $record): bool => $record === null || $record->expires_on !== null)->default(fn () => now('Asia/Jakarta')->addYearsNoOverflow(3)->toDateString())->helperText('Peringatan mulai 90 hari sebelum berakhir. Masa tenggang tiga bulan; status tidak mengubah visibilitas publik.'),
            Textarea::make('import_notes')->label('Catatan data sumber')->readOnly()->saved(false)->visible(fn (?Assessor $record): bool => filled($record?->import_notes))->columnSpanFull(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nama Asesor')->searchable()->sortable(),
            TextColumn::make('certificate')->label('Nomor Sertifikat (MET)')->searchable(),
            TextColumn::make('field')->label('Bidang')->searchable(),
            TextColumn::make('expires_on')->label('Masa berlaku sampai')->placeholder('Belum diketahui')->date('d M Y')->sortable(),
            TextColumn::make('grace_ends_on')->label('Batas masa tenggang')->state(fn (Assessor $record) => $record->graceEndsOn())->date('d M Y'),
            TextColumn::make('expiry_status')->label('Status masa berlaku')->state(fn (Assessor $record): string => $record->expiryStatus())->badge()->color(fn (string $state): string => Assessor::EXPIRY_STATUSES[$state]),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->filters([
            TernaryFilter::make('is_active')->label('Status aktif'),
            SelectFilter::make('expiry_status')->label('Status masa berlaku')->options(array_combine(array_keys(Assessor::EXPIRY_STATUSES), array_keys(Assessor::EXPIRY_STATUSES)))->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, string $status): Builder => $query->withExpiryStatus($status))),
            Filter::make('critical')->label('Asesor kritis')->query(fn (Builder $query): Builder => $query->critical()),
        ])
            ->description('Tahan Shift lalu seret untuk menggeser tabel. Gunakan scrollbar atau tombol panah sebagai alternatif.')
            ->headerActions([ExportAction::make()->label('Ekspor')->exports([ContentExport::make()])->authorize('manage-admin')])
            ->toolbarActions([ExportBulkAction::make()->label('Ekspor terpilih')->exports([ContentExport::make()])->authorize('manage-admin')])
            ->emptyStateHeading('Belum ada data yang sesuai')->emptyStateActions([CreateAction::make()->label('Tambah')->modalWidth('5xl')])
            ->recordAction('edit')->recordActions([Action::make('deactivate')->label('Nonaktifkan')->color('warning')->requiresConfirmation()->visible(fn ($record) => $record->is_active)->authorize('manage-admin')->action(fn ($record) => $record->update(['is_active' => false])), EditAction::make()->label('Edit')->modalWidth('5xl'), DeleteAction::make()->label('Hapus')]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAssessors::route('/')];
    }
}

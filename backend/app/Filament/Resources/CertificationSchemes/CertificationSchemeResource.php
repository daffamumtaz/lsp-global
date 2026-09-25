<?php

namespace App\Filament\Resources\CertificationSchemes;

use App\Filament\Resources\CertificationSchemes\Pages\ManageCertificationSchemes;
use App\Filament\Resources\UnavailableResource;
use App\Models\CertificationScheme;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class CertificationSchemeResource extends UnavailableResource
{
    protected static ?string $model = CertificationScheme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Item';

    protected static ?string $navigationLabel = 'Skema Sertifikasi';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama Skema')->required()->maxLength(255),
            TextInput::make('category')->label('Kategori')->required()->maxLength(255),
            TextInput::make('level')->label('Level')->maxLength(255),
            TextInput::make('price')->label('Harga')->numeric()->minValue(0)->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Skema')->searchable()->sortable(),
                TextColumn::make('category')->label('Kategori')->searchable(),
                TextColumn::make('level')->label('Level'),
                TextColumn::make('price')->label('Harga')->money('IDR'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->headerActions([ExportAction::make()->label('Ekspor Excel')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->contextMenuActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCertificationSchemes::route('/')];
    }
}

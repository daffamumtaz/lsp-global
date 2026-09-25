<?php

namespace App\Filament\Resources\SitePages;

use App\Filament\Resources\SitePages\Pages\ListSitePageActivities;
use App\Filament\Resources\SitePages\Pages\ManageSitePages;
use App\Filament\Resources\UnavailableResource;
use App\Models\SitePage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class SitePageResource extends UnavailableResource
{
    protected static ?string $model = SitePage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Landing Page';

    protected static string|\UnitEnum|null $navigationGroup = 'Edit Halaman';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kind')->label('Jenis Halaman')->options([
                'landing' => 'Landing Page',
                'profile' => 'Profil LSP',
                'information' => 'Informasi',
                'identity' => 'Identitas',
            ])->required()->unique(ignoreRecord: true),
            TextInput::make('title')->label('Judul')->required()->maxLength(255),
            Textarea::make('summary')->label('Ringkasan')->rows(3),
            Textarea::make('body')->label('Konten')->rows(8),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Halaman')->searchable()->sortable(),
                TextColumn::make('kind')->label('Jenis')->badge(),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime('d M Y H:i')->sortable(),
            ])
            ->headerActions([ExportAction::make()->label('Ekspor Excel')])
            ->recordActions([
                EditAction::make(),
                Action::make('history')->label('Riwayat')->url(fn (SitePage $record): string => static::getUrl('activities', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->contextMenuActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSitePages::route('/'),
            'activities' => ListSitePageActivities::route('/{record}/activities'),
        ];
    }
}

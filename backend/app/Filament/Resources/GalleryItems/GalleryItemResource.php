<?php

namespace App\Filament\Resources\GalleryItems;

use App\Filament\Resources\GalleryItems\Pages\ManageGalleryItems;
use App\Filament\Resources\UnavailableResource;
use App\Models\GalleryItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GalleryItemResource extends UnavailableResource
{
    protected static ?string $model = GalleryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Item';

    protected static ?string $navigationLabel = 'Galeri';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Judul')->required()->maxLength(255),
            TextInput::make('year')->label('Tahun')->maxLength(4),
            Textarea::make('description')->label('Deskripsi')->rows(3),
            SpatieMediaLibraryFileUpload::make('image')->label('Gambar')->collection('gallery')->image(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')->label('Gambar')->collection('gallery'),
                TextColumn::make('title')->label('Galeri')->searchable()->sortable(),
                TextColumn::make('year')->label('Tahun')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->contextMenuActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGalleryItems::route('/')];
    }
}

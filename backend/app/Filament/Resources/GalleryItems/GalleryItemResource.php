<?php

namespace App\Filament\Resources\GalleryItems;

use App\Filament\Resources\GalleryItems\Pages\ManageGalleryItems;
use App\Models\GalleryItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GalleryItemResource extends Resource
{
    protected static ?string $model = GalleryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Data Sertifikasi';

    protected static ?string $navigationLabel = 'Galeri';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Galeri';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('media_type')->label('Jenis Album')->options(['photo' => 'Foto', 'video' => 'Video'])->required()->in(['photo', 'video'])->live()
                ->disabled(fn (?GalleryItem $record): bool => $record === null || $record->media_type !== null)->dehydrated()
                ->helperText('Jenis album dikunci setelah disimpan. Album lama yang kosong harus memilih jenis sebelum mengunggah.'),
            TextInput::make('title')->label('Nama Kegiatan')->required()->maxLength(255),
            TextInput::make('location')->label('Lokasi')->required()->maxLength(255),
            DatePicker::make('date')->label('Tanggal')->required()->minDate('1900-01-01')->maxDate('2100-12-31'),
            Textarea::make('description')->label('Deskripsi')->rows(3)->maxLength(10000),
            SpatieMediaLibraryFileUpload::make('image')->label(fn (Get $get): string => $get('media_type') === 'video' ? 'Video' : 'Foto')->collection('gallery')->multiple()->reorderable()->acceptedFileTypes(fn (Get $get): array => GalleryItem::acceptedMimeTypes($get('media_type')))->maxSize(fn (): int => config('filesystems.max_upload_size_kb'))->disk('public')->visibility('public')->required()->disabled(fn (Get $get): bool => blank($get('media_type')))->helperText(fn (Get $get): string => $get('media_type') === 'video'
                ? 'File MP4 atau WebM, maksimal '.(config('filesystems.max_upload_size_kb') / 1024).' MB per file. Disarankan 1920 × 1080 px (1080p), rasio 16:9.'
                : 'File JPEG, PNG, atau WebP, maksimal '.(config('filesystems.max_upload_size_kb') / 1024).' MB per file. Disarankan 1920 × 1080 px, rasio 16:9; foto potret juga diterima.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ViewColumn::make('image')->label('Foto / Video')->view('filament.tables.gallery-cover'),
                TextColumn::make('media_type')->label('Jenis Album')->formatStateUsing(fn (?string $state): string => match ($state) {
                    'photo' => 'Foto', 'video' => 'Video', default => 'Belum ditentukan'
                }),
                TextColumn::make('title')->label('Nama Kegiatan')->searchable()->sortable(),
                TextColumn::make('location')->label('Lokasi')->searchable(),
                TextColumn::make('date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('year')->label('Tahun')->sortable(),
            ])
            ->filters([SelectFilter::make('year')->label('Tahun')->options(fn () => GalleryItem::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all())])
            ->emptyStateHeading('Belum ada album galeri')->emptyStateActions([static::chooseMediaAction()])
            ->modifyQueryUsing(fn ($query) => $query->with('media'))
            ->recordAction('edit')->recordActions([EditAction::make()->label('Edit'), DeleteAction::make()->label('Hapus')])
            ->contextMenuActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGalleryItems::route('/')];
    }

    public static function chooseMediaAction(): Action
    {
        return Action::make('create')->label('Tambah galeri')->modalHeading('Pilih jenis album')->modalDescription('Foto: beberapa foto dapat diunggah sekaligus. Video: beberapa video dapat diunggah sekaligus.')->modalSubmitAction(false)
            ->extraModalFooterActions([
                Action::make('photo')->label('Tambah Foto')->icon(Heroicon::OutlinedPhoto)->action(fn ($livewire) => $livewire->replaceMountedAction('createAlbum', ['media_type' => 'photo'])),
                Action::make('video')->label('Tambah Video')->icon(Heroicon::OutlinedVideoCamera)->action(fn ($livewire) => $livewire->replaceMountedAction('createAlbum', ['media_type' => 'video'])),
            ]);
    }

    public static function createAlbumAction(): CreateAction
    {
        return CreateAction::make('createAlbum')->label('Tambah album')->modalWidth('5xl')->createAnother(false)
            ->fillForm(fn (array $arguments): array => ['media_type' => $arguments['media_type'] ?? null]);
    }
}

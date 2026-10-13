<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Exports\ContentExport;
use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Filament\Resources\UnavailableResource;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;

class ContactMessageResource extends UnavailableResource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Pesan Masuk';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sender')->label('Pengirim')->disabled(),
            TextInput::make('email')->label('Email')->disabled(),
            TextInput::make('subject')->label('Subjek')->disabled(),
            Textarea::make('message')->label('Pesan')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Tanggal')->dateTime('d M Y H:i')->timezone('Asia/Jakarta')->sortable(),
                TextColumn::make('sender')->label('Pengirim')->searchable(),
                TextColumn::make('subject')->label('Subjek')->searchable(),
                IconColumn::make('is_read')->label('Dibaca')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')->filters([TernaryFilter::make('is_read')->label('Status dibaca')->trueLabel('Dibaca')->falseLabel('Belum dibaca')])
            ->headerActions([ExportAction::make()->label('Ekspor')->authorize('manage-admin')->exports([ContentExport::make()])])
            ->toolbarActions([ExportBulkAction::make()->label('Ekspor pilihan')->authorize('manage-admin')->exports([ContentExport::make()])])
            ->recordActions([
                ViewAction::make()->label('Detail'),
                Action::make('markRead')->label('Tandai Dibaca')->icon('heroicon-o-check')->authorize('manage-admin')->visible(fn (ContactMessage $record): bool => ! $record->is_read)->action(function (ContactMessage $record): void {
                    Gate::authorize('manage-admin');
                    $record->update(['is_read' => true]);
                }),
            ])->emptyStateHeading('Belum ada pesan')->emptyStateDescription('Pesan yang masuk akan muncul di sini.');
    }

    public static function getPages(): array
    {
        return ['index' => ManageContactMessages::route('/')];
    }
}

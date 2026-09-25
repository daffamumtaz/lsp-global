<?php

namespace App\Filament\Resources\ContactMessages;

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
use Filament\Tables\Table;

class ContactMessageResource extends UnavailableResource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Pesan Masuk';

    protected static bool $shouldRegisterNavigation = false;

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
                TextColumn::make('created_at')->label('Tanggal')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('sender')->label('Pengirim')->searchable(),
                TextColumn::make('subject')->label('Subjek')->searchable(),
                IconColumn::make('is_read')->label('Dibaca')->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('markRead')->label('Tandai Dibaca')->icon('heroicon-o-check')->visible(fn (ContactMessage $record): bool => ! $record->is_read)->action(fn (ContactMessage $record) => $record->update(['is_read' => true])),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ManageContactMessages::route('/')];
    }
}

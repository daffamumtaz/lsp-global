<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\UnavailableResource;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends UnavailableResource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen';

    protected static ?string $navigationLabel = 'Manajemen Pengguna';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(255),
            TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')->label('Kata Sandi')->password()->required(fn (string $operation): bool => $operation === 'create')->dehydrated(fn (?string $state): bool => filled($state)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->contextMenuActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}

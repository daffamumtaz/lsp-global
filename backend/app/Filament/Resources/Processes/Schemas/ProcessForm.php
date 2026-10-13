<?php

namespace App\Filament\Resources\Processes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProcessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Judul Alur')->required()->maxLength(255),
            TextInput::make('slug')->label('Alamat Alur (slug)')->disabled()->dehydrated(false),
            Textarea::make('description')->label('Deskripsi')->maxLength(10000)->columnSpanFull(),
            Toggle::make('is_active')->label('Tampilkan di website')->default(true)->required(),
        ]);
    }
}

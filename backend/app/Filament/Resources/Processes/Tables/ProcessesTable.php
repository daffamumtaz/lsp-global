<?php

namespace App\Filament\Resources\Processes\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProcessesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Alur Asesmen')->searchable(), TextColumn::make('slug')->label('Alamat Alur'), IconColumn::make('is_active')->label('Tampil di website')->boolean(),
        ])->recordActions([EditAction::make()->label('Kelola Alur')]);
    }
}

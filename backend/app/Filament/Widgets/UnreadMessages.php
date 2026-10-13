<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UnreadMessages extends TableWidget
{
    protected static bool $isLazy = false;

    public static int $gridW = 6;

    public static int $gridMinW = 6;

    public static int $gridH = 11;

    public static int $gridMinH = 11;

    public static bool $gridSizeToContent = false;

    public function getTitle(): string
    {
        return 'Pesan belum dibaca';
    }

    public function table(Table $table): Table
    {
        return $table->heading($this->getTitle())->query(ContactMessage::query()->where('is_read', false)->latest())
            ->columns([TextColumn::make('sender')->label('Pengirim')->wrap(), TextColumn::make('subject')->label('Subjek')->wrap(), TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->timezone('Asia/Jakarta')])
            ->recordUrl(fn (ContactMessage $record) => ContactMessageResource::getUrl(parameters: ['tableAction' => 'view', 'tableActionRecord' => $record->id]))
            ->headerActions([Action::make('inbox')->label('Semua pesan')->url(ContactMessageResource::getUrl())])
            ->paginated([5])->defaultPaginationPageOption(5)->emptyStateHeading('Belum ada pesan yang belum dibaca.');
    }
}

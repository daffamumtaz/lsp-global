<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\History;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Spatie\Activitylog\Models\Activity;

class RecentActivities extends TableWidget
{
    protected static bool $isLazy = false;

    public static int $gridW = 6;

    public static int $gridMinW = 6;

    public static int $gridH = 11;

    public static int $gridMinH = 11;

    public static bool $gridSizeToContent = false;

    public function getTitle(): string
    {
        return 'Perubahan terbaru';
    }

    public function table(Table $table): Table
    {
        return $table->heading($this->getTitle())->query(Activity::query()->with('causer')->latest())
            ->columns([TextColumn::make('causer.name')->label('Admin')->placeholder('Sistem')->wrap(), TextColumn::make('description')->label('Perubahan')->formatStateUsing(fn (string $state): string => ['created' => 'Ditambahkan', 'updated' => 'Diperbarui', 'deleted' => 'Dihapus'][$state] ?? $state)->description(fn (Activity $record): string => class_basename($record->subject_type ?? ''))->wrap(), TextColumn::make('created_at')->label('Waktu')->dateTime('d M H:i')->wrap()->timezone('Asia/Jakarta')])
            ->headerActions([Action::make('history')->label('Semua riwayat')->url(History::getUrl())])
            ->paginated([5])->defaultPaginationPageOption(5)->emptyStateHeading('Belum ada perubahan.');
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class History extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.history';

    protected static ?string $title = 'Riwayat Perubahan';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        return $table->query(Activity::query()->with('causer'))->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->timezone('Asia/Jakarta')->sortable(),
                TextColumn::make('causer.name')->label('Admin')->placeholder('Sistem'),
                TextColumn::make('event')->label('Aksi')->formatStateUsing(fn (?string $state): string => ['created' => 'Tambah', 'updated' => 'Ubah', 'deleted' => 'Hapus', 'exported' => 'Ekspor'][$state] ?? 'Ubah'),
                TextColumn::make('description')->label('Perubahan')->searchable()->wrap(),
                TextColumn::make('subject_type')->label('Jenis data')->formatStateUsing(fn (?string $state): string => class_basename($state ?? ''))->toggleable(),
            ])->filters([
                SelectFilter::make('causer_id')->label('Admin')->options(fn (): array => User::query()->pluck('name', 'id')->all()),
                SelectFilter::make('subject_type')->label('Jenis data')->options(fn (): array => Activity::query()->whereNotNull('subject_type')->distinct()->pluck('subject_type')->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])->all()),
                SelectFilter::make('event')->label('Aksi')->options(['created' => 'Tambah', 'updated' => 'Ubah', 'deleted' => 'Hapus', 'exported' => 'Ekspor']),
                Filter::make('dates')->label('Tanggal')->schema([DatePicker::make('from')->label('Dari'), DatePicker::make('until')->label('Sampai')])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->where('created_at', '>=', CarbonImmutable::parse($date, 'Asia/Jakarta')->utc()))->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->where('created_at', '<', CarbonImmutable::parse($date, 'Asia/Jakarta')->addDay()->utc()))),
            ])->recordActions([ViewAction::make()->label('Detail')->schema([
                Textarea::make('attribute_changes')->label('Sebelum / Sesudah')->formatStateUsing(fn (Activity $record): string => json_encode($record->attribute_changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))->rows(12)->disabled(),
                Textarea::make('properties')->label('Detail perubahan')->formatStateUsing(fn (Activity $record): string => json_encode($record->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))->rows(8)->disabled(),
            ])])->emptyStateHeading('Belum ada riwayat perubahan')->emptyStateDescription('Perubahan konten dan akses admin akan tercatat di sini.');
    }
}

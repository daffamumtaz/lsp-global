<?php

namespace App\Providers;

use App\Models\Media;
use App\Models\User;
use App\Observers\MediaObserver;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config(['media-library.media_observer' => MediaObserver::class, 'media-library.media_model' => Media::class, 'media-library.max_file_size' => 104857600]);
        config(['livewire.temporary_file_upload.rules' => 'file|max:102400']);
    }

    public function boot(): void
    {
        Table::configureUsing(fn (Table $table) => $table->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action->button()));
        Repeater::configureUsing(fn (Repeater $repeater) => $repeater
            ->collapseAllAction(fn (Action $action) => $action->button())
            ->expandAllAction(fn (Action $action) => $action->button()));
        FileUpload::configureUsing(fn (FileUpload $field) => $field->openable()->previewable());
        Filament::serving(fn () => app()->setLocale('id'));
        Gate::before(fn (User $user): ?bool => $user->canAccessPanel(Filament::getPanel('admin')) ? null : false);
        Gate::define('manage-admin', fn (User $user): bool => $user->canAccessPanel(Filament::getPanel('admin')));
    }
}

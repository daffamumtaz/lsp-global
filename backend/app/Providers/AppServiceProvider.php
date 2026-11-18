<?php

namespace App\Providers;

use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\GalleryItem;
use App\Models\Media;
use App\Models\SchemeCategory;
use App\Models\Tuk;
use App\Models\User;
use App\Observers\ContentDirectoryObserver;
use App\Observers\MediaObserver;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $maximumUploadSize = config('filesystems.max_upload_size_kb');
        config(['media-library.media_observer' => MediaObserver::class, 'media-library.media_model' => Media::class, 'media-library.max_file_size' => $maximumUploadSize * 1024]);
        config(['livewire.temporary_file_upload.rules' => "file|max:$maximumUploadSize"]);
    }

    public function boot(): void
    {
        Password::defaults(fn (): Password => Password::min(12));
        RateLimiter::for('public-content', fn (Request $request): Limit => Limit::perMinute(max(1, (int) config('security.public_content_requests_per_minute')))->by($request->ip()));
        foreach ([Tuk::class, Assessor::class, CertificationScheme::class, SchemeCategory::class, GalleryItem::class, Media::class] as $model) {
            $model::observe(ContentDirectoryObserver::class);
        }

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

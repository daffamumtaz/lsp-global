<?php

namespace App\Providers;

use App\Filesystem\SupabaseStorageAdapter;
use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\GalleryItem;
use App\Models\Media;
use App\Models\Tuk;
use App\Models\User;
use App\Observers\ContentDirectoryObserver;
use App\Observers\MediaObserver;
use Aws\CommandInterface;
use Aws\Middleware;
use Aws\S3\S3Client;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use League\Flysystem\Filesystem;

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
        foreach ([Tuk::class, Assessor::class, CertificationScheme::class, GalleryItem::class, Media::class] as $model) {
            $model::observe(ContentDirectoryObserver::class);
        }

        Storage::extend('supabase', function (Application $app, array $config): AwsS3V3Adapter {
            $client = new S3Client(array_merge($config, [
                'version' => 'latest',
                'credentials' => ['key' => $config['key'], 'secret' => $config['secret']],
                'request_checksum_calculation' => 'when_required',
                'response_checksum_validation' => 'when_required',
            ]));
            // Supabase controls visibility on buckets and does not implement object ACLs.
            $client->getHandlerList()->appendInit(Middleware::mapCommand(function (CommandInterface $command): CommandInterface {
                unset($command['ACL']);

                return $command;
            }), 'supabase-no-object-acl');
            $adapter = new SupabaseStorageAdapter($client, $config['bucket'], $config['root'] ?? '');

            return new AwsS3V3Adapter(new Filesystem($adapter, $config), $adapter, $config, $client);
        });
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

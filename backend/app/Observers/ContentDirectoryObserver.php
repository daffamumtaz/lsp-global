<?php

namespace App\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ContentDirectoryObserver implements ShouldHandleEventsAfterCommit
{
    public const string CACHE_VERSION_KEY = 'content-directory:version';

    public function saved(Model $model): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, (string) Str::uuid());
    }

    public function deleted(Model $model): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, (string) Str::uuid());
    }
}

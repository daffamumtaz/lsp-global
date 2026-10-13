<?php

namespace App\Observers;

use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\Models\Observers\MediaObserver as BaseObserver;

class MediaObserver extends BaseObserver
{
    /** Upload menulis berkas sebelum commit; rollback harus membersihkan berkas tersebut. */
    public function created(Media $media): void
    {
        if ($media->getConnection()->transactionLevel() > 0) {
            $media->getConnection()->afterRollBack(fn () => app(Filesystem::class)->removeAllFiles($media));
        }
    }

    /** Tunda penghapusan berkas sampai commit agar rollback tidak menghilangkan media yang masih dipakai. */
    public function deleted(Media $media): void
    {
        $media->getConnection()->afterCommit(fn () => parent::deleted($media));
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('media:sync-to-supabase {--source= : Folder media lokal, default storage/app/public} {--dry-run : Hitung file tanpa mengunggah}')]
#[Description('Menyalin media lokal ke Supabase dengan path tetap tanpa menimpa file remote')]
class SyncPublicMediaToSupabase extends Command
{
    public function handle(): int
    {
        if (config('filesystems.disks.public.driver') !== 'supabase') {
            $this->error('Set PUBLIC_FILESYSTEM_DRIVER=supabase sebelum menjalankan sinkronisasi.');

            return self::FAILURE;
        }
        $root = $this->option('source') ?: storage_path('app/public');
        if (! is_dir($root)) {
            $this->error('Folder sumber media tidak ditemukan.');

            return self::FAILURE;
        }
        $source = Storage::build(['driver' => 'local', 'root' => $root, 'throw' => true]);
        $destination = Storage::disk('public');
        $copied = 0;
        $skipped = 0;
        foreach ($source->allFiles() as $path) {
            if ($destination->exists($path)) {
                $skipped++;

                continue;
            }
            if (! $this->option('dry-run')) {
                $stream = $source->readStream($path);
                try {
                    $destination->put($path, $stream, 'public');
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            }
            $copied++;
        }
        $action = $this->option('dry-run') ? 'akan disalin' : 'disalin';
        $this->info("$copied file $action; $skipped file remote dipertahankan. File sumber tetap tersedia.");

        return self::SUCCESS;
    }
}

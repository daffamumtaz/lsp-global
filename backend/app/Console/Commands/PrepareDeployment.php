<?php

namespace App\Console\Commands;

use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\Tuk;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('deployment:prepare {--import-content : Impor konten awal hanya ke direktori yang masih kosong} {--import-assets : Lanjutkan impor aset tanpa mengimpor ulang direktori}')]
#[Description('Menjalankan migrasi deployment dan impor konten awal secara eksplisit')]
class PrepareDeployment extends Command
{
    public function handle(): int
    {
        if ($this->option('import-content') && $this->option('import-assets')) {
            $this->error('Pilih salah satu: --import-content atau --import-assets.');

            return self::FAILURE;
        }

        if ($this->call('migrate', ['--force' => true, '--no-interaction' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        if ($this->option('import-content')) {
            $defaultTuk = ['name' => 'Jobhun', 'website_url' => 'https://jobhun.id', 'logo_path' => 'tuks/jobhun.png', 'code' => null, 'city' => null, 'province' => null, 'address' => null, 'maps_url' => null, 'latitude' => null, 'longitude' => null, 'kind' => null, 'is_active' => true];
            $tuks = Tuk::query()->get();
            $hasExistingTuks = $tuks->count() > 1 || $tuks->contains(fn (Tuk $tuk): bool => $tuk->only(array_keys($defaultTuk)) !== $defaultTuk);
            if (CertificationScheme::query()->exists() || Assessor::query()->exists() || $hasExistingTuks) {
                $this->error('Direktori sudah berisi data. Impor awal dibatalkan agar perubahan admin tetap tersimpan.');

                return self::FAILURE;
            }
            foreach (['content:import-lspglobal', 'content:import-frontend-assets'] as $command) {
                if ($this->call($command, ['--no-interaction' => true]) !== self::SUCCESS) {
                    return self::FAILURE;
                }
            }
        }
        if ($this->option('import-assets')) {
            return $this->call('content:import-frontend-assets', ['--no-interaction' => true]);
        }

        return self::SUCCESS;
    }
}

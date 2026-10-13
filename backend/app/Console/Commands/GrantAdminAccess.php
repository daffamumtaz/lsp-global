<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('admin:grant {email : Email akun existing yang diizinkan menjadi admin}')]
#[Description('Memberikan akses panel kepada satu akun existing secara eksplisit')]
class GrantAdminAccess extends Command
{
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Akun tidak ditemukan. Tidak ada akun yang diubah.');

            return self::FAILURE;
        }
        $user->forceFill(['is_admin' => true])->save();
        $this->info('Akses admin diberikan kepada '.$user->email.'.');

        return self::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use App\Models\Tuk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TukSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->put('tuks/jobhun.png', file_get_contents(resource_path('data/jobhun.png')));
        $tuk = Tuk::query()->firstOrCreate(['name' => 'Jobhun'], ['website_url' => 'https://jobhun.id', 'logo_path' => 'tuks/jobhun.png', 'is_active' => true]);
        if ($tuk->logo_path === null) {
            $tuk->update(['logo_path' => 'tuks/jobhun.png']);
        }
    }
}

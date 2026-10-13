<?php

namespace App\Console\Commands;

use App\Models\SitePage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('content:import-frontend-assets')]
#[Description('Memindahkan aset konten frontend ke storage dan mencatat path tanpa menimpa upload admin')]
class ImportFrontendAssets extends Command
{
    public function handle(): int
    {
        $public = dirname(base_path()).'/frontend/public/';
        DB::transaction(function () use ($public): void {
            $profile = SitePage::firstOrCreate(['kind' => 'profile'], ['title' => 'Tentang Kami', 'content' => SitePage::defaultProfileContent()]);
            $content = $profile->profileContent();
            $crops = ['photo_path' => [15, 24, 278, 402], 'photo_two_path' => [315, 72, 278, 238], 'photo_three_path' => [315, 331, 278, 238]];
            foreach ($crops as $field => [$left, $top, $width, $height]) {
                if (filled($content['profile'][$field])) {
                    continue;
                }
                $source = imagecreatefrompng($public.'figma/profile-company.png');
                $image = imagecrop($source, ['x' => $left, 'y' => $top, 'width' => $width, 'height' => $height]);
                if ($image === false) {
                    throw new \RuntimeException('Gagal memindahkan foto profil.');
                }
                ob_start();
                imagepng($image);
                $bytes = ob_get_clean();
                imagedestroy($image);
                imagedestroy($source);
                $path = 'profile/photos/'.$field.'.png';
                Storage::disk('public')->put($path, $bytes);
                $content['profile'][$field] = $path;
            }
            foreach ($content['certificates'] as &$certificate) {
                if (blank($certificate['image_path'] ?? null) && filled($certificate['fallback_image'] ?? null)) {
                    $certificate['image_path'] = $this->copy($public.'figma/'.$certificate['fallback_image'], 'profile/certificates/'.$certificate['fallback_image']);
                    $certificate['fallback_image'] = null;
                }
            }
            unset($certificate);
            $profile->update(['content' => $content]);
            $landing = SitePage::firstOrCreate(['kind' => 'landing'], ['title' => 'Landing Page', 'content' => SitePage::defaultContent()]);
            $content = $landing->landingContent();
            foreach (['background_path' => [$public.'images/office-boardroom.webp', 'landing/hero/office-boardroom.webp'], 'poster_path' => [$public.'profile-video-poster.jpg', 'landing/hero/profile-video-poster.jpg'], 'video_path' => [resource_path('media/video-profile-lfRo5LN6.webm'), 'landing/hero/profile-video.webm']] as $field => [$source, $path]) {
                if (blank($content['hero'][$field] ?? null)) {
                    $content['hero'][$field] = $this->copy($source, $path);
                }
            }
            $landing->update(['content' => $content]);
            $identity = SitePage::firstOrCreate(['kind' => 'identity'], ['title' => 'Identitas', 'content' => SitePage::identityContent()]);
            $content = $identity->content;
            if (blank($content['logo_path'] ?? null)) {
                $content['logo_path'] = $this->copy($public.'figma/imgLogoLspTikGlobal22.png', 'identity/logo.png');
            }
            if (blank($content['favicon_path'] ?? null)) {
                $content['favicon_path'] = $this->copy($public.'favicon.ico', 'identity/favicon.ico');
            }
            if (blank($content['admin_favicon_path'] ?? null)) {
                $content['admin_favicon_path'] = $this->copy(public_path('admin-favicon.png'), 'identity/admin-favicon.png');
            }
            $identity->update(['content' => $content]);
        });
        $this->info('Aset aktif tersedia di field media Filament. Upload admin yang sudah ada dipertahankan.');

        return self::SUCCESS;
    }

    private function copy(string $source, string $path): string
    {
        if (! is_file($source)) {
            throw new \RuntimeException('Aset tidak ditemukan: '.$source);
        }
        Storage::disk('public')->put($path, file_get_contents($source));

        return $path;
    }
}

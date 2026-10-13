<?php

namespace App\Http\Resources;

use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\SitePage;
use App\Models\Tuk;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LandingPageContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SitePage $page */
        $page = $this->resource;
        $content = $page->landingContent();
        $visible = static fn (array $items): array => array_values(array_filter($items, static fn (array $item): bool => $item['visible'] ?? true));
        $mediaUrl = static fn (?string $path): ?string => filled($path) ? url(Storage::disk('public')->url($path)) : null;

        $identity = SitePage::identityContent();

        return [
            'sections' => SitePage::sectionHeadings(),
            'identity' => [
                ...$identity,
                'logo_url' => $mediaUrl($identity['logo_path']),
                'icon_url' => $mediaUrl($identity['icon_path']),
                'favicon_url' => $mediaUrl($identity['favicon_path']),
            ],
            'hero' => [
                'title' => $content['hero']['title'],
                'hook' => $content['hero']['hook'],
                'description' => trim($content['hero']['description'] ?? ''),
                'strengths' => $content['hero']['strengths'],
                'video_url' => $mediaUrl($content['hero']['video_path']) ?? route('media.profile-video'),
                'poster_url' => $mediaUrl($content['hero']['poster_path']),
                'background_url' => $mediaUrl($content['hero']['background_path'] ?? null),
            ],
            'about' => $content['about'],
            'partners_section' => $content['partners_section'],
            'why_us' => [
                'hook' => $content['why_us']['hook'],
                'description' => trim($content['why_us']['description'] ?? ''),
                'features' => array_map(static fn (array $item): array => [
                    'title' => $item['title'],
                    'caption' => trim($item['caption'] ?? ''),
                    'icon' => $item['icon'],
                    'href' => $item['href'],
                ], $visible($content['why_us']['features'])),
            ],
            'social_links' => $identity['social_links'],
            'highlighted_scheme_ids' => array_values(array_map('strval', $content['highlighted_scheme_ids'])),
            'statistics' => [
                ['value' => (string) CertificationScheme::query()->where('is_active', true)->count(), 'label' => "Skema\nSertifikasi"],
                ['value' => (string) Tuk::query()->where('is_active', true)->count(), 'label' => "Tempat Uji\nKompetensi (TUK)"],
                ['value' => (string) Assessor::query()->where('is_active', true)->count(), 'label' => "Asesor\nKompeten"],
            ],
            'reviews' => [
                'hook' => $content['reviews']['hook'],
                'description' => trim($content['reviews']['description'] ?? ''),
                'items' => array_map(static fn (array $item): array => [
                    'name' => $item['name'],
                    'occupation' => $item['occupation'],
                    'quote' => $item['quote'],
                    'photo_url' => $mediaUrl($item['photo_path'] ?? null),
                ], $visible($content['reviews']['items'])),
            ],
            'partners' => Tuk::query()->where('is_active', true)->orderBy('name')->get()->map(static fn (Tuk $tuk): array => [
                'name' => $tuk->name,
                'url' => $tuk->website_url ?? '',
                'logo_url' => $mediaUrl($tuk->logo_path),
            ])->all(),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }
}

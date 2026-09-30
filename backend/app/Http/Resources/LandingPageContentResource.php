<?php

namespace App\Http\Resources;

use App\Models\SitePage;
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

        return [
            'hero' => [
                'hook' => $content['hero']['hook'],
                'description' => $content['hero']['description'],
                'video_url' => $mediaUrl($content['hero']['video_path']),
                'poster_url' => $mediaUrl($content['hero']['poster_path']),
            ],
            'why_us' => [
                'hook' => $content['why_us']['hook'],
                'description' => $content['why_us']['description'],
                'features' => array_map(static fn (array $item): array => [
                    'title' => $item['title'],
                    'caption' => $item['caption'],
                    'icon' => $item['icon'],
                ], $visible($content['why_us']['features'])),
            ],
            'statistics' => array_map(static fn (array $item): array => [
                'value' => $item['value'],
                'label' => $item['label'],
            ], $visible($content['statistics'])),
            'reviews' => [
                'hook' => $content['reviews']['hook'],
                'description' => $content['reviews']['description'],
                'items' => array_map(static fn (array $item): array => [
                    'name' => $item['name'],
                    'occupation' => $item['occupation'],
                    'quote' => $item['quote'],
                    'photo_url' => $mediaUrl($item['photo_path'] ?? null),
                ], $visible($content['reviews']['items'])),
            ],
            'partners' => array_map(static fn (array $item): array => [
                'name' => $item['name'],
                'url' => $item['url'],
                'logo_url' => $mediaUrl($item['logo_path'] ?? null),
            ], $visible($content['partners'])),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }
}

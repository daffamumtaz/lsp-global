<?php

namespace App\Http\Resources;

use App\Models\SitePage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePageContentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var SitePage $page */
        $page = $this->resource;
        $content = $page->profileContent();
        $profile = $content['profile'];
        $mediaUrl = static fn (?string $path): ?string => filled($path) ? url(Storage::disk('public')->url($path)) : null;

        return [
            'profile' => [
                'hook' => $profile['hook'],
                'description' => filled(trim(strip_tags($profile['description'] ?? ''))) ? Str::sanitizeHtml($profile['description']) : '',
                'photo_urls' => array_map($mediaUrl, [$profile['photo_path'], $profile['photo_two_path'], $profile['photo_three_path']]),
            ],
            'vision' => [
                'hook' => $content['vision']['hook'],
                'vision' => Str::sanitizeHtml($content['vision']['vision']),
                'mission' => Str::sanitizeHtml($content['vision']['mission']),
            ],
            'certificates' => array_values(array_map(static fn (array $item): array => [
                'id' => $item['id'], 'title' => $item['title'], 'number' => $item['number'] ?? '', 'description' => trim($item['description'] ?? ''),
                'image_url' => $mediaUrl($item['image_path'] ?? null), 'fallback_image' => $item['fallback_image'] ?? null,
            ], array_filter($content['certificates'], static fn (array $item): bool => $item['visible'] ?? true))),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }
}

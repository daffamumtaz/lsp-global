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
        $nodes = array_column($content['organization'], null, 'id');
        $visibleNodes = array_filter($nodes, static function (array $node) use ($nodes): bool {
            $seen = [];
            while (true) {
                if (! ($node['visible'] ?? true) || in_array($node['id'], $seen, true)) {
                    return false;
                }
                $seen[] = $node['id'];
                if ($node['parent_id'] === null) {
                    return true;
                }
                if (! isset($nodes[$node['parent_id']])) {
                    return false;
                }
                $node = $nodes[$node['parent_id']];
            }
        });

        return [
            'profile' => [
                'hook' => $profile['hook'],
                'description' => Str::sanitizeHtml($profile['description']),
                'photo_urls' => array_values(array_filter(array_map($mediaUrl, [$profile['photo_path'], $profile['photo_two_path'], $profile['photo_three_path']]))),
            ],
            'vision' => [
                'hook' => $content['vision']['hook'],
                'vision' => Str::sanitizeHtml($content['vision']['vision']),
                'mission' => Str::sanitizeHtml($content['vision']['mission']),
            ],
            'organization' => array_values(array_map(static fn (array $node): array => [
                'id' => $node['id'], 'parent_id' => $node['parent_id'], 'order' => $node['order'], 'title' => $node['title'], 'name' => $node['name'],
            ], $visibleNodes)),
            'certificates' => array_values(array_map(static fn (array $item): array => [
                'id' => $item['id'], 'title' => $item['title'], 'description' => $item['description'],
                'image_url' => $mediaUrl($item['image_path'] ?? null), 'fallback_image' => $item['fallback_image'] ?? null,
            ], array_filter($content['certificates'], static fn (array $item): bool => $item['visible'] ?? true))),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }
}

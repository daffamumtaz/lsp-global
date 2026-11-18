<?php

namespace Database\Seeders;

use App\Models\SitePage;
use Illuminate\Database\Seeder;

class DummyReviewsSeeder extends Seeder
{
    public function run(): void
    {
        $page = SitePage::query()->firstOrCreate(['kind' => 'landing'], ['title' => 'Landing Page', 'content' => SitePage::defaultContent()]);
        $content = $page->landingContent();
        $items = json_decode(file_get_contents(resource_path('data/dummy-reviews.json')), true, 512, JSON_THROW_ON_ERROR);
        $names = array_column($items, 'name');
        $existing = array_filter($content['reviews']['items'], static fn (array $review): bool => ! in_array($review['name'], $names, true) && ! str_ends_with($review['name'], ' (Contoh)') && ! ($review['name'] === 'Daffa Naufal Mumtaz' && $review['occupation'] === 'Head of XYZ Company, West Java'));
        $content['reviews']['items'] = [...array_values($existing), ...$items];
        $page->update(['content' => $content]);
    }
}

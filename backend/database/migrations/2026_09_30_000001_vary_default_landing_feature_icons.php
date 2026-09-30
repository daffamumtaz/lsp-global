<?php

use App\Models\SitePage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = SitePage::defaultContent()['why_us']['features'];
        foreach (DB::table('site_pages')->where('kind', 'landing')->get() as $page) {
            $content = json_decode($page->content ?? '{}', true, flags: JSON_THROW_ON_ERROR);
            $changed = false;
            foreach ($content['why_us']['features'] ?? [] as $index => $feature) {
                foreach ($defaults as $default) {
                    if (($feature['icon'] ?? null) === 'award'
                        && ($feature['title'] ?? null) === $default['title']
                        && ($feature['caption'] ?? null) === $default['caption']
                        && $default['icon'] !== 'award') {
                        $content['why_us']['features'][$index]['icon'] = $default['icon'];
                        $changed = true;
                    }
                }
            }
            if ($changed) {
                DB::table('site_pages')->where('id', $page->id)->update([
                    'content' => json_encode($content, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Preserve icon choices that administrators may have edited after this update.
    }
};

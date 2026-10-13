<?php

use App\Models\CertificationScheme;
use App\Models\SitePage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = SitePage::defaultContent();
        $old = [
            'hero' => [
                'hook' => 'Wujudkan Kompetensi Profesional Anda',
                'description' => 'LSP TIK Global siap mencetak Sumber Daya Manusia (SDM) TIK yang unggul, profesional, dan kompeten untuk menghadapi tantangan industri global.',
            ],
            'why_us' => [
                'hook' => 'Mengapa Memilih LSP TIK Global?',
                'description' => 'Kami berkomitmen memberikan layanan sertifikasi kompetensi yang profesional, terpercaya, dan berorientasi pada kebutuhan dunia kerja.',
            ],
        ];
        $slugs = ['pengembang-web-web-developer', 'data-analyst', 'ilmuwan-data-data-scientist', 'penetration-tester', 'digital-marketing'];
        $schemes = CertificationScheme::query()->where('is_active', true)->whereIn('slug', $slugs)->pluck('id', 'slug');
        foreach (SitePage::query()->where('kind', 'landing')->get() as $page) {
            $content = $page->landingContent();
            foreach ($old as $section => $fields) {
                foreach ($fields as $field => $value) {
                    if ($content[$section][$field] === $value) {
                        $content[$section][$field] = $defaults[$section][$field];
                    }
                }
            }
            if (count($content['highlighted_scheme_ids']) <= 3 && $schemes->count() === 5) {
                $content['highlighted_scheme_ids'] = array_map(static fn (string $slug): string => (string) $schemes[$slug], $slugs);
            }
            SitePage::withoutEvents(static fn () => $page->update(['content' => $content]));
        }
    }

    public function down(): void {}
};

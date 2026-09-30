<?php

namespace App\Models;

use Database\Factories\SitePageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SitePage extends Model
{
    /** @use HasFactory<SitePageFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['kind', 'title', 'summary', 'body', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    /** @return array<string, mixed> */
    public static function defaultContent(): array
    {
        $review = [
            'name' => 'Daffa Naufal Mumtaz',
            'occupation' => 'Head of XYZ Company, West Java',
            'quote' => 'Proses pendaftaran hingga pelaksanaan Uji Kompetensi berjalan sangat lancar. Skema sertifikasinya benar-benar relevan dengan teknologi yang saya gunakan sehari-hari.',
            'photo_path' => null,
            'visible' => true,
        ];

        return [
            'hero' => [
                'hook' => 'Wujudkan Kompetensi Profesional Anda',
                'description' => 'LSP TIK Global siap mencetak Sumber Daya Manusia (SDM) TIK yang unggul, profesional, dan kompeten untuk menghadapi tantangan industri global.',
                'video_path' => null,
                'poster_path' => null,
            ],
            'why_us' => [
                'hook' => 'Mengapa Memilih LSP TIK Global?',
                'description' => 'Kami berkomitmen memberikan layanan sertifikasi kompetensi yang profesional, terpercaya, dan berorientasi pada kebutuhan dunia kerja.',
                'features' => [
                    ['title' => 'Sertifikat Kompetensi Diakui Secara Nasional', 'caption' => 'Memiliki sertifikat kompetensi resmi dari BNSP RI sebagai bukti sah keterampilan Anda.', 'icon' => 'award', 'visible' => true],
                    ['title' => 'Skema Sertifikasi Berbasis SKKNI', 'caption' => 'Seluruh skema sertifikasi mengacu pada SKKNI dan peraturan ESDM terbaru serta kebutuhan industri.', 'icon' => 'shield', 'visible' => true],
                    ['title' => 'Jaringan TUK di Berbagai Daerah', 'caption' => 'Memiliki jaringan Tempat Uji Kompetensi (TUK) yang luas dan tersebar di berbagai wilayah Indonesia.', 'icon' => 'network', 'visible' => true],
                ],
            ],
            'statistics' => [
                ['value' => '39', 'label' => "Skema\nSertifikasi", 'visible' => true],
                ['value' => '15+', 'label' => "Tempat Uji\nKompetensi (TUK)", 'visible' => true],
                ['value' => '72+', 'label' => "Asesor\nKompeten", 'visible' => true],
            ],
            'reviews' => [
                'hook' => 'Review Kami',
                'description' => 'Apa kata mereka tentang LSP TIK Global?',
                'items' => [$review, $review, $review],
            ],
            'partners' => array_fill(0, 10, [
                'name' => 'Jobhun',
                'url' => 'https://jobhun.id',
                'logo_path' => null,
                'visible' => true,
            ]),
        ];
    }

    /** @return array<string, mixed> */
    public function landingContent(): array
    {
        $defaults = self::defaultContent();
        $content = array_replace($defaults, $this->content ?? []);

        foreach (['hero', 'why_us', 'reviews'] as $section) {
            $content[$section] = array_replace($defaults[$section], $content[$section] ?? []);
        }

        return $content;
    }

    /** @return array<string, mixed> */
    public static function defaultProfileContent(): array
    {
        return json_decode(file_get_contents(resource_path('data/profile.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
    public function profileContent(): array
    {
        $defaults = self::defaultProfileContent();
        $content = array_replace($defaults, $this->content ?? []);
        foreach (['profile', 'vision'] as $section) {
            $content[$section] = array_replace($defaults[$section], $content[$section] ?? []);
        }

        return $content;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}

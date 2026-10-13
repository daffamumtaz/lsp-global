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
                'title' => 'Bersama LSP TIK Global',
                'hook' => 'Bangun Perjalanan Karier Anda dengan Kompetensi yang Diakui',
                'description' => 'Validasi kompetensi Anda dan tingkatkan kredibilitas di industri teknologi dan digital.',
                'strengths' => ['Lisensi Resmi BNSP', 'Kompetensi Terverifikasi', 'Asesor Profesional Berpengalaman', 'Jejaring Profesional luas', 'Relevan dengan Industri'],
                'video_path' => null,
                'poster_path' => null,
                'background_path' => null,
            ],
            'about' => [
                'hook' => 'Tentang LSP TIK Global',
                'description' => "LSP TIK Global berkomitmen membangun SDM yang kompeten dan profesional melalui sertifikasi kompetensi yang relevan dengan kebutuhan industri.\n\nSebagai Lembaga Sertifikasi Profesi di bidang Teknologi Informasi dan Komunikasi, kami hadir untuk memberikan pengakuan atas kompetensi yang dimiliki individu melalui proses asesmen yang objektif, terukur, dan mengacu pada standar kompetensi yang berlaku.\n\nDengan beragam skema sertifikasi di bidang IT dan digital serta didukung oleh asesor kompeten, LSP TIK Global membuka kesempatan bagi mahasiswa, profesional, maupun talenta digital untuk membuktikan keahlian, memperkuat kredibilitas profesional, dan meningkatkan daya saing di tengah perkembangan industri yang semakin dinamis.\n\nBagi kami, sertifikasi bukan sekadar tentang mendapatkan sertifikat, tetapi tentang memastikan setiap kompetensi memiliki bukti, pengakuan, dan nilai yang dapat dibawa untuk melangkah lebih jauh.",
            ],
            'why_us' => [
                'hook' => 'Mengapa Memilih Sertifikasi di LSP TIK Global?',
                'description' => 'Jadikan kompetensi Anda lebih bernilai dengan pengakuan yang memperkuat kredibilitas dan posisi profesional di tengah persaingan industri.',
                'features' => [
                    ['title' => 'Sertifikat Kompetensi Diakui Secara Nasional', 'caption' => 'Memiliki sertifikat kompetensi resmi dari BNSP RI sebagai bukti sah keterampilan Anda.', 'icon' => 'award', 'href' => '/tentang-kami#lisensi', 'visible' => true],
                    ['title' => 'Skema Sertifikasi Berbasis SKKNI', 'caption' => 'Seluruh skema sertifikasi mengacu pada SKKNI dan peraturan ESDM terbaru serta kebutuhan industri.', 'icon' => 'shield', 'href' => '/tentang-kami#lisensi', 'visible' => true],
                    ['title' => 'Jaringan TUK di Berbagai Daerah', 'caption' => 'Memiliki jaringan Tempat Uji Kompetensi (TUK) yang luas dan tersebar di berbagai wilayah Indonesia.', 'icon' => 'network', 'href' => '/informasi#tuk', 'visible' => true],
                ],
            ],
            'social_links' => ['Facebook' => 'https://www.facebook.com/', 'Instagram' => 'https://www.instagram.com/', 'YouTube' => 'https://www.youtube.com/'],
            'partners_section' => ['hook' => 'Mitra Kami', 'description' => 'Dipercaya oleh berbagai perusahaan dan institusi pendidikan'],
            'highlighted_scheme_ids' => [],
            'reviews' => [
                'hook' => 'Review Kami',
                'description' => 'Apa kata mereka tentang LSP TIK Global?',
                'items' => [$review, $review, $review],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function landingContent(): array
    {
        $defaults = self::defaultContent();
        $content = array_replace($defaults, $this->content ?? []);

        foreach (['hero', 'about', 'why_us', 'reviews', 'social_links', 'partners_section'] as $section) {
            $content[$section] = array_replace($defaults[$section], $content[$section] ?? []);
        }

        $content['why_us']['features'] = array_map(static fn (array $item): array => [
            ...$item,
            'href' => $item['href'] ?? ($item['icon'] === 'network' ? '/informasi#tuk' : '/tentang-kami#lisensi'),
        ], $content['why_us']['features']);

        return $content;
    }

    /** @return array<string, mixed> */
    public static function defaultProfileContent(): array
    {
        return json_decode(file_get_contents(resource_path('data/profile.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function defaultInformationContent(): array
    {
        return json_decode(file_get_contents(resource_path('data/information.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function informationContent(): array
    {
        $defaults = self::defaultInformationContent();
        $content = [];
        foreach ($defaults as $section => $fields) {
            foreach ($fields as $field => $default) {
                $value = $this->content[$section][$field] ?? $default;
                $content[$section][$field] = is_string($value) ? $value : $default;
            }
        }

        return $content;
    }

    /** @return array<string, mixed> */
    public function profileContent(): array
    {
        $defaults = self::defaultProfileContent();
        $content = array_replace($defaults, $this->content ?? []);
        foreach (['profile', 'vision'] as $section) {
            $content[$section] = array_replace($defaults[$section], $content[$section] ?? []);
        }

        $numbers = array_column($defaults['certificates'], 'number', 'title');
        foreach ($content['certificates'] as &$certificate) {
            $certificate['number'] ??= $numbers[$certificate['title']] ?? '';
        }
        unset($certificate);

        return $content;
    }

    public static function sectionHeadings(): array
    {
        $defaults = json_decode(file_get_contents(resource_path('data/section-headings.json')), true, 512, JSON_THROW_ON_ERROR);
        $saved = self::query()->where('kind', 'sections')->value('content') ?? [];

        return array_replace_recursive($defaults, array_intersect_key($saved, $defaults));
    }

    public static function identityContent(): array
    {
        $defaults = [
            'brand_name' => 'LSP TIK Global',
            'phone' => '021-27651353',
            'email' => 'info@lspglobal.id',
            'whatsapp' => '+62 851-9553-3360',
            'address' => 'Jl. Lebak Bulus Raya Komplek Ruko Victory 88 No. 88 B, Cilandak, Jakarta Selatan',
            'maps_url' => null,
            'working_hours' => '',
            'license_number' => 'BNSP-LSP-2150-ID',
            'whatsapp_message' => 'Halo LSP TIK Global, saya ingin bertanya tentang sertifikasi.',
            'whatsapp_registration_message' => 'Halo {brand_name}, saya ingin mendaftar sertifikasi skema {scheme_name}.',
            'whatsapp_contact_message' => "Subjek: {subject}\nNama: {name}\nInstansi: {institution}\nEmail: {email}\nTelepon: {phone}\nPreferensi balasan: {preference}\n\n{message}",
            'copyright_text' => 'All rights reserved.',
            'logo_path' => null, 'icon_path' => null, 'favicon_path' => null, 'admin_favicon_path' => null,
            'footer_heading' => 'Bersama Membangun Kompetensi',
            'footer_caption' => 'Kami hadir untuk bersama membangun kompetensi yang terpercaya.',
            'footer_description' => 'Lembaga Sertifikasi Profesi bidang Teknologi Informasi & Komunikasi berlisensi BNSP.',
            'social_links' => self::query()->where('kind', 'landing')->first()?->landingContent()['social_links'] ?? self::defaultContent()['social_links'],
        ];
        $content = array_replace($defaults, self::query()->where('kind', 'identity')->first()?->content ?? []);
        $content['social_links'] = array_replace($defaults['social_links'], $content['social_links'] ?? []);

        return $content;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}

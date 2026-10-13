<?php

namespace App\Filament\Pages;

use App\Models\SitePage;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SectionHeadings extends Page
{
    protected string $view = 'filament.pages.section-headings';

    protected static ?string $title = 'Judul & Subjudul Section';

    protected static string|\UnitEnum|null $navigationGroup = 'Konten Website';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $this->form->fill(SitePage::sectionHeadings());
    }

    public function form(Schema $schema): Schema
    {
        $labels = ['home_schemes' => 'Beranda — Skema Unggulan', 'about_hero' => 'Tentang Kami — Hero', 'about_vision' => 'Tentang Kami — Visi', 'about_mission' => 'Tentang Kami — Misi', 'about_structure' => 'Tentang Kami — Struktur Organisasi', 'about_legal' => 'Tentang Kami — Lisensi', 'schemes_hero' => 'Skema Sertifikasi — Hero', 'gallery_hero' => 'Galeri — Hero', 'gallery_albums' => 'Galeri — Album', 'gallery_year' => 'Galeri — Tahun', 'footer_explore' => 'Footer — Jelajahi', 'footer_contact' => 'Footer — Kontak', 'footer_social' => 'Footer — Media Sosial', 'partners_hero' => 'Seluruh Mitra — Hero', 'contact_hero' => 'Kontak — Hero', 'contact_info' => 'Kontak — Pengantar', 'contact_details' => 'Kontak — Detail', 'contact_socials' => 'Kontak — Media Sosial', 'contact_form' => 'Kontak — Form', 'contact_location' => 'Kontak — Lokasi', 'scheme_units' => 'Detail Skema — Unit', 'scheme_requirements' => 'Detail Skema — Persyaratan', 'scheme_basic' => 'Detail Skema — Persyaratan Dasar', 'scheme_administrative' => 'Detail Skema — Persyaratan Administrasi'];
        $sections = [];
        foreach ($labels as $key => $label) {
            $sections[] = Section::make($label)->schema([
                TextInput::make($key.'.title')->label('Judul')->required()->maxLength(255)->helperText($key === 'gallery_albums' ? 'Gunakan {media} untuk jenis foto atau video.' : ($key === 'gallery_year' ? 'Gunakan {year} untuk tahun album.' : null)),
                Textarea::make($key.'.subtitle')->label('Subjudul')->maxLength(2000)->rows(2),
            ])->columns(2)->collapsible()->collapsed();
        }

        return $schema->components($sections)->statePath('data');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        SitePage::query()->updateOrCreate(['kind' => 'sections'], ['title' => 'Judul & Subjudul Section', 'content' => $this->form->getState()]);
        Notification::make()->success()->title('Judul dan subjudul tersimpan.')->send();
    }
}

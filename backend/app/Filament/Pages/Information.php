<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Processes\ProcessResource;
use App\Models\SitePage;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Information extends Page
{
    protected string $view = 'filament.pages.information';

    protected static ?string $title = 'Informasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Konten Website';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $page = SitePage::query()->where('kind', 'information')->first() ?? new SitePage;
        $this->form->fill($page->informationContent());
    }

    public function form(Schema $schema): Schema
    {
        $sections = ['hero' => 'Hero', 'seo' => 'Metadata SEO', 'tuk' => 'Tabel TUK', 'assessor' => 'Tabel Asesor', 'journey' => 'Pengembangan Kompetensi', 'directory' => 'Tabel, Filter & Aksesibilitas', 'flow' => 'Alur: Kontrol, Status & Aksesibilitas'];
        $labels = ['title' => 'Judul', 'description' => 'Deskripsi', 'directory_title' => 'Nama Direktori', 'filter_label' => 'Label Filter', 'all_filter' => 'Semua Filter', 'search_placeholder' => 'Petunjuk Pencarian', 'name' => 'Nama', 'code' => 'Kode', 'city' => 'Kota', 'province' => 'Provinsi', 'address' => 'Alamat', 'certificate' => 'Sertifikat', 'field' => 'Bidang'];
        $components = [];
        foreach (['hero', 'tuk', 'assessor'] as $section) {
            $fields = array_intersect_key(SitePage::defaultInformationContent()[$section], array_flip(['title', 'description']));
            $inputs = [];
            foreach ($fields as $field => $default) {
                preg_match_all('/\{([a-z_]+)\}/', $default, $matches);
                $inputs[] = Textarea::make($section.'.'.$field)->label($labels[$field] ?? str($field)->replace('_', ' ')->title()->toString())
                    ->rows(2)->maxLength(2000)->required(! str_ends_with($field, 'description'))
                    ->helperText($matches[0] ? 'Placeholder otomatis: '.implode(', ', array_unique($matches[0])).'. Pertahankan nama di dalam kurung kurawal.' : null);
            }
            $components[] = Section::make($sections[$section])->schema($inputs)->columns(2)->collapsible()->collapsed($section !== 'hero');
        }

        return $schema->components($components)->statePath('data');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $state = $this->form->getState();
        $data = [];
        foreach (['hero', 'tuk', 'assessor'] as $section) {
            $data[$section] = array_intersect_key($state[$section] ?? [], array_flip(['title', 'description']));
        }
        foreach ($data as &$fields) {
            foreach ($fields as &$value) {
                $value ??= '';
            }
            unset($value);
        }
        unset($fields);
        SitePage::query()->updateOrCreate(['kind' => 'information'], ['title' => 'Informasi', 'content' => array_replace_recursive($this->informationPageContent(), $data)]);
        Notification::make()->success()->title('Perubahan tersimpan dan langsung tampil di situs.')->send();
    }

    protected function informationPageContent(): array
    {
        return (SitePage::query()->where('kind', 'information')->first() ?? new SitePage)->informationContent();
    }

    public function assessmentUrl(): string
    {
        return ProcessResource::getNavigationUrl();
    }
}

<?php

namespace App\Filament\Resources\SitePages\Pages;

use App\Filament\Resources\SitePages\SitePageResource;
use App\Models\CertificationScheme;
use App\Models\SitePage;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ManageSitePages extends Page
{
    protected static string $resource = SitePageResource::class;

    protected static ?string $title = 'Edit Halaman > Landing Page';

    protected string $view = 'filament.resources.site-pages.pages.manage-site-pages';

    /** @var array<string, mixed> */
    public ?array $heroData = [];

    /** @var array<string, mixed> */
    public ?array $videoData = [];

    /** @var array<string, mixed> */
    public ?array $whyData = [];

    public ?array $aboutData = [];

    /** @var array<string, mixed> */
    public ?array $highlightsData = [];

    /** @var array<string, mixed> */
    public ?array $reviewsData = [];

    public ?array $partnersData = [];

    public function mount(): void
    {
        $content = $this->landingPage()->landingContent();

        $this->heroForm->fill($content['hero']);
        $this->videoForm->fill($content['hero']);
        $this->whyForm->fill($content['why_us']);
        $this->aboutForm->fill($content['about']);
        $this->highlightsForm->fill(['ids' => $content['highlighted_scheme_ids']]);
        $this->reviewsForm->fill($content['reviews']);
        $this->partnersForm->fill($content['partners_section']);
        $this->whyData['features'] = $content['why_us']['features'];
    }

    public function heroForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Judul Utama')->required()->maxLength(80),
            TextInput::make('hook')->label('Kalimat Pendukung (Baris 1)')->required()->maxLength(120),
            Textarea::make('description')->label('Kalimat Pendukung (Baris 2)')->rows(3)->maxLength(400),
            Repeater::make('strengths')->label('5 Poin Ceklis di Kanan Hero')
                ->simple(TextInput::make('title')->required()->maxLength(120))
                ->required()->minItems(5)->maxItems(5)->reorderableWithButtons(),
        ])->statePath('heroData');
    }

    public function videoForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                FileUpload::make('background_path')->label('Gambar Latar Hero')->disk('public')->visibility('public')->directory('landing/hero')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->openable()->helperText('1 file JPEG, PNG, atau WebP, maksimal 5 MB. Disarankan 1920 × 1080 px, rasio 16:9.'),
                FileUpload::make('video_path')->label('Video Profil')->disk('public')->visibility('public')->directory('landing/hero')->acceptedFileTypes(['video/mp4', 'video/webm'])->maxSize(fn (): int => config('filesystems.max_upload_size_kb'))->openable()->helperText(fn (): string => '1 file MP4 atau WebM, maksimal '.(config('filesystems.max_upload_size_kb') / 1024).' MB. Disarankan 1920 × 1080 px (1080p), rasio 16:9.'),
                FileUpload::make('poster_path')->label('Gambar Sampul')->disk('public')->visibility('public')->directory('landing/hero')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->openable()->helperText('1 file JPEG, PNG, atau WebP, maksimal 5 MB. Disarankan 1920 × 1080 px, rasio 16:9.'),
            ]),
        ])->statePath('videoData');
    }

    public function saveVideo(): void
    {
        $this->saveSection('hero', $this->videoForm->getState());
    }

    public function aboutForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hook')->label('Judul Bagian')->required()->maxLength(120),
            Textarea::make('description')->label('Deskripsi')->required()->rows(12)->maxLength(3000),
        ])->statePath('aboutData');
    }

    public function saveAbout(): void
    {
        $this->saveSection('about', $this->aboutForm->getState());
    }

    public function whyForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hook')->label('Judul Bagian')->required()->maxLength(120),
            Textarea::make('description')->label('Deskripsi')->rows(3)->maxLength(400),
        ])->statePath('whyData');
    }

    public function highlightsForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('ids')->label('Skema Highlight')->multiple()->searchable()->reorderable()
                ->required(fn (): bool => CertificationScheme::query()->where('is_active', true)->whereNotNull('slug')->exists())
                ->options(fn (): array => CertificationScheme::query()->where('is_active', true)->whereNotNull('slug')->orderBy('name')->pluck('name', 'id')->all())
                ->minItems(fn (): int => min(5, CertificationScheme::query()->where('is_active', true)->whereNotNull('slug')->count()))->maxItems(5)
                ->helperText('Pilih 5 skema aktif. Urutan pilihan menentukan urutan showcase; jika data belum mencapai 5 skema, pilih seluruh skema yang tersedia.'),
        ])->statePath('highlightsData');
    }

    public function reviewsForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hook')->label('Judul Bagian')->required()->maxLength(120),
            Textarea::make('description')->label('Deskripsi')->rows(2)->maxLength(300),
            Repeater::make('items')->label('Review')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(120),
                    TextInput::make('occupation')->label('Jabatan atau asal')->required()->maxLength(160),
                ]),
                Textarea::make('quote')->label('Ulasan')->required()->rows(3)->maxLength(600),
                FileUpload::make('photo_path')->label('Foto')->disk('public')->visibility('public')->directory('landing/reviews')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->openable()->helperText('1 file JPEG, PNG, atau WebP, maksimal 5 MB. Disarankan 400 × 400 px, rasio 1:1.'),
                Toggle::make('visible')->label('Tampilkan')->default(true),
            ])->itemLabel(fn (array $state): ?string => $state['name'] ?? null)->collapsible()->reorderable()->reorderableWithButtons()->addActionLabel('Tambah Review'),
        ])->statePath('reviewsData');
    }

    public function editFeatureAction(): Action
    {
        return Action::make('editFeature')->label('Edit')->modalHeading('Poin Mengapa Kami')
            ->modalSubmitActionLabel('Terapkan')->modalDescription('Terapkan ke daftar, lalu klik Simpan Mengapa Kami untuk menerbitkan.')
            ->fillForm(fn (array $arguments): array => $this->whyData['features'][$arguments['index'] ?? -1] ?? ['visible' => true, 'icon' => 'award'])
            ->schema([
                TextInput::make('title')->label('Judul')->required()->maxLength(120),
                Textarea::make('caption')->label('Caption')->rows(3)->maxLength(300),
                Select::make('icon')->label('Ikon')->options(['award' => 'Penghargaan', 'shield' => 'Perisai', 'network' => 'Jaringan'])
                    ->live()->prefixIcon(fn (?string $state): Heroicon => match ($state) {
                        'shield' => Heroicon::OutlinedShieldCheck,
                        'network' => Heroicon::OutlinedGlobeAlt,
                        default => Heroicon::OutlinedTrophy,
                    })->required(),
                Select::make('href')->label('Tujuan Klik')->options(['/tentang-kami#lisensi' => 'Lisensi & Legalitas', '/informasi#tuk' => 'Tempat Uji Kompetensi (TUK)'])->default('/tentang-kami#lisensi')->required(),
                Toggle::make('visible')->label('Tampilkan')->default(true),
            ])->action(function (array $data, array $arguments): void {
                $this->applyItem('features', $data, $arguments);
            });
    }

    protected function applyItem(string $collection, array $data, array $arguments): void
    {
        $items = ($this->whyData['features'] ?? []);
        if (isset($arguments['index'])) {
            abort_unless(array_key_exists($arguments['index'], $items), 404);
            $items[$arguments['index']] = [...$data, 'href' => $data['href'] ?? $items[$arguments['index']]['href'] ?? '/tentang-kami#lisensi'];
        } else {
            $items[] = $data;
        }
        $this->setItems($collection, $items);
    }

    protected function setItems(string $collection, array $items): void
    {
        abort_unless($collection === 'features', 404);
        $this->whyData['features'] = array_values($items);
    }

    public function moveItem(string $collection, int $index, int $direction): void
    {
        abort_unless(in_array($direction, [-1, 1], true), 422);
        $items = ($this->whyData['features'] ?? []);
        $target = $index + $direction;
        if (isset($items[$index], $items[$target])) {
            [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
            $this->setItems($collection, $items);
        }
    }

    public function removeItemAction(): Action
    {
        return Action::make('removeItem')->label('Hapus')->color('danger')->requiresConfirmation()
            ->modalHeading('Hapus item dari daftar?')->modalDescription('Perubahan diterbitkan setelah bagian ini disimpan.')
            ->action(function (array $arguments): void {
                $collection = $arguments['collection'];
                $items = ($this->whyData['features'] ?? []);
                unset($items[$arguments['index']]);
                $this->setItems($collection, $items);
            });
    }

    public function saveHero(): void
    {
        $this->saveSection('hero', $this->heroForm->getState());
    }

    public function saveWhy(): void
    {
        $data = $this->whyForm->getState();
        $features = Validator::make(['features' => $this->whyData['features'] ?? []], [
            'features' => ['present', 'array', 'max:50'],
            'features.*' => ['array:title,caption,icon,visible,href'],
            'features.*.title' => ['required', 'string', 'max:120'],
            'features.*.caption' => ['nullable', 'string', 'max:300'],
            'features.*.icon' => ['required', 'in:award,shield,network'],
            'features.*.href' => ['required', 'in:/tentang-kami#lisensi,/informasi#tuk'],
            'features.*.visible' => ['required', 'boolean'],
        ])->validate();
        $this->saveSection('why_us', [...$data, 'features' => array_values($features['features'])]);
    }

    public function saveHighlights(): void
    {
        $this->saveSection('highlighted_scheme_ids', array_map('strval', $this->highlightsForm->getState()['ids'] ?? []));
    }

    public function saveReviews(): void
    {
        $this->saveSection('reviews', $this->reviewsForm->getState());
    }

    public function partnersForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hook')->label('Judul Bagian')->required()->maxLength(120),
            Textarea::make('description')->label('Caption')->rows(3)->maxLength(400),
        ])->statePath('partnersData');
    }

    public function savePartners(): void
    {
        $this->saveSection('partners_section', $this->partnersForm->getState());
    }

    protected function saveSection(string $section, array $data): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $page = $this->landingPage();
        $label = [
            'hero' => 'Hero',
            'about' => 'Tentang',
            'why_us' => 'Mengapa Kami',
            'highlighted_scheme_ids' => 'Skema Highlight',
            'reviews' => 'Review',
            'partners_section' => 'Mitra',
            'social_links' => 'Media Sosial',
        ][$section];

        // Baca ulang setelah lock agar penyimpanan satu bagian tidak menimpa bagian lain.
        DB::transaction(function () use ($page, $section, $data, $label): void {
            $page = SitePage::query()->lockForUpdate()->findOrFail($page->id);
            $content = $page->landingContent();
            $old = $content[$section] ?? null;
            $content[$section] = $section === 'hero' ? array_replace($content[$section], $data) : $data;
            SitePage::withoutEvents(static fn () => $page->update(['content' => $content]));
            activity()->performedOn($page)->causedBy(auth()->user())->event('updated')->withProperties(['section' => $section])->withChanges(['old' => [$section => $old], 'attributes' => [$section => $data]])->log('Mengubah '.$label);
        });

        Notification::make()->success()->title('Perubahan tersimpan dan langsung tampil di situs.')->send();
    }

    protected function landingPage(): SitePage
    {
        return SitePage::query()->firstOrCreate(['kind' => 'landing'], [
            'title' => 'Landing Page',
            'content' => SitePage::defaultContent(),
        ]);
    }

    public function videoUrl(): ?string
    {
        $path = $this->landingPage()->landingContent()['hero']['video_path'];

        return filled($path) ? Storage::disk('public')->url($path) : route('media.profile-video');
    }

    public function posterUrl(): ?string
    {
        $path = $this->landingPage()->landingContent()['hero']['poster_path'];

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}

<?php

namespace App\Filament\Resources\SitePages\Pages;

use App\Filament\Resources\SitePages\SitePageResource;
use App\Models\SitePage;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
    public ?array $whyData = [];

    /** @var array<string, mixed> */
    public ?array $statisticsData = [];

    /** @var array<string, mixed> */
    public ?array $reviewsData = [];

    /** @var array<string, mixed> */
    public ?array $partnersData = [];

    public function mount(): void
    {
        $content = $this->landingPage()->landingContent();

        $this->heroForm->fill($content['hero']);
        $this->whyForm->fill($content['why_us']);
        $this->statisticsForm->fill(['items' => $content['statistics']]);
        $this->reviewsForm->fill($content['reviews']);
        $this->partnersData = ['items' => $content['partners']];
        $this->whyData['features'] = $content['why_us']['features'];
    }

    public function heroForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hook')->label('Judul Hero')->required()->maxLength(120),
            Textarea::make('description')->label('Deskripsi')->required()->rows(3)->maxLength(400),
            Grid::make(2)->schema([
                FileUpload::make('video_path')->label('Video Profil')->disk('public')->visibility('public')->directory('landing/hero')->acceptedFileTypes(['video/mp4', 'video/webm'])->maxSize(102400)->openable()->helperText('MP4 atau WebM, maksimal 100 MB.'),
                FileUpload::make('poster_path')->label('Gambar Sampul')->disk('public')->visibility('public')->directory('landing/hero')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->openable()->helperText('JPEG, PNG, atau WebP, maksimal 5 MB.'),
            ]),
        ])->statePath('heroData');
    }

    public function whyForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hook')->label('Judul Bagian')->required()->maxLength(120),
            Textarea::make('description')->label('Deskripsi')->required()->rows(3)->maxLength(400),
        ])->statePath('whyData');
    }

    public function statisticsForm(Schema $schema): Schema
    {
        return $schema->components([
            Repeater::make('items')->label('Statistik')->table([
                TableColumn::make('Nilai'), TableColumn::make('Label'), TableColumn::make('Tampilkan'),
            ])->schema([
                TextInput::make('value')->label('Nilai')->required()->maxLength(12)->helperText('Contoh: 39 atau 15+.'),
                TextInput::make('label')->label('Label')->required()->maxLength(80),
                Toggle::make('visible')->label('Tampilkan')->default(true),
            ])->reorderable()->reorderableWithButtons()->addActionLabel('Tambah Statistik'),
        ])->statePath('statisticsData');
    }

    public function reviewsForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hook')->label('Judul Bagian')->required()->maxLength(120),
            Textarea::make('description')->label('Deskripsi')->required()->rows(2)->maxLength(300),
            Repeater::make('items')->label('Review')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(120),
                    TextInput::make('occupation')->label('Jabatan atau asal')->required()->maxLength(160),
                ]),
                Textarea::make('quote')->label('Ulasan')->required()->rows(3)->maxLength(600),
                FileUpload::make('photo_path')->label('Foto')->disk('public')->visibility('public')->directory('landing/reviews')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->openable(),
                Toggle::make('visible')->label('Tampilkan')->default(true),
            ])->itemLabel(fn (array $state): ?string => $state['name'] ?? null)->collapsible()->reorderable()->reorderableWithButtons()->addActionLabel('Tambah Review'),
        ])->statePath('reviewsData');
    }

    public function editFeatureAction(): Action
    {
        return Action::make('editFeature')->label('Tambah Poin')->modalHeading('Poin Mengapa Kami')
            ->modalSubmitActionLabel('Terapkan')->modalDescription('Terapkan ke daftar, lalu klik Simpan Mengapa Kami untuk menerbitkan.')
            ->fillForm(fn (array $arguments): array => $this->whyData['features'][$arguments['index'] ?? -1] ?? ['visible' => true, 'icon' => 'award'])
            ->schema([
                TextInput::make('title')->label('Judul')->required()->maxLength(120),
                Textarea::make('caption')->label('Caption')->required()->rows(3)->maxLength(300),
                Select::make('icon')->label('Ikon')->options(['award' => 'Penghargaan', 'shield' => 'Perisai', 'network' => 'Jaringan'])
                    ->live()->prefixIcon(fn (?string $state): Heroicon => match ($state) {
                        'shield' => Heroicon::OutlinedShieldCheck,
                        'network' => Heroicon::OutlinedGlobeAlt,
                        default => Heroicon::OutlinedTrophy,
                    })->required(),
                Toggle::make('visible')->label('Tampilkan')->default(true),
            ])->action(function (array $data, array $arguments): void {
                $this->applyItem('features', $data, $arguments);
            });
    }

    public function editPartnerAction(): Action
    {
        return Action::make('editPartner')->label('Tambah Partner')->modalHeading('Mitra / Brand')
            ->modalSubmitActionLabel('Terapkan')->modalDescription('Terapkan ke daftar, lalu klik Simpan Mitra untuk menerbitkan.')
            ->fillForm(fn (array $arguments): array => $this->partnersData['items'][$arguments['index'] ?? -1] ?? ['visible' => true])
            ->schema([
                TextInput::make('name')->label('Nama')->required()->maxLength(120),
                TextInput::make('url')->label('Tautan')->url()->rules(['starts_with:http://,https://'])->required()->maxLength(255),
                FileUpload::make('logo_path')->label('Logo')->disk('public')->visibility('public')->directory('landing/partners')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->openable(),
                Toggle::make('visible')->label('Tampilkan')->default(true),
            ])->action(function (array $data, array $arguments): void {
                $this->applyItem('partners', $data, $arguments);
            });
    }

    protected function applyItem(string $collection, array $data, array $arguments): void
    {
        $items = $collection === 'features' ? ($this->whyData['features'] ?? []) : ($this->partnersData['items'] ?? []);
        if (isset($arguments['index'])) {
            abort_unless(array_key_exists($arguments['index'], $items), 404);
            $items[$arguments['index']] = $data;
        } else {
            $items[] = $data;
        }
        $this->setItems($collection, $items);
    }

    protected function setItems(string $collection, array $items): void
    {
        abort_unless(in_array($collection, ['features', 'partners'], true), 404);
        if ($collection === 'features') {
            $this->whyData['features'] = array_values($items);
        } else {
            $this->partnersData['items'] = array_values($items);
        }
    }

    public function moveItem(string $collection, int $index, int $direction): void
    {
        abort_unless(in_array($direction, [-1, 1], true), 422);
        $items = $collection === 'features' ? ($this->whyData['features'] ?? []) : ($this->partnersData['items'] ?? []);
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
                $items = $collection === 'features' ? ($this->whyData['features'] ?? []) : ($this->partnersData['items'] ?? []);
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
            'features.*' => ['array:title,caption,icon,visible'],
            'features.*.title' => ['required', 'string', 'max:120'],
            'features.*.caption' => ['required', 'string', 'max:300'],
            'features.*.icon' => ['required', 'in:award,shield,network'],
            'features.*.visible' => ['required', 'boolean'],
        ])->validate();
        $this->saveSection('why_us', [...$data, 'features' => array_values($features['features'])]);
    }

    public function saveStatistics(): void
    {
        $this->saveSection('statistics', $this->statisticsForm->getState()['items'] ?? []);
    }

    public function saveReviews(): void
    {
        $this->saveSection('reviews', $this->reviewsForm->getState());
    }

    public function savePartners(): void
    {
        $data = Validator::make($this->partnersData, [
            'items' => ['present', 'array', 'max:100'],
            'items.*' => ['array:name,url,logo_path,visible'],
            'items.*.name' => ['required', 'string', 'max:120'],
            'items.*.url' => ['required', 'url:http,https', 'max:255'],
            'items.*.logo_path' => ['nullable', 'string', 'regex:~^landing/partners/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~'],
            'items.*.visible' => ['required', 'boolean'],
        ])->validate();
        $this->saveSection('partners', array_values($data['items']));
    }

    protected function saveSection(string $section, array $data): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $page = $this->landingPage();
        $content = $page->landingContent();
        $content[$section] = $data;
        SitePage::withoutEvents(static fn () => $page->update(['content' => $content]));

        $label = [
            'hero' => 'Hero',
            'why_us' => 'Mengapa Kami',
            'statistics' => 'Statistik',
            'reviews' => 'Review',
            'partners' => 'Mitra',
        ][$section];

        activity()->performedOn($page)->causedBy(auth()->user())->withProperties(['section' => $section])->log('Mengubah '.$label);

        Notification::make()->success()->title('Perubahan tersimpan dan langsung tampil di situs.')->send();
    }

    protected function landingPage(): SitePage
    {
        return SitePage::query()->firstOrCreate(['kind' => 'landing'], [
            'title' => 'Landing Page',
            'content' => SitePage::defaultContent(),
        ]);
    }

    public function historyUrl(): string
    {
        return SitePageResource::getUrl('activities', ['record' => $this->landingPage()]);
    }

    public function videoUrl(): ?string
    {
        $path = $this->landingPage()->landingContent()['hero']['video_path'];

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function posterUrl(): ?string
    {
        $path = $this->landingPage()->landingContent()['hero']['poster_path'];

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}

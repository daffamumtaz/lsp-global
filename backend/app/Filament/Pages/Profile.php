<?php

namespace App\Filament\Pages;

use App\Models\SitePage;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

class Profile extends Page
{
    protected string $view = 'filament.pages.profile';

    protected static ?string $title = 'Edit Halaman > Tentang Kami';

    protected static ?string $navigationLabel = 'Profil LSP';

    protected static string|\UnitEnum|null $navigationGroup = 'Edit Halaman';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 2;

    public ?array $profileData = [];

    public ?array $visionData = [];

    #[Locked]
    public array $certificatesData = [];

    public function mount(): void
    {
        $content = $this->profilePage()->profileContent();
        $this->profileForm->fill($content['profile']);
        $this->visionForm->fill($content['vision']);
        $this->certificatesData = $content['certificates'];
    }

    public function profileForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hook')->label('Judul Profil')->required()->maxLength(200),
            TextInput::make('slug')->label('Alamat halaman')->default('/tentang-kami')->disabled()->dehydrated(false),
            $this->richText('description', 'Deskripsi'),
            $this->imageUpload('photo_path', 'Foto Utama', 'profile/photos'),
            $this->imageUpload('photo_two_path', 'Foto Kedua', 'profile/photos'),
            $this->imageUpload('photo_three_path', 'Foto Ketiga', 'profile/photos'),
        ])->statePath('profileData');
    }

    public function visionForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hook')->label('Judul Bagian')->required()->maxLength(120),
            TextInput::make('slug')->label('Alamat bagian')->default('/tentang-kami#visi-misi')->disabled()->dehydrated(false),
            $this->richText('vision', 'Visi'),
            $this->richText('mission', 'Misi'),
        ])->statePath('visionData');
    }

    protected function richText(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)->label($label)->required()->maxLength(20000)
            ->toolbarButtons(['bold', 'italic', 'strike', 'link', 'bulletList', 'orderedList', 'undo', 'redo']);
    }

    protected function imageUpload(string $name, string $label, string $directory): FileUpload
    {
        return FileUpload::make($name)->label($label)->disk('public')->visibility('public')->directory($directory)
            ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->openable()
            ->helperText('JPEG, PNG, atau WebP, maksimal 5 MB.');
    }

    public function editCertificateAction(): Action
    {
        return Action::make('editCertificate')->label('Tambah Sertifikat')->modalHeading('Sertifikat & Legalitas')
            ->modalSubmitActionLabel('Terapkan')->modalDescription('Terapkan ke daftar, lalu Simpan Sertifikat untuk menerbitkan.')
            ->fillForm(fn (array $arguments): array => $this->certificatesData[$arguments['index'] ?? -1] ?? ['visible' => true])
            ->schema(fn (array $arguments): array => [
                TextInput::make('title')->label('Judul')->required()->maxLength(160),
                Textarea::make('description')->label('Deskripsi')->required()->rows(5)->maxLength(2000),
                $this->imageUpload('image_path', 'Gambar Sertifikat', 'profile/certificates')
                    ->required(empty($this->certificatesData[$arguments['index'] ?? -1]['fallback_image'])),
                Toggle::make('visible')->label('Tampilkan')->default(true),
            ])->action(function (array $data, array $arguments): void {
                if (isset($arguments['index'])) {
                    $index = (int) $arguments['index'];
                    abort_unless(isset($this->certificatesData[$index]), 404);
                    $this->certificatesData[$index] = [...$this->certificatesData[$index], ...$data];
                } else {
                    $this->certificatesData[] = [...$data, 'id' => max(array_column($this->certificatesData, 'id') ?: [0]) + 1, 'fallback_image' => null];
                }
            });
    }

    public function deleteCertificateAction(): Action
    {
        return Action::make('deleteCertificate')->label('Hapus')->color('danger')->requiresConfirmation()
            ->modalHeading('Hapus sertifikat dari daftar?')->modalDescription('Perubahan diterbitkan saat Simpan Sertifikat.')
            ->action(function (array $arguments): void {
                unset($this->certificatesData[(int) $arguments['index']]);
                $this->certificatesData = array_values($this->certificatesData);
            });
    }

    public function moveCertificate(int $index, int $direction): void
    {
        abort_unless(in_array($direction, [-1, 1], true), 422);
        $target = $index + $direction;
        if (isset($this->certificatesData[$index], $this->certificatesData[$target])) {
            [$this->certificatesData[$index], $this->certificatesData[$target]] = [$this->certificatesData[$target], $this->certificatesData[$index]];
        }
    }

    public function saveProfile(): void
    {
        $this->saveSection('profile', $this->profileForm->getState(), 'Tentang Kami');
    }

    public function saveVision(): void
    {
        $this->saveSection('vision', $this->visionForm->getState(), 'Visi dan Misi');
    }

    public function saveCertificates(): void
    {
        $this->saveSection('certificates', $this->certificatesData, 'Sertifikat');
    }

    protected function saveSection(string $section, array $data, string $label): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $page = $this->profilePage();
        DB::transaction(function () use ($page, $section, $data, $label): void {
            $page = SitePage::query()->lockForUpdate()->findOrFail($page->id);
            $content = $page->profileContent();
            $content[$section] = $data;
            SitePage::withoutEvents(static fn () => $page->update(['content' => $content]));
            activity()->performedOn($page)->causedBy(auth()->user())->withProperties(['section' => $section])->log('Mengubah '.$label);
        });
        Notification::make()->success()->title('Perubahan tersimpan dan langsung tampil di situs.')->send();
    }

    protected function profilePage(): SitePage
    {
        return SitePage::query()->firstOrCreate(['kind' => 'profile'], ['title' => 'Tentang Kami', 'content' => SitePage::defaultProfileContent()]);
    }
}

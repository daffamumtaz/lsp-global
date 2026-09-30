<?php

namespace App\Filament\Pages;

use App\Models\SitePage;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
    public array $organizationData = [];

    #[Locked]
    public array $certificatesData = [];

    public function mount(): void
    {
        $content = $this->profilePage()->profileContent();
        $this->profileForm->fill($content['profile']);
        $this->visionForm->fill($content['vision']);
        $this->organizationData = $content['organization'];
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

    public function editOrganizationAction(): Action
    {
        return Action::make('editOrganization')->label('Tambah Jabatan')->modalHeading('Jabatan Organisasi')
            ->modalSubmitActionLabel('Terapkan')->modalDescription('Terapkan ke daftar, lalu Simpan Struktur untuk menerbitkan. Menyembunyikan atasan juga menyembunyikan cabangnya.')
            ->fillForm(fn (array $arguments): array => $this->findOrganization($arguments['id'] ?? null) ?? ['parent_id' => null, 'order' => 1, 'visible' => true])
            ->schema(fn (array $arguments): array => [
                TextInput::make('title')->label('Jabatan')->required()->maxLength(120),
                TextInput::make('name')->label('Nama')->required()->maxLength(160),
                Select::make('parent_id')->label('Atasan')->placeholder('Tanpa atasan (puncak organisasi)')
                    ->options($this->parentOptions($arguments['id'] ?? null))->searchable(),
                TextInput::make('order')->label('Urutan di bawah atasan yang sama')->numeric()->integer()->minValue(1)->maxValue(100)->required(),
                Toggle::make('visible')->label('Tampilkan')->default(true),
            ])->action(function (array $data, array $arguments): void {
                $nodes = $this->organizationData;
                $id = isset($arguments['id']) ? (int) $arguments['id'] : (max(array_column($nodes, 'id') ?: [0]) + 1);
                $node = [...$data, 'id' => $id, 'parent_id' => filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null, 'order' => (int) $data['order']];
                if (isset($arguments['id'])) {
                    $index = array_search($id, array_column($nodes, 'id'), true);
                    abort_if($index === false, 404);
                    $nodes[$index] = $node;
                } else {
                    $nodes[] = $node;
                }
                $this->validateOrganization($nodes);
                $this->organizationData = $nodes;
            });
    }

    public function deleteOrganizationAction(): Action
    {
        return Action::make('deleteOrganization')->label('Hapus')->color('danger')->requiresConfirmation()
            ->modalHeading('Hapus jabatan?')->modalDescription('Jabatan yang masih memiliki bawahan harus dipindahkan atau dikosongkan terlebih dahulu. Perubahan diterbitkan saat Simpan Struktur.')
            ->action(function (array $arguments, Action $action): void {
                $id = (int) $arguments['id'];
                if (in_array($id, array_column($this->organizationData, 'parent_id'), true)) {
                    Notification::make()->warning()->title('Pindahkan bawahan terlebih dahulu.')->send();
                    $action->halt();
                }
                $this->organizationData = array_values(array_filter($this->organizationData, fn (array $node): bool => $node['id'] !== $id));
            });
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

    public function saveOrganization(): void
    {
        $this->validateOrganization($this->organizationData);
        $this->saveSection('organization', $this->organizationData, 'Struktur Organisasi');
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

    public function organizationRows(): array
    {
        return collect($this->organizationData)->sortBy([['parent_id', 'asc'], ['order', 'asc'], ['id', 'asc']])->values()->all();
    }

    public function organizationLabel(?int $id): string
    {
        return $this->findOrganization($id)['title'] ?? '—';
    }

    protected function findOrganization(?int $id): ?array
    {
        foreach ($this->organizationData as $node) {
            if ($node['id'] === $id) {
                return $node;
            }
        }

        return null;
    }

    protected function parentOptions(?int $id): array
    {
        $excluded = [$id];
        do {
            $count = count($excluded);
            foreach ($this->organizationData as $node) {
                if ($id !== null && in_array($node['parent_id'], $excluded, true) && ! in_array($node['id'], $excluded, true)) {
                    $excluded[] = $node['id'];
                }
            }
        } while ($count !== count($excluded));

        return collect($this->organizationData)->reject(fn (array $node): bool => in_array($node['id'], $excluded, true))
            ->mapWithKeys(fn (array $node): array => [$node['id'] => $node['title'].' — '.$node['name']])->all();
    }

    protected function validateOrganization(array $nodes): void
    {
        Validator::make(['organization' => $nodes], [
            'organization' => ['present', 'array', 'max:100'],
            'organization.*' => ['array:id,parent_id,order,title,name,visible'],
            'organization.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'organization.*.parent_id' => ['nullable', 'integer', 'min:1'],
            'organization.*.order' => ['required', 'integer', 'min:1', 'max:100'],
            'organization.*.title' => ['required', 'string', 'max:120'],
            'organization.*.name' => ['required', 'string', 'max:160'],
            'organization.*.visible' => ['required', 'boolean'],
        ])->validate();
        $parents = array_column($nodes, 'parent_id', 'id');
        foreach ($nodes as $node) {
            $seen = [$node['id']];
            $parent = $node['parent_id'];
            while ($parent !== null) {
                if (! array_key_exists($parent, $parents) || in_array($parent, $seen, true)) {
                    throw ValidationException::withMessages(['organization' => 'Atasan tidak valid atau hubungan organisasi membentuk lingkaran.']);
                }
                $seen[] = $parent;
                $parent = $parents[$parent];
            }
        }
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\SitePage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class Identity extends Page
{
    protected string $view = 'filament.pages.identity';

    protected static ?string $title = 'Identitas';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SitePage::identityContent());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Branding')->schema([
                TextInput::make('brand_name')->label('Nama Brand')->required()->maxLength(255),
                FileUpload::make('logo_path')->label('Logo')->disk('public')->visibility('public')->directory('identity')->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])->maxSize(5120)->openable()->helperText('1 file PNG, JPEG, atau WebP, maksimal 5 MB. Disarankan 800 × 400 px, rasio 2:1; rasio logo asli tetap dipertahankan.'),
                FileUpload::make('icon_path')->label('Icon')->disk('public')->visibility('public')->directory('identity')->image()->acceptedFileTypes(['image/png', 'image/webp'])->maxSize(1024)->openable()->helperText('1 file PNG atau WebP, maksimal 1 MB. Disarankan 512 × 512 px, rasio 1:1.'),
                FileUpload::make('favicon_path')->label('Favicon Publik')->disk('public')->visibility('public')->directory('identity')->image()->acceptedFileTypes(['image/png', 'image/webp'])->maxSize(1024)->openable()->helperText('1 file PNG atau WebP, maksimal 1 MB. Disarankan 64 × 64 px, rasio 1:1.'),
                FileUpload::make('admin_favicon_path')->label('Favicon Admin')->disk('public')->visibility('public')->directory('identity')->image()->acceptedFileTypes(['image/png', 'image/webp'])->maxSize(1024)->openable()->helperText('1 file PNG atau WebP, maksimal 1 MB. Disarankan 64 × 64 px, rasio 1:1; hanya tampil pada domain admin.'),
            ]),
            Section::make('Kontak & Lokasi')->schema([
                TextInput::make('phone')->label('Nomor Telepon')->tel()->required()->regex('/^(?=(?:\D*\d){8,15}\D*$)\+?[0-9 ()-]+$/')->maxLength(24),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(254),
                TextInput::make('whatsapp')->label('Nomor WhatsApp')->tel()->required()->regex('/^(?=(?:\D*\d){8,15}\D*$)(?:\+?62|0)[0-9 ()-]+$/')->maxLength(24)->helperText('Gunakan format +62 atau 08. Link WhatsApp dibuat otomatis.'),
                Textarea::make('address')->label('Alamat / Lokasi')->required()->maxLength(2000),
                TextInput::make('maps_url')->label('Link Lokasi Google Maps')->url()->rules(['nullable', 'url:https'])->maxLength(2048)->helperText('Kosongkan untuk membuat link berdasarkan alamat.'),
                TextInput::make('working_hours')->label('Jam Layanan')->maxLength(255),
                Textarea::make('whatsapp_message')->label('Template WhatsApp — kontak umum')->required()->maxLength(2000)->helperText('Untuk footer, kontak mengambang, dan halaman Kontak. Variabel: {brand_name}.'),
                Textarea::make('whatsapp_registration_message')->label('Template WhatsApp — pendaftaran skema')->required()->maxLength(2000)->helperText('Variabel: {brand_name}, {scheme_name}.'),
                Textarea::make('whatsapp_contact_message')->label('Template WhatsApp — formulir kontak')->required()->maxLength(2000)->helperText('Variabel: {brand_name}, {subject}, {name}, {institution}, {email}, {phone}, {preference}, {message}.'),
            ]),
            Section::make('Legalitas')->schema([
                TextInput::make('license_number')->label('Nomor Lisensi')->required()->maxLength(255),
            ]),
            Section::make('Footer')->schema([
                TextInput::make('footer_heading')->label('Judul Footer')->required()->maxLength(255),
                Textarea::make('footer_caption')->label('Caption Footer')->required()->maxLength(2000),
                TextInput::make('copyright_text')->label('Teks Hak Cipta')->required()->maxLength(255),
                Textarea::make('footer_description')->label('Deskripsi Brand')->required()->maxLength(2000),
            ]),
            Section::make('Media Sosial')->schema([
                TextInput::make('social_links.Facebook')->label('URL Facebook')->url()->rules(['nullable', 'url:https'])->maxLength(2048),
                TextInput::make('social_links.Instagram')->label('URL Instagram')->url()->rules(['nullable', 'url:https'])->maxLength(2048),
                TextInput::make('social_links.YouTube')->label('URL YouTube')->url()->rules(['nullable', 'url:https'])->maxLength(2048),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        abort_unless(Gate::allows('manage-admin'), 403);
        SitePage::query()->updateOrCreate(['kind' => 'identity'], ['title' => 'Identitas', 'content' => $this->form->getState()]);
        Notification::make()->success()->title('Identitas tersimpan dan langsung tampil di situs.')->send();
    }
}

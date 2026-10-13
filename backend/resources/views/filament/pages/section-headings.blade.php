<x-filament-panels::page>
    <p>Judul dan subjudul Beranda lainnya tetap dapat diedit di Landing Page; Profil, Visi & Misi di Profil LSP; Informasi di halaman Informasi.</p>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">Simpan Judul & Subjudul</x-filament::button>
    </form>
</x-filament-panels::page>

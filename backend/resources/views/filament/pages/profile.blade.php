<x-filament-panels::page>
    <div class="profile-editor landing-editor">
        <nav class="landing-editor-nav" aria-label="Bagian Tentang Kami">
            <strong>Bagian halaman</strong>
            <a href="#profile">Tentang Kami</a>
            <a href="#vision">Visi dan Misi</a>
            <a href="#organization">Struktur Organisasi</a>
            <a href="#certificates">Sertifikat</a>
            <span>Perubahan diterbitkan setiap kali bagian disimpan.</span>
        </nav>
        <div class="landing-editor-sections">
            @if ($errors->any())
                <div class="content-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <section id="profile" class="landing-editor-card">
                <h2>Tentang Kami</h2>
                <form wire:submit="saveProfile">{{ $this->profileForm }}<x-filament::button type="submit">Simpan Tentang Kami</x-filament::button></form>
            </section>
            <section id="vision" class="landing-editor-card">
                <h2>Visi dan Misi</h2>
                <form wire:submit="saveVision">{{ $this->visionForm }}<x-filament::button type="submit">Simpan Visi dan Misi</x-filament::button></form>
            </section>
            <section id="organization" class="landing-editor-card">
                <h2>Struktur Organisasi</h2>
                <p class="content-help">Kelola jabatan, atasan, nama pejabat, gelar, dan urutan melalui halaman Struktur Organisasi.</p>
                <a href="{{ \App\Filament\Resources\OrganizationPositions\OrganizationPositionResource::getUrl() }}">Kelola Struktur Organisasi →</a>
            </section>
            <section id="certificates" class="landing-editor-card">
                <div class="content-items-toolbar"><h2>Sertifikat</h2>{{ $this->editCertificateAction }}</div>
                <div class="dashboard-table-scroll">
                    <table class="dashboard-table content-table">
                        <caption class="sr-only">Daftar sertifikat dan legalitas</caption>
                        <thead><tr><th scope="col">Urutan</th><th scope="col">Judul / Deskripsi</th><th scope="col">Dokumen</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                        <tbody>
                            @forelse ($certificatesData as $index => $certificate)
                                <tr wire:key="certificate-{{ $certificate['id'] }}">
                                    <td><div class="content-row-actions"><span>{{ $index + 1 }}</span><button type="button" aria-label="Naikkan sertifikat {{ $index + 1 }}" wire:click="moveCertificate({{ $index }}, -1)" @disabled($loop->first)>↑</button><button type="button" aria-label="Turunkan sertifikat {{ $index + 1 }}" wire:click="moveCertificate({{ $index }}, 1)" @disabled($loop->last)>↓</button></div></td>
                                    <td><strong>{{ $certificate['title'] }}</strong><span class="content-item-description">{{ $certificate['description'] }}</span></td>
                                    <td>@if (!empty($certificate['image_path']))<a href="{{ Storage::disk('public')->url($certificate['image_path']) }}" target="_blank" rel="noopener noreferrer">Lihat gambar ↗</a>@else<span class="content-help">Dokumen bawaan</span>@endif</td>
                                    <td><span class="dashboard-status {{ $certificate['visible'] ? 'is-success' : 'is-warning' }}">{{ $certificate['visible'] ? 'Tampil' : 'Disembunyikan' }}</span></td>
                                    <td><div class="content-row-actions"><x-filament::button type="button" size="sm" color="gray" wire:click="mountAction('editCertificate', { index: {{ $index }} })">Edit</x-filament::button><x-filament::button type="button" size="sm" color="danger" outlined wire:click="mountAction('deleteCertificate', { index: {{ $index }} })">Hapus</x-filament::button></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="dashboard-empty">Belum ada sertifikat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="content-section-footer"><span>{{ count($certificatesData) }} sertifikat</span><x-filament::button type="button" wire:click="saveCertificates">Simpan Sertifikat</x-filament::button></div>
            </section>
        </div>
    </div>
</x-filament-panels::page>

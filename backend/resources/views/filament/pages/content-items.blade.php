<div class="content-items">
    <div class="content-items-toolbar">
        <p>{{ count($items) }} item · Perubahan diterbitkan setelah bagian disimpan.</p>
    </div>
    <div class="dashboard-table-scroll">
        <table class="dashboard-table content-table">
            <caption class="sr-only">{{ $collection === 'features' ? 'Poin Mengapa Kami' : 'Daftar Mitra' }}</caption>
            <thead><tr><th scope="col">Urutan</th><th scope="col">{{ $collection === 'features' ? 'Ikon' : 'Logo' }}</th><th scope="col">{{ $collection === 'features' ? 'Judul / Caption' : 'Nama / Tautan' }}</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                @forelse ($items as $index => $item)
                    <tr wire:key="{{ $collection }}-{{ $index }}">
                        <td><div class="content-row-actions"><span>{{ $index + 1 }}</span><button type="button" aria-label="Naikkan item {{ $index + 1 }}" wire:click="moveItem('{{ $collection }}', {{ $index }}, -1)" @disabled($loop->first)>↑</button><button type="button" aria-label="Turunkan item {{ $index + 1 }}" wire:click="moveItem('{{ $collection }}', {{ $index }}, 1)" @disabled($loop->last)>↓</button></div></td>
                        <td>
                            @if ($collection === 'features')
                                <x-filament::icon :icon="match ($item['icon'] ?? 'award') { 'shield' => 'heroicon-o-shield-check', 'network' => 'heroicon-o-globe-alt', default => 'heroicon-o-trophy' }" class="content-item-icon" />
                            @elseif (!empty($item['logo_path']))
                                <img class="content-item-logo" src="{{ Storage::disk('public')->url($item['logo_path']) }}" alt="Logo {{ $item['name'] }}" />
                            @else
                                <span class="content-item-placeholder">—</span>
                            @endif
                        </td>
                        <td><x-filament::button type="button" color="gray" outlined wire:click="mountAction('{{ $editAction }}', { index: {{ $index }} })">{{ $item['title'] ?? $item['name'] }}</x-filament::button><span class="content-item-description">{{ $item['caption'] ?? $item['url'] }}</span></td>
                        <td><div class="content-row-actions"><x-filament::button type="button" size="sm" color="gray" wire:click="mountAction('{{ $editAction }}', { index: {{ $index }} })">Edit</x-filament::button><x-filament::button type="button" size="sm" color="danger" outlined wire:click="mountAction('removeItem', { collection: '{{ $collection }}', index: {{ $index }} })">Hapus</x-filament::button></div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="dashboard-empty">Belum ada item.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@php
    $page = $schemaComponent->getLivewire();
    $visible = $page->getVisibleWidgetGridLayout();
    $layoutKey = md5(json_encode([$page->widgetGridLayout, $page->widgetGridEditing]));
@endphp
<div wire:key="dashboard-grid-{{ $layoutKey }}"
    class="fi-wg admin-dashboard-grid"
    x-load
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-widget-grid-styles', 'johnrivera7/filament-custom-dashboard-widgets'))]"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filamentWidgetGrid', 'johnrivera7/filament-custom-dashboard-widgets') }}"
    x-data="filamentWidgetGrid(@js($page->getWidgetGridAlpineConfig()))"
    x-on:keydown.escape.window="onEscape($event)">
    @if ($page->widgetGridEditing)
        <x-filament::section heading="Widget tersedia">
            <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">Pilih widget yang ditampilkan. Seret dan ubah ukuran di desktop, atau gunakan Atur urutan dan ukuran.</p>
            <div class="flex flex-wrap gap-4">
                @foreach ($page->getWidgetGridCatalog() as $widget)
                    <label class="flex min-h-11 items-center gap-2">
                        <input type="checkbox" class="fi-checkbox-input" @checked($widget['visible'])
                            x-on:change="toggleWidget(@js($widget['class']), $event.target.checked)">
                        {{ $widget['title'] }}
                    </label>
                @endforeach
            </div>
        </x-filament::section>
    @endif
    <div x-ref="grid" class="grid-stack fi-wg-grid" wire:ignore.self>
        @forelse ($visible as $item)
            <div class="grid-stack-item fi-wg-item" wire:ignore.self
                gs-id="{{ $item['widget'] }}" gs-x="{{ $item['x'] }}" gs-y="{{ $item['y'] }}"
                gs-w="{{ $item['w'] }}" gs-h="{{ $item['h'] }}"
                gs-min-w="6" gs-min-h="{{ $item['widget']::$gridMinH }}">
                <div class="grid-stack-item-content fi-wg-item-content admin-widget-content" tabindex="0" role="region" aria-label="{{ app($item['widget'])->getTitle() }}">
                    @if ($page->widgetGridEditing)
                        <button type="button" class="fi-wg-remove" aria-label="Hapus {{ app($item['widget'])->getTitle() }} dari layout"
                            x-on:click.stop="toggleWidget(@js($item['widget']), false)">×</button>
                        <div class="fi-wg-drag-surface" aria-hidden="true"></div>
                    @endif
                    <div class="fi-wg-widget">
                        @livewire($item['widget'], key('dashboard-' . $item['widget'] . '-' . $page->widgetGridUserId()))
                    </div>
                </div>
            </div>
        @empty
            <p>Belum ada widget pada layout ini. Gunakan Atur dashboard untuk menambahkan widget.</p>
        @endforelse
    </div>
</div>

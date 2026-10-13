<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap gap-3">
            @foreach ([\App\Filament\Resources\CertificationSchemes\CertificationSchemeResource::class => 'Tambah skema', \App\Filament\Resources\Tuks\TukResource::class => 'Tambah TUK', \App\Filament\Resources\Assessors\AssessorResource::class => 'Tambah asesor', \App\Filament\Resources\GalleryItems\GalleryItemResource::class => 'Tambah galeri'] as $resource => $label)
                <x-filament::button tag="a" :href="$resource::getUrl(parameters: ['action' => 'create'])">{{ $label }}</x-filament::button>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

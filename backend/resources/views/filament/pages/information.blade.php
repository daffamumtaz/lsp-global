<x-filament-panels::page>
    <x-filament::section heading="Informasi">
        <div class="flex flex-wrap gap-4">
            <x-filament::button tag="a" :href="\App\Filament\Resources\Tuks\TukResource::getUrl()">TUK</x-filament::button>
            <x-filament::button tag="a" :href="\App\Filament\Resources\Assessors\AssessorResource::getUrl()">Asesor</x-filament::button>
            <x-filament::button tag="a" :href="$this->assessmentUrl()">Edit alur asesmen daring</x-filament::button>
        </div>
    </x-filament::section>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">Simpan Informasi</x-filament::button>
    </form>
</x-filament-panels::page>

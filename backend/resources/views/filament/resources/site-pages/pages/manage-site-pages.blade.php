<x-filament-panels::page>
    <div class="landing-editor">
        <nav class="landing-editor-nav" aria-label="Bagian Landing Page">
            <strong>Bagian halaman</strong>
            <a href="#hero">Hero</a>
            <a href="#why-us">Mengapa Kami</a>
            <a href="#statistics">Statistik</a>
            <a href="#reviews">Review</a>
            <a href="#partners">Mitra</a>
            <span>Skema Sertifikasi dikelola pada tahap terpisah.</span>
            <a href="{{ $this->historyUrl() }}">Lihat riwayat perubahan</a>
        </nav>

        <div class="landing-editor-sections">
            <section id="hero" class="landing-editor-card" aria-labelledby="hero-heading">
                <h2 id="hero-heading">Hero</h2>
                <form wire:submit="saveHero">
                    {{ $this->heroForm }}
                    @if ($videoUrl = $this->videoUrl())
                        <div class="landing-editor-video"><span>Video saat ini</span><video controls preload="metadata" @if ($this->posterUrl()) poster="{{ $this->posterUrl() }}" @endif src="{{ $videoUrl }}"></video></div>
                    @endif
                    <x-filament::button type="submit">Simpan Hero</x-filament::button>
                </form>
            </section>

            <section id="why-us" class="landing-editor-card" aria-labelledby="why-heading">
                <h2 id="why-heading">Mengapa Kami dan Fitur</h2>
                <form wire:submit="saveWhy">
                    {{ $this->whyForm }}
                    @include('filament.pages.content-items', ['collection' => 'features', 'items' => $whyData['features'] ?? [], 'editAction' => 'editFeature'])
                    <x-filament::button type="submit">Simpan Mengapa Kami</x-filament::button>
                </form>
            </section>

            <section id="statistics" class="landing-editor-card" aria-labelledby="statistics-heading">
                <h2 id="statistics-heading">Statistik</h2>
                <form wire:submit="saveStatistics">
                    {{ $this->statisticsForm }}
                    <x-filament::button type="submit">Simpan Statistik</x-filament::button>
                </form>
            </section>

            <section id="reviews" class="landing-editor-card" aria-labelledby="reviews-heading">
                <h2 id="reviews-heading">Review</h2>
                <form wire:submit="saveReviews">
                    {{ $this->reviewsForm }}
                    <x-filament::button type="submit">Simpan Review</x-filament::button>
                </form>
            </section>

            <section id="partners" class="landing-editor-card" aria-labelledby="partners-heading">
                <h2 id="partners-heading">Mitra</h2>
                <form wire:submit="savePartners">
                    @include('filament.pages.content-items', ['collection' => 'partners', 'items' => $partnersData['items'] ?? [], 'editAction' => 'editPartner'])
                    <x-filament::button type="submit">Simpan Mitra</x-filament::button>
                </form>
            </section>
        </div>
    </div>
</x-filament-panels::page>

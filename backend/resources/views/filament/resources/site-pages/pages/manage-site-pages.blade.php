<x-filament-panels::page>
    <div class="landing-editor">
        <nav class="landing-editor-nav" aria-label="Bagian Landing Page">
            <strong>Bagian halaman</strong>
            <a href="#hero">Hero</a>
            <a href="#about">Video &amp; Tentang</a>
            <a href="#why-us">Mengapa Kami</a>
            <a href="#statistics">Statistik</a>
            <a href="#highlights">Skema Highlight</a>
            <a href="#reviews">Review</a>
            <a href="#partners">Mitra</a>
        </nav>

        <div class="landing-editor-sections">
            <section id="hero" class="landing-editor-card" aria-labelledby="hero-heading">
                <h2 id="hero-heading">Hero</h2>
                <form wire:submit="saveHero">
                    {{ $this->heroForm }}
                    <x-filament::button type="submit">Simpan Hero</x-filament::button>
                </form>
            </section>

            <section id="about" class="landing-editor-card" aria-labelledby="about-heading">
                <h2 id="about-heading">Video &amp; Tentang</h2>
                <div class="landing-editor-pair">
                    <form wire:submit="saveVideo">
                        {{ $this->videoForm }}
                        <x-filament::button type="submit">Simpan Video</x-filament::button>
                    </form>
                    <form wire:submit="saveAbout">
                        {{ $this->aboutForm }}
                        <x-filament::button type="submit">Simpan Tentang</x-filament::button>
                    </form>
                </div>
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
                <p>Jumlah skema sertifikasi, TUK, dan asesor dihitung otomatis dari data aktif.</p>
            </section>

            <section id="highlights" class="landing-editor-card" aria-labelledby="highlights-heading">
                <h2 id="highlights-heading">Skema Highlight</h2>
                <form wire:submit="saveHighlights">
                    {{ $this->highlightsForm }}
                    <x-filament::button type="submit">Simpan Skema Highlight</x-filament::button>
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
                    {{ $this->partnersForm }}
                    <x-filament::button type="submit">Simpan Mitra</x-filament::button>
                </form>
                <p>Logo dan website mitra berasal dari data TUK yang sama dengan halaman Informasi.</p>
                <x-filament::button tag="a" :href="\App\Filament\Resources\Tuks\TukResource::getUrl()">Kelola Mitra / TUK</x-filament::button>
            </section>
        </div>
    </div>
</x-filament-panels::page>

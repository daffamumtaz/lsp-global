@php($media = $getRecord()->getMedia('gallery')->sortBy('order_column')->first())
<div class="gallery-cover">
    @if ($media)
        @if (str_starts_with($media->mime_type, 'video/'))
            <video muted preload="metadata" src="{{ $media->getUrl() }}#t=0.1" aria-label="Video pertama {{ $getRecord()->title }}"></video>
        @else
            <img src="{{ $media->getUrl() }}" alt="Foto pertama {{ $getRecord()->title }}" loading="lazy" />
        @endif
    @else
        <span>Belum ada media</span>
    @endif
</div>

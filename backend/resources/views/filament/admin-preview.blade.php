<div>
<div
    wire:ignore
    x-data="{
        latest: @js($payload),
        componentId: @js($componentId),
        origin: @js($origin),
        url: @js($url),
        revision: -1,
        ready: false,
        failed: false,
        timer: null,
        wait() {
            this.ready = false;
            this.failed = false;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { if (!this.ready) this.failed = true; }, 15000);
        },
        send() {
            if (!this.ready) return;
            this.revision = Math.max(this.revision + 1, Number.isSafeInteger(this.latest.revision) ? this.latest.revision : 0);
            this.$refs.frame.contentWindow.postMessage(JSON.parse(JSON.stringify({ ...this.latest, revision: this.revision })), this.origin);
        },
        receive(event) {
            if (event.origin !== this.origin || event.source !== this.$refs.frame.contentWindow ||
                event.data?.type !== 'admin-preview:ready' || event.data?.kind !== this.latest.kind) return;
            clearTimeout(this.timer);
            this.ready = true;
            this.failed = false;
            this.send();
        },
        update(event) {
            if (event.detail?.componentId !== this.componentId || event.detail?.payload?.kind !== this.latest.kind) return;
            this.latest = event.detail.payload;
            this.send();
        },
        reload() {
            this.wait();
            this.$refs.frame.src = this.url;
        },
        destroy() { clearTimeout(this.timer); }
    }"
    x-init="wait()"
    x-on:message.window="receive($event)"
    x-on:admin-preview.window="update($event)"
>
    <div class="mb-3 flex flex-wrap items-center gap-3">
        <x-filament::button type="button" wire:click="updatePreview" wire:loading.attr="disabled" wire:target="updatePreview">Perbarui preview</x-filament::button>
        <x-filament::button type="button" color="gray" x-on:click="reload()">Muat ulang preview</x-filament::button>
        <span wire:loading wire:target="updatePreview" role="status">Memperbarui preview…</span>
    </div>
    <p x-show="!ready && !failed" role="status">Memuat preview…</p>
    <p x-show="failed" x-cloak role="alert">Preview tidak dapat dimuat. Periksa koneksi lalu muat ulang preview.</p>
    <div wire:ignore>
        <iframe
            x-ref="frame"
            src="{{ $url }}"
            title="Preview admin {{ $payload['kind'] }}"
            x-on:error="failed = true; ready = false"
            style="width: 100%; height: clamp(760px, 80vw, 1100px); border: 0; background: white;"
        ></iframe>
    </div>
</div>
    @if ($this->previewError)
        <p role="alert" class="mb-3 text-danger-600">{{ $this->previewError }}</p>
    @endif
</div>

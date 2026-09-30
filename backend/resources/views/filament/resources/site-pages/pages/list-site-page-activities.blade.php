<x-filament-panels::page>
    <div class="landing-editor-card">
        <h2>50 perubahan terbaru</h2>
        @forelse ($activities as $activity)
            <div style="padding: 12px 0; border-top: 1px solid #e5e5e5;">
                <strong>{{ $activity->causer?->name ?? 'Sistem' }}</strong>
                <span>{{ $activity->description }}</span>
                <time datetime="{{ $activity->created_at->toIso8601String() }}" style="display: block; color: #737373; font-size: 13px;">{{ $activity->created_at->format('d M Y H:i') }}</time>
            </div>
        @empty
            <p>Belum ada perubahan.</p>
        @endforelse
    </div>
</x-filament-panels::page>

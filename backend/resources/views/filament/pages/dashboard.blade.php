<x-filament-panels::page>
    <div class="admin-dashboard">
        <section class="dashboard-welcome" aria-label="Profil admin">
            <div class="dashboard-welcome-user">
                <span class="dashboard-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()?->name ?? 'Admin', 0, 2)) }}</span>
                <span><strong>{{ auth()->user()?->name ?? 'Admin' }}</strong><small>Administrator</small></span>
            </div>
            <form method="post" action="{{ filament()->getLogoutUrl() }}">
                @csrf
                <button class="dashboard-signout" type="submit">Sign out</button>
            </form>
        </section>

        <section class="dashboard-statistics" aria-label="Ringkasan statistik">
            @foreach ($statistics as $statistic)
                <div class="dashboard-stat">
                    <span>{{ $statistic['label'] }}</span>
                    <strong>{{ $statistic['value'] }}</strong>
                </div>
            @endforeach
        </section>

        <section class="dashboard-card dashboard-quick-actions" aria-labelledby="quick-actions-heading">
            <h2 id="quick-actions-heading">Aksi Cepat</h2>
            <div class="dashboard-action-list">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['url'] }}" class="dashboard-primary-button">{{ $action['label'] }}</a>
                @endforeach
            </div>
        </section>

        <section class="dashboard-card dashboard-table-card" aria-labelledby="messages-heading">
            <div class="dashboard-card-heading">
                <h2 id="messages-heading">Pesan Masuk</h2>
                <a href="{{ \App\Filament\Resources\ContactMessages\ContactMessageResource::getUrl() }}" class="dashboard-primary-button">Lihat Selengkapnya</a>
            </div>
            <div class="dashboard-table-scroll">
                <table class="dashboard-table">
                    <thead><tr><th>Tanggal</th><th>Waktu</th><th>Pengirim</th><th>Subjek</th><th>Jenis</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($messages as $message)
                            <tr>
                                <td>{{ $message['date'] }}</td><td>{{ $message['time'] }}</td>
                                <td>{{ $message['sender'] }}</td><td>{{ $message['subject'] }}</td>
                                <td><span class="dashboard-status {{ $message['status'] === 'Dibaca' ? 'is-success' : 'is-warning' }}">{{ $message['status'] }}</span></td>
                                <td></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="dashboard-empty">Belum ada pesan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-card dashboard-table-card" aria-labelledby="changes-heading">
            <div class="dashboard-card-heading">
                <h2 id="changes-heading">Perubahan Terbaru</h2>
                <a href="{{ \App\Filament\Pages\History::getUrl() }}" class="dashboard-primary-button">Lihat Selengkapnya</a>
            </div>
            <div class="dashboard-table-scroll">
                <table class="dashboard-table">
                    <thead><tr><th>Tanggal</th><th>Waktu</th><th>Jenis</th><th>Objek</th><th>Jenis</th><th>Admin</th></tr></thead>
                    <tbody>
                        @forelse ($changes as $change)
                            <tr>
                                <td>{{ $change['date'] }}</td><td>{{ $change['time'] }}</td>
                                <td>{{ $change['type'] }}</td><td>{{ $change['object'] }}</td>
                                <td><span class="dashboard-status {{ $change['action'] === 'Tambah' ? 'is-success' : ($change['action'] === 'Hapus' ? 'is-danger' : 'is-warning') }}">{{ $change['action'] }}</span></td>
                                <td>{{ $change['admin'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="dashboard-empty">Belum ada perubahan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>

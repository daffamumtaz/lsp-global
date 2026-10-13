# Backend LSP TIK Global

Laravel 13 menyediakan API konten publik; Filament 5 menyediakan panel admin. Database default adalah SQLite. PHP 8.5 dan Composer digunakan untuk verifikasi proyek ini.

Untuk checkout baru:

```bash
composer install
cp .env.example .env
php artisan key:generate
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate
php artisan storage:link
npm ci
npm run build
```

Penyalinan `.env` dan pembuatan key hanya untuk setup awal. Pada lingkungan yang sudah berjalan, pertahankan `.env`, key, database, dan upload yang ada.

Jalankan backend:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Saat mengubah tema admin, jalankan `npm run dev` di terminal terpisah. `composer run dev` juga tersedia untuk menjalankan layanan pengembangan melalui Artisan.

Buat akun admin secara interaktif, lalu berikan akses kepada akun tersebut:

```bash
php artisan make:filament-user
php artisan admin:grant admin@example.com
```

Gunakan email akun yang benar-benar dibuat. Akun baru memiliki `is_admin=false`; pembuatan akun saja belum memberi akses panel.

Pemeriksaan:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

API didefinisikan di [routes/api.php](routes/api.php). Panel admin dikonfigurasi di [AdminPanelProvider](app/Providers/Filament/AdminPanelProvider.php). `DatabaseSeeder` kosong; beberapa migration sudah menambahkan konten awal, sedangkan alur asesmen dapat dibuat di admin atau melalui `AssessmentProcessSeeder`.

Lihat [guidebook](../frontend/docs/guidebook-codebase.md) untuk penjelasan seeder, struktur database, kontrak API, dan troubleshooting; lihat [audit sistem dan UX](../frontend/docs/audit-sistem-ux.md) untuk pekerjaan lanjutan.

### Preview admin dan video profil

Preview struktur organisasi, alur sertifikasi, dan TUK menggunakan iframe frontend `/admin-preview/{kind}`. Atur `FRONTEND_URL` ke origin frontend yang digunakan admin, dan `ADMIN_PREVIEW_ORIGIN` di frontend ke origin panel admin (termasuk port). Preview hanya menerima pesan dari parent dan origin tersebut. Tombol **Perbarui preview** membaca draft tanpa menyimpan; batal atau simpan memuat ulang data tersimpan.

Video profil bawaan berada di `resources/media/video-profile-lfRo5LN6.webm` dan dilayani melalui `/media/profile-video` dengan dukungan Range. Sertakan file ini saat deployment. Unggahan video dari CMS tetap mengutamakan video yang diunggah. Nomor asesor baru memakai `MET.000.` + nomor + tahun Jakarta; nomor lama tetap dapat diedit.

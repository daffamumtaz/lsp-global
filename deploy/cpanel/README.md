---
modified: 2026-11-12
created: 2026-11-05
---
# Deploy ke cPanel

Ikuti [panduan deploy lengkap](DEPLOY.md) untuk instalasi baru, migrasi data, konfigurasi PHP dan Node.js, build tema admin, pemeriksaan, serta pembaruan. [Audit paket cPanel](AUDIT.md) mencatat perubahan dependensi dan hasil verifikasi pada 6 November 2026.

Proyek ini menjalankan dua aplikasi. Laravel menyediakan API dan panel admin; Next.js melayani website publik. Siapkan dua subdomain, misalnya `www.example.com` untuk frontend dan `api.example.com` untuk backend. Pada cPanel, arahkan document root subdomain backend ke folder `backend/public`. Jangan arahkan domain ke folder `backend`.

## Persyaratan hosting

- PHP 8.4.1 atau lebih baru, Composer, MariaDB, dan ekstensi PHP yang diminta Composer/Laravel. PHP 8.5 disarankan.
- Node.js 24 pada menu **Setup Node.js App** atau **Application Manager**. Paket juga mengizinkan Node.js 22; Node.js 20.20.2 hanya pilihan sementara pada hosting yang belum menyediakan versi lebih baru.
- SSH/Terminal cPanel disarankan untuk menjalankan Composer, Artisan, dan npm.
- Ruang tulis untuk `backend/storage` dan `backend/bootstrap/cache`.

Jika paket hosting tidak menyediakan Node.js yang kompatibel, tempatkan frontend pada host Node.js lain dan biarkan Laravel di cPanel.

## 1. Unggah kode

Unggah repositori di luar `public_html`, misalnya:

```text
/home/CPANEL_USER/lsp-global/backend
/home/CPANEL_USER/lsp-global/frontend
```

Atur document root `api.example.com` ke `/home/CPANEL_USER/lsp-global/backend/public`. Domain utama akan diarahkan ke aplikasi Node.js melalui fitur **Setup Node.js App** cPanel.

## 2. Siapkan database dan Laravel

Buat database dan user MariaDB melalui **MySQL Database Wizard**, lalu beri user hak akses ke database. Di Terminal cPanel:

```bash
cd ~/lsp-global/backend
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
```

Edit `.env`: isi `APP_URL=https://api.example.com`, `FRONTEND_URL=https://www.example.com`, detail `DB_*`, `MAIL_*` bila email diaktifkan, dan pertahankan `APP_DEBUG=false`. Buat key satu kali:

```bash
php artisan key:generate
```

Jangan mengganti `APP_KEY` ketika memperbarui aplikasi yang sudah berjalan. Jalankan migrasi dan siapkan file publik:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Pastikan `storage` dan `bootstrap/cache` dapat ditulis oleh akun aplikasi. File video bawaan di `backend/resources/media/` harus ikut diunggah. Buat akun admin dengan `php artisan make:filament-user`, lalu beri hak panel memakai `php artisan admin:grant EMAIL_ADMIN`.

## 3. Siapkan Next.js

Atur variabel lingkungan frontend di **Setup Node.js App**. URL API harus menggunakan alamat HTTPS publik backend:

```dotenv
LANDING_API_URL=https://api.example.com/api/landing-page
PROFILE_API_URL=https://api.example.com/api/profile-page
ORGANIZATION_API_URL=https://api.example.com/api/organization
CONTENT_API_URL=https://api.example.com/api/content-directory
INFORMATION_API_URL=https://api.example.com/api/information-page
PROCESSES_API_URL=https://api.example.com/api/processes
ADMIN_PREVIEW_ORIGIN=https://api.example.com
LANDING_MEDIA_ORIGIN=https://api.example.com
NEXT_PUBLIC_MAP_TILE_URL=https://tile.openstreetmap.org/{z}/{x}/{y}.png
NEXT_PUBLIC_MAP_ATTRIBUTION='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
```

`NEXT_PUBLIC_*` ditanam saat build, jadi isi sebelum menjalankan `npm run build`. Jangan set `NEXT_PUBLIC_BASE_PATH` untuk domain utama; gunakan hanya jika frontend memang ditempatkan di subpath.

Buat aplikasi Node.js pada cPanel:

- **Node.js version:** 24
- **Application mode:** Production
- **Application root:** `lsp-global/frontend`
- **Application URL:** domain frontend, misalnya `www.example.com`
- **Application startup file:** `app.js`

Setelah membuat aplikasi, gunakan tombol **Run NPM Install** atau Terminal:

```bash
cd ~/lsp-global/frontend
npm install --include=dev --no-audit --no-fund
npm run build
```

Restart aplikasi dari cPanel setelah build atau perubahan environment.

## 4. Pembaruan berikutnya

Unggah perubahan kode tanpa menimpa `.env` backend. Jalankan `composer install --no-dev --optimize-autoloader` setelah dependensi PHP berubah, lalu `php artisan migrate --force` bila ada migrasi baru. Untuk frontend jalankan `npm install --include=dev` pada environment Node Selector dan `npm run build`, kemudian restart aplikasi Node.js dari cPanel. Pada mesin build biasa tanpa symlink Node Selector, gunakan `npm ci --include=dev`.

## Pemeriksaan awal

- Buka `https://api.example.com/up` untuk memeriksa Laravel.
- Buka `https://api.example.com/admin` untuk panel Filament.
- Buka domain frontend dan pastikan halaman yang memuat data API tampil.
- Jika gambar unggahan tidak muncul, periksa `LANDING_MEDIA_ORIGIN`, symlink `public/storage`, dan izin baca `storage/app/public`.
- Jika aplikasi Node.js gagal start, lihat log aplikasi pada **Setup Node.js App** dan pastikan application root memuat `node_modules` serta `.next` hasil build.

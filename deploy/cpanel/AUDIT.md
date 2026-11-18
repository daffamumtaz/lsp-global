# Audit paket cPanel

Dokumen ini mencatat audit salinan deploy pada 6 November 2026. Perubahan cPanel kemudian digabungkan ke repositori `lsp-global` pada 18 November 2026. [Panduan deployment](DEPLOY.md) sudah memakai nama folder dan alur Git repositori utama.

Pada penggabungan, minimum PHP `^8.4.1`, engine Node yang kompatibel, pembaruan lockfile, dan startup `frontend/app.js` diterapkan. Skrip serta dependensi pengujian frontend tetap ada di repositori utama; keduanya tidak perlu dipasang pada server produksi setelah build. Audit 18 November menemukan advisory baru untuk Laravel 13.33.0, sehingga lockfile diperbarui ke Laravel 13.43.0. `composer audit --no-dev` dan `npm audit --omit=dev` kemudian melaporkan 0 advisory produksi. Verifikasi lokal pada Node 24.22.3 dan PHP 8.5.12: 71 tes unit frontend, 33 tes fitur backend (181 assertion), TypeScript, ESLint, build frontend dan tema admin, pemeriksaan platform Composer, serta HTTP 200 dari `app.js` untuk Beranda dan katalog. Verifikasi ini tidak menggantikan uji pada akun cPanel yang sebenarnya.

Tanggal: 6 November 2026. Sumber: kondisi working directory `lsp-global` saat salinan dibuat, termasuk perubahan aplikasi yang belum di-commit. Fokus audit: kompatibilitas runtime, dependensi produksi, jalur aset/data, dan kelengkapan paket deploy. Ini bukan pengujian penetrasi atau audit visual seluruh antarmuka.

## Perbaikan

- Minimum PHP manifest diselaraskan menjadi `^8.4.1` berdasarkan dependensi terkunci. Target hosting dan pemeriksaan lokal memakai PHP 8.5.
- Manifest Node frontend dan backend mengizinkan `^20.20.2 || >=22.12.0`. Semua engine pada lockfile npm memenuhi Node 20.20.2.
- Alat pengembangan backend `concurrently` dan `@laravel/multiplex` yang memerlukan Node 22 dikeluarkan dari paket cPanel. Skrip test frontend dan dependensi test langsung Playwright dikeluarkan dari paket sumber deploy.
- Startup `frontend/app.js` digunakan untuk cPanel dan `npm start`, dengan mode produksi dan pelaporan kegagalan startup. Import dinamis kompatibel dengan Node 20/24 dan aturan ESLint proyek.
- Aset template bawaan Next yang tidak dirujuk (`file.svg`, `globe.svg`, `next.svg`, `vercel.svg`, `window.svg`) dikeluarkan.
- Panduan mencakup build tema Filament, ekstensi PHP, instalasi build dependencies pada mode Production cPanel, symlink storage, pemindahan database/unggahan, dan upgrade Node 24.

## Pembaruan keamanan dependensi

Audit awal menemukan advisory pada Next.js dan enam paket PHP. Paket diperbarui dalam mayor yang sama:

| Paket | Sebelum | Sesudah |
| --- | --- | --- |
| next | 16.3.8 | 16.4.5 |
| eslint-config-next | 16.3.8 | 16.4.5 |
| livewire/livewire | 4.4.6 | 4.4.8 |
| symfony/html-sanitizer | 8.1.7 | 8.1.11 |
| symfony/http-foundation | 8.1.7 | 8.1.11 |
| symfony/http-kernel | 8.1.7 | 8.1.11 |
| symfony/mime | 8.1.7 | 8.1.10 |
| symfony/routing | 8.1.6 | 8.1.11 |

Setelah perubahan, `npm audit --omit=dev` melaporkan 0 vulnerability dan `composer audit --no-dev` melaporkan 0 advisory. Hasil ini mengikuti basis data advisory pada tanggal audit dan tidak menjamin semua kerentanan telah diketahui.

## Pemeriksaan yang selesai

- Instalasi npm mengikuti lockfile dengan npm 10 pada Node 20.20.2.
- Build frontend Next.js, pemeriksaan TypeScript, dan build tema admin Vite lulus pada Node 20.20.2.
- Build frontend dan tema admin juga lulus pada Node 24.22.1 dengan codebase dan lockfile yang sama. Startup `app.js` pada Node 24 merespons HTTP 200.
- ESLint frontend lulus.
- `composer validate` dan `composer check-platform-reqs --no-dev` lulus pada PHP 8.5.12. Peringatan tentang pin versi plugin dashboard 1.0.2 tetap ada; versi ini dipertahankan.
- 33 tes backend dari `AdminAccessTest`, `LandingPageTest`, dan `PublicContentProtectionTest` lulus dengan 181 assertion setelah patch keamanan. Tes dijalankan sementara pada salinan, menggunakan SQLite terisolasi.
- Migrasi seluruh schema dan `deployment:prepare --import-content` lulus pada database uji baru. Impor awal memproses 45 TUK, 39 skema, 82 asesor, dan aset sumber.
- Pemeriksaan HTTP lokal: health, enam endpoint konten utama, login admin, tujuh halaman website, dan contoh media storage merespons 200 setelah patch keamanan.

## Batas dan pekerjaan pada hosting

Node 20 sudah [EOL menurut Node.js](https://nodejs.org/en/about/eol). Kompatibilitas teknis Node 20.20.2 disediakan untuk hosting yang ada; untuk produksi gunakan Node 22 atau 24 LTS terbaru ketika tersedia.

Pemeriksaan database menggunakan SQLite. Koneksi MariaDB, izin document root, proxy Passenger, ekstensi PHP, batas memori, HTTPS, dan kebijakan symlink perlu diperiksa pada cPanel sebenarnya. Paket belum diunggah ke akun hosting.

Paket mempertahankan `backend/resources/data`, `backend/resources/media`, dan `frontend/public` karena dipakai migrasi, runtime, atau impor aset. Hasil build tema admin dan aset publik plugin Filament disertakan. `node_modules`, `vendor`, `.next`, file test, metadata agen, dokumen pengembangan lama, database lokal, `.env` aktif, cache, log, session, dan data uji dikeluarkan dari hasil akhir.

Database dan unggahan CMS terbaru tetap berada di proyek lama. Untuk mempertahankan konten terbaru, pindahkan ekspor database dan berkas storage yang diperlukan secara terpisah. Konten impor awal dari snapshot sumber dapat berbeda dari hasil pengeditan CMS terbaru.

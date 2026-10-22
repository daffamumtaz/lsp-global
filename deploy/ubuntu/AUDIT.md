# Audit kesiapan staging Ubuntu

Tanggal: 22 Oktober 2026. Target: Ubuntu melalui SSH, Nginx + PHP 8.5-FPM + MariaDB + Node.js 24/systemd. Tidak ada perubahan server, database lokal, APP_KEY, atau backup proyek SQLite yang dilakukan dalam cleanup ini.

Status: konfigurasi dan panduan staging sudah disiapkan. Belum dapat dinyatakan sudah terdeploy atau lolos seluruh pemeriksaan staging karena server/domain belum diberikan dan sandbox membatasi socket/proses anak.

## Temuan dan penanganan

| Prioritas | Temuan | Penanganan/status |
| --- | --- | --- |
| P1 | Blueprint Render dan Docker/PostgreSQL berbeda dari target SSH/MariaDB | Dua blueprint, Dockerfile, entrypoint, konfigurasi Apache/PHP Docker dan dokumentasi cloud lama dihapus. Diganti panduan Ubuntu, Nginx, PHP-FPM dan systemd. |
| P1 | Adapter Supabase aktif, upload public bercabang ke S3, CLI Supabase terpasang | Adapter, registrasi driver, sync command, tes khusus integrasi yang dihapus, konfigurasi CLI dan metadata lokal Supabase dihapus. Public storage memakai disk lokal. |
| P1 | SDK S3 dan CLI Supabase memperbesar dependency | Composer membuang 5 paket: flysystem-aws-s3-v3, aws-sdk-php, aws-crt-php, jmespath.php, dan symfony/filesystem yang tidak lagi diperlukan. npm uninstall membuang 8 paket. Manifest dan kedua lockfile diperbarui dengan package manager; vendor lokal diselaraskan. |
| P1 | Contoh environment masih memilih PostgreSQL/Supabase; default database SQLite | Default database dan contoh environment diselaraskan ke MariaDB. Contoh staging terpisah menetapkan debug off, sesi aman/terenkripsi, MFA, dan mail log. Environment aktif lokal tidak diubah. |
| P1 | MFA secara default hanya wajib pada production | Sekarang wajib di luar local/testing, termasuk staging; override eksplisit tetap tersedia. Tes regresi staging gagal sebelum perubahan dan lolos setelah perubahan. |
| P1 | Tidak ada layanan boot/restart untuk frontend/worker | Unit systemd disiapkan; Node hanya bind 127.0.0.1. Nginx membatasi document root backend ke public dan melarang eksekusi PHP selain index.php. |
| P1 | Restore database saja tidak mempertahankan file CMS | Panduan memisahkan transfer kode, dump MariaDB, dan media public. APP_KEY harus dipertahankan agar MFA/data terenkripsi tetap terbaca. Media ~1,3 GB tidak dihapus. |
| P2 | Working directory ~5,7 GB mencampur source, vendor, cache, upload, dan referensi | rsync.exclude mengecualikan dependency/build lokal, secrets, SQLite, upload dan referensi root. Dry-run kode sekitar 62,6 MB; upload dipindahkan terpisah. Arsip sumber foto/dokumen tetap dipertahankan, bukan dianggap sampah. |
| P2 | Ada file root `tatus --short` berisi ekspor diff terminal | Dihapus sebagai artefak yang tidak digunakan runtime. |
| P2 | Requirement utama PHP ^8.3 tidak menggambarkan kebutuhan lockfile | Paket terkunci membutuhkan >=8.4.1. Panduan memakai PHP 8.5, Node.js 24, dan composer check-platform-reqs pada server. |

## Yang sengaja dipertahankan

- Migrasi PostgreSQL/Supabase historis tetap disimpan karena sudah menjadi bagian riwayat migrasi. Keduanya guard terhadap driver PostgreSQL dan tidak aktif pada MariaDB. Menghapus file migrasi yang sudah diterapkan bukan cleanup runtime yang aman. Tes PostgreSQL opsional tetap tersedia untuk riwayat tersebut.
- SQLite tetap tersedia sebagai driver pengujian. Pengujian backend menggunakan database memory, bukan database MariaDB lokal.
- Foto/dokumen referensi root, video pada resources/media, frontend/public, dan upload CMS tidak dihapus. Hanya referensi root yang dikecualikan dari transfer kode.
- Konfigurasi database standar Laravel yang tidak terpakai bukan service aktif dan tidak memasang dependensi server tambahan.

## Hasil verifikasi lokal

- PHPUnit: 216 tes, 211 passed, 5 skipped khusus PostgreSQL; 2.423 assertions.
- Build Vite backend: berhasil.
- Composer validate: valid; ada warning lama tentang pin versi widget 1.0.2 yang dipertahankan.
- Pint pada seluruh PHP berubah: lolos setelah formatting.
- TypeScript `tsc --noEmit`: berhasil.
- Frontend ESLint: berhasil.
- Frontend node tests: 11 dari 13 berkas lolos. Dua berkas membutuhkan HTTP listener; eksekusi langsung menunjukkan `listen EPERM 127.0.0.1` dari sandbox, bukan kegagalan assertion logika yang teramati.
- Next build penuh belum terverifikasi: Turbopack tidak selesai dalam sandbox; percobaan webpack berhenti pada parsing output TypeScript. Reproduksi proses anak menunjukkan `spawnSync /usr/bin/node EPERM` dengan stdout kosong, sedangkan `tsc --showConfig` langsung menghasilkan JSON valid.
- Sintaks shell `prepare-staging.sh` dan `git diff --check`: lolos.
- Dry-run rsync: hanya contoh environment yang ikut, `.env` aktif, SQLite dan storage/upload tidak ikut.
- Nginx tidak terpasang di lingkungan audit; `nginx -t` harus dijalankan pada server. systemd-analyze terhalang pembatasan socket sandbox; unit harus diverifikasi pada Ubuntu.
- Pemindaian advisory dependency terkini belum tersedia: akses Packagist/DNS dari shell terhalang. Jangan menyamakan Composer validate dengan security audit dependency.

## Gerbang sebelum staging dinyatakan siap digunakan

1. Tentukan host SSH, domain frontend/backend, DNS, akses sudo dan spesifikasi server. Jalankan langkah instalasi pada README; tidak ada SSH ke server yang telah dilakukan.
2. Verifikasi PHP CLI/FPM dan Node, lockfile, Nginx/systemd, HTTPS dan firewall. Jangan membuka MariaDB/Node ke internet.
3. Restore ke database staging terpisah, pindahkan media, pertahankan APP_KEY, dan periksa restore lintas versi. Lokal memakai MariaDB 13.0.2; paket Ubuntu mungkin berbeda. Jangan menyalin datadir atau menganggap downgrade fisik didukung.
4. Jalankan `npm test`, `npm run lint`, `npx tsc --noEmit`, dan `npm run build` frontend dari terminal yang mengizinkan proses anak/socket. Build dan dua tes HTTP belum lolos dari sesi ini.
5. Uji health endpoint, isi API yang sebenarnya, login/MFA, CRUD CMS, upload, media/video, peta, dan iframe preview. Frontend fallback dapat menghasilkan HTTP 200 meskipun API tidak tersedia.
6. Uji backup/restore serta rollback kode dan database. Alur saat ini memakai satu direktori dan maintenance window, bukan zero-downtime deployment.

## Batas kompatibilitas MariaDB yang perlu diketahui

Migrasi data ke MariaDB sebelumnya membuktikan kecocokan 26 tabel/1.924 baris. Itu tidak membuktikan kesetaraan seluruh constraint database. `2026_09_30_105755_enforce_content_integrity.php` membuat sejumlah trigger/indeks ekspresi khusus SQLite; migrasi PostgreSQL memiliki implementasi terpisah. MariaDB belum memiliki terjemahan seluruh guard tersebut. Validasi/model aplikasi tetap ada dan suite SQLite lolos, tetapi penulisan SQL langsung atau beberapa kasus konkurensi tidak boleh dianggap memiliki perlindungan identik. Tinjau dan uji terhadap database MariaDB terpisah sebelum promosi produksi; jangan menjalankan PHPUnit pada database berisi data aktif.

Panduan lengkap: [README Ubuntu](README.md). Rujukan konfigurasi: [Laravel deployment](https://laravel.com/framework/docs/deployment), [Next.js self-hosting](https://nextjs.org/docs/app/guides/self-hosting).

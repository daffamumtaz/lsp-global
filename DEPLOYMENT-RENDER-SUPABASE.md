# Audit dan panduan deploy Render + Supabase

Tanggal audit: 16 Oktober 2026. Scope: backend Laravel/Filament, migrasi PostgreSQL, Docker, Blueprint Render, penyimpanan media, dan konfigurasi koneksi frontend. Audit membaca working tree, termasuk perubahan yang belum di-commit. Tidak ada perubahan infrastruktur atau migrasi remote yang dijalankan.

## Hasil audit

Arsitektur yang sesuai dengan kode: backend Laravel di Render Docker, database PostgreSQL dan media publik di Supabase. Login admin tetap menggunakan Laravel/Filament; aplikasi tidak memerlukan Supabase Auth atau Edge Functions.

| Prioritas | Temuan dan bukti | Tindakan |
| --- | --- | --- |
| P1 | Migrasi membuat unggahan S3: `backend/database/migrations/2026_09_30_084738_create_content_directory_tables.php:41`. Bucket/kunci yang belum siap menyebabkan migrasi gagal. | Siapkan bucket publik dan S3 credentials sebelum migrasi pertama. |
| P1 | Startup hanya memigrasikan database, tidak mengimpor direktori atau membuat admin: `backend/docker-entrypoint.sh:34`, `backend/app/Console/Commands/PrepareDeployment.php:27`. Blueprint memakai Free yang tidak memiliki Shell. | Bootstrap melalui Docker lokal seperti langkah di bawah, atau gunakan Shell layanan berbayar. |
| P1 jika memindahkan sistem lama | Media lokal dikecualikan dari image: `backend/Dockerfile.dockerignore:16`. Tidak ditemukan importer seluruh data SQLite ke PostgreSQL. | Pisahkan migrasi data lama dan sinkronisasi media. Impor snapshot bawaan bukan pemindahan database lama. |
| P2 | Blueprint memakai Free; fetch frontend dibatasi 5 detik, contoh `frontend/src/lib/landing.ts:81`. | Untuk produksi pilih compute yang selalu aktif; cold start Free dapat memicu fallback atau halaman unavailable. |
| P2 | Migrasi berjalan pada startup tanpa koordinasi antar-replica; temporary uploads masih lokal. | Mulai dengan satu instance. Sebelum scaling, pisahkan migration release step dan gunakan shared temporary storage. |
| P2 | RLS/grant migration mengubah default privileges serta sequence grants pada schema `public`. | Gunakan project Supabase khusus aplikasi ini. Jangan menerapkannya ke project yang dipakai aplikasi lain tanpa meninjau dampaknya. |

Kontrol yang sudah tersedia: cookie HTTPS, database sessions/cache, MFA produksi, pembatasan API, signed preview, APP_KEY wajib, PostgreSQL compatibility migration, RLS dan pencabutan akses Data API, serta adapter S3 yang menghindari object ACL unsupported. `/up` memeriksa tabel database penting melalui `CheckDatabaseHealth`; endpoint ini belum menguji operasi storage atau seluruh schema aplikasi.

## 1. Pilih konfigurasi Render

Gunakan **`backend/render.yaml`** untuk backend saja. File **`render.yaml` di root juga membuat frontend**. Pilih salah satu Blueprint agar tidak mengelola service yang sama melalui dua Blueprint.

Konfigurasi backend yang sudah tersedia:

```yaml
type: web
runtime: docker
plan: free
region: singapore
dockerfilePath: ./backend/Dockerfile
dockerContext: .
healthCheckPath: /up
autoDeployTrigger: 'off'
```

Ini cuplikan field service, bukan pengganti seluruh Blueprint. Daftar env lengkap tetap berada di `backend/render.yaml`. Free cocok untuk preview. Jika memilih compute berbayar untuk produksi, sesuaikan `plan` di Blueprint yang digunakan agar sinkronisasi berikutnya tidak mengembalikan konfigurasi Free.

Untuk setup manual Web Service: pilih runtime Docker, kosongkan **Root Directory**, isi Dockerfile Path `backend/Dockerfile`, Docker Build Context `.`, health check `/up`, dan gunakan CMD/ENTRYPOINT image. Jangan menambahkan `php artisan serve` sebagai start command. Docker membutuhkan file `frontend/public` dari root repository.

## 2. Siapkan Supabase sebelum deploy

1. Buat project khusus aplikasi ini; pilih region dekat Render Singapore dan simpan password database.
2. Buka **Connect → Session pooler**. Salin host dan username persis dari Dashboard; gunakan port `5432`. Jangan menebak host berdasarkan region. Untuk konfigurasi Laravel saat ini hindari transaction pooler port `6543` yang memiliki batas prepared statements. [Dokumentasi koneksi](https://supabase.com/docs/guides/database/connecting-to-postgres).
3. Pada pengaturan Data API, nonaktifkan Data API jika database hanya digunakan Laravel. Lakukan sebelum migrasi pertama agar tabel belum sempat terbuka selama rangkaian migrasi. Tetap pertahankan migration RLS/grants. [Keamanan Data API](https://supabase.com/docs/guides/api/securing-your-api).
4. Buat bucket **PUBLIC** bernama `website-media`. Samakan batas file bucket dengan batas aplikasi, maksimal 50 MiB untuk contoh ini. Bucket hanya untuk file yang boleh dibaca publik.
5. Pada pengaturan S3 Storage, buat **Access Key ID + Secret Access Key** dan salin region serta endpoint. Kunci ini untuk server dan melewati Storage RLS; jangan masukkan ke frontend. [Autentikasi S3](https://supabase.com/docs/guides/storage/s3/authentication).

`backend/supabase/config.toml` mengatur lingkungan CLI lokal; file tersebut tidak otomatis mengubah pengaturan project hosted. Schema aplikasi dikelola migration PHP Laravel, sehingga jangan memakai `supabase db push` untuk menggantikan `php artisan migrate`.

## 3. Isi environment backend

Template lengkap: **`backend/.env.production.example`**. Isi nilai berikut di Render Environment. Placeholder harus diganti, tanpa tanda `< >`.

```dotenv
APP_NAME="LSP TIK Global"
APP_ENV=production
APP_DEBUG=false
APP_KEY=<key-laravel-yang-disimpan-tetap>
APP_URL=https://<service-backend>.onrender.com
FRONTEND_URL=https://<domain-frontend>
TRUSTED_PROXIES=*
RUN_MIGRATIONS=true
ADMIN_MFA_REQUIRED=true
PUBLIC_CONTENT_REQUESTS_PER_MINUTE=600
CONTENT_DIRECTORY_CACHE_SECONDS=30

DB_CONNECTION=pgsql
DB_HOST=<session-pooler-host-dari-Connect>
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=<password-database>
DB_SSLMODE=require

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
PUBLIC_FILESYSTEM_DRIVER=supabase
MEDIA_MAX_UPLOAD_KB=51200
LOG_CHANNEL=stderr
LOG_LEVEL=warning
MAIL_MAILER=log

AWS_ACCESS_KEY_ID=<supabase-s3-access-key>
AWS_SECRET_ACCESS_KEY=<supabase-s3-secret-key>
AWS_DEFAULT_REGION=<region-dari-S3-settings>
AWS_BUCKET=website-media
AWS_ENDPOINT=https://<project-ref>.storage.supabase.co/storage/v1/s3
AWS_URL=https://<project-ref>.supabase.co/storage/v1/object/public/website-media
```

Gunakan endpoint yang disalin dari pengaturan S3; hostname storage langsung di atas direkomendasikan dokumentasi untuk unggahan besar. Endpoint `https://<ref>.supabase.co/storage/v1/s3` pada template lama juga merupakan bentuk endpoint Storage. URL publik `AWS_URL` berbeda dari endpoint S3.

Biarkan `DB_URL` tidak diset jika menggunakan field `DB_*`; URL lama dapat menimpa host/user yang baru. Password pada field terpisah tidak perlu URL encoding. Di Render isi nilai mentah, tanpa pembungkus kutip dotenv. `APP_URL` dan `FRONTEND_URL` adalah origin HTTPS tanpa path `/admin` atau `/api`.

`FILESYSTEM_DISK=local` benar untuk konfigurasi sekarang: media final memakai disk `public` secara eksplisit dan driver disk itu diganti menjadi Supabase. Jangan memakai API key anon/publishable/service-role sebagai password database maupun S3 credentials. Gunakan role database yang sama untuk migration dan runtime, yaitu owner tabel, karena RLS saat ini tidak menyediakan policy bagi role runtime terpisah.

## 4. Bootstrap database kosong dan admin, termasuk Render Free

Jalankan dari **root repository**, dengan Docker daemon aktif. Langkah ini menulis ke database/bucket tujuan: periksa target Supabase dan pastikan ini project baru yang benar. Untuk database lama ikuti bagian migrasi data, jangan menjalankan impor snapshot awal.

```bash
docker build --target production -f backend/Dockerfile -t lsp-global-backend:deploy .
cp backend/.env.production.example .env.render.local
chmod 600 .env.render.local
docker run --rm lsp-global-backend:deploy php artisan key:generate --show
```

Isi `.env.render.local` menggunakan nilai pada bagian 3, termasuk APP_KEY dari output terakhir. APP_KEY itu harus sama dengan Render. Jangan membagikan output key. File `.env.render.local` diabaikan Git; tetap simpan sebagai secret lokal. Gunakan format `KEY=value` tanpa pembungkus kutip untuk file `docker --env-file`; ubah baris APP_NAME menjadi `APP_NAME=LSP TIK Global`. Jangan `source` file ini ke shell.

```bash
docker run --rm --env-file .env.render.local lsp-global-backend:deploy php artisan deployment:prepare --import-content --no-interaction
docker run --rm -it --env-file .env.render.local lsp-global-backend:deploy php artisan make:filament-user
docker run --rm --env-file .env.render.local lsp-global-backend:deploy php artisan admin:grant admin@example.com --no-interaction
docker run --rm --env-file .env.render.local lsp-global-backend:deploy php artisan migrate:status --no-interaction
```

Ganti `admin@example.com` dengan email akun yang dibuat. Gunakan password minimal 12 karakter. Saat login produksi, lakukan enrollment MFA dan simpan recovery codes. Bootstrap ini tidak membutuhkan container web tetap hidup atau port mapping; command PHP langsung mengakses Supabase. Entrypoint melewatkan startup web untuk command `php`.

Impor awal membaca snapshot yang dibundel dalam repo. Jika impor konten berhasil tetapi tahap aset gagal, perbaiki storage lalu jalankan recovery berikut, bukan mengulangi importer direktori secara langsung:

```bash
docker run --rm --env-file .env.render.local lsp-global-backend:deploy php artisan deployment:prepare --import-assets --no-interaction
```

Untuk layanan berbayar, command Artisan yang sama dapat dijalankan melalui Render Shell. Shell dan one-off jobs tidak tersedia pada Free. [Batas Render Free](https://render.com/docs/free).

## 5. Deploy backend ke Render

1. Review dan commit semua file yang dibutuhkan, termasuk Dockerfile, entrypoint, config, command, adapter, migration, lockfile, dan Blueprint. Banyak file deployment masih untracked saat audit; hanya mendorong YAML tidak cukup. Jangan commit `.env` atau database lokal.
2. Push commit yang sudah ditinjau ke branch yang akan di-deploy.
3. Buat Blueprint dari `https://github.com/daffamumtaz/lsp-global` dan pilih Blueprint Path **`backend/render.yaml`**. Jika memakai root `render.yaml`, siapkan env frontend juga.
4. Isi seluruh env `sync: false` dan pastikan sama dengan bootstrap. Gunakan hostname service yang benar untuk APP_URL.
5. Apply dan periksa build/deploy logs. Apache otomatis mendengarkan `$PORT`; startup membuat cache Laravel dan menjalankan migration tertunda saat `RUN_MIGRATIONS=true`.
6. Karena `autoDeployTrigger: 'off'`, deploy commit berikutnya secara manual. Pada Blueprint existing, env baru dengan `sync: false` harus ditambahkan manual. [Referensi Blueprint](https://render.com/docs/blueprint-spec).

Jika CLI Render tersedia, temukan sintaks lewat `render blueprints --help` lalu validasi Blueprint sebelum deploy. CLI Render tidak tersedia pada lingkungan audit ini; validasi server Render belum dilakukan.

Untuk multi-instance berbayar, pindahkan migrasi ke satu release/pre-deploy step `php artisan deployment:prepare --no-interaction`, set `RUN_MIGRATIONS=false`, lalu konfigurasi shared temporary storage. Jangan mematikan migration startup sebelum menyediakan migration step penggantinya.

## 6. Hubungkan frontend

Salin nilai dari `frontend/.env.production.example`, ganti seluruh origin backend dengan domain Render. Path masing-masing API sudah ditentukan template. Atur `ADMIN_PREVIEW_ORIGIN` ke origin backend, `LANDING_MEDIA_ORIGIN` ke `https://<ref>.supabase.co`, dan `FRONTEND_URL` backend ke origin frontend. Deploy ulang frontend setelah perubahan environment.

## 7. Jika membawa data dari deployment lama

Mengganti `DB_CONNECTION` tidak memindahkan SQLite ke PostgreSQL. Backup database, media, dan APP_KEY lama; rencanakan pemindahan data dengan mempertahankan ID/relasi, JSON, boolean, sequence PostgreSQL, serta kredensial/MFA. Verifikasi di staging terlebih dahulu dan batasi penulisan saat cutover. Jangan menjalankan `migrate:fresh` atau impor snapshot untuk menggantikan migrasi data CMS yang sudah diedit.

Untuk media lokal, jalankan dari host yang memiliki file lama dan driver/kredensial Supabase yang sudah dikonfigurasi. Bersihkan config cache lokal bila masih memakai konfigurasi sebelumnya:

```bash
php artisan config:clear
php artisan media:sync-to-supabase --dry-run
php artisan media:sync-to-supabase
```

Command mempertahankan path dan melewati file remote yang sudah ada. Dry-run tetap membutuhkan akses baca remote untuk mengecek keberadaan file. Sinkronisasi media tidak memindahkan record database. Pertahankan source sampai hasil upload dan URL diverifikasi.

## 8. Verifikasi setelah deploy

```bash
curl --fail --show-error https://<backend>/up
curl --fail --show-error https://<backend>/api/landing-page
curl --fail --show-error https://<backend>/api/content-directory
```

Periksa payload konten yang diharapkan, login `/admin`, MFA, preview frontend, dan unggah gambar melalui CMS. Buka URL publik hasil unggahan, redeploy, lalu pastikan file tetap tersedia. `/up` saja belum membuktikan operasi S3 berhasil. Pastikan migration status seluruhnya selesai, RLS aktif dan role anon/authenticated tidak mendapat akses tabel Laravel. Verifikasi backup database dan object storage secara terpisah beserta prosedur restore.

Jika startup gagal: cek DB host/user/password, session port 5432, TLS, dan migration log. `AccessDenied` saat unggah: cek S3 credentials, bucket, region dan endpoint; bucket private tidak cocok dengan disk publik saat ini. Login/CSRF atau URL HTTP: cek APP_URL HTTPS, trusted proxies, cookie secure dan APP_KEY. Jangan menyelesaikan error dengan mengaktifkan APP_DEBUG di produksi.

## Bukti verifikasi audit

Tes terfokus deployment/storage/security dijalankan dengan `vendor/bin/phpunit --filter='Deployment|PrepareDeployment|PublicContentProtection|AdminSecurityPolicy|SyncPublicMedia'`: 33 passed, 4 skipped, 157 assertions. Tes PostgreSQL dilewati karena PHP lokal belum memiliki `pdo_pgsql`; konfigurasi `phpunit.postgres.xml` tersedia untuk database pengujian terpisah. Jangan arahkan PHPUnit ke database produksi.

Seluruh suite backend juga dijalankan dengan `vendor/bin/phpunit`: 219 tests, 214 passed, 5 skipped, 2.439 assertions. Pemeriksaan sintaks shell entrypoint (`sh -n`) lulus.

Build image Docker, koneksi Supabase aktual, operasi bucket remote, dan health layanan Render belum diverifikasi dalam audit ini. Laporan verifikasi pada dokumen sesi sebelumnya tidak menggantikan pemeriksaan deployment saat ini.

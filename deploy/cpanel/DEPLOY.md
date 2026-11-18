# Panduan lengkap deploy ke cPanel

Panduan ini menganggap Anda memakai domain utama `example.com`, domain API `api.example.com`, dan folder aplikasi `/home/USER/lsp-global`. Ganti semua contoh domain, nama akun, nama database, email, dan path dengan nilai milik Anda. cPanel bisa memakai tema Meridian atau Classic; nama menu mungkin sedikit berbeda, tetapi fitur yang dicari sama. Panduan ini adalah bagian dari repositori `lsp-global`; riwayat audit paket awal ada di [AUDIT.md](AUDIT.md).

## Gambaran hasil akhirnya

- Aplikasi Next.js berjalan sebagai aplikasi Node.js pada `https://example.com`.
- Backend Laravel berjalan pada `https://api.example.com`, dengan document root tepat ke folder `backend/public`.
- Database MySQL/MariaDB berada di hosting dan diakses backend melalui `.env`.
- Kode aplikasi disimpan di luar `public_html` jika hosting mengizinkan.

Checkout Git atau arsip kode sumber tidak menyertakan database lokal, `.env` aktif, atau unggahan lokal. Memindahkan kode saja tidak memindahkan konten dan akun yang ada di database. Ikuti bagian **Memindahkan website yang sudah berisi data** bila ingin membawa konten dan akun yang sudah ada.

Node.js 20.20.2 lolos pemeriksaan kompatibilitas paket, tetapi Node 20 sudah berstatus EOL. Untuk aplikasi produksi pilih Node 24 LTS terbaru bila tersedia; Node 22 juga didukung. Node 20 hanya pilihan sementara bila cPanel hosting memang belum menyediakan versi yang lebih baru. cPanel Anda dapat menyediakan PHP 8.3, 8.4, dan 8.5: pilih PHP 8.5. Dependensi backend memerlukan PHP minimal 8.4.1.

## 1. Siapkan sumber di komputer

1. Ambil commit rilis yang dipilih dari repositori Git. Catat hash commit agar versi yang sama dapat dipasang ulang atau dikembalikan.
2. Buat arsip rilis dari checkout bersih jika File Manager cPanel dipakai untuk unggah. Sertakan `frontend/`, `backend/`, dan dokumentasi yang diperlukan. Jangan sertakan `.git`, `.env` aktif, database lokal, unggahan CMS, `node_modules`, `vendor`, atau `.next` dari mesin lain.
3. Simpan arsip dan checksum SHA-256 di komputer. Periksa checksum sebelum ekstraksi. Ukuran arsip bergantung pada aset dan tidak lagi mengikuti ukuran paket `lspglobal-coba` yang lama.

## 2. Siapkan domain dan folder di cPanel

1. Masuk ke cPanel. Di Meridian atau Classic, gunakan **Search Tools** dan cari **Domains**. Pastikan domain utama sudah ditambahkan dan DNS-nya mengarah ke hosting.
2. Siapkan subdomain untuk API, misalnya `api.example.com`. Tambahkan melalui **Domains** atau **Subdomains** (tergantung versi cPanel) dan aktifkan sertifikat HTTPS/AutoSSL untuk domain utama dan subdomain API.
3. Di **File Manager**, buka direktori home akun, biasanya `/home/USER`. Jangan mulai di `public_html`.
4. Bila sudah ada folder `lsp-global` dari percobaan sebelumnya, ubah namanya menjadi `lsp-global-backup` atau pindahkan ke lokasi aman. Ekstrak arsip rilis baru ke home, bukan di atas folder lama; ekstraksi di atas folder lama dapat meninggalkan file usang.
5. Unggah arsip rilis ke `/home/USER` melalui **File Manager → Upload**, lalu tekan **Extract**. Pastikan hasilnya `/home/USER/lsp-global/frontend` dan `/home/USER/lsp-global/backend`. Jika arsip Anda tidak memiliki folder tingkat atas `lsp-global`, buat folder itu sebelum ekstraksi.
6. Pastikan File Manager menampilkan file tersembunyi (pengaturan **Show Hidden Files / dotfiles**). File `.htaccess`, `.env.production.example`, dan file konfigurasi lain yang diawali titik diperlukan. Jangan membuat `.env` dapat diunduh dari web.
7. Jika ukuran unggahan dibatasi, unggah melalui SFTP atau minta hosting menaikkan batas. Jangan mengekstrak kode backend ke document root publik.

### Arahkan domain API ke Laravel

Di pengaturan domain `api.example.com`, set **Document Root** ke:

```text
/home/USER/lsp-global/backend/public
```

Ini penting: arahkan ke folder `public`, bukan `backend` atau folder proyek. Laravel harus menyajikan web hanya dari `public`. Jika cPanel tidak mengizinkan document root di luar `public_html`, tanyakan dukungan hosting tentang custom document root atau cara aman mengarahkan subdomain ke folder `backend/public`; jangan menyelesaikannya dengan memindahkan seluruh folder backend ke web root.

## 3. Atur PHP untuk domain API

1. Cari **PHP Selector** atau **MultiPHP Manager**. Jika memakai PHP Selector seperti pada gambar Anda, buka tab **Per Domain Settings** dan pilih baris `api.example.com`. Pengaturan global dapat memengaruhi domain lain, jadi pilih per-domain bila tersedia.
2. Pilih **PHP 8.5**. Jika versi 8.5 belum dapat digunakan untuk domain tersebut, pilih PHP 8.4 minimal versi patch 8.4.1. Jangan pilih 8.3.
3. Di tab **Extensions**, aktifkan `pdo_mysql`, `intl`, `gd`, `exif`, `mbstring`, `fileinfo`, `zip`, `dom`, `simplexml`, `xml`, `xmlreader`, dan `xmlwriter` serta ekstensi inti bawaan. `pdo_mysql` dibutuhkan untuk koneksi database; `intl` dan `gd` dibutuhkan oleh dependensi aplikasi.
4. Simpan/perbarui pilihan jika cPanel meminta tindakan itu. Nama ekstensi yang tersedia dapat berbeda sedikit menurut hosting.
5. Cari **Terminal** di cPanel. Periksa `php -v` dan `composer --version`. PHP untuk Terminal (CLI) harus memenuhi minimal 8.4.1 juga. Pengaturan PHP domain dan PHP CLI kadang berbeda. Jika `php -v` masih 8.3 atau Composer tidak tersedia, gunakan binary PHP 8.5 dan Composer yang disediakan hosting; minta nama/path yang tepat ke penyedia hosting, jangan menebak path binary.

Contoh perintah berikut dijalankan di Terminal cPanel setelah PHP CLI dan Composer siap:

```bash
cd ~/lsp-global/backend
php -v
composer --version
```

## 4. Buat database MySQL/MariaDB

1. Cari **MySQL Database Wizard** (atau **MySQL Databases**) di cPanel.
2. Buat database baru, misalnya nama pendek `lspglobal`. cPanel biasanya menambahkan prefix username akun, sehingga nama lengkap dapat menjadi `USER_lspglobal`.
3. Buat user database khusus aplikasi dengan password kuat yang berbeda dari password cPanel. Simpan password di password manager.
4. Tambahkan user ke database yang baru dibuat dan berikan **ALL PRIVILEGES**.
5. Catat nama database lengkap, username lengkap, password, dan host database. Host sering `localhost`, tetapi gunakan nilai yang diberikan hosting bila berbeda.
6. Buka **phpMyAdmin** untuk memastikan database baru tersedia dan kosong. Belum perlu membuat tabel; migrasi Laravel yang akan membuatnya.

## 5. Isi konfigurasi backend

Di File Manager, buka `/home/USER/lsp-global/backend`. Salin `.env.production.example` menjadi `.env` (File Manager dapat menyalin/rename; jika Terminal tersedia, `cp .env.production.example .env`). Edit `.env` dan isi nilai seperti ini:

```dotenv
APP_NAME="LSP Global"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.example.com
FRONTEND_URL=https://example.com

DB_CONNECTION=mariadb
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=USER_lspglobal
DB_USERNAME=USER_lspuser
DB_PASSWORD="GANTI_DENGAN_PASSWORD_DATABASE"

SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_STORE=database
MAIL_MAILER=log
```

Gunakan seluruh nilai lain dari template. Jangan memasukkan tanda kurung sudut (`< >`) di sekitar nilai. Bila password mengandung karakter khusus, pertahankan tanda kutip dan format `.env`. `QUEUE_CONNECTION=sync` membuat tugas antrean berjalan di request, cocok untuk setup sederhana tanpa worker. Bila memilih `database`, siapkan worker/cron sesuai bagian pembaruan di bawah. `MAIL_MAILER=log` tidak mengirim email; konfigurasi SMTP hosting jika aplikasi perlu mengirim email sungguhan.

Lindungi `.env`: simpan di folder `backend` di luar document root `backend/public`, jangan mengirimkannya ke Git atau membagikan screenshot berisi rahasia. Pastikan `APP_DEBUG=false`.

## 6. Pasang backend dan pilih alur data

Di Terminal cPanel:

```bash
cd ~/lsp-global/backend
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
```

Jika perintah pertama gagal, baca pesan platform PHP/extension dan cocokkan dengan langkah PHP. Jangan memakai `--ignore-platform-reqs` untuk melewati masalah.

### A. Instalasi baru tanpa data lokal

Gunakan alur ini hanya untuk database baru/kosong. Ini membuat kunci enkripsi baru, tabel database, dan konten awal dari sumber aplikasi:

```bash
php artisan key:generate --no-interaction
php artisan deployment:prepare --import-content --no-interaction
php artisan storage:link --no-interaction
php artisan make:filament-user
```

Saat diminta, buat akun administrator awal dan catat kredensialnya secara aman. Lalu berikan hak admin aplikasi dengan email akun tersebut:

```bash
php artisan admin:grant admin@example.com --no-interaction
php artisan optimize --no-interaction
```

Ganti `admin@example.com` dengan email yang benar. `deployment:prepare --import-content` memasukkan konten awal yang dikemas dengan aplikasi; jangan jalankan berulang di database yang sudah berisi konten CMS karena dapat menambahkan ulang konten sumber.

### B. Memindahkan website yang sudah berisi data

Pilih bagian ini jika ingin mempertahankan data CMS, admin, atau unggahan dari instalasi lokal/hosting lain. Sebelum perubahan, buat backup database sumber dan salinan folder unggahan. Pastikan Anda tahu database tujuan agar tidak menimpa data lain.

1. Ekspor database sumber (misalnya melalui phpMyAdmin) sebagai SQL, lalu impor ke database hosting melalui phpMyAdmin. Jika ukuran SQL melampaui batas unggah, gunakan Terminal atau minta bantuan hosting.
2. Salin `backend/storage/app/public` dari proyek sumber ke `backend/storage/app/public` di hosting. Salin juga berkas privat lain yang memang dipakai aplikasi. Folder `storage` perlu digabung dengan hati-hati agar tidak menghapus file yang dibutuhkan aplikasi.
3. Ambil nilai `APP_KEY` dari `.env` sumber dan pertahankan persis nilainya di `.env` hosting. Kunci ini diperlukan untuk membaca data terenkripsi, termasuk MFA. Jangan jalankan `key:generate` pada migrasi data.
4. Perbarui nilai domain dan koneksi database di `.env`, tetapi jangan mengganti `APP_KEY`.
5. Jalankan migrasi dan optimasi berikut. Migrasi hanya menerapkan perubahan skema yang disediakan aplikasi:

   ```bash
   cd ~/lsp-global/backend
   php artisan migrate --force --no-interaction
   php artisan storage:link --no-interaction
   php artisan optimize --no-interaction
   ```

Jangan jalankan `deployment:prepare --import-content` untuk alur ini. Setelah migrasi, periksa jumlah konten dan akun admin di aplikasi sebelum mengarahkan pengguna ke situs baru.

Folder `backend/storage` dan `backend/bootstrap/cache` harus bisa ditulis oleh akun cPanel. Jangan gunakan izin `777`. Jika `storage:link` gagal karena symlink dinonaktifkan, minta hosting mengaktifkan symlink atau memberi prosedur Laravel yang didukung.

## 7. Siapkan aplikasi frontend Node.js

1. Cari **Setup Node.js App** atau **Node.js Selector** di cPanel. Jika menu tidak muncul, hosting perlu mengaktifkan fitur Node.js untuk akun Anda.
2. Buat aplikasi. Pilih Node.js **24** versi terbaru bila ada; Node.js 22 juga didukung. Pilih Node 20.20.2 hanya jika itu versi tertinggi yang disediakan, dengan rencana upgrade saat hosting menyediakan Node LTS yang masih didukung.
3. Isi aplikasi kira-kira seperti berikut. Jika form berbeda, gunakan nilai ekuivalen:

   | Pengaturan | Nilai |
   | --- | --- |
   | Node.js version | 24 (atau 22; sementara 20.20.2) |
   | Application mode | Production |
   | Application root | `lsp-global/frontend` |
   | Application URL | domain utama, misalnya `https://example.com/` |
   | Application startup file | `app.js` |

   Jika form hanya menerima root relatif terhadap home, masukkan `lsp-global/frontend`, bukan `/home/USER/...`.
4. Simpan/buat aplikasi. cPanel akan menampilkan perintah untuk mengaktifkan environment, umumnya `source /home/USER/nodevenv/lsp-global/frontend/VERSI/bin/activate`. Salin dan jalankan perintah persis dari panel; bagian path dan versi berbeda tiap akun. Pastikan `node -v` menunjukkan versi yang dipilih.
5. Di File Manager, salin `frontend/.env.production.example` menjadi `frontend/.env.production`. Isi URL API dan origin di dalamnya dengan `https://api.example.com`. Template mencakup enam URL API yang dipakai situs; pertahankan nama variabel yang ada. Untuk domain utama biarkan `NEXT_PUBLIC_BASE_PATH` kosong. `LANDING_MEDIA_ORIGIN` dan `ADMIN_PREVIEW_ORIGIN` harus berupa origin backend tanpa path.
6. Jika Node Selector punya bagian **Environment variables**, set `NODE_ENV=production` di sana. Pastikan juga variabel frontend dalam `.env.production` benar sebelum build. Nilai `NEXT_PUBLIC_*` disematkan ke output saat build; mengubahnya setelah build mengharuskan build ulang.
7. Pasang dependency dan build saat environment Node sudah aktif:

   ```bash
   cd ~/lsp-global/frontend
   npm install --include=dev --no-audit --no-fund
   npm run build
   ```

   `--include=dev` diperlukan karena TypeScript dan tool CSS dipakai saat build. cPanel Node Selector dapat mengelola `node_modules` melalui symlink, maka gunakan tombol **Run NPM Install** atau `npm install` dari environment itu. Pada mesin build biasa tanpa symlink Node Selector, gunakan `npm ci --include=dev` agar mengikuti lockfile.
8. Kembali ke **Setup Node.js App** dan tekan **Restart**. `app.js` membaca port yang disediakan hosting. Jangan mengganti port secara manual.

Jika hosting tidak mampu melakukan build karena memori atau batas waktu, build di mesin Linux dengan versi Node yang sama dan environment produksi yang sama. Unggah hasil `.next` ke aplikasi. Tetap pasang dependency di environment cPanel; hasil build harus cocok dengan versi Node dan konfigurasi URL yang dipakai.

## 8. Build tema admin (hanya jika diperlukan)

Checkout Git mungkin tidak menyertakan hasil build tema admin atau aset plugin Filament. Setelah `composer install`, periksa `backend/public/build/manifest.json` dan tampilan `/admin`. Jika belum ada atau aset tidak cocok, aktifkan environment Node yang sama, lalu:

```bash
cd ~/lsp-global/backend
npm install --include=dev --no-audit --no-fund
npm run build
```

## 9. Periksa situs setelah deploy

1. Buka `https://api.example.com/up`; backend harus merespons sukses.
2. Buka `https://api.example.com/api/landing-page`; respons harus berupa JSON, bukan halaman error.
3. Buka `https://api.example.com/admin`; halaman login Filament harus tampil dengan CSS dan JavaScript lengkap.
4. Buka `https://example.com`; periksa halaman utama, navigasi, dan halaman konten.
5. Periksa gambar/berkas unggahan, video, preview editor, dan ekspor tabel admin jika fitur itu digunakan.
6. Jika gagal, lihat **Errors**, log PHP, log aplikasi Laravel `backend/storage/logs`, log Node.js di Setup Node.js App, dan log domain. Jangan mengaktifkan `APP_DEBUG=true` pada situs publik.

## 10. Pembaruan dan upgrade Node.js

Saat memperbarui kode, pertahankan `.env`, `APP_KEY`, database, serta file unggahan. Backup database dan unggahan sebelum update. Setelah memasang file versi baru, jalankan Composer sesuai lockfile, jalankan migrasi baru dengan `php artisan migrate --force --no-interaction`, lalu bersihkan dan buat ulang cache konfigurasi:

```bash
php artisan optimize:clear --no-interaction
php artisan optimize --no-interaction
```

Build ulang frontend dan restart aplikasi Node. Jangan menimpa `.env` saat mengunggah versi baru.

Untuk berpindah dari Node 20 ke Node 24 dengan codebase yang sama: pilih Node 24 pada Setup Node.js App, aktifkan perintah environment baru yang ditampilkan cPanel, pasang ulang dependency di environment tersebut (`npm install --include=dev` atau **Run NPM Install**), jalankan `npm run build`, lalu restart aplikasi. Bila Node Selector membuat environment terpisah untuk setiap versi, jangan memakai `node_modules` dari environment Node 20. Lockfile yang sama tetap digunakan. Jika membangun tema backend di hosting, pasang dependency dan jalankan build tema di environment Node baru juga. Runtime backend tetap PHP.

Jika memakai `QUEUE_CONNECTION=database`, siapkan worker persisten yang didukung hosting atau cron. Contoh cron setiap menit (ganti `/PATH/TO/PHP85` dengan path PHP CLI yang dikonfirmasi hosting):

```text
* * * * * /PATH/TO/PHP85 /home/USER/lsp-global/backend/artisan queue:work --stop-when-empty --max-time=50 --tries=3 >> /home/USER/lsp-global/backend/storage/logs/queue-worker.log 2>&1
```

Jika tidak mengaktifkan antrean database, `QUEUE_CONNECTION=sync` tidak memerlukan cron worker.

## Kendala umum

- **Composer menyatakan versi PHP/extension tidak cocok:** pastikan versi PHP CLI dengan `php -v`, bukan hanya versi pada PHP Selector. Aktifkan extension yang disebut error atau gunakan binary PHP/Composer versi 8.5 dari hosting.
- **500 atau database tidak tersambung:** cek nama database dan user lengkap dengan prefix cPanel, password, DB host, serta hak ALL PRIVILEGES. Cek `backend/storage/logs`.
- **Gambar tidak tampil:** pastikan berkas sumber ada di `backend/storage/app/public`, symlink `backend/public/storage` berhasil, dan `APP_URL` memakai HTTPS.
- **Frontend menampilkan data kosong atau API error:** cocokkan semua endpoint di `frontend/.env.production` dengan domain API, lalu build ulang dan restart.
- **Halaman admin tanpa styling:** pastikan `backend/public/build` tersedia setelah build tema dan aset Filament diterbitkan oleh `composer install`; setelah perubahan dependency/theme, build ulang tema.
- **Aplikasi Node tidak mau mulai:** cek versi Node, Application root, startup file `app.js`, environment produksi, dan log Node di Setup Node.js App.
- **Build kehabisan memori:** gunakan mesin build dengan versi Node yang sama, unggah hasil build dan tetap pasang dependency runtime melalui Node Selector.

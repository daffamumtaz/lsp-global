# Staging Ubuntu melalui SSH

Target: satu server Ubuntu 24.04 LTS dengan dua domain, Nginx, PHP 8.5-FPM, MariaDB, Node.js 24, dan systemd. Backend berada di `/var/www/lsp-global/backend`, frontend di `/var/www/lsp-global/frontend`. Ganti `SERVER_IP`, `staging.example.com`, dan `cms-staging.example.com` dengan nilai Anda. Ini panduan instalasi baru; jangan menimpa instalasi produksi.

Staging belum dideploy dari sesi ini. Diperlukan akses SSH/sudo, dua DNS A record menuju server, port 22/80/443, dan pemeriksaan pada server asli. Jangan gunakan `artisan serve` atau `npm run dev` sebagai layanan staging.

## 1. Persiapan server

Login sebagai akun Ubuntu yang memiliki sudo. Pertahankan sesi SSH yang sedang terbuka selama pengaturan firewall.

```bash
ssh ubuntu@SERVER_IP
sudo apt update
sudo apt upgrade -y
sudo apt install -y nginx mariadb-server composer git rsync unzip curl ca-certificates software-properties-common certbot python3-certbot-nginx
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.5-cli php8.5-fpm php8.5-mysql php8.5-mbstring php8.5-xml php8.5-curl php8.5-zip php8.5-intl php8.5-gd php8.5-bcmath
sudo update-alternatives --set php /usr/bin/php8.5
```

PPA Ondřej adalah repositori pihak ketiga untuk PHP, bukan paket bawaan Ubuntu. Tinjau sumber paket sesuai kebijakan server. Lockfile aplikasi membutuhkan setidaknya PHP 8.4.1; panduan ini menggunakan 8.5 yang sudah dipakai lokal.

Pasang Node.js 24 melalui repositori NodeSource. Unduh skrip dahulu agar dapat ditinjau sebelum dijalankan sebagai root:

```bash
curl -fsSL https://deb.nodesource.com/setup_24.x -o /tmp/nodesource-24.sh
less /tmp/nodesource-24.sh
sudo bash /tmp/nodesource-24.sh
sudo apt install -y nodejs
php8.5 -v
php8.5 -m
node --version
npm --version
sudo systemctl enable --now nginx mariadb php8.5-fpm
```

Pastikan PHP CLI dan FPM memiliki `pdo_mysql`, `mbstring`, XML/DOM, curl, zip, intl, GD, bcmath, dan fileinfo. Jangan membuka port MariaDB atau port Node 3000 ke internet.

```bash
sudo adduser deploy
sudo usermod -aG www-data deploy
sudo install -d -o deploy -g www-data -m 2750 /var/www/lsp-global
sudo install -d -o deploy -g deploy -m 700 /home/deploy/.ssh
sudoedit /home/deploy/.ssh/authorized_keys
sudo chown deploy:deploy /home/deploy/.ssh/authorized_keys
sudo chmod 600 /home/deploy/.ssh/authorized_keys
```

Isi `authorized_keys` dengan public key komputer Anda. Verifikasi login `ssh deploy@SERVER_IP` di terminal baru sebelum mengubah firewall. Akun `deploy` tidak perlu sudo penuh; konfigurasi systemd/Nginx dilakukan melalui akun administrator.

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

Sesuaikan aturan SSH jika port SSH bukan 22. Pastikan firewall penyedia VPS juga mengizinkan port yang sama.

## 2. Database staging terpisah

Di server, buka `sudo mariadb`, lalu jalankan SQL berikut dengan password acak milik staging. Jangan memakai password contoh atau akun root untuk aplikasi.

```sql
CREATE DATABASE lsp_global_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lsp_staging'@'127.0.0.1' IDENTIFIED BY 'GANTI_PASSWORD_ACAK';
GRANT ALL PRIVILEGES ON lsp_global_staging.* TO 'lsp_staging'@'127.0.0.1';
```

## 3. Kirim kode melalui SSH

Dari komputer lokal, bukan server:

```bash
cd /home/neuralia/Projects/lsp-global
rsync -az --exclude-from=deploy/ubuntu/rsync.exclude ./ deploy@SERVER_IP:/var/www/lsp-global/
```

Jangan menambahkan `--delete`: environment server, upload, database, dan build tidak boleh terhapus karena tidak ada di komputer sumber. Daftar pengecualian tidak mengirim `.git`, `.env` aktif, vendor, node_modules, build/cache, SQLite, upload, serta arsip dokumen/foto sumber di root. Upload ditransfer terpisah pada langkah berikutnya. File media yang memang digunakan aplikasi di `backend/resources/` dan `frontend/public/` tetap dikirim.

## 4. Pindahkan data dan media yang sudah ada

Migrasi Laravel membangun struktur, bukan memindahkan konten database lokal. Untuk mempertahankan data, ekspor database MariaDB aktif dari lokal. Lihat nama database di `backend/.env`; nama hasil migrasi Anda mengandung waktu dan suffix acak.

Hentikan proses lokal yang menulis data selama snapshot dan penyalinan media. Dari lokal:

```bash
umask 077
mariadb-dump --socket=/home/neuralia/Projects/lspujikom/.local/mariadb.sock --user=root --single-transaction --routines --triggers --hex-blob NAMA_DATABASE_LOKAL > /tmp/lsp-global-staging.sql
scp /tmp/lsp-global-staging.sql deploy@SERVER_IP:/home/deploy/lsp-global-staging.sql
rsync -az backend/storage/app/public/ deploy@SERVER_IP:/var/www/lsp-global/backend/storage/app/public/
```

Jangan menggunakan `--databases`; dump harus dapat dimuat ke database staging dengan nama berbeda. Dump berisi data sensitif, termasuk akun admin dan sesi. Jaga izin file dan hapus salinan transfer setelah import diverifikasi.

Di server sebagai `deploy`:

```bash
chmod 600 /home/deploy/lsp-global-staging.sql
mariadb --protocol=TCP -h 127.0.0.1 -u lsp_staging -p lsp_global_staging < /home/deploy/lsp-global-staging.sql
```

Lokal saat audit memakai MariaDB 13.0.2; versi Ubuntu dapat berbeda. Impor logis lintas versi harus berhasil tanpa error sebelum diteruskan. Jangan menyalin datadir MariaDB antarmesin. Jika ada incompatibility SQL, gunakan versi MariaDB server yang kompatibel dan validasi restore; jangan menghapus statement dump secara sembarang agar import terlihat sukses.

Untuk staging, hapus sesi dan job tersalin agar pengguna perlu login ulang dan antrean lokal tidak dijalankan di server:

```bash
mariadb --protocol=TCP -h 127.0.0.1 -u lsp_staging -p lsp_global_staging
```

```sql
DELETE FROM sessions;
DELETE FROM cache;
DELETE FROM cache_locks;
DELETE FROM jobs;
```

Ini hanya pada database staging yang baru dipulihkan, bukan database lokal. Akun dan password admin tetap sama. MFA terenkripsi memerlukan APP_KEY lama.

## 5. Environment dan build

Di server sebagai `deploy`:

```bash
cd /var/www/lsp-global/backend
cp .env.staging.example .env
chmod 640 .env
nano .env
```

Isi APP_KEY dengan **key yang sama dari backend lokal jika memulihkan database lokal**. Jangan menjalankan `key:generate` pada database yang diimpor; key baru dapat membuat rahasia MFA/data terenkripsi tidak terbaca. Untuk database yang benar-benar baru tanpa data lama, key baru dibuat sekali saja.

Isi APP_URL/FRONTEND_URL dengan domain HTTPS, DB_PASSWORD dengan password staging, DB_HOST=127.0.0.1, dan biarkan DB_SOCKET kosong. Jangan menyalin socket lokal komputer Anda. Pastikan APP_ENV=staging, APP_DEBUG=false, ADMIN_MFA_REQUIRED=true, SESSION_SECURE_COOKIE=true, MAIL_MAILER=log. Email staging dicatat ke log, tidak dikirim ke pengguna asli.

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer check-platform-reqs --no-dev
npm ci --ignore-scripts
npm run build
cd ../frontend
cp .env.production.example .env.production
chmod 640 .env.production
nano .env.production
```

Atur keenam `*_API_URL` ke domain backend staging yang benar, `LANDING_MEDIA_ORIGIN` dan `ADMIN_PREVIEW_ORIGIN` ke origin backend HTTPS. Semua URL harus sudah benar saat build karena sebagian konfigurasi disematkan dalam build Next.js. Jangan gunakan origin Supabase lama atau localhost pada konfigurasi remote ini.

```bash
npm ci
npm run build
```

Build frontend dapat memerlukan backend yang sudah bisa diakses. Jika domain belum aktif, selesaikan langkah Nginx/HTTPS dan persiapan backend, lalu ulangi build frontend sebelum menyalakan layanannya. Build membutuhkan resource lebih besar daripada runtime; pantau RAM/disk server jika proses terbunuh.

## 6. Hak akses, Nginx, HTTPS

Sebagai administrator server:

```bash
sudo mkdir -p /var/www/lsp-global/backend/storage/framework/{cache/data,sessions,views} /var/www/lsp-global/backend/storage/logs /var/www/lsp-global/backend/storage/app/{private,public} /var/www/lsp-global/backend/bootstrap/cache
sudo chown -R deploy:www-data /var/www/lsp-global
sudo chmod -R g+rX /var/www/lsp-global
sudo chmod -R g+rwX /var/www/lsp-global/backend/storage /var/www/lsp-global/backend/bootstrap/cache
sudo find /var/www/lsp-global/backend/storage /var/www/lsp-global/backend/bootstrap/cache -type d -exec chmod g+s {} +
sudo cp /var/www/lsp-global/deploy/ubuntu/php-staging.ini /etc/php/8.5/fpm/conf.d/99-lsp-staging.ini
sudo cp /var/www/lsp-global/deploy/ubuntu/nginx.conf /etc/nginx/sites-available/lsp-global-staging
sudoedit /etc/nginx/sites-available/lsp-global-staging
sudo ln -s /etc/nginx/sites-available/lsp-global-staging /etc/nginx/sites-enabled/lsp-global-staging
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx -d staging.example.com -d cms-staging.example.com --redirect
sudo certbot renew --dry-run
sudo systemctl restart php8.5-fpm
```

Ganti kedua domain di konfigurasi Nginx dan perintah Certbot. DNS harus sudah menunjuk server. Jangan lanjut memakai cookie aman melalui HTTP; aktifkan HTTPS dahulu. Nginx hanya mengekspos `backend/public`, melarang eksekusi file PHP lain, dan meneruskan frontend ke loopback Node. Header `noindex` mengurangi pengindeksan, tetapi bukan pembatas akses. Untuk staging privat, batasi akses melalui firewall penyedia/VPN dengan tetap mengizinkan koneksi server sendiri ke API backend. Jangan menambahkan Basic Auth ke backend tanpa menyesuaikan autentikasi fetch Next.js.

## 7. Persiapan database dan layanan

Setelah `.env`, dependency, database dan media tersedia, sebagai `deploy`:

```bash
bash /var/www/lsp-global/deploy/ubuntu/prepare-staging.sh --confirm-staging
```

Skrip memeriksa environment staging, MariaDB, APP_KEY, debug off, dan origin HTTPS; mengaktifkan maintenance mode; menjalankan pending migration tanpa mengimpor ulang konten; membuat storage symlink; lalu cache konfigurasi/rute/view. Jika gagal, maintenance tetap aktif untuk diperiksa. Jangan menjalankan `migrate:fresh` atau impor konten awal pada database restore.

Sebagai administrator:

```bash
sudo cp /var/www/lsp-global/deploy/ubuntu/lsp-global-*.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now lsp-global-frontend lsp-global-worker
sudo systemctl restart php8.5-fpm
```

Sebagai `deploy`:

```bash
cd /var/www/lsp-global/backend
php8.5 artisan up
php8.5 artisan migrate:status
curl --fail --silent --show-error https://cms-staging.example.com/up
curl --fail --silent --show-error https://cms-staging.example.com/api/content-directory > /tmp/lsp-content-check.json
curl --fail --silent --show-error https://staging.example.com/ > /tmp/lsp-home-check.html
```

Lalu periksa di browser: login admin/MFA, halaman depan, data skema/TUK/asesor, peta, galeri, video (seek), upload file, dan iframe preview admin. HTTP 200 frontend saja tidak cukup karena frontend memiliki fallback konten. Cocokkan data API dengan data yang diimpor dan pastikan media berasal dari domain backend staging. Setelah verifikasi, hapus dump transfer dari lokal dan server.

Tidak ada scheduled task bisnis pada `routes/console.php` saat audit; jangan menambah cron scheduler tanpa kebutuhan. Worker systemd disediakan untuk koneksi antrean database.

## 8. Update berikutnya, log, dan rollback

Sebelum update: backup database staging dan `backend/storage/app/public`, simpan `.env` serta APP_KEY secara privat, dan catat revision kode yang sedang berjalan. Hentikan worker dan frontend, aktifkan `php8.5 artisan down`, transfer kode tanpa `--delete`, lalu ulangi Composer install, kedua npm build, dan `prepare-staging.sh --confirm-staging`. Menghentikan frontend menghindari mengganti `.next` ketika proses lama masih membaca build tersebut.

```bash
sudo systemctl restart php8.5-fpm lsp-global-frontend lsp-global-worker
```

Kemudian `php8.5 artisan up` dan ulangi smoke test. Jangan mengimpor ulang snapshot lokal setiap deploy karena akan menimpa perubahan staging.

```bash
sudo journalctl -u lsp-global-frontend -u lsp-global-worker -n 100 --no-pager
sudo tail -n 100 /var/log/nginx/error.log
tail -n 100 /var/www/lsp-global/backend/storage/logs/laravel-*.log
```

Rollback kode dengan snapshot/revision sebelumnya lalu install dependency/build ulang. Jika migration tidak backward-compatible, pulihkan database dan media dari snapshot yang sesuai dalam maintenance mode. Jangan menjalankan `migrate:rollback` secara buta. Rancangan ini memakai satu direktori aplikasi dan maintenance window, belum merupakan deploy tanpa downtime.

Referensi: [Laravel deployment](https://laravel.com/framework/docs/deployment), [Next.js self-hosting](https://nextjs.org/docs/app/guides/self-hosting), [Ubuntu PHP](https://ubuntu.com/server/docs/programming-php/).

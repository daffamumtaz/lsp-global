# Pembaruan Skema dari Checkpoint Data Engineer Implementation Plan

> **For agentic workers:** Gunakan superpowers:executing-plans saat pengguna mengotorisasi pelaksanaan. Kerjakan per tugas dengan checklist berikut.

**Goal:** Memperbarui delapan skema lama dan menambah empat skema mulai dari Insinyur Data (Data Engineer), tanpa mengubah 15 skema hasil batch kemarin atau skema lain di luar pemetaan.

**Architecture:** Pakai importer yang sudah tersedia dengan manifest dan journal batch baru, pemetaan ID eksplisit, baseline penuh target, checksum aset, dry-run, transaksi, backup, dan rollback. Tidak perlu migrasi struktur database. Pertahankan ID/slug dan seluruh atribut lama di luar nama, unit, persyaratan dasar, dan gambar yang sengaja dipilih.

**Tech Stack:** Laravel 13, Eloquent/Artisan, MariaDB, public storage, SSH; Next.js hanya diverifikasi terhadap nilai kosong dan konten terbaru.

**Spec:** `New Skema.md` versi modified 2026-10-29, checkpoint pengguna pada Insinyur Data (termasuk skema tersebut), dan `docs/audits/2026-10-29-new-skema-data-engineer.md`.

## Global Constraints

- Pengguna telah mengotorisasi implementasi dan penerapan melalui SSH pada 29 Oktober 2026. Catatan pelaksanaan berada di bagian akhir.
- Hash sumber saat audit: `b7d638fdd45a4d3efe68edab9df9602da98a9d8faa5f670786b147e2d83f0cee`.
- Cakupan sumber hanya entri 16–27 (baris 25–36); 15 entri pertama tetap.
- Penggantian eksplisit: source_index→target_id `16→25, 20→17, 21→23, 22→22, 23→21, 24→19, 25→20, 26→36`.
- Penambahan: source_index `17, 18, 19, 27`; total akhir yang diharapkan 51 jika baseline masih 47.
- Instruksi eksplisit pengguna: “Jenis skema yang kosong defaultnya Okupasi”. Semua 12 entri batch ditetapkan sebagai Okupasi.
- Jangan mengganti berdasarkan kemiripan nama/kode otomatis. Supervisor Data Scientist bukan target ID 21; Information Security Manager bukan target ID 14.
- Jangan menimpa artefak atau journal batch `new-schemes-20261028`.
- Pertahankan penyesuaian deployment server (basePath, public-image). Tidak ada perubahan frontend yang direncanakan; perbaikan level kosong kemarin sudah terpasang.

## Review Focus

- Batch baru tidak memproses ulang 15 skema kemarin atau menggunakan default manifest tanggal 28.
- Tumpang tindih unit Supervisor/Associate tidak menyebabkan pemetaan ID tertukar.
- Koreksi spasi dan versi kode serta empat alternatif persyaratan tersalin persis tanpa penggabungan.
- Kegagalan aset/validasi dan pengulangan batch tidak meninggalkan duplikat atau menimpa perubahan admin.
- Nilai level/kategori/metode kosong tetap dapat ditemukan di katalog dan tidak ditampilkan sebagai level 0.

## Peta file

| Aksi | File | Tanggung jawab |
| --- | --- | --- |
| Buat | `backend/resources/data/lspglobal/new-schemes-20261029.json` | 12 entri checkpoint, baseline target, atribut dari sumber, keputusan jenis, dan hash gambar |
| Buat | `backend/resources/data/lspglobal/new-scheme-photos-20261029.json` | Asal gambar, lisensi, fotografer bila tersedia, pemetaan source_index, dan checksum |
| Tambah bila perlu | `backend/resources/data/lspglobal/stock/<id-foto>.webp` | Aset baru; hindari benturan isi pada nama file lama |
| Ubah | `backend/tests/Feature/ImportNewSchemesTest.php` | Regresi batch 29 dan pelestarian batch 28 |
| Pakai tanpa perubahan kecuali tes membuktikan kebutuhan | `backend/app/Console/Commands/ImportNewSchemes.php` | Importer generic `--manifest`, preview/apply/rollback yang sudah diuji |
| Tetap | seluruh manifest, foto, plan, dan journal batch 28 | Riwayat impor sebelumnya |

### Task 1: Kunci manifest checkpoint

**Interfaces:** manifest mengikuti struktur yang diterima `ImportNewSchemes`: batch `new-schemes-20261029`, metadata sumber/hash, `baseline_ids`, `entries` berisi `source_index`, `target_id`, `before`, `attributes`, `photo_file`, `photo_sha256`.

- [x] Baca ulang `New Skema.md` dan ambil snapshot live terbaru dari 47 skema beserta halaman/highlight. Bandingkan hash dan baseline audit; jika sumber berubah, audit ulang bagian yang berubah sebelum mengunci manifest.
- [x] Susun hanya 12 entri source_index 16–27. Gunakan target map pada Global Constraints dan empat null untuk create. Masukkan `before` dari snapshot terbaru untuk setiap target.
- [x] Isi `name`, `units` (`code`, `title`), dan `basic_requirements` menurut sumber. Jumlah unit dalam urutan checkpoint wajib `[11, 8, 8, 9, 8, 7, 7, 4, 6, 4, 11, 5]`; semua persyaratan empat alternatif. Pertahankan kode versi, urutan, dan variasi judul sumber yang ditandai audit.
- [x] Delapan update tidak mengirim atribut kategori, level, harga, metode, URL pendaftaran, atau persyaratan administrasi agar nilai lama tetap. Type tetap Okupasi. Gambar lama dipakai bila cocok.
- [x] Empat tambahan: isi `type=Okupasi` dengan catatan keputusan; kategori/level/price/registration_url null, methods/administrative_requirements array kosong, is_active true. Jangan mengambil default persyaratan Junior Coder dari model.
- [x] Verifikasi manifest memuat 12 source_index unik, delapan target unik yang ada, empat create, 88 unit, dan 48 persyaratan; tidak ada source_index 1–15 atau target di luar map.

### Task 2: Pilih dan siapkan gambar

**Interfaces:** photo_file harus bernama ID numerik `.webp`, checksum SHA-256 sesuai berkas lokal; thumbnail `schemes/stock/<id>.webp`, mengikuti aturan importer sekarang.

- [x] Periksa delapan thumbnail lama beserta sumber/lisensinya terhadap judul baru; pertahankan jika relevan dan file ada.
- [x] Cari empat gambar sesuai tema pada audit; sumber harus mempunyai lisensi yang mengizinkan pemakaian website. Simpan URL foto/lisensi, fotografer bila tersedia, alasan relevansi, dan hash di manifest foto baru.
- [x] Sesuaikan rasio/crop dan ukuran dengan koleksi stok yang ada; periksa contact sheet. Jangan menimpa gambar lama jika byte/hash berbeda—gunakan ID aset lain.
- [x] Verifikasi semua photo_file dan checksum melalui dry-run/test sebelum transfer. Siapkan daftar hanya aset yang memang baru untuk backup/rollback batch.

### Task 3: Uji batch baru dengan importer yang ada

**Files:** Modify `backend/tests/Feature/ImportNewSchemesTest.php`; gunakan manifest 29, pertahankan seluruh tes batch 28.

**Interfaces:** `content:import-new-schemes --manifest=<path>` untuk preview; `--apply` menulis; `--rollback` memakai journal batch dari manifest yang sama.

- [x] Tulis regresi sebelum perubahan importer apa pun: seed baseline 47, simpan snapshot, jalankan batch 29. Assert delapan ID map diperbarui, empat create, total 51, 39 record lama lain identik, ID/slug dan field di luar dokumen pada delapan update tetap.
- [x] Tambahkan assertions: ID 25 mempunyai 11 unit termasuk `J.62PDP00.011.1`; ID 22 memakai `J.62DMI00.007.1` tanpa spasi; ID 36 memakai `.010.2/.014.2/.016.2`; ID 21 menjadi Associate dengan empat unit, Supervisor record baru dengan sembilan unit; Chief Cyber Security Officer ID 14 tidak berubah.
- [x] Assert 12 entri dan seluruh kode/judul/persyaratan cocok manifest, termasuk variasi judul ML dan `atau:` pada Technician. Assert jenis empat tambahan adalah keputusan Okupasi dan harga/level tidak dikarang.
- [x] Uji preview tidak mengubah data/storage, apply ulang tidak membuat record baru, baseline drift menggagalkan batch, aset hilang/hash salah menggagalkan batch, rollback hanya mengembalikan batch29 dan mempertahankan hasil batch28. Gunakan tes recovery kegagalan apply yang sudah ada.
- [x] Run `php artisan test --compact tests/Feature/ImportNewSchemesTest.php`. Jika importer gagal pada manifest baru, buktikan kegagalan dahulu, lakukan perubahan minimum, dan jalankan lagi sampai lolos. Jangan mengubah default manifest 28 sebagai jalan pintas.
- [x] Run `php artisan test --compact tests/Feature/ImportNewSchemesTest.php tests/Feature/ContentDirectoryTest.php tests/Feature/SchemeCategoriesTest.php` serta `vendor/bin/pint --dirty --format agent` bila PHP berubah. Jalankan `node --test src/lib/schemes.test.mjs` dari frontend untuk mempertahankan perilaku level kosong.

### Task 4: Backup, preview, apply melalui SSH

**Target:** alias `neuralia`, backend `/var/www/lsp-global-staging/backend`, database MariaDB `lsp_global_staging`, user aplikasi deploy. Jangan mengganti konfigurasi environment.

- [x] Konfirmasi path/service dan baseline ulang sebelum eksekusi. Ambil backup baru database + storage di `/var/backups/lsp-global/new-schemes-20261029/`; validasi arsip dan catat checksum. Jangan memakai ulang atau menimpa backup28.
- [x] Transfer hanya manifest29, metadata gambar29, aset yang diperlukan, dan importer jika benar-benar berubah. Jangan menyalin perubahan lokal lain yang belum diminta.
- [x] Jalankan sebagai deploy dari backend: `php artisan content:import-new-schemes --manifest=resources/data/lspglobal/new-schemes-20261029.json`. Preview harus **Update: 8; Create: 4; Unchanged: 0** bila sumber/baseline masih sama; total database tetap47 sebelum apply.
- [x] Setelah otorisasi pelaksanaan sudah ada dan preview cocok, jalankan perintah yang sama dengan `--apply`. Journal baru harus di `storage/app/private/scheme-imports/new-schemes-20261029/`.
- [x] Jalankan ulang preview eksplisit manifest29: **Update: 0; Create: 0; Unchanged: 12**. Bandingkan snapshot sesudah: total51, 39 record lama identik, delapan ID/slug tetap, halaman/highlight identik, 12 entri cocok penuh dengan manifest.
- [x] Periksa API, katalog, pencarian seluruh12 nama, detail URL lama delapan target, detail empat tambahan, dan HTTP200 gambar. Katalog default harus mencakup51, level kosong tampil belum ditetapkan, filter range eksplisit tidak menebak level.
- [x] Tidak perlu build frontend selama hanya data/aset berubah. Jika verifikasi menemukan bug frontend baru, diagnosis dan uji perubahan terpisah; pertahankan penyesuaian khusus server dan build terpisah seperti batch sebelumnya.
- [x] Simpan laporan hasil. Jika perlu membatalkan batch29 dan belum ada perubahan lanjutan, gunakan `php artisan content:import-new-schemes --manifest=resources/data/lspglobal/new-schemes-20261029.json --rollback`. Jangan menjalankan rollback default manifest28. Bila record/page sudah berubah, periksa konflik sebelum pemulihan manual.

## Batas pelaksanaan saat ini

Implementasi telah diotorisasi dan selesai diterapkan. Hasil serta lokasi backup dicatat di bawah.


## Hasil pelaksanaan — 29 Oktober 2026

- SSH berhasil; hash sumber tetap sesuai audit. Baseline47 diverifikasi ulang sebelum manifest dibuat.
- Manifest `backend/resources/data/lspglobal/new-schemes-20261029.json`: 12 entri, 88 unit, 48 butir persyaratan; semua type Okupasi berdasarkan instruksi eksplisit pengguna. SHA-256 manifest: `e4d98c1619ede94a0c0839407cd83d6538a01bda953ea0cfe367f4b08b5bbfbb`.
- Delapan thumbnail update dipertahankan. Tiga foto baru diunduh dari Pexels (34804005, 12899188, 5922669); foto tim keamanan 5380597 dipakai kembali untuk Information Security Manager. Sumber, lisensi, fotografer, dimensi, relevansi, dan hash tercatat di manifest foto29. Contact sheet sudah diperiksa.
- Pengujian: regresi checkpoint gagal sebelum manifest tersedia, lalu lolos202 assertions; tes terkait backend36 lolos607 assertions; tes katalog frontend6 lolos; Pint lolos. Importer dan frontend tidak perlu diubah atau dibuild ulang.
- Review independen menyatakan tidak ada blocker; nama,88 unit,48 persyaratan, baseline/map, dan checksum foto cocok.
- Backup baru: `/var/backups/lsp-global/new-schemes-20261029/database.sql.gz` dan `public-storage.tar`. Dump gzip dan daftar arsip storage tervalidasi.
- SHA-256 backup database: `cd9b6eaffe8d36c7405cdf0cdaa926c22644c0bfb1db773afe990403feeee040`.
- SHA-256 backup storage: `5afab84d7166704417d697da9f95c00c8e125ca42181dd5c0607ccefa7396736`.
- Transfer hanya dua manifest dan tiga aset baru; seluruh file importer, frontend, dan perubahan lokal lainnya tidak dideploy.
- Dry-run sebelum apply: **Update:8; Create:4; Unchanged:0**. Apply transaksi berhasil; ID baru48 ML Engineer,49 AI Engineer,50 Data Scientist Supervisor,51 Information Security Manager.
- Dry-run sesudah apply: **Update:0; Create:0; Unchanged:12**.
- Verifikasi database: **51 skema**, seluruh12 entri cocok penuh dengan manifest;39 record lama lain identik, termasuk15 skema kemarin. ID/slug/field lain dari delapan update tetap; halaman dan highlight identik dengan snapshot sebelum batch.
- Verifikasi HTTP/API: katalog memuat51; seluruh12 skema muncul di pencarian; halaman detail dan gambar semuanya HTTP200; unit/persyaratan/type API cocok; level kosong tetap tampil sebagai belum ditetapkan.
- Journal batch berada di `/var/www/lsp-global-staging/backend/storage/app/private/scheme-imports/new-schemes-20261029/`. Backup dan journal batch28 tetap tersedia. Rollback batch29 harus memakai manifest29 eksplisit sebagaimana Task4, dan menolak konflik perubahan lanjutan.

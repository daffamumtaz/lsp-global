# Verifikasi kategori terhadap lspglobal.id — 29 Oktober 2026

Pengguna meminta seluruh 52 skema berkategori, tanpa nilai kosong atau jenis skema “Okupasi” yang salah ditempatkan sebagai kategori. Ini menggantikan instruksi sebelumnya untuk mempertahankan kategori existing yang kosong.

## Pemeriksaan sumber langsung

- Halaman utama diambil langsung dari https://lspglobal.id/; katalog SPA pada https://lspglobal.id/skema.
- Bundle publik terbaru dari HTML: https://lspglobal.id/assets/index-H7Vxomg0.js.
- SHA-256 bundle: `97735bde94130d2d3208f19092c2c85bbe07dc58f38e3ed6abd1e94e472d76ef`.
- AST bundle diekstrak tanpa mengeksekusi kode: 78 objek skema terdiri atas dua dataset yang masing-masing memuat 39 nama skema lama, bukan 52 skema dokumen baru. Data pertama memakai label bidang dengan kapitalisasi biasa; dataset kedua menggunakan label normalisasi.
- Halaman detail sumber juga dibuka dengan browser: https://lspglobal.id/skema/6a56f001-a314-41fd-a9d5-2de66c90a153 untuk DATA MANAGEMENT STAFF. Tampilan kategori memang “Okupasi”. Dataset bidang mentah pada bundle menempatkan nama yang sama pada “Data Management System”. Dataset normalisasi mengubahnya keliru menjadi “Okupasi”. Kesalahan inilah yang terbawa pada database lama.
- Untuk jabatan yang tidak tersedia langsung pada 39 nama sumber (misalnya ML Engineer dan Enterprise Architecture Manager), kategori ditentukan berdasarkan jabatan/unit serta pola kategori bidang yang tersedia. Ini inferensi yang dicatat, bukan klaim bahwa sumber sudah memuat 52 skema baru.

## Pemetaan koreksi 13 record

| ID | Nama | Sebelumnya | Sesudah | Dasar |
| --- | --- | --- | --- | --- |
| 36 | TEKNISI MANAJEMEN DATA (DATA MANAGEMENT TECHNICIAN) | Okupasi | DATA MANAGEMENT SYSTEM | Padanan Data Management Staff pada dataset bidang sumber |
| 40 | MANAJER ARSITEKTUR PERUSAHAAN (ENTERPRISE ARCHITECTURE MANAGER) | (kosong) | IT ENTERISE ARCHITECTURE | Kelompok arsitektur enterprise seperti Enterprise Architect sumber |
| 41 | MANAJER REKAYASA PERANGKAT LUNAK (SOFTWARE ENGINEERING MANAGER) | (kosong) | PROGRAMMING DAN PENGEMBANGAN SOFTWARE | Inferensi jabatan mengikuti kelompok bidang sumber |
| 42 | INSINYUR PERANGKAT LUNAK (SOFTWARE ENGINEER) | (kosong) | PROGRAMMING DAN PENGEMBANGAN SOFTWARE | Inferensi jabatan mengikuti kelompok bidang sumber |
| 43 | ANALIS PROGRAM (PROGRAM ANALYST) | (kosong) | PROGRAMMING DAN PENGEMBANGAN SOFTWARE | Inferensi jabatan mengikuti kelompok bidang sumber |
| 44 | PENGEMBANG FRONTEND (FRONTEND DEVELOPER) | (kosong) | PROGRAMMING DAN PENGEMBANGAN SOFTWARE | Inferensi jabatan mengikuti kelompok bidang sumber |
| 45 | SUPERVISOR PEMROGRAM MOBILE (MOBILE PROGRAMMER SUPERVISOR) | (kosong) | PROGRAMMING DAN PENGEMBANGAN SOFTWARE | Inferensi jabatan mengikuti kelompok bidang sumber |
| 46 | ANALIS SISTEM (SYSTEM ANALYST) | (kosong) | PROGRAMMING DAN PENGEMBANGAN SOFTWARE | Inferensi jabatan mengikuti kelompok bidang sumber |
| 47 | PEMIMPIN DATA (CHIEF DATA OFFICER) | (kosong) | DATA MANAGEMENT SYSTEM | Inferensi jabatan mengikuti kelompok bidang sumber |
| 48 | INSINYUR PEMBELAJARAN MESIN (MACHINE LEARNING ENGINEER) | (kosong) | DATA MANAGEMENT SYSTEM | Inferensi jabatan mengikuti kelompok bidang sumber |
| 49 | INSINYUR KECERDASAN ARTIFISIAL (AI ENGINEER) | (kosong) | DATA MANAGEMENT SYSTEM | Inferensi jabatan mengikuti kelompok bidang sumber |
| 50 | SUPERVISOR ILMUWAN DATA (DATA SCIENTIST SUPERVISOR) | (kosong) | DATA MANAGEMENT SYSTEM | Inferensi jabatan mengikuti kelompok bidang sumber |
| 51 | MANAJER KEAMANAN INFORMASI (INFORMATION SECURITY MANAGER) | (kosong) | IT SECURITY AND COMPLIANCE | Inferensi jabatan mengikuti kelompok bidang sumber |

39 skema lainnya sudah memiliki kategori bidang yang masuk akal dan tetap. Seluruh label tujuan sudah ada di inventaris sehingga tidak membuat kategori baru. Label lama “IT ENTERISE ARCHITECTURE” dipakai konsisten sesuai inventaris existing; sumber mentah mengejanya “IT Enterprise Architecture”. Perbaikan ejaan lintas inventaris bukan bagian dari koreksi kategori kosong/Okupasi ini.

Kategori inventaris “Okupasi” yang tidak lagi dipakai dihapus agar tidak dipilih lagi; jenis skema 52 tetap Okupasi. Inventaris kategori menjadi 12. Harga tetap null; dua syarat administrasi seragam tetap; data lainnya dan halaman tidak diubah.

## Pengujian dan penerapan

Manifest baru: `backend/resources/data/lspglobal/scheme-categories-verified-20261029.json`. Command default tetap melarang perubahan kategori existing; hanya pemetaan `category_changes` dengan from/to yang cocok persis boleh mengubahnya. Penghapusan kategori menolak kategori yang masih dipakai. Rollback memulihkan kategori sebelum record skema untuk menjaga foreign key.

Target hasil: 52 skema,0 kategori kosong,0 kategori Okupasi,13 pembaruan kategori,39 unchanged,0 create/delete skema. Backup/journal terpisah dari batch sebelumnya diperlukan. Rollback dilakukan urutan terbaru dahulu: kategori→administrasi→rekonsiliasi lengkap.


### Hasil penerapan

- Apply berhasil: 13 update, 39 unchanged, 0 create/delete. Pengulangan command menghasilkan `Already applied; no changes.`
- Snapshot database dan API setelah apply: 52 skema, 0 kategori kosong, 0 kategori “Okupasi”, 12 kategori inventaris. Perbandingan semua field membuktikan hanya kategori dan timestamp 13 record yang berubah; halaman tetap identik.
- Harga 52 skema tetap belum tersedia dan setiap skema tetap memiliki dua persyaratan administrasi yang disepakati.
- Pint lulus; 43 tes backend lulus (1.428 assertions). Review independen tidak menemukan blocker.
- Backup database: `/var/backups/lsp-global/scheme-categories-verified-20261029/database.sql.gz` (integritas gzip diperiksa). Command sebelumnya disimpan di `ReconcileCertificationSchemes.before.php` pada direktori yang sama.
- Journal: `/var/www/lsp-global-staging/backend/storage/app/private/scheme-reconciliations/scheme-categories-verified-20261029/before.json` dan `after.json`.

Rollback batch terbaru dari direktori backend server:

```sh
php artisan content:reconcile-schemes --manifest=resources/data/lspglobal/scheme-categories-verified-20261029.json --rollback
```

Lakukan rollback kategori ini sebelum rollback batch administrasi atau rekonsiliasi lengkap. Proteksi snapshot menolak rollback jika ada perubahan berikutnya yang belum dibatalkan.

Pemeriksaan browser setelah apply lulus: katalog menampilkan 52 skema, filter tidak memuat Okupasi, detail Teknisi Manajemen Data menampilkan DATA MANAGEMENT SYSTEM, dan tab Persyaratan tetap aktif pertama.

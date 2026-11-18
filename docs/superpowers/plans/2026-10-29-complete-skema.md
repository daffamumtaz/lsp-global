# Rekonsiliasi katalog lengkap Implementation Plan

> **For agentic workers:** superpowers:executing-plans. Pelaksanaan dan penghapusan telah diotorisasi pengguna.

**Goal:** Katalog52 sesuai dokumen lengkap, kategori lama tetap, kategori tambahan diisi, semua harga null, Persyaratan tampil pertama.

**Architecture:** Manifest penuh dengan snapshot baseline, map ID eksplisit, transaksi update/create/delete dan cleanup highlight. Command rekonsiliasi terpisah menjaga batch impor sebelumnya tetap utuh. Backup penuh + journal before/after menyediakan rollback konflik-aware.

**Tech Stack:** Laravel13/Eloquent/MariaDB/Artisan; React19/Next.js16; SSH.

**Spec:** `New Skema.md` serta `docs/audits/2026-10-29-complete-skema.md`.

## Global Constraints

- 52 sumber;40 target lama;12 tambahan;11 delete eksplisit. Semua harga null, semua jenis default Okupasi.
- Kategori existing termasuk null dipertahankan;12 tambahan memakai kategori existing sebagaimana tabel audit, tanpa kategori baru.
- ID/slug lama dipertahankan; hanya highlight31/39 dihapus. Thumbnail lama tetap ada di storage.
- Kolom38/39 ditukar secara semantik; semua52 mempunyai empat alternatif persyaratan dan jumlah unit total452.
- Perubahan UI hanya urutan/keaktifan tab, tanpa mengubah styling atau data unit.

## Review Focus

1. Kandidat nama mirip dipetakan berdasarkan jabatan, bukan overlap kode semata.
2. Baseline drift harus menggagalkan penghapusan dan rollback.
3. Highlight terhapus tidak menyisakan referensi yatim; rollback memulihkannya.
4. Category lama/null dan ID/slug tetap; harga tidak menjadi0.
5. Panel default, urutan DOM, ARIA dan navigasi keyboard konsisten setelah pertukaran tab.

## Tasks

- [x] Simpan manifest `backend/resources/data/lspglobal/complete-schemes-20261029.json` dan metadata foto; validasi52/452/208, map40,create12,delete11 dan kategori terhadap baseline.
- [x] Tulis dan jalankan tes merah `backend/tests/Feature/ReconcileCertificationSchemesTest.php`: preview, apply, kategori/harga, highlight, repeat, rollback, drift dan aset hilang; fixture nyata52.
- [x] Implementasikan `backend/app/Console/Commands/ReconcileCertificationSchemes.php` (`content:reconcile-schemes --manifest=<path> [--apply|--rollback]`) dengan backup journal, cek snapshot penuh, validasi aset, transaksi, cleanup highlight dan rollback data.
- [x] Tambahkan tes browser `frontend/tests/scheme-tabs.browser.mjs`; buktikan default lama gagal. Ubah hanya `frontend/src/components/schemes/scheme-detail-tabs.tsx` untuk Persyaratan pertama/default, unit kedua, dengan ARIA/keyboard konsisten.
- [x] Jalankan tes backend terkait, Pint, tes frontend, lint/TypeScript dan browser; review independen sebelum apply.
- [x] Backup server database/storage pada direktori batch `complete-schemes-20261029`; transfer hanya manifest, command dan aset yang diperlukan; preview seharusnya28 update,12 unchanged,12 create,11 delete jika baseline tetap.
- [x] Apply transaksi; verifikasi52, harga seluruhnya null,40 ID/slug/kategori tetap,11 ID hilang, highlight17/24/28,52 entri cocok manifest dan semua halaman/gambar/search sehat.
- [x] Build frontend dari salinan sumber server, mempertahankan basePath/public-image; tukar build setelah berhasil. Verifikasi browser desktop/mobile default Persyaratan dan tab unit tetap bekerja. Simpan laporan dan instruksi rollback.


## Hasil pelaksanaan

- Katalog lengkap diterapkan: 28 update, 12 unchanged, 12 create, 11 delete. Total akhir **52 skema**, 452 unit, 208 butir persyaratan dasar. Semua harga null dan semua jenis Okupasi.
- 40 ID/slug/kategori lama dipertahankan, termasuk 12 kategori existing yang sudah kosong. 12 tambahan diberi kategori existing sesuai tabel audit; inventaris kategori tetap 13, tidak ada kategori baru.
- Highlight landing dibersihkan dari ID31/39; urutan tersisa17/24/28. Tidak ada file gambar lama yang dihapus. Seluruh 11 URL lama yang dihapus mengembalikan404.
- Kolom sumber System Engineer dan Data Center Manager dipulihkan sesuai makna. Uji sumber penuh memastikan masing-masing9 dan11 unit serta empat alternatif persyaratan.
- Pengguna kemudian meminta dua syarat administrasi untuk SEMUA skema. Dibuat manifest dan journal lanjutan `scheme-administration-20261029`, terpisah dari rekonsiliasi utama: 51 update dan1 unchanged, total52 tetap. Array administrasi tiap skema persis:
  1. KTP (WNI)/ Paspor dan Kartu Izin Tinggal Terbatas (Kitas) (WNA)
  2. Pas Photo 3x4 background merah
- SHA-256 manifest utama: `8acdb343c74153df96de26f54603b313e1f2ef522d18aec75005b4383858adcb`.
- SHA-256 manifest administrasi: `4eef432de26b948834f93614b54bc1ec2f5ae31aa6f373c1a94264762650443f`.
- Kedua batch berulang kembali menunjukkan `Already applied; no changes.`. Semua52 entri cocok dengan manifest utama plus override administrasi, kategori/field existing selain harga dan konten yang diminta tetap. Perubahan halaman hanya penghapusan dua referensi highlight.
- Backup utama di server: `/var/backups/lsp-global/complete-schemes-20261029/database.sql.gz` dan `scheme-images.tar.gz` (hanya gambar skema karena galeri tidak berubah). Backup sebelum syarat administrasi: `/var/backups/lsp-global/scheme-administration-20261029/database.sql.gz`. Semua arsip tervalidasi.
- Journal masing-masing di `backend/storage/app/private/scheme-reconciliations/<batch>/before.json` dan `after.json`. Snapshot/journal impor28/29 sebelumnya tetap utuh.
- UI: Persyaratan menjadi tab pertama dan panel aktif awal; Unit Kompetensi kedua dengan pagination tetap. Indeks tab/panel, ARIA, keyboard Home/End/panah, dan penanda visual tab aktif terverifikasi pada1440px dan390px.
- Frontend dibuild dengan webpack dari salinan sumber server agar basePath/public-image tetap. Build lama tersedia di `/var/www/lsp-global-staging/frontend/.next.before-complete-20261029`; sumber tab lama di backup utama `scheme-detail-tabs.before.tsx`.
- Validasi:41 tes backend terkait lolos1360 assertions;70 tes frontend lolos;1 regresi render tab lolos setelah gagal pada urutan lama; lint dan TypeScript lolos; browser desktop/mobile lolos termasuk warna penanda aktif. Review independen rekonsiliasi, UI, dan batch administrasi tidak menemukan blocker.
- HTTP/API seluruh52: detail, pencarian, gambar200; katalog menghitung52; semua harga belum tersedia; semua administrasi cocok;52 detail menampilkan tab Persyaratan pertama.

## Rollback berurutan

Jalankan dari backend server sebagai deploy. Karena administrasi adalah batch terbaru, pulihkan administrasi lebih dulu, baru rekonsiliasi utama:

```bash
php artisan content:reconcile-schemes --manifest=resources/data/lspglobal/scheme-administration-20261029.json --rollback
php artisan content:reconcile-schemes --manifest=resources/data/lspglobal/complete-schemes-20261029.json --rollback
```

Command menolak bila snapshot sudah berubah sejak apply; periksa perubahan admin sebelum pemulihan. Rollback utama memulihkan51 skema awal, harga/kategori/persyaratan lama serta highlight. Rollback frontend terpisah: stop service, arsipkan `.next` baru, kembalikan `.next.before-complete-20261029` dan sumber tab dari backup, lalu start service. Jangan menghapus backup sebelum pemulihan tervalidasi.


## Pembaruan kategori setelah penerapan

Instruksi terbaru pengguna menggantikan keputusan mempertahankan kategori existing yang kosong. Batch `scheme-categories-verified-20261029` telah mengisi 12 kategori kosong dan mengganti kategori “Okupasi” pada ID 36 dengan DATA MANAGEMENT SYSTEM. Seluruh 52 skema kini berkategori; inventaris “Okupasi” yang tidak terpakai dihapus. Rincian audit sumber, pemetaan, bukti verifikasi, dan rollback terbaru tersedia di [audit kategori](../../audits/2026-10-29-category-verification.md). Rollback wajib dimulai dari batch kategori sebelum administrasi dan rekonsiliasi lengkap.

# Audit struktur organisasi — 5 Oktober 2026

Playwright dengan Chromium 155.0.8059.12, mode headless. Pengujian Filament menggunakan salinan database SQLite dan akun uji; pengujian browser tidak mengubah database utama.

## Hasil

- **14 skenario halaman/ukuran layar lulus:** `/tentang-kami` dan `/tentang-kami/struktur-organisasi`, masing-masing pada lebar 320, 375, 390, 768, 1024, 1440, dan 1920 piksel.
- Semua 12 kotak dan 11 hubungan atasan tampil. Tidak ada kotak bertumpuk, garis melewati kotak, halaman meluber horizontal, atau galat JavaScript. Zoom, geser, sesuaikan bagan, dan perpindahan daftar/bagan berfungsi; daftar memuat seluruh nama.
- **Filament lulus:** pengunjung diarahkan ke login, admin masuk, tambah jabatan dengan beberapa nama, gabungkan jabatan, urutkan nama, edit nama/posisi, simpan ke API, perbarui iframe pratinjau, tolak posisi yang terisi, dan hapus jabatan daun. Formulir ponsel tidak meluber horizontal.
- Verifikasi terakhir: 19 tes organisasi backend (123 assertion) dan seluruh 46 tes frontend lulus. Lint, TypeScript, build produksi, dan `git diff --check` juga lulus.

## Temuan yang diperbaiki

1. Teks bagan terlalu kecil ketika seluruh bagan disesuaikan ke viewport. Tinggi bagan desktop diperbesar; pada lebar sampai 600 piksel, tampilan awal berupa daftar yang dapat dibaca. Pengguna tetap bisa memilih bagan. Pada lebar 1440 piksel, ukuran visual teks terkecil sekitar 12 piksel di halaman publik dan 13,5 piksel di halaman pratinjau.
2. Penolakan posisi yang sudah terisi tidak menampilkan pesan pada formulir Filament. Galat validasi model kini dipetakan ke kolom formulir tindakan yang sedang terbuka; pesan benturan terlihat dan data tidak tersimpan.

Pada tablet, tampilan seluruh bagan masih membutuhkan zoom atau pilihan **Lihat daftar** untuk membaca teks kecil. Ini berlaku pula bila pengguna ponsel secara eksplisit memilih bagan.

## Bukti dan cara menjalankan ulang

- [Hasil halaman publik](/tmp/lsp-organization-audit/public-results.json), [hasil edit Filament](/tmp/lsp-organization-audit/cms-results.json), [hasil anggota dan penghapusan](/tmp/lsp-organization-audit/cms-members-results.json).
- [Bagan desktop](/tmp/lsp-organization-audit/organization-1440-embed.png), [daftar ponsel](/tmp/lsp-organization-audit/organization-mobile-default-list.png), [formulir ponsel](/tmp/lsp-organization-audit/cms-mobile-edit.png), [pratinjau Filament](/tmp/lsp-organization-audit/cms-preview.png).

Dengan backend di port 8000 dan frontend di port 3000, jalankan dari direktori `frontend`:

```sh
PLAYWRIGHT_MODULE=/path/to/playwright/index.mjs node scripts/check-organization.mjs
```

`SITE_ORIGIN`, `API_ORIGIN`, dan `AUDIT_ARTIFACTS` dapat mengganti alamat server serta direktori hasil. Berkas bukti di `/tmp` bersifat sementara.

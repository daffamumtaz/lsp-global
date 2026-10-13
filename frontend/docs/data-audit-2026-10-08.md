# Audit data publik — 8 Oktober 2026

Data dibandingkan dengan [lspglobal.id](https://lspglobal.id), termasuk bundle publik terkini. Jumlah sumber: 45 TUK, 82 asesor, dan 39 skema. Nama, kode TUK, kota/provinsi, alamat, jenis TUK, atribut skema, persyaratan, dan unit dibandingkan dengan snapshot dan database lokal. Konten sumber masih sama dengan snapshot sebelumnya. Koreksi berikut memakai registri BNSP untuk menyelesaikan kesalahan pada sumber LSP.

## Koreksi yang diterapkan

- **Anang Kurnia:** sumber LSP memakai MET yang sama dengan Bowo Witanto. [Registri asesor BNSP](https://bnsp.go.id/lsp/4102/asesors) mencatat Anang sebagai `MET.000.008457 2023`. Nomor ini diterapkan. Bowo memperoleh kembali `MET.000.000128 2023` dari sumber LSP; catatan konflik yang sudah terselesaikan dihapus. Database kini memiliki 82 nomor MET unik. Masa berlaku tidak ditebak.
- **Chief Data Scientist:** sumber LSP memiliki delapan baris dengan kode `J.62DMI00.020.1` berulang dan beberapa judul Data Mining. [Unit resmi BNSP](https://bnsp.go.id/lsp/unit-skema/73242) memiliki tujuh unit. Duplikat dihapus dan judul disesuaikan dengan registri. Total seluruh unit sekarang 359. Deskripsi yang sudah ada tetap dipertahankan.
- Koreksi disimpan di `assessor-enrichment.json` dan `scheme-enrichment.json`, sehingga import berikutnya juga menerapkan hasil verifikasi. Database lokal diperbarui hanya pada atribut tersebut; tidak menjalankan import penuh yang bisa menimpa edit admin.

## Kekurangan TUK yang belum dapat diverifikasi

| TUK | Kode | Status lokal | Data belum tersedia |
| --- | --- | --- | --- |
| TUK Saintisindo Banten | TUK-TIKGLOBAL-002 | Aktif | Logo, Situs, Latitude, Longitude |
| TUK Hijau Teknologi Digital | TUK-006/2023 | Aktif | Latitude, Longitude |
| SMK Al-Quran Zaenuddin Tegal | TUK-001/2022 | Nonaktif | Logo, Situs |
| Arung Dunia Digital Cimahi | TUK-007/2023 | Aktif | Logo, Situs, Latitude, Longitude |
| Hanipati Taliwang NTB | TUK-009/2023 | Aktif | Logo, Situs |
| Pinter Kerja | TUK-020/2024 | Aktif | Logo, Situs, Latitude, Longitude |
| Ilmu Komputer | TUK-024/2024 | Aktif | Logo, Situs, Latitude, Longitude |
| HARRIS Hotel | TUK-029/2024 | Aktif | Alamat, Latitude, Longitude |
| Artavista | TUK-050/2026 | Aktif | Latitude, Longitude |

Dari 44 TUK aktif, lima belum memiliki logo/situs, tujuh belum memiliki koordinat, dan satu belum memiliki alamat. Sumber LSP tidak menerbitkan logo, tautan situs, atau koordinat individual TUK; alamat HARRIS juga kosong dan cabangnya tidak dijelaskan. Data tersebut dibiarkan kosong. Logo yang belum tersedia tetap disembunyikan dari halaman Seluruh Mitra. Semua 39 berkas logo yang dirujuk database tersedia.

SMK Al-Quran Zaenuddin tetap tercatat di database sesuai sumber LSP, tetapi status nonaktif sebelumnya dipertahankan. [Registri LSP BNSP](https://bnsp.go.id/lsp/teknologi-informasi-dan-komunikasi-global) memuat 44 TUK dan tidak memasukkan kode tersebut. Perbedaan ini tidak dijadikan alasan untuk menghapus record.

## Tautan dan media

Semua 39 tautan TUK berlogo diperiksa. Sebagian situs resmi menolak akses otomatis (403/TLS); hal tersebut bukan bukti identitas tautan salah. MySantika Mega City masih terindeks di sumber resmi tetapi mengembalikan 404 saat pemeriksaan langsung. Grand Rocky mengembalikan 500; domain alternatifnya hanya mengarahkan kembali ke situs tersebut. Belum ditemukan alamat pengganti yang terverifikasi, sehingga tautan tidak diganti dengan hasil tebakan. Hasil per tautan disimpan di `audit-2026-10-08.json`.

Seluruh 39 skema memiliki gambar berbeda dan berkas tersedia, kategori, level KKNI, jenis, harga, metode, unit, serta persyaratan. Foto lokal Pexels berukuran 1920 × 1080; sumber dan lisensi tercatat dalam `scheme-photos.json`.

## Batas audit

Terdapat perbedaan jumlah unit dengan tabel BNSP untuk Auditor Ahli Teknologi Informasi (LSP: 6, BNSP: 9) dan Manager Audit Teknologi Informasi (LSP: 9, BNSP: 8). Data tetap mengikuti sumber utama lspglobal.id karena perbedaan versi skema belum terverifikasi. Kasus Chief Data Scientist berbeda: sumber memiliki duplikat kode yang dapat dibuktikan langsung.

BNSP menerbitkan 80 baris asesor, termasuk satu placeholder. Sumber LSP menerbitkan 82 asesor. Record LSP tidak dihapus hanya karena tidak ditemukan pada registri pembanding. Nomor MET dan masa berlaku yang tidak punya bukti pembanding tidak diubah. Audit ini diterapkan pada workspace dan database lokal; belum dideploy.

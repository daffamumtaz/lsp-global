# Audit mitra dan lokasi TUK — 6 Oktober 2026

## Daftar publik

Daftar rinci [BNSP](https://bnsp.go.id/lsp/teknologi-informasi-dan-komunikasi-global) melalui [endpoint TUK](https://bnsp.go.id/lsp/4102/tuks) berisi 44 kode TUK. Seluruh kode tersebut cocok dengan snapshot proyek. Alamat juga cocok; perbedaan teks Undip hanya encoding tanda pisah pada sumber BNSP.

Snapshot LSP memiliki satu entri tambahan: SMK Al-Quran Zaenuddin Tegal (`TUK-001/2022`). Record ini tetap disimpan, dengan `is_active=false`, sehingga daftar publik menampilkan 44 mitra. Salinan pembanding ada di `backend/resources/data/lspglobal/bnsp-tuks.json`.

## Logo

39 dari 44 mitra memiliki logo yang sudah dicocokkan. Aset disimpan lokal dan sumber setiap logo dicatat di `backend/resources/data/lspglobal/tuk-enrichment.json`. Logo HARRIS dan Santika menggunakan merek hotel; Distrik Navigasi menggunakan identitas institusi induk Kementerian Perhubungan. Logo Ayola diekstrak dari Gambar 4.1 halaman 51 pada dokumen [repository UIN Suska](https://repository.uin-suska.ac.id/41087/1/GABUNGAN%20KECUALI%20BAB%20V.pdf), yang menyebut sumber website hotel.

Lima logo tetap kosong setelah pencarian:

| Mitra | Hasil pencarian |
| --- | --- |
| Saintisindo Banten | Domain Sainstisindo tidak dapat diakses; hasil lain belum memastikan identitas lembaga di Serang. |
| Arung Dunia Digital Cimahi | Nama lengkap, alamat Cihanjuang, dan domain yang sesuai belum menghasilkan logo resmi. |
| Hanipati Taliwang NTB | Lembaga/restoran ditemukan, tetapi aset logo resmi yang sesuai belum tersedia. |
| Pinter Kerja | Pencarian nama dan domain belum menghasilkan identitas visual yang dapat dipastikan. |
| Ilmu Komputer | Logo IlmuKomputer.Com tersedia, tetapi kaitannya dengan TUK pada alamat Bogor belum terbukti. |

Semua 44 entri tetap tampil di All Mitra. Lima slot tersebut memiliki area logo kosong dan nama yang terlihat; tidak memakai logo buatan. Beberapa logo dari profil sosial/dokumen beresolusi rendah; sumber resmi dengan resolusi lebih tinggi dapat menggantikannya nanti.

## Peta dan Google Maps

37 TUK aktif memiliki koordinat berpasangan dan link Google Maps ke lokasi yang sama. Database juga menyimpan satu lokasi pada entri yang dinonaktifkan, sehingga jumlah koordinat tersimpan tetap 38.

Tujuh lokasi tetap kosong: Saintisindo, Hijau Teknologi Digital, Arung Dunia Digital, Pinter Kerja, Ilmu Komputer, HARRIS, dan Artavista. HARRIS tidak diberi pin karena cabang/alamat belum diketahui, sesuai permintaan pengguna.

Enam pin mengacu pada kampus institusi: Unisba, ULM, PNJ, Unram, Undip, dan UPN Veteran Jakarta. Pin Hanipati berasal dari Plus Code yang dipublikasikan. Ketelitian dan sumbernya dicatat per entri; pin tersebut bukan verifikasi ruangan ujian.

Peta pada Next.js menggunakan Leaflet dan latar OpenStreetMap. Tautan pada popup membuka Google Maps, dengan koordinat dan place ID bila tersedia.

## Perilaku UX yang diterapkan

- All Mitra berupa grid logo, tanpa tabel, formulir pencarian, atau peta.
- Logo mempertahankan warna dan proporsinya. Logo putih diberi latar gelap agar terbaca.
- Nama tampil saat hover; link website memiliki outline saat fokus keyboard. Pada perangkat sentuh nama terlihat tanpa hover. Logo tanpa website tetap menjadi elemen statis.
- Carousel beranda memiliki panah kiri/kanan, geser native, indikator tombol nonaktif di ujung, dan menghormati reduced motion. Tidak ada autoplay.
- Titik peta menampilkan nama/alamat dan link Maps ketika dipilih. Kelompok pin memilih daftar TUK yang bersangkutan.
- Tombol panah, pin, zoom, dan penutup popup memiliki target minimal 44 px. Pin dapat dipilih dengan Enter/Space; Escape menutup popup. Popup tetap berada di dalam peta pada layar kecil.
- TUK tanpa koordinat tetap tersedia pada daftar, tanpa pin atau link Maps rekaan.

## Verifikasi

- PHPUnit impor: 2 tes, 813 assertion; termasuk kecocokan 44 kode BNSP, logo lokal, koordinat, URL, dan impor berulang.
- TypeScript, ESLint pada komponen terkait, serta Pint pada berkas PHP terkait lolos.
- Empat tes utilitas mitra/TUK lolos.
- Browser dengan data database aktual pada lebar 320, 390, dan 1440 px: 44 slot, 39 gambar dapat dimuat, carousel, 37 lokasi, popup, link, keyboard, touch, dan tanpa overflow horizontal atau error JavaScript.
- Pemeriksaan browser memblokir tile eksternal untuk menguji pin dan UI secara konsisten; bukan verifikasi ketersediaan layanan tile eksternal. Tidak dilakukan deployment.

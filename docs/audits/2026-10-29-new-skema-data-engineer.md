# Audit perbedaan skema — checkpoint Data Engineer, 29 Oktober 2026

Audit bersifat baca saja. Tidak ada perubahan database, kode aplikasi, gambar, atau deployment pada tahap ini.

## Sumber dan cakupan

- Sumber terbaru: `New Skema.md`, metadata modified 2026-10-29.
- SHA-256 sumber: `b7d638fdd45a4d3efe68edab9df9602da98a9d8faa5f670786b147e2d83f0cee`.
- Pembanding batch kemarin: `backend/resources/data/lspglobal/new-schemes-20261028.json`.
- SSH alias `neuralia` berhasil; hostname `VM-29-5-ubuntu`, user `ubuntu`.
- Baseline langsung dari model aplikasi di `/var/www/lsp-global-staging/backend`: 47 skema. Pengambilan snapshot mencakup seluruh field skema dan halaman, tanpa menampilkan konfigurasi rahasia.
- Checkpoint pengguna ditafsirkan sebagai awal batch, **termasuk INSINYUR DATA (DATA ENGINEER)**: entri 16–27, baris 25–36 sumber. Bukan hanya skema Data Engineer.

## Ringkasan

Dokumen kini berisi 27 skema, bertambah 12 dari 15 skema kemarin. Nama, jenis yang terisi, unit, dan persyaratan 15 skema sebelumnya tidak berubah setelah normalisasi `<br>` dan whitespace. Atribut hasil impor kemarin juga masih cocok dengan data server.

Rencana batch checkpoint: **8 pembaruan + 4 penambahan**, sehingga total 47 → **51**. Delapan pembaruan mempertahankan ID dan slug lama. Sebanyak 39 record yang sudah ada tidak disentuh: 15 hasil kemarin dan 24 skema lain. Tidak ada skema yang dihapus atau dinonaktifkan karena tidak tercantum pada dokumen baru.

## Pemetaan 12 skema

| No. sumber | Skema terbaru | Aksi | ID lama | Unit lama → baru | Persyaratan lama → baru |
| --- | --- | --- | --- | --- | --- |
| 16 | INSINYUR DATA (DATA ENGINEER) | Perbarui | 25 | 10 → 11 | 3 → 4 |
| 17 | INSINYUR PEMBELAJARAN MESIN (MACHINE LEARNING ENGINEER) | Tambah | — | — → 8 | — → 4 |
| 18 | INSINYUR KECERDASAN ARTIFISIAL (AI ENGINEER) | Tambah | — | — → 8 | — → 4 |
| 19 | SUPERVISOR ILMUWAN DATA (DATA SCIENTIST SUPERVISOR) | Tambah | — | — → 9 | — → 4 |
| 20 | ANALIS DATA (DATA ANALYST) | Perbarui | 17 | 7 → 8 | 3 → 4 |
| 21 | ADMINISTRATOR BASIS DATA (DATABASE ADMINISTRATOR) | Perbarui | 23 | 7 → 7 | 3 → 4 |
| 22 | ANALIS INTELIJEN BISNIS (BUSINESS INTELLIGENCE ANALYST) | Perbarui | 22 | 7 → 7 | 4 → 4 |
| 23 | ILMUWAN DATA MUDA (ASSOCIATE DATA SCIENTIST) | Perbarui | 21 | 9 → 4 | 3 → 4 |
| 24 | ANALIS DATA MUDA (ASSOCIATE DATA ANALYST) | Perbarui | 19 | 5 → 6 | 3 → 4 |
| 25 | INSINYUR DATA MUDA (ASSOCIATE DATA ENGINEER) | Perbarui | 20 | 7 → 4 | 3 → 4 |
| 26 | TEKNISI MANAJEMEN DATA (DATA MANAGEMENT TECHNICIAN) | Perbarui | 36 | 11 → 11 | 3 → 4 |
| 27 | MANAJER KEAMANAN INFORMASI (INFORMATION SECURITY MANAGER) | Tambah | — | — → 5 | — → 4 |

Semua 12 skema checkpoint mempunyai kolom Jenis Skema kosong. Di seluruh dokumen ada 14 kolom jenis kosong; dua lainnya adalah entri 12 dan 13 yang sudah diputuskan sebagai Okupasi kemarin. Untuk delapan pembaruan, pertahankan `type=Okupasi` yang sudah ada. Untuk empat tambahan, usulan `Okupasi` mengikuti pola okupasi/jabatan dan keputusan batch kemarin; ini **inferensi**, bukan nilai tertulis dalam sumber.

## Dasar pemetaan nama

- ID 25: Insinyur Data adalah nama yang sama.
- ID 17, 23, 22: label Indonesia ditambahkan pada Data Analyst, Database Administrator, dan Business Intelligence Analyst; nama Inggrisnya sama.
- ID 21 dan 20: Junior/Associate Data Scientist serta Junior/Associate Data Engineer menjadi Ilmuwan Data Muda dan Insinyur Data Muda (Associate); jabatan Associate tetap sama.
- ID 19: Associate Data Analyst menjadi Analis Data Muda (Associate Data Analyst).
- ID 36: Data Management Staff menjadi Teknisi Manajemen Data (Data Management Technician). Ini kandidat nama mirip, bukan terjemahan persis: delapan kode identik, tiga lainnya versi lebih baru dari kode yang sama. Pemetaan mengacu pada arahan pengguna untuk mengganti skema lama yang sama/mirip.
- Supervisor Ilmuwan Data ditambahkan terpisah. Kesembilan kode unitnya sama dengan unit ID 21 yang lama, tetapi dokumen juga memuat Associate Data Scientist tersendiri dengan empat unit. Jangan mengambil ID 21 untuk Supervisor lalu membuat duplikat Associate.
- Manajer Keamanan Informasi ditambahkan terpisah dari ID 14 Chief Cyber Security Officer: jabatan berbeda dan tidak ada kode unit yang sama. Jangan mengganti hanya karena sama-sama bidang keamanan/manajerial.
- Machine Learning Engineer dan AI Engineer adalah dua skema baru terpisah. Kemiripan beberapa kode dengan Data Scientist tidak cukup untuk mengganti skema tersebut.

## Perbedaan delapan record lama

Kolom unit akan mengikuti kode, judul, dan urutan pada dokumen. Daftar berikut memakai perbandingan literal kode agar perubahan versi dan salah spasi tetap terlihat. Perubahan kapitalisasi judul dicatat terpisah dari perubahan isi. Sumber tidak menyediakan deskripsi unit; replacement array mengikuti batch kemarin (`code`, `title`) dan tidak membawa deskripsi lama yang mungkin tidak sesuai.

### Entri 16 / ID 25: INSINYUR DATA (DATA ENGINEER)

Nama lama: **INSINYUR DATA (DATA ENGINEER)**. Slug dipertahankan: `insinyur-data-data-engineer`.

- Unit dihapus: tidak ada.
- Unit ditambahkan: `J.62PDP00.011.1` — Menerapkan Program Kerja Pelindungan Data Pribadi.
- Judul unit berubah:
  - `J.62DMS00.001.1`: “Memahami Kebutuhan Bisnis Akan Data” → “Mengidentifikasi kebutuhan pengelolaan data untuk proses bisnis”.
  - `J.62DMS00.012.1`: “Mengelola Kualitas Data” → “Mengelola kualitas data”.
- Persyaratan dasar: 3 → 4 butir; teks diganti dari empat alternatif a–d sumber.
- Urutan kode hasil yang diharapkan: `J.62DMS00.001.1`, `J.62DMS00.002.1`, `J.62DMS00.003.1`, `J.62DMS00.004.1`, `J.62DMS00.005.1`, `J.62DMS00.007.1`, `J.62DMS00.008.1`, `J.62DMS00.009.1`, `J.62DMS00.012.1`, `J.62DMS00.014.1`, `J.62PDP00.011.1`.

### Entri 20 / ID 17: ANALIS DATA (DATA ANALYST)

Nama lama: **DATA ANALYST**. Slug dipertahankan: `data-analyst`.

- Unit dihapus: `M.70BDA00.013.1` — Menyusun Laporan Hasil Analisis.
- Unit ditambahkan: `J.62DMS00.004.1` — Merencanakan Integrasi Data; `J.62DMI00.008.1` — Membersihkan Data.
- Judul unit berubah:
  - `J.62DMS00.001.1`: “Mengidentifikasi Kebutuhan Pengelolaan Data untuk Proses Bisnis” → “Mengidentifikasi kebutuhan pengelolaan data untuk proses bisnis”.
- Persyaratan dasar: 3 → 4 butir; teks diganti dari empat alternatif a–d sumber.
- Urutan kode hasil yang diharapkan: `J.62DMS00.001.1`, `J.62DMS00.004.1`, `J.62DMI00.004.1`, `J.62DMI00.005.1`, `J.62DMI00.006.1`, `J.62DMI00.007.1`, `J.62DMI00.008.1`, `J.62DMS00.015.1`.

### Entri 21 / ID 23: ADMINISTRATOR BASIS DATA (DATABASE ADMINISTRATOR)

Nama lama: **DATABASE ADMINISTRATOR**. Slug dipertahankan: `database-administrator`.

- Unit dihapus: `J.62DMS00.011.1` — Membuat integrasi data.
- Unit ditambahkan: `J.62DMS00.017.1` — Mengadministrasi Basis Data.
- Judul unit berubah:
  - `J.62DMS00.006.1`: “Mendesain basis data” → “Merancang Basis Data”.
  - `J.62DMS00.010.1`: “Membuat basis data” → “Membuat Basis Data”.
  - `J.620100.021.02`: “Menerapkan akses basis data” → “Menerapkan Akses Basis Data”.
- Persyaratan dasar: 3 → 4 butir; teks diganti dari empat alternatif a–d sumber.
- Urutan kode hasil yang diharapkan: `J.62DMS00.006.1`, `J.62DMS00.010.1`, `J.62DMS00.012.1`, `J.62DMS00.016.1`, `J.620100.020.02`, `J.620100.021.02`, `J.62DMS00.017.1`.

### Entri 22 / ID 22: ANALIS INTELIJEN BISNIS (BUSINESS INTELLIGENCE ANALYST)

Nama lama: **BUSINESS INTELLIGENCE ANALYST**. Slug dipertahankan: `business-intelligence-analyst`.

- Unit dihapus: `J.62DMI00.007. 1` — Menentukan Objek Data.
- Unit ditambahkan: `J.62DMI00.007.1` — Menentukan Objek Data.
- Judul untuk kode yang tetap: tidak berubah secara literal.
- Persyaratan dasar: 4 → 4 butir; teks diganti dari empat alternatif a–d sumber.
- Urutan kode hasil yang diharapkan: `J.62DMI00.004.1`, `J.62DMI00.005.1`, `J.62DMI00.006.1`, `J.62DMI00.007.1`, `J.62DMI00.009.1`, `J.62DMI00.011.1`, `J.62DMS00.015.1`.

### Entri 23 / ID 21: ILMUWAN DATA MUDA (ASSOCIATE DATA SCIENTIST)

Nama lama: **JUNIOR/ASSOCIATE DATA SCIENTIST**. Slug dipertahankan: `juniorassociate-data-scientist`.

- Unit dihapus: `J.62DMI00.006.1` — Memvalidasi Data; `J.62DMI00.007.1` — Menentukan Objek Data; `J.62DMI00.009.1` — Mengkonstruksi Data; `J.62DMI00.013.1` — Membangun Model; `J.62DMI00.014.1` — Mengevaluasi Hasil Pemodelan.
- Unit ditambahkan: tidak ada.
- Judul unit berubah:
  - `J.62DMI00.010.1`: “Menentukan Label data” → “Menentukan Label Data”.
- Persyaratan dasar: 3 → 4 butir; teks diganti dari empat alternatif a–d sumber.
- Urutan kode hasil yang diharapkan: `J.62DMI00.004.1`, `J.62DMI00.005.1`, `J.62DMI00.008.1`, `J.62DMI00.010.1`.

### Entri 24 / ID 19: ANALIS DATA MUDA (ASSOCIATE DATA ANALYST)

Nama lama: **ASSOCIATE DATA ANALYST**. Slug dipertahankan: `associate-data-analyst`.

- Unit dihapus: `J.62DMI00.006.1` — Memvalidasi Data; `J.620100.021.02` — Menerapkan Akses Basis Data.
- Unit ditambahkan: `J.62DMI00.008.1` — Membersihkan Data; `J.620100.020.02` — Menggunakan SQL; `J.62DMS00.019.1` — Menggunakan Data.
- Judul untuk kode yang tetap: tidak berubah secara literal.
- Persyaratan dasar: 3 → 4 butir; teks diganti dari empat alternatif a–d sumber.
- Urutan kode hasil yang diharapkan: `J.62DMI00.004.1`, `J.62DMI00.005.1`, `J.62DMI00.007.1`, `J.62DMI00.008.1`, `J.620100.020.02`, `J.62DMS00.019.1`.

### Entri 25 / ID 20: INSINYUR DATA MUDA (ASSOCIATE DATA ENGINEER)

Nama lama: **JUNIOR/ASSOCIATE DATA ENGINEER**. Slug dipertahankan: `juniorassociate-data-engineer`.

- Unit dihapus: `J.62DMS00.006.1` — Merancang Basis Data; `J.62DMS00.008.1` — Mengelola Reference and Master Data; `J.62DMS00.009.1` — Mengelola Metadata.
- Unit ditambahkan: tidak ada.
- Judul unit berubah:
  - `J.62DMS00.010.1`: “Membuat Basis Data” → “Membuat basis data”.
  - `J.62DMS00.011.1`: “Membuat Integrasi Data” → “Membuat integrasi data”.
- Persyaratan dasar: 3 → 4 butir; teks diganti dari empat alternatif a–d sumber.
- Urutan kode hasil yang diharapkan: `J.620100.020.02`, `J.62DMS00.010.1`, `J.62DMS00.011.1`, `J.62DMS00.019.1`.

### Entri 26 / ID 36: TEKNISI MANAJEMEN DATA (DATA MANAGEMENT TECHNICIAN)

Nama lama: **DATA MANAGEMENT STAFF**. Slug dipertahankan: `data-management-staff`.

- Unit dihapus: `J.63OPR00.010.1` — Menggunakan aplikasi media sosial; `J.63OPR00.014.1` — Melakukan pemasukan data; `J.63OPR00.016.1` — Mengidentifikasi aspek keamanan informasi pengguna.
- Unit ditambahkan: `J.63OPR00.010.2` — Menggunakan Aplikasi Media Sosial; `J.63OPR00.014.2` — Melakukan Pemasukan Data; `J.63OPR00.016.2` — Mengidentifikasi Aspek Keamanan Informasi Pengguna.
- Judul unit berubah:
  - `J.63OPR00.001.2`: “Menggunakan perangkat komputer” → “Menggunakan Perangkat Komputer”.
  - `J.63OPR00.002.2`: “Menggunakan sistem operasi” → “Menggunakan Sistem Operasi”.
  - `J.63OPR00.003.2`: “Menggunakan peralatan peripheral” → “Menggunakan Peralatan Peripheral”.
  - `J.63OPR00.006.2`: “Menggunakan perangkat lunak presentasi - tingkat dasar” → “Menggunakan Perangkat Lunak Presentasi Tingkat Dasar”.
  - `J.63OPR00.007.2`: “Menggunakan penelusur situs web (web browser)” → “Menggunakan Penelusur Situs Web (Web Browser)”.
  - `J.63OPR00.008.2`: “Menggunakan perangkat lunak pengakses surat elektronik (e-mail client)” → “Menggunakan Perangkat Lunak Pengakses Surat Elektronik (E-mail Client)”.
  - `J.63OPR00.011.2`: “Menggunakan perangkat lunak pengolah kata – tingkat lanjut” → “Menggunakan Perangkat Lunak Pengolah Kata Tingkat Lanjut”.
  - `J.63OPR00.012.2`: “Menggunakan perangkat lunak lembar kerja – tingkat lanjut” → “Menggunakan Perangkat Lunak Lembar Kerja Tingkat Lanjut”.
- Persyaratan dasar: 3 → 4 butir; teks diganti dari empat alternatif a–d sumber.
- Urutan kode hasil yang diharapkan: `J.63OPR00.001.2`, `J.63OPR00.002.2`, `J.63OPR00.003.2`, `J.63OPR00.006.2`, `J.63OPR00.007.2`, `J.63OPR00.008.2`, `J.63OPR00.010.2`, `J.63OPR00.011.2`, `J.63OPR00.012.2`, `J.63OPR00.014.2`, `J.63OPR00.016.2`.

## Persyaratan dasar batch checkpoint

Seluruh 12 skema memuat empat alternatif (a/b/c/d), bukan empat kewajiban kumulatif. Kata “atau” harus tetap ada. Bagian a mencakup pendidikan yang sesuai, mata kuliah/unit relevan, serta magang atau minimal dua proyek tervalidasi. Bagian b pelatihan berbasis kompetensi, bagian c jabatan/tugas relevan, bagian d pengalaman minimal enam bulan dalam dua tahun terakhir.

| Kelompok | Skema | Pendidikan pada jalur a |
| --- | --- | --- |
| Magister/Magister Terapan | Data Engineer, Machine Learning Engineer, AI Engineer, Information Security Manager | Program Magister/Magister Terapan pada bidang TIK |
| Diploma/Strata 1 | Supervisor Data Scientist, Data Analyst, Database Administrator, Business Intelligence Analyst, tiga Associate, Data Management Technician | Diploma/Strata 1 bidang TIK |

Data Management Technician secara eksplisit meminta fotokopi dokumen bukti pada tiap jalur. Jangan menggantinya dengan versi kalimat umum atau menyalin persyaratan skema lain. Nama jurusan/area fungsi dan bentuk bukti harus disalin lengkap dari masing-masing baris sumber.

## Temuan kualitas dan hal yang perlu dijaga

1. **Jenis kosong pada semua 12 entri checkpoint.** Pertahankan jenis lama; empat tambahan diusulkan Okupasi dengan keputusan yang dicatat pada manifest. Jangan menyatakan bahwa nilai ini tertulis di dokumen.
2. **Kesalahan spasi pada kode lama BI Analyst.** ID 22 menyimpan `J.62DMI00.007. 1`; dokumen terbaru `J.62DMI00.007.1`. Secara literal ini remove/add satu kode; secara makna koreksi format, jumlah tetap tujuh.
3. **Versi unit Data Management Technician berubah.** `J.63OPR00.010.1`, `.014.1`, `.016.1` masing-masing menjadi `.010.2`, `.014.2`, `.016.2`; bukan sekadar perubahan judul.
4. **Satu kode punya judul sumber yang berbeda antar-skema.** `J.62PDP00.011.1` pada Machine Learning Engineer tertulis “Menerapkan Program Kerja Perlindungan DataPribadi”; Data Engineer/AI Engineer memakai “Menerapkan Program Kerja Pelindungan Data Pribadi”. `J.62DMI00.021.1` pada ML tertulis “Melakukan Laporan Akhir Proyek Data Science”, sedangkan AI/Lead Data Scientist memakai “Membuat Laporan Akhir Proyek Data Science”. Jangan melakukan koreksi substantif otomatis; salin per skema dan catat sebagai teks sumber yang perlu klarifikasi bila ingin diseragamkan.
5. **Tanda baca sumber Data Management Technician:** akhir jalur c tertulis `atau:`. Pertahankan dahulu bila sumber belum direvisi; rapikan hanya whitespace dan `<br>`.
6. **Supervisor vs Associate mempunyai tumpang tindih unit.** Pemetaan harus eksplisit menurut jabatan, bukan memilih target berdasar jumlah unit yang sama.
7. **Field yang tidak disediakan sumber:** kategori, level KKNI, harga, metode, link pendaftaran, persyaratan administrasi, thumbnail. Pertahankan pada delapan update. Untuk empat tambahan, field fakta tetap null/array kosong sesuai model, thumbnail dipilih tersendiri. Jangan menebak level/biaya dari syarat pendidikan.
8. **Impor kemarin harus tetap dapat diaudit.** Gunakan batch/journal/manifest baru bertanggal 29 Oktober; jangan mengganti file manifest, receipt, atau backup 28 Oktober. Default command masih menunjuk manifest 28 Oktober: eksekusi berikutnya wajib memakai `--manifest` eksplisit.

## Gambar yang perlu disiapkan saat pelaksanaan

Delapan update dapat memakai gambar lama jika masih sesuai jabatan; audit aset sebelum mengganti. Untuk empat tambahan: ML Engineer (pemodelan/pipeline ML), AI Engineer (pengembangan solusi AI), Data Scientist Supervisor (tim analisis/pengawasan proyek data), Information Security Manager (tata kelola/kebijakan keamanan informasi). Cari foto berlisensi jelas, simpan lokal dengan hash dan metadata sumber. Pencarian/pengunduhan gambar belum dilakukan pada tahap audit.

## Validasi audit

- Terbaca 27 baris skema dengan empat kolom; 15 baris awal cocok dengan manifest kemarin.
- Checkpoint mencakup 12 skema, **88 unit**, 48 butir persyaratan dasar.
- Nomor unit berurutan dan tidak ada kode duplikat di dalam satu skema checkpoint.
- Seluruh butir persyaratan checkpoint di bawah batas model 2.000 karakter (maksimum ditemukan 603 karakter setelah normalisasi).
- Setiap calon target ID ada pada database langsung dan dipakai tepat sekali dalam pemetaan.
- Audit ini membandingkan sumber pengguna dengan data aplikasi; tidak mengklaim memverifikasi isi SKKNI atau dokumen lisensi skema di luar sumber.


## Tindak lanjut audit

Setelah audit ini, pengguna mengotorisasi implementasi dan menetapkan jenis kosong sebagai **Okupasi**. Batch29 sudah diterapkan:8 pembaruan +4 penambahan, total51. Detail hasil, pengujian, verifikasi, dan backup ada di `docs/superpowers/plans/2026-10-29-new-skema-data-engineer.md`. Pernyataan baca-saja di awal dokumen menjelaskan tahap audit sebelum otorisasi, bukan status database setelah implementasi.

# Audit gap sistem dan UX LSP TIK Global

Tanggal audit: 1 Oktober 2026. Sasaran audit adalah membantu calon peserta menemukan informasi yang dapat dipercaya dan membantu admin mengelola konten dengan jelas.

Sistem saat ini merupakan website informasi dengan CMS. Perbaikan paling mendesak adalah menyamakan sumber skema Beranda dengan katalog, membuat aktivitas diagram dapat diklik, dan membedakan data kosong dari gangguan API. Pengembangan transaksi peserta merupakan keputusan produk tersendiri.

## Metode dan batas pemeriksaan

Audit menelusuri halaman, komponen, loader frontend, route API, model, form admin, serta tes yang tersedia. Pemeriksaan browser dilakukan terhadap layanan lokal dengan data yang ada, tanpa mengubah database, membuat akun, atau mengirim pesan.

| Pemeriksaan browser | Hasil pada sesi ini |
| --- | --- |
| Beranda, Informasi, katalog, dan Kontak pada 375/768/1440 px | Satu H1 per halaman; `scrollWidth` halaman tidak melebihi lebar viewport pada 12 kombinasi ini. Ini bukan bukti seluruh elemen sudah ideal. |
| Pencarian header dengan kata “Junior” | Menuju `/skema-sertifikasi?q=Junior`; reset menghapus filter. Katalog CMS tetap kosong. |
| Tautan skema `junior-coder` yang ditampilkan Beranda | Halaman detail mengembalikan HTTP 404. |
| Diagram Informasi | 21 kartu dan 21 aktivitas pada alternatif daftar, termasuk pada 375 px. |
| Keyboard diagram | Fokus ke kartu pertama + Enter membuka detail; Escape menutup detail dan mengembalikan fokus ke kartu. |
| Klik kartu diagram | Gagal: pane React Flow menerima pointer. Computed style tombol dan node adalah `pointer-events: none`. |
| Menu mobile | Tombol Menu membuka navigasi; Escape menutupnya. |
| Kontak | Penjelasan bahwa pesan hanya menjadi draf terlihat; tidak dilakukan pengiriman. |
| API direktori lokal | Respons sukses memuat 1 TUK, 0 asesor, 0 skema, 0 kegiatan galeri. Angka ini adalah kondisi database lokal, bukan data lembaga sebenarnya. |

Temuan admin, simulasi gangguan API pada UI, detail skema dengan data aktif, dan galeri berisi media dinilai dari kode/tes, bukan pengujian browser penuh pada sesi ini. Rasio kontras, zoom 200%, seluruh interaksi sentuh, performa produksi, dan kepatuhan aksesibilitas menyeluruh belum diverifikasi. Screenshot/log sementara tidak dijadikan dokumentasi permanen karena hasil dapat dibuat ulang dan bergantung pada data lokal.

## Kemampuan sistem sekarang

| Area | Sudah tersedia | Batas saat ini |
| --- | --- | --- |
| Website informasi | Beranda, Tentang Kami, katalog/detail skema, Informasi, galeri, Mitra, Kontak | Sebagian sumber konten masih hardcoded atau memakai fallback contoh. |
| Direktori | CRUD TUK/asesor dan penyaringan data aktif | Tidak mengelola reservasi tempat uji atau penugasan asesor kepada peserta. |
| Skema | Metode, level, unit kompetensi, persyaratan, biaya, URL pendaftaran | CTA meneruskan ke kanal eksternal; tidak membuat record pendaftaran peserta. |
| Alur asesmen | Tahap, pelaksana, aktivitas, percabangan, revisi, preview signed URL | Definisi prosedur umum; bukan status asesmen individual. |
| Kontak | Validasi isian dan persiapan draf email/WhatsApp | Belum ada POST penerimaan pesan dari form website ke inbox admin. |
| Admin | CMS, dashboard, pengguna admin, ekspor, riwayat, MFA opsional | Satu tingkat izin `is_admin`; belum ada editor/reviewer terpisah. |
| Media | Upload gambar/video dan penjagaan berkas saat transaksi | Tanggal/lokasi galeri serta thumbnail video belum menjadi metadata lengkap. |

## Perjalanan pengguna

| Tujuan | Jalur saat ini | Titik yang perlu diperjelas |
| --- | --- | --- |
| Memilih skema | Beranda/search → katalog → filter → detail → kanal pendaftaran | Skema Beranda harus tersedia di katalog; harga dan persyaratan harus berasal dari record yang sama. |
| Memahami pelaksanaan | Informasi → TUK/asesor → alur asesmen → detail aktivitas/daftar | Klik aktivitas perlu bekerja; panduan umum tidak boleh terlihat sebagai status pendaftaran pribadi. |
| Memeriksa kredibilitas | Tentang Kami → profil/struktur/lisensi; Beranda → statistik/testimoni | Contoh desain perlu dipisahkan dari bukti yang sudah diverifikasi; dokumen legalitas perlu dapat dibaca penuh. |
| Menghubungi lembaga | Kontak → isi → draf → email/WhatsApp | Tindakan membuka draf tidak berarti pesan sudah diterima lembaga. Penjelasan ini sudah tersedia dan perlu dipertahankan. |
| Mengubah konten | Admin → form/modal → Terapkan → simpan bagian → API → publik | Bedakan perubahan dalam sesi dengan perubahan yang sudah diterbitkan. |
| Memeriksa perubahan | Admin → Riwayat → detail sebelum/sesudah | Nama model PHP dan JSON mentah sulit digunakan operator nonteknis. |

## Temuan prioritas P1

P1 berarti menghambat tugas utama atau memberi kesan status yang keliru. Usaha “kecil/sedang” adalah perkiraan lingkup relatif, bukan estimasi waktu pekerjaan.

### G01 — Beranda dan katalog memakai sumber skema berbeda

**Bukti:** [Beranda](../src/app/page.tsx) memakai `sampleSchemes.slice(0, 3)`, sedangkan katalog dan detail memakai [getPublicContent](../src/lib/content-api.ts). Browser membuktikan kartu Junior Coder tampil di Beranda dengan harga, tetapi URL detail 404 karena tidak ada skema aktif pada API lokal.

**Dampak:** pengunjung memilih penawaran yang tidak tersedia dalam katalog, atau melihat harga/metadata yang berbeda dari CMS.

**Perbaikan minimum:** ambil kartu Beranda dari skema aktif CMS yang sama. Pertahankan fixture contoh untuk tes saja. Jika belum ada skema, tampilkan keterangan singkat dan kanal pertanyaan tanpa kartu contoh.

**Selesai bila:** setiap kartu Beranda menuju detail aktif; perubahan nama/harga admin tercermin pada keduanya; CMS kosong tidak menampilkan penawaran contoh. **Usaha:** kecil–sedang.

### G02 — Kartu diagram tidak menerima klik

**Bukti:** [AssessmentFlow](../src/components/information/assessment-process-flow.tsx) mematikan drag/selection node. Style React Flow untuk node yang tidak interaktif diwarisi tombol. Pada browser, tombol dan node aktivitas mempunyai `pointer-events: none`; klik biasa terhalang pane. Enter tetap membuka detail.

**Dampak:** instruksi “Pilih aktivitas untuk membaca detail” tidak dapat diikuti dengan mouse; pengguna sentuh juga berisiko menemui kendala yang sama, tetapi tap perangkat nyata belum diuji.

**Perbaikan minimum:** aktifkan penerimaan pointer pada tombol kartu di CSS komponen, tanpa mengaktifkan drag node atau mengganggu pan pada ruang kosong. Hindari `force: true` pada tes karena dapat menyembunyikan masalah pengguna.

**Selesai bila:** klik biasa dan tap membuka aktivitas yang dipilih; drag ruang kosong tetap menggeser diagram; Enter/Escape dan pengembalian fokus tetap bekerja. Tambahkan klik biasa ke pemeriksaan browser yang kini lebih banyak menggunakan keyboard. **Usaha:** kecil.

### G03 — Gangguan API terlihat seperti tidak ada konten

**Bukti:** [getPublicContent](../src/lib/content-api.ts) menangkap kegagalan HTTP, payload, dan timeout lalu mengembalikan empat array kosong. Katalog, galeri, serta tabel direktori menampilkan keadaan tanpa hasil. Respons kosong yang sukses juga memperoleh pesan pencarian tidak cocok; browser menunjukkan reset katalog kosong tidak membantu.

**Dampak:** pengunjung berulang kali mencari/reset, padahal backend gagal atau konten memang belum diterbitkan.

**Perbaikan minimum:** gunakan tiga keadaan: gagal dimuat, belum tersedia, dan filter tidak cocok. Pola `unavailable` sudah ada pada loader organisasi/alur. Beri muat ulang/kontak untuk gangguan dan reset hanya untuk filter yang tidak cocok.

**Selesai bila:** respons 500/timeout, respons 200 kosong, serta pencarian tanpa kecocokan menghasilkan pesan dan tindakan pemulihan berbeda. **Usaha:** sedang; melibatkan loader dan seluruh pemanggilnya.

### G04 — Detail skema mengubah gangguan layanan menjadi 404

**Bukti:** halaman detail memanggil `notFound()` ketika slug tidak ditemukan di array dari loader G03. Tidak ada pembedaan antara katalog sukses dan loader gagal.

**Dampak:** URL sah dapat terlihat tidak ada ketika API bermasalah. Ini diturunkan dari alur kode; simulasi outage halaman detail belum dilakukan di browser.

**Perbaikan minimum:** periksa status pemuatan sebelum memutuskan 404; gunakan tampilan gangguan yang bisa dipulihkan ketika API gagal.

**Selesai bila:** 404 hanya muncul ketika pemuatan sukses dan slug memang tidak tersedia. API gagal tidak menyatakan skema hilang. **Usaha:** kecil setelah G03.

### G05 — CTA pendaftaran tidak menjelaskan kanal kepada pengguna visual

**Bukti:** detail menggunakan URL pendaftaran atau fallback WhatsApp; label visual tetap “Daftar Sekarang”, sedangkan keterangan WhatsApp berada pada teks `sr-only`.

**Dampak:** pengunjung dapat mengira tombol mencatat pendaftaran di website, padahal hanya membuka percakapan.

**Perbaikan minimum:** tampilkan “Daftar melalui WhatsApp” atau “Tanyakan skema ini” sesuai proses nyata. Untuk URL eksternal, jelaskan tujuan dan langkah setelah klik.

**Selesai bila:** kanal dan konsekuensi klik terbaca tanpa screen reader; konteks nama skema tetap dibawa; tidak ada klaim pendaftaran berhasil sebelum proses eksternal selesai. **Usaha:** kecil.

### G06 — Contoh desain ditampilkan sebagai testimoni dan statistik publik

**Bukti:** [SitePage::defaultContent](../../backend/app/Models/SitePage.php) dan [defaultLandingContent](../src/lib/landing.ts) berisi tiga ulasan identik dengan jabatan “Head of XYZ Company” serta angka 39/15+/72+. Browser melihatnya tampil. [ReviewCard](../src/components/landing/cards.tsx) selalu menggambar lima bintang, tanpa field rating. Statistik dashboard berasal dari database, sedangkan statistik Beranda berupa teks yang dapat diedit.

**Dampak:** pengunjung tidak mengetahui mana data terverifikasi, contoh, inventaris aktif, atau statistik historis. Perbedaan jumlah dengan CMS tidak otomatis salah apabila metrik historis memang dimaksudkan, tetapi definisinya belum terlihat.

**Perbaikan minimum:** default testimoni publik kosong sampai ada sumber yang disetujui; bintang hanya ditampilkan jika rating memang tersedia. Tentukan arti/periode statistik. Jika metrik berarti inventaris aktif, gunakan query yang konsisten dengan daftar publik.

**Selesai bila:** database baru dan API gagal tidak memublikasikan ulasan contoh; rating tidak dibuat dari asumsi; statistik mempunyai sumber/definisi yang jelas. **Usaha:** sedang + validasi pemilik konten.

## Temuan prioritas P2

| ID | Bukti dan dampak | Perbaikan minimum | Kriteria penerimaan |
| --- | --- | --- | --- |
| G07 | [ContactForm](../src/components/contact/contact-form.tsx) hanya membuat draf; [route API](../../backend/routes/api.php) GET-only. Inbox/widget admin memberi kesan pipeline pesan tersedia. | Jelaskan sumber inbox pada admin. Tambahkan penerimaan website hanya jika diperlukan operasional. | Operator mengetahui kanal yang dicatat; empty state tidak menjanjikan pesan form website masuk otomatis. Jika integrasi ditambahkan, uji pengiriman sampai inbox. |
| G08 | `Profile::saveSection` dan `ManageSitePages::saveSection` menerbitkan langsung. Modal “Terapkan” masih memerlukan simpan bagian; perubahan sesi dapat hilang. | Indikator perubahan belum disimpan, peringatan navigasi, dan label simpan/publikasi yang jelas. Mulai dari ini sebelum sistem draft baru. | Operator dapat membedakan perubahan sesi dari konten publik, tidak kehilangan perubahan tanpa pemberitahuan, dan dapat membuka halaman hasil. |
| G09 | [History](../../backend/app/Filament/Pages/History.php) menampilkan `class_basename` dan JSON sebelum/sesudah. | Nama jenis/field dalam bahasa operator dan ringkasan perubahan per field; kebutuhan restore diputuskan terpisah. | Operator dapat menjawab siapa mengubah apa dan kapan tanpa membaca JSON. |
| G10 | [ProcessRelationManager](../../backend/app/Filament/Resources/Processes/RelationManagers/ProcessRelationManager.php) memerlukan filter tahap dan pelaksana sebelum reorder aktivitas. | Petunjuk urutan kerja tahap → pelaksana → aktivitas → koneksi; tampilkan syarat reorder sebelum tindakan. | Admin baru dapat mengisi alur dan mengurutkan satu kelompok tanpa perlu menebak prasyarat. |
| G11 | [ContentDirectoryController](../../backend/app/Http/Controllers/ContentDirectoryController.php) memberi tanggal galeri null, lokasi kosong, dimensi 1200×800 tetap, dan gambar sertifikasi sebagai thumbnail video. UI menawarkan pencarian lokasi. | Sesuaikan petunjuk dengan metadata nyata; tambah tanggal/lokasi dan metadata media jika dibutuhkan. Gunakan thumbnail video yang relevan. | Field yang ditawarkan UI dapat diisi/dicari; informasi yang belum tersedia diberi keterangan jujur; foto/video tidak direpresentasikan oleh metadata tebakan. |
| G12 | Bagian lisensi Tentang Kami menampilkan gambar/judul/deskripsi, tanpa tindakan pembukaan dokumen penuh atau URL verifikasi pada kontrak data. | Buka gambar ukuran penuh atau dokumen resmi; tautan verifikasi jika sumbernya tersedia. | Dokumen dapat dibaca melalui mouse/keyboard dengan ukuran memadai dan sumbernya jelas. |
| G13 | [contactInfo](../src/lib/contact.ts) hardcoded; nomor lisensi ada pada [Footer](../src/components/landing/chrome.tsx). CMS profil tidak mengelola semuanya. | Tetapkan satu sumber yang diketahui untuk identitas operasional; gunakan CMS jika pengurus perlu mengubahnya. | Perubahan kontak memperbarui halaman, footer, WhatsApp dan peta; nomor lisensi tidak menyimpang antarhalaman. |
| G14 | [DirectoryTable](../src/components/information/directory-table.tsx) menyimpan filter hanya dalam state, memakai 3 baris default, dan merender semua nomor halaman. | Jika direktori rutin dipakai, simpan pencarian/filter pada URL seperti katalog; sesuaikan ukuran halaman dan jendela pagination dengan jumlah record. | Refresh/link dapat memulihkan konteks; ratusan record tidak menghasilkan deretan tombol halaman yang berlebihan. |

## Peningkatan bersyarat dan prioritas P3

| ID | Kondisi sekarang | Kapan dan bagaimana ditingkatkan |
| --- | --- | --- |
| G15 | Semua admin memakai flag `is_admin` dan gate `manage-admin`; editor, penerbit, dan pengelola akun tidak dibedakan. MFA sudah tersedia tetapi opsional. | Saat beberapa operator membutuhkan batas tugas berbeda, definisikan kewenangan editor/admin terlebih dahulu. Terapkan pada tindakan backend, bukan hanya menyembunyikan menu. Pilihan kebijakan MFA mengikuti kebutuhan pengurus. |
| G16 | Semua pemanggil direktori mengambil TUK, asesor, skema, dan galeri sekaligus. Layout panah memakai pencarian grid yang biayanya meningkat saat kartu bertambah. | Ukur volume payload dan waktu layout lebih dahulu. Pecah endpoint atau pindahkan routing ke worker ketika ada hambatan yang terukur; jangan menambah cache permanen tanpa aturan pembaruan konten. |
| G17 | `getLandingContent` tidak memakai timeout, sementara beberapa loader lain membatasi fetch 5 detik. | Samakan batas pemulihan yang masuk akal dan ukur waktu muat. Fallback tidak boleh menambahkan testimoni/penawaran contoh. |

## Urutan pengerjaan yang disarankan

| Tahap | Fokus | Hasil yang dituju |
| --- | --- | --- |
| 1 | G01 dan G02 | Tautan Beranda menuju konten nyata; aktivitas diagram dapat diakses dengan mouse, keyboard, dan sentuhan. |
| 2 | G03, G04, G05, G06 | Status pemuatan/pendaftaran jelas dan bukti publik tidak berasal dari placeholder. |
| 3 | G07–G10 | Operator memahami kanal pesan, publikasi, riwayat, dan pengelolaan alur. |
| 4 | G11–G14 | Media, dokumen, identitas dan penjelajahan direktori lebih berguna. |
| Sesuai kebutuhan | G15–G17 dan transaksi peserta | Investasi mengikuti kebutuhan operasional dan pengukuran, bukan menambah fitur sebelum diperlukan. |

Jika tujuan produk berubah menjadi aplikasi sertifikasi operasional, mulai dari pendaftaran peserta dan status permohonan; lanjutkan berkas/persyaratan, penjadwalan dan penugasan, hasil asesmen, lalu penerbitan sertifikat. Pembayaran hanya ditambahkan jika proses bisnis memerlukannya. Tentukan siapa mengerjakan setiap tahap dan bagaimana peserta mendapat kepastian status sebelum membangun modul.

## Pengujian penerimaan setelah perbaikan

- Dengan CMS kosong, skema aktif, skema nonaktif, URL tidak dikenal, API 500, payload tidak valid, dan timeout: pesan/tujuan navigasi harus sesuai kondisi sebenarnya.
- Pada 375/768/1440 px dan zoom browser 200%: isi dan kontrol dapat dibaca, tabel/diagram boleh memiliki scroll lokal, dan header/kontak tidak menutup tindakan utama.
- Dengan mouse/tap serta Tab/Shift+Tab/Enter/Space/Escape: menu, tab detail, modal media, dan kartu diagram berfungsi; fokus kembali ke pemicu setelah detail/modal ditutup.
- Dengan teks panjang, foto gagal, video tanpa thumbnail, daftar kosong, dan banyak record: tidak ada teks terpotong atau tindakan yang meminta pengguna memperbaiki masalah sistem.
- Dengan dua sesi admin: perubahan bagian halaman tidak saling menimpa; label publikasi akurat; riwayat membantu menjelaskan perubahan. Jangan gunakan database operasional untuk skenario destruktif.
- Uji tugas bersama pengguna: cari skema yang sesuai, pahami biaya dan persyaratan, jelaskan apa yang terjadi saat klik daftar, temukan TUK, serta periksa lisensi. Catat keberhasilan dan ekspektasi, bukan hanya jumlah klik.

## Yang sudah membantu pengguna

Pencarian header memiliki tujuan nyata. Filter katalog/galeri tersimpan di URL. Katalog menyediakan metadata skema, biaya yang belum diketahui, dan tautan kembali dengan konteks filter. Kontak menjelaskan batas draf dengan benar. Diagram/organisasi mempunyai alternatif daftar. Skip link, label kontrol, fokus, dan reduced motion telah mendapat perhatian pada implementasi; kualitas lengkapnya tetap perlu diuji per perjalanan pengguna.

Refaktor dan cleanup pada sesi ini menjaga kontrak yang ada. Temuan UX di atas tetap menjadi daftar pekerjaan; audit ini tidak menyatakan semuanya telah diperbaiki. Panduan memahami implementasi tersedia dalam [guidebook codebase](guidebook-codebase.md).

# Audit dan rencana halaman Skema Sertifikasi

Tanggal: 30 September 2026. Status: implementasi frontend selesai; menggunakan data contoh.

## Cakupan dan dasar audit

Pengguna meminta halaman daftar Skema Sertifikasi penuh serta detail skema. Audit menggunakan screenshot dan design context Figma, ditambah pembacaan kode frontend. Ini bukan hasil pengujian dengan pengguna atau verifikasi tampilan browser.

- [Katalog — frame 43:11469](https://www.figma.com/design/huSEDQMmzpxdBkIxlCpc8n/Prototype-Website?node-id=43-11469).
- [Detail Junior Coder — frame 92:5646](https://www.figma.com/design/huSEDQMmzpxdBkIxlCpc8n/Prototype-Website?node-id=92-5646).
- Frame 125:5533 juga diperiksa: screenshot memperlihatkan hero dan footer dengan bagian tengah kosong. Gunakan 92:5646 sebagai referensi detail yang lengkap.
- Pedoman audit: [Web Interface Guidelines](https://github.com/vercel-labs/web-interface-guidelines/blob/main/command.md).

Tujuan: calon peserta dapat menemukan skema sesuai bidang, metode, dan level; membaca unit dan persyaratan; kemudian menghubungi penyelenggara dengan konteks skema yang jelas.

Pertahankan keputusan sebelumnya: frontend dengan data contoh, header persisten dan ringkas, footer bersama dengan placeholder Facebook/Instagram/YouTube, serta hero non-Beranda berwarna sama dengan bagian di bawahnya dan dekorasi IT yang tenang.

## Temuan prioritas

| Prioritas | Bukti/lokasi | Dampak | Perubahan yang direncanakan |
|---|---|---|---|
| P1 | `src/components/landing/header-navigation.tsx:13`; `chrome.tsx:45` | Menu skema hanya menuju bagian Beranda, belum ada katalog penuh. | Arahkan ke `/skema-sertifikasi`; tandai menu aktif pada katalog dan detail. |
| P1 | `src/components/landing/ui.tsx:39` | Pencarian terlihat aktif tetapi tidak memiliki form, nama parameter, atau tujuan. | Form GET ke katalog, parameter `q`, mendukung Enter dan tombol pencarian. |
| P1 | `src/components/landing/cards.tsx:28`; `src/app/page.tsx:49` | Detail dan Lihat Seluruh Sertifikasi dinonaktifkan; alur berhenti. | Gunakan Link ke detail/katalog dan kartu berbasis data yang konsisten. |
| P1 | Figma 43:11469 | Total 39, enam kartu per halaman, tetapi pagination hingga 20. | Hitung total dan jumlah halaman dari hasil filter; 39 data dengan ukuran halaman 6 berarti 7 halaman. |
| P1 | Figma 92:5646 | Delapan kartu unit mengulang kode dan judul; nomor berulang 1 dan 2. | Nomor 1–N dan unit unik; jumlah mengikuti data. Jangan menganggap unit placeholder sebagai standar resmi. |
| P1 | Figma 92:6042 | “Daftar Sekarang” belum memiliki tujuan pendaftaran yang tersedia pada frontend. | Gunakan “Tanyakan skema ini” melalui kontak WhatsApp yang sudah ada; aktifkan “Daftar” hanya jika URL pendaftaran disediakan. |
| P2 | Figma hero katalog/detail: 96 px, subtitle 24 px italic | Hero mendominasi dan ringkasan detail sulit dipindai; gambar ramai bersaing dengan teks. | Judul 36–48 px, subtitle 18 px, hero terang dengan dekorasi circuit bersama; ringkasan sebagai panel informasi. |
| P2 | Figma filter 12–14 px; metadata kartu 12 px | Informasi metode dan kategori terlalu kecil untuk penggunaan nyaman. | Input/body 16 px, label/metadata 14 px, judul kartu 22–24 px; line-height 1.4–1.6. |
| P2 | Warna CTA `#308ec3` dengan teks putih | Rasio kontras sekitar 3,63:1, kurang untuk teks normal kecil. | CTA utama `#004195` dan putih; warna lebih terang untuk aksen. Placeholder `#bdbdbd` pada putih juga terlalu rendah, sekitar 1,88:1. |
| P2 | Figma 43:11469 filter “Aktifitas / Ascending” | Arti pengurutan tidak jelas untuk skema. Filter aktif dan cara reset kurang terlihat. | Opsi Nama A–Z/Z–A, Harga terendah/tertinggi, Level terendah/tertinggi; tampilkan filter aktif dan Reset filter. |
| P2 | Figma frame desktop 1440 px; beberapa kotak teks melewati lebar kartu | Penyalinan posisi absolut berisiko overflow pada layar kecil dan judul panjang. | Grid responsif, tinggi kartu mengikuti konten, pembungkusan judul/kode, tanpa fixed-height untuk isi. |
| P2 | Figma detail: “Kembali ke Halaman Sebelumnya” | Tujuan tidak pasti jika halaman dibuka langsung. | Link “Kembali ke daftar skema” dengan state katalog yang dibawa ke detail. |
| P2 | Persyaratan detail berwarna biru/italic dan kalimat “anda memungkinkan” | Tampak seperti tautan, sulit dipindai, hubungan alternatif wajib belum jelas. | Daftar biasa; jelaskan “Penuhi salah satu persyaratan dasar” dan “Siapkan semua berkas administrasi”. Teks contoh diberi label. |
| P2 | Figma tidak menunjukkan hasil kosong, loading, slug salah, atau filter tidak valid | Pengguna tidak mendapat arahan ketika pencarian gagal. | Hasil kosong dengan reset; fallback loading; halaman 404 untuk slug tidak dikenal; normalisasi parameter invalid. |

## Desain yang direkomendasikan

### Katalog `/skema-sertifikasi`

Header dan footer yang sudah digunakan seluruh halaman. Hero menggunakan `InternalHero`, background `#f4f7f7`, judul biru tua, dan dekorasi circuit yang tersedia. Tidak menambahkan gambar hero futuristis Figma karena keputusan pengguna sebelumnya tentang hero non-Beranda tetap berlaku.

Konten maksimal sekitar 1252 px. Desktop mulai 1024 px: sidebar filter sekitar 280 px dan area hasil dua kolom kartu seperti Figma. Tablet dan mobile: filter menjadi panel disclosure native berlabel “Filter skema”, hasil satu kolom di bawah 640 px dan dua kolom di atasnya. Kontrol minimal 44 px; label checkbox/radio seluruhnya dapat diklik.

Filter: metode yang tersedia, level KKNI minimum/maksimum, kategori multi-select, dan pencarian kategori lokal. Kategori berasal dari data skema. Pencarian kategori hanya menyaring daftar opsi, tidak mengganti pencarian skema atau menghapus pilihan tersembunyi. Tidak ada pilihan kategori berarti semua kategori. Pilihan kategori digabung OR; metode yang dipilih digabung OR; antar kelompok filter digabung AND. Metode jarak jauh hanya menampilkan skema yang secara eksplisit mendukungnya.

Pencarian judul/kategori menggunakan form submit. Filter dan sort langsung diterapkan, mereset halaman ke 1. Parameter URL: `q`, `metode`, `level_min`, `level_max`, `kategori` (berulang), `urutan`, `halaman`. Refresh dan Back/Forward memulihkan hasil. Default tanpa filter, nama A–Z, enam kartu per halaman. Batas KKNI 1–9; rentang terbalik dinormalisasi dengan menukar batas. Parameter tak dikenal/invalid diabaikan, halaman berlebih dibatasi ke halaman terakhir.

Kartu: thumbnail, nama, kategori, level, jumlah unit, metode, jenis skema, harga, dan Link “Lihat detail”. Gunakan metadata dengan label singkat, bukan paragraf kecil berulang. Harga memakai `Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })`; harga null menjadi “Hubungi kami untuk biaya”. Total “Menampilkan 1–6 dari N skema” berasal dari data; hasil kosong tidak menampilkan rentang palsu. Pagination hanya muncul bila diperlukan, menggunakan Link dan `aria-current`.

### Detail `/skema-sertifikasi/[slug]`

Hero terang dengan breadcrumb “Beranda / Skema Sertifikasi / Nama skema”, judul 36–48 px, kategori 18 px, dan ringkasan level KKNI, jenis skema, metode, jumlah unit, serta biaya. Panel ringkasan dua kolom desktop dan satu kolom mobile. Gunakan asset sertifikasi yang tersedia untuk thumbnail katalog; detail tidak memakai background sci-fi yang mengganggu keterbacaan.

CTA WhatsApp berlabel “Tanyakan skema ini”, membuka pesan dengan nama skema; bukan simulasi bahwa pendaftaran telah selesai. Jika sumber data kelak memiliki URL pendaftaran valid, tampilkan Link “Daftar sekarang”. Tidak membuat form pendaftaran, pembayaran, atau dashboard peserta pada pekerjaan ini.

Urutan isi: unit kompetensi; persyaratan dasar; persyaratan administrasi. Daftar unit terbuka pada awalnya, dapat disembunyikan dengan native `details/summary`, dan pencarian kode/judul yang case-insensitive. Unit dua kolom desktop, satu kolom mobile; penomoran mengikuti urutan asli data sehingga tidak berubah saat difilter. Tampilkan “X dari N unit” serta reset ketika pencarian tidak menemukan hasil. Persyaratan memakai daftar semantik, body 16 px; hindari italic pada seluruh paragraf.

Link kembali menuju katalog dengan parameter filter/sort/halaman yang dibawa lewat tautan detail. Pembukaan langsung tetap memiliki tujuan katalog yang valid. Slug tidak dikenal menghasilkan `notFound()`.

### Interaksi, gerakan, dan data

Hover mengubah warna/border, focus-visible memakai ring yang jelas, pressed mempergelap kontrol, disabled berbeda secara visual. Transisi hanya color/background/border/box-shadow sekitar 150 ms; hormati reduced-motion; tidak ada carousel/autoplay/parallax baru. Pengumuman jumlah hasil memakai `aria-live="polite"`.

Sumber awal adalah data contoh frontend yang diberi label. Siapkan sedikitnya sembilan skema unik untuk menguji pagination, berbagai kategori/metode/level, satu harga null, dan satu judul panjang. Junior Coder mengikuti metadata contoh Figma; unit dan persyaratan harus dilabeli contoh sampai diverifikasi. Jangan mengarang klaim bahwa data tersebut sudah disahkan.

Backend `CertificationScheme` saat ini hanya memiliki name, category, level, price, is_active; API publik skema belum ada. Media, metode, slug, unit, persyaratan, jenis skema, dan URL pendaftaran belum tersedia pada kontrak itu. Integrasi backend merupakan tahap terpisah.

## Urutan implementasi yang diusulkan

1. **Data dan logika katalog.** Buat `src/lib/schemes.ts`, `scheme-samples.ts`, serta `schemes.test.mjs`. Tipe Scheme mencakup id, slug, name, category, level, type, methods, price nullable, thumbnail, units (code/title), basicRequirements, administrativeRequirements, registrationUrl nullable. Fungsi murni membaca URL, membangun URL, memfilter/mengurutkan/memaginasi, dan mencari unit. Uji kombinasi OR/AND, batas KKNI, parameter invalid, hasil kosong, halaman berlebih, tie-break sort stabil, dan non-mutation.
2. **Katalog penuh.** Buat `src/app/skema-sertifikasi/page.tsx`, `src/components/schemes/scheme-browser.tsx`, `scheme-card.tsx`, dan `schemes.module.css`. Gunakan Suspense untuk state URL di client bila route statis; gunakan native History API yang didukung Next untuk filter. Kartu dan pagination berupa Link. Tambahkan panel filter responsif, notice data contoh, indikator filter aktif, reset, jumlah hasil, dan empty state.
3. **Detail skema.** Buat `src/app/skema-sertifikasi/[slug]/page.tsx`, `unit-list.tsx`, serta `page.module.css`. Render data/detail utama di server; client hanya untuk pencarian unit. Metadata title/deskripsi sesuai skema, 404 untuk slug salah, ringkasan/persyaratan/CTA sebagaimana desain di atas. Baca panduan Next yang terpasang terlebih dahulu, terutama params/searchParams asinkron dan notFound.
4. **Hubungkan alur website.** Ubah `header-navigation.tsx` dan `chrome.tsx`: route katalog dan status menu aktif. Ubah `ui.tsx` hanya pada SearchField agar menjadi form pencarian GET yang fungsional. Pada `src/app/page.tsx`, aktifkan Link “Lihat Seluruh Sertifikasi” dan gunakan tiga kartu contoh dari sumber data yang sama. Ganti penggunaan CertificationCard lama pada Beranda dengan SchemeCard; hapus fungsi lama jika sudah tidak digunakan. Jangan mengubah DesignButton global karena ada tombol lain di luar cakupan.
5. **Verifikasi.** Jalankan `node --test src/lib/*.test.mjs`, `npm run lint`, `npm run build`, `git diff --check`. Periksa HTTP katalog/detail/404/aset. Bila browser tersedia, uji 375/768/1440 px, keyboard, fokus, Enter pencarian, kombinasi filter, category search, URL refresh/Back/Forward, pagination, link kembali, unit disclosure/search, CTA WhatsApp, reduced-motion, dan pembesaran halaman 200%. Catat batas verifikasi bila browser tidak tersedia.

## Kriteria penerimaan

- Katalog dan detail dapat dibuka melalui menu, pencarian global, serta kartu Beranda.
- Jumlah hasil, kartu, pagination, harga, dan jumlah unit konsisten dengan data; tidak meniru angka statis Figma.
- Filter dan sort tersimpan di URL; detail dapat dibuka langsung; kembali ke daftar mempertahankan konteks.
- Tidak ada overflow horizontal pada mobile atau teks panjang; kontrol bisa dipakai dengan keyboard dan memiliki focus-visible.
- Hero/header/footer konsisten dengan halaman terbaru; placeholder sosial footer tetap ada.
- Data contoh diberi label; pendaftaran hanya mengarah ke tujuan yang nyata dan tersedia.
- Tidak menambahkan dependensi, mengubah backend, atau membangun alur pendaftaran dalam tahap ini.

Rekomendasi: implementasikan alur katalog dan detail bersama agar tidak menghasilkan tombol Detail tanpa tujuan. Menyalin Figma persis akan mempertahankan masalah data/typography; membuat redesign besar akan memperluas pekerjaan tanpa kebutuhan. Adaptasi layout Figma dengan komponen dan keputusan visual yang sudah ada memberi cakupan paling tepat.

## Catatan implementasi

- Katalog, detail, pencarian global, menu aktif, serta tiga kartu Beranda memakai sumber data skema contoh yang sama.
- Sembilan skema unik, delapan unit contoh Junior Coder, dan dua halaman katalog tersedia. Kategori URL divalidasi terhadap kategori dataset; kategori tidak dikenal diabaikan.
- Review kode menemukan masalah kategori URL tersebut, lalu perbaikan diverifikasi melalui pengujian yang gagal sebelum perbaikan dan lolos sesudahnya, serta review ulang.
- Selector kategori Beranda yang sebelumnya hanya memiliki satu opsi diganti label “Pratinjau 3 skema — data contoh”; filter lengkap tersedia di katalog.
- Tidak ada dependensi baru, integrasi backend, commit, atau deployment. Pekerjaan berada pada branch `feat/skema-sertifikasi` dalam workspace yang sama agar perubahan sebelumnya tetap tersedia.
- Seluruh 17 pengujian logika lolos. Pemeriksaan HTTP: Beranda/katalog/detail/aset 200, slug tidak dikenal 404, form GET dan tautan WhatsApp/detail/kembali tersedia. Browser tidak tersedia, sehingga pengujian visual, keyboard nyata, dan screen reader belum dilakukan.

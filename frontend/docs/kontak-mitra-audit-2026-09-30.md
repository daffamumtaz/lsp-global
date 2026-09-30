# Audit dan implementasi Kontak / Semua Mitra

Referensi: [Kontak 45:13359](https://www.figma.com/design/huSEDQMmzpxdBkIxlCpc8n/Prototype-Website?node-id=45-13359), [Mitra Kami 76:6271](https://www.figma.com/design/huSEDQMmzpxdBkIxlCpc8n/Prototype-Website?node-id=76-6271). Frame Mitra masih bernama Kontak di Figma, tetapi screenshot/context menunjukkan direktori mitra.

Pengguna meminta audit dan implementasi langsung. Audit berdasarkan screenshot/design context Figma dan kode; tidak ada sesi pengujian pengguna.

| Temuan | Implementasi |
|---|---|
| Hero 96 px dan latar ramai tidak konsisten dengan keputusan halaman internal sebelumnya. | Memakai InternalHero terang, font 36–48 px, subtitle 18 px, dekorasi circuit yang sudah ada. |
| Field placeholder dan opsi radio kecil; label wajib tidak konsisten. | Input 16 px, label 14 px, kontrol minimal 44 px, fieldset/legend, label klik, tipe email/tel dan autocomplete sesuai. |
| Tombol kirim tidak memiliki kontrak API pengiriman kontak. | Form menyiapkan draf email/WhatsApp; status eksplisit “Pesan belum dikirim”. Tidak menyimpan atau mengirim pesan di backend. |
| Belum ada feedback invalid form dan batas pesan. | Error di setiap field, focus ke field invalid pertama, validasi email/nomor, telepon wajib untuk preferensi Telepon/WhatsApp, batas 1.000 kata serta 5.000 karakter. |
| Peta Figma berupa gambar, bukan petunjuk arah yang dapat digunakan. | Iframe peta lazy dengan title, alamat teks, tautan peta dan petunjuk arah sebagai fallback. Tautan mengikuti [format Maps URLs resmi](https://developers.google.com/maps/documentation/urls/get-started). |
| Logo Jobhun berulang dan angka total 39 statis. | Daftar dinormalisasi, duplikat nama dihapus, total dan pencarian mengikuti data mitra yang tersedia. Tidak membuat mitra fiktif untuk mengisi grid. |
| Logo kosong berpotensi memakai logo merek lain. | Logo Jobhun hanya untuk Jobhun; mitra lain tanpa logo menampilkan inisial dan nama. Tautan website tidak valid tidak dibuat aktif. |
| Kembali “halaman sebelumnya” tidak memiliki tujuan stabil. | Link “Kembali ke Beranda” menuju bagian mitra. Pencarian GET `/mitra?q=...`, reset dan empty state tersedia. |
| Kontak navigasi mengarah footer; Semua Mitra dinonaktifkan. | `/kontak` dan `/mitra` terhubung melalui menu/footer/Beranda. Placeholder Facebook/Instagram/YouTube tetap tersedia. |

## Batas tahap ini

- Mengikuti preferensi frontend dahulu; tidak membuat API, mengubah backend, atau mengirim pesan percobaan.
- Semua Mitra memakai sumber mitra Landing yang sudah tersedia, termasuk fallback data desain. Jumlah mengikuti mitra unik, bukan angka Figma.
- Form mempertahankan isian selama menyiapkan draf; perubahan isian membatalkan draf lama agar tidak mengirim isi yang tertinggal. Pengguna dapat memeriksa dan menyalin isi draf bila aplikasi email tidak terbuka.
- ContactMessage backend belum memiliki field instansi/telepon/preferensi, dan belum ada route POST kontak. Integrasi penyimpanan pesan memerlukan kontrak tambahan pada tahap backend nanti.
- Tidak ada dependensi baru atau deployment.

## Verifikasi

- 21 pengujian logika lolos; mencakup validasi kontak, encoding draf, deduplikasi mitra, pencarian dan keamanan URL.
- HTTP `/kontak`, `/mitra`, pencarian mitra, hasil kosong, dan logo mengembalikan 200; isi halaman diperiksa untuk form, tautan, peta lazy, dan empty state.
- Review kode tidak menemukan masalah penting pada cakupan ini. Peringatan lint tentang import tidak terpakai dan atribut radio dibersihkan.
- Browser inventory kosong: layout mobile, interaksi keyboard nyata, pembukaan aplikasi email/WhatsApp, dan render peta belum diverifikasi secara interaktif.

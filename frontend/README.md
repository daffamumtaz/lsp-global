# Frontend LSP TIK Global

Next.js 16, React 19, dan TypeScript untuk website publik. Gunakan Node.js 24, versi yang dipakai untuk menjalankan tes proyek ini.

Untuk checkout baru:

```bash
npm ci
cp .env.example .env.local
npm run dev
```

Jika `.env.local` sudah ada, sesuaikan nilainya tanpa menimpanya. Jalankan backend terlebih dahulu agar katalog dan direktori dapat dimuat.

Variabel API pada `.env.local` dipakai oleh server Next.js. `LANDING_MEDIA_ORIGIN` mengatur origin gambar upload backend; setelah mengubah konfigurasi environment, jalankan ulang server frontend.

Pemeriksaan:

```bash
npm test
npm run lint
npm run build
```

Build produksi dijalankan dengan `npm run start`. Halaman ada di `src/app`, komponen di `src/components`, dan logika data/filter/diagram di `src/lib`.

- [Guidebook pemula](docs/guidebook-codebase.md)
- [Audit sistem dan UX](docs/audit-sistem-ux.md)

`src/lib/*.test.mjs` menjalankan tes fungsi dan kontrak menggunakan test runner bawaan Node. Script tambahan `scripts/check-assessment-process.mjs` memerlukan Playwright yang sudah tersedia, browser, kedua server lokal, dan alur asesmen aktif. Script tersebut membuat route fixture sementara dan membersihkannya; hasil screenshot lokal di `artifacts/` tidak dimasukkan ke Git.

### Preview Filament

Atur `ADMIN_PREVIEW_ORIGIN` ke origin panel Laravel yang membuka iframe (contoh `http://localhost:8000`), dan `FRONTEND_URL` backend ke origin frontend. Perbedaan hostname atau port akan ditolak. Route `/admin-preview/{organization,process,tuks}` tidak memuat data admin sendiri dan tidak menyimpan draft. Preview menerima snapshot hanya dari parent yang dipercaya.

Halaman `/mitra` menampilkan kartu dan filter tanpa peta. Peta publik tetap tersedia di `/informasi#tuk`. Konfigurasi tile berada di `NEXT_PUBLIC_MAP_TILE_URL` dan `NEXT_PUBLIC_MAP_ATTRIBUTION`.

Setelah build produksi, pemeriksaan browser dapat dijalankan dengan `node tests/admin-preview.browser.mjs`, `npm run test:browser:tuk`, `node tests/filament-preview.browser.mjs`, serta `node tests/profile-video.browser.mjs`. Pengujian peta mengintersep tile OSM; pengujian video memakai database SQLite sementara.

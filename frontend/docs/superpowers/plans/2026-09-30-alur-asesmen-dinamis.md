# Rencana Implementasi Alur Asesmen Dinamis SJJ

> **For agentic workers:** Gunakan `superpowers:executing-plans` untuk menjalankan task secara berurutan setelah pengguna menyetujui rencana. Langkah menggunakan checkbox untuk pelacakan. Rencana ini belum memberi izin implementasi.

**Goal:** Admin mengelola tahap, pelaksana, aktivitas, urutan, dan percabangan melalui Filament; diagram publik di `/informasi#alur-asesmen` berasal dari database dan menyesuaikan layout otomatis.

**Architecture:** Laravel menyimpan dan memvalidasi graph proses, Filament menyediakan editor relasional, API Resources mengirim kontrak semantik. Next.js mengambil data di server; React Flow menampilkan container tahap dan aktivitas, ELK menghitung layout lokal dan routing; tahap dikemas otomatis dalam komposisi berkelok seperti referensi.

**Tech Stack:** Next.js 16.3.6, TypeScript, React 19.2.8, React Flow 12.12.0, Laravel 13, Filament 5.8.4, PHPUnit. Usulan satu dependency baru frontend: `elkjs`. Dagre 3.1.1 tetap digunakan oleh bagan organisasi.

**Spec:** Bagian desain dan kontrak di dokumen ini merangkum permintaan pengguna beserta screenshot SJJ. Keputusan pengguna: seed enam tahap mengikuti screenshot, termasuk penjadwalan pra-asesmen sebagai tahap tersendiri. Seluruh tahap tetap dapat ditambah, diedit, diurutkan, atau dihapus dari Filament.

## Batasan global

- Tahap perencanaan saja; belum mengubah aplikasi, database, atau dependency.
- Pertahankan header, footer, TUK, asesor, skema sertifikasi, dan bagan organisasi existing.
- Database menjadi satu-satunya sumber konten proses. Tidak ada seed/fallback proses di React, Next.js, atau komponen diagram.
- Tidak menyimpan image diagram atau koordinat X/Y. `order` menentukan urutan presentasi; connections menentukan hubungan aktivitas. Mengurutkan aktivitas tidak otomatis menciptakan hubungan.
- Simpan dan tampilkan plain text; tidak menerima HTML/CSS diagram dari CMS.
- Gunakan aturan dan versi yang terpasang: baca panduan lokal Next.js dan dokumentasi Laravel/Filament sebelum implementasi.
- Tambahan `elkjs` merupakan bagian usulan rencana yang perlu disetujui; jangan memasang orgchart/ApexTree sekaligus.
- Seed dijalankan eksplisit pertama kali, bukan setiap startup/request; menjalankannya ulang tidak menimpa edit admin.
- Semua hasil layout bersifat sementara di frontend. Public canvas tidak menyediakan editing struktur atau pemindahan node.

## Temuan project dan pilihan library

- `frontend/src/app/informasi/page.tsx` saat ini berisi ringkasan enam tahap yang hardcoded dan gambar `/figma/remote-assessment-flow.png` di dalam disclosure.
- `src/components/about/organization-flow.tsx` sudah menggunakan React Flow, custom cards, zoom/pan, pengukuran node, dan fit view.
- `src/lib/organization-layout.ts` menggunakan Dagre. Gunakan pola pengukuran dan loading existing; jangan menyalin asumsi graph organisasi yang berupa tree atau mematikan fokus node pada diagram interaktif ini.
- Backend sudah memiliki resource Filament, reorder table, API `/api/organization`, serta feature tests/Livewire tests. Ikuti pola folder, autentikasi panel admin, label Bahasa Indonesia, dan respons `data`.

| Pilihan | Penilaian untuk fitur ini |
| --- | --- |
| React Flow + ELK | Direkomendasikan: mempertahankan renderer existing, mendukung custom nodes dan grouping, serta menyediakan layout/routing untuk graph bertingkat. |
| React Flow + Dagre | Dependency sudah ada dan sederhana untuk graph dasar; grouping dengan koneksi keluar grup membutuhkan penanganan tambahan. Tidak dipilih untuk layout utama proses. |
| nextjs-orgchart / ApexTree | Berorientasi chart organisasi/tree. Tidak dipilih karena proses memerlukan cross-links, branching, grouping aktor, dan kemungkinan jalur revisi. Halaman npm nextjs-orgchart tidak dapat dibaca (403); tidak mengasumsikan kemampuan API yang belum terverifikasi. |

Sumber: [React Flow layout](https://reactflow.dev/learn/layouting/layouting), [React Flow grouping](https://reactflow.dev/learn/layouting/sub-flows), [ELK Layered](https://eclipse.dev/elk/reference/algorithms/org-eclipse-elk-layered.html), [ApexTree](https://apexcharts.com/apextree/), [Filament relationship managers](https://filamentphp.com/docs/5.x/resources/managing-relationships).

## Desain visual dan layout

Komposisi seed desktop mengikuti arah baca screenshot:

```text
[Pendaftaran] → [Penjadwalan Pra-Asesmen] → [Pra-Asesmen] → [Persiapan Pelaksanaan]
                                                                    ↓
                               [Pasca-Asesmen] ← [Pelaksanaan Asesmen]
```

Ini adalah hasil target untuk seed, bukan daftar ID/koordinat yang dibekukan dalam frontend. Bila jumlah atau ukuran tahap berubah, layout menghitung ulang baris dan posisi.

- Tahap berupa container besar berlatar cyan sangat muda, judul biru tua dan nomor urutan. Ruang antartahap cukup untuk panah dan label koneksi.
- Pendaftaran memiliki kolom/lane Asesi dan Admin. Aktor lain, aktor tambahan, dan aktivitas tanpa aktor berasal dari data; tanpa aktor diberi label “Tanpa pelaksana”.
- Aktivitas berupa kartu rounded dengan judul dan pelaksana, ukuran teks dasar 16 px. Detail panjang berada di panel, judul membungkus dan ukuran kartu diukur dari DOM.
- Warna memakai palet website. Aktor dibedakan dengan label dan accent; outcome memakai accent status hijau/merah disertai teks. Decision memiliki penanda bentuk/label sehingga warna bukan satu-satunya pembeda.
- Connector aktivitas adalah hubungan nyata database. Panah ringkas antartahap merupakan agregasi koneksi lintas tahap yang benar-benar ada; tidak menambahkan hubungan hanya karena tahap bersebelahan.
- Canvas landscape dengan zoom/pan, fullscreen, dan reset. Mobile membuka awal alur dengan skala terbaca, bukan mengecilkan seluruh proses agar masuk layar. “Lihat seluruh alur” tetap tersedia.
- Sediakan tampilan daftar tahap/aktor/aktivitas dari data yang sama untuk keyboard, screen reader, dan pembacaan tanpa canvas. Daftar menyebut aktivitas berikutnya beserta label percabangan.

### Algoritma yang akan diimplementasikan

1. Urutkan tahap dan aktor menurut `(order, id)`; aktivitas menurut `(order, id)` di dalam tahap/aktor. Hubungan tetap berdasarkan connections.
2. Bentuk graph lokal setiap tahap dengan lane aktor. Ukur kartu dan label, lalu jalankan ELK Layered dengan orthogonal routing dan hierarchy untuk koneksi antarlane. Topology memengaruhi rank; `order` menjadi prioritas presentasi untuk node setara, bukan instruksi membalik panah.
3. Hitung bounding box tahap dari lane, kartu, header, dan connector. Gunakan ukuran tersebut untuk packing baris berkelok: baris pertama ke kanan, baris berikutnya ke kiri. Baris terakhir dirapatkan ke sisi masuknya alur, seperti reference.
4. Budget lebar komposisi dihitung dari container desktop dengan batas minimum landscape; pada tablet/mobile komposisi tidak dipadatkan sampai teks tidak terbaca. Container tahap boleh lebih lebar dari viewport dan tetap dapat dipan.
5. Routing koneksi lintas tahap menggunakan port keluar/masuk dan koridor kosong antartahap/baris. Jangan menggunakan bend points lama setelah tahap dipindahkan. Jalur revisi/koneksi yang melompati tahap menggunakan koridor luar, dengan label tetap terbaca.
6. Parent container muncul sebelum child node di React Flow; positions child relatif terhadap parent. ID memakai prefix `stage:`, `actor:`, `step:` dan `connection:` untuk mencegah bentrok.
7. Hitung ulang saat data, ukuran node, atau budget layout berubah. Abaikan hasil kalkulasi async yang sudah usang; jangan fit/reset viewport setiap kali pengguna membuka detail atau mengganti filter.

Uji layout dan routing dengan seed serta fixture graph yang berbeda sebelum integrasi final. ELK tidak dianggap otomatis menghasilkan komposisi berkelok; packing tahap dan penyesuaian routing adalah pekerjaan eksplisit pada task layout.

## Data dan integritas

Gunakan lima tabel sesuai permintaan:

| Tabel | Field dan relasi |
| --- | --- |
| `processes` | `id`, `title`, `slug` unique, `description` nullable, `is_active`, `order`, timestamps |
| `process_stages` | `id`, `process_id` FK, `title`, `description` nullable, `order`, `is_active`, timestamps |
| `process_actors` | `id`, `process_id` FK, `name`, `label`, `description` nullable, `order`, `is_active`, timestamps; `name` unique per process |
| `process_steps` | `id`, `stage_id` FK, `actor_id` nullable FK, `title`, `description` nullable, `type`, `order`, `is_active`, timestamps; optional sederhana `document_reference`, `information_url` nullable |
| `process_connections` | `id`, `process_id` FK, `source_step_id` FK, `target_step_id` FK, `label` nullable, `type` nullable, `order`, timestamps |

`type` berupa string dengan validasi PHP enum, bukan database enum: aktivitas `normal`, `decision`, `outcome`, `document`, `start`, `end`; koneksi `normal`, `success`, `revision`. Null connection type berarti `normal` di API.

Aturan pada lapisan model/domain, bukan hanya dropdown Filament:

- Pelaksana aktivitas dan tahapnya harus berasal dari process yang sama. Pindah tahap dalam process diperbolehkan; pindah lintas process ditolak.
- Source dan target koneksi harus berada dalam process koneksi. Tolak self-connection dan duplikasi pasangan source/target dengan label/type yang sama; percabangan dan parallel edge dengan label berbeda tetap diperbolehkan.
- Jalur kembali/revisi ke aktivitas sebelumnya diperbolehkan. Traversal tidak mengasumsikan DAG/tree dan tidak boleh loop tanpa batas.
- Kepemilikan process pada stage/actor tidak dapat dipindahkan dari form edit. Admin memindahkan aktivitas melalui “Tahap”.
- Penghapusan aktivitas menghapus koneksi masuk/keluarnya dalam transaksi. Penghapusan tahap/process membutuhkan konfirmasi jumlah data yang ikut terhapus. Hapus actor mengosongkan `actor_id` dengan penjelasan pada dialog.
- Nonaktifkan tahap menyembunyikan aktivitasnya. Nonaktifkan actor menyembunyikan aktivitas yang ditugaskan kepadanya. API menghilangkan koneksi jika salah satu endpoint tersembunyi; tidak menciptakan shortcut pengganti.
- Semua reorder memakai transaksi, scope parent/group yang sedang dibuka, otorisasi panel, serta validasi bahwa daftar ID lengkap dan tidak berasal dari kelompok lain.
- Index mengikuti query public/relasi, misalnya `(process_id, is_active, order)` pada stage/actor dan `(stage_id, is_active, order)` pada step. Tidak menambahkan unique `order`; tie diurutkan dengan ID.

## Editor Filament

Menu “Alur Asesmen” membuka daftar process. Form process: judul, slug, deskripsi, urutan, aktif. Label `is_active` dijelaskan sebagai “Tampilkan di website”; tidak ada workflow draft/publish tambahan.

Halaman edit process menampilkan:

1. **Tahap:** daftar terurut; CRUD dan drag reorder.
2. **Pelaksana:** Asesi/Admin/Asesor beserta aktor tambahan; CRUD, urutan, aktif.
3. **Aktivitas:** tampilan grouped berdasarkan tahap, lalu pelaksana. Form berlabel “Judul Aktivitas”, “Tahap”, “Pelaksana”, “Jenis Aktivitas”, “Deskripsi”, “Urutan”, “Aktif”, “Dokumen Terkait”, “Link Informasi”.
4. **Hubungan Aktivitas:** “Dari”, “Aktivitas Berikutnya”, “Label”, “Tipe Hubungan”, “Urutan”. Pilihan menampilkan `Tahap · Pelaksana · Judul`, dibatasi process yang diedit.
5. **Preview:** membuka renderer Next.js yang sama, termasuk record nonaktif, dengan label jelas “Preview”.

Reorder aktivitas hanya dalam tahap/pelaksana yang dipilih; pemindahan kelompok dilakukan lewat form. Jangan memakai reorder global lintas kelompok. Tidak ada canvas editor atau koordinat manual.

Preview menggunakan API route signed sementara (10 menit) yang URL-nya dibuat dari aksi admin terautentikasi. Next.js meneruskan signature/expiry ke backend route yang tetap, bukan menerima arbitrary API URL. Respons no-store; halaman preview noindex. Data public tidak pernah menerima parameter `include_inactive` dari pengguna anonim.

## Kontrak API dan fetching

```text
GET /api/processes
GET /api/processes/{slug}
GET /api/processes/{slug}/preview?expires=...&signature=...  (signed)
```

List mengembalikan metadata process aktif, terurut; detail mengembalikan graph lengkap melalui API Resource dengan envelope `{ "data": ... }`:

```json
{
  "data": {
    "id": 1,
    "title": "Alur Pelaksanaan Asesmen Sertifikasi Jarak Jauh (SJJ)",
    "slug": "asesmen-sertifikasi-jarak-jauh",
    "description": "...",
    "order": 1,
    "actors": [
      { "id": 1, "name": "asesi", "label": "Asesi", "description": null, "order": 1 }
    ],
    "stages": [
      {
        "id": 1, "title": "Pendaftaran", "description": null, "order": 1,
        "steps": [
          { "id": 1, "title": "Pendaftaran Calon Asesi", "description": null,
            "actorId": 1, "type": "start", "order": 1,
            "documentReference": null, "informationUrl": null }
        ]
      }
    ],
    "connections": [
      { "id": 1, "sourceStepId": 1, "targetStepId": 2,
        "label": null, "type": "normal", "order": 1 }
    ]
  }
}
```

Contoh ini hanya mendokumentasikan bentuk payload; implementasi tidak menanamkannya di frontend. Aktor dikirim satu kali di level process; lane per tahap diturunkan dari actorId pada step. Tidak perlu menduplikasi tabel aktor ke setiap tahap.

- Detail process nonaktif/tidak dikenal: 404. List kosong: `data: []`. Public graph memuat hanya record efektif aktif.
- Eager-load relasi; tidak mengeluarkan koordinat, field database internal, atau timestamps yang tidak digunakan.
- Environment frontend `PROCESSES_API_URL` berisi endpoint list. Halaman Informasi memilih process aktif pertama menurut `(order, id)`, lalu mengambil detail memakai slug dari respons. Admin menentukan alur yang tampil melalui status dan urutan process; mengganti slug atau membuat process pengganti tidak memerlukan perubahan kode/config frontend.
- `getAssessmentProcess()` memakai server fetch `cache: 'no-store'`, timeout 5 detik, dan validasi payload/reference. API juga mengirim `Cache-Control: no-store`.
- Perubahan terlihat pada kunjungan/refresh berikutnya. Tidak menambahkan polling/WebSocket; update otomatis berarti tidak membutuhkan edit kode/rebuild, bukan live push ke tab yang sudah terbuka.
- Tidak ada process aktif: tampilkan “Alur asesmen belum tersedia.” API gagal: “Alur asesmen sementara tidak dapat dimuat.” Diagram rusak tidak digantikan oleh screenshot/seed.
- Skeleton/loading canvas dan error layout hanya berada dalam bagian alur; halaman Informasi lainnya tetap berfungsi.

## Seed awal

Seeder membaca `backend/resources/data/assessment-process.json`. Dalam transaksi, buat process hanya jika slug seed belum ada; jika sudah ada, lewati seluruh graph agar edit dan penghapusan admin tidak muncul kembali.

Seed awal mengandung enam tahap sesuai pilihan pengguna:

1. **Pendaftaran:** Asesi mendaftar, mengajukan permohonan, mengisi FR.APL.01/asesmen mandiri FR.APL.02; Admin memverifikasi pendaftaran dan FR.APL.01. Connection memuat urutan dan handoff Asesi → Admin secara eksplisit.
2. **Penjadwalan Pra-Asesmen:** Admin membuat tautan online meeting pra-asesmen.
3. **Pra-Asesmen:** Asesor memverifikasi FR.APL.02, membuat rekomendasi pelaksanaan, menyiapkan mekanisme/perangkat, dan menjelaskan mekanisme banding.
4. **Persiapan Pelaksanaan:** Admin membuat tautan online meeting asesmen.
5. **Pelaksanaan Asesmen:** Asesor menjalankan proses, meninjau proses, dan membuat rekomendasi hasil; Asesi mengisi umpan balik, lalu Asesor mengakhiri proses asesmen.
6. **Pasca-Asesmen:** decision “Hasil Asesmen” bercabang menjadi outcome “Kompeten” dan “Belum Kompeten”; masing-masing menuju penerbitan/pengiriman sertifikat dan opsi re-asesmen. Label koneksi/jenis outcome berasal dari seed database, bukan pengecekan judul/ID pada frontend.

Seeder tidak mengarang URL dokumen/pendaftaran atau klaim operasional tambahan. Konten awal dapat diedit; penamaan dokumen yang belum pasti harus diperiksa pengguna sebelum publikasi. Cabang re-asesmen awal berakhir pada opsi tersebut; admin dapat menambahkan koneksi kembali bila diperlukan.

## File dan task implementasi

Path backend relatif terhadap `../backend`; frontend relatif terhadap direktori saat ini. Timestamp migration dihasilkan Artisan pada saat implementasi.

### Task 1 — PHASE 1: Database, models, dan invariants

**Files:** migration `database/migrations/*_create_assessment_process_tables.php`; `app/Models/Process.php`, `ProcessStage.php`, `ProcessActor.php`, `ProcessStep.php`, `ProcessConnection.php`; `app/Enums/ProcessStepType.php`, `ProcessConnectionType.php`; factories untuk kelima model; `tests/Feature/ProcessIntegrityTest.php`.

**Produces:** model relationships `Process::stages/actors/connections`, `ProcessStage::process/steps`, `ProcessActor::process/steps`, `ProcessStep::stage/actor/incomingConnections/outgoingConnections`, `ProcessConnection::process/sourceStep/targetStep`. Method reorder ter-scope pada model yang memiliki urutan.

- [x] Tulis failing tests: relasi, scope process, move step, cross-process actor/connection ditolak, duplicate connection ditolak, branching dan revision cycle diterima, cascade/delete actor, reorder parsial/asing tidak mengubah data.
- [x] Run `php artisan test --compact tests/Feature/ProcessIntegrityTest.php`; expected FAIL karena schema/model belum tersedia.
- [x] Buat migration, model, enum, factories, serta validation/transaksi minimal sesuai kontrak integritas.
- [x] Run ulang test; expected PASS. Periksa migration pada database test terisolasi; jangan memakai `migrate:fresh` pada database project.

### Task 2 — PHASE 2: Filament CRUD dan reorder

**Files:** `app/Filament/Resources/Processes/ProcessResource.php`; `Pages/ListProcesses.php`, `CreateProcess.php`, `EditProcess.php`; `RelationManagers/StagesRelationManager.php`, `ActorsRelationManager.php`, `StepsRelationManager.php`, `ConnectionsRelationManager.php`; `tests/Feature/ProcessCmsTest.php`.

**Consumes:** models/invariants Task 1. **Produces:** resource menu “Alur Asesmen”, form dan CRUD child, grouped view, scoped reorder.

- [x] Tulis failing Livewire tests: create/edit/toggle process dan seluruh child, label/options ter-scope, ubah actor/stage/order, reorder, delete dengan konfirmasi, unauthorized actions ditolak.
- [x] Run `php artisan test --compact tests/Feature/ProcessCmsTest.php`; expected FAIL resource belum tersedia.
- [x] Implementasikan resource dan relation managers menggunakan API Filament 5. Akses steps melalui relasi process-to-stages yang sesuai; jangan menganggap `process_id` ada pada steps.
- [x] Run tests Task 1–2; expected PASS. Preview action ditambahkan pada Task 7 ketika endpoint/renderer sudah ada.

### Task 3 — PHASE 3: API dan seed

**Files:** `app/Http/Controllers/ProcessController.php`; `app/Http/Resources/ProcessResource.php`; `routes/api.php`; `database/seeders/AssessmentProcessSeeder.php`; `resources/data/assessment-process.json`; `tests/Feature/ProcessApiTest.php`.

**Consumes:** models Task 1. **Produces:** list/detail API sesuai kontrak; signed preview API; seed graph yang dapat diedit.

- [x] Tulis failing tests: list active/sort, payload graph, label/type cabang, nonaktif/404, empty, pruning endpoint nonaktif, no-store, query count tidak tumbuh per aktivitas, signed preview valid/invalid/expired.
- [x] Tambahkan test seeder dua kali menghasilkan satu graph; edit/hapus child lalu seed ulang tidak mengembalikannya.
- [x] Run `php artisan test --compact tests/Feature/ProcessApiTest.php`; expected FAIL endpoint belum ada.
- [x] Implementasikan eager-loaded controller/resources/routes dan seeder create-once dalam transaksi. Public dan preview memakai serializer yang sama dengan visibility berbeda.
- [x] Run tests Task 1–3; expected PASS. Seeder pengisian database project dijalankan eksplisit setelah implementasi diverifikasi, bukan ditambahkan diam-diam ke startup.

### Task 4 — PHASE 4: Kontrak TypeScript, server fetching, preview

**Files:** `src/lib/assessment-process.ts`, `assessment-process.test.mjs`, `assessment-process-api.ts`; `.env.example`.

**Consumes:** API Task 3. **Produces:** type `AssessmentProcess`, `parseAssessmentProcess(input: unknown): AssessmentProcess`, `getAssessmentProcess(): Promise<{process: AssessmentProcess | null; unavailable: boolean}>`, preview loader terikat backend origin/path.

- [x] Tulis failing tests parser: nullable actor, branching/cycle, ID duplicate/dangling, unknown type, malformed fields, URL info unsafe, urutan stabil.
- [x] Run `node --test src/lib/assessment-process.test.mjs`; expected FAIL module belum ada.
- [x] Implementasikan types/parser/fetcher mengikuti `organization-api.ts`, tanpa fallback seed. URL informasi harus valid HTTPS tanpa credentials; URL tidak aman tidak menjadi link.
- [x] Implementasikan preview loader terikat origin backend yang dikonfigurasi: `getAssessmentPreview(slug, {expires, signature})`. Halaman preview dan aksi admin diintegrasikan pada Task 7 agar langsung menggunakan renderer final. Public loader memilih alur melalui list API, bukan konstanta slug.
- [x] Verify parser PASS; integration tests membuktikan timeout/non-2xx/malformed payload menghasilkan unavailable; signed preview mencakup inactive hanya dengan signature valid.

### Task 5 — PHASE 5–6: Transformasi graph dan automatic layout

**Files:** `src/lib/assessment-process-layout.ts`, `assessment-process-layout.test.mjs`; `package.json`, `package-lock.json` untuk `elkjs` saja; test fixtures proses di direktori test existing atau dalam test module.

**Consumes:** `AssessmentProcess`. **Produces:** `layoutAssessmentProcess(process, sizes, compositionWidth): Promise<{nodes, edges}>`; sizes berisi ukuran node terukur; edges menyimpan bend points/label positions hasil layout. Tidak memutasi payload.

- [x] Tulis failing tests linear, handoff antarlane, branching, convergence, revision cycle, lintas tahap, aktor null, tahap kosong, banyak tahap, order berubah, string panjang/variable sizes.
- [x] Assertions: semua step memiliki node; setiap connection memiliki edge dan endpoint valid; tidak ada overlap kartu/container; isi berada dalam parent; urutan tahap konsisten; semua koordinat finite; panah tidak memotong rectangle kartu; tidak ada edge tambahan; output stabil untuk input sama.
- [x] Run `node --test src/lib/assessment-process-layout.test.mjs`; expected FAIL module belum ada.
- [x] Pasang `elkjs` setelah persetujuan rencana; implementasikan pipeline lokal → stage packing → routing lintas tahap. Diagram organisasi/Dagre tidak diubah.
- [x] Run kedua test module assessment; expected PASS. Bandingkan seed desktop dengan komposisi screenshot sebelum membangun semua interaksi.

### Task 6 — PHASE 7–8: Renderer, detail, controls, responsive

**Files:** `src/components/information/assessment-process-flow.tsx`, `assessment-process-flow.module.css`, `assessment-process-view.tsx`; `scripts/check-assessment-process.mjs` mengikuti pola browser check existing.

**Consumes:** process + layout Task 4–5. **Produces:** server-friendly wrapper untuk semantic list/error; canvas client custom stage/lane/activity nodes, routed edges, kontrol dan detail.

- [x] Tulis browser check failing untuk diagram dari fixture/API, keyboard Enter/Space membuka aktivitas, fokus/Escape detail, zoom, pan, fullscreen enter/exit, reset, filter actor/stage, dan data update.
- [x] Implementasikan custom nodes/edges; group tidak menjadi default node final. Detail desktop berupa panel samping; mobile berupa panel di bawah canvas, bukan teks kecil di dalam kartu.
- [x] Node detail: judul, tahap, pelaksana, jenis, deskripsi, dokumen, link bila tersedia. Aktivitas dapat diakses dengan keyboard; daftar semantik menjadi alternatif pembacaan lengkap.
- [x] Gunakan native Fullscreen API, tangani browser yang tidak mendukungnya, letakkan detail/controls di dalam fullscreen container. Reset mengembalikan view awal; tombol “Lihat seluruh alur” memakai fit view.
- [x] Filter single-select actor dan stage membuat aktivitas lain subdued tetapi tetap dapat dibaca dan tidak menghapus graph. Tidak menghitung ulang layout hanya karena filter berubah.
- [x] Run browser check; expected PASS pada 375/768/1440 px dan pembesaran 200%, termasuk touch pan, text panjang, reduced motion, dan fokus terlihat.

### Task 7 — PHASE 9: Integrasi halaman dan validasi end-to-end

**Files:** `src/app/informasi/page.tsx`, `page.module.css`; `src/app/informasi/alur-asesmen/[slug]/preview/page.tsx`; backend `config/services.php`, `.env.example`, preview action pada `EditProcess.php`; extend `ProcessCmsTest.php` / browser check.

**Consumes:** seluruh deliverable sebelumnya. **Produces:** pengganti diagram statis dan ringkasan tahap hardcoded; preview dan public memakai renderer/data contract sama.

- [x] Buat halaman Informasi async untuk fetch process; pertahankan anchor `alur-asesmen`. Ganti gambar/disclosure dan ringkasan enam tahap dengan diagram serta daftar berbasis API. Journey Pelatihan → Asesmen → Sertifikasi → Pengembangan Profesional yang berbeda konteks tetap dipertahankan.
- [x] Jangan menghapus asset gambar dari repo bila masih dirujuk bagian lain; pastikan tidak lagi menjadi diagram utama public.
- [x] Buat route preview noindex/no-store dan aksi admin preview dengan signature 10 menit; gunakan renderer Task 6 dan loader Task 4. Konfigurasikan backend frontend URL tanpa menanam domain deployment di kode. Verifikasi preview aktif/nonaktif, signature invalid/expired, dan authorization pada pembuat tautan.
- [x] E2E: dari Filament tambah/ubah tahap, actor, step, order dan cabang; reload public/preview; assert konten, struktur, dan layout berubah tanpa perubahan source atau rebuild frontend.
- [x] Uji seed hanya pada database test dahulu. Setelah verifikasi, jalankan migration/seed eksplisit untuk database project yang sesuai; jangan menjalankan destructive reset.
- [x] Run backend `php artisan test --compact` dan `vendor/bin/pint --dirty --format agent`; expected semua tests PASS dan formatting selesai.
- [x] Run frontend `node --test src/lib/*.test.mjs`, browser check, `npm run lint`, `npx tsc --noEmit`, `npm run build`, `git diff --check`; expected exit 0.
- [x] Review screenshot desktop/mobile, branch labels, lane grouping, ukuran terbaca, koneksi lintas baris, loading/error/empty, serta regresi halaman Informasi dan bagan organisasi.

## Review focus

- Stage/lane besar dan cross-links setelah wrapping: routing tidak menembus kartu atau membuat label cabang salah endpoint (Task 5–6).
- Pengeditan saat layout async berlangsung: hasil lama tidak menimpa graph terbaru atau mereset pan pengguna (Task 6).
- Mutasi/ID lintas process dan reorder parsial: transaksi gagal tanpa merusak record lain (Task 1–2).
- Menonaktifkan/hapus actor/stage/step: API dan diagram tidak menyisakan dangling edges atau shortcut yang tidak dibuat admin (Task 1, 3–5).
- Preview inactive: API public tetap tidak mengungkap data tersebut; signature expired/invalid ditolak (Task 3–4, 7).

## Kriteria selesai dan cakupan awal

Selesai jika admin dapat menjalankan seluruh operasi yang diminta dari Filament, public graph dan preview memakai data database, perubahan terlihat setelah refresh, cabang tidak hardcoded, dan layout tetap terbaca setelah struktur/ukuran node berubah.

Cakupan awal mencakup zoom/pan/fullscreen/reset/detail, legend/filter actor dan stage, daftar semantik, serta reorder CMS. Collapse/expand tahap, export diagram, histori versi, live polling, attachment upload, durasi/output, dan editor canvas ditunda karena optional dan tidak diperlukan untuk tujuan utama. Tambahkan kemudian bila ada kebutuhan operasional nyata.

Implementasi dan verifikasi selesai pada 30 September 2026. Persetujuan rencana mencakup pilihan React Flow + satu dependency ELK, komposisi berkelok, semantik publikasi/preview, serta enam tahap seed yang sudah dipilih pengguna.


## Hasil implementasi

- Public: `/informasi#alur-asesmen`. CMS: `/admin/processes` → Kelola Alur, lalu tab Tahap, Pelaksana, Aktivitas, dan Hubungan Aktivitas.
- Pilihan proses public: alur aktif pertama menurut `(order, id)`. Perubahan CMS terlihat setelah refresh; tidak ada seed/fallback process dalam komponen frontend.
- Preview Alur membuka renderer yang sama, termasuk data nonaktif. Signature berlaku 10 menit; halaman noindex dan respons tidak disimpan cache.
- Migrasi dan `AssessmentProcessSeeder` sudah dijalankan pada database lokal. Seeder membuat graph sekali dan tidak menimpa konten yang telah diedit.
- Deployment: set `PROCESSES_API_URL` pada frontend dan `FRONTEND_URL` pada backend. Jalankan `php artisan migrate --no-interaction` dan `php artisan db:seed --class=AssessmentProcessSeeder --no-interaction` pada database tujuan.
- Verifikasi ulang 30 September 2026: 58 tes backend (429 assertions), 36 tes frontend, ESLint, TypeScript, build produksi, browser 375/768/1440/720 px, magnifikasi native 200%, pan mouse/sentuhan, keyboard/detail, fullscreen, label panjang/paralel, arah panah, dan CMS → API → public/preview. Pint lulus pada penyelesaian implementasi; tidak ada perubahan PHP pada verifikasi ulang.
- Browser checks: `node scripts/check-assessment-process.mjs` dan `node scripts/check-assessment-cms.mjs` dengan frontend dev server dan Laravel lokal berjalan. Gunakan `PLAYWRIGHT_MODULE`/`CHROMIUM_EXECUTABLE` jika runtime browser di luar project. Check pertama membuat route fixture sementara dan menghapusnya; check CMS membuat akun/content QA sementara dan membersihkannya. Jalankan kedua check secara berurutan agar mutasi CMS tidak mengganggu snapshot public.
- Layout ELK berjalan per lane; packing lane/tahap otomatis. Label terukur menjadi node presentasi agar fit-view mencakup semua label. Routing grid ditujukan untuk alur berukuran moderat; pertimbangkan worker bila proses besar mulai lambat.
- Collapse tahap, export, histori versi, live polling, upload attachment, dan editor canvas tetap di luar cakupan awal sesuai rencana.

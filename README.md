# LSP TIK Global

Website publik dan CMS untuk profil lembaga, skema sertifikasi, TUK, asesor, galeri, dan panduan alur asesmen.

| Aplikasi | Teknologi | Petunjuk |
| --- | --- | --- |
| `frontend/` | Next.js 16, React 19, TypeScript | [Menjalankan frontend](frontend/README.md) |
| `backend/` | Laravel 13, Filament 5, SQLite secara default | [Menjalankan backend](backend/README.md) |

Frontend mengambil konten dari API backend. Admin mengelola konten melalui panel Filament. Pendaftaran dan komunikasi diteruskan ke kanal eksternal; website belum menyimpan transaksi sertifikasi peserta.

- [Guidebook codebase untuk pemula](frontend/docs/guidebook-codebase.md): setup, istilah, peta folder, alur data, contoh perubahan, dan pengujian.
- [Audit gap sistem dan UX](frontend/docs/audit-sistem-ux.md): kondisi saat ini, bukti temuan, prioritas, dan kriteria perbaikan.

Jalankan perintah aplikasi dari folder masing-masing. Detail refaktor dan cleanup tercatat dalam guidebook.

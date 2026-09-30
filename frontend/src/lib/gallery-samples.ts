import type { GalleryActivity, GalleryMedia } from "./gallery";

const eventPhoto: GalleryMedia = {
  id: "event-photo", type: "photo", src: "/figma/gallery/sjj-revou-2025.png",
  thumbnail: "/figma/gallery/sjj-revou-2025.png", width: 740, height: 414,
  alt: "Foto bersama peserta dan penyelenggara kegiatan sertifikasi",
};
const documentation: GalleryMedia = {
  id: "documentation-photo", type: "photo", src: "/figma/profile-company.png",
  thumbnail: "/figma/profile-company.png", width: 634, height: 601,
  alt: "Kolase dokumentasi pertemuan dan kegiatan LSP TIK Global",
};

// Example years and captions demonstrate navigation; they are not verified event records.
export const sampleGallery: GalleryActivity[] = [
  {
    id: "sjj-revou-example", title: "SJJ RevoU 2025", year: 2025, date: "2025-09-17", location: "Zoom Meeting",
    description: "Dokumentasi kegiatan sertifikasi SJJ RevoU.",
    media: [{ ...eventPhoto, id: "sjj-revou-photo" }],
  },
  {
    id: "documentation-2025-example", title: "Dokumentasi LSP", year: 2025, date: null, location: "Lokasi belum tersedia",
    description: "Dokumentasi pertemuan dan kegiatan LSP TIK Global.",
    media: [{ ...documentation, id: "documentation-2025-photo" }],
  },
  {
    id: "documentation-2026-example", title: "Dokumentasi kegiatan 2026", year: 2026, date: null, location: "Lokasi belum tersedia",
    description: "Album dokumentasi pertemuan dan kegiatan LSP TIK Global.",
    media: [{ ...documentation, id: "documentation-2026-photo" }],
  },
  {
    id: "documentation-2024-example", title: "Dokumentasi kegiatan 2024", year: 2024, date: null, location: "Lokasi belum tersedia",
    description: "Album dokumentasi kegiatan sertifikasi.",
    media: [{ ...eventPhoto, id: "documentation-2024-photo" }],
  },
];

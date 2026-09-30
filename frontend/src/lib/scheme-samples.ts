import type { Scheme, SchemeMethod } from "./schemes";

// Preview records only: names, prices, levels, requirements and units need verification before publication.
const entries: [string, string, string, number, SchemeMethod[], number | null][] = [
  ["junior-coder", "Junior Coder", "Programming dan Pengembangan Software", 2, ["luring", "jarak-jauh"], 1000000],
  ["web-developer", "Web Developer", "Programming dan Pengembangan Software", 4, ["luring", "jarak-jauh"], 1500000],
  ["mobile-developer", "Mobile Developer", "Programming dan Pengembangan Software", 4, ["luring"], 1500000],
  ["data-analyst", "Data Analyst", "Data Management System", 5, ["luring", "jarak-jauh"], 1800000],
  ["database-administrator", "Database Administrator", "Data Management System", 4, ["luring"], 1600000],
  ["network-technician", "Network Technician", "Infrastruktur dan Jaringan", 3, ["luring"], 1200000],
  ["it-support", "IT Support", "Infrastruktur dan Jaringan", 2, ["luring", "jarak-jauh"], 900000],
  ["cybersecurity", "Cybersecurity Analyst", "Keamanan Informasi", 5, ["jarak-jauh"], null],
  ["software-quality", "Software Quality Assurance untuk Aplikasi dan Layanan Digital", "Programming dan Pengembangan Software", 4, ["luring", "jarak-jauh"], 1500000],
];
const programmingUnits = [
  "Menerapkan perintah eksekusi bahasa pemrograman berbasis teks, grafik, dan multimedia",
  "Menyusun algoritma untuk kebutuhan aplikasi", "Menggunakan struktur data dalam program",
  "Menerapkan antarmuka pengguna", "Mengelola penyimpanan data aplikasi",
  "Menguji fungsi program", "Memperbaiki kesalahan pada program", "Mendokumentasikan kode program",
];
export const sampleSchemes: Scheme[] = entries.map(([slug, name, category, level, methods, price]) => ({
  id: slug, slug, name, category, level, methods, price, type: "Okupasi",
  thumbnail: "/figma/certification.jpg", registrationUrl: null,
  units: (slug === "junior-coder" ? programmingUnits : [
    `Mengidentifikasi kebutuhan pekerjaan ${name}`, `Melaksanakan tugas dasar ${name}`,
    "Memeriksa hasil pekerjaan", "Mendokumentasikan hasil pekerjaan",
  ]).map((title, index) => ({ code: `UNIT-${String(index + 1).padStart(2, "0")}`, title })),
  basicRequirements: slug === "junior-coder" ? [
    "Salinan kartu pelajar atau rapor terakhir SMK jurusan Rekayasa Perangkat Lunak.",
    "Salinan sertifikat pelatihan berbasis kompetensi Pemrograman Komputer.",
    "Surat pengalaman kerja sebagai Junior Coder minimal satu tahun secara berkelanjutan.",
    "Salinan ijazah minimal D1 bidang Informatika yang dilegalisasi perguruan tinggi.",
  ] : ["Bukti pendidikan atau pelatihan yang relevan dengan bidang skema.", "Bukti pengalaman kerja yang relevan dengan bidang skema."],
  administrativeRequirements: ["Pasfoto 3 × 4 dengan latar merah.", "Salinan KTP."],
}));

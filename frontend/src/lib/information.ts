import type { DirectoryEntry } from "./directory";

// Prototype data only; names and certificate details from the Figma frame are unverified.
export const sampleTuks: DirectoryEntry[] = [
  { id: "tuk-1", name: "TUK Saintisindo Banten", code: "TUK-TIKGLOBAL-002", city: "Kota Serang", province: "Banten", address: "Kp. Pelawad Tegal RT/RW 002/003, Ds. Pelawad, Kec. Ciruas" },
  { id: "tuk-2", name: "TUK WTI Nganjuk", code: "TUK-TIKGLOBAL-003", city: "Nganjuk", province: "Jawa Timur", address: "Jl. Citarum 2 No. 1, Begadung" },
  { id: "tuk-3", name: "Aston City Kemayoran Jakarta", code: "TUK-TIKGLOBAL-004", city: "Jakarta Pusat", province: "DKI Jakarta", address: "Alamat belum tersedia" },
];

export const sampleAssessors: DirectoryEntry[] = [
  { id: "assessor-1", name: "A. Irwan Fasihi, S.Si", certificate: "MET.000.002531 2017", field: "Bidang belum tersedia" },
  { id: "assessor-2", name: "Asesor Contoh 02", certificate: "Belum tersedia", field: "Pemrograman (contoh)" },
  { id: "assessor-3", name: "Asesor Contoh 03", certificate: "Belum tersedia", field: "Jaringan Komputer (contoh)" },
];

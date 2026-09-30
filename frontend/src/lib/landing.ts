import { connection } from "next/server";

export type LandingFeature = { title: string; caption: string; icon: "award" | "shield" | "network" };
export type LandingStatistic = { value: string; label: string };
export type LandingReview = { name: string; occupation: string; quote: string; photo_url: string | null };
export type LandingPartner = { name: string; url: string; logo_url: string | null };

export type LandingContent = {
  hero: { hook: string; description: string; video_url: string | null; poster_url: string | null };
  why_us: { hook: string; description: string; features: LandingFeature[] };
  statistics: LandingStatistic[];
  reviews: { hook: string; description: string; items: LandingReview[] };
  partners: LandingPartner[];
  updated_at: string | null;
};

export const defaultLandingContent: LandingContent = {
  hero: {
    hook: "Wujudkan Kompetensi Profesional Anda",
    description: "LSP TIK Global siap mencetak Sumber Daya Manusia (SDM) TIK yang unggul, profesional, dan kompeten untuk menghadapi tantangan industri global.",
    video_url: null,
    poster_url: null,
  },
  why_us: {
    hook: "Mengapa Memilih LSP TIK Global?",
    description: "Kami berkomitmen memberikan layanan sertifikasi kompetensi yang profesional, terpercaya, dan berorientasi pada kebutuhan dunia kerja.",
    features: [
      { title: "Sertifikat Kompetensi Diakui Secara Nasional", caption: "Memiliki sertifikat kompetensi resmi dari BNSP RI sebagai bukti sah keterampilan Anda.", icon: "award" },
      { title: "Skema Sertifikasi Berbasis SKKNI", caption: "Seluruh skema sertifikasi mengacu pada SKKNI dan peraturan ESDM terbaru serta kebutuhan industri.", icon: "shield" },
      { title: "Jaringan TUK di Berbagai Daerah", caption: "Memiliki jaringan Tempat Uji Kompetensi (TUK) yang luas dan tersebar di berbagai wilayah Indonesia.", icon: "network" },
    ],
  },
  statistics: [
    { value: "39", label: "Skema\nSertifikasi" },
    { value: "15+", label: "Tempat Uji\nKompetensi (TUK)" },
    { value: "72+", label: "Asesor\nKompeten" },
  ],
  reviews: {
    hook: "Review Kami",
    description: "Apa kata mereka tentang LSP TIK Global?",
    items: Array.from({ length: 3 }, () => ({
      name: "Daffa Naufal Mumtaz",
      occupation: "Head of XYZ Company, West Java",
      quote: "Proses pendaftaran hingga pelaksanaan Uji Kompetensi berjalan sangat lancar. Skema sertifikasinya benar-benar relevan dengan teknologi yang saya gunakan sehari-hari.",
      photo_url: null,
    })),
  },
  partners: Array.from({ length: 10 }, () => ({ name: "Jobhun", url: "https://jobhun.id", logo_url: null })),
  updated_at: null,
};

export async function getLandingContent(): Promise<LandingContent> {
  await connection();
  const url = process.env.LANDING_API_URL ?? "http://127.0.0.1:8000/api/landing-page";

  try {
    const response = await fetch(url, { cache: "no-store" });
    if (!response.ok) throw new Error(`Landing API returned ${response.status}`);
    const payload = await response.json() as { data?: LandingContent };
    if (!payload.data?.hero || !payload.data.why_us || !payload.data.reviews || !Array.isArray(payload.data.partners)) {
      throw new Error("Landing API returned an invalid payload");
    }
    return payload.data;
  } catch (error) {
    console.error("Unable to load landing content:", error);
    return defaultLandingContent;
  }
}

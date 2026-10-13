import { cache } from "react";
import sectionDefaults from "./section-headings.json";
import { connection } from "next/server";

export type LandingFeature = { title: string; caption: string; icon: "award" | "shield" | "network"; href: "/tentang-kami#lisensi" | "/informasi#tuk" };
export type LandingStatistic = { value: string; label: string };
export type LandingReview = { name: string; occupation: string; quote: string; photo_url: string | null };
export type LandingPartner = { name: string; url: string; logo_url: string | null };

export type SiteIdentity = { phone: string; email: string; whatsapp: string; address: string; maps_url: string | null; working_hours: string; license_number: string; whatsapp_message: string; whatsapp_registration_message: string; whatsapp_contact_message: string; copyright_text: string; brand_name: string; logo_url: string | null; icon_url: string | null; favicon_url: string | null; footer_heading: string; footer_caption: string; footer_description: string };

export type LandingContent = {
  sections: typeof sectionDefaults;
  identity: SiteIdentity;
  hero: { title: string; hook: string; description: string; strengths: string[]; video_url: string | null; poster_url: string | null; background_url?: string | null };
  about: { hook: string; description: string };
  why_us: { hook: string; description: string; features: LandingFeature[] };
  social_links: { Facebook: string | null; Instagram: string | null; YouTube: string | null };
  statistics: LandingStatistic[];
  highlighted_scheme_ids: string[];
  reviews: { hook: string; description: string; items: LandingReview[] };
  partners: LandingPartner[];
  partners_section: { hook: string; description: string };
  updated_at: string | null;
};

export const defaultLandingContent: LandingContent = {
  sections: sectionDefaults,
  identity: { phone: "021-27651353", email: "info@lspglobal.id", whatsapp: "+62 851-9553-3360", address: "Jl. Lebak Bulus Raya Komplek Ruko Victory 88 No. 88 B, Cilandak, Jakarta Selatan", maps_url: null, working_hours: "", license_number: "BNSP-LSP-2150-ID", whatsapp_message: "Halo LSP TIK Global, saya ingin bertanya tentang sertifikasi.", whatsapp_registration_message: "Halo {brand_name}, saya ingin mendaftar sertifikasi skema {scheme_name}.", whatsapp_contact_message: "Subjek: {subject}\nNama: {name}\nInstansi: {institution}\nEmail: {email}\nTelepon: {phone}\nPreferensi balasan: {preference}\n\n{message}", copyright_text: "All rights reserved.", brand_name: "LSP TIK Global", logo_url: null, icon_url: null, favicon_url: null, footer_heading: "Bersama Membangun Kompetensi", footer_caption: "Kami hadir untuk bersama membangun kompetensi yang terpercaya.", footer_description: "Lembaga Sertifikasi Profesi bidang Teknologi Informasi & Komunikasi berlisensi BNSP." },
  hero: {
    title: "Bersama LSP TIK Global",
    hook: "Bangun Perjalanan Karier Anda dengan Kompetensi yang Diakui",
    description: "Validasi kompetensi Anda dan tingkatkan kredibilitas di industri teknologi dan digital.",
    strengths: ["Lisensi Resmi BNSP", "Kompetensi Terverifikasi", "Asesor Profesional Berpengalaman", "Jejaring Profesional luas", "Relevan dengan Industri"],
    video_url: null,
    poster_url: null,
  },
  about: { hook: "Tentang LSP TIK Global", description: "LSP TIK Global berkomitmen membangun SDM yang kompeten dan profesional melalui sertifikasi kompetensi yang relevan dengan kebutuhan industri.\n\nSebagai Lembaga Sertifikasi Profesi di bidang Teknologi Informasi dan Komunikasi, kami hadir untuk memberikan pengakuan atas kompetensi yang dimiliki individu melalui proses asesmen yang objektif, terukur, dan mengacu pada standar kompetensi yang berlaku.\n\nDengan beragam skema sertifikasi di bidang IT dan digital serta didukung oleh asesor kompeten, LSP TIK Global membuka kesempatan bagi mahasiswa, profesional, maupun talenta digital untuk membuktikan keahlian, memperkuat kredibilitas profesional, dan meningkatkan daya saing di tengah perkembangan industri yang semakin dinamis.\n\nBagi kami, sertifikasi bukan sekadar tentang mendapatkan sertifikat, tetapi tentang memastikan setiap kompetensi memiliki bukti, pengakuan, dan nilai yang dapat dibawa untuk melangkah lebih jauh." },
  why_us: {
    hook: "Mengapa Memilih Sertifikasi di LSP TIK Global?",
    description: "Jadikan kompetensi Anda lebih bernilai dengan pengakuan yang memperkuat kredibilitas dan posisi profesional di tengah persaingan industri.",
    features: [
      { title: "Sertifikat Kompetensi Diakui Secara Nasional", caption: "Memiliki sertifikat kompetensi resmi dari BNSP RI sebagai bukti sah keterampilan Anda.", icon: "award", href: "/tentang-kami#lisensi" },
      { title: "Skema Sertifikasi Berbasis SKKNI", caption: "Seluruh skema sertifikasi mengacu pada SKKNI dan peraturan ESDM terbaru serta kebutuhan industri.", icon: "shield", href: "/tentang-kami#lisensi" },
      { title: "Jaringan TUK di Berbagai Daerah", caption: "Memiliki jaringan Tempat Uji Kompetensi (TUK) yang luas dan tersebar di berbagai wilayah Indonesia.", icon: "network", href: "/informasi#tuk" },
    ],
  },
  statistics: [],
  highlighted_scheme_ids: [],
  reviews: {
    hook: "Review Kami",
    description: "Apa kata mereka tentang LSP TIK Global?",
    items: Array.from({ length: 6 }, (_, index) => ({
      name: `Placeholder Review ${index + 1}`,
      occupation: "Nama dan profesi akan ditambahkan",
      quote: "Placeholder — ulasan peserta sertifikasi akan ditampilkan di sini.",
      photo_url: null,
    })),
  },
  social_links: { Facebook: "https://www.facebook.com/", Instagram: "https://www.instagram.com/lsptikglobal/", YouTube: "https://www.youtube.com/" },
  partners: [{ name: "Jobhun", url: "https://jobhun.id", logo_url: null }],
  partners_section: { hook: "Mitra Kami", description: "Dipercaya oleh berbagai perusahaan dan institusi pendidikan" },
  updated_at: null,
};

export const getLandingContent = cache(async (): Promise<LandingContent> => {
  await connection();
  const url = process.env.LANDING_API_URL ?? "http://127.0.0.1:8000/api/landing-page";

  try {
    const response = await fetch(url, { cache: "no-store" });
    if (!response.ok) throw new Error(`Landing API returned ${response.status}`);
    const payload = await response.json() as { data?: LandingContent };
    if (!payload.data?.hero || !payload.data.why_us || !payload.data.reviews || !Array.isArray(payload.data.partners)) {
      throw new Error("Landing API returned an invalid payload");
    }
    return { ...payload.data, sections: Object.fromEntries(Object.entries(sectionDefaults).map(([key, value]) => [key, { ...value, ...payload.data?.sections?.[key as keyof typeof sectionDefaults] }])) as typeof sectionDefaults, partners_section: { ...defaultLandingContent.partners_section, ...payload.data.partners_section }, hero: { ...defaultLandingContent.hero, ...payload.data.hero }, about: { ...defaultLandingContent.about, ...payload.data.about }, identity: { ...defaultLandingContent.identity, ...payload.data.identity } };
  } catch (error) {
    console.error("Unable to load landing content:", error);
    return defaultLandingContent;
  }
});

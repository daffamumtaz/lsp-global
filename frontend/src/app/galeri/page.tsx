import { getLandingContent } from "@/lib/landing";
import type { Metadata } from "next";
import { Suspense } from "react";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { GalleryBrowser } from "@/components/gallery/gallery-browser";
import { getPublicContent } from "@/lib/content-api";

export const metadata: Metadata = {
  title: "Galeri | LSP TIK Global",
  description: "Jelajahi album foto dan video kegiatan sertifikasi LSP TIK Global berdasarkan tahun.",
};

export default async function GalleryPage() {
  const content = await getPublicContent();
  const { sections } = await getLandingContent();
  return <>
    <Header currentPage="gallery" />
    <main id="main" className="bg-white">
      <InternalHero white id="gallery-title" title={sections.gallery_hero.title}>{sections.gallery_hero.subtitle}</InternalHero>
      <Suspense fallback={<p className="mx-auto max-w-[1252px] px-6 py-16 text-center" role="status">Memuat galeri…</p>}>
        <GalleryBrowser activities={content.gallery} heading={sections.gallery_albums} yearHeading={sections.gallery_year} />
      </Suspense>
    </main>
    <Footer />
  </>;
}

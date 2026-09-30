import type { Metadata } from "next";
import { Suspense } from "react";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { GalleryBrowser } from "@/components/gallery/gallery-browser";
import { sampleGallery } from "@/lib/gallery-samples";

export const metadata: Metadata = {
  title: "Galeri | LSP TIK Global",
  description: "Jelajahi album foto dan video kegiatan sertifikasi LSP TIK Global berdasarkan tahun.",
};

export default function GalleryPage() {
  return <>
    <Header currentPage="gallery" />
    <main id="main">
      <InternalHero id="gallery-title" title="Galeri">Dokumentasi momen-momen berharga dari berbagai program sertifikasi yang telah kami selenggarakan.</InternalHero>
      <Suspense fallback={<p className="mx-auto max-w-[1252px] px-6 py-16 text-center" role="status">Memuat galeri…</p>}>
        <GalleryBrowser activities={sampleGallery} />
      </Suspense>
    </main>
    <Footer />
  </>;
}

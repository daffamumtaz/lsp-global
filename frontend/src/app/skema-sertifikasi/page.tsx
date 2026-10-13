import { getLandingContent } from "@/lib/landing";
import type { Metadata } from "next";
import { Suspense } from "react";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { SchemeBrowser } from "@/components/schemes/scheme-browser";
import { getPublicContent } from "@/lib/content-api";

export const metadata: Metadata = { title: "Skema Sertifikasi | LSP TIK Global", description: "Temukan skema sertifikasi berdasarkan bidang, metode pelaksanaan, dan level KKNI." };
export default async function SchemesPage() {
  const content = await getPublicContent();
  const { sections } = await getLandingContent();
  return <><Header currentPage="schemes" /><main id="main" className="bg-white">
    <InternalHero white id="schemes-title" title={sections.schemes_hero.title}>{sections.schemes_hero.subtitle}</InternalHero>
    <Suspense fallback={<p className="px-6 py-16 text-center" role="status">Memuat skema sertifikasi…</p>}><SchemeBrowser schemes={content.schemes} /></Suspense>
  </main><Footer /></>;
}

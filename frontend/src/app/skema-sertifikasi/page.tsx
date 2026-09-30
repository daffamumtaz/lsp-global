import type { Metadata } from "next";
import { Suspense } from "react";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { SchemeBrowser } from "@/components/schemes/scheme-browser";
import { sampleSchemes } from "@/lib/scheme-samples";

export const metadata: Metadata = { title: "Skema Sertifikasi | LSP TIK Global", description: "Temukan skema sertifikasi berdasarkan bidang, metode pelaksanaan, dan level KKNI." };
export default function SchemesPage() {
  return <><Header currentPage="schemes" /><main id="main">
    <InternalHero id="schemes-title" title="Skema Sertifikasi">Temukan skema sertifikasi kompetensi sesuai bidang dan kebutuhan profesional Anda.</InternalHero>
    <Suspense fallback={<p className="px-6 py-16 text-center" role="status">Memuat skema sertifikasi…</p>}><SchemeBrowser schemes={sampleSchemes} /></Suspense>
  </main><Footer /></>;
}

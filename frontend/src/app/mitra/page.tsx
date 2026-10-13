import { getLandingContent } from "@/lib/landing";
import type { Metadata } from "next";
import Link from "next/link";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { PartnerGrid } from "@/components/partners/partner-grid";
import { getPublicContent } from "@/lib/content-api";
import { normalizePartners } from "@/lib/partners";
import styles from "./page.module.css";

export const metadata: Metadata = { title: "Semua Mitra | LSP TIK Global", description: "Daftar seluruh Tempat Uji Kompetensi (TUK) mitra LSP TIK Global." };
export default async function PartnersPage() {
  const content = await getPublicContent();
  const { sections } = await getLandingContent();
  const partners = normalizePartners(content.tuks).filter(partner => partner.logo_url);
  return <>
    <Header currentPage="partners" />
    <main id="main">
      <InternalHero id="partners-title" title={sections.partners_hero.title}>{sections.partners_hero.subtitle}</InternalHero>
      <section className={styles.content} aria-labelledby="partners-list-title">
        <Link className={styles.back} href="/#mitra">← Kembali ke Beranda</Link>
        <h2 id="partners-list-title" className="sr-only">Daftar semua mitra</h2>
        {content.unavailable ? <p role="alert">Data mitra belum dapat dimuat. Muat ulang halaman untuk mencoba lagi.</p>
          : partners.length ? <PartnerGrid partners={partners} /> : <p>Data mitra belum tersedia.</p>}
      </section>
    </main>
    <Footer />
  </>;
}

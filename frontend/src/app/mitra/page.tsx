import type { Metadata } from "next";
import Link from "next/link";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { PartnerGrid } from "@/components/partners/partner-grid";
import { VisualMotif } from "@/components/landing/visual-motif";
import { getLandingContent } from "@/lib/landing";
import { normalizePartners, searchPartners } from "@/lib/partners";
import styles from "./page.module.css";

export const metadata: Metadata = { title: "Semua Mitra | LSP TIK Global", description: "Jelajahi perusahaan dan institusi dalam jaringan mitra LSP TIK Global." };
export default async function PartnersPage({ searchParams }: { searchParams: Promise<Record<string, string | string[] | undefined>> }) {
  const [content, params] = await Promise.all([getLandingContent(), searchParams]);
  const query = typeof params.q === "string" ? params.q.trim() : "";
  const partners = normalizePartners(content.partners);
  const results = searchPartners(partners, query);
  return <>
    <Header currentPage="partners" />
    <main id="main">
      <InternalHero id="partners-title" title="Mitra Kami">Bersama perusahaan dan institusi pendidikan, kami membangun jaringan kompetensi profesional.</InternalHero>
      <section className={styles.content} aria-labelledby="partners-list-title">
        <Link className={styles.back} href="/#mitra">← Kembali ke Beranda</Link>
        <h2 id="partners-list-title" className="sr-only">Daftar semua mitra</h2>
        <form method="get" action="/mitra" role="search" className={styles.search}>
          <label>Cari mitra<input key={query} name="q" type="search" defaultValue={query} autoComplete="off" placeholder="Contoh: Jobhun…" /></label>
          <button type="submit">Cari mitra</button>{query && <Link href="/mitra">Reset pencarian</Link>}
        </form>
        <p className={styles.count} role="status">{query ? `${results.length} dari ${partners.length} mitra ditemukan` : `${partners.length} mitra tersedia`}</p>
        <div className={styles.ecosystem}>
          {results.length > 0 && <VisualMotif className={styles.network} />}
          <PartnerGrid partners={results} />
        </div>
        {!results.length && <div className={styles.empty}><h3>{query ? "Mitra tidak ditemukan" : "Daftar mitra belum tersedia"}</h3><p>{query ? "Coba nama mitra lain atau reset pencarian." : "Informasi mitra akan ditampilkan setelah tersedia."}</p>{query && <Link href="/mitra">Lihat semua mitra</Link>}</div>}
      </section>
    </main>
    <Footer />
  </>;
}

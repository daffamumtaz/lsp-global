import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { SchemeDetailTabs } from "@/components/schemes/scheme-detail-tabs";
import { getRegistrationUrl } from "@/lib/contact";
import { getLandingContent } from "@/lib/landing";
import { getPublicContent } from "@/lib/content-api";
import { formatSchemePrice, methodLabels, readSchemeState, schemeHref } from "@/lib/schemes";
import styles from "./page.module.css";
import shared from "@/components/schemes/schemes.module.css";

type Props = { params: Promise<{ slug: string }>; searchParams: Promise<Record<string, string | string[] | undefined>> };
export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  const { schemes, unavailable } = await getPublicContent();
  if (unavailable) return { title: "Skema sementara tidak tersedia | LSP TIK Global", robots: { index: false, follow: true } };
  const scheme = schemes.find(item => item.slug === slug);
  if (!scheme) return { title: "Skema tidak ditemukan | LSP TIK Global", robots: { index: false, follow: true } };
  return { title: `${scheme.name} | LSP TIK Global`, description: `Informasi skema ${scheme.name}, unit kompetensi, dan persyaratan sertifikasi.` };
}
export default async function SchemeDetailPage({ params, searchParams }: Props) {
  const { slug } = await params;
  const { schemes, unavailable } = await getPublicContent();
  if (unavailable) return <><Header currentPage="schemes" /><main id="main" className="bg-white">
    <InternalHero white id="scheme-title" title="Skema sementara tidak tersedia">Informasi skema belum dapat dimuat. Silakan coba lagi beberapa saat lagi.</InternalHero>
    <div className={`${styles.content} ${styles.recoveryActions}`}>
      <a className={shared.primary} href={`/skema-sertifikasi/${encodeURIComponent(slug)}`}>Coba lagi</a>
      <Link className={shared.secondary} href="/skema-sertifikasi">Kembali ke daftar skema</Link>
    </div>
  </main><Footer /></>;
  const scheme = schemes.find(item => item.slug === slug);
  if (!scheme) notFound();
  const query = new URLSearchParams();
  Object.entries(await searchParams).forEach(([key, value]) => { if (Array.isArray(value)) value.forEach(item => query.append(key, item)); else if (value !== undefined) query.set(key, value); });
  const backHref = schemeHref(readSchemeState(query, schemes.map(item => item.category)));
  const { identity, sections } = await getLandingContent();
  const registration = getRegistrationUrl(identity, scheme.name);
  return <><Header currentPage="schemes" /><main id="main" className="bg-white">
    <section className={styles.hero} style={{ backgroundImage: `url(${JSON.stringify(scheme.thumbnail)})` }} aria-labelledby="scheme-title">
      <div className={styles.heroInner}>
        <Link className={styles.backLink} href={backHref}>← Kembali ke daftar skema</Link>
        <h1 id="scheme-title">{scheme.name}</h1>
        <p className={styles.category}>{scheme.category}</p>
        <dl className={styles.metadata}>
          <div><dt>Level KKNI</dt><dd>{scheme.level}</dd></div>
          <div><dt>Jenis skema</dt><dd>{scheme.type}</dd></div>
          <div><dt>Metode pelaksanaan</dt><dd>{scheme.methods.map(method => methodLabels[method]).join(" & ")}</dd></div>
          <div><dt>Unit kompetensi</dt><dd>{scheme.units.length} unit</dd></div>
        </dl>
        <div className={styles.actions}>
          <p className={scheme.price === null ? styles.priceMissing : styles.price} aria-label="Biaya sertifikasi">{formatSchemePrice(scheme.price)}</p>
          <a href={registration} target="_blank" rel="noopener noreferrer" className={shared.primary}>Daftar Sekarang <span aria-hidden="true">↗</span><span className="sr-only">{registration.startsWith("https://wa.me/") ? " melalui WhatsApp" : ""} (tab baru)</span></a>
        </div>
      </div>
    </section>
    <div className={styles.content}>
      <SchemeDetailTabs headings={sections} units={scheme.units} basicRequirements={scheme.basicRequirements} administrativeRequirements={scheme.administrativeRequirements} />
    </div>
  </main><Footer /></>;
}

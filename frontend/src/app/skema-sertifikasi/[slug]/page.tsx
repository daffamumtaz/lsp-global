import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Footer, Header } from "@/components/landing/chrome";
import { SchemeDetailTabs } from "@/components/schemes/scheme-detail-tabs";
import { contactInfo } from "@/lib/contact";
import { sampleSchemes } from "@/lib/scheme-samples";
import { formatSchemePrice, methodLabels, readSchemeState, registrationHref, schemeHref } from "@/lib/schemes";
import styles from "./page.module.css";
import shared from "@/components/schemes/schemes.module.css";

type Props = { params: Promise<{ slug: string }>; searchParams: Promise<Record<string, string | string[] | undefined>> };
export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  const scheme = sampleSchemes.find(item => item.slug === slug);
  return { title: `${scheme?.name ?? "Skema tidak ditemukan"} | LSP TIK Global`, description: scheme ? `Informasi skema ${scheme.name}, unit kompetensi, dan persyaratan sertifikasi.` : undefined };
}
export default async function SchemeDetailPage({ params, searchParams }: Props) {
  const { slug } = await params;
  const scheme = sampleSchemes.find(item => item.slug === slug);
  if (!scheme) notFound();
  const query = new URLSearchParams();
  Object.entries(await searchParams).forEach(([key, value]) => { if (Array.isArray(value)) value.forEach(item => query.append(key, item)); else if (value !== undefined) query.set(key, value); });
  const backHref = schemeHref(readSchemeState(query, sampleSchemes.map(item => item.category)));
  const registration = registrationHref(scheme.registrationUrl) ?? `https://wa.me/${contactInfo.whatsappNumber}?text=${encodeURIComponent(`Halo LSP TIK Global, saya ingin mendaftar sertifikasi skema ${scheme.name}.`)}`;
  return <><Header currentPage="schemes" /><main id="main">
    <nav className={styles.breadcrumb} aria-label="Breadcrumb"><Link href="/">Beranda</Link><span aria-hidden="true">/</span><Link href={backHref}>Skema Sertifikasi</Link><span aria-hidden="true">/</span><span aria-current="page">{scheme.name}</span></nav>
    <section className={styles.hero} aria-labelledby="scheme-title" style={{ backgroundImage: `url(${JSON.stringify(scheme.thumbnail)})` }}>
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
      <SchemeDetailTabs units={scheme.units} basicRequirements={scheme.basicRequirements} administrativeRequirements={scheme.administrativeRequirements} />
    </div>
  </main><Footer /></>;
}

import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { UnitList } from "@/components/schemes/unit-list";
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
  const registration = registrationHref(scheme.registrationUrl);
  const whatsapp = `https://wa.me/6285195533360?text=${encodeURIComponent(`Halo LSP TIK Global, saya ingin bertanya tentang skema ${scheme.name}, persyaratan, biaya, dan jadwal sertifikasinya.`)}`;
  return <><Header currentPage="schemes" /><main id="main">
    <nav className={styles.breadcrumb} aria-label="Breadcrumb"><Link href="/">Beranda</Link><span aria-hidden="true">/</span><Link href={backHref}>Skema Sertifikasi</Link><span aria-hidden="true">/</span><span aria-current="page">{scheme.name}</span></nav>
    <InternalHero id="scheme-title" title={scheme.name}>{scheme.category}</InternalHero>
    <div className={styles.content}>
      <Link className={styles.backLink} href={backHref}>← Kembali ke daftar skema</Link>
      <section className={styles.overview} aria-labelledby="scheme-overview"><h2 id="scheme-overview">Ringkasan skema</h2><dl>
        <div><dt>Level KKNI</dt><dd>{scheme.level}</dd></div><div><dt>Jenis skema</dt><dd>{scheme.type}</dd></div>
        <div><dt>Metode pelaksanaan</dt><dd>{scheme.methods.map(method => methodLabels[method]).join(" & ")}</dd></div><div><dt>Unit kompetensi</dt><dd>{scheme.units.length} unit</dd></div>
        <div><dt>Biaya sertifikasi</dt><dd>{formatSchemePrice(scheme.price)}</dd></div>
      </dl><div className={styles.actions}><a href={whatsapp} target="_blank" rel="noopener noreferrer" className={shared.primary}>Tanyakan skema ini <span className="sr-only">melalui WhatsApp (tab baru)</span>↗</a>{registration && <a href={registration} target="_blank" rel="noopener noreferrer" className={shared.secondary}>Daftar sekarang <span className="sr-only">(tab baru)</span>↗</a>}</div></section>
      <h2 className="sr-only">Unit kompetensi</h2><UnitList units={scheme.units} />
      <section className={styles.requirements} aria-labelledby="basic-requirements"><h2 id="basic-requirements">Persyaratan dasar</h2><p>Penuhi salah satu persyaratan berikut. Konfirmasikan ketentuan resmi kepada penyelenggara.</p><ul>{scheme.basicRequirements.map(item => <li key={item}>{item}</li>)}</ul></section>
      <section className={styles.requirements} aria-labelledby="administrative-requirements"><h2 id="administrative-requirements">Persyaratan administrasi</h2><p>Siapkan semua berkas berikut sebelum mendaftar.</p><ul>{scheme.administrativeRequirements.map(item => <li key={item}>{item}</li>)}</ul></section>
    </div>
  </main><Footer /></>;
}

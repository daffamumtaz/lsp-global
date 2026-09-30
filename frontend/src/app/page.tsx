import Image from "next/image";
import Link from "next/link";
import { BenefitCard, ReviewCard } from "@/components/landing/cards";
import { SchemeCard } from "@/components/schemes/scheme-card";
import { sampleSchemes } from "@/lib/scheme-samples";
import { PartnerGrid } from "@/components/partners/partner-grid";
import { normalizePartners } from "@/lib/partners";
import { Footer, Header } from "@/components/landing/chrome";
import { MediaImage, SearchField, SectionHeading } from "@/components/landing/ui";
import { VisualMotif } from "@/components/landing/visual-motif";
import { getLandingContent } from "@/lib/landing";

function BrandText({ text, className = "" }: { text: string; className?: string }) {
  const marker = "LSP TIK Global";
  const index = text.indexOf(marker);
  if (index < 0) return text;
  return <>{text.slice(0, index)}<span className={className}>{marker}</span>{text.slice(index + marker.length)}</>;
}

export default async function Home() {
  const content = await getLandingContent();
  const partners = normalizePartners(content.partners).filter(partner => partner.logo_url).slice(0, 10);

  return (
    <>
      <Header />
      <main id="main" className="home-page">
        <section className="hero relative isolate overflow-hidden bg-brand text-white" aria-labelledby="hero-title">
          <Image src="/images/office-boardroom.webp" alt="" fill preload sizes="100vw" className="hero-office" />
          <div className="hero-overlay" aria-hidden="true" />
          <VisualMotif className="hero-network" />
          <div className="hero-content mx-auto grid max-w-[1292px] items-start gap-12 px-6 lg:grid-cols-[1.22fr_1fr] xl:gap-[103px]">
            <div>
              <h1 id="hero-title" className="max-w-[628px] text-[36px] leading-[1.12] font-bold tracking-[-0.03em] sm:text-[40px] lg:text-[56px]">{content.hero.hook}</h1>
              <p className="mt-5 max-w-[628px] text-lg leading-relaxed"><BrandText text={content.hero.description} className="font-bold" /></p>
              <div className="mt-10 flex flex-wrap items-center gap-4"><SearchField /><Link href="/kontak" className="hero-contact flex min-h-[52px] w-[194px] items-center justify-center rounded-lg border border-white font-jakarta text-base font-bold">Hubungi Kami</Link></div>
            </div>
            {content.hero.video_url ? <video controls preload="metadata" poster={content.hero.poster_url ?? "/figma/imgRectangle374.png"} className="hero-video mt-[13px] aspect-[515/290] w-full max-w-[515px] overflow-hidden rounded-xl bg-black"><source src={content.hero.video_url} />Browser Anda tidak mendukung pemutaran video.</video> : <div className="hero-video mt-[13px] w-full max-w-[515px] overflow-hidden rounded-xl"><MediaImage src={content.hero.poster_url} fallback="imgRectangle374.png" alt="Video profil LSP TIK Global" width={515} height={290} className="aspect-[515/290] w-full object-cover" /></div>}
          </div>
        </section>

        <section id="tentang-kami" className="benefits-section relative isolate overflow-hidden px-6" aria-label="Mengapa memilih kami">
          <SectionHeading subtitle={content.why_us.description}><BrandText text={content.why_us.hook} className="text-brand-dark" /></SectionHeading>
          <div className="benefits-grid relative mx-auto grid lg:grid-cols-3">{content.why_us.features.map((benefit, index) => <BenefitCard key={`${benefit.title}-${index}`} {...benefit} />)}</div>
        </section>

        <section aria-label="Statistik LSP TIK Global" className="statistics-section bg-section px-6 text-brand-dark">
          <dl className="statistics-panel">{content.statistics.map((stat, index) => <div key={`${stat.value}-${index}`}><dt>{stat.label}</dt><dd>{stat.value}</dd></div>)}</dl>
        </section>

        <section id="sertifikasi" className="schemes-section relative isolate overflow-hidden px-6" aria-label="Skema sertifikasi">
          <VisualMotif variant="framework" className="section-framework" />
          <SectionHeading subtitle="Cari dan pilih skema sertifikasi yang sesuai dengan kebutuhan Anda">Skema Sertifikasi</SectionHeading>
          <div className="scheme-controls relative flex flex-wrap justify-center gap-7">
            <Link href="/skema-sertifikasi" className="flex min-h-[44px] items-center justify-center rounded-lg bg-brand-dark px-5 py-3 font-semibold text-white hover:bg-[#003475]">Lihat Seluruh Sertifikasi</Link>
          </div>
          <div className="home-card-grid relative mx-auto mt-8 grid lg:grid-cols-3">{sampleSchemes.slice(0, 3).map(scheme => <SchemeCard key={scheme.id} scheme={scheme} />)}</div>
          <p className="relative mt-[33px] text-center text-base leading-none tracking-[-0.03em] text-muted italic underline"><Link href="/kontak">Butuh informasi lanjut mengenai sertifikasi?</Link></p>
        </section>

        <section className="reviews-section bg-section px-6" aria-label="Review kami">
          <SectionHeading subtitle={content.reviews.description}>{content.reviews.hook}</SectionHeading>
          <div className="reviews-grid mx-auto grid lg:grid-cols-3">{content.reviews.items.map((review, index) => <ReviewCard key={`${review.name}-${index}`} review={review} first={index === 0} />)}</div>
        </section>

        <section id="mitra" className="partners-section relative isolate overflow-hidden px-6" aria-label="Mitra kami">
          {partners.length > 0 && <VisualMotif className="partners-network" />}
          <SectionHeading subtitle="Dipercaya oleh berbagai perusahaan dan institusi pendidikan">Mitra Kami</SectionHeading>
          <div className="relative mx-auto mt-10 max-w-[1298px]">
            <PartnerGrid partners={partners} />
          </div>
          <div className="relative mt-8 text-center"><Link href="/mitra" className="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-dark px-5 py-3 font-semibold text-white hover:bg-[#003475]">Lihat Seluruh Mitra</Link></div>
        </section>
      </main>
      <Footer />
    </>
  );
}

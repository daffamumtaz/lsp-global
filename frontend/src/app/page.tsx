import Image from "next/image";
import Link from "next/link";
import { BenefitCard } from "@/components/landing/cards";
import { ReviewCarousel } from "@/components/landing/review-carousel";
import { ProfileVideo } from "@/components/landing/profile-video";
import { SchemeCard } from "@/components/schemes/scheme-card";
import { getPublicContent } from "@/lib/content-api";
import { PartnerCarousel } from "@/components/partners/partner-carousel";
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
  const [content, directory] = await Promise.all([getLandingContent(), getPublicContent()]);
  const highlights = content.highlighted_scheme_ids.length
    ? content.highlighted_scheme_ids.map(id => directory.schemes.find(scheme => scheme.id === id)).filter(scheme => scheme !== undefined).slice(0, 5)
    : directory.schemes.slice(0, 5);
  const partners = normalizePartners(content.partners).filter(partner => partner.logo_url);

  return (
    <>
      <Header />
      <main id="main" className="home-page">
        <section className="hero relative isolate overflow-hidden bg-brand text-white" aria-labelledby="hero-title">
          <Image src={content.hero.background_url ?? "/images/office-boardroom.webp"} unoptimized={Boolean(content.hero.background_url)} alt="" fill preload sizes="100vw" className="hero-office" />
          <div className="hero-overlay" aria-hidden="true" />
          <VisualMotif className="hero-network" />
          <div className="hero-content mx-auto grid items-center gap-8">
            <div className="hero-copy">
              <h1 id="hero-title" className="hero-title leading-[1.12] font-bold tracking-[-0.03em]">{content.hero.title}</h1>
              <div className="hero-support mt-5 grid max-w-[628px] gap-0">
                <p>{content.hero.hook}</p>
                {content.hero.description && <p>{content.hero.description}</p>}
              </div>
              <div className="mt-7"><SearchField /></div>
            </div>
            <div className="hero-strengths lg:self-center">
              <ul aria-label="Keunggulan" className="grid gap-4">{content.hero.strengths.map((title, index) => <li key={index} className="flex items-start gap-3"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="mt-1 size-5 shrink-0"><path d="m5 12 4 4L19 6" /></svg><span>{title}</span></li>)}</ul>
            </div>
          </div>
        </section>

        <section id="tentang-kami" className="home-about-section bg-section px-6" aria-labelledby="about-title">
          <h2 id="about-title" className="mb-8 text-center text-[length:var(--text-section)] leading-tight font-bold tracking-[-0.025em] text-brand-dark">{content.about.hook}</h2>
          <div className="grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
            <div id="video-profil" className="min-w-0">
              {content.hero.video_url ? <ProfileVideo src={content.hero.video_url} poster={content.hero.poster_url ?? (content.hero.video_url.endsWith("/media/profile-video") ? "/profile-video-poster.jpg" : "/figma/imgRectangle374.png")} /> : <div><MediaImage src={content.hero.poster_url} fallback="imgRectangle374.png" alt="Profil LSP TIK Global" width={720} height={405} className="aspect-video w-full rounded-xl object-cover" /><p className="mt-3 text-sm text-muted">Video profil belum tersedia.</p></div>}
            </div>
            <div className="home-about-copy min-w-0">
              <div className="mt-6 grid gap-4 text-muted">{content.about.description.split(/\n\s*\n/).filter(Boolean).map((paragraph, index) => <p key={index}>{paragraph}</p>)}</div>
              <Link href="/tentang-kami" className="action-button mt-6 inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-dark px-5 py-3 font-semibold text-white">Lihat Selengkapnya</Link>
            </div>
          </div>
        </section>

        <section id="mengapa-kami" className="benefits-section relative isolate overflow-hidden px-6" aria-label="Mengapa memilih kami">
          <SectionHeading subtitle={content.why_us.description}><BrandText text={content.why_us.hook} className="text-brand-dark" /></SectionHeading>
          <div className="benefits-grid relative mx-auto grid lg:grid-cols-3">{content.why_us.features.map((benefit, index) => <BenefitCard key={`${benefit.title}-${index}`} {...benefit} />)}</div>
        </section>

        {content.statistics.length > 0 && <section aria-label="Statistik LSP TIK Global" className="statistics-section bg-section px-6 text-brand-dark">
          <dl className="statistics-panel">{content.statistics.map((stat, index) => <div key={`${stat.value}-${index}`}><dt><Link className="statistic-link" href={["/skema-sertifikasi", "/informasi#tuk", "/informasi#asesor"][index] ?? "/informasi"} aria-label={`Lihat data ${stat.label.replace(/\n/g, " ")}`}>{stat.label}</Link></dt><dd>{stat.value}</dd></div>)}</dl>
        </section>}

        <section id="sertifikasi" className="schemes-section relative isolate overflow-hidden px-6" aria-label="Skema sertifikasi">
          <SectionHeading subtitle={content.sections.home_schemes.subtitle}>{content.sections.home_schemes.title}</SectionHeading>
          <div className="scheme-controls relative flex flex-wrap justify-center gap-7">
            <Link href="/skema-sertifikasi" className="action-button flex min-h-[44px] items-center justify-center rounded-lg bg-brand-dark px-5 py-3 font-semibold text-white">Lihat Seluruh Sertifikasi</Link>
          </div>
          <div className="home-card-grid relative mx-auto mt-8 grid lg:grid-cols-3">{highlights.map(scheme => <SchemeCard key={scheme.id} scheme={scheme} />)}</div>
          <p className="relative mt-[33px] text-center text-base leading-none tracking-[-0.03em] text-muted italic"><Link href="/kontak">Butuh informasi lanjut mengenai sertifikasi?</Link></p>
        </section>

        {content.reviews.items.length > 0 && <section className="reviews-section bg-section px-6" aria-label="Review kami">
          <SectionHeading subtitle={content.reviews.description}>{content.reviews.hook}</SectionHeading>
          <ReviewCarousel reviews={content.reviews.items} />
        </section>}

        <section id="mitra" className="partners-section relative isolate overflow-hidden px-6" aria-label="Mitra kami">
          <SectionHeading subtitle={content.partners_section.description}>{content.partners_section.hook}</SectionHeading>
          <div className="relative mx-auto mt-10 max-w-[1298px]">
            <PartnerCarousel partners={partners} />
          </div>
          <div className="relative mt-8 text-center"><Link href="/mitra" className="action-button inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-dark px-5 py-3 font-semibold text-white">Lihat Seluruh Mitra</Link></div>
        </section>
      </main>
      <Footer />
    </>
  );
}

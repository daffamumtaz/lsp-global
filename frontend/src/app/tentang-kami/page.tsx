import type { Metadata } from "next";
import { InternalHero } from "@/components/landing/internal-hero";
import { Footer, Header } from "@/components/landing/chrome";
import { FigmaImage, MediaImage } from "@/components/landing/ui";
import { OrganizationChart } from "@/components/about/organization-chart";
import { getAboutContent } from "@/lib/about";
import styles from "./page.module.css";

export const metadata: Metadata = {
  title: "Tentang Kami | LSP TIK Global",
  description: "Profil perusahaan, visi dan misi, struktur, serta lisensi LSP TIK Global.",
};

export default async function AboutPage() {
  const content = await getAboutContent();
  return (
    <>
      <Header currentPage="about" />
      <main id="main">
        <InternalHero id="about-title" title="Tentang Kami">Mengenal lebih dekat perjalanan, visi, dan dedikasi <strong>LSP TIK Global</strong> dalam mencetak Sumber Daya Manusia (SDM) yang unggul di Indonesia.</InternalHero>

        <section className={styles.profile} aria-labelledby="profile-title">
          <div className={styles.profileInner}>
            <div className={styles.profileVisual}>
              {content.profile.photo_urls.length ? content.profile.photo_urls.map((src, index) => (
                <MediaImage key={`${src}-${index}`} src={src} fallback="profile-company.png" alt={`Dokumentasi kegiatan LSP TIK Global ${index + 1}`} width={634} height={601} className={styles.profileImage} />
              )) : <FigmaImage file="profile-company.png" alt="Dokumentasi kegiatan sertifikasi LSP TIK Global" width={634} height={601} className={styles.profileImage} preload />}
            </div>
            <div className={styles.profileCopy}>
              <h2 id="profile-title">{content.profile.hook}</h2>
              <div className={styles.profileText} dangerouslySetInnerHTML={{ __html: content.profile.description }} />
            </div>
          </div>
        </section>

        <section id="visi-misi" className={styles.vision} aria-labelledby="vision-title">
          <div className={styles.sectionInner}>
            <h2 id="vision-title" className={styles.sectionTitle}>{content.vision.hook}</h2>
            <div className={styles.visionCards}>
              <article className={styles.quoteCard}>
                <FigmaImage file="quote-mark.svg" width={101} height={77} className={styles.quoteIcon} />
                <div className={styles.quoteContent}><h3>Visi</h3><div className={styles.richText} dangerouslySetInnerHTML={{ __html: content.vision.vision }} /></div>
              </article>
              <article className={styles.quoteCard}>
                <FigmaImage file="quote-mark.svg" width={101} height={77} className={styles.quoteIcon} />
                <div className={styles.quoteContent}><h3>Misi</h3><div className={styles.richText} dangerouslySetInnerHTML={{ __html: content.vision.mission }} /></div>
              </article>
            </div>
          </div>
        </section>

        <section id="struktur" className={styles.structure} aria-labelledby="structure-title">
          <div className={styles.sectionInner}>
            <div className={styles.structureHeading}>
              <h2 id="structure-title">Struktur Perusahaan</h2>
              <p>Susunan manajemen dan pelaksana tugas <strong>LSP TIK Global</strong> dalam mewujudkan sertifikasi yang independen dan bermutu.</p>
            </div>
            <div className={styles.structureImageWrap}>
              <OrganizationChart nodes={content.organization} />
            </div>
          </div>
        </section>

        <section id="lisensi" className={styles.legal} aria-labelledby="legal-title">
          <div className={styles.sectionInner}>
            <div className={styles.legalHeading}><h2 id="legal-title" className={styles.sectionTitle}>Lisensi &amp; Legalitas</h2><p>Sebagai komitmen terhadap mutu dan profesionalisme, operasional <strong>LSP TIK Global</strong> telah diakui dan dilisensi secara resmi oleh negara.</p></div>
            <div className={styles.licenseCards}>
              {content.certificates.map(({ id, title, image_url, fallback_image, description }) => (
                <article className={styles.licenseCard} key={id}>
                  {(image_url || fallback_image) && <MediaImage src={image_url} fallback={fallback_image ?? "license-certificate.png"} alt={title} width={266} height={357} className={styles.licenseImage} />}
                  <div className={styles.licenseContent}><h3>{title}</h3><p>{description}</p></div>
                </article>
              ))}
              {content.certificates.length === 0 && <p className={styles.empty}>Belum ada sertifikat yang ditampilkan.</p>}
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </>
  );
}

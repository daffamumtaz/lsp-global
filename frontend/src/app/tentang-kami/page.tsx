import { getLandingContent } from "@/lib/landing";
import type { Metadata } from "next";
import { InternalHero } from "@/components/landing/internal-hero";
import { Footer, Header } from "@/components/landing/chrome";
import { FigmaImage } from "@/components/landing/ui";
import { ProfileCollage } from "@/components/about/profile-collage";
import { CertificateImage } from "@/components/about/certificate-image";
import { OrganizationChart } from "@/components/about/organization-chart";
import { getOrganization } from "@/lib/organization-api";
import { getAboutContent } from "@/lib/about";
import styles from "./page.module.css";

export const metadata: Metadata = {
  title: "Tentang Kami | LSP TIK Global",
  description: "Profil perusahaan, visi dan misi, struktur, serta lisensi LSP TIK Global.",
};

export default async function AboutPage() {
  const [content, organization] = await Promise.all([getAboutContent(), getOrganization()]);
  const { sections } = await getLandingContent();
  return (
    <>
      <Header currentPage="about" />
      <main id="main">
        <InternalHero white id="about-title" title={sections.about_hero.title}>{sections.about_hero.subtitle}</InternalHero>

        <section className={styles.profile} aria-labelledby="profile-title">
          <div className={styles.profileInner}>
            <ProfileCollage photos={content.profile.photo_urls} />
            <div className={styles.profileCopy}>
              <h2 id="profile-title">{content.profile.hook}</h2>
              {content.profile.description && <div className={styles.profileText} dangerouslySetInnerHTML={{ __html: content.profile.description }} />}
            </div>
          </div>
        </section>

        <section id="visi-misi" className={styles.vision} aria-labelledby="vision-title">
          <div className={styles.sectionInner}>
            <h2 id="vision-title" className={styles.sectionTitle}>{content.vision.hook}</h2>
            <div className={styles.visionCards}>
              <article className={styles.quoteCard}>
                <FigmaImage file="quote-mark.svg" width={101} height={77} className={styles.quoteIcon} />
                <div className={styles.quoteContent}><h3>{sections.about_vision.title}</h3>{sections.about_vision.subtitle && <p>{sections.about_vision.subtitle}</p>}<div className={styles.richText} dangerouslySetInnerHTML={{ __html: content.vision.vision }} /></div>
              </article>
              <article className={styles.quoteCard}>
                <FigmaImage file="quote-mark.svg" width={101} height={77} className={styles.quoteIcon} />
                <div className={styles.quoteContent}><h3>{sections.about_mission.title}</h3>{sections.about_mission.subtitle && <p>{sections.about_mission.subtitle}</p>}<div className={styles.richText} dangerouslySetInnerHTML={{ __html: content.vision.mission }} /></div>
              </article>
            </div>
          </div>
        </section>

        <section id="struktur" className={styles.structure} aria-labelledby="structure-title">
          <div className={styles.sectionInner}>
            <div className={styles.structureHeading}>
              <h2 id="structure-title">{sections.about_structure.title}</h2>
              <p>{sections.about_structure.subtitle}</p>
            </div>
            <div className={styles.structureImageWrap}>
              <OrganizationChart nodes={organization.nodes} unavailable={organization.unavailable} />
            </div>
          </div>
        </section>

        <section id="lisensi" className={styles.legal} aria-labelledby="legal-title">
          <div className={styles.sectionInner}>
            <div className={styles.legalHeading}><h2 id="legal-title" className={styles.sectionTitle}>{sections.about_legal.title}</h2><p>{sections.about_legal.subtitle}</p></div>
            <div className={styles.licenseCards}>
              {content.certificates.map(({ id, title, number, image_url, fallback_image, description }) => (
                image_url || fallback_image ? <CertificateImage key={id} src={image_url ?? `/figma/${fallback_image}`} title={title} description={description} className={styles.licenseImage} cardClassName={styles.licenseCard}>
                  <span className={styles.licenseContent}><span role="heading" aria-level={3}>{title}</span><span className={styles.licenseNumber}>{number || "Nomor belum tersedia"}</span>{description && <span>{description}</span>}</span>
                </CertificateImage> : <article className={styles.licenseCard} key={id}><div className={styles.licenseContent}><h3>{title}</h3><p className={styles.licenseNumber}>{number || "Nomor belum tersedia"}</p>{description && <p>{description}</p>}</div></article>
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

import type { Metadata } from "next";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { DirectoryTable } from "@/components/information/directory-table";
import { getPublicContent } from "@/lib/content-api";
import { AssessmentProcessView } from "@/components/information/assessment-process-view";
import { getAssessmentProcess } from "@/lib/assessment-process-api";
import { getInformationContent } from "@/lib/information-api";
import { tukDirectoryEntry } from "@/lib/tuks";
import styles from "./page.module.css";

export async function generateMetadata(): Promise<Metadata> {
  return (await getInformationContent()).seo;
}

export default async function InformationPage() {
  const [content, copy, assessment] = await Promise.all([getPublicContent(), getInformationContent(), getAssessmentProcess()]);
  return <>
    <Header currentPage="information" />
    <main id="main">
      <InternalHero white id="information-title" title={copy.hero.title}>{copy.hero.description}</InternalHero>
      <section id="tuk" className={styles.section} aria-labelledby="tuk-title">
        <div className={styles.inner}>
          <div className={styles.heading}><h2 id="tuk-title">{copy.tuk.title}</h2>{copy.tuk.description && <p>{copy.tuk.description}</p>}</div>
          <DirectoryTable id="tuk" title={copy.tuk.directory_title} entries={content.tuks.map(tukDirectoryEntry)} tuks={content.tuks} unavailable={content.unavailable} filterKey="province" filterLabel={copy.tuk.filter_label} allFilter={copy.tuk.all_filter} searchPlaceholder={copy.tuk.search_placeholder} copy={copy.directory} columns={[
            { key: "name", label: copy.tuk.name }, { key: "code", label: copy.tuk.code }, { key: "city", label: copy.tuk.city }, { key: "province", label: copy.tuk.province }, { key: "address", label: copy.tuk.address },
          ]} />
        </div>
      </section>
      <section id="asesor" className={`${styles.section} ${styles.alternate}`} aria-labelledby="assessor-title">
        <div className={styles.inner}>
          <div className={styles.heading}><h2 id="assessor-title">{copy.assessor.title}</h2>{copy.assessor.description && <p>{copy.assessor.description}</p>}</div>
          <DirectoryTable id="asesor" title={copy.assessor.directory_title} entries={content.assessors} filterKey="field" filterLabel={copy.assessor.filter_label} allFilter={copy.assessor.all_filter} searchPlaceholder={copy.assessor.search_placeholder} copy={copy.directory} columns={[
            { key: "name", label: copy.assessor.name }, { key: "certificate", label: copy.assessor.certificate }, { key: "field", label: copy.assessor.field },
          ]} />
        </div>
      </section>
      <section id="alur-asesmen" className={styles.section} aria-labelledby="flow-title">
        <div className={styles.inner}>
          <AssessmentProcessView {...assessment} copy={copy.flow} headingId="flow-title" />
        </div>
      </section>
    </main>
    <Footer />
  </>;
}

import type { Metadata } from 'next';
import { Header, Footer } from '@/components/landing/chrome';
import { AssessmentProcessView } from '@/components/information/assessment-process-view';
import { getAssessmentPreview } from '@/lib/assessment-process-api';
import styles from '../../../page.module.css';

export const metadata: Metadata = { title: 'Preview Alur Asesmen | LSP TIK Global', robots: { index: false, follow: false } };
export default async function PreviewPage({ params, searchParams }: { params: Promise<{ slug: string }>; searchParams: Promise<Record<string, string | string[] | undefined>> }) {
  const { slug } = await params;
  const query = await searchParams;
  const result = await getAssessmentPreview({ slug, expires: typeof query.expires === 'string' ? query.expires : '', signature: typeof query.signature === 'string' ? query.signature : '' });
  return <><Header currentPage="information" /><main id="main" className={styles.section}><div className={styles.inner}><h1>Preview Alur Asesmen</h1><AssessmentProcessView {...result} preview /></div></main><Footer /></>;
}

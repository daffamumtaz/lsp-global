import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { AdminPreview } from '@/components/admin/admin-preview';
import { adminPreviewOrigin, isPreviewKind } from '@/lib/admin-preview';

export const metadata: Metadata = { title: 'Preview Admin | LSP TIK Global', robots: { index: false, follow: false } };
export const dynamic = 'force-dynamic';

export default async function PreviewPage({ params }: { params: Promise<{ kind: string }> }) {
  const { kind } = await params;
  if (!isPreviewKind(kind)) notFound();
  return <AdminPreview kind={kind} adminOrigin={adminPreviewOrigin(process.env.ADMIN_PREVIEW_ORIGIN)} />;
}

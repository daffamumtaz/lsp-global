import type { Metadata } from 'next';
import { OrganizationChart } from '@/components/about/organization-chart';
import { getOrganization } from '@/lib/organization-api';

export const metadata: Metadata = { title: 'Struktur Organisasi', robots: { index: false, follow: false } };

export default async function OrganizationPreviewPage() {
  const { nodes, unavailable } = await getOrganization();
  return <main><OrganizationChart nodes={nodes} unavailable={unavailable} /></main>;
}

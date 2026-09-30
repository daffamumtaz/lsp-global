import { connection } from 'next/server';
import { buildOrganizationTree, type OrganizationNode } from './organization';

export async function getOrganization(): Promise<{ nodes: OrganizationNode[]; unavailable: boolean }> {
  await connection();
  const profileUrl = process.env.PROFILE_API_URL ?? new URL('profile-page', process.env.LANDING_API_URL ?? 'http://127.0.0.1:8000/api/landing-page').href;
  const url = process.env.ORGANIZATION_API_URL ?? new URL('organization', profileUrl).href;
  try {
    const response = await fetch(url, { cache: 'no-store', signal: AbortSignal.timeout(5000) });
    if (!response.ok) throw new Error(`Organization API returned ${response.status}`);
    const { data } = await response.json() as { data: OrganizationNode[] };
    buildOrganizationTree(data);
    return { nodes: data, unavailable: false };
  } catch (error) {
    console.error('Unable to load organization:', error);
    return { nodes: [], unavailable: true };
  }
}

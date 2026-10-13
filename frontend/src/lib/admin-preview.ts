import { buildOrganizationTree, type OrganizationNode } from './organization.ts';
import { parseAssessmentProcess, type AssessmentProcess } from './assessment-process.ts';
import { hasCoordinates, type Tuk } from './tuks.ts';

export type PreviewKind = 'organization' | 'process' | 'tuks';
export type TukPreview = Tuk & { is_active: boolean };
export type PreviewUpdate = { type: 'admin-preview:update'; revision: number; draft: boolean } & (
  { kind: 'organization'; data: OrganizationNode[] } |
  { kind: 'process'; data: AssessmentProcess } |
  { kind: 'tuks'; data: TukPreview[] }
);

export function adminPreviewOrigin(value = 'http://localhost:8000'): string {
  const url = new URL(value);
  if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) throw new Error('Invalid admin preview origin');
  return url.origin;
}

export function isPreviewKind(value: string): value is PreviewKind {
  return ['organization', 'process', 'tuks'].includes(value);
}

export function trustedPreviewEvent(event: { origin: string; source: unknown }, parent: unknown, origin: string): boolean {
  return event.source === parent && event.origin === origin;
}

export function parsePreviewUpdate(input: unknown, kind: PreviewKind, lastRevision: number): PreviewUpdate | null {
  if (!input || typeof input !== 'object' || Array.isArray(input)) throw new Error('Pesan preview tidak valid.');
  const message = input as Record<string, unknown>;
  if (message.type !== 'admin-preview:update' || message.kind !== kind ||
      !Number.isSafeInteger(message.revision) || (message.revision as number) < 0 || typeof message.draft !== 'boolean') {
    throw new Error('Pesan preview tidak valid.');
  }
  if ((message.revision as number) <= lastRevision) return null;
  let data;
  if (kind === 'organization') {
    if (!Array.isArray(message.data) || message.data.some(node => !node || typeof node !== 'object')) throw new Error('Struktur organisasi tidak valid.');
    data = message.data as OrganizationNode[];
    buildOrganizationTree(data);
  } else if (kind === 'process') {
    data = parseAssessmentProcess(message.data);
  } else {
    if (!Array.isArray(message.data) || message.data.length > 1000) throw new Error('Daftar TUK tidak valid.');
    const ids = new Set<string>();
    data = message.data.map(value => {
      if (!value || typeof value !== 'object' || Array.isArray(value)) throw new Error('Data TUK tidak valid.');
      const tuk = value as TukPreview;
      if (['id', 'name', 'url', 'logo_url', 'code', 'city', 'province', 'address', 'maps_url'].some(field => typeof value[field] !== 'string') ||
          !tuk.id.trim() || !tuk.name.trim() || ids.has(tuk.id) || typeof tuk.is_active !== 'boolean') throw new Error('Data TUK tidak valid.');
      if (!(tuk.latitude === null && tuk.longitude === null) && !hasCoordinates(tuk)) throw new Error('Koordinat TUK tidak valid. Isi lintang dan bujur bersama, dalam rentang yang benar.');
      ids.add(tuk.id);
      return tuk;
    });
  }
  return { type: 'admin-preview:update', kind, revision: message.revision, draft: message.draft, data } as PreviewUpdate;
}

export type StepType = 'normal' | 'decision' | 'outcome' | 'document' | 'start' | 'end';
export type ConnectionType = 'normal' | 'success' | 'revision';
type Ordered = { id: number; order: number };
type Content = Ordered & { title: string; description: string | null; isActive: boolean };
export type ProcessActor = Ordered & { name: string; label: string; description: string | null; isActive: boolean };
export type ProcessStep = Content & {
  actorId: number | null;
  type: StepType;
  documentReference: string | null;
  informationUrl: string | null;
};
export type ProcessStage = Content & { descriptionHtml?: string | null; steps: ProcessStep[] };
export type ProcessConnection = Ordered & {
  sourceStepId: number;
  targetStepId: number;
  label: string | null;
  type: ConnectionType;
};
export type AssessmentProcess = Content & {
  slug: string;
  actors: ProcessActor[];
  stages: ProcessStage[];
  connections: ProcessConnection[];
};
export type ProcessResult = { process: AssessmentProcess | null; unavailable: boolean };
export type PreviewParams = { slug: string; expires: string; signature: string };

function asRecord(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    throw new Error('Invalid process record');
  }
  return value as Record<string, unknown>;
}

function positiveInteger(value: unknown): number {
  if (typeof value !== 'number' || !Number.isSafeInteger(value) || value < 1) {
    throw new Error('Invalid process number');
  }
  return value;
}

function nonEmptyText(value: unknown): string {
  if (typeof value !== 'string' || !value.trim()) {
    throw new Error('Invalid process text');
  }
  return value;
}

function nullableText(value: unknown): string | null {
  if (value === null || value === undefined || value === '') return null;
  return nonEmptyText(value);
}

function parseVisibility(value: unknown): boolean {
  if (value === undefined) return true;
  if (typeof value !== 'boolean') throw new Error('Invalid process visibility');
  return value;
}

function parseOrdered(value: Record<string, unknown>): Ordered {
  return { id: positiveInteger(value.id), order: positiveInteger(value.order) };
}

function parseContent(value: Record<string, unknown>): Content {
  return {
    ...parseOrdered(value),
    title: nonEmptyText(value.title),
    description: nullableText(value.description),
    isActive: parseVisibility(value.isActive),
  };
}

function parseRows<T extends Ordered>(value: unknown, parse: (row: Record<string, unknown>) => T): T[] {
  if (!Array.isArray(value)) throw new Error('Invalid process collection');
  const result = value.map(row => parse(asRecord(row)));
  if (new Set(result.map(row => row.id)).size !== result.length) {
    throw new Error('Duplicate process ID');
  }
  return result.sort((a, b) => a.order - b.order || a.id - b.id);
}

export function safeInformationUrl(value: string | null): string | null {
  if (!value) return null;
  try {
    const url = new URL(value);
    return url.protocol === 'https:' && !url.username && !url.password ? url.href : null;
  } catch {
    return null;
  }
}

export function parseAssessmentProcess(input: unknown): AssessmentProcess {
  // Tipe TypeScript tidak memvalidasi JSON; periksa bentuk dan relasi sebelum membuat diagram.
  const data = asRecord(input);
  const slug = nonEmptyText(data.slug);
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw new Error('Invalid process slug');

  const actors = parseRows(data.actors, actor => ({
    ...parseOrdered(actor),
    name: nonEmptyText(actor.name),
    label: nonEmptyText(actor.label),
    description: nullableText(actor.description),
    isActive: parseVisibility(actor.isActive),
  }));
  const actorIds = new Set(actors.map(actor => actor.id));
  const stepIds = new Set<number>();

  const parseStep = (step: Record<string, unknown>): ProcessStep => {
    const id = positiveInteger(step.id);
    const actorId = step.actorId === null ? null : positiveInteger(step.actorId);
    if (stepIds.has(id) || (actorId !== null && !actorIds.has(actorId))) {
      throw new Error('Invalid process activity reference');
    }
    if (!['normal', 'decision', 'outcome', 'document', 'start', 'end'].includes(nonEmptyText(step.type))) {
      throw new Error('Invalid activity type');
    }
    stepIds.add(id);
    return {
      ...parseContent(step),
      actorId,
      type: step.type as StepType,
      documentReference: nullableText(step.documentReference),
      informationUrl: safeInformationUrl(nullableText(step.informationUrl)),
    };
  };

  const stages = parseRows(data.stages, stage => ({
    ...parseContent(stage),
    descriptionHtml: nullableText(stage.descriptionHtml),
    steps: parseRows(stage.steps, parseStep),
  }));
  const connections = parseRows(data.connections, connection => {
    const sourceStepId = positiveInteger(connection.sourceStepId);
    const targetStepId = positiveInteger(connection.targetStepId);
    if (!stepIds.has(sourceStepId) || !stepIds.has(targetStepId) || sourceStepId === targetStepId) {
      throw new Error('Invalid process connection');
    }
    if (!['normal', 'success', 'revision'].includes(nonEmptyText(connection.type))) {
      throw new Error('Invalid connection type');
    }
    return {
      ...parseOrdered(connection),
      sourceStepId,
      targetStepId,
      label: nullableText(connection.label),
      type: connection.type as ConnectionType,
    };
  });

  return { ...parseContent(data), slug, actors, stages, connections };
}

export async function fetchAssessmentProcess(apiUrl: string, preview?: PreviewParams): Promise<ProcessResult> {
  try {
    const base = new URL(apiUrl.endsWith('/') ? apiUrl : `${apiUrl}/`);
    const request = async (url: URL): Promise<unknown> => {
      const response = await fetch(url, { cache: 'no-store', signal: AbortSignal.timeout(5000) });
      if (!response.ok) throw new Error(`Process API returned ${response.status}`);
      return asRecord(await response.json()).data;
    };

    let url: URL;
    if (preview) {
      // Preview memakai origin API yang dikonfigurasi; token tidak boleh menentukan host tujuan.
      if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(preview.slug) ||
          !/^\d+$/.test(preview.expires) || !/^[a-f0-9]{64}$/.test(preview.signature)) {
        throw new Error('Invalid preview signature');
      }
      url = new URL(`${encodeURIComponent(preview.slug)}/preview`, base);
      url.search = new URLSearchParams({ expires: preview.expires, signature: preview.signature }).toString();
    } else {
      const listing = await request(new URL(apiUrl));
      const processes = parseRows(listing, row => ({ ...parseOrdered(row), slug: nonEmptyText(row.slug) }));
      if (!processes.length) return { process: null, unavailable: false };
      const slug = processes[0].slug;
      if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw new Error('Invalid process slug');
      url = new URL(encodeURIComponent(slug), base);
    }
    return { process: parseAssessmentProcess(await request(url)), unavailable: false };
  } catch (error) {
    console.error('Unable to load assessment process:', error instanceof Error ? error.message : 'Unknown error');
    return { process: null, unavailable: true };
  }
}

import defaults from "./information-defaults.json" with { type: "json" };

export type InformationContent = typeof defaults;
export const defaultInformationContent: InformationContent = defaults;

export function normalizeInformationContent(value: unknown): InformationContent {
  const content = structuredClone(defaults);
  if (!value || typeof value !== "object") return content;
  for (const section of Object.keys(content) as (keyof InformationContent)[]) {
    const source = (value as Record<string, unknown>)[section];
    if (!source || typeof source !== "object") continue;
    const target = content[section] as Record<string, string>;
    for (const field of Object.keys(target)) {
      const text = (source as Record<string, unknown>)[field];
      if (typeof text === "string") target[field] = text;
    }
  }
  return content;
}

export function informationText(template: string, values: Record<string, string | number>): string {
  return template.replace(/\{([a-z_]+)\}/g, (match, key: string) => String(values[key] ?? match));
}

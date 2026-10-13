export const PARTNERS_SUBTITLE = "Dipercaya oleh berbagai perusahaan dan institusi pendidikan";
import type { LandingPartner } from "./landing";
export type Partner = { name: string; url: string | null; logo_url: string | null };
function webUrl(value: string | null) {
  if (!value) return null;
  try { const url = new URL(value); return ["https:", "http:"].includes(url.protocol) && !url.username && !url.password ? url.href : null; }
  catch { return null; }
}
export function normalizePartners(partners: LandingPartner[]): Partner[] {
  const unique = new Map<string, Partner>();
  for (const partner of partners) {
    const name = partner.name.trim();
    if (!name) continue;
    const url = webUrl(partner.url);
    const logo = partner.logo_url;
    const logoUrl = logo?.startsWith("/") && !logo.startsWith("//") && !logo.includes("\\") ? logo : webUrl(logo);
    const key = name.toLocaleLowerCase("id");
    const existing = unique.get(key);
    if (existing) { existing.logo_url ??= logoUrl; existing.url ??= url; }
    else unique.set(key, { name, url, logo_url: logoUrl ?? (key === "jobhun" ? "/figma/imgImage20.png" : null) });
  }
  return [...unique.values()];
}
export function searchPartners(partners: Partner[], query: string) {
  const search = query.trim().toLocaleLowerCase("id");
  return partners.filter(partner => partner.name.toLocaleLowerCase("id").includes(search));
}

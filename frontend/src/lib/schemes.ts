export type SchemeMethod = "luring" | "jarak-jauh";
export type SchemeUnit = { code: string; title: string };
export type Scheme = {
  id: string; slug: string; name: string; category: string; level: number;
  type: string; methods: SchemeMethod[]; price: number | null; thumbnail: string;
  units: SchemeUnit[]; basicRequirements: string[]; administrativeRequirements: string[];
  registrationUrl: string | null;
};
export const methodLabels: Record<SchemeMethod, string> = { luring: "Luring", "jarak-jauh": "Daring" };
export const schemeSorts = {
  "name-asc": "Nama A–Z", "name-desc": "Nama Z–A",
  "price-asc": "Harga terendah", "price-desc": "Harga tertinggi",
  "level-asc": "Level terendah", "level-desc": "Level tertinggi",
};
export type SchemeState = {
  query: string; methods: SchemeMethod[]; minLevel: number; maxLevel: number;
  categories: string[]; sort: keyof typeof schemeSorts; page: number;
};
export function readSchemeState(params: Pick<URLSearchParams, "get" | "getAll">, availableCategories: readonly string[] = []): SchemeState {
  const level = (key: string, fallback: number) => /^[1-9]$/.test(params.get(key) ?? "") ? Number(params.get(key)) : fallback;
  const min = level("level_min", 1), max = level("level_max", 9);
  const sort = params.get("urutan") ?? "name-asc";
  const page = Number(params.get("halaman"));
  return {
    query: params.get("q") ?? "",
    methods: [...new Set(params.getAll("metode").filter((method): method is SchemeMethod => method === "luring" || method === "jarak-jauh"))],
    minLevel: Math.min(min, max), maxLevel: Math.max(min, max),
    categories: [...new Set(params.getAll("kategori").filter(category => availableCategories.includes(category)))],
    sort: Object.hasOwn(schemeSorts, sort) ? sort as SchemeState["sort"] : "name-asc",
    page: Number.isSafeInteger(page) && page > 0 ? page : 1,
  };
}
export function schemeHref(state: SchemeState, slug?: string) {
  const params = new URLSearchParams();
  if (state.query) params.set("q", state.query);
  state.methods.forEach(value => params.append("metode", value));
  if (state.minLevel !== 1) params.set("level_min", String(state.minLevel));
  if (state.maxLevel !== 9) params.set("level_max", String(state.maxLevel));
  state.categories.forEach(value => params.append("kategori", value));
  if (state.sort !== "name-asc") params.set("urutan", state.sort);
  if (state.page > 1) params.set("halaman", String(state.page));
  const query = params.toString();
  return `/skema-sertifikasi${slug ? `/${encodeURIComponent(slug)}` : ""}${query ? `?${query}` : ""}`;
}
export function selectSchemes(schemes: Scheme[], state: SchemeState, pageSize = 6) {
  const query = state.query.trim().toLocaleLowerCase("id");
  const items = schemes.filter(scheme =>
    (!query || `${scheme.name} ${scheme.category}`.toLocaleLowerCase("id").includes(query)) &&
    (!state.methods.length || state.methods.some(method => scheme.methods.includes(method))) &&
    (!state.categories.length || state.categories.includes(scheme.category)) &&
    scheme.level >= state.minLevel && scheme.level <= state.maxLevel
  ).sort((a, b) => {
    const direction = state.sort.endsWith("desc") ? -1 : 1;
    const name = a.name.localeCompare(b.name, "id", { numeric: true }) || a.id.localeCompare(b.id);
    if (state.sort.startsWith("price")) {
      if (a.price === null || b.price === null) return a.price === b.price ? name : a.price === null ? 1 : -1;
      return (a.price - b.price) * direction || name;
    }
    if (state.sort.startsWith("level")) return (a.level - b.level) * direction || name;
    return name * direction;
  });
  const size = Math.max(1, Math.floor(pageSize));
  const total = items.length, pageCount = Math.ceil(total / size);
  const page = Math.max(1, Math.min(state.page, pageCount || 1)), offset = (page - 1) * size;
  return { items: items.slice(offset, offset + size), total, pageCount, page, start: total ? offset + 1 : 0, end: Math.min(offset + size, total) };
}
export function selectUnits(units: SchemeUnit[], query: string) {
  const search = query.trim().toLocaleLowerCase("id");
  return units.map((unit, index) => ({ ...unit, number: index + 1 }))
    .filter(unit => `${unit.code} ${unit.title}`.toLocaleLowerCase("id").includes(search));
}
export function formatSchemePrice(price: number | null) {
  return price === null ? "Hubungi kami untuk biaya" : new Intl.NumberFormat("id-ID", {
    style: "currency", currency: "IDR", maximumFractionDigits: 0,
  }).format(price);
}
export function registrationHref(url: string | null) {
  if (!url) return null;
  try { const parsed = new URL(url); return parsed.protocol === "https:" && !parsed.username && !parsed.password ? parsed.href : null; }
  catch { return null; }
}

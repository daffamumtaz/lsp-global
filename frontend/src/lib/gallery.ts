export type GalleryMedia = {
  id: string; type: "photo" | "video"; src: string; thumbnail: string;
  alt: string; width: number; height: number;
};
export type GalleryActivity = {
  id: string; title: string; year: number; date: string | null;
  location: string; description: string; media: GalleryMedia[];
};
export type GalleryState = {
  type: "photo" | "video"; year: number | null; query: string;
  sort: "newest" | "oldest" | "title-asc" | "title-desc"; page: number;
};
export type ViewerItem = { activity: GalleryActivity; media: GalleryMedia };
export type GalleryCard = {
  id: string; year: number; title: string; cover: GalleryMedia;
  count: number; activity: GalleryActivity | null;
};

export function readGalleryState(params: { get: (key: string) => string | null }): GalleryState {
  const year = params.get("tahun") ?? "";
  const page = Number(params.get("halaman"));
  const sort = params.get("urutan");
  return {
    type: params.get("jenis") === "video" ? "video" : "photo",
    year: /^\d{4}$/.test(year) && Number(year) >= 1900 && Number(year) <= 2100 ? Number(year) : null,
    query: params.get("q") ?? "",
    sort: sort === "oldest" || sort === "title-asc" || sort === "title-desc" ? sort : "newest",
    page: Number.isSafeInteger(page) && page > 0 ? page : 1,
  };
}

export function galleryHref(state: GalleryState) {
  const params = new URLSearchParams();
  params.set("jenis", state.type === "video" ? "video" : "foto");
  if (state.year !== null) params.set("tahun", String(state.year));
  if (state.query) params.set("q", state.query);
  if (state.sort !== "newest") params.set("urutan", state.sort);
  if (state.page > 1) params.set("halaman", String(state.page));
  return `/galeri?${params.toString()}`;
}

export function yearMedia(activities: GalleryActivity[], year: number, type: GalleryState["type"]): ViewerItem[] {
  return activities.filter(activity => activity.year === year)
    .sort((a, b) => (b.date ?? "").localeCompare(a.date ?? "") || a.title.localeCompare(b.title, "id"))
    .flatMap(activity => activity.media.filter(media => media.type === type && media.src).map(media => ({ activity, media })));
}

export function selectGallery(activities: GalleryActivity[], state: GalleryState, pageSize = 8) {
  const query = state.query.trim().toLocaleLowerCase("id");
  const available = activities.filter(activity => activity.media.some(media => media.type === state.type && media.src));
  let items: GalleryCard[];
  if (state.year === null) {
    const years = [...new Set(available.map(activity => activity.year))].sort((a, b) => b - a);
    items = years.filter(year => String(year).includes(query)).map(year => {
      const media = yearMedia(available, year, state.type);
      return { id: `year-${year}`, title: String(year), year, cover: media[0].media, count: media.length, activity: null };
    });
  } else {
    items = available.filter(activity => activity.year === state.year &&
      (!query || `${activity.title} ${activity.location}`.toLocaleLowerCase("id").includes(query)))
      .sort((a, b) => state.sort.startsWith("title") ?
        a.title.localeCompare(b.title, "id", { numeric: true }) * (state.sort === "title-desc" ? -1 : 1) :
        (a.date ?? "").localeCompare(b.date ?? "") * (state.sort === "oldest" ? 1 : -1) || a.title.localeCompare(b.title, "id"))
      .map(activity => {
        const media = activity.media.filter(media => media.type === state.type && media.src);
        return { id: activity.id, title: activity.title, year: activity.year, cover: media[0], count: media.length, activity };
      });
  }
  const total = items.length;
  const pageCount = Math.ceil(total / pageSize);
  const page = Math.max(1, Math.min(state.page, pageCount || 1));
  return { items: items.slice((page - 1) * pageSize, page * pageSize), total, pageCount, page };
}

export function moveMediaIndex(index: number, change: number, count: number) {
  return Math.max(0, Math.min(count - 1, index + change));
}
export function nextZoom(zoom: number, change: number) { return Math.max(100, Math.min(300, zoom + change)); }

export function formatGalleryDate(date: string | null) {
  if (!date) return "Tanggal belum tersedia";
  const value = new Date(`${date}T00:00:00Z`);
  return Number.isNaN(value.getTime()) ? "Tanggal belum tersedia" :
    new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "long", year: "numeric", timeZone: "UTC" }).format(value);
}

export type DirectoryEntry = { id: string; name: string; [key: string]: string };
export type DirectoryOptions = {
  query: string; filterKey: string; filter: string;
  sortKey: string; direction: "asc" | "desc"; page: number; pageSize: number;
};

export function selectDirectory(entries: DirectoryEntry[], options: DirectoryOptions) {
  const query = options.query.trim().toLocaleLowerCase("id");
  const filtered = entries.filter(entry =>
    (!options.filter || entry[options.filterKey] === options.filter) &&
    (!query || Object.entries(entry).some(([key, value]) => key !== "id" && value.toLocaleLowerCase("id").includes(query)))
  ).sort((a, b) => (a[options.sortKey] ?? "").localeCompare(b[options.sortKey] ?? "", "id", { numeric: true }) * (options.direction === "asc" ? 1 : -1));
  const pageSize = Math.max(1, Math.floor(options.pageSize));
  const total = filtered.length;
  const pageCount = Math.ceil(total / pageSize);
  const page = Math.max(1, Math.min(options.page, pageCount || 1));
  const offset = (page - 1) * pageSize;
  return { rows: filtered.slice(offset, offset + pageSize), total, pageCount, page, start: total ? offset + 1 : 0, end: Math.min(offset + pageSize, total) };
}

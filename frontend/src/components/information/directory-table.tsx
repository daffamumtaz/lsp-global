"use client";

import { Component, useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from "react";
import dynamic from "next/dynamic";
import Image from "next/image";
import { type Tuk } from "@/lib/tuks";
import { selectDirectory, type DirectoryEntry } from "@/lib/directory";
import { registrationHref } from "@/lib/schemes";
import { informationText, type InformationContent } from "@/lib/information-copy";
import styles from "./directory-table.module.css";

const TukMap = dynamic(() => import("./tuk-map"), { ssr: false, loading: () => <p role="status" className={styles.mapStatus}>Memuat peta…</p> });

class MapBoundary extends Component<{ children: ReactNode }, { failed: boolean }> {
  state = { failed: false };
  static getDerivedStateFromError() { return { failed: true }; }
  render() { return this.state.failed ? <p role="status" className={styles.mapStatus}>Peta tidak tersedia. Silakan gunakan daftar TUK.</p> : this.props.children; }
}

export function DirectoryTable({ id, title, entries, columns, filterKey, filterLabel, allFilter, searchPlaceholder, copy, tuks, cards = false, initialQuery = "", unavailable = false }: {
  tuks?: Tuk[]; cards?: boolean; initialQuery?: string; unavailable?: boolean;
  id: string; title: string; entries: DirectoryEntry[];
  columns: { key: string; label: string }[]; filterKey: string; filterLabel: string;
  allFilter: string; searchPlaceholder: string; copy: InformationContent["directory"];
}) {
  const [query, setQuery] = useState(initialQuery);
  const [filter, setFilter] = useState("");
  const [sortKey, setSortKey] = useState("name");
  const [direction, setDirection] = useState<"asc" | "desc">("asc");
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(5);
  const resultsView = useRef<HTMLDivElement>(null);
  const scrollPending = useRef(false);
  const [selectedIds, setSelectedIds] = useState<string[] | null>(null);
  const select = useCallback((ids: string[]) => { scrollPending.current = ids.length > 1; setSelectedIds(ids); setPage(1); }, []);
  const mapEntries = useMemo(() => {
    const filtered = selectDirectory(entries, { query, filterKey, filter, sortKey: "name", direction: "asc", page: 1, pageSize: Math.max(1, entries.length) });
    const ids = new Set(filtered.rows.map(entry => entry.id));
    return tuks?.filter(tuk => ids.has(tuk.id)) ?? [];
  }, [entries, query, filterKey, filter, tuks]);
  const result = selectDirectory(entries, { query, filterKey, filter, sortKey, direction, page, pageSize: pageSize === 0 ? Math.max(1, entries.length) : pageSize, selectedIds });
  const filters = [...new Set(entries.map(entry => entry[filterKey]).filter(Boolean))].sort((a, b) => a.localeCompare(b, "id"));
  useEffect(() => {
    if (!scrollPending.current) return;
    scrollPending.current = false;
    resultsView.current?.scrollIntoView({ block: "start", behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "instant" : "smooth" });
  }, [result.page, pageSize, selectedIds]);
  function goToPage(value: number) { scrollPending.current = true; setPage(value); }

  function reset() { setSelectedIds(null); setQuery(""); setFilter(""); setSortKey("name"); setDirection("asc"); setPage(1); }
  function sort(key: string) {
    setDirection(sortKey === key && direction === "asc" ? "desc" : "asc");
    setSortKey(key); setPage(1);
  }

  return <div className={styles.directory}>
    <div className={styles.toolbar}>
      <label>{filterLabel}<select value={filter} onChange={event => { setFilter(event.target.value); setSelectedIds(null); setPage(1); }}>
        <option value="">{allFilter}</option>
        {filters.map(value => <option key={value} value={value}>{value}</option>)}
      </select></label>
      <label>{copy.order}<select value={direction} onChange={event => { setDirection(event.target.value as "asc" | "desc"); setPage(1); }}>
        <option value="asc">{copy.ascending}</option><option value="desc">{copy.descending}</option>
      </select></label>
      <label className={styles.search}>{informationText(copy.search, { title })}<input type="search" name={`${id}-search`} placeholder={searchPlaceholder} value={query} onChange={event => { setQuery(event.target.value); setSelectedIds(null); setPage(1); }} /></label>
      <button className={styles.reset} type="button" onClick={reset} disabled={!query && !filter && sortKey === "name" && direction === "asc"}>{copy.reset}</button>
    </div>
    {unavailable && <p role="alert" className={styles.mapStatus}>Data TUK belum dapat dimuat. Muat ulang halaman untuk mencoba lagi.</p>}
    {tuks && <>
      <MapBoundary><TukMap entries={mapEntries} onSelect={select} /></MapBoundary>
      <div className={styles.selection}>
        <button type="button" disabled={!selectedIds} onClick={() => { setSelectedIds(null); setPage(1); }}>Tampilkan semua</button>
      </div>
    </>}
    {cards ? <div ref={resultsView} className={styles.cards}>
      {result.rows.map(entry => <article key={entry.id} className={styles.card}>
        {entry.logo_url && <Image src={entry.logo_url} alt={`Logo ${entry.name}`} width={160} height={80} unoptimized />}
        <h3>{entry.name}</h3>
        <p>{[entry.city, entry.province].filter(Boolean).join(", ") || "Lokasi belum tersedia"}</p>
        {entry.address && <p>{entry.address}</p>}
        <div>{registrationHref(entry.url) && <a href={registrationHref(entry.url)!} target="_blank" rel="noopener noreferrer">Website {entry.name} ↗</a>}
        {registrationHref(entry.maps_url) && <a href={registrationHref(entry.maps_url)!} target="_blank" rel="noopener noreferrer">Google Maps {entry.name} ↗</a>}</div>
      </article>)}
      {!result.total && <p className={styles.empty}>Mitra tidak ditemukan. Coba pencarian atau provinsi lain.</p>}
    </div> : <>
    {tuks && <p className={styles.mapsHint}>Klik alamat yang bertanda ↗ untuk buka di Google Maps.</p>}
    <p className={styles.scrollHint} id={`${id}-scroll-help`}>{copy.scroll_hint}</p>
    <div ref={resultsView} className={styles.tableScroll} tabIndex={0} role="region" aria-label={informationText(copy.table_label, { title })} aria-describedby={`${id}-scroll-help`}>
      <table className={styles.table}>
        <caption className="sr-only">{informationText(copy.caption, { title })}</caption>
        <thead><tr><th scope="col">{copy.number}</th>{columns.map(column => <th key={column.key} scope="col" aria-sort={sortKey === column.key ? direction === "asc" ? "ascending" : "descending" : "none"}>
          <button type="button" onClick={() => sort(column.key)} aria-label={informationText(copy.sort_label, { title: column.label })}>
            {column.label}<span aria-hidden="true">{sortKey === column.key ? direction === "asc" ? "↑" : "↓" : "↕"}</span>
          </button>
        </th>)}</tr></thead>
        <tbody>{result.rows.map((entry, index) => {
          const mapsHref = registrationHref(entry.maps_url);
          return <tr key={entry.id}>
            <td>{result.start + index}</td>
            {columns.map(column => <td key={column.key} className={column.key === "name" ? styles.name : undefined}>
              {column.key === "address" && mapsHref
                ? <a className={styles.locationLink} href={mapsHref} target="_blank" rel="noopener noreferrer" title="Klik untuk buka di Google Maps">{entry.address || copy.maps} ↗</a>
                : entry[column.key] || copy.missing}
            </td>)}
          </tr>;
        })}
          {result.total === 0 && <tr><td colSpan={columns.length + 1} className={styles.empty}>{informationText(copy.empty, { title: title.toLocaleLowerCase("id") })}</td></tr>}
        </tbody>
      </table>
    </div>
    </>}
    <div className={styles.tableFooter}>
      <p role="status" aria-live="polite">{result.total ? informationText(copy.results, { start: result.start, end: result.end, total: result.total }) : copy.no_results}</p>
      <label>{copy.per_page}<select value={pageSize > result.total ? 0 : pageSize} onChange={event => { scrollPending.current = true; setPageSize(Number(event.target.value)); setPage(1); }}>{[1, 3, 5, 10, 25, 50, 100].filter(size => size <= result.total).map(size => <option key={size} value={size}>{size}</option>)}<option value={0}>Lihat semua</option></select></label>
      <nav className={styles.pagination} aria-label={informationText(copy.pagination_label, { title })}>
        <button type="button" aria-label={informationText(copy.previous, { title })} disabled={result.page <= 1} onClick={() => goToPage(result.page - 1)}>←</button>
        {Array.from({ length: result.pageCount }, (_, index) => index + 1).map(number => <button type="button" key={number} aria-label={informationText(copy.page, { page: number, title })} aria-current={result.page === number ? "page" : undefined} onClick={() => goToPage(number)}>{number}</button>)}
        <button type="button" aria-label={informationText(copy.next, { title })} disabled={!result.total || result.page >= result.pageCount} onClick={() => goToPage(result.page + 1)}>→</button>
      </nav>
    </div>
  </div>;
}

"use client";

import { useState } from "react";
import { selectDirectory, type DirectoryEntry } from "@/lib/directory";
import styles from "./directory-table.module.css";

export function DirectoryTable({ id, title, entries, columns, filterKey, filterLabel }: {
  id: string; title: string; entries: DirectoryEntry[];
  columns: { key: string; label: string }[]; filterKey: string; filterLabel: string;
}) {
  const [query, setQuery] = useState("");
  const [filter, setFilter] = useState("");
  const [sortKey, setSortKey] = useState("name");
  const [direction, setDirection] = useState<"asc" | "desc">("asc");
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(3);
  const result = selectDirectory(entries, { query, filterKey, filter, sortKey, direction, page, pageSize });
  const filters = [...new Set(entries.map(entry => entry[filterKey]).filter(Boolean))].sort((a, b) => a.localeCompare(b, "id"));

  function reset() { setQuery(""); setFilter(""); setSortKey("name"); setDirection("asc"); setPage(1); }
  function sort(key: string) {
    setDirection(sortKey === key && direction === "asc" ? "desc" : "asc");
    setSortKey(key); setPage(1);
  }

  return <div className={styles.directory}>
    <div className={styles.toolbar}>
      <label>{filterLabel}<select value={filter} onChange={event => { setFilter(event.target.value); setPage(1); }}>
        <option value="">Semua {filterLabel.toLocaleLowerCase("id")}</option>
        {filters.map(value => <option key={value} value={value}>{value}</option>)}
      </select></label>
      <label>Urutan<select value={direction} onChange={event => { setDirection(event.target.value as "asc" | "desc"); setPage(1); }}>
        <option value="asc">A–Z</option><option value="desc">Z–A</option>
      </select></label>
      <label className={styles.search}>Cari {title}<input type="search" name={`${id}-search`} placeholder={id === "tuk" ? "Nama, kode, atau lokasi…" : "Nama, nomor sertifikat, atau bidang…"} value={query} onChange={event => { setQuery(event.target.value); setPage(1); }} /></label>
      <button className={styles.reset} type="button" onClick={reset} disabled={!query && !filter && sortKey === "name" && direction === "asc"}>Reset</button>
    </div>
    <p className={styles.scrollHint} id={`${id}-scroll-help`}>Pada layar kecil, geser tabel ke samping untuk melihat seluruh kolom.</p>
    <div className={styles.tableScroll} tabIndex={0} role="region" aria-label={`Tabel ${title}`} aria-describedby={`${id}-scroll-help`}>
      <table className={styles.table}>
        <caption className="sr-only">Direktori {title}</caption>
        <thead><tr><th scope="col">No.</th>{columns.map(column => <th key={column.key} scope="col" aria-sort={sortKey === column.key ? direction === "asc" ? "ascending" : "descending" : "none"}>
          <button type="button" onClick={() => sort(column.key)} aria-label={`Urutkan berdasarkan ${column.label}`}>
            {column.label}<span aria-hidden="true">{sortKey === column.key ? direction === "asc" ? "↑" : "↓" : "↕"}</span>
          </button>
        </th>)}</tr></thead>
        <tbody>{result.rows.map((entry, index) => <tr key={entry.id}><td>{result.start + index}</td>{columns.map(column => <td key={column.key} className={column.key === "name" ? styles.name : undefined}>{entry[column.key] || "—"}</td>)}</tr>)}
          {result.total === 0 && <tr><td colSpan={columns.length + 1} className={styles.empty}>Tidak ada {title.toLocaleLowerCase("id")} yang sesuai. Coba kata kunci lain atau reset filter.</td></tr>}
        </tbody>
      </table>
    </div>
    <div className={styles.tableFooter}>
      <p role="status" aria-live="polite">{result.total ? `Menampilkan ${result.start}–${result.end} dari ${result.total} hasil` : "Tidak ada hasil"}</p>
      <label>Per halaman<select value={pageSize} onChange={event => { setPageSize(Number(event.target.value)); setPage(1); }}><option value={1}>1</option><option value={3}>3</option><option value={10}>10</option></select></label>
      <nav className={styles.pagination} aria-label={`Halaman direktori ${title}`}>
        <button type="button" aria-label={`Halaman sebelumnya untuk ${title}`} disabled={result.page <= 1} onClick={() => setPage(result.page - 1)}>←</button>
        {Array.from({ length: result.pageCount }, (_, index) => index + 1).map(number => <button type="button" key={number} aria-label={`Halaman ${number} untuk ${title}`} aria-current={result.page === number ? "page" : undefined} onClick={() => setPage(number)}>{number}</button>)}
        <button type="button" aria-label={`Halaman berikutnya untuk ${title}`} disabled={!result.total || result.page >= result.pageCount} onClick={() => setPage(result.page + 1)}>→</button>
      </nav>
    </div>
  </div>;
}

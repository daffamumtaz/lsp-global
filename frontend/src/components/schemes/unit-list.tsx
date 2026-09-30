"use client";

import { useState } from "react";
import { selectUnits, type SchemeUnit } from "@/lib/schemes";
import styles from "./schemes.module.css";

export function UnitList({ units }: { units: SchemeUnit[] }) {
  const [query, setQuery] = useState("");
  const results = selectUnits(units, query);
  return <details className={styles.units} open>
    <summary>{units.length} unit kompetensi <span>· Buka / sembunyikan</span></summary>
    <div className={styles.unitBody}>
      <label className={styles.field}>Cari kode atau judul unit<input type="search" name="unit-search" autoComplete="off" placeholder="Contoh: Algoritma…" value={query} onChange={event => setQuery(event.target.value)} /></label>
      <p className={styles.helper} role="status" aria-live="polite">{results.length} dari {units.length} unit ditampilkan</p>
      <ol className={styles.unitGrid} role="list">{results.map(unit => <li key={unit.code} value={unit.number}><span className={styles.unitNumber}>{unit.number}</span><div><h3>{unit.code}</h3><p>{unit.title}</p></div></li>)}</ol>
      {!results.length && <p>Tidak ada unit yang sesuai. Coba kode atau judul lain.</p>}
      {query && <button type="button" className={styles.textButton} onClick={() => setQuery("")}>Reset pencarian unit</button>}
    </div>
  </details>;
}

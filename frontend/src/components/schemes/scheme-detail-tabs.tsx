"use client";

import { useEffect, useId, useRef, useState } from "react";
import type { LandingContent } from "@/lib/landing";
import type { Scheme } from "@/lib/schemes";
import styles from "./schemes.module.css";


export function SchemeDetailTabs({ units, basicRequirements, administrativeRequirements, headings }: Pick<Scheme, "units" | "basicRequirements" | "administrativeRequirements"> & { headings: LandingContent["sections"] }) {
  const labels = [headings.scheme_units.title, headings.scheme_requirements.title];
  const [active, setActive] = useState(0);
  const [pageSize, setPageSize] = useState(5);
  const table = useRef<HTMLTableElement>(null);
  const scrollPending = useRef(false);
  const [page, setPage] = useState(1);
  const size = pageSize || Math.max(1, units.length);
  const pageCount = Math.max(1, Math.ceil(units.length / size));
  const currentPage = Math.min(page, pageCount);
  const offset = (currentPage - 1) * size;
  const id = useId();
  const tabs = useRef<(HTMLButtonElement | null)[]>([]);
  useEffect(() => {
    if (!scrollPending.current) return;
    scrollPending.current = false;
    table.current?.scrollIntoView({ block: "start", behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "instant" : "smooth" });
  }, [currentPage, pageSize]);
  function goToPage(value: number) { scrollPending.current = true; setPage(value); }
  return <>
    <div className={styles.tabs} role="tablist" aria-label="Detail sertifikasi">
      {labels.map((label, index) => <button key={label} type="button" role="tab" id={`${id}-tab-${index}`} aria-controls={`${id}-panel-${index}`} aria-selected={active === index} tabIndex={active === index ? 0 : -1} ref={element => { tabs.current[index] = element; }} onClick={() => setActive(index)} onKeyDown={event => {
        let next;
        if (event.key === "ArrowRight") next = (index + 1) % labels.length;
        else if (event.key === "ArrowLeft") next = (index + labels.length - 1) % labels.length;
        else if (event.key === "Home") next = 0;
        else if (event.key === "End") next = labels.length - 1;
        else return;
        event.preventDefault();
        setActive(next);
        tabs.current[next]?.focus();
      }}>{label}</button>)}
    </div>
    <div className={styles.tabPanel} role="tabpanel" id={`${id}-panel-0`} aria-labelledby={`${id}-tab-0`} tabIndex={0} hidden={active !== 0}>
      {headings.scheme_units.subtitle && <p>{headings.scheme_units.subtitle}</p>}
      <table ref={table} className={styles.unitTable}>
        <caption className="sr-only">Unit kompetensi untuk skema sertifikasi</caption>
        <colgroup><col className={styles.numberColumn} /><col className={styles.codeColumn} /><col /></colgroup>
        <thead><tr><th scope="col">No.</th><th scope="col">Kode Unit</th><th scope="col">Judul Unit</th></tr></thead>
        <tbody>{units.slice(offset, offset + size).map((unit, index) => <tr key={`${unit.code}-${index}`}><td>{offset + index + 1}</td><td>{unit.code}</td><td>{unit.title}</td></tr>)}</tbody>
      </table>
      <div className={styles.unitControls}>
        <label>Baris per halaman <select value={pageSize > units.length ? 0 : pageSize} onChange={event => { scrollPending.current = true; setPageSize(Number(event.target.value)); setPage(1); }}>{[1, 3, 5, 10, 25, 50, 100].filter(value => value <= units.length).map(value => <option key={value} value={value}>{value}</option>)}<option value={0}>Lihat semua</option></select></label>
        <nav aria-label="Halaman unit kompetensi"><button type="button" className={styles.secondary} disabled={currentPage === 1} onClick={() => goToPage(currentPage - 1)}>Sebelumnya</button><span>{currentPage} / {pageCount}</span><button type="button" className={styles.secondary} disabled={currentPage === pageCount} onClick={() => goToPage(currentPage + 1)}>Berikutnya</button></nav>
      </div>
      {!units.length && <p className={styles.helper}>Unit kompetensi belum tersedia.</p>}
    </div>
    <div className={styles.tabPanel} role="tabpanel" id={`${id}-panel-1`} aria-labelledby={`${id}-tab-1`} tabIndex={0} hidden={active !== 1}>
      {headings.scheme_requirements.subtitle && <p>{headings.scheme_requirements.subtitle}</p>}
      <section className={styles.requirements} aria-labelledby={`${id}-basic`}><h2 id={`${id}-basic`}>{headings.scheme_basic.title}</h2><p>{headings.scheme_basic.subtitle}</p><ul>{basicRequirements.map(item => <li key={item}>{item}</li>)}</ul></section>
      <section className={styles.requirements} aria-labelledby={`${id}-administrative`}><h2 id={`${id}-administrative`}>{headings.scheme_administrative.title}</h2><p>{headings.scheme_administrative.subtitle}</p><ul>{administrativeRequirements.map(item => <li key={item}>{item}</li>)}</ul></section>
    </div>
  </>;
}

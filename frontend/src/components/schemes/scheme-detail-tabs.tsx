"use client";

import { useId, useRef, useState } from "react";
import type { Scheme } from "@/lib/schemes";
import styles from "./schemes.module.css";

const labels = ["Unit Kompetensi", "Persyaratan"];

export function SchemeDetailTabs({ units, basicRequirements, administrativeRequirements }: Pick<Scheme, "units" | "basicRequirements" | "administrativeRequirements">) {
  const [active, setActive] = useState(0);
  const id = useId();
  const tabs = useRef<(HTMLButtonElement | null)[]>([]);
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
      <table className={styles.unitTable}>
        <caption className="sr-only">Unit kompetensi untuk skema sertifikasi</caption>
        <colgroup><col className={styles.numberColumn} /><col className={styles.codeColumn} /><col /></colgroup>
        <thead><tr><th scope="col">No.</th><th scope="col">Kode Unit</th><th scope="col">Judul Unit</th></tr></thead>
        <tbody>{units.map((unit, index) => <tr key={`${unit.code}-${index}`}><td>{index + 1}</td><td>{unit.code}</td><td>{unit.title}</td></tr>)}</tbody>
      </table>
      {!units.length && <p className={styles.helper}>Unit kompetensi belum tersedia.</p>}
    </div>
    <div className={styles.tabPanel} role="tabpanel" id={`${id}-panel-1`} aria-labelledby={`${id}-tab-1`} tabIndex={0} hidden={active !== 1}>
      <section className={styles.requirements} aria-labelledby={`${id}-basic`}><h2 id={`${id}-basic`}>Persyaratan Dasar</h2><p>Penuhi salah satu persyaratan berikut. Konfirmasikan ketentuan resmi kepada penyelenggara.</p><ul>{basicRequirements.map(item => <li key={item}>{item}</li>)}</ul></section>
      <section className={styles.requirements} aria-labelledby={`${id}-administrative`}><h2 id={`${id}-administrative`}>Persyaratan Administrasi</h2><p>Siapkan semua berkas berikut sebelum mendaftar.</p><ul>{administrativeRequirements.map(item => <li key={item}>{item}</li>)}</ul></section>
    </div>
  </>;
}

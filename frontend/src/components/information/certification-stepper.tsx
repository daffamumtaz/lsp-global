"use client";

import Link from "next/link";
import { useId, useState, type CSSProperties } from "react";
import type { ProcessStage } from "@/lib/assessment-process";
import styles from "./certification-stepper.module.css";

export function CertificationStepper({ stages, preview = false }: { stages: ProcessStage[]; preview?: boolean }) {
  const id = useId();
  const [selected, setSelected] = useState(stages[0]?.id);
  const current = stages.find(stage => stage.id === selected) ?? stages[0];

  if (!stages.length) return <p role="status">Tahapan sertifikasi belum tersedia.</p>;

  return <div className={styles.stepper}>
    <p className={styles.hint}>Pilih tahap untuk melihat proses dan persiapannya.</p>
    <ol className={styles.steps} role="list" aria-label="Tahapan sertifikasi jarak jauh"
      style={{ "--stage-count": Math.min(stages.length, 6) } as CSSProperties}>
      {stages.map((stage, index) => {
        const active = current.id === stage.id;
        const panelId = `${id}-${stage.id}-detail`;
        const buttonId = `${id}-${stage.id}-button`;
        return <li key={stage.id} role="listitem" className={styles.step} data-active={active}>
          <button id={buttonId} type="button" className={styles.stepButton}
            aria-current={active ? "step" : undefined} aria-expanded={active} aria-controls={panelId}
            onClick={() => setSelected(stage.id)}>
            <span className={styles.marker} aria-hidden="true">{index + 1}</span>
            <span className={styles.title}>{stage.title}{preview && !stage.isActive && " (Nonaktif)"}</span>
          </button>
          <div id={panelId} role="region" aria-labelledby={buttonId} hidden={!active} className={styles.panel}>
            {active && <>
              <p className={styles.counter}>Tahap {index + 1} dari {stages.length}</p>
              <h3>{stage.title}</h3>
              {stage.descriptionHtml
                ? <div className={styles.content} dangerouslySetInnerHTML={{ __html: stage.descriptionHtml }} />
                : <p className={styles.plainText}>{stage.description || "Detail tahap belum tersedia."}</p>}
            </>}
          </div>
        </li>;
      })}
    </ol>
    <div className={styles.closing}>
      <div><h3>Masih memiliki pertanyaan tentang sertifikasi?</h3>
        <p>Periksa skema dan persyaratannya sebelum mendaftar. Jika ada yang belum jelas, hubungi tim LSP TIK Global.</p></div>
      <div className={styles.actions}>
        <Link href="/skema-sertifikasi">Lihat Skema Sertifikasi</Link>
        <Link href="/kontak">Hubungi Kami</Link>
      </div>
    </div>
  </div>;
}

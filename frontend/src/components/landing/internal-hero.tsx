import type { ReactNode } from "react";
import styles from "./internal-hero.module.css";

export function InternalHero({ id, title, children }: { id: string; title: string; children: ReactNode }) {
  return <section className={styles.hero} aria-labelledby={id}>
    <svg className={styles.circuit} viewBox="0 0 1440 360" fill="none" aria-hidden="true">
      <g stroke="currentColor" strokeWidth="1.5">
        <path d="M0 70h160l70 70h120M0 110h130l70 70h90M0 290h170l60-60h130M1440 60h-180l-80 80h-120M1440 110h-150l-90 90h-80M1440 290h-220l-60-60h-120" />
        <path d="M50 0v70m50 290v-70M1350 0v60m-70 300v-70" />
        <circle cx="350" cy="140" r="5" /><circle cx="290" cy="180" r="5" /><circle cx="360" cy="230" r="5" />
        <circle cx="1060" cy="140" r="5" /><circle cx="1120" cy="200" r="5" /><circle cx="1040" cy="230" r="5" />
      </g>
    </svg>
    <div className={styles.copy}><h1 id={id}>{title}</h1><p>{children}</p></div>
  </section>;
}

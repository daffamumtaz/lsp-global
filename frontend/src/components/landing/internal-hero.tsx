import type { ReactNode } from "react";
import styles from "./internal-hero.module.css";
import { VisualMotif } from "./visual-motif";

export function InternalHero({ id, title, children, motif = "network" }: { id: string; title: string; children: ReactNode; motif?: "network" | "framework" }) {
  return <section className={styles.hero} aria-labelledby={id}>
    <VisualMotif variant={motif} className={styles.networkLeft} />
    <VisualMotif variant={motif} className={styles.networkRight} />
    <div className={styles.copy}><h1 id={id}>{title}</h1><p>{children}</p></div>
  </section>;
}

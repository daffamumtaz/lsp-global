import type { ReactNode } from "react";
import styles from "./internal-hero.module.css";

function PolygonNetwork({ className }: { className: string }) {
  return <svg className={className} viewBox="0 0 400 300" fill="none" aria-hidden="true">
    <g stroke="currentColor" strokeWidth=".8">
      <path d="M-20 230 35 165 12 95 90 125 70 40 140 85 195 20 225 110 310 75 355 145 420 105M-20 230 85 235 35 165 90 125 140 85 165 180 225 110 280 190 355 145 340 260 420 225M12 95 70 40 195 20M35 165 165 180 85 235 175 285 280 190 340 260M90 125 225 110 310 75 280 190M165 180 175 285 340 260" />
      <path d="m35 165 55-40-5 110Zm55-40 50-40 25 95Zm105-105 30 90-85-25Zm30 90 85-35-30 115Zm130 35-75 45 60 70Z" fill="currentColor" fillOpacity=".15" />
    </g>
    <g fill="currentColor">{[[35,165],[12,95],[90,125],[70,40],[140,85],[195,20],[225,110],[310,75],[355,145],[85,235],[165,180],[280,190],[340,260],[175,285]].map(([cx,cy]) => <circle key={`${cx}-${cy}`} cx={cx} cy={cy} r="2.5" />)}</g>
  </svg>;
}

export function InternalHero({ id, title, children, white = false }: { id: string; title: string; children: ReactNode; white?: boolean }) {
  return <section className={`${styles.hero} ${white ? styles.white : ""}`} aria-labelledby={id}>
    <PolygonNetwork className={styles.left} /><PolygonNetwork className={styles.right} />
    <div className={styles.copy}><h1 id={id}>{title}</h1>{children && <p>{children}</p>}</div>
  </section>;
}

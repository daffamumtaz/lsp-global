import Image from "next/image";
import Link from "next/link";
import { AcademicCapIcon, BuildingOfficeIcon, ClipboardDocumentListIcon, TrophyIcon } from "@heroicons/react/24/outline";
import { formatSchemePrice, methodLabels, type Scheme } from "@/lib/schemes";
import styles from "./schemes.module.css";

export function SchemeCard({ scheme, href }: { scheme: Scheme; href?: string }) {
  return <Link href={href ?? `/skema-sertifikasi/${scheme.slug}`} className={styles.card} aria-label={`Lihat detail ${scheme.name}`}>
    <Image unoptimized={!scheme.thumbnail.startsWith("/")} src={scheme.thumbnail} alt="" width={400} height={108} sizes="(max-width: 639px) 90vw, (max-width: 1023px) 45vw, 440px" className={styles.thumbnail} />
    <div className={styles.cardBody}>
      <div className={styles.cardHeading}><h3>{scheme.name}</h3><p className={styles.category}>{scheme.category}</p></div>
      <ul className={styles.cardMeta}>
        <li><TrophyIcon aria-hidden="true" /><span>Level {scheme.level} KKNI</span></li>
        <li><BuildingOfficeIcon aria-hidden="true" /><span>{scheme.methods.map(method => methodLabels[method]).join(" & ")}</span></li>
        <li><ClipboardDocumentListIcon aria-hidden="true" /><span>{scheme.units.length} Unit</span></li>
        <li><AcademicCapIcon aria-hidden="true" /><span>Skema {scheme.type}</span></li>
      </ul>
      <div className={styles.cardBottom}><p>{formatSchemePrice(scheme.price)}</p><span className={styles.primary} aria-hidden="true">Lihat detail →</span></div>
    </div>
  </Link>;
}

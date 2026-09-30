import Image from "next/image";
import Link from "next/link";
import { formatSchemePrice, methodLabels, type Scheme } from "@/lib/schemes";
import styles from "./schemes.module.css";

export function SchemeCard({ scheme, href }: { scheme: Scheme; href?: string }) {
  return <article className={styles.card}>
    <Image src={scheme.thumbnail} alt="" width={400} height={108} sizes="(max-width: 639px) 90vw, (max-width: 1023px) 45vw, 440px" className={styles.thumbnail} />
    <div className={styles.cardBody}>
      <h3>{scheme.name}</h3><p className={styles.category}>{scheme.category}</p>
      <ul className={styles.cardMeta}><li>Level {scheme.level} KKNI</li><li>{scheme.units.length} unit kompetensi</li><li>{scheme.methods.map(method => methodLabels[method]).join(" & ")}</li><li>Skema {scheme.type}</li></ul>
      <div className={styles.cardBottom}><p>{formatSchemePrice(scheme.price)}</p><Link className={styles.primary} href={href ?? `/skema-sertifikasi/${scheme.slug}`} aria-label={`Lihat detail ${scheme.name}`}>Lihat detail →</Link></div>
    </div>
  </article>;
}

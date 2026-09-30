import Image from "next/image";
import type { Partner } from "@/lib/partners";
import styles from "./partners.module.css";

export function PartnerGrid({ partners }: { partners: Partner[] }) {
  return <ul className={styles.grid} role="list">{partners.map(partner => {
    const content = partner.logo_url
      ? <Image src={partner.logo_url} alt={partner.name} width={189} height={106} sizes="(max-width: 639px) 40vw, 189px" unoptimized={!partner.logo_url.startsWith("/")} className={styles.logo} />
      : <span className={styles.fallback}>{partner.name}</span>;
    return <li key={partner.name}>{partner.url
      ? <a href={partner.url} target="_blank" rel="noopener noreferrer" aria-label={`Kunjungi website ${partner.name} (tab baru)`}>{content}</a>
      : <div className={styles.logoSlot}>{content}</div>}</li>;
  })}</ul>;
}

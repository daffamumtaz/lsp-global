import Image from "next/image";
import type { Partner } from "@/lib/partners";
import styles from "./partners.module.css";

export function PartnerGrid({ partners, carousel = false }: { partners: Partner[]; carousel?: boolean }) {
  return <ul className={`${styles.grid} ${carousel ? styles.strip : ""}`} role="list">{partners.map(partner => {
    const content = partner.logo_url
      ? <Image src={partner.logo_url} alt={partner.name} width={189} height={106} sizes="(max-width: 639px) 40vw, 189px" unoptimized={!partner.logo_url.startsWith("/")} className={styles.logo} />
      : <span className={styles.logo} aria-hidden="true" />;
    const caption = <span className={`${styles.caption} ${!partner.logo_url ? styles.missingCaption : ""}`} aria-hidden={partner.logo_url ? true : undefined}>{partner.name}{partner.url ? " ↗" : ""}</span>;
    return <li key={partner.name}>{partner.url
      ? <a href={partner.url} target="_blank" rel="noopener noreferrer" aria-label={`Kunjungi website ${partner.name} (tab baru)`}>{content}{caption}</a>
      : <div className={styles.logoSlot}>{content}{caption}</div>}</li>;
  })}</ul>;
}

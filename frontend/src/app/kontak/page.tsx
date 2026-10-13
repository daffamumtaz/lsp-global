import type { Metadata } from "next";
import { Footer, Header } from "@/components/landing/chrome";
import { getLandingContent } from "@/lib/landing";
import { SocialIcon, type SocialName } from "@/components/landing/social-icon";
import { InternalHero } from "@/components/landing/internal-hero";
import { ContactForm } from "@/components/contact/contact-form";
import { getContactInfo } from "@/lib/contact";
import styles from "./page.module.css";
import contact from "@/components/contact/contact.module.css";

export const metadata: Metadata = { title: "Kontak | LSP TIK Global", description: "Hubungi LSP TIK Global untuk informasi sertifikasi, kerja sama, serta masukan dan saran." };
export default async function ContactPage() {
  const { social_links, identity, sections } = await getLandingContent();
  const contactInfo = getContactInfo(identity);
  const { directionsUrl, mapsUrl, whatsappUrl, mapEmbedUrl } = contactInfo;
  return <><Header currentPage="contact" /><main id="main" className="bg-white"><InternalHero white id="contact-title" title={sections.contact_hero.title}>{sections.contact_hero.subtitle}</InternalHero>
    <div className={styles.content}><div className={contact.layout}>
      <aside className={contact.info}><div><h2>{sections.contact_info.title}</h2><p className="mt-4">{sections.contact_info.subtitle}</p></div><div><h3>{sections.contact_details.title}</h3>{sections.contact_details.subtitle && <p>{sections.contact_details.subtitle}</p>}<address><a href={contactInfo.phoneHref}>Telepon: {contactInfo.phone}</a><a href={whatsappUrl} target="_blank" rel="noopener noreferrer">WhatsApp: {contactInfo.whatsapp}<span className="sr-only"> (tab baru)</span></a><a href={`mailto:${contactInfo.email}`}>{contactInfo.email}</a><p>{contactInfo.address}</p>{identity.working_hours && <p>{identity.working_hours}</p>}</address></div><div><h3>{sections.contact_socials.title}</h3>{sections.contact_socials.subtitle && <p>{sections.contact_socials.subtitle}</p>}<div className={contact.socials}>{(["Facebook", "Instagram", "YouTube"] as SocialName[]).map(name => social_links[name as keyof typeof social_links] ? <a key={name} href={social_links[name as keyof typeof social_links]!} target="_blank" rel="noopener noreferrer" aria-label={`${name} (tab baru)`}><SocialIcon name={name} /></a> : <span key={name} role="img" aria-label={`${name} — tautan belum tersedia`} title={`${name} — tautan belum tersedia`}><SocialIcon name={name} /></span>)}</div></div></aside>
      <ContactForm destination={contactInfo} heading={sections.contact_form} />
    </div><section className={styles.location} aria-labelledby="location-title"><h2 id="location-title">{sections.contact_location.title}</h2><p>{sections.contact_location.subtitle}</p><address>{contactInfo.address}</address><div className={styles.map}><iframe title="Peta lokasi kantor LSP TIK Global" src={mapEmbedUrl} loading="lazy" referrerPolicy="no-referrer-when-downgrade" allowFullScreen /></div><div className={contact.actions}><a href={mapsUrl} target="_blank" rel="noopener noreferrer" className={contact.secondary}>Buka Google Maps ↗<span className="sr-only"> (tab baru)</span></a><a href={directionsUrl} target="_blank" rel="noopener noreferrer" className={contact.primary}>Petunjuk arah ↗<span className="sr-only"> (tab baru)</span></a></div><p className={styles.mapHelp}>Jika peta tidak tampil, gunakan tombol Google Maps atau Petunjuk arah.</p></section></div>
  </main><Footer /></>;
}

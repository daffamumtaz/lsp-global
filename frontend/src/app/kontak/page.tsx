import type { Metadata } from "next";
import { Footer, Header } from "@/components/landing/chrome";
import { SocialIcon, type SocialName } from "@/components/landing/social-icon";
import { InternalHero } from "@/components/landing/internal-hero";
import { ContactForm } from "@/components/contact/contact-form";
import { contactInfo, directionsUrl, mapsUrl, whatsappUrl } from "@/lib/contact";
import styles from "./page.module.css";
import contact from "@/components/contact/contact.module.css";

export const metadata: Metadata = { title: "Kontak | LSP TIK Global", description: "Hubungi LSP TIK Global untuk informasi sertifikasi, kerja sama, serta masukan dan saran." };
export default function ContactPage() {
  return <><Header currentPage="contact" /><main id="main"><InternalHero id="contact-title" title="Hubungi Kami">Sampaikan pertanyaan tentang sertifikasi, kebutuhan informasi, atau rencana kerja sama Anda.</InternalHero>
    <div className={styles.content}><div className={contact.layout}>
      <aside className={contact.info}><div><h2>Mari terhubung</h2><p className="mt-4">Gunakan form untuk menyiapkan pesan atau hubungi kami langsung melalui kanal berikut.</p></div><div><h3>Kontak kami</h3><address><a href={contactInfo.phoneHref}>Telepon: {contactInfo.phone}</a><a href={whatsappUrl} target="_blank" rel="noopener noreferrer">WhatsApp: {contactInfo.whatsapp}<span className="sr-only"> (tab baru)</span></a><a href={`mailto:${contactInfo.email}`}>{contactInfo.email}</a><p>{contactInfo.address}</p></address></div><div><h3>Media sosial kami</h3><div className={contact.socials}>{(["Facebook", "Instagram", "YouTube"] as SocialName[]).map(name => <span key={name} title="Tautan belum tersedia"><SocialIcon name={name} />{name}</span>)}</div><p className={`${contact.helpLight} mt-3`}>Tautan media sosial belum tersedia.</p></div></aside>
      <ContactForm />
    </div><section className={styles.location} aria-labelledby="location-title"><h2 id="location-title">Kunjungi kami</h2><p>Temukan kantor LSP TIK Global dan dapatkan petunjuk arah menuju lokasi kami.</p><address>{contactInfo.address}</address><div className={styles.map}><iframe title="Peta lokasi kantor LSP TIK Global" src={`https://www.google.com/maps?q=${encodeURIComponent(`LSP TIK Global, ${contactInfo.address}`)}&output=embed`} loading="lazy" referrerPolicy="no-referrer-when-downgrade" allowFullScreen /></div><div className={contact.actions}><a href={mapsUrl} target="_blank" rel="noopener noreferrer" className={contact.secondary}>Buka Google Maps ↗<span className="sr-only"> (tab baru)</span></a><a href={directionsUrl} target="_blank" rel="noopener noreferrer" className={contact.primary}>Petunjuk arah ↗<span className="sr-only"> (tab baru)</span></a></div><p className={styles.mapHelp}>Jika peta tidak tampil, gunakan tombol Google Maps atau Petunjuk arah.</p></section></div>
  </main><Footer /></>;
}

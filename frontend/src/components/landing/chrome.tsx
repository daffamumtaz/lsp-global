import Link from "next/link";
import { MediaImage } from "./ui";
import { HeaderSearch } from "./header-search";
import { HeaderNavigation, type HeaderPage } from "./header-navigation";
import { getContactInfo } from "@/lib/contact";
import { SocialIcon, type SocialName } from "./social-icon";
import { getLandingContent } from "@/lib/landing";
import { FloatingContact } from "./floating-contact";

export function ContactIcon({ kind }: { kind: "phone" | "email" | "location" | "whatsapp" }) {
  if (kind === "whatsapp") return <SocialIcon name="WhatsApp" className="contact-icon" />;
  return <svg className="contact-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    {kind === "email" ? <><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 6 9 7 9-7" /></> : kind === "location" ? <><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /></> : <path d="m8 3 2 5-3 2a15 15 0 0 0 7 7l2-3 5 2v3a2 2 0 0 1-2 2C10 20 4 14 3 5a2 2 0 0 1 2-2Z" />}
  </svg>;
}

export async function Header({ currentPage = "home" }: { currentPage?: HeaderPage }) {
  const { identity } = await getLandingContent();
  return <header id="beranda" className="site-header-shell">
    <a href="#main" className="skip-link">Lewati ke konten</a>
    <div className="site-header">
      <Link href="/" className="header-brand" aria-label={`${identity.brand_name} — Beranda`}><MediaImage src={identity.logo_url} fallback="imgLogoLspTikGlobal22.png" alt={identity.brand_name} width={115} height={60.41} /></Link>
      <HeaderNavigation currentPage={currentPage} />
      <div className="header-search"><HeaderSearch /></div>
    </div>
  </header>;
}

export async function Footer() {
  const { social_links, identity, sections } = await getLandingContent();
  const contactInfo = getContactInfo(identity);
  const { whatsappUrl } = contactInfo;
  const socialLinks = Object.entries(social_links).filter((entry): entry is [string, string] => Boolean(entry[1])).map(([name, url]) => ({ name: name as SocialName, url }));
  return <>
    <footer id="kontak" className="site-footer">
      <div className="footer-inner">
        <div className="footer-intro"><h2>{identity.footer_heading}</h2>{identity.footer_caption && <p>{identity.footer_caption}</p>}</div>
        <div className="footer-columns">
          <div className="footer-identity">
            <Link href="/" aria-label={`${identity.brand_name} — Beranda`}><MediaImage src={identity.logo_url} fallback="imgLogoLspTikGlobal22.png" alt={identity.brand_name} width={128} height={67.24} /></Link>
            <p>{identity.footer_description}</p>
            <p className="footer-license">Nomor Lisensi: <strong>{identity.license_number}</strong></p>
          </div>
          <nav aria-label="Navigasi footer"><h3>{sections.footer_explore.title}</h3>{sections.footer_explore.subtitle && <p>{sections.footer_explore.subtitle}</p>}<ul>
            <li><Link href="/">Beranda</Link></li><li><Link href="/tentang-kami">Tentang Kami</Link></li>
            <li><Link href="/skema-sertifikasi">Skema Sertifikasi</Link></li><li><Link href="/informasi">Informasi</Link></li><li><Link href="/galeri">Galeri</Link></li><li><Link href="/tentang-kami#lisensi">Lisensi &amp; Legalitas</Link></li><li><Link href="/mitra">Mitra Kami</Link></li><li><Link href="/kontak">Kontak</Link></li>
          </ul></nav>
          <div><h3>{sections.footer_contact.title}</h3>{sections.footer_contact.subtitle && <p>{sections.footer_contact.subtitle}</p>}<address>
            <a href={contactInfo.phoneHref}><ContactIcon kind="phone" /><span>{contactInfo.phone}</span></a>
            <a href={whatsappUrl} target="_blank" rel="noopener noreferrer"><ContactIcon kind="whatsapp" /><span>{contactInfo.whatsapp} <small>WhatsApp</small></span></a>
            <a href={`mailto:${contactInfo.email}`}><ContactIcon kind="email" /><span>{contactInfo.email}</span></a>
            <a href={contactInfo.mapsUrl} target="_blank" rel="noopener noreferrer"><ContactIcon kind="location" /><span>{contactInfo.address}<span className="sr-only"> (peta, tab baru)</span></span></a>
            {identity.working_hours && <p>{identity.working_hours}</p>}
          </address>
            <div className="footer-socials"><h3>{sections.footer_social.title}</h3>{sections.footer_social.subtitle && <p>{sections.footer_social.subtitle}</p>}<ul aria-label="Media sosial">
              {socialLinks.map(social => <li key={social.name}>
                <a href={social.url} target="_blank" rel="noopener noreferrer" aria-label={`${social.name} (tab baru)`}><SocialIcon name={social.name} /></a>
              </li>)}
            </ul></div>
          </div>
        </div>
        <p className="footer-copyright">© {new Date().getFullYear()} {identity.brand_name}. {identity.copyright_text}</p>
      </div>
    </footer>
    <FloatingContact whatsappUrl={whatsappUrl} />
  </>;
}

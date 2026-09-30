import Link from "next/link";
import { FigmaImage, SearchField } from "./ui";
import { HeaderNavigation, type HeaderPage } from "./header-navigation";
import { contactInfo, whatsappUrl } from "@/lib/contact";
import { SocialIcon, type SocialName } from "./social-icon";

export function ContactIcon({ kind }: { kind: "phone" | "email" | "location" | "whatsapp" }) {
  if (kind === "whatsapp") return <SocialIcon name="WhatsApp" className="contact-icon" />;
  return <svg className="contact-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    {kind === "email" ? <><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 6 9 7 9-7" /></> : kind === "location" ? <><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /></> : <path d="m8 3 2 5-3 2a15 15 0 0 0 7 7l2-3 5 2v3a2 2 0 0 1-2 2C10 20 4 14 3 5a2 2 0 0 1 2-2Z" />}
  </svg>;
}

export function Header({ currentPage = "home" }: { currentPage?: HeaderPage }) {
  return <header id="beranda" className="site-header-shell">
    <a href="#main" className="skip-link">Lewati ke konten</a>
    <div className="site-header">
      <Link href="/" className="header-brand" aria-label="LSP TIK Global — Beranda"><FigmaImage file="imgLogoLspTikGlobal22.png" alt="LSP TIK Global" width={115} height={60.41} preload /></Link>
      <HeaderNavigation currentPage={currentPage} />
      <div className="header-search"><SearchField compact /></div>
    </div>
  </header>;
}

const defaultSocialLinks: { name: SocialName; url: string | null }[] = [
  { name: "Facebook", url: null },
  { name: "Instagram", url: null },
  { name: "YouTube", url: null },
];

export function Footer({ socialLinks = defaultSocialLinks }: {
  socialLinks?: { name: SocialName; url: string | null }[];
} = {}) {
  return <>
    <footer id="kontak" className="site-footer">
      <div className="footer-inner">
        <div className="footer-intro"><h2>Bersama Membangun Kompetensi</h2><p>Kami hadir untuk bersama membangun kompetensi yang terpercaya.</p></div>
        <div className="footer-columns">
          <div className="footer-identity">
            <Link href="/" aria-label="LSP TIK Global — Beranda"><FigmaImage file="imgLogoLspTikGlobal22.png" alt="LSP TIK Global" width={128} height={67.24} /></Link>
            <p>Lembaga Sertifikasi Profesi bidang Teknologi Informasi &amp; Komunikasi berlisensi BNSP.</p>
            <p className="footer-license">Nomor Lisensi: <strong>BNSP-LSP-2150-ID</strong></p>
          </div>
          <nav aria-label="Navigasi footer"><h3>Jelajahi</h3><ul>
            <li><Link href="/">Beranda</Link></li><li><Link href="/tentang-kami">Tentang Kami</Link></li>
            <li><Link href="/skema-sertifikasi">Skema Sertifikasi</Link></li><li><Link href="/informasi">Informasi</Link></li><li><Link href="/galeri">Galeri</Link></li><li><Link href="/tentang-kami#lisensi">Lisensi &amp; Legalitas</Link></li><li><Link href="/mitra">Mitra Kami</Link></li><li><Link href="/kontak">Kontak</Link></li>
          </ul></nav>
          <div><h3>Hubungi Kami</h3><address>
            <a href={contactInfo.phoneHref}><ContactIcon kind="phone" /><span>{contactInfo.phone}</span></a>
            <a href={whatsappUrl} target="_blank" rel="noopener noreferrer"><ContactIcon kind="whatsapp" /><span>{contactInfo.whatsapp} <small>WhatsApp</small></span></a>
            <a href={`mailto:${contactInfo.email}`}><ContactIcon kind="email" /><span>{contactInfo.email}</span></a>
            <div><ContactIcon kind="location" /><p>{contactInfo.address}</p></div>
          </address>
            <div className="footer-socials"><h3>Ikuti Kami</h3><ul aria-label="Media sosial">
              {socialLinks.map(social => <li key={social.name}>{social.url ?
                <a href={social.url} target="_blank" rel="noopener noreferrer" aria-label={`${social.name} (tab baru)`}><SocialIcon name={social.name} /><span>{social.name}</span></a> :
                <span className="social-placeholder" aria-label={`${social.name} — tautan belum tersedia`} title={`${social.name} — tautan belum tersedia`}><SocialIcon name={social.name} /><span>{social.name}</span></span>
              }</li>)}
            </ul></div>
          </div>
        </div>
        <p className="footer-copyright">© 2026 LSP TIK Global. All rights reserved.</p>
      </div>
    </footer>
    <a className="whatsapp-bubble" href={whatsappUrl} target="_blank" rel="noopener noreferrer" aria-label="Hubungi melalui WhatsApp (buka tab baru)">
      <ContactIcon kind="whatsapp" /><span className="whatsapp-tooltip" aria-hidden="true">Hubungi melalui WhatsApp</span>
    </a>
  </>;
}

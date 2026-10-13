"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";

export type HeaderPage = "home" | "about" | "information" | "gallery" | "schemes" | "contact" | "partners";
const navigation = [
  { key: "home", label: "Beranda", href: "/" },
  { key: "about", label: "Tentang Kami", href: "/tentang-kami", sections: [
    ["Visi & Misi", "/tentang-kami#visi-misi"], ["Struktur", "/tentang-kami#struktur"],
    ["Lisensi", "/tentang-kami#lisensi"],
  ] },
  { key: "schemes", label: "Skema Sertifikasi", href: "/skema-sertifikasi" },
  { key: "information", label: "Informasi", href: "/informasi", sections: [
    ["Tempat Uji Kompetensi (TUK)", "/informasi#tuk"], ["Asesor Kompetensi", "/informasi#asesor"],
    ["Alur Asesmen Daring", "/informasi#alur-asesmen"],
  ] },
  { key: "gallery", label: "Galeri", href: "/galeri" },
  { key: "contact", label: "Kontak", href: "/kontak" },
];

export function HeaderNavigation({ currentPage }: { currentPage: HeaderPage }) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [openDropdown, setOpenDropdown] = useState<string | null>(null);
  const root = useRef<HTMLDivElement>(null);
  const menuButton = useRef<HTMLButtonElement>(null);
  const dropdownButtons = useRef<Record<string, HTMLButtonElement | null>>({});

  function close() { setMobileOpen(false); setOpenDropdown(null); }

  useEffect(() => {
    function outside(event: PointerEvent) {
      if (!root.current?.contains(event.target as Node)) close();
    }
    document.addEventListener("pointerdown", outside);
    return () => document.removeEventListener("pointerdown", outside);
  }, []);

  return (
    <div className="header-navigation" ref={root} onBlur={event => {
      if (!event.currentTarget.contains(event.relatedTarget)) close();
    }} onKeyDown={event => {
      if (event.key !== "Escape") return;
      event.preventDefault();
      if (openDropdown) { dropdownButtons.current[openDropdown]?.focus(); setOpenDropdown(null); }
      else { setMobileOpen(false); menuButton.current?.focus(); }
    }}>
      <button ref={menuButton} className="mobile-menu-toggle" type="button" aria-expanded={mobileOpen} aria-controls="main-navigation" onClick={() => { setMobileOpen(value => !value); setOpenDropdown(null); }}>
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path d={mobileOpen ? "m6 6 12 12M6 18 18 6" : "M4 6h16M4 12h16M4 18h16"} /></svg>
        Menu
      </button>
      <nav id="main-navigation" aria-label="Navigasi utama" className="main-navigation" data-open={mobileOpen}>
        <ul className="navigation-list">
          {navigation.map(item => item.sections ? <li key={item.key} className="about-navigation" onBlur={event => {
            if (!event.currentTarget.contains(event.relatedTarget)) setOpenDropdown(value => value === item.key ? null : value);
          }} onMouseEnter={() => {
            if (window.matchMedia("(hover: hover) and (min-width: 1200px)").matches) setOpenDropdown(item.key);
          }} onMouseLeave={event => {
            if (!event.currentTarget.contains(document.activeElement)) setOpenDropdown(value => value === item.key ? null : value);
          }}>
            <div className="about-navigation-trigger">
              <Link href={item.href} aria-current={currentPage === item.key ? "page" : undefined} onClick={close}>{item.label}</Link>
              <button ref={element => { dropdownButtons.current[item.key] = element; }} type="button" aria-label={`Bagian ${item.label}`} aria-expanded={openDropdown === item.key} aria-controls={`${item.key}-navigation-links`} onClick={() => setOpenDropdown(value => value === item.key ? null : item.key)}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
              </button>
            </div>
            <ul id={`${item.key}-navigation-links`} className="about-dropdown" hidden={openDropdown !== item.key}>
              {item.sections.map(([label, href]) => <li key={href}><Link href={href} onClick={close}>{label}</Link></li>)}
            </ul>
          </li> : <li key={item.key}><Link href={item.href} aria-current={currentPage === item.key ? "page" : undefined} onClick={close}>{item.label}</Link></li>)}
        </ul>
      </nav>
    </div>
  );
}

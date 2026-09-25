import { Divider, FigmaImage, SearchField } from "./ui";

const navigation = [
  { label: "Tentang Kami", href: "#tentang-kami" },
  { label: "Skema Sertifikasi", href: "#sertifikasi" },
  { label: "Informasi" },
  { label: "Galeri" },
  { label: "Kontak", href: "#kontak" },
];

function NavigationItems() {
  return navigation.map(({ label, href }) => <li key={label}>{href ? <a href={href}>{label}</a> : <button type="button" disabled>{label}</button>}</li>);
}

export function Header() {
  return (
    <header id="beranda" className="bg-white">
      <a href="#main" className="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-4">Lewati ke konten</a>
      <div className="site-header mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-5 px-[17px] py-3">
        <a href="#beranda" aria-label="LSP TIK Global — Beranda"><FigmaImage file="imgLogoLspTikGlobal22.png" alt="LSP TIK Global" width={115} height={60.41} preload /></a>
        <nav aria-label="Navigasi utama" className="main-navigation order-3 w-full overflow-x-auto font-inter text-sm font-medium lg:order-none lg:w-auto lg:text-xl"><ul className="flex items-center justify-between gap-[21px] whitespace-nowrap"><NavigationItems /></ul></nav>
        <SearchField compact />
      </div>
    </header>
  );
}

function ContactIcon({ file }: { file: string }) {
  return <span className="flex size-[30px] shrink-0 items-center justify-center rounded-full bg-[#d9d9d9]"><FigmaImage file={file} width={24} height={24} /></span>;
}

export function Footer() {
  return (
    <footer id="kontak" className="bg-white">
      <div className="footer-inner mx-auto max-w-[1440px] px-6 pt-11 pb-[19px] xl:px-[34px]">
        <h2 className="text-3xl leading-tight font-bold md:text-4xl md:leading-6">Bersama Membangun Kompetensi</h2>
        <p className="mt-[18px] text-xl leading-6 text-muted">Kami hadir untuk bersama membangun kompetensi yang terpercaya.</p>
        <div className="mt-[21px]"><Divider file="imgLine12.svg" /></div>
        <div className="footer-columns grid gap-10 pt-[34px] pb-[23px] md:grid-cols-2 xl:grid-cols-[1.4fr_0.7fr_1fr] xl:gap-8">
          <div>
            <FigmaImage file="imgLogoLspTikGlobal22.png" alt="LSP TIK Global" width={178} height={93.51} />
            <p className="mt-[19px] max-w-[513px] text-xl leading-6"><strong>LSP TIK Global</strong> – Lembaga Sertifikasi Profesi bidang Teknologi Informasi &amp; Komunikasi berlisensi BNSP</p>
            <p className="mt-6 text-xl leading-6 italic">Nomor Lisensi: BNSP-LSP-2150-ID</p>
          </div>
          <nav aria-label="Navigasi footer"><h3 className="text-2xl leading-6 font-bold">Navigasi</h3><ul className="mt-[9px] space-y-2.5 text-base leading-6 text-muted"><li><a href="#beranda">Beranda</a></li><NavigationItems /></ul></nav>
          <div>
            <h3 className="text-2xl leading-6 font-bold">Kontak Kami</h3>
            <address className="mt-2 space-y-[11px] text-xl leading-6 text-muted not-italic">
              <div className="flex items-center gap-[11px]"><ContactIcon file="phone.png" /><span><a className="underline" href="tel:02127651353">021-27651353</a> / <a className="underline" href="tel:+6285195533360">+6285195533360</a></span></div>
              <div className="flex items-center gap-[11px]"><ContactIcon file="email.png" /><a className="underline" href="mailto:info@lspglobal.id">info@lspglobal.id</a></div>
              <div className="flex items-center gap-[11px]"><ContactIcon file="location.png" /><p>Jl. Lebak Bulus Raya Komplek Ruko Victory 88 No. 88 B Cilandak Jakarta Selatan</p></div>
            </address>
            <h4 className="mt-2 text-xl leading-6 font-bold text-black">Ikuti Kami</h4>
            <ul className="mt-2 flex gap-3" aria-label="Media sosial">{["Facebook", "Youtube", "Instagram"].map(name => <li key={name}><button type="button" disabled aria-label={name}><ContactIcon file={`${name.toLowerCase()}.png`} /></button></li>)}</ul>
          </div>
        </div>
        <Divider file="imgLine13.svg" />
        <p className="mt-[22px] text-center text-xs leading-6 text-muted">Copyright © 2026 LSP TIK Global. All Rights Reserved.</p>
      </div>
    </footer>
  );
}

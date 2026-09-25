import { BenefitCard, CertificationCard, ReviewCard, type Benefit } from "@/components/landing/cards";
import { Footer, Header } from "@/components/landing/chrome";
import { Contours, DesignButton, FigmaImage, SearchField, SectionHeading } from "@/components/landing/ui";

const benefits: Benefit[] = [
  { title: "Sertifikat Kompetensi Diakui Secara Nasional", description: "Memiliki sertifikat kompetensi resmi dari BNSP RI sebagai bukti sah keterampilan Anda." },
  { title: "Skema Sertifikasi Berbasis SKKNI", description: "Seluruh skema sertifikasi mengacu pada SKKNI dan peraturan ESDM terbaru serta kebutuhan industri." },
  { title: "Jaringan TUK di Berbagai Daerah", description: "Memiliki jaringan Tempat Uji Kompetensi (TUK) yang luas dan tersebar di berbagai wilayah Indonesia." },
];
const statistics = [
  { value: "39", label: <>Skema<br />Sertifikasi</> },
  { value: "15+", label: <>Tempat Uji<br />Kompetensi (TUK)</> },
  { value: "72+", label: <>Asesor<br />Kompeten</> },
];

export default function Home() {
  return (
    <>
      <Header />
      <main id="main">
        <section className="hero relative isolate overflow-hidden bg-brand text-white" aria-labelledby="hero-title">
          <div className="hero-waves pointer-events-none absolute -z-10" aria-hidden="true" />
          <div className="hero-content mx-auto grid max-w-[1292px] items-start gap-12 px-6 lg:grid-cols-[1.22fr_1fr] xl:gap-[103px] xl:px-0">
            <div>
              <h1 id="hero-title" className="max-w-[628px] text-[44px] leading-none font-bold tracking-[-0.03em] md:text-[64px]">Wujudkan Kompetensi Profesional Anda</h1>
              <p className="mt-5 max-w-[628px] text-xl leading-tight tracking-[-0.03em] md:text-2xl md:leading-none"><strong>LSP TIK Global</strong> siap mencetak Sumber Daya Manusia (SDM) TIK yang unggul, profesional, dan kompeten untuk menghadapi tantangan industri global.</p>
              <div className="mt-[44px] flex flex-wrap items-center gap-[17px]"><SearchField /><a href="#kontak" className="flex h-[57px] w-[194px] items-center justify-center rounded-xl border border-white bg-brand font-jakarta text-xl font-bold">Hubungi Kami</a></div>
            </div>
            <button type="button" disabled aria-label="Video profil LSP TIK Global" className="hero-video mt-[13px] w-full max-w-[515px] overflow-hidden rounded-xl"><FigmaImage file="imgRectangle374.png" alt="Pratinjau video profil dengan pemandangan kota saat matahari terbenam" width={515} height={290} className="aspect-[515/290] w-full object-cover" preload /></button>
          </div>
        </section>

        <section id="tentang-kami" className="benefits-section relative isolate overflow-hidden px-6" aria-label="Mengapa memilih kami">
          <Contours section="benefits" />
          <SectionHeading subtitle="Kami berkomitmen memberikan layanan sertifikasi kompetensi yang profesional, terpercaya, dan berorientasi pada kebutuhan dunia kerja." highlight="wide">Mengapa Memilih <span className="text-[#022d8a]">LSP TIK Global?</span></SectionHeading>
          <div className="benefits-grid relative mx-auto grid max-w-[1268px] gap-[25px] lg:grid-cols-3">{benefits.map(benefit => <BenefitCard key={benefit.title} {...benefit} />)}</div>
        </section>

        <section aria-label="Statistik LSP TIK Global" className="statistics-section bg-section px-6 text-brand-dark">
          <dl className="mx-auto grid max-w-[1054px] grid-cols-3 gap-6 text-center">{statistics.map(stat => <div key={stat.value} className="flex flex-col items-center"><dd className="text-5xl leading-[86px] font-extrabold sm:text-7xl lg:text-[96px]">{stat.value}</dd><dt className="mt-3 text-base leading-6 md:text-2xl">{stat.label}</dt></div>)}</dl>
        </section>

        <section id="sertifikasi" className="schemes-section relative isolate overflow-hidden px-6" aria-label="Skema sertifikasi">
          <Contours section="schemes" />
          <SectionHeading subtitle="Cari dan pilih skema sertifikasi yang sesuai dengan kebutuhan Anda" highlight="short">Skema Sertifikasi</SectionHeading>
          <div className="scheme-controls relative flex flex-wrap justify-center gap-7">
            <label className="relative"><span className="sr-only">Kategori skema sertifikasi</span><select className="h-[39px] w-[236px] appearance-none rounded-xl border border-action bg-white py-1.5 pr-8 pl-3 font-jakarta text-base text-black" defaultValue="all"><option value="all">Semua Skema (6 Unit)</option></select><span className="pointer-events-none absolute top-[7px] right-2"><FigmaImage file="imgKeyboardArrowDown.svg" width={24} height={24} /></span></label>
            <DesignButton className="w-[217px]">Lihat Seluruh Sertifikasi</DesignButton>
          </div>
          <div className="relative mx-auto mt-[33px] grid max-w-[1290px] gap-[45px] lg:grid-cols-3">{[false, true, false].map((remote, index) => <CertificationCard key={index} remote={remote} />)}</div>
          <p className="relative mt-[33px] text-center text-base leading-none tracking-[-0.03em] text-muted italic underline"><a href="#kontak">Butuh informasi lanjut mengenai sertifikasi?</a></p>
        </section>

        <section className="reviews-section bg-section px-6" aria-label="Review kami">
          <SectionHeading subtitle={<>Apa kata mereka tentang <em>LSP TIK Global?</em></>} accent>Review Kami</SectionHeading>
          <div className="reviews-grid mx-auto grid max-w-[1298px] gap-10 lg:grid-cols-3">{[0, 1, 2].map(index => <ReviewCard key={index} first={index === 0} />)}</div>
        </section>

        <section id="mitra" className="partners-section relative isolate overflow-hidden px-6" aria-label="Mitra kami">
          <Contours section="partners" />
          <SectionHeading subtitle="Dipercaya oleh berbagai perusahaan dan institusi pendidikan" highlight="partners">Mitra Kami</SectionHeading>
          <div className="relative mx-auto max-w-[1298px]">
            <div className="partner-arrows flex justify-end gap-10">{["Left", "Right"].map(direction => <button key={direction} disabled type="button" aria-label={direction === "Left" ? "Mitra sebelumnya" : "Mitra berikutnya"}><FigmaImage file={`imgChevron${direction}.svg`} width={40} height={40} /></button>)}</div>
            <ul className="partner-grid mx-auto grid grid-cols-2 justify-items-center sm:grid-cols-5">{Array.from({ length: 10 }, (_, index) => <li key={index}><FigmaImage file="imgImage20.png" alt="Jobhun" width={189} height={106} className="h-auto w-[189px]" /></li>)}</ul>
          </div>
          <div className="relative mt-[53px] text-center"><DesignButton className="w-[183px]">Lihat Seluruh Mitra</DesignButton></div>
        </section>
      </main>
      <Footer />
    </>
  );
}

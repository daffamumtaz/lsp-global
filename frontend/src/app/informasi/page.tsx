import type { Metadata } from "next";
import Image from "next/image";
import { Footer, Header } from "@/components/landing/chrome";
import { InternalHero } from "@/components/landing/internal-hero";
import { DirectoryTable } from "@/components/information/directory-table";
import { sampleAssessors, sampleTuks } from "@/lib/information";
import styles from "./page.module.css";

export const metadata: Metadata = {
  title: "Informasi | LSP TIK Global",
  description: "Informasi tempat uji kompetensi, asesor kompetensi, dan alur asesmen jarak jauh LSP TIK Global.",
};

export default function InformationPage() {
  return <>
    <Header currentPage="information" />
    <main id="main">
      <InternalHero id="information-title" title="Informasi">Temukan informasi mengenai tempat uji kompetensi, asesor, dan alur sertifikasi jarak jauh <strong>LSP TIK Global</strong>.</InternalHero>
      <section id="tuk" className={styles.section} aria-labelledby="tuk-title">
        <div className={styles.inner}>
          <div className={styles.heading}><h2 id="tuk-title">Tempat Uji Kompetensi <span>(TUK)</span></h2><p>Temukan lokasi tempat uji kompetensi berdasarkan provinsi, kota, atau nama TUK.</p></div>
          <DirectoryTable id="tuk" title="TUK" entries={sampleTuks} filterKey="province" filterLabel="Provinsi" columns={[
            { key: "name", label: "Nama TUK" }, { key: "code", label: "Kode TUK" }, { key: "city", label: "Kota/Kabupaten" }, { key: "province", label: "Provinsi" }, { key: "address", label: "Alamat" },
          ]} />
        </div>
      </section>
      <section id="asesor" className={`${styles.section} ${styles.alternate}`} aria-labelledby="assessor-title">
        <div className={styles.inner}>
          <div className={styles.heading}><h2 id="assessor-title">Asesor Kompetensi</h2><p>Kenali peran asesor dalam menilai kompetensi peserta sesuai skema sertifikasi.</p></div>
          <DirectoryTable id="asesor" title="Asesor" entries={sampleAssessors} filterKey="field" filterLabel="Bidang" columns={[
            { key: "name", label: "Nama Asesor" }, { key: "certificate", label: "Nomor Sertifikat (MET)" }, { key: "field", label: "Bidang" },
          ]} />
        </div>
      </section>
      <section id="alur-asesmen" className={styles.section} aria-labelledby="flow-title">
        <div className={styles.inner}>
          <div className={styles.heading}><h2 id="flow-title">Alur Asesmen Daring</h2><p>Panduan tahapan pelaksanaan uji kompetensi secara daring, mulai dari pendaftaran hingga hasil asesmen.</p></div>
          <figure className={styles.flowFigure}>
            <a href="/figma/remote-assessment-flow.png" target="_blank" rel="noopener noreferrer" aria-label="Buka diagram alur asesmen jarak jauh ukuran penuh (tab baru)">
              <Image src="/figma/remote-assessment-flow.png" alt="Diagram alur asesmen jarak jauh: pendaftaran, penjadwalan pra-asesmen, pra-asesmen, persiapan pelaksanaan, asesmen, dan pasca-asesmen. Ringkasan tersedia di bawah gambar." width={1287} height={866} sizes="(max-width: 767px) calc(100vw - 40px), (max-width: 1340px) calc(100vw - 48px), 1239px" className={styles.flowImage} />
            </a>
            <figcaption><span>Geser ke ringkasan di bawah atau buka gambar untuk membaca detail.</span><a href="/figma/remote-assessment-flow.png" target="_blank" rel="noopener noreferrer">Lihat diagram ukuran penuh ↗</a></figcaption>
          </figure>
          <ol className={styles.steps} aria-label="Ringkasan tahapan asesmen jarak jauh">
            <li><h3>Pendaftaran</h3><p>Peserta mengajukan permohonan dan mengisi formulir yang diperlukan. Admin memverifikasi pendaftaran.</p></li>
            <li><h3>Penjadwalan pra-asesmen</h3><p>Admin menyiapkan tautan pertemuan daring untuk pra-asesmen.</p></li>
            <li><h3>Pra-asesmen</h3><p>Asesor memverifikasi formulir, memberi rekomendasi pelaksanaan, dan menjelaskan mekanisme asesmen.</p></li>
            <li><h3>Persiapan pelaksanaan</h3><p>Admin menyiapkan tautan pertemuan daring untuk asesmen.</p></li>
            <li><h3>Pelaksanaan asesmen</h3><p>Asesor menjalankan dan meninjau proses asesmen, kemudian membuat rekomendasi hasil. Peserta mengisi umpan balik.</p></li>
            <li><h3>Pasca-asesmen</h3><p>Hasil kompeten dilanjutkan dengan penerbitan dan pengiriman sertifikat. Hasil belum kompeten memiliki opsi asesmen ulang.</p></li>
          </ol>
        </div>
      </section>
    </main>
    <Footer />
  </>;
}

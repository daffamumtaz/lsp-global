import { DesignButton, Divider, FigmaImage } from "./ui";

export type Benefit = { title: string; description: string };

export function BenefitCard({ title, description }: Benefit) {
  return (
    <article className="benefit-card rounded-xl border-2 border-divider bg-white px-[29px] pt-[19px] pb-7">
      <FigmaImage file="award.png" width={70} height={70} />
      <h3 className="mt-[15px] max-w-[345px] text-2xl leading-7 font-semibold">{title}</h3>
      <p className="mt-2.5 max-w-[349px] text-base leading-5">{description}</p>
    </article>
  );
}

export function CertificationCard({ remote = false }: { remote?: boolean }) {
  return (
    <article className="certification-card overflow-hidden rounded-xl border-2 border-divider bg-white">
      <FigmaImage file="certification.jpg" alt="Ilustrasi teknologi untuk sertifikasi Junior Coder" width={400} height={108} className="h-[108px] w-full object-cover" />
      <div className="px-[18px] pt-4 pb-[18px]">
        <h3 className="text-2xl leading-7 font-semibold">Junior Coder</h3>
        <p className="mt-2.5 text-base leading-5 text-muted">Programming dan Pengembangan Software</p>
        <ul className="my-[19px] grid list-disc grid-cols-2 gap-x-5 gap-y-[9px] pl-[18px] text-xs leading-5">
          <li>Level 2 KKNI</li><li>8 Unit Kompetensi</li>
          <li>{remote ? "Tatap Muka & Jarak Jauh" : "Hanya Tatap Muka"}</li><li>Skema Okupasi</li>
        </ul>
        <Divider />
        <div className="mt-[17px] flex items-center justify-between gap-3">
          <p className="text-2xl leading-7 font-bold">Rp1.000.000</p>
          <DesignButton className="w-[140px]">Detail</DesignButton>
        </div>
      </div>
    </article>
  );
}

export function ReviewCard({ first = false }: { first?: boolean }) {
  return (
    <figure className="review-card rounded-xl border-2 border-divider bg-white px-[25px] pt-[27px] pb-[19px]">
      <FigmaImage file="imgGroup45.svg" width={86} height={61.4603} className="ml-1" />
      <blockquote className="mt-[15px] ml-1 max-w-[329px] text-base leading-5">Proses pendaftaran hingga pelaksanaan Uji Kompetensi berjalan sangat lancar. Skema sertifikasinya benar-benar relevan dengan teknologi yang saya gunakan sehari-hari.</blockquote>
      <div className="mt-[25px]"><Divider file="imgLine11.svg" /></div>
      <figcaption className="mt-2.5 flex items-end gap-[14px]">
        <FigmaImage file={first ? "imgEllipse45.png" : "imgEllipse46.svg"} width={40} height={40} className="shrink-0 rounded-full" />
        <div className="pb-0.5"><p className="text-base leading-4 font-semibold">Daffa Naufal Mumtaz</p><p className="mt-1.5 text-xs leading-3 italic">Head of XYZ Company, West Java</p></div>
      </figcaption>
    </figure>
  );
}

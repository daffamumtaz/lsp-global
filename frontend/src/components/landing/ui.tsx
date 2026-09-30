import Image from "next/image";
import type { ReactNode } from "react";

export function FigmaImage({ file, alt = "", width, height, className = "", preload = false }: {
  file: string; alt?: string; width: number; height: number; className?: string; preload?: boolean;
}) {
  return <Image src={`/figma/${file}`} alt={alt} width={width} height={height} className={className} preload={preload} />;
}

export function MediaImage({ src, fallback, alt, width, height, className = "" }: {
  src: string | null; fallback: string; alt: string; width: number; height: number; className?: string;
}) {
  return <Image src={src ?? `/figma/${fallback}`} alt={alt} width={width} height={height} className={className} unoptimized={Boolean(src)} />;
}

export function SectionHeading({ children, subtitle, accent = false, highlight }: {
  children: ReactNode; subtitle: ReactNode; accent?: boolean; highlight?: "wide" | "short" | "partners";
}) {
  return (
    <div className="section-heading relative mx-auto max-w-[953px] text-center">
      {highlight && <span className={`heading-highlight heading-highlight-${highlight}`} aria-hidden="true">
        <FigmaImage file={highlight === "wide" ? "imgElement.svg" : highlight === "partners" ? "imgElement2.svg" : "imgElement1.svg"} width={highlight === "wide" ? 715.108 : 407.293} height={highlight === "wide" ? 39.5701 : 32.3778} />
      </span>}
      <h2 className={`relative text-4xl leading-none font-bold tracking-[-0.03em] md:text-5xl xl:text-[64px] ${accent ? "text-brand-dark" : ""}`}>{children}</h2>
      <p className="relative mt-5 text-lg leading-tight tracking-[-0.03em] text-muted md:text-2xl md:leading-none">{subtitle}</p>
    </div>
  );
}

export function Contours({ section }: { section: "benefits" | "schemes" | "partners" }) {
  return <div aria-hidden="true" className={`contours contours-${section}`} />;
}

export function SearchField({ compact = false }: { compact?: boolean }) {
  return (
    <form action="/skema-sertifikasi" method="get" role="search" aria-label={compact ? "Pencarian skema di header" : "Pencarian skema di Beranda"} className={`search-field flex min-h-[44px] items-center gap-2 rounded-xl bg-white font-jakarta ${compact ? "w-[177px] border border-[#404040] pl-3 pr-1" : "min-h-[60px] w-full border-2 border-[#00897a] pl-3 pr-1 sm:w-[368px]"}`}>
      <label className="flex min-w-0 flex-1 items-center gap-2">
      <FigmaImage file={compact ? "imgFrame1.svg" : "imgFrame.svg"} width={20} height={20} className="shrink-0" />
      <span className="sr-only">Cari Skema{compact ? "" : " Sertifikasi"}</span>
      <input name="q" type="search" autoComplete="off" placeholder={compact ? "Skema…" : "Cari skema…"} className="min-w-0 w-full bg-transparent text-base font-medium text-foreground outline-none placeholder:text-[#647478]" />
      </label>
      <button type="submit" aria-label="Cari skema sertifikasi" className="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg bg-brand-dark px-3 text-sm font-semibold text-white hover:bg-[#003475]">{compact ? "→" : "Cari"}</button>
    </form>
  );
}

// Only the current frame is implemented; controls without a supplied destination
// remain disabled instead of inventing routes, video URLs, or application flows.
export function DesignButton({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <button type="button" disabled className={`design-button ${className}`}>{children}</button>;
}

export function Divider({ file = "imgLine10.svg" }: { file?: string }) {
  return <div className="overflow-hidden" aria-hidden="true"><FigmaImage file={file} width={file === "imgLine12.svg" || file === "imgLine13.svg" ? 1387 : 365.049} height={1} className="max-w-none" /></div>;
}

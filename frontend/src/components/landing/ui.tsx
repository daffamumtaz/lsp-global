import Image from "next/image";
import type { ReactNode } from "react";

export function FigmaImage({ file, alt = "", width, height, className = "", preload = false }: {
  file: string; alt?: string; width: number; height: number; className?: string; preload?: boolean;
}) {
  return <Image src={`/figma/${file}`} alt={alt} width={width} height={height} className={className} preload={preload} />;
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
    <label className={`search-field flex items-center gap-3 rounded-xl bg-white font-jakarta ${compact ? "h-[39px] w-[177px] border border-[#404040] px-3" : "h-[60px] w-full border-2 border-[#00897a] px-3 sm:w-[368px]"}`}>
      <FigmaImage file={compact ? "imgFrame1.svg" : "imgFrame.svg"} width={20} height={20} className="shrink-0" />
      <span className="sr-only">Cari Skema{compact ? "" : " Sertifikasi"}</span>
      <input type="search" placeholder={compact ? "Cari Skema" : "Cari Skema Sertifikasi"} className="min-w-0 w-full bg-transparent text-base font-medium text-foreground outline-none placeholder:text-[#bdbdbd]" />
    </label>
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

import Image from "next/image";
import type { ReactNode } from "react";
import { MagnifyingGlassIcon } from "@heroicons/react/24/outline";

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

export function SectionHeading({ children, subtitle, highlight }: {
  children: ReactNode; subtitle: ReactNode; accent?: boolean; highlight?: "wide" | "short" | "partners";
}) {
  return (
    <div className="section-heading relative mx-auto max-w-[953px] text-center">
      {highlight && <span className={`heading-highlight heading-highlight-${highlight}`} aria-hidden="true">
        <FigmaImage file={highlight === "wide" ? "imgElement.svg" : highlight === "partners" ? "imgElement2.svg" : "imgElement1.svg"} width={highlight === "wide" ? 715.108 : 407.293} height={highlight === "wide" ? 39.5701 : 32.3778} />
      </span>}
      <h2 className="relative text-[length:var(--text-section)] leading-tight font-bold tracking-[-0.025em] text-brand-dark">{children}</h2>
      <p className="relative mx-auto mt-4 max-w-[780px] text-[length:var(--text-intro)] leading-relaxed text-muted">{subtitle}</p>
    </div>
  );
}

export function Contours({ section }: { section: "benefits" | "schemes" | "partners" }) {
  return <div aria-hidden="true" className={`contours contours-${section}`} />;
}

export function SearchField({ compact = false }: { compact?: boolean }) {
  return (
    <form action="/skema-sertifikasi" method="get" role="search" aria-label={compact ? "Pencarian skema di header" : "Pencarian skema di Beranda"} className={`search-field ${compact ? "search-field-compact" : "search-field-hero"}`}>
      <label>
      <MagnifyingGlassIcon aria-hidden="true" className="search-icon" />
      <span className="sr-only">Cari skema sertifikasi</span>
      <input name="q" type="search" autoComplete="off" placeholder="Cari skema…" />
      </label>
      <button type="submit" aria-label="Cari skema sertifikasi">Cari</button>
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

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

export function SectionHeading({ children, subtitle }: {
  children: ReactNode; subtitle: ReactNode;
}) {
  return (
    <div className="section-heading relative mx-auto max-w-[953px] text-center">
      <h2 className="relative text-[length:var(--text-section)] leading-tight font-bold tracking-[-0.025em] text-brand-dark">{children}</h2>
      {subtitle && <p className="relative mx-auto mt-4 max-w-[780px] text-[length:var(--text-intro)] leading-relaxed text-muted">{subtitle}</p>}
    </div>
  );
}

export function SearchField({ compact = false, placeholder }: { compact?: boolean; placeholder?: string }) {
  return (
    <form action="/skema-sertifikasi" method="get" role="search" aria-label={compact ? "Pencarian skema di header" : "Pencarian skema di Beranda"} className={`search-field ${compact ? "search-field-compact" : "search-field-hero"}`}>
      <label>
      <span className="sr-only">Cari skema sertifikasi</span>
      <input name="q" type="search" autoComplete="off" placeholder={placeholder ?? (compact ? "Cari skema…" : "Cari Skema, cth: Data Analyst")} />
      </label>
      <button type="submit" aria-label="Cari skema sertifikasi"><MagnifyingGlassIcon aria-hidden="true" className="search-icon" /></button>
    </form>
  );
}

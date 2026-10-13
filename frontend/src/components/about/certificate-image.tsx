"use client";

import Image from "next/image";
import { useState, type ReactNode } from "react";
import { GalleryViewer } from "@/components/gallery/gallery-viewer";
import type { ViewerItem } from "@/lib/gallery";

export function CertificateImage({ src, title, description, className, cardClassName, children }: {
  src: string; title: string; description: string; className: string; cardClassName: string; children: ReactNode;
}) {
  const [opener, setOpener] = useState<HTMLElement | null>(null);
  const item: ViewerItem = {
    activity: { id: src, title, description, year: 0, date: null, location: "", media: [] },
    media: { id: src, type: "photo", src, thumbnail: src, alt: title, width: 266, height: 357 },
  };
  return <>
    <button type="button" className={`certificate-preview ${cardClassName}`} aria-label={`Lihat Sertifikat ${title}`} onClick={event => setOpener(event.currentTarget)}>
      <span className="certificate-media"><Image src={src} alt={title} unoptimized={!src.startsWith("/")} width={266} height={357} className={className} /><span className="certificate-hint">Lihat Sertifikat ↗</span></span>
      {children}
    </button>
    {opener && <GalleryViewer items={[item]} initialMediaId={src} opener={opener} onClose={() => setOpener(null)} showMetadata={false} />}
  </>;
}

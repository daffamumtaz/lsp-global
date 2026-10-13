"use client";

import Image from "next/image";
import { useState } from "react";
import { GalleryViewer } from "@/components/gallery/gallery-viewer";
import type { ViewerItem } from "@/lib/gallery";
import styles from "@/app/tentang-kami/page.module.css";

const crops = [
  { left: 15, top: 24, width: 278, height: 402 },
  { left: 315, top: 72, width: 278, height: 238 },
  { left: 315, top: 331, width: 278, height: 238 },
];

export function ProfileCollage({ photos }: { photos: (string | null)[] }) {
  const [opened, setOpened] = useState<{ id: string; opener: HTMLElement } | null>(null);
  const items: ViewerItem[] = crops.map((crop, index) => {
    const src = photos[index] ?? "/figma/profile-company.png";
    const title = `Dokumentasi kegiatan LSP TIK Global ${index + 1}`;
    return {
      activity: { id: String(index), title, description: "", year: 0, date: null, location: "", media: [] },
      media: { id: String(index), type: "photo", src, thumbnail: src, alt: title, width: 634, height: 601, crop: photos[index] ? undefined : crop },
    };
  });
  return <div className={styles.profileVisual}>
    {items.map(({ media }) => <button key={media.id} type="button" className={styles.profilePhoto} aria-label={`Lihat ${media.alt} fullscreen`} onClick={event => setOpened({ id: media.id, opener: event.currentTarget })}>
      {media.crop ? <span className={styles.defaultPhoto} style={{ backgroundImage: `url(${media.src})`, backgroundSize: `${634 / media.crop.width * 100}% ${601 / media.crop.height * 100}%`, backgroundPosition: `${media.crop.left / (634 - media.crop.width) * 100}% ${media.crop.top / (601 - media.crop.height) * 100}%` }} role="img" aria-label={media.alt} /> : <Image src={media.src} alt={media.alt} fill unoptimized={!media.src.startsWith("/")} sizes="(max-width: 767px) 45vw, 25vw" className={styles.profileImage} />}
      <span className={styles.photoHint} aria-hidden="true">Lihat foto ↗</span>
    </button>)}
    {opened && <GalleryViewer items={items} initialMediaId={opened.id} opener={opened.opener} onClose={() => setOpened(null)} showMetadata={false} />}
  </div>;
}

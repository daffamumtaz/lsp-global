"use client";

import Image from "next/image";
import { useEffect, useRef, useState } from "react";
import { formatGalleryDate, moveMediaIndex, nextZoom, type ViewerItem } from "@/lib/gallery";
import styles from "./gallery.module.css";

export function GalleryViewer({ items, initialMediaId, opener, onClose }: {
  items: ViewerItem[]; initialMediaId: string; opener: HTMLElement; onClose: () => void;
}) {
  const [index, setIndex] = useState(() => Math.max(0, items.findIndex(item => item.media.id === initialMediaId)));
  const [zoom, setZoom] = useState(100);
  const dialog = useRef<HTMLDialogElement>(null);
  const video = useRef<HTMLVideoElement>(null);
  const thumbnails = useRef<HTMLDivElement>(null);
  const current = items[index];

  useEffect(() => {
    const element = dialog.current;
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    if (element && !element.open) element.showModal();
    return () => {
      document.body.style.overflow = overflow;
      if (opener.isConnected) opener.focus({ preventScroll: true });
    };
  }, [opener]);

  useEffect(() => {
    thumbnails.current?.querySelector('[aria-current="true"]')?.scrollIntoView({ block: "nearest", inline: "nearest" });
    const player = video.current;
    return () => { player?.pause(); };
  }, [index]);

  function select(next: number) {
    video.current?.pause();
    setIndex(next); setZoom(100);
  }
  function move(change: number) { select(moveMediaIndex(index, change, items.length)); }

  return <dialog ref={dialog} className={styles.viewer} aria-labelledby="gallery-viewer-title" aria-describedby="gallery-viewer-description" onClose={onClose} onKeyDown={event => {
    if (event.ctrlKey || event.metaKey || event.altKey || (event.target as HTMLElement).closest("video, input, select, textarea")) return;
    if (event.key === "ArrowLeft" || event.key === "ArrowRight") {
      event.preventDefault(); move(event.key === "ArrowLeft" ? -1 : 1);
    }
  }}>
    <div className={styles.viewerLayout}>
      <div className={styles.viewerToolbar}>
        <span className={styles.viewerCount} role="status" aria-live="polite">{index + 1} / {items.length} · Tahun {current.activity.year}</span>
        <div className={styles.viewerControls}>
          <button type="button" aria-label="Media sebelumnya" disabled={index === 0} onClick={() => move(-1)}>←</button>
          <button type="button" aria-label="Media berikutnya" disabled={index === items.length - 1} onClick={() => move(1)}>→</button>
          {current.media.type === "photo" && <>
            <button type="button" aria-label="Perkecil foto" disabled={zoom === 100} onClick={() => setZoom(value => nextZoom(value, -25))}>−</button>
            <output aria-live="polite">{zoom}%</output>
            <button type="button" aria-label="Perbesar foto" disabled={zoom === 300} onClick={() => setZoom(value => nextZoom(value, 25))}>+</button>
            <button type="button" onClick={() => setZoom(100)}>Reset</button>
          </>}
          <button type="button" className={styles.closeButton} autoFocus aria-label="Tutup viewer dan kembali ke galeri" onClick={() => dialog.current?.close()}>Tutup ×</button>
        </div>
      </div>
      <div key={current.media.id} className={styles.mediaViewport} tabIndex={0} role="region" aria-label={current.media.type === "photo" ? "Foto; gunakan kontrol zoom dan gulir untuk melihat detail" : "Pemutar video"}>
        {current.media.type === "photo" ? <div className={styles.photoStage} style={{ width: `${zoom}%`, height: `${zoom}%` }}>
          <Image src={current.media.src} alt={current.media.alt} fill sizes={`${zoom}vw`} className={styles.fullPhoto} />
        </div> : <video ref={video} className={styles.fullVideo} controls playsInline preload="metadata" poster={current.media.thumbnail} src={current.media.src}>Browser Anda tidak mendukung video.</video>}
      </div>
      <div className={styles.viewerCaption}>
        <h2 id="gallery-viewer-title">{current.activity.title}</h2>
        <p className={styles.viewerMeta}>{formatGalleryDate(current.activity.date)} · {current.activity.location || "Lokasi belum tersedia"}</p>
        <p id="gallery-viewer-description">{current.activity.description}</p>
        {current.media.type === "photo" && <p className={styles.zoomHint}>Gunakan + untuk memperbesar, lalu gulir foto ke samping atau ke atas/bawah. Reset mengembalikan ukuran awal.</p>}
      </div>
      <div className={styles.thumbnailSection}>
        <div className={styles.thumbnailHeading}><h3>{current.media.type === "photo" ? "Foto" : "Video"} dari tahun {current.activity.year}</h3><span>Gunakan ← / → untuk berpindah media</span></div>
        <div className={styles.thumbnailNavigation}>
          <button type="button" aria-label="Geser thumbnail ke kiri" onClick={() => thumbnails.current?.scrollBy({ left: -300, behavior: "auto" })}>←</button>
          <div ref={thumbnails} className={styles.thumbnails} role="group" aria-label="Pilih media dari tahun yang sama">
            {items.map((item, position) => <button key={item.media.id} type="button" aria-current={position === index ? "true" : undefined} aria-label={`${item.activity.title}, media ${position + 1}`} onClick={() => select(position)}>
              <Image src={item.media.thumbnail} alt="" width={104} height={64} sizes="104px" />
            </button>)}
          </div>
          <button type="button" aria-label="Geser thumbnail ke kanan" onClick={() => thumbnails.current?.scrollBy({ left: 300, behavior: "auto" })}>→</button>
        </div>
      </div>
    </div>
  </dialog>;
}

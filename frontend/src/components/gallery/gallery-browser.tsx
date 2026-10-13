"use client";

import Image from "next/image";
import { MapPinIcon, CalendarDaysIcon } from "@heroicons/react/24/outline";
import { useSearchParams } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { formatGalleryDate, galleryHref, readGalleryState, selectGallery, type GalleryActivity, type GalleryState, type ViewerItem } from "@/lib/gallery";
import { GalleryViewer } from "./gallery-viewer";
import styles from "./gallery.module.css";

export function GalleryBrowser({ activities, heading, yearHeading }: { activities: GalleryActivity[]; heading: { title: string; subtitle: string }; yearHeading: { title: string; subtitle: string } }) {
  const params = useSearchParams();
  const state = readGalleryState(params);
  const result = selectGallery(activities, state);
  const galleryView = useRef<HTMLElement>(null);
  const previousPage = useRef(state.page);
  useEffect(() => {
    if (previousPage.current === state.page) return;
    previousPage.current = state.page;
    galleryView.current?.scrollIntoView({ block: "start", behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "instant" : "smooth" });
  }, [state.page]);
  const [viewer, setViewer] = useState<{ items: ViewerItem[]; mediaId: string; opener: HTMLElement } | null>(null);
  const mediaLabel = state.type === "photo" ? "foto" : "video";

  useEffect(() => {
    const closeViewer = () => setViewer(null);
    window.addEventListener("popstate", closeViewer);
    return () => window.removeEventListener("popstate", closeViewer);
  }, []);

  function update(patch: Partial<GalleryState>, replace = false) {
    const current = readGalleryState(new URLSearchParams(window.location.search));
    const href = galleryHref({ ...current, ...patch });
    if (replace) window.history.replaceState(null, "", href);
    else window.history.pushState(null, "", href);
    setViewer(null);
  }

  return <section ref={galleryView} className={styles.gallerySection} aria-label="Album galeri">
    <div className={styles.inner}>
      <div className={styles.mediaTabs} role="group" aria-label="Jenis galeri">
        <button type="button" aria-pressed={state.type === "photo"} onClick={() => update({ type: "photo", year: null, query: "", sort: "newest", page: 1 })}>Jelajahi Foto Kami</button>
        <button type="button" aria-pressed={state.type === "video"} onClick={() => update({ type: "video", year: null, query: "", sort: "newest", page: 1 })}>Jelajahi Video Kami</button>
      </div>
      {state.year !== null ? <div className={styles.yearHeading}>
        <button type="button" className={styles.backButton} onClick={() => update({ year: null, query: "", sort: "newest", page: 1 })}>← Kembali ke daftar tahun</button>
        <h2>{yearHeading.title.replace("{year}", String(state.year))}</h2>
      </div> : <h2 className={styles.albumHeading}>{heading.title.replace("{media}", mediaLabel)}</h2>}
      {(state.year === null ? heading.subtitle : yearHeading.subtitle) && <p>{state.year === null ? heading.subtitle : yearHeading.subtitle}</p>}
      <div className={styles.toolbar}>
        {state.year !== null && <label>Urutkan kegiatan<select value={state.sort} onChange={event => update({ sort: event.target.value as GalleryState["sort"], page: 1 }, true)}>
          <option value="newest">Terbaru dahulu</option><option value="oldest">Terlama dahulu</option><option value="title-asc">Judul A–Z</option><option value="title-desc">Judul Z–A</option>
        </select></label>}
        <label className={styles.search}>{state.year === null ? "Cari tahun" : "Cari kegiatan"}<input type="search" name="gallery-search" value={state.query} placeholder={state.year === null ? "Contoh: 2025…" : "Judul atau lokasi kegiatan…"} onChange={event => update({ query: event.target.value, page: 1 }, true)} /></label>
        {state.query && <button type="button" className={styles.resetButton} onClick={() => update({ query: "", page: 1 }, true)}>Reset pencarian</button>}
      </div>
      <p className={styles.resultCount} role="status" aria-live="polite">{result.total} {state.year === null ? "album" : "kegiatan"}{state.query ? " ditemukan" : " tersedia"}</p>
      <div className={styles.grid}>
        {result.items.map(card => <button className={`${styles.card} ${card.activity ? styles.activityCard : styles.albumCard}`} type="button" key={card.id} aria-label={card.activity ? `Buka ${card.title}` : `Buka album ${mediaLabel} tahun ${card.year}`} onClick={event => {
          if (!card.activity) { update({ year: card.year, query: "", sort: "newest", page: 1 }); return; }
          const activity = card.activity;
          setViewer({ items: activity.media.filter(media => media.type === state.type && media.src).map(media => ({ activity, media })), mediaId: card.cover.id, opener: event.currentTarget });
        }}>
          <div className={styles.cardMedia}>
            {card.cover.type === "video"
              ? <video className={styles.cover} muted playsInline preload="metadata" src={`${card.cover.src}#t=0.1`} aria-hidden="true" />
              : <Image unoptimized={!card.cover.thumbnail.startsWith("/")} src={card.cover.thumbnail} alt="" fill sizes="(max-width: 639px) calc(100vw - 40px), (max-width: 1023px) 45vw, 300px" className={styles.cover} />}
            {!card.activity && <div className={styles.cardOverlay} aria-hidden="true" />}
          </div>
          {card.activity ? <div className={styles.cardCaption}>
            <h3>{card.title}</h3><p className={styles.metadata}><MapPinIcon aria-hidden="true" />{card.activity.location || "Lokasi belum tersedia"}</p><p className={styles.metadata}><CalendarDaysIcon aria-hidden="true" />{formatGalleryDate(card.activity.date)}</p><span>{card.count} {mediaLabel}</span>
          </div> : <div className={styles.albumCaption}><h3>{card.year}</h3><p>{card.count} {mediaLabel}</p></div>}
        </button>)}
      </div>
      {!result.total && <div className={styles.empty}>
        <h3>{state.type === "video" && !state.query ? "Video belum tersedia" : "Tidak ada hasil"}</h3>
        <p>{state.query ? "Coba tahun atau kata kunci lain, atau reset pencarian." : state.type === "video" ? "Dokumentasi video akan ditampilkan di sini setelah tersedia. Anda dapat menjelajahi galeri foto." : "Album foto untuk tahun ini belum tersedia. Kembali ke daftar tahun untuk melihat album lain."}</p>
        {state.type === "video" && <button type="button" onClick={() => update({ type: "photo", year: null, query: "", page: 1 })}>Lihat galeri foto</button>}
      </div>}
      {result.pageCount > 1 && <nav className={styles.pagination} aria-label="Halaman galeri">
        <button type="button" aria-label="Halaman sebelumnya" disabled={result.page === 1} onClick={() => update({ page: result.page - 1 })}>←</button>
        {Array.from({ length: result.pageCount }, (_, i) => i + 1).map(page => <button key={page} type="button" aria-current={page === result.page ? "page" : undefined} aria-label={`Halaman ${page}`} onClick={() => update({ page })}>{page}</button>)}
        <button type="button" aria-label="Halaman berikutnya" disabled={result.page === result.pageCount} onClick={() => update({ page: result.page + 1 })}>→</button>
      </nav>}
    </div>
    {viewer && <GalleryViewer items={viewer.items} initialMediaId={viewer.mediaId} opener={viewer.opener} onClose={() => setViewer(null)} />}
  </section>;
}

"use client";

import { useEffect, useId, useRef, useState } from "react";
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/react/24/outline";
import type { Partner } from "@/lib/partners";
import { PartnerGrid } from "./partner-grid";
import styles from "./partners.module.css";

export function PartnerCarousel({ partners }: { partners: Partner[] }) {
  const id = useId();
  const viewport = useRef<HTMLDivElement>(null);
  const root = useRef<HTMLDivElement>(null);
  const [paused, setPaused] = useState(false);
  const [edges, setEdges] = useState({ start: true, end: true });
  useEffect(() => {
    const element = viewport.current;
    if (!element) return;
    const update = () => setEdges({ start: element.scrollLeft <= 1, end: element.scrollLeft + element.clientWidth >= element.scrollWidth - 1 });
    const resize = new ResizeObserver(update);
    resize.observe(element);
    element.addEventListener("scroll", update, { passive: true });
    update();
    return () => { resize.disconnect(); element.removeEventListener("scroll", update); };
  }, [partners]);
  useEffect(() => {
    if (paused || partners.length < 2) return;
    const timer = setInterval(() => {
      const element = viewport.current;
      if (!element || document.hidden || matchMedia("(prefers-reduced-motion: reduce)").matches || root.current?.matches(":hover, :focus-within")) return;
      const end = element.scrollLeft + element.clientWidth >= element.scrollWidth - 1;
      element.scrollTo({ left: end ? 0 : element.scrollLeft + element.clientWidth, behavior: "smooth" });
    }, 3000);
    return () => clearInterval(timer);
  }, [paused, partners.length]);
  function move(direction: number) {
    const element = viewport.current!;
    element.scrollBy({ left: direction * element.clientWidth, behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "instant" : "smooth" });
  }
  if (!partners.length) return <p className={styles.empty}>Logo mitra belum tersedia.</p>;
  return <div ref={root} className={styles.carousel}>
    <button type="button" className={styles.arrow} aria-label="Mitra sebelumnya" aria-controls={id} disabled={edges.start} onClick={() => move(-1)}><ChevronLeftIcon aria-hidden="true" /></button>
    <div id={id} ref={viewport} className={styles.viewport} role="region" aria-label="Logo mitra; geser untuk melihat lainnya" tabIndex={0}>
      <PartnerGrid partners={partners} carousel />
    </div>
    <button type="button" className={styles.arrow} aria-label="Mitra berikutnya" aria-controls={id} disabled={edges.end} onClick={() => move(1)}><ChevronRightIcon aria-hidden="true" /></button>
    <button type="button" className="carousel-pause" aria-pressed={paused} onClick={() => setPaused(value => !value)}>{paused ? "Lanjutkan mitra otomatis" : "Jeda mitra otomatis"}</button>
  </div>;
}

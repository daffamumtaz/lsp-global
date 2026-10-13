"use client";

import { useEffect, useId, useRef, useState } from "react";
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/react/24/outline";
import type { LandingReview } from "@/lib/landing";
import { ReviewCard } from "./cards";

export function ReviewCarousel({ reviews }: { reviews: LandingReview[] }) {
  const id = useId();
  const viewport = useRef<HTMLDivElement>(null);
  const root = useRef<HTMLDivElement>(null);
  const [paused, setPaused] = useState(false);
  useEffect(() => {
    if (paused || reviews.length < 2) return;
    const timer = setInterval(() => {
      const element = viewport.current;
      if (!element || document.hidden || matchMedia("(prefers-reduced-motion: reduce)").matches || root.current?.matches(":hover, :focus-within")) return;
      const end = element.scrollLeft + element.clientWidth >= element.scrollWidth - 1;
      element.scrollTo({ left: end ? 0 : element.scrollLeft + element.clientWidth, behavior: "smooth" });
    }, 3000);
    return () => clearInterval(timer);
  }, [paused, reviews.length]);
  if (!reviews.length) return null;
  function move(direction: number) {
    const element = viewport.current!;
    const end = element.scrollLeft + element.clientWidth >= element.scrollWidth - 1;
    const left = direction > 0 && end ? 0 : direction < 0 && element.scrollLeft <= 1 ? element.scrollWidth - element.clientWidth : element.scrollLeft + direction * element.clientWidth;
    element.scrollTo({ left, behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "instant" : "smooth" });
  }
  return <div ref={root} className="review-carousel">
    <button type="button" className="review-arrow" aria-label="Review sebelumnya" aria-controls={id} onClick={() => move(-1)} disabled={reviews.length < 2}><ChevronLeftIcon aria-hidden="true" /></button>
    <div id={id} ref={viewport} className="review-viewport" role="region" aria-label="Review peserta; geser untuk melihat lainnya" tabIndex={0}>
      <div className="reviews-grid mx-auto" aria-live={paused ? "polite" : "off"}>
        {reviews.map((review, index) => <ReviewCard key={`${review.name}-${index}`} review={review} first={index === 0} />)}
      </div>
    </div>
    <button type="button" className="review-arrow" aria-label="Review berikutnya" aria-controls={id} onClick={() => move(1)} disabled={reviews.length < 2}><ChevronRightIcon aria-hidden="true" /></button>
    <button type="button" className="carousel-pause" aria-pressed={paused} onClick={() => setPaused(value => !value)}>{paused ? "Lanjutkan review otomatis" : "Jeda review otomatis"}</button>
  </div>;
}

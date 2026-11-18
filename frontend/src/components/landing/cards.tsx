import Link from "next/link";
import type { LandingFeature, LandingReview } from "@/lib/landing";
import { StarIcon } from "@heroicons/react/24/solid";
import { MediaImage } from "./ui";

export function BenefitCard({ title, caption, href }: LandingFeature) {
  return (
    <Link href={href} className="benefit-card rounded-xl border border-divider bg-white">
      <div>
        <h3 className="text-[length:var(--text-subheading)] leading-snug font-semibold text-brand-dark">{title}</h3>
        {caption && <p className="mt-3 text-base leading-relaxed text-muted">{caption}</p>}
      </div>
    </Link>
  );
}

export function ReviewCard({ review }: { review: LandingReview }) {
  const fruits = ["🍎", "🍊", "🍋", "🍐", "🍇", "🍓", "🍑", "🍒", "🍉", "🥝"];
  const fruit = fruits[Array.from(review.name).reduce((sum, character) => sum + character.codePointAt(0)!, 0) % fruits.length];
  return (
    <figure className="review-card flex flex-col rounded-xl border-2 border-divider bg-white">
      <div className="flex gap-1 text-[#b77900]" role="img" aria-label="5 dari 5 bintang">
        {Array.from({ length: 5 }, (_, index) => <StarIcon key={index} aria-hidden="true" className="size-6" />)}
      </div>
      <blockquote className="mt-4 mb-6 text-base leading-relaxed">{review.quote}</blockquote>
      <figcaption className="mt-auto flex items-center gap-4 border-t border-divider pt-4">
        {review.photo_url ? <MediaImage src={review.photo_url} fallback="" alt={`Foto ${review.name}`} width={48} height={48} className="shrink-0 rounded-full object-cover" /> : <span className="review-avatar flex size-12 shrink-0 items-center justify-center rounded-full bg-[#edf5fb] text-2xl" role="img" aria-label="Avatar buah">{fruit}</span>}
        <div className="min-w-0"><p className="text-base leading-normal font-semibold">{review.name}</p><p className="mt-2 text-sm leading-normal italic">{review.occupation}</p></div>
      </figcaption>
    </figure>
  );
}

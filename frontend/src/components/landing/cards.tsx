import type { LandingFeature, LandingReview } from "@/lib/landing";
import { TrophyIcon, ShieldCheckIcon, GlobeAltIcon } from "@heroicons/react/24/outline";
import { StarIcon } from "@heroicons/react/24/solid";
import { MediaImage } from "./ui";

export function BenefitCard({ title, caption, icon }: LandingFeature) {
  const Icon = { award: TrophyIcon, shield: ShieldCheckIcon, network: GlobeAltIcon }[icon] ?? TrophyIcon;
  return (
    <article className="benefit-card rounded-xl border-2 border-divider bg-white">
      <Icon aria-hidden="true" className="size-14 text-brand-dark" />
      <h3 className="mt-4 text-[length:var(--text-subheading)] leading-snug font-semibold text-brand-dark">{title}</h3>
      <p className="mt-3 text-base leading-relaxed text-muted">{caption}</p>
    </article>
  );
}

export function ReviewCard({ review, first = false }: { review: LandingReview; first?: boolean }) {
  return (
    <figure className="review-card flex flex-col rounded-xl border-2 border-divider bg-white">
      <div className="flex gap-1 text-[#b77900]" role="img" aria-label="5 dari 5 bintang">
        {Array.from({ length: 5 }, (_, index) => <StarIcon key={index} aria-hidden="true" className="size-6" />)}
      </div>
      <blockquote className="mt-4 mb-6 text-base leading-relaxed">{review.quote}</blockquote>
      <figcaption className="mt-auto flex items-center gap-4 border-t border-divider pt-4">
        <MediaImage src={review.photo_url} fallback={first ? "imgEllipse45.png" : "imgEllipse46.svg"} alt={`Foto ${review.name}`} width={40} height={40} className="shrink-0 rounded-full object-cover" />
        <div className="min-w-0"><p className="text-base leading-normal font-semibold">{review.name}</p><p className="mt-2 text-sm leading-normal italic">{review.occupation}</p></div>
      </figcaption>
    </figure>
  );
}

import type { LandingFeature, LandingReview } from "@/lib/landing";
import { TrophyIcon, ShieldCheckIcon, GlobeAltIcon } from "@heroicons/react/24/outline";
import { StarIcon } from "@heroicons/react/24/solid";
import { Divider, MediaImage } from "./ui";

export function BenefitCard({ title, caption, icon }: LandingFeature) {
  const Icon = { award: TrophyIcon, shield: ShieldCheckIcon, network: GlobeAltIcon }[icon] ?? TrophyIcon;
  return (
    <article className="benefit-card rounded-xl border-2 border-divider bg-white px-[29px] pt-[19px] pb-7">
      <Icon aria-hidden="true" className="size-14 text-brand-dark" />
      <h3 className="mt-[15px] max-w-[345px] text-2xl leading-7 font-semibold">{title}</h3>
      <p className="mt-2.5 max-w-[349px] text-base leading-5">{caption}</p>
    </article>
  );
}

export function ReviewCard({ review, first = false }: { review: LandingReview; first?: boolean }) {
  return (
    <figure className="review-card rounded-xl border-2 border-divider bg-white px-[25px] pt-[27px] pb-[19px]">
      <div className="flex gap-1 text-[#b77900]" role="img" aria-label="5 dari 5 bintang">
        {Array.from({ length: 5 }, (_, index) => <StarIcon key={index} aria-hidden="true" className="size-6" />)}
      </div>
      <blockquote className="mt-[15px] ml-1 max-w-[329px] text-base leading-5">{review.quote}</blockquote>
      <div className="mt-[25px]"><Divider file="imgLine11.svg" /></div>
      <figcaption className="mt-2.5 flex items-end gap-[14px]">
        <MediaImage src={review.photo_url} fallback={first ? "imgEllipse45.png" : "imgEllipse46.svg"} alt={`Foto ${review.name}`} width={40} height={40} className="shrink-0 rounded-full object-cover" />
        <div className="pb-0.5"><p className="text-base leading-4 font-semibold">{review.name}</p><p className="mt-1.5 text-xs leading-3 italic">{review.occupation}</p></div>
      </figcaption>
    </figure>
  );
}

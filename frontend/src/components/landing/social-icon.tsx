import { faFacebookF } from "@fortawesome/free-brands-svg-icons/faFacebookF";
import { faInstagram } from "@fortawesome/free-brands-svg-icons/faInstagram";
import { faYoutube } from "@fortawesome/free-brands-svg-icons/faYoutube";
import { faWhatsapp } from "@fortawesome/free-brands-svg-icons/faWhatsapp";

const icons = { Facebook: faFacebookF, Instagram: faInstagram, YouTube: faYoutube, WhatsApp: faWhatsapp };
export type SocialName = keyof typeof icons;

export function SocialIcon({ name, className = "" }: { name: SocialName; className?: string }) {
  const [width, height, , , path] = icons[name].icon;
  return <svg className={`social-icon social-icon-${name.toLowerCase()} ${className}`} width="20" height="20" viewBox={`0 0 ${width} ${height}`} fill="currentColor" aria-hidden="true" focusable="false">
    {Array.isArray(path) ? path.map((d, index) => <path key={index} d={d} />) : <path d={path} />}
  </svg>;
}

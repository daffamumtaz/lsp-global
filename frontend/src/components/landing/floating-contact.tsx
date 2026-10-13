import { SocialIcon } from "./social-icon";

export function FloatingContact({ whatsappUrl }: { whatsappUrl: string }) {
  return <div className="contact-bubble">
    <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" className="contact-bubble-trigger" aria-label="Hubungi kami melalui WhatsApp (tab baru)">
      <SocialIcon name="WhatsApp" /><span className="contact-bubble-label">WhatsApp</span>
    </a>
  </div>;
}

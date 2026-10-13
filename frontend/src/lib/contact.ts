import type { SiteIdentity } from "./landing";

export const contactInfo = {
  phone: "021-27651353", phoneHref: "tel:02127651353",
  whatsapp: "+62 851-9553-3360", whatsappNumber: "6285195533360",
  email: "info@lspglobal.id",
  address: "Jl. Lebak Bulus Raya Komplek Ruko Victory 88 No. 88 B, Cilandak, Jakarta Selatan",
};
export const contactSubjects = ["Pendaftaran Sertifikasi", "Kerja Sama", "Informasi", "Masukan dan Saran"];
export const contactPreferences = ["Email", "Telepon", "WhatsApp", "Email dan WhatsApp"];
export type ContactInput = { subject: string; name: string; institution: string; phone: string; email: string; preference: string; message: string };
export type ContactErrors = Partial<Record<keyof ContactInput, string>>;
export function countWords(text: string) { return text.trim() ? text.trim().split(/\s+/u).length : 0; }
export function validateContact(input: ContactInput): ContactErrors {
  const errors: ContactErrors = {};
  if (!contactSubjects.includes(input.subject)) errors.subject = "Pilih subjek pesan yang tersedia.";
  if (!input.name.trim() || input.name.trim().length > 100) errors.name = "Isi nama lengkap, maksimal 100 karakter.";
  if (!input.institution.trim() || input.institution.trim().length > 150) errors.institution = "Isi instansi atau tulis Mandiri, maksimal 150 karakter.";
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.email.trim()) || input.email.trim().length > 254) errors.email = "Isi alamat email yang valid, misalnya nama@example.com.";
  if (!contactPreferences.includes(input.preference)) errors.preference = "Pilih preferensi balasan yang tersedia.";
  const digits = input.phone.replace(/\D/g, "");
  if ((input.phone.trim() || input.preference !== "Email") && (!/^[+\d\s()-]+$/.test(input.phone) || digits.length < 8 || digits.length > 15)) errors.phone = "Isi nomor telepon yang valid (8–15 digit), misalnya +62 812 3456 7890.";
  if (!input.message.trim() || input.message.length > 5000 || countWords(input.message) > 1000) errors.message = "Isi pesan, maksimal 1.000 kata dan 5.000 karakter.";
  return errors;
}
export function contactDraft(input: ContactInput, destination: Pick<typeof contactInfo, "email" | "whatsappNumber"> & { whatsappContactTemplate?: string; brandName?: string } = contactInfo) {
  const body = [
    `Subjek: ${input.subject}`, `Nama: ${input.name.trim()}`, `Instansi: ${input.institution.trim()}`,
    `Email: ${input.email.trim()}`, `Telepon: ${input.phone.trim() || "Tidak dicantumkan"}`,
    `Preferensi balasan: ${input.preference}`, "", input.message.trim(),
  ].join("\n");
  const whatsappBody = destination.whatsappContactTemplate ? renderWhatsAppTemplate(destination.whatsappContactTemplate, {
    ...input, name: input.name.trim(), institution: input.institution.trim(), email: input.email.trim(),
    phone: input.phone.trim() || "Tidak dicantumkan", message: input.message.trim(), brand_name: destination.brandName ?? "LSP TIK Global",
  }) : body;
  return {
    body, whatsappBody,
    email: `mailto:${destination.email}?subject=${encodeURIComponent(`${input.subject} — ${input.name.trim()}`)}&body=${encodeURIComponent(body)}`,
    whatsapp: `https://wa.me/${destination.whatsappNumber}?text=${encodeURIComponent(whatsappBody)}`,
  };
}
export function renderWhatsAppTemplate(template: string, values: Record<string, string>): string {
  return template.replace(/\{([a-z_]+)\}/g, (match, key: string) => values[key] ?? match);
}
export function getContactInfo(identity: SiteIdentity) {
  const digits = identity.whatsapp.replace(/\D/g, "");
  const whatsappNumber = digits.startsWith("0") ? `62${digits.slice(1)}` : digits;
  const destination = encodeURIComponent(`${identity.brand_name}, ${identity.address}`);
  return {
    phone: identity.phone, phoneHref: `tel:${identity.phone.replace(/[^+\d]/g, "")}`,
    email: identity.email, whatsapp: identity.whatsapp, whatsappNumber, address: identity.address,
    brandName: identity.brand_name,
    whatsappContactTemplate: identity.whatsapp_contact_message,
    whatsappUrl: `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(renderWhatsAppTemplate(identity.whatsapp_message, { brand_name: identity.brand_name }))}`,
    mapsUrl: identity.maps_url || `https://www.google.com/maps/search/?api=1&query=${destination}`,
    directionsUrl: `https://www.google.com/maps/dir/?api=1&destination=${destination}`,
    mapEmbedUrl: `https://www.google.com/maps?q=${destination}&output=embed`,
  };
}

export function getRegistrationUrl(identity: SiteIdentity, schemeName: string): string {
  const { whatsappNumber } = getContactInfo(identity);
  return `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(renderWhatsAppTemplate(identity.whatsapp_registration_message ?? "Halo {brand_name}, saya ingin mendaftar sertifikasi skema {scheme_name}.", { brand_name: identity.brand_name, scheme_name: schemeName }))}`;
}

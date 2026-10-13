import test from "node:test";
import assert from "node:assert/strict";
import { validateContact, contactDraft, countWords, getContactInfo, getRegistrationUrl } from "./contact.ts";

const valid = { subject: "Informasi", name: "Andi", institution: "Mandiri", phone: "", email: "andi@example.test", preference: "Email", message: "Saya ingin mengetahui jadwal sertifikasi." };
test("contact validation trims required fields, checks email and conditional phone", () => {
  assert.deepEqual(validateContact(valid), {});
  const errors = validateContact({ ...valid, name: "  ", email: "invalid", preference: "WhatsApp" });
  assert.ok(errors.name); assert.ok(errors.email); assert.ok(errors.phone);
  assert.deepEqual(validateContact({ ...valid, phone: "+62 812-3456-7890", preference: "Telepon" }), {});
  assert.ok(validateContact({ ...valid, subject: "unknown" }).subject);
  assert.ok(validateContact({ ...valid, preference: "unknown" }).preference);
});
test("message limits and safe encoded drafts preserve content without sending", () => {
  assert.equal(countWords("  satu\n dua  "), 2);
  assert.ok(validateContact({ ...valid, message: "word ".repeat(1001) }).message);
  assert.ok(validateContact({ ...valid, message: "x".repeat(5001) }).message);
  const draft = contactDraft({ ...valid, message: "Riset & TIK?\nBaris kedua." });
  assert.ok(draft.email.startsWith("mailto:info@lspglobal.id?"));
  assert.match(new URLSearchParams(draft.email.split("?")[1]).get("body"), /Riset & TIK\?\nBaris kedua\./);
  assert.match(new URL(draft.whatsapp).searchParams.get("text"), /Preferensi balasan: Email/);
  assert.equal(new URL(draft.whatsapp).hostname, "wa.me");
});

test("configured identity supplies normalized contacts, map links and draft destinations", () => {
  const identity = { brand_name: "LSP Uji", phone: "021-12345678", email: "kontak@example.com", whatsapp: "0812-3456-7890", address: "Jalan Uji & Jakarta", maps_url: null, whatsapp_message: "Halo, ingin bertanya?" };
  const contact = getContactInfo(identity);
  assert.equal(contact.phoneHref, "tel:02112345678");
  assert.equal(contact.whatsappNumber, "6281234567890");
  assert.equal(new URL(contact.whatsappUrl).searchParams.get("text"), identity.whatsapp_message);
  assert.equal(new URL(contact.mapsUrl).searchParams.get("query"), "LSP Uji, Jalan Uji & Jakarta");
  assert.equal(new URL(contact.mapEmbedUrl).searchParams.get("q"), "LSP Uji, Jalan Uji & Jakarta");
  const draft = contactDraft(valid, contact);
  assert.ok(draft.email.startsWith("mailto:kontak@example.com?"));
  assert.equal(new URL(draft.whatsapp).pathname, "/6281234567890");
  assert.equal(getContactInfo({ ...identity, whatsapp: "+62 812-3456-7890", maps_url: "https://maps.google.com/custom" }).whatsappNumber, contact.whatsappNumber);
  assert.equal(getContactInfo({ ...identity, maps_url: "https://maps.google.com/custom" }).mapsUrl, "https://maps.google.com/custom");
});

test("editable templates preserve defaults and safely interpolate values once", () => {
  const identity = { brand_name: "LSP Uji", phone: "021-12345678", email: "kontak@example.com", whatsapp: "081234567890", address: "Jakarta", maps_url: null, whatsapp_message: "Halo {brand_name}", whatsapp_registration_message: "Daftar {scheme_name} — {brand_name}", whatsapp_contact_message: "Dari {name}: {message}" };
  const contact = getContactInfo(identity);
  assert.equal(new URL(contact.whatsappUrl).searchParams.get("text"), "Halo LSP Uji");
  assert.equal(new URL(getRegistrationUrl(identity, "TIK & Data?")).searchParams.get("text"), "Daftar TIK & Data? — LSP Uji");
  const draft = contactDraft({ ...valid, message: "Isi {name} & pertanyaan?" }, contact);
  assert.equal(new URL(draft.whatsapp).searchParams.get("text"), "Dari Andi: Isi {name} & pertanyaan?");
  assert.match(new URLSearchParams(draft.email.split("?")[1]).get("body"), /Subjek: Informasi/);
  assert.equal(draft.whatsappBody, "Dari Andi: Isi {name} & pertanyaan?");
});

test("contact props passed into client forms contain only serializable data", () => {
  const identity = { brand_name: "LSP", phone: "02112345678", email: "a@example.com", whatsapp: "081234567890", address: "Jakarta", maps_url: null, whatsapp_message: "Halo" };
  const contact = getContactInfo(identity);
  assert.ok(Object.values(contact).every(value => typeof value !== "function"));
  assert.equal(structuredClone(contact).whatsappUrl, contact.whatsappUrl);
});

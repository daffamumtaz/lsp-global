import test from "node:test";
import assert from "node:assert/strict";
import { validateContact, contactDraft, countWords } from "./contact.ts";

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

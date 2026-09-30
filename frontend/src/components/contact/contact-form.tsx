"use client";

import { useEffect, useRef, useState } from "react";
import { contactDraft, contactPreferences, contactSubjects, countWords, validateContact, type ContactErrors, type ContactInput } from "@/lib/contact";
import styles from "./contact.module.css";

export function ContactForm() {
  const [errors, setErrors] = useState<ContactErrors>({});
  const [message, setMessage] = useState("");
  const [preference, setPreference] = useState("Email");
  const [draft, setDraft] = useState<ReturnType<typeof contactDraft> | null>(null);
  const [dirty, setDirty] = useState(false);
  const prepared = useRef<HTMLDivElement>(null);
  useEffect(() => {
    if (!dirty) return;
    const warn = (event: BeforeUnloadEvent) => { event.preventDefault(); event.returnValue = ""; };
    window.addEventListener("beforeunload", warn);
    return () => window.removeEventListener("beforeunload", warn);
  }, [dirty]);
  useEffect(() => { if (draft) prepared.current?.focus(); }, [draft]);
  const fields = [
    { name: "name", label: "Nama lengkap", type: "text", autoComplete: "name", maxLength: 100, placeholder: "Contoh: Andi Budi…" },
    { name: "institution", label: "Asal instansi", type: "text", autoComplete: "organization", maxLength: 150, placeholder: "Contoh: Nama perusahaan atau Mandiri…" },
    { name: "phone", label: "Nomor telepon", type: "tel", autoComplete: "tel", maxLength: 24, placeholder: "Contoh: +62 812 3456 7890…" },
    { name: "email", label: "Email", type: "email", autoComplete: "email", maxLength: 254, placeholder: "Contoh: nama@example.com…" },
  ] as const;

  return <form className={styles.form} noValidate onChange={() => { setDirty(true); setDraft(null); }} onSubmit={event => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const input = Object.fromEntries((["subject", "name", "institution", "phone", "email", "preference", "message"] as const).map(key => [key, String(data.get(key) ?? "")])) as ContactInput;
    const nextErrors = validateContact(input);
    setErrors(nextErrors);
    const first = Object.keys(nextErrors)[0];
    if (first) { event.currentTarget.querySelector<HTMLElement>(`[name="${first}"]`)?.focus(); return; }
    setDraft(contactDraft(input));
  }}>
    <h2>Siapkan pesan Anda</h2>
    <p className={styles.formIntro}>Isi form, lalu buka draf di email atau WhatsApp untuk mengirimkannya. Pesan tidak dikirim atau disimpan melalui website ini.</p>
    <p className={styles.help}>Field bertanda * wajib diisi.</p>
    <fieldset aria-describedby={errors.subject ? "subject-error" : undefined}><legend>Subjek pesan *</legend><div className={styles.radioGroup}>{contactSubjects.map((subject, index) => <label key={subject}><input type="radio" name="subject" value={subject} defaultChecked={index === 0} required />{subject}</label>)}</div>{errors.subject && <p id="subject-error" className={styles.error}>{errors.subject}</p>}</fieldset>
    {fields.map(field => <div className={styles.field} key={field.name}><label htmlFor={`contact-${field.name}`}>{field.label} {field.name !== "phone" || preference !== "Email" ? "*" : "(opsional)"}</label><input {...field} id={`contact-${field.name}`} required={field.name !== "phone" || preference !== "Email"} spellCheck={field.type === "email" ? false : undefined} aria-invalid={Boolean(errors[field.name])} aria-describedby={[field.name === "phone" ? "phone-help" : "", errors[field.name] ? `${field.name}-error` : ""].filter(Boolean).join(" ") || undefined} />{field.name === "phone" && <p id="phone-help" className={styles.help}>Wajib untuk preferensi balasan Telepon atau WhatsApp.</p>}{errors[field.name] && <p id={`${field.name}-error`} className={styles.error}>{errors[field.name]}</p>}</div>)}
    <fieldset aria-describedby={errors.preference ? "preference-error" : undefined}><legend>Preferensi balasan *</legend><div className={styles.radioGroup}>{contactPreferences.map(value => <label key={value}><input type="radio" name="preference" value={value} checked={preference === value} onChange={() => setPreference(value)} />{value}</label>)}</div>{errors.preference && <p id="preference-error" className={styles.error}>{errors.preference}</p>}</fieldset>
    <div className={styles.field}><label htmlFor="contact-message">Isi pesan *</label><textarea id="contact-message" name="message" autoComplete="off" rows={6} maxLength={5000} required placeholder="Contoh: Saya ingin mengetahui jadwal sertifikasi…" value={message} onChange={event => setMessage(event.target.value)} aria-invalid={Boolean(errors.message)} aria-describedby={`message-help${errors.message ? " message-error" : ""}`} /><p id="message-help" className={styles.help}>{countWords(message)} / 1.000 kata · {message.length} / 5.000 karakter</p>{errors.message && <p id="message-error" className={styles.error}>{errors.message}</p>}</div>
    {Object.keys(errors).length > 0 && <p role="alert" className={styles.error}>Periksa field yang ditandai, lalu siapkan pesan kembali.</p>}
    <button type="submit" className={styles.primary}>Siapkan draf pesan →</button>
    {draft && <div ref={prepared} className={styles.prepared} tabIndex={-1} aria-labelledby="prepared-title"><h3 id="prepared-title">Draf pesan siap</h3><p role="status">Pesan belum dikirim. Pilih email atau WhatsApp, periksa kembali isinya, lalu kirim melalui aplikasi tersebut.</p><details><summary>Periksa isi draf</summary><pre>{draft.body}</pre></details><div className={styles.actions}><a className={styles.primary} href={draft.email} onClick={() => setDirty(false)}>Buka draf email</a><a className={styles.secondary} href={draft.whatsapp} target="_blank" rel="noopener noreferrer" onClick={() => setDirty(false)}>Buka WhatsApp ↗<span className="sr-only"> (tab baru)</span></a></div><p className={styles.help}>Jika aplikasi email tidak terbuka atau isi draf terpotong, salin teks dari “Periksa isi draf” ke aplikasi email Anda.</p></div>}
  </form>;
}

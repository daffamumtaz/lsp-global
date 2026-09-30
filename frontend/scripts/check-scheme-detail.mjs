import assert from "node:assert/strict";
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || "playwright");
const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROMIUM_EXECUTABLE });
const page = await browser.newPage();
const base = process.env.SCHEME_BASE_URL || "http://localhost:3000";
try {
  await page.goto(`${base}/skema-sertifikasi/junior-coder?metode=luring&halaman=2`);
  const tabs = page.getByRole("tab");
  assert.equal(await tabs.count(), 2, "detail must have two tabs");
  const units = page.getByRole("tab", { name: "Unit Kompetensi", exact: true });
  const requirements = page.getByRole("tab", { name: "Persyaratan", exact: true });
  assert.equal(await units.getAttribute("aria-selected"), "true");
  assert.deepEqual(await page.getByRole("columnheader").allTextContents(), ["No.", "Kode Unit", "Judul Unit"]);
  assert.deepEqual(await page.locator("tbody tr td:first-child").allTextContents(), ["1", "2", "3", "4", "5", "6", "7", "8"]);
  assert.equal(await page.locator('[name="unit-search"], main details').count(), 0);
  assert.equal(await page.getByRole("heading", { name: "Ringkasan skema" }).count(), 0);
  assert.equal(await page.getByRole("link", { name: /Kembali ke daftar skema/ }).getAttribute("href"), "/skema-sertifikasi?metode=luring&halaman=2");
  const destination = new URL(await page.getByRole("link", { name: /Daftar Sekarang/ }).getAttribute("href"));
  assert.equal(destination.hostname, "wa.me");
  assert.equal(destination.pathname, "/6285195533360");
  assert.match(destination.searchParams.get("text"), /mendaftar.*Junior Coder/);
  await units.focus();
  for (const [key, target] of [["ArrowRight", requirements], ["ArrowRight", units], ["ArrowLeft", requirements], ["Home", units], ["End", requirements]]) {
    await page.keyboard.press(key);
    assert.equal(await target.getAttribute("aria-selected"), "true", key);
    assert.equal(await target.evaluate(element => element === document.activeElement), true, `${key} focus`);
  }
  assert.equal(await page.getByRole("heading", { name: "Persyaratan Dasar", exact: true }).isVisible(), true);
  assert.equal(await page.getByRole("heading", { name: "Persyaratan Administrasi", exact: true }).isVisible(), true);
  assert.equal(await page.getByRole("table").isVisible(), false);
  await units.click();
  for (const width of [375, 768, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, `overflow at ${width}`);
    const price = await page.locator('[aria-label="Biaya sertifikasi"]').boundingBox();
    const cta = await page.getByRole("link", { name: /Daftar Sekarang/ }).boundingBox();
    assert.ok(width === 375 ? price.y + price.height <= cta.y : price.x + price.width <= cta.x, `price/CTA at ${width}`);
  }
  await page.setViewportSize({ width: 720, height: 450 });
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, "200% zoom equivalent");
  await page.goto(`${base}/skema-sertifikasi/software-quality`);
  await page.setViewportSize({ width: 375, height: 1000 });
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, "long title overflow");
  await page.goto(`${base}/skema-sertifikasi/cybersecurity`);
  assert.equal(await page.getByLabel("Biaya sertifikasi").textContent(), "Hubungi kami untuk biaya");
  console.log("Scheme detail: tabs, keyboard, table, CTA, filters, missing price, long title and responsive checks passed.");
} finally {
  await browser.close();
}

import assert from "node:assert/strict";
import { chromium, expect } from "@playwright/test";

const url = process.env.TYPOGRAPHY_TEST_URL ?? "http://127.0.0.1:13109/lspglobal/public";
const browser = await chromium.launch({ headless: true });
try {
  const page = await browser.newPage({ reducedMotion: "reduce" });
  const errors = [];
  page.on("pageerror", error => errors.push(error.message));
  for (const width of [375, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto(url, { waitUntil: "networkidle" });
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `Overflow at ${width}`);
    assert(await page.locator(".hero-title").evaluate(element => parseFloat(getComputedStyle(element).fontSize) >= 36));
    await expect(page.locator(".benefit-check")).toHaveCount(0);
    console.log(`Typography ${width}px: PASS`);
  }
  await page.setViewportSize({ width: 768, height: 1000 });
  const review = page.locator(".review-viewport");
  const partners = page.getByRole("region", { name: "Logo mitra; geser untuk melihat lainnya" });
  await page.mouse.move(0, 0);
  await page.waitForTimeout(3500);
  assert.equal(await review.evaluate(element => element.scrollLeft), 0);
  assert.equal(await partners.evaluate(element => element.scrollLeft), 0);
  const card = page.locator(".benefit-card").first();
  await card.hover();
  assert.equal(await card.evaluate(element => getComputedStyle(element).transform), "none");
  console.log("Device reduced motion: cards static and carousels paused, as expected");
  await expect(page.locator(".motion-control")).toHaveCount(0);
  await expect(page.getByRole("button", { name: "Aktifkan animasi", exact: true })).toHaveCount(0);
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: "instant" }));
  await page.screenshot({ path: "/tmp/lsp-typography-desktop.png", fullPage: true });
  await page.setViewportSize({ width: 375, height: 900 });
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: "instant" }));
  await page.screenshot({ path: "/tmp/lsp-typography-mobile.png", fullPage: true });
  for (const route of ["/tentang-kami", "/kontak", "/skema-sertifikasi"]) {
    await page.goto(url + route, { waitUntil: "networkidle" });
    await expect(page.locator("h1")).toBeVisible();
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `Overflow on ${route}`);
  }
  await page.locator('a[aria-label^="Lihat detail"]').first().click();
  await page.getByRole("tab").nth(1).click();
  await expect(page.locator("table tbody td").nth(1)).toBeVisible();
  assert(await page.locator("table tbody td").nth(1).evaluate(element => getComputedStyle(element).fontFamily.includes("monospace")));
  assert.deepEqual(errors, []);
  console.log("Device motion preferences respected; footer toggle removed; no JS errors");
} finally { await browser.close(); }

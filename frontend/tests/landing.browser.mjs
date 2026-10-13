import assert from "node:assert/strict";
import { chromium, expect } from "@playwright/test";
import { spawn } from "node:child_process";

const url = process.env.LANDING_TEST_URL ?? "http://127.0.0.1:3102";
const api = process.env.LANDING_TEST_URL ? null : spawn("php", ["artisan", "serve", "--host=127.0.0.1", "--port=18082", "--no-interaction"], { cwd: new URL("../../backend/", import.meta.url), stdio: "ignore" });
const server = process.env.LANDING_TEST_URL ? null : spawn(process.execPath, ["node_modules/next/dist/bin/next", "start", "--port", "3102"], {
  cwd: new URL("..", import.meta.url), stdio: "ignore",
  env: { ...process.env, LANDING_API_URL: "http://127.0.0.1:18082/api/landing-page", CONTENT_API_URL: "http://127.0.0.1:18082/api/content-directory", PROFILE_API_URL: "http://127.0.0.1:18082/api/profile-page", ORGANIZATION_API_URL: "http://127.0.0.1:18082/api/organization", INFORMATION_API_URL: "http://127.0.0.1:18082/api/information-page", PROCESSES_API_URL: "http://127.0.0.1:18082/api/processes" },
});
let browser;
try {
  for (let attempt = 0; attempt < 60; attempt++) {
    try { if ((await fetch(url)).ok) break; } catch {}
    if (attempt === 59) throw new Error("Landing server failed to start; run npm run build first.");
    await new Promise(resolve => setTimeout(resolve, 500));
  }
  browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  const errors = [];
  page.on("pageerror", error => errors.push(error.message));
  for (const width of [375, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto(url);
    await expect(page.locator(".hero video")).toHaveCount(0);
    await expect(page.locator("#hero-title")).toHaveText("Bersama LSP TIK Global");
    assert(await page.locator("#hero-title").evaluate(element => {
      const style = getComputedStyle(element);
      return element.getBoundingClientRect().height <= parseFloat(style.lineHeight) + 1;
    }), `Hero title must stay on one line at ${width}px`);
    await expect(page.locator(".hero-support p")).toHaveCount(2);
    await expect(page.locator(".hero-support p").first()).toHaveText("Bangun Perjalanan Karier Anda dengan Kompetensi yang Diakui");
    await expect(page.locator(".hero-support p").last()).toHaveText("Validasi kompetensi Anda dan tingkatkan kredibilitas di industri teknologi dan digital.");
    await expect(page.locator(".hero-strengths li")).toHaveCount(5);
    await expect(page.locator(".hero-strengths h2")).toHaveCount(0);
    assert.equal(await page.locator(".hero-support").evaluate(element => parseFloat(getComputedStyle(element).rowGap)), 0);
    await expect(page.locator(".home-about-copy p")).toHaveCount(4);
    await expect(page.locator("#tentang-kami #video-profil")).toBeVisible();
    await expect(page.getByRole("heading", { name: "Skema Sertifikasi Unggulan", exact: true })).toBeVisible();
    await expect(page.locator(".home-card-grid > a")).toHaveCount(5);
    const whatsapp = page.locator(".contact-bubble > a");
    const contact = page.getByRole("button", { name: "Kontak kami", exact: true });
    await expect(whatsapp).toHaveAttribute("href", /^https:\/\/wa\.me\//);
    await expect(contact).toHaveCount(0);
    const search = page.locator('.hero').getByRole("search", { name: "Pencarian skema di Beranda" });
    await expect(search.getByRole("button")).toHaveText("");
    assert.equal(await search.getByRole("button").evaluate(element => getComputedStyle(element).backgroundColor), "rgb(0, 65, 149)");
    assert.equal(await search.locator("svg").evaluate(element => getComputedStyle(element).color), "rgb(255, 255, 255)");
    const aboutLink = page.locator(".home-about-copy a");
    await expect(aboutLink).toHaveText("Lihat Selengkapnya");
    assert.equal(await aboutLink.evaluate(element => getComputedStyle(element).fontStyle), "normal");
    assert.equal(await aboutLink.evaluate(element => getComputedStyle(element).backgroundColor), "rgb(0, 65, 149)");
    assert.equal(await aboutLink.evaluate(element => getComputedStyle(element).color), "rgb(255, 255, 255)");
    assert.equal(await page.locator("#about-title").evaluate(element => getComputedStyle(element).fontSize), await page.locator(".reviews-section h2").evaluate(element => getComputedStyle(element).fontSize));
    assert.equal(await page.locator("#about-title").evaluate(element => getComputedStyle(element).textAlign), "center");
    assert(await page.locator("#about-title").evaluate(element => element.getBoundingClientRect().bottom < document.querySelector("#video-profil").getBoundingClientRect().top));
    await expect(page.locator(".reviews-grid .review-card")).toHaveCount(8);
    await expect(page.locator(".reviews-grid")).not.toContainText("Placeholder");
    for (const button of await page.locator(".carousel-pause").all()) assert.equal(await button.evaluate(element => getComputedStyle(element).textDecorationLine), "none");
    await page.getByRole("button", { name: "Buka pencarian skema" }).click();
    const modalSearch = page.locator("#header-search-dialog .search-field");
    for (const property of ["borderRadius", "paddingLeft", "minHeight"]) assert.equal(await modalSearch.evaluate((element, property) => getComputedStyle(element)[property], property), await search.evaluate((element, property) => getComputedStyle(element)[property], property));
    assert(await modalSearch.evaluate(element => element.querySelector("button").getBoundingClientRect().left >= element.querySelector("input").getBoundingClientRect().right));
    await page.keyboard.press("Escape");
    assert(await search.evaluate(element => element.querySelector("button").getBoundingClientRect().left >= element.querySelector("input").getBoundingClientRect().right), "Search icon sits right of the input");
    await expect(page.locator('.footer-socials a[aria-label="Instagram (tab baru)"]')).toHaveAttribute("href", "https://www.instagram.com/lsptikglobal/");
    assert.equal(await page.locator('.site-footer .social-icon-whatsapp').evaluate(element => getComputedStyle(element).color), "rgb(83, 104, 111)");
    for (const card of await page.locator(".home-card-grid > a").all()) {
      await expect(card.locator("li svg")).toHaveCount(4);
      assert.equal(await card.locator("ul").evaluate(element => getComputedStyle(element).gridTemplateColumns.split(" ").length), 2);
    }
    if (width >= 1024) {
      const videoBox = await page.locator("#video-profil").boundingBox();
      const aboutBox = await page.locator(".home-about-copy").boundingBox();
      assert(videoBox.x + videoBox.width <= aboutBox.x, "Video must sit left of About");
    }
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `Horizontal overflow at ${width}px`);
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.screenshot({ path: `/tmp/lsp-landing-review/landing-${width}.png`, fullPage: true });
  }
  await page.emulateMedia({ reducedMotion: "no-preference" });
  await page.clock.install();
  await page.goto(url);
  await expect(page.locator(".reviews-grid figcaption").first()).toContainText("Adam Smith");
  const reviewViewport = page.getByRole("region", { name: "Review peserta; geser untuk melihat lainnya" });
  const partnerViewport = page.getByRole("region", { name: "Logo mitra; geser untuk melihat lainnya" });
  await page.mouse.move(0, 0);
  await page.clock.runFor(2500);
  assert.equal(await reviewViewport.evaluate(element => element.scrollLeft), 0);
  assert.equal(await partnerViewport.evaluate(element => element.scrollLeft), 0);
  await page.clock.runFor(1500);
  await expect.poll(() => reviewViewport.evaluate(element => element.scrollLeft), { message: "Reviews slide automatically after 3 seconds" }).toBeGreaterThan(0);
  await expect.poll(() => partnerViewport.evaluate(element => element.scrollLeft), { message: "Partners advance automatically after 3 seconds" }).toBeGreaterThan(0);
  const cardWidth = await page.locator(".reviews-grid .review-card").first().evaluate(element => element.getBoundingClientRect().width);
  const viewportWidth = await reviewViewport.evaluate(element => element.clientWidth);
  assert(cardWidth < viewportWidth / 3 && cardWidth > viewportWidth / 4, "Three reviews fit per desktop viewport");
  await page.clock.resume();
  const pauseReviews = page.getByRole("button", { name: "Jeda review otomatis" });
  await pauseReviews.click();
  await expect(page.getByRole('button', { name: 'Lanjutkan review otomatis' })).toHaveAttribute('aria-pressed', 'true');
  await page.mouse.move(0, 0);
  await page.getByRole("search", { name: "Pencarian skema di Beranda" }).getByRole("searchbox").focus();
  await page.waitForTimeout(1000);
  const pausedPosition = await reviewViewport.evaluate(element => element.scrollLeft);
  await page.clock.runFor(10000);
  const afterPause = await reviewViewport.evaluate(element => element.scrollLeft);
  assert(Math.abs(afterPause - pausedPosition) <= 3, 'Paused reviews stay on the same cards: ' + pausedPosition + ' -> ' + afterPause);
  await page.getByRole("search", { name: "Pencarian skema di Beranda" }).getByRole("searchbox").fill("Data Analyst");
  await page.getByRole("search", { name: "Pencarian skema di Beranda" }).getByRole("button").click();
  await expect(page).toHaveURL(/\/skema-sertifikasi\?q=Data\+Analyst/);
  await page.clock.resume();
  await page.goto(url);
  const detailHref = await page.locator('.home-card-grid > a').first().getAttribute('href');
  await page.locator('.home-card-grid > a').first().click();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('summary').filter({ hasText: 'Deskripsi unit' })).toHaveCount(0);
  const publicPage = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  publicPage.on('pageerror', error => errors.push(error.message));
  await publicPage.goto(new URL(detailHref, url).href);
  await publicPage.waitForLoadState('networkidle');
  const unitRows = publicPage.locator('table tbody tr');
  await expect(unitRows).toHaveCount(5);
  await expect(publicPage.getByRole('combobox', { name: 'Baris per halaman' })).toHaveValue('5');
  await publicPage.getByRole('combobox', { name: 'Baris per halaman' }).selectOption('3');
  await expect(unitRows).toHaveCount(3);
  await publicPage.getByRole('button', { name: 'Berikutnya', exact: true }).click();
  await expect(unitRows.first().locator('td').first()).toHaveText('4');
  await expect.poll(() => publicPage.locator('table').evaluate(element => Math.round(element.getBoundingClientRect().top))).toBeLessThanOrEqual(125);
  await publicPage.getByRole('combobox', { name: 'Baris per halaman' }).selectOption('0');
  assert(await unitRows.count() > 5);
  for (const value of await publicPage.getByRole('combobox', { name: 'Baris per halaman' }).locator('option').evaluateAll(options => options.map(option => Number(option.value)))) assert(value <= await unitRows.count());
  await publicPage.route('https://tile.openstreetmap.org/**', route => route.fulfill({ contentType: 'image/png', body: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64') }));
  for (const route of ['/tentang-kami', '/informasi', '/skema-sertifikasi', '/galeri', '/kontak']) {
    await publicPage.goto(url + route);
    assert.equal(await publicPage.locator('h1').evaluate(element => getComputedStyle(element.closest('section')).backgroundColor), 'rgb(255, 255, 255)', route + ' has a white hero');
    if (['/skema-sertifikasi', '/galeri', '/kontak'].includes(route)) assert.equal(await publicPage.locator('main').evaluate(element => getComputedStyle(element).backgroundColor), 'rgb(255, 255, 255)');
    if (route === '/tentang-kami') {
      assert(await publicPage.locator('#profile-title').evaluate(element => parseFloat(getComputedStyle(element.closest('section')).paddingTop)) >= 48);
    }
    if (route === '/informasi') {
      for (const id of ['tuk', 'asesor']) {
        const directory = publicPage.locator('#' + id);
        await expect(directory.getByRole('combobox', { name: 'Per halaman', exact: true })).toHaveValue('5');
        await expect(directory.locator('tbody tr')).toHaveCount(5);
        await directory.getByRole('combobox', { name: 'Per halaman', exact: true }).selectOption('0');
        assert(await directory.locator('tbody tr').count() > 10, id + ' shows all rows');
        const total = await directory.locator('tbody tr').count();
        const options = await directory.getByRole('combobox', { name: 'Per halaman', exact: true }).locator('option').evaluateAll(options => options.map(option => Number(option.value)));
        assert(options.every(value => value <= total));
        assert.deepEqual(options, [1, 3, 5, 10, 25, 50, 100].filter(value => value <= total).concat(0));
      }
    }
    const surface = route === '/tentang-kami' ? publicPage.locator('.react-flow') : route === '/informasi' ? publicPage.locator('.leaflet-container') : null;
    if (surface) {
      await expect(surface).toBeVisible();
      await surface.scrollIntoViewIfNeeded();
      const box = await surface.boundingBox();
      await publicPage.mouse.move(box.x + box.width / 2, Math.max(1, Math.min(850, box.y + box.height / 2)));
      const before = await publicPage.evaluate(() => scrollY);
      await publicPage.mouse.wheel(0, 350);
      await expect.poll(() => publicPage.evaluate(() => scrollY)).toBeGreaterThan(before);
    }
  }
  await publicPage.goto(url + '/mitra');
  const partners = publicPage.getByRole('region', { name: 'Daftar semua mitra' });
  await expect(partners.locator('ul img')).toHaveCount(39);
  await expect(partners.locator('ul a')).toHaveCount(39);
  await expect(partners.getByRole('link', { name: /MED Studio Jakarta/ })).toHaveAttribute('href', 'https://desty.page/medstudiojakarta');
  await publicPage.goto(url);
  const allPartners = publicPage.getByRole('link', { name: 'Lihat Seluruh Mitra', exact: true });
  await allPartners.scrollIntoViewIfNeeded();
  assert(await publicPage.evaluate(() => scrollY) > 500);
  await allPartners.click();
  await expect(publicPage).toHaveURL(url + '/mitra');
  await expect.poll(() => publicPage.evaluate(() => scrollY)).toBe(0);
  await publicPage.goto(url + '/skema-sertifikasi');
  const catalog = publicPage.getByRole('region', { name: 'Katalog skema sertifikasi' });
  const search = catalog.getByRole('searchbox', { name: 'Cari skema', exact: true });
  await search.fill('Data');
  await catalog.getByRole('button', { name: 'Cari skema', exact: true }).click();
  await expect(publicPage).toHaveURL(/q=Data/);
  await expect.poll(() => search.locator('xpath=ancestor::form/parent::div').evaluate(element => Math.round(element.getBoundingClientRect().top))).toBeLessThanOrEqual(125);
  await catalog.getByRole('combobox', { name: 'Minimum', exact: true }).selectOption('6');
  await expect(publicPage).toHaveURL(/level_min=6/);
  async function expectHoverScale(element) {
    await element.hover();
    await expect.poll(() => element.evaluate(node => new DOMMatrix(getComputedStyle(node).transform).a)).toBeGreaterThan(1);
  }
  await publicPage.goto(url);
  const statistics = publicPage.getByRole('region', { name: 'Statistik LSP TIK Global' });
  assert.deepEqual(await statistics.locator('a').evaluateAll(links => links.map(link => link.getAttribute('href'))), ['/skema-sertifikasi', '/informasi#tuk', '/informasi#asesor']);
  await expectHoverScale(publicPage.getByRole('region', { name: 'Logo mitra; geser untuk melihat lainnya' }).locator('li').first());
  await publicPage.goto(url + '/mitra');
  await expectHoverScale(partners.locator('li').first());
  await publicPage.goto(url + '/galeri');
  const album = publicPage.getByRole('button', { name: /Buka album foto tahun/ }).first();
  await expectHoverScale(album);
  await publicPage.goto(url + '/tentang-kami#lisensi');
  const certificate = publicPage.getByRole('button', { name: /^Lihat Sertifikat/ }).first();
  await expect(certificate.getByText('Lihat Sertifikat ↗', { exact: true })).toBeVisible();
  await expectHoverScale(certificate);
  await certificate.click();
  await expect(publicPage.getByRole('dialog')).toBeVisible();
  await publicPage.getByRole('button', { name: 'Tutup tampilan fullscreen', exact: true }).click();
  await expect(certificate).toBeFocused();
  await publicPage.emulateMedia({ reducedMotion: 'reduce' });
  await certificate.hover();
  assert.equal(await certificate.evaluate(node => getComputedStyle(node).transform), 'none');
  await publicPage.emulateMedia({ reducedMotion: 'no-preference' });
  await publicPage.goto(url + '/informasi#tuk');
  const location = publicPage.locator('#tuk a[title="Klik untuk buka di Google Maps"]').first();
  await expect(location).toHaveAttribute('target', '_blank');
  await expect(publicPage.locator('#tuk').getByText('Klik alamat yang bertanda ↗ untuk buka di Google Maps.', { exact: true })).toBeVisible();
  await expect(location.locator('span')).toHaveCount(0);
  await location.hover();
  await expect.poll(() => location.evaluate(node => getComputedStyle(node).backgroundColor)).toBe('rgb(237, 245, 255)');
  assert.deepEqual(errors, []);
  console.log("PASS landing at 375–1440px, matching search bars, centered About title, carousels, white public pages, table pagination, map/graph scrolling and partner links.");
} finally {
  await browser?.close();
  server?.kill();
  api?.kill();
}

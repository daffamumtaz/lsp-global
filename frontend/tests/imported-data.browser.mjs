import { spawn } from 'node:child_process';
import assert from 'node:assert/strict';
import { chromium, expect } from '@playwright/test';

const root = process.cwd(), apiOrigin = 'http://127.0.0.1:18083', siteOrigin = process.env.TEST_SITE_ORIGIN || 'http://127.0.0.1:3104';
const api = spawn('php', ['-S', '127.0.0.1:18083', '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'], { cwd: root + '/../backend/public', env: { ...process.env, APP_URL: apiOrigin }, stdio: 'ignore' });
const site = process.env.TEST_SITE_ORIGIN ? null : spawn(process.execPath, ['node_modules/next/dist/bin/next', 'start', '--port', '3104'], { env: { ...process.env, CONTENT_API_URL: apiOrigin + '/api/content-directory', LANDING_API_URL: apiOrigin + '/api/landing-page', INFORMATION_API_URL: apiOrigin + '/api/information-page', PROCESSES_API_URL: apiOrigin + '/api/processes' }, stdio: 'ignore' });
let browser;
try {
  browser = await chromium.launch();
  const page = await browser.newPage();
  for (const url of [apiOrigin + '/api/content-directory', siteOrigin]) for (let attempt = 0; attempt < 60; attempt++) {
    try { if ((await page.request.get(url)).ok()) break; } catch {}
    if (attempt === 59) throw new Error('Server unavailable: ' + url);
    await new Promise(resolve => setTimeout(resolve, 500));
  }
  const { data } = await (await page.request.get(apiOrigin + '/api/content-directory')).json();
  assert.equal(data.tuks.length, 44);
  assert.equal(data.schemes.length, 39);
  assert.equal(data.assessors.length, 82);
  const partners = data.tuks.filter(tuk => tuk.logo_url);
  assert.equal(partners.length, 39);
  assert.equal(data.tuks.filter(tuk => tuk.maps_url).length, 37);
  assert.equal(data.tuks.filter(tuk => tuk.latitude !== null && tuk.longitude !== null).length, 37);
  for (const tuk of data.tuks) {
    assert.equal(tuk.latitude === null, tuk.longitude === null, tuk.name);
    if (tuk.latitude === null) { assert.ok(!tuk.maps_url, tuk.name); continue; }
    assert.ok(Math.abs(tuk.latitude) <= 90 && Math.abs(tuk.longitude) <= 180, tuk.name);
    assert.equal(new URL(tuk.maps_url).searchParams.get('query'), `${tuk.latitude},${tuk.longitude}`, tuk.name);
  }
  for (const partner of partners) {
    const image = await page.request.get(partner.logo_url);
    assert.equal(image.status(), 200, partner.name);
    assert.ok(image.headers()['content-type'].startsWith('image/'));
    await image.dispose();
  }
  for (const scheme of data.schemes) {
    const image = await page.request.get(scheme.thumbnail);
    assert.equal(image.status(), 200, scheme.name);
    assert.ok(image.headers()['content-type'].startsWith('image/'));
    await image.dispose();
  }
  const coder = data.schemes.find(scheme => scheme.name.includes('JUNIOR CODER'));
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('https://tile.openstreetmap.org/**', route => route.abort());
  for (const width of [320, 390, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto(siteOrigin + '/#mitra');
    const landingPartners = page.locator('#mitra');
    await expect(landingPartners.locator('ul img')).toHaveCount(partners.length);
    const strip = landingPartners.getByRole('region', { name: 'Logo mitra; geser untuk melihat lainnya' });
    const previous = landingPartners.getByRole('button', { name: 'Mitra sebelumnya', exact: true });
    const next = landingPartners.getByRole('button', { name: 'Mitra berikutnya', exact: true });
    await expect(previous).toBeDisabled();
    await expect(next).toBeEnabled();
    assert.ok(await next.evaluate(element => element.getBoundingClientRect().width >= 44 && element.getBoundingClientRect().height >= 44));
    await next.click();
    await expect(previous).toBeEnabled();
    assert.ok(await strip.evaluate(element => element.scrollLeft > 0));
    await previous.click();
    await expect(previous).toBeDisabled();
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await page.goto(siteOrigin + '/mitra');
    const partnerGrid = page.getByRole('region', { name: 'Daftar semua mitra', exact: true });
    await expect(partnerGrid.locator('ul li')).toHaveCount(data.tuks.length);
    await expect(partnerGrid.locator('ul img')).toHaveCount(partners.length);
    await expect(partnerGrid.locator('article, table, select, input, .leaflet-container')).toHaveCount(0);
    for (const partner of partners) {
      const logo = partnerGrid.getByRole('img', { name: partner.name, exact: true });
      await logo.scrollIntoViewIfNeeded();
      await expect(logo).toBeVisible();
      await expect.poll(() => logo.evaluate(image => image.complete && image.naturalWidth > 0), { message: partner.name }).toBe(true);
    }
    assert.ok(await partnerGrid.locator('img').evaluateAll(images => images.every(image => image.complete && image.naturalWidth > 0)), 'All partner images load');
    for (const partner of data.tuks.filter(tuk => !tuk.logo_url)) {
      const slot = partnerGrid.locator('li').filter({ hasText: partner.name });
      await expect(slot).toBeVisible();
      await expect(slot.locator('img')).toHaveCount(0);
    }
    const partnerLink = partnerGrid.getByRole('link', { name: 'Kunjungi website Jobhun (tab baru)' });
    await partnerLink.focus();
    await expect(partnerLink.locator('span')).toBeVisible();
    assert.notEqual(await partnerLink.evaluate(element => getComputedStyle(element).outlineStyle), 'none');
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await page.goto(siteOrigin + '/skema-sertifikasi/' + coder.slug);
    await expect(page.locator('tbody tr')).toHaveCount(8);
    const details = page.locator('details').first();
    await details.locator('summary').click();
    await expect(details.locator('p')).toContainText('struktur data');
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await page.goto(siteOrigin + '/informasi#tuk');
    const directory = page.locator('#tuk');
    const markers = directory.locator('.leaflet-marker-icon');
    await expect(markers.first()).toBeVisible();
    assert.equal(await markers.evaluateAll(elements => elements.reduce((sum, element) => sum + (/^\d+$/.test(element.textContent) ? Number(element.textContent) : 1), 0)), 37);
    await directory.locator('input[type="search"]').fill('Jobhun');
    await expect(markers).toHaveCount(1);
    await markers.first().click();
    await expect(directory.getByRole('status').filter({ hasText: 'TUK dalam pilihan peta' })).toContainText('1 TUK dalam pilihan peta');
    const mapsUrl = data.tuks.find(tuk => tuk.name.includes('Jobhun')).maps_url;
    await expect(directory.locator('tbody a')).toHaveAttribute('href', mapsUrl);
    const popup = directory.locator('.leaflet-popup');
    await expect(popup).toContainText('Jobhun');
    await expect.poll(async () => {
      const mapBox = await directory.locator('.leaflet-container').boundingBox();
      const popupBox = await popup.boundingBox();
      return popupBox.x >= mapBox.x && popupBox.x + popupBox.width <= mapBox.x + mapBox.width + 1;
    }, { message: 'Popup stays within map width after auto-pan' }).toBe(true);
    for (const name of ['Zoom in', 'Zoom out', 'Close popup']) {
      const box = await directory.getByRole('button', { name, exact: true }).boundingBox();
      assert.ok(box.width >= 44 && box.height >= 44, name);
    }
    await expect(popup.getByRole('link', { name: /di Google Maps/ })).toHaveAttribute('href', mapsUrl);
    await markers.first().focus();
    await page.keyboard.press('Escape');
    await expect(popup).toHaveCount(0);
    await page.keyboard.press('Enter');
    await expect(popup).toBeVisible();
    await popup.getByRole('button', { name: 'Close popup' }).click();
    await expect(popup).toHaveCount(0);
    await page.locator('#tuk input[type="search"]').fill('Saintisindo');
    await expect(page.locator('#tuk').getByText('TUK Saintisindo Banten', { exact: true })).toBeVisible();
    await expect(markers).toHaveCount(0);
    await expect(directory.locator('tbody a')).toHaveCount(0);
    await expect(directory.getByRole('status').filter({ hasText: 'TUK belum memiliki lokasi peta' })).toContainText('1 TUK belum memiliki lokasi peta');
    await page.locator('#asesor input[type="search"]').fill('Rudi Safarudin');
    await expect(page.locator('#asesor').getByText('Rudi Safarudin', { exact: true })).toBeVisible();
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    console.log('PASS imported data/UI at', width);
  }
  const touch = await browser.newPage({ viewport: { width: 390, height: 1000 }, isMobile: true, hasTouch: true });
  await touch.goto(siteOrigin + '/mitra');
  const touchGrid = touch.getByRole('region', { name: 'Daftar semua mitra', exact: true });
  await expect(touchGrid.locator('li').first().locator('span')).toBeVisible();
  await touch.goto(siteOrigin + '/informasi#tuk');
  await touch.locator('#tuk input[type="search"]').fill('Jobhun');
  await touch.locator('#tuk .leaflet-marker-icon').tap();
  await expect(touch.locator('#tuk .leaflet-popup')).toContainText('Buka di Google Maps');
  await touch.close();
  assert.deepEqual(errors, []);
  console.log('PASS 44 partners, 39 logos, 37 locations; descriptions, search, no JS errors');
} finally {
  await browser?.close();
  api.kill();
  site?.kill();
}

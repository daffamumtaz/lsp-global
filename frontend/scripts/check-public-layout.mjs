import assert from 'node:assert/strict';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const origin = process.env.SITE_ORIGIN || 'http://localhost:3000';
const api = process.env.API_ORIGIN || 'http://localhost:8000';
const { data } = await (await fetch(`${api}/api/content-directory`)).json();
const routes = ['/', '/tentang-kami', '/skema-sertifikasi', '/informasi', '/galeri', '/mitra', '/kontak', ...data.schemes.map(scheme => `/skema-sertifikasi/${scheme.slug}`)];
const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_EXECUTABLE });
try {
  const page = await browser.newPage({ reducedMotion: 'reduce' });
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  for (const width of [320, 375, 390, 768, 1024, 1280, 1536]) {
    await page.setViewportSize({ width, height: 900 });
    for (const route of routes) {
      const response = await page.goto(`${origin}${route}`);
      assert.equal(response.status(), 200, `${route} at ${width}px`);
      assert.equal(await page.locator('main').count(), 1);
      assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${route} overflows at ${width}px`);
    }
  }
  await page.setViewportSize({ width: 320, height: 640 });
  await page.goto(`${origin}/tentang-kami`);
  const photo = page.getByRole('button', { name: 'Lihat Dokumentasi kegiatan LSP TIK Global 1 fullscreen' });
  await photo.click();
  const dialog = page.locator('dialog[open]');
  await dialog.waitFor();
  assert.ok(await dialog.evaluate(element => [...element.querySelectorAll('button')].filter(button => !button.closest('[class*="thumbnails"]')).every(button => {
    const bounds = button.getBoundingClientRect();
    return bounds.left >= 0 && bounds.right <= innerWidth + 1;
  })), 'Viewer controls must fit the screen');
  await page.getByRole('button', { name: 'Perbesar foto' }).click();
  await page.getByRole('button', { name: 'Media berikutnya' }).click();
  await page.keyboard.press('Escape');
  assert.ok(await photo.evaluate(element => document.activeElement === element));
  await page.setViewportSize({ width: 1280, height: 900 });
  const trigger = page.getByRole('button', { name: 'Hubungi kami', exact: true });
  await trigger.hover();
  await page.waitForFunction(() => document.querySelector('.contact-bubble-trigger').getAttribute('aria-expanded') === 'true');
  await page.keyboard.press('Escape');
  await page.waitForFunction(() => getComputedStyle(document.querySelector('.contact-bubble-options')).visibility === 'hidden');
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, reducedMotion: 'reduce' });
  await context.route('https://wa.me/**', route => route.fulfill({ status: 200, body: 'Test destination' }));
  const mobile = await context.newPage();
  await mobile.goto(`${origin}/tentang-kami`);
  await mobile.getByRole('button', { name: 'Hubungi kami', exact: true }).tap();
  const whatsapp = mobile.getByRole('link', { name: 'Hubungi kami melalui WhatsApp (tab baru)', exact: true });
  await whatsapp.tap();
  assert.equal(context.pages().length, 1, 'First tap must not open WhatsApp');
  await mobile.waitForFunction(() => getComputedStyle(document.querySelector('.contact-bubble-social[data-selected="true"]')).backgroundColor === 'rgb(0, 65, 149)');
  const opened = context.waitForEvent('page');
  await whatsapp.tap();
  const popup = await opened;
  await popup.waitForLoadState();
  assert.ok(popup.url().startsWith('https://wa.me/'));
  await mobile.locator('h1').tap();
  assert.equal(await mobile.locator('.contact-bubble-trigger').getAttribute('aria-expanded'), 'false');
  await context.close();
  assert.deepEqual(errors, []);
  console.log(`${routes.length} public routes at 7 widths, viewer, hover/Escape and two-tap contact passed.`);
} finally {
  await browser.close();
}

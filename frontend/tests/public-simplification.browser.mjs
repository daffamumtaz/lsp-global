import assert from 'node:assert/strict';
import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: true });
try {
  for (const width of [375, 1440]) {
    const page = await browser.newPage({ viewport: { width, height: 1000 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://localhost:3000');
    const hero = page.locator('.hero input[type="search"]');
    assert.equal(await hero.getAttribute('placeholder'), 'Cari Skema, cth: Data Analyst');
    await page.getByRole('button', { name: 'Buka pencarian skema' }).click();
    const modal = page.locator('#header-search-dialog input');
    await modal.waitFor({ state: 'visible' });
    assert.equal(await modal.getAttribute('placeholder'), await hero.getAttribute('placeholder'));
    await page.keyboard.press('Escape');
    if (width === 375) await page.getByRole('button', { name: 'Menu', exact: true }).click();
    await page.getByRole('button', { name: 'Bagian Tentang Kami' }).click();
    assert.equal(await page.locator('#about-navigation-links a[href="/mitra"]').count(), 0);
    assert.equal(await page.locator('#about-navigation-links a').count(), 3);
    for (const route of ['/informasi', '/tentang-kami']) {
      await page.goto(`http://localhost:3000${route}`);
      assert.equal(await page.getByRole('button', { name: /^Lihat (daftar|bagan)$/ }).count(), 0);
      if (route === '/informasi') {
        await page.getByRole('list', { name: 'Tahapan sertifikasi jarak jauh' }).waitFor();
        assert.equal(await page.locator('#alur-asesmen .react-flow').count(), 0);
        assert.equal(await page.getByTestId('assessment-list').count(), 0);
      } else {
        await page.locator('.react-flow__node').first().waitFor({ timeout: 30000 });
        const zoom = page.getByRole('button', { name: 'Perbesar bagan', exact: true });
        await zoom.scrollIntoViewIfNeeded();
        await zoom.click();
      }
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    }
    assert.deepEqual(errors, []);
    await page.close();
  }
  console.log('PASS: caption, modal, dropdown, public stepper, organization zoom and mobile overflow.');
} finally {
  await browser.close();
}

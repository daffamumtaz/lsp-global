import assert from 'node:assert/strict';
import { chromium, expect } from '@playwright/test';

const origin = process.env.SCHEME_ORIGIN ?? 'http://127.0.0.1:18781/lspglobal/public';
const browser = await chromium.launch({ headless: true });
try {
  const page = await browser.newPage();
  for (const width of [1440, 390]) {
    await page.setViewportSize({ width, height: 950 });
    await page.goto(`${origin}/skema-sertifikasi/data-analyst`);
    const tabs = page.getByRole('tab');
    await expect(tabs).toHaveCount(2);
    await expect(tabs.nth(0)).toHaveText(/Persyaratan/i);
    await expect(tabs.nth(0)).toHaveAttribute('aria-selected', 'true');
    const requirements = page.getByRole('tabpanel', { name: /Persyaratan/i });
    const units = page.getByRole('tabpanel', { name: /Unit Kompetensi/i, includeHidden: true });
    await expect(requirements).toBeVisible();
    await expect(units).toBeHidden();
    assert.match(await requirements.innerText(), /Mahasiswa|Fotokopi|Copy/i);
    await tabs.nth(0).focus();
    await page.keyboard.press('ArrowRight');
    await expect(tabs.nth(1)).toBeFocused();
    await expect(tabs.nth(1)).toHaveAttribute('aria-selected', 'true');
    await expect(units).toBeVisible();
    await expect(units.getByRole('table')).toBeVisible();
    await page.keyboard.press('Home');
    await expect(requirements).toBeVisible();
    await page.keyboard.press('End');
    await expect(units).toBeVisible();
    await tabs.nth(0).click();
    await expect(requirements).toBeVisible();
    await expect(tabs.nth(0)).toHaveAttribute('aria-selected', 'true');
    await expect(tabs.nth(0)).toHaveCSS('border-bottom-color', 'rgb(0, 65, 149)');
    await expect(tabs.nth(1)).toHaveCSS('border-bottom-color', 'rgba(0, 0, 0, 0)');
    await page.screenshot({ path: `/tmp/scheme-requirements-first-${width}.png`, fullPage: false });
    console.log(`PASS ${width}px: requirements first/default, units usable, keyboard and ARIA aligned`);
  }
} finally {
  await browser.close();
}

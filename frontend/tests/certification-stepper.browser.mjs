import assert from 'node:assert/strict';
import { chromium, expect } from '@playwright/test';

const browser = await chromium.launch({ headless: true });

async function checkConnectorGaps(steps, desktop) {
  const gaps = await steps.evaluateAll((items, horizontal) => items.slice(0, -1).map((item, index) => {
    const button = item.querySelector('button');
    const circle = button.firstElementChild.getBoundingClientRect();
    const nextCircle = items[index + 1].querySelector('button').firstElementChild.getBoundingClientRect();
    const origin = (horizontal ? button : item).getBoundingClientRect();
    const line = getComputedStyle(horizontal ? button : item, horizontal ? '::after' : '::before');
    const start = horizontal ? origin.x + parseFloat(line.left) : origin.y + parseFloat(line.top);
    const end = start + parseFloat(horizontal ? line.width : line.height);
    return {
      startGap: start - (horizontal ? circle.right : circle.bottom),
      endGap: (horizontal ? nextCircle.left : nextCircle.top) - end,
      length: end - start,
    };
  }), desktop);
  assert.ok(gaps.every(gap => gap.startGap >= 8 && gap.endGap >= 8 && gap.length > 0), JSON.stringify(gaps));
}

try {
  for (const width of [320, 375, 768, 1024, 1440, 1920]) {
    const page = await browser.newPage({ viewport: { width, height: 1000 }, reducedMotion: 'reduce' });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(`${process.env.TEST_BASE_URL || 'http://localhost:3000'}/informasi#alur-asesmen`);
    const flow = page.locator('#alur-asesmen');
    const steps = flow.getByRole('list', { name: 'Tahapan sertifikasi jarak jauh' }).getByRole('listitem');
    await expect(steps).toHaveCount(6);
    assert.equal(await flow.locator('.react-flow').count(), 0);
    const buttons = steps.locator(':scope > button');
    const bounds = await buttons.evaluateAll(nodes => nodes.map(node => {
      const rect = node.getBoundingClientRect();
      return { x: rect.x, y: rect.y, right: rect.right };
    }));
    assert.ok(bounds.every(rect => rect.x >= 0 && rect.right <= width));
    assert.equal(width >= 1024 ? bounds.every(rect => rect.y === bounds[0].y) : bounds.every((rect, index) => !index || rect.y > bounds[index - 1].y), true);

    for (let index = 0; index < 6; index++) {
      const button = buttons.nth(index);
      await button.focus();
      await page.keyboard.press(index % 2 ? 'Space' : 'Enter');
      await expect(button).toHaveAttribute('aria-current', 'step');
      await expect(flow.locator('[aria-current="step"]')).toHaveCount(1);
      const region = steps.nth(index).getByRole('region');
      await expect(region).toBeVisible();
      await expect(flow.getByRole('region')).toHaveCount(1);
      await expect(region.locator('h3')).toBeVisible();
      assert.ok((await region.locator('p').nth(1).innerText()).length > 100);
      await checkConnectorGaps(steps, width >= 1024);
      await expect(button.locator(':scope > span').first()).toHaveText(String(index + 1));
      if (width < 1024) {
        const panel = await region.boundingBox();
        const control = await button.boundingBox();
        assert.ok(panel.y >= control.y + control.height);
        if (index < 5) assert.ok(panel.y + panel.height <= (await buttons.nth(index + 1).boundingBox()).y);
      }
      await expect(region.getByRole('button')).toHaveCount(0);
      await expect(region.locator('details, h4')).toHaveCount(0);
      assert.equal(await button.locator(':scope > span').last().evaluate(node => getComputedStyle(node).textDecorationLine), 'none');
      if (index === 4) {
        await expect(region.getByText(/Jika dinyatakan kompeten/)).toBeVisible();
        await expect(region.getByText(/Jika belum kompeten/)).toBeVisible();
        await expect(region.getByText(/termasuk asesmen ulang sesuai ketentuan LSP/)).toBeVisible();
        await region.locator('h3').click();
        await page.screenshot({ path: `/tmp/sjj-${width}.png`, fullPage: true });
        await flow.screenshot({ path: `/tmp/sjj-section-${width}.png`, style: '.site-header-shell, .skip-link, .contact-bubble { visibility: hidden !important; }' });
      }
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    }
    await buttons.first().click();
    assert.doesNotMatch(await flow.innerText(), /Sudah dilihat|Sudah dibaca|Tanda centang|Dipilih|Lihat tahap/);
    for (let index = 0; index < 6; index++) {
      await expect(buttons.nth(index).locator(':scope > span').first()).toHaveText(String(index + 1));
    }
    await expect(flow.getByRole('link', { name: 'Lihat Skema Sertifikasi' })).toHaveAttribute('href', '/skema-sertifikasi');
    await expect(flow.getByRole('link', { name: 'Hubungi Kami' })).toHaveAttribute('href', '/kontak');
    assert.doesNotMatch(await flow.innerText(), /FR\.APL/);
    assert.deepEqual(errors, []);
    await page.close();
  }
  console.log('PASS: connector gaps, persistent numbers, no read status, six stages, responsive placement, keyboard selection, unified narrative, no underline, CTA routes and overflow at 320/375/768/1024/1440/1920px.');
} finally {
  await browser.close();
}

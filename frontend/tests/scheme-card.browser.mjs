import assert from 'node:assert/strict';
import { chromium } from '@playwright/test';
const browser = await chromium.launch({ headless: true });
try {
  const page = await browser.newPage();
  for (const width of [1440, 1024, 768, 640, 390, 320]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto('http://localhost:3000/skema-sertifikasi');
    const cards = page.locator('a[aria-label^="Lihat detail "]');
    await cards.first().waitFor();
    const result = await cards.evaluateAll(cards => {
      const cases = [['PENGEMBANG WEB (WEB DEVELOPER)', 'Pengembangan aplikasi web dan perangkat lunak'], ['DATA ANALYST', 'Data'], ['NETWORK ADMINISTRATOR', 'Administrasi jaringan komputer']];
      cards.forEach((card, i) => {
        const [title, category] = cases[i % 3];
        card.querySelector('h3').textContent = title;
        card.querySelector('h3 + p').textContent = category;
        card.querySelectorAll('li span')[2].textContent = '22 Unit';
        card.querySelectorAll('li span')[1].textContent = 'Luring & Daring';
        if (i % 2) card.querySelector('ul + div > p').textContent = 'Hubungi kami untuk biaya';
      });
      const rect = el => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y, height: r.height, width: r.width }; };
      return { overflow: document.documentElement.scrollWidth > innerWidth, cards: cards.map(card => ({
        box: rect(card), title: rect(card.querySelector('h3')), category: rect(card.querySelector('h3 + p')), metadata: rect(card.querySelector('ul')), footer: rect(card.querySelector('ul + div')), button: rect(card.querySelector('ul + div > span')),
        clipped: [...card.querySelectorAll('h3, p, li span')].some(el => el.scrollWidth > el.clientWidth + 1 || el.scrollHeight > el.clientHeight + 1),
        headingGap: rect(card.querySelector('h3 + p')).y - (rect(card.querySelector('h3')).y + rect(card.querySelector('h3')).height),
        collision: [...card.querySelectorAll('li')].some(li => rect(li.querySelector('svg')).x + 20 > rect(li.querySelector('span')).x),
      })) };
    });
    assert.equal(result.overflow, false, `${width}: page overflow`);
    for (const card of result.cards) { assert.equal(card.clipped, false, `${width}: clipped text`); assert.equal(card.collision, false, `${width}: icon collision`); assert.ok(Math.abs(card.headingGap - 6) < 1, `${width}: subtitle must follow title`); }
    for (let i = 0; i < result.cards.length - 1; i++) {
      const a = result.cards[i], b = result.cards[i + 1];
      if (Math.abs(a.box.y - b.box.y) > 1) continue;
      for (const [key, field] of [['box','height'], ['metadata','y'], ['footer','y'], ['button','y']]) assert.ok(Math.abs(a[key][field] - b[key][field]) < 1, `${width}: ${key}.${field}: ${a[key][field]} vs ${b[key][field]}`);
    }
    if (width === 1440) {
      await cards.first().hover();
      await page.waitForTimeout(220);
      const hover = await cards.first().evaluate(card => ({ transform: getComputedStyle(card).transform, background: getComputedStyle(card).backgroundColor, border: getComputedStyle(card).borderColor, href: card.getAttribute('href') }));
      assert.match(hover.transform, /1\.03/);
      assert.equal(hover.background, 'rgb(237, 245, 255)');
      assert.equal(hover.border, 'rgb(21, 118, 181)');
      assert.ok(hover.href.startsWith('/skema-sertifikasi/'));
      await page.mouse.move(0, 0);
    }
    console.log(`PASS ${width}px: row alignment, wrapping, no clipping or icon collisions`);
  }
} finally { await browser.close(); }

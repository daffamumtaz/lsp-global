import assert from 'node:assert/strict';
import { mkdir, writeFile } from 'node:fs/promises';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const origin = process.env.SITE_ORIGIN || 'http://localhost:3000';
const api = process.env.API_ORIGIN || 'http://localhost:8000';
const artifacts = process.env.AUDIT_ARTIFACTS || '/tmp/lsp-organization-audit';
const { data } = await (await fetch(`${api}/api/organization`)).json();
assert.ok(data.length, 'An active organization is required for this browser check');
await mkdir(artifacts, { recursive: true });
const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROMIUM_EXECUTABLE });
const results = [];
try {
  const context = await browser.newContext({ reducedMotion: 'reduce' });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const zoom = () => page.locator('.react-flow__viewport').evaluate(element => Number(element.style.transform.match(/scale\(([^)]+)/)[1]));
  for (const width of [320, 375, 390, 768, 1024, 1440, 1920]) {
    await page.setViewportSize({ width, height: 1100 });
    for (const route of ['/tentang-kami', '/tentang-kami/struktur-organisasi']) {
      const response = await page.goto(`${origin}${route}`);
      assert.equal(response.status(), 200);
      assert.equal(await page.getByRole('button', { name: /^Lihat (daftar|bagan)$/ }).count(), 0);
      await page.locator('.react-flow').scrollIntoViewIfNeeded();
      await page.locator('.react-flow__node').last().waitFor({ state: 'visible' });
      await page.evaluate(() => document.fonts.ready);
      await page.waitForFunction(count => document.querySelectorAll('.react-flow__edge-path').length === count, data.filter(node => node.parent_id !== null).length);
      assert.equal(await page.locator('.react-flow__node').count(), data.length);
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true, `Page overflow at ${width}px`);
      const geometry = await page.evaluate(() => {
        const nodes = [...document.querySelectorAll('.react-flow__node')].map(element => {
          const box = element.getBoundingClientRect();
          return { id: element.dataset.id, x: box.x, y: box.y, right: box.right, bottom: box.bottom, font: parseFloat(getComputedStyle(element.querySelector('p')).fontSize) * box.width / element.offsetWidth };
        });
        const overlaps = [];
        for (let i = 0; i < nodes.length; i++) for (let j = i + 1; j < nodes.length; j++) {
          const a = nodes[i], b = nodes[j];
          if (a.x < b.right - 1 && a.right > b.x + 1 && a.y < b.bottom - 1 && a.bottom > b.y + 1) overlaps.push([a.id, b.id]);
        }
        const crossings = [];
        for (const path of document.querySelectorAll('.react-flow__edge-path')) {
          const values = path.getAttribute('d').match(/-?\d+(?:\.\d+)?/g).map(Number);
          const matrix = path.getScreenCTM();
          const points = [];
          for (let i = 0; i < values.length; i += 2) points.push(new DOMPoint(values[i], values[i + 1]).matrixTransform(matrix));
          for (let i = 1; i < points.length; i++) {
            const a = points[i - 1], b = points[i];
            for (const node of nodes) {
              const crosses = Math.abs(a.x - b.x) < .1
                ? a.x > node.x + 1 && a.x < node.right - 1 && Math.max(a.y, b.y) > node.y + 1 && Math.min(a.y, b.y) < node.bottom - 1
                : a.y > node.y + 1 && a.y < node.bottom - 1 && Math.max(a.x, b.x) > node.x + 1 && Math.min(a.x, b.x) < node.right - 1;
              if (crosses) crossings.push([path.id, node.id]);
            }
          }
        }
        return { overlaps, crossings, minimumFont: Math.min(...nodes.map(node => node.font)) };
      });
      assert.deepEqual(geometry.overlaps, [], `${route}: overlapping cards at ${width}px`);
      assert.deepEqual(geometry.crossings, [], `${route}: connectors cross cards at ${width}px`);
      const initial = await zoom();
      await page.getByRole('button', { name: 'Perbesar bagan', exact: true }).click();
      await page.waitForFunction(before => Number(document.querySelector('.react-flow__viewport').style.transform.match(/scale\(([^)]+)/)[1]) > before + .01, initial);
      const enlarged = await zoom();
      await page.getByRole('button', { name: 'Perkecil bagan', exact: true }).click();
      await page.waitForFunction(before => Number(document.querySelector('.react-flow__viewport').style.transform.match(/scale\(([^)]+)/)[1]) < before - .01, enlarged);
      await page.getByRole('button', { name: 'Sesuaikan bagan', exact: true }).click();
      const pane = await page.locator('.react-flow__pane').boundingBox();
      const beforePan = await page.locator('.react-flow__viewport').getAttribute('style');
      const panY = Math.max(160, pane.y + 40);
      await page.mouse.move(pane.x + pane.width - 24, panY);
      await page.mouse.down(); await page.mouse.move(pane.x + pane.width - 100, panY + 50, { steps: 8 }); await page.mouse.up();
      assert.notEqual(await page.locator('.react-flow__viewport').getAttribute('style'), beforePan, 'Dragging must pan the diagram');
      await page.getByRole('button', { name: 'Sesuaikan bagan', exact: true }).click();
      if ([375, 1440].includes(width)) await page.locator('.react-flow').locator('..').locator('..').screenshot({ path: `${artifacts}/organization-${width}-${route.endsWith('struktur-organisasi') ? 'embed' : 'public'}.png` });
      const text = await page.locator('.react-flow').innerText();
      for (const node of data) for (const member of node.members || [node]) assert.ok(text.includes(member.person_name), `List is missing ${member.person_name}`);
      results.push({ route, width, cards: data.length, minimumOverviewFont: Number(geometry.minimumFont.toFixed(1)) });
    }
  }
  assert.deepEqual(errors, []);
  await writeFile(`${artifacts}/public-results.json`, JSON.stringify({ browser: await browser.version(), results, errors }, null, 2));
  console.log(`${results.length} route/width cases passed: geometry, zoom, pan, diagram content and no JavaScript errors.`);
  console.log(JSON.stringify(results));
} finally { await browser.close(); }

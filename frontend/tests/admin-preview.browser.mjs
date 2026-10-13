import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { chromium, expect } from '@playwright/test';

const parentOrigin = 'http://127.0.0.1:18082';
const frontendOrigin = 'http://127.0.0.1:3102';
const parent = createServer((_req, res) => {
  res.setHeader('Content-Type', 'text/html');
  res.end(`<html lang="id"><body><iframe title="Preview" src="${frontendOrigin}/admin-preview/tuks"></iframe><script>window.readyCount=0;window.latest=null;window.addEventListener('message',event=>{if(event.origin==='${frontendOrigin}'&&event.source===document.querySelector('iframe').contentWindow&&event.data.type==='admin-preview:ready'){window.readyCount++;if(window.latest)event.source.postMessage(window.latest,'${frontendOrigin}')}});window.send=payload=>{window.latest=payload;document.querySelector('iframe').contentWindow.postMessage(payload,'${frontendOrigin}')};</script></body></html>`);
});
await new Promise(resolve => parent.listen(18082, '127.0.0.1', resolve));
const server = spawn(process.execPath, ['node_modules/next/dist/bin/next', 'start', '--port', '3102'], { cwd: new URL('..', import.meta.url), env: { ...process.env, ADMIN_PREVIEW_ORIGIN: parentOrigin }, stdio: 'ignore' });
let browser;
try {
  for (let attempt = 0; attempt < 60; attempt++) {
    try { if ((await fetch(`${frontendOrigin}/admin-preview/tuks`)).ok) break; } catch {}
    if (attempt === 59) throw new Error('Preview route unavailable; run npm run build first.');
    await new Promise(resolve => setTimeout(resolve, 500));
  }
  browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('https://tile.openstreetmap.org/**', route => route.abort());
  await page.goto(parentOrigin);
  await expect.poll(() => page.evaluate(() => window.readyCount)).toBe(1);
  const preview = page.frameLocator('iframe');
  await expect(preview.getByRole('status')).toContainText('Menunggu data preview');
  const tuk = { id: '1', name: 'TUK Draft', url: '', logo_url: '', code: '', city: 'Bandung', province: 'Jawa Barat', address: 'Alamat draft', maps_url: '', latitude: null, longitude: null, is_active: false };
  const payload = { type: 'admin-preview:update', kind: 'tuks', revision: 1, draft: true, data: [tuk] };
  const send = value => page.evaluate(value => window.send(value), value);
  await send(payload);
  await expect(preview.getByText('Belum disimpan', { exact: true })).toBeVisible();
  await expect(preview.getByText('TUK Draft', { exact: true })).toBeVisible();
  await expect(preview.getByText('Alamat draft', { exact: true })).toBeVisible();
  await expect(preview.locator('li')).toContainText('Nonaktif');
  await expect(preview.locator('header, footer')).toHaveCount(0);
  await expect(preview.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/);
  await send({ ...payload, revision: 0, data: [{ ...tuk, name: 'Stale' }] });
  await expect(preview.getByText('TUK Draft', { exact: true })).toBeVisible();
  const frame = page.frames().find(frame => frame.url().includes('/admin-preview/tuks'));
  await frame.evaluate(payload => window.postMessage(payload, window.location.origin), { ...payload, revision: 2, data: [{ ...tuk, name: 'Untrusted' }] });
  await expect(preview.getByText('TUK Draft', { exact: true })).toBeVisible();
  await send({ ...payload, revision: 2, data: [{ ...tuk, latitude: 1 }] });
  await expect(preview.locator('main [role="alert"]')).toContainText('Koordinat TUK tidak valid');
  await expect(preview.getByText('TUK Draft', { exact: true })).toBeVisible();
  const updated = { ...payload, revision: 3, draft: false, data: [{ ...tuk, name: 'TUK Disimpan', is_active: true, latitude: -6.9, longitude: 107.6 }] };
  await send(updated);
  await expect(preview.locator('main [role="alert"]')).toHaveCount(0);
  await expect(preview.getByText('Belum disimpan', { exact: true })).toHaveCount(0);
  await expect(preview.locator('.leaflet-marker-icon')).toHaveCount(1);
  await page.evaluate(() => { const frame = document.querySelector('iframe'); frame.src = frame.src; });
  await expect.poll(() => page.evaluate(() => window.readyCount)).toBe(2);
  await expect(preview.getByText('TUK Disimpan', { exact: true })).toBeVisible();
  assert.deepEqual(errors, []);
  console.log('PASS admin preview: origin/source, ready/reload, stale revisions, validation retention, draft/inactive labels, map, chrome-free, noindex');
} finally {
  await browser?.close();
  server.kill();
  await new Promise(resolve => parent.close(resolve));
}

import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { chromium, expect } from '@playwright/test';

const clip = await readFile(new URL('../../backend/tests/Fixtures/gallery-video.webm', import.meta.url));
const apiOrigin = 'http://127.0.0.1:18084';
const origin = 'http://127.0.0.1:3105';
const activity = { id: '1', title: 'Asesmen Video', year: 2025, date: '2025-08-12', location: 'Jakarta', description: '', media: [{ id: 'video-1', type: 'video', src: `${apiOrigin}/clip.webm`, thumbnail: '', alt: 'Asesmen Video', width: 160, height: 90 }] };
const api = createServer((req, res) => {
  if (req.url?.startsWith('/clip.webm')) {
    res.writeHead(200, { 'Content-Type': 'video/webm', 'Content-Length': clip.length }).end(clip);
    return;
  }
  res.setHeader('Content-Type', 'application/json');
  res.end(JSON.stringify({ data: { tuks: [], assessors: [], schemes: [], gallery: [activity] } }));
});
await new Promise(resolve => api.listen(18084, '127.0.0.1', resolve));
const server = spawn(process.execPath, ['node_modules/next/dist/bin/next', 'start', '--port', '3105'], {
  cwd: new URL('..', import.meta.url), env: { ...process.env, CONTENT_API_URL: `${apiOrigin}/api/content-directory` }, stdio: 'ignore',
});
let browser;
try {
  for (let attempt = 0; attempt < 60; attempt++) {
    try { if ((await fetch(`${origin}/galeri`)).ok) break; } catch {}
    if (attempt === 59) throw new Error('Gallery test server unavailable');
    await new Promise(resolve => setTimeout(resolve, 500));
  }
  browser = await chromium.launch({ headless: true });
  for (const width of [375, 1440]) {
    const page = await browser.newPage({ viewport: { width, height: 1000 }, reducedMotion: 'reduce' });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(`${origin}/galeri?jenis=video`);
    const album = page.getByRole('button', { name: 'Buka album video tahun 2025', exact: true });
    await expect.poll(() => album.locator('video').evaluate(node => node.readyState)).toBeGreaterThanOrEqual(2);
    await album.click();
    const card = page.getByRole('button', { name: 'Buka Asesmen Video', exact: true });
    await expect.poll(() => card.locator('video').evaluate(node => node.videoWidth)).toBe(160);
    await expect(card.locator('img')).toHaveCount(0);
    const metadata = await card.locator('p').evaluateAll(nodes => nodes.map(node => node.getBoundingClientRect().y));
    assert.ok(metadata[1] > metadata[0]);
    await card.click();
    const dialog = page.getByRole('dialog');
    const player = dialog.locator('video[controls]');
    await expect(player).toHaveAttribute('src', activity.media[0].src);
    assert.equal(await player.getAttribute('poster'), null);
    const thumbnail = dialog.getByRole('group', { name: 'Pilih media' }).locator('video');
    await expect.poll(() => thumbnail.evaluate(node => node.readyState)).toBeGreaterThanOrEqual(2);
    const viewerMetadata = await dialog.locator('span').filter({ has: page.locator('svg') }).evaluateAll(nodes => nodes.map(node => node.getBoundingClientRect().y));
    assert.ok(viewerMetadata[1] > viewerMetadata[0]);
    await expect(dialog.getByRole('group', { name: 'Pilih media' }).locator('img')).toHaveCount(0);
    await page.keyboard.press('Escape');
    await expect(dialog).toHaveCount(0);
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    await page.goto(`${origin}/tentang-kami#lisensi`);
    const certificate = page.getByRole('button', { name: /^Lihat Sertifikat/ }).first();
    await expect(certificate).toBeVisible();
    const radii = await certificate.evaluate(node => ({ card: getComputedStyle(node).borderRadius, hint: getComputedStyle(node.querySelector('.certificate-hint')).borderRadius }));
    assert.equal(radii.hint, radii.card);
    assert.deepEqual(errors, []);
    await page.close();
  }
  console.log('PASS: separate gallery metadata rows, video frames from uploaded file in album/card/viewer, no generic poster, certificate button radius matches card, 375/1440px.');
} finally {
  await browser?.close();
  server.kill();
  await new Promise(resolve => api.close(resolve));
}

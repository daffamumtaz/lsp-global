import assert from 'node:assert/strict';
import { mkdtemp, writeFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync, spawn } from 'node:child_process';
import { chromium, expect as baseExpect } from '@playwright/test';

const expect = baseExpect.configure({ timeout: 15000 });
const backend = fileURLToPath(new URL('../../backend', import.meta.url));
const temporary = await mkdtemp(path.join(tmpdir(), 'filament-preview-'));
const database = path.join(temporary, 'database.sqlite');
await writeFile(database, '');
const adminOrigin = 'http://127.0.0.1:18083';
const frontendOrigin = 'http://127.0.0.1:3104';
const env = { ...process.env, APP_ENV: 'testing', APP_URL: adminOrigin, APP_CONFIG_CACHE: path.join(temporary, 'config.php'), DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'file', SESSION_SECURE_COOKIE: 'false', FRONTEND_URL: frontendOrigin };
const php = code => execFileSync('php', ['-r', `require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); ${code}`], { cwd: backend, env, encoding: 'utf8' });
let adminServer;
let frontendServer;
let browser;
try {
  execFileSync('php', ['artisan', 'migrate', '--force', '--no-interaction'], { cwd: backend, env, stdio: 'pipe' });
  php("App\\Models\\Tuk::query()->delete(); App\\Models\\User::factory()->admin()->create(['email'=>'preview@example.test','password'=>'preview-test-password']); App\\Models\\Tuk::factory()->create(['name'=>'TUK Tersimpan','address'=>'Alamat tersimpan','latitude'=>-6.9,'longitude'=>107.6]);");
  const processId = Number(php("$process = App\\Models\\Process::factory()->create(['title'=>'Proses Browser','slug'=>'proses-browser']); $stage = App\\Models\\ProcessStage::factory()->create(['process_id'=>$process->id,'title'=>'Tahap Browser']); App\\Models\\ProcessStep::factory()->create(['stage_id'=>$stage->id,'title'=>'Aktivitas Tersimpan']); echo $process->id;"));
  adminServer = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=18083'], { cwd: backend, env, stdio: 'ignore' });
  frontendServer = spawn(process.execPath, ['node_modules/next/dist/bin/next', 'start', '--port', '3104'], { cwd: new URL('..', import.meta.url), env: { ...process.env, ADMIN_PREVIEW_ORIGIN: adminOrigin, LANDING_API_URL: `${adminOrigin}/api/landing-page` }, stdio: 'ignore' });
  for (const url of [`${adminOrigin}/admin/login`, `${frontendOrigin}/admin-preview/tuks`]) {
    for (let attempt = 0; attempt < 90; attempt++) {
      try { if ((await fetch(url)).ok) break; } catch {}
      if (attempt === 89) throw new Error(`Server unavailable: ${url}`);
      await new Promise(resolve => setTimeout(resolve, 500));
    }
  }
  browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  page.setDefaultTimeout(15000);
  const errors = [];
  page.on('pageerror', error => { errors.push(error.message); console.error('PAGE ERROR', error.message); });
  await page.route('https://tile.openstreetmap.org/**', route => route.abort());
  await page.goto(`${adminOrigin}/admin/login`);
  await page.locator('input[type="email"]').fill('preview@example.test');
  await page.locator('input[type="password"]').fill('preview-test-password');
  await page.locator('button[type="submit"]').click();
  await expect(page).not.toHaveURL(/login/);
  await page.goto(`${adminOrigin}/admin/tuks`);
  const saved = page.frameLocator('iframe').last();
  await expect(saved.getByText('TUK Tersimpan', { exact: true })).toBeVisible();
  await expect(saved.getByText('Belum disimpan', { exact: true })).toHaveCount(0);
  await page.getByRole('button', { name: 'Perbarui preview', exact: true }).click();
  await expect(saved.getByText('TUK Tersimpan', { exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Edit', exact: true }).click();
  const modal = page.getByRole('dialog').filter({ has: page.locator('input[id$=".name"]') });
  await expect(modal.locator('input[id$=".name"]')).toBeVisible();
  await modal.locator('input[id$=".name"]').fill('TUK Draft Browser');
  await modal.getByLabel('Alamat', { exact: true }).fill('Alamat draft browser');
  await modal.getByLabel('Latitude', { exact: true }).fill('-7.2');
  await modal.getByLabel('Longitude', { exact: true }).fill('112.7');
  await modal.getByRole('button', { name: 'Perbarui preview', exact: true }).click();
  const draft = modal.frameLocator('iframe');
  await expect(draft.getByText('TUK Draft Browser', { exact: true })).toBeVisible();
  await expect(draft.getByText('Belum disimpan', { exact: true })).toBeVisible();
  await expect(saved.getByText('TUK Draft Browser', { exact: true })).toBeVisible();
  assert.equal(php("echo App\\Models\\Tuk::sole()->name;"), 'TUK Tersimpan');
  await modal.getByRole('button', { name: /Batal|Cancel/, exact: true }).click();
  await expect(modal.locator('input[id$=".name"]')).toBeHidden();
  await expect(saved.getByText('TUK Tersimpan', { exact: true })).toBeVisible();
  await expect(saved.getByText('Belum disimpan', { exact: true })).toHaveCount(0);
  await page.getByRole('button', { name: 'Muat ulang preview', exact: true }).click();
  await expect(saved.getByText('TUK Tersimpan', { exact: true })).toBeVisible();
  assert.equal(php("echo App\\Models\\Tuk::sole()->name;"), 'TUK Tersimpan');
  await page.goto(`${adminOrigin}/admin/processes/${processId}/edit`);
  const processPreview = page.frameLocator('iframe').last();
  await expect(processPreview.getByRole('heading', { name: 'Proses Browser', exact: true })).toBeVisible();
  await page.getByRole('row').filter({ hasText: 'Tahap Browser' }).getByRole('button', { name: 'Edit', exact: true }).click();
  const activityModal = page.getByRole('dialog').filter({ has: page.locator('input[id$=".title"]') });
  await activityModal.locator('input[id$=".title"]').fill('Tahap Draft Browser');
  await activityModal.locator('.CodeMirror').click();
  await page.keyboard.press('ControlOrMeta+A');
  await page.keyboard.insertText('Narasi **draft** dari Filament.\n\n- Siapkan dokumen.\n- Ikuti petunjuk asesor.');
  await activityModal.getByRole('button', { name: 'Perbarui preview', exact: true }).click();
  const activityPreview = activityModal.frameLocator('iframe');
  await expect(activityPreview.getByText('Belum disimpan', { exact: true })).toBeVisible();
  await expect(activityPreview.getByText('Tahap Draft Browser', { exact: true }).first()).toBeVisible();
  await expect(activityPreview.getByText('Siapkan dokumen.', { exact: true })).toBeVisible();
  await expect(activityPreview.getByRole('region').locator('strong')).toHaveText('draft');
  await expect(activityPreview.getByRole('region').getByRole('button')).toHaveCount(0);
  await expect(processPreview.getByText('Belum disimpan', { exact: true })).toBeVisible();
  assert.equal(php("echo App\\Models\\ProcessStage::query()->where('title','Tahap Browser')->count();"), '1');
  await activityModal.getByRole('button', { name: 'Batal', exact: true }).click();
  await expect(processPreview.getByText('Belum disimpan', { exact: true })).toHaveCount(0);
  console.log('PASS process relation event bridge: draft modal to parent and iframe, cancel restore, no writes');
  assert.deepEqual(errors, [], 'No Filament/Alpine/iframe JavaScript errors');
  console.log('PASS real Filament preview: saved ready, draft modal update, shared footer update, cancel restore, reload, no writes, no JS errors');
} finally {
  await browser?.close();
  adminServer?.kill();
  frontendServer?.kill();
  await rm(temporary, { recursive: true, force: true });
}

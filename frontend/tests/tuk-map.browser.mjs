import assert from "node:assert/strict";
import { createServer } from "node:http";
import { readFile } from "node:fs/promises";
import { spawn } from "node:child_process";
import { chromium, expect } from "@playwright/test";

const template = { url: "https://example.com", logo_url: "/figma/profile-company.png", code: "", city: "", province: "", address: "", maps_url: "https://www.google.com/maps?q=Indonesia", latitude: null, longitude: null };
const fixtures = [
  { id: "1", name: "TUK Sama", city: "Surabaya", province: "Jawa Timur", address: "Alamat satu", latitude: -7.2574719, longitude: 112.7520883 },
  { id: "2", name: "TUK Sama", city: "Surabaya", province: "Jawa Timur", address: "Alamat dua", latitude: -7.2574719, longitude: 112.7520883 },
  { id: "3", name: "TUK Dekat", city: "Surabaya", province: "Jawa Timur", address: "Alamat tiga", latitude: -7.26, longitude: 112.75 },
  { id: "4", name: "TUK Sama", city: "Jayapura", province: "Papua", address: "Alamat Papua", latitude: -2.53, longitude: 140.72 },
  { id: "5", name: "TUK Aceh", province: "Aceh", address: "Alamat Aceh", latitude: 5.55, longitude: 95.32 },
  { id: "6", name: "TUK Tanpa Lokasi", address: "Alamat tanpa koordinat", logo_url: "" },
  { id: "7", name: "TUK Bandung", province: "Jawa Barat", address: "Alamat Bandung", latitude: -6.9, longitude: 107.6 },
].map(tuk => ({ ...template, ...tuk }));
let mode = "normal";
let tuks = fixtures;
const api = createServer((req, res) => {
  if (mode === "failed" && req.url === "/api/content-directory") { res.writeHead(503).end(); return; }
  res.setHeader("Content-Type", "application/json");
  res.end(JSON.stringify({ data: req.url === "/api/content-directory" ? { tuks: mode === "empty" ? [] : tuks, assessors: [], schemes: [], gallery: [] } : {} }));
});
await new Promise(resolve => api.listen(18081, "127.0.0.1", resolve));
const server = spawn(process.execPath, ["node_modules/next/dist/bin/next", "start", "--port", "3101"], {
  cwd: new URL("..", import.meta.url),
  env: { ...process.env, CONTENT_API_URL: "http://127.0.0.1:18081/api/content-directory", LANDING_API_URL: "http://127.0.0.1:18081/api/landing-page", INFORMATION_API_URL: "http://127.0.0.1:18081/api/information-page", PROCESSES_API_URL: "http://127.0.0.1:18081/api/processes" }, stdio: "ignore",
});
let browser;
try {
  for (let attempt = 0; attempt < 60; attempt++) {
    try { if ((await fetch("http://127.0.0.1:3101/mitra")).ok) break; } catch {}
    if (attempt === 59) throw new Error("Next server failed to start; run npm run build first.");
    await new Promise(resolve => setTimeout(resolve, 500));
  }
  browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(10000);
  const errors = [];
  page.on("pageerror", error => errors.push(error.message));
  await page.addInitScript(() => {
    const NativeObserver = window.ResizeObserver;
    window.ResizeObserver = class extends NativeObserver {
      constructor(callback) { super(callback); this.callback = callback; }
      observe(element, options) { this.map = element.classList.contains('leaflet-container'); super.observe(element, options); }
      disconnect() {
        super.disconnect();
        if (this.map) setTimeout(() => this.callback([], this), 0);
      }
    };
  });
  let tileFailed = false;
  await page.route("https://tile.openstreetmap.org/**", route => tileFailed ? route.abort() : route.fulfill({ contentType: "image/png", body: Buffer.from("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=", "base64") }));
  let navigation = 0;
  const open = async path => {
    await page.goto(`http://127.0.0.1:3101${path.split("#")[0]}?browser-test=${++navigation}`);
    const directory = path.startsWith("/mitra") ? page.getByRole("region", { name: "Daftar semua mitra", exact: true }) : page.locator("#tuk");
    if (path.startsWith("/mitra")) await expect(directory.locator(".leaflet-container")).toHaveCount(0);
    else await expect(directory.locator(".leaflet-container")).toBeVisible();
    return directory;
  };
  const resultRows = directory => directory.locator("article, tbody tr");
  const selection = directory => directory.getByRole("button", { name: "Tampilkan semua" });
  await open('/informasi#tuk');
  await page.locator('.site-footer').getByRole('link', { name: 'Mitra Kami', exact: true }).click();
  await expect(page).toHaveURL(/\/mitra/);
  await page.waitForTimeout(100);
  assert.deepEqual(errors, [], 'A queued map resize callback after navigation must not read a detached ref');
  console.log('PASS map cleanup with a queued resize callback');
  for (const path of ["/informasi#tuk"]) {
    for (const width of [320, 390, 768, 1440, 1920]) {
      await page.setViewportSize({ width, height: 1000 });
      const directory = await open(path);
      await expect(selection(directory)).toBeDisabled();
      await expect(directory).not.toContainText("TUK belum memiliki lokasi peta");
      await expect(directory).not.toContainText("TUK dalam pilihan peta");
      const markers = directory.locator(".leaflet-marker-icon");
      await expect(markers.first()).toBeVisible();
      const count = await markers.evaluateAll(elements => elements.reduce((sum, element) => sum + (/^\d+$/.test(element.textContent) ? Number(element.textContent) : 1), 0));
      assert.equal(count, 6, "Cluster counts cover every located TUK");
      if (width === 1440 && process.env.TUK_SCREENSHOTS) {
        await directory.scrollIntoViewIfNeeded();
        await page.screenshot({ path: `${process.env.TUK_SCREENSHOTS}/${path.startsWith("/mitra") ? "mitra" : "informasi"}.png`, fullPage: true });
      }
      const search = directory.locator('input[type="search"]');
      await search.fill("Sama");
      await expect(selection(directory)).toBeDisabled();
      await directory.getByRole("button", { name: "2 TUK dalam kelompok", exact: false }).click();
      await expect(selection(directory)).toBeEnabled();
      await expect(resultRows(directory)).toHaveCount(2);
      await expect.poll(() => directory.locator('table').evaluate(element => Math.round(element.getBoundingClientRect().top))).toBeLessThanOrEqual(125);
      await expect(directory).toContainText("Alamat satu");
      await expect(directory).toContainText("Alamat dua");
      await expect(directory).not.toContainText("Alamat Papua");
      await directory.getByRole("button", { name: "Zoom in", exact: true }).click();
      await expect(resultRows(directory)).toHaveCount(2);
      await directory.locator(".leaflet-container").focus();
      await page.keyboard.press("ArrowRight");
      await expect(resultRows(directory)).toHaveCount(2);
      await directory.getByRole("button", { name: "Kembali ke Indonesia" }).click();
      await expect(resultRows(directory)).toHaveCount(2);
      await directory.getByRole("button", { name: "Tampilkan semua" }).click();
      await expect(resultRows(directory)).toHaveCount(3);
      await search.fill("Papua");
      await expect(markers).toHaveCount(1);
      await markers.first().focus();
      await page.keyboard.press("Enter");
      await expect(resultRows(directory)).toHaveCount(1);
      await search.fill("");
      await expect(selection(directory)).toBeDisabled();
      await directory.getByRole("combobox", { name: "Provinsi", exact: true }).selectOption("Jawa Timur");
      await expect(selection(directory)).toBeDisabled();
      const cluster = directory.getByRole("button", { name: "3 TUK dalam kelompok", exact: false });
      await cluster.focus(); await page.keyboard.press("Space");
      await expect(selection(directory)).toBeEnabled();
      await expect(resultRows(directory)).toHaveCount(3);
      await directory.getByRole("combobox", { name: "Provinsi", exact: true }).selectOption("");
      await expect(selection(directory)).toBeDisabled();
      await search.fill("Tanpa Lokasi");
      await expect(markers).toHaveCount(0);
      await expect(resultRows(directory)).toContainText("Alamat tanpa koordinat");
      await expect(directory).toContainText("Belum ada lokasi peta");
      await search.fill("tidak-ada");
      await expect(directory).toContainText(path === "/mitra" ? "Mitra tidak ditemukan" : "Tidak ada tuk");
      assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `No overflow at ${path} ${width}`);
      console.log(`PASS ${path} ${width}px: cluster, IDs, keyboard, zoom, pan, reset, filters, missing location, empty search`);
    }
    const directory = await open(path);
    await expect(resultRows(directory)).toHaveCount(5);
    assert.deepEqual(await directory.getByRole("combobox", { name: "Per halaman", exact: true }).locator('option').evaluateAll(options => options.map(option => Number(option.value))), [1, 3, 5, 0]);
    await directory.getByRole("combobox", { name: "Per halaman", exact: true }).selectOption("1");
    await expect(resultRows(directory)).toHaveCount(1);
    await directory.locator('nav[aria-label*="Halaman"]').getByRole("button").last().click();
    await expect.poll(() => directory.locator('table').evaluate(element => Math.round(element.getBoundingClientRect().top))).toBeLessThanOrEqual(125);
    await directory.locator('input[type="search"]').fill("Sama");
    await expect(directory.locator('button[aria-current="page"]')).toHaveText("1");
    if (path === "/mitra") {
      await expect(directory.getByRole("link", { name: "Website TUK Sama" })).toHaveAttribute("href", "https://example.com/");
      await expect(directory.getByRole("link", { name: "Google Maps TUK Sama" })).toHaveAttribute("target", "_blank");
      await expect(directory.locator("article img")).toBeVisible();
    } else {
      await directory.getByRole("button", { name: "Urutkan berdasarkan Nama TUK" }).click();
      await expect(directory.locator('th[aria-sort="descending"]')).toContainText("Nama");
      await expect(directory.locator('tbody a')).toHaveAttribute("href", "https://www.google.com/maps?q=Indonesia");
    }
    for (const testMode of ["empty", "failed"]) {
      mode = testMode;
      const directory = await open(path);
      await expect(directory.locator(".leaflet-marker-icon")).toHaveCount(0);
      if (mode === "failed") await expect(directory.getByRole("alert")).toContainText("Data TUK belum dapat dimuat");
    }
    mode = "normal"; tileFailed = true;
    const failedMap = await open(path);
    await expect(failedMap).toContainText("Latar peta tidak dapat dimuat");
    await failedMap.locator('input[type="search"]').fill("Tanpa Lokasi");
    await expect(resultRows(failedMap)).toContainText("Alamat tanpa koordinat");
    tileFailed = false;
    console.log(`PASS ${path}: pagination, links, API empty/failure, tile failure`);
  }
  for (const width of [320, 390, 768, 1440, 1920]) {
    await page.setViewportSize({ width, height: 1000 });
    const directory = await open("/mitra");
    await expect(page.getByText("Dipercaya oleh berbagai perusahaan dan institusi pendidikan", { exact: true })).toBeVisible();
    await expect(directory.locator("ul img")).toHaveCount(4);
    await expect(directory).not.toContainText("TUK Tanpa Lokasi");
    await expect(directory.locator("article, table, select, input")).toHaveCount(0);
    await expect(directory).not.toContainText("Alamat satu");
    await expect(directory.getByRole("link", { name: "Kunjungi website TUK Sama (tab baru)", exact: true })).toHaveAttribute("href", "https://example.com/");
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `No Mitra overflow at ${width}`);
    console.log(`PASS /mitra ${width}px: logo grid, website links, no directory or map`);
  }
  for (const testMode of ["empty", "failed"]) {
    mode = testMode;
    const directory = await open("/mitra");
    if (mode === "failed") await expect(directory.getByRole("alert")).toContainText("Data mitra belum dapat dimuat");
    else await expect(directory).toContainText("Data mitra belum tersedia");
  }
  mode = "normal";
  if (process.env.TUK_BROWSER_FIXTURE) {
    tuks = JSON.parse(await readFile(process.env.TUK_BROWSER_FIXTURE, "utf8")).data.tuks;
    for (const path of ["/informasi#tuk"]) {
      const directory = await open(path);
      await directory.locator('input[type="search"]').fill("Peta Uji");
      await expect(directory.locator(".leaflet-marker-icon")).toHaveCount(1);
      await directory.locator(".leaflet-marker-icon").click();
      await expect(resultRows(directory)).toHaveCount(1);
      console.log(`PASS ${path}: coordinates saved through Filament in isolated database`);
    }
  }
  assert.deepEqual(errors, [], "No JavaScript errors");
} finally {
  await browser?.close();
  server.kill();
  await new Promise(resolve => api.close(resolve));
}

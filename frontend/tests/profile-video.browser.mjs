import assert from "node:assert/strict";
import { spawn, spawnSync } from "node:child_process";
import { mkdtemp, rm, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { chromium, expect } from "@playwright/test";

const frontend = new URL("..", import.meta.url);
const backend = new URL("../../backend/", import.meta.url);
const apiUrl = "http://127.0.0.1:18082";
const siteUrl = "http://127.0.0.1:3103";
const temporary = await mkdtemp(join(tmpdir(), "profile-video-"));
const database = join(temporary, "database.sqlite");
await writeFile(database, "");
const backendEnv = { ...process.env, APP_ENV: "testing", APP_URL: apiUrl, DB_CONNECTION: "sqlite", DB_DATABASE: database, DB_URL: "", CACHE_STORE: "array", SESSION_DRIVER: "array", LOG_CHANNEL: "stderr", TELESCOPE_ENABLED: "false", NIGHTWATCH_ENABLED: "false" };
let api;
let server;
let browser;

async function waitFor(url) {
  for (let attempt = 0; attempt < 60; attempt++) {
    try { if ((await fetch(url)).ok) return; } catch {}
    await new Promise(resolve => setTimeout(resolve, 500));
  }
  throw new Error(`Server did not become ready: ${url}; run npm run build first.`);
}

try {
  const migration = spawnSync("php", ["artisan", "migrate", "--force", "--no-interaction"], { cwd: backend, env: backendEnv, encoding: "utf8" });
  assert.equal(migration.status, 0, migration.stderr + migration.stdout);
  api = spawn("php", ["-S", "127.0.0.1:18082", "../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"], { cwd: new URL("public/", backend), env: backendEnv, stdio: "ignore" });
  await waitFor(`${apiUrl}/api/landing-page`);
  server = spawn(process.execPath, ["node_modules/next/dist/bin/next", "start", "--port", "3103"], {
    cwd: frontend,
    env: { ...process.env, LANDING_API_URL: `${apiUrl}/api/landing-page`, CONTENT_API_URL: `${apiUrl}/api/content-directory` },
    stdio: "ignore",
  });
  await waitFor(siteUrl);
  browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(15000);
  const errors = [];
  page.on("pageerror", error => errors.push(error.message));

  for (const width of [390, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto(siteUrl);
    const video = page.locator('video[aria-label="Video profil LSP TIK Global"]');
    await expect(video).toBeVisible();
    await expect(video).toHaveAttribute("src", `${apiUrl}/media/profile-video`);
    await expect(video).toHaveAttribute("preload", "metadata");
    await expect(video).toHaveAttribute("poster", "/profile-video-poster.jpg");
    const play = page.getByRole("button", { name: "Putar video profil", exact: true });
    await expect(play).toBeVisible();
    await play.focus();
    await page.keyboard.press("Enter");
    await expect(play).toHaveCount(0);
    await expect.poll(() => video.evaluate(element => element.paused)).toBe(false);
    await video.evaluate(element => element.pause());
    assert.ok(await video.evaluate(element => element.controls && element.playsInline && !element.autoplay && !element.loop));
    await expect.poll(() => video.evaluate(element => element.duration)).toBeGreaterThan(130);
    const duration = await video.evaluate(element => element.duration);
    assert.ok(duration < 134, `Expected original 132-second video, got ${duration}`);
    await video.click();
    await video.evaluate(element => element.play());
    await expect.poll(() => video.evaluate(element => element.currentTime)).toBeGreaterThan(0.1);
    await video.evaluate(element => element.pause());
    assert.ok(await video.evaluate(element => element.paused));
    await video.evaluate(element => { element.currentTime = 60; });
    await expect.poll(() => video.evaluate(element => element.seeking)).toBe(false);
    assert.ok(await video.evaluate(element => Math.abs(element.currentTime - 60) < 0.5 && !element.error));
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    console.log(`PASS ${width}px: controls, inline playback, original metadata (${duration}s), play, pause, seek, no overflow`);
  }

  await page.route(`${apiUrl}/media/profile-video`, route => route.abort());
  await page.goto(siteUrl);
  const alert = page.getByRole("alert").filter({ hasText: "Video tidak dapat diputar" });
  await expect(alert).toBeVisible();
  await expect(alert.getByRole("link", { name: "Buka video profil" })).toHaveAttribute("href", `${apiUrl}/media/profile-video`);
  assert.deepEqual(errors, [], "No JavaScript errors");
  console.log("PASS failed media: accessible alert and direct video link");
} finally {
  await browser?.close();
  const processes = [server, api].filter(Boolean);
  await Promise.all(processes.filter(child => child.exitCode === null).map(child => new Promise(resolve => { child.once("exit", resolve); child.kill(); })));
  await rm(temporary, { recursive: true, force: true });
}

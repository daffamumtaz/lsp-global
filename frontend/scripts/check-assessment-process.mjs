import assert from 'node:assert/strict';
import { mkdir, writeFile, rm } from 'node:fs/promises';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const listing = (await (await fetch('http://localhost:8000/api/processes')).json()).data.sort((a,b) => a.order-b.order || a.id-b.id);
assert.ok(listing.length, 'Seed an active process before checking the public diagram');
const assessment = (await (await fetch(`http://localhost:8000/api/processes/${listing[0].slug}`)).json()).data;
const count = assessment.stages.flatMap(stage => stage.steps).length;
const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_EXECUTABLE, args: ['--no-sandbox'] });
try {
  for (const width of [375, 768, 1440, 720]) {
    const page = await browser.newPage({ viewport: { width, height: 1000 }, reducedMotion: 'reduce' });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://localhost:3000/informasi');
    const diagram = page.getByTestId('assessment-diagram');
    await diagram.waitFor({ timeout: 30000 });
    await page.locator('[data-activity-id]').first().waitFor();
    assert.equal(await page.locator('[data-activity-id]').count(), count);
    for (const step of assessment.stages.flatMap(stage => stage.steps)) {
      const activity = page.locator(`[data-activity-id="${step.id}"]`);
      assert.equal(await activity.innerText(), step.title);
      assert.deepEqual(await activity.evaluate(element => {
        const style = getComputedStyle(element);
        return [style.boxShadow, style.borderLeftWidth, style.borderRightWidth];
      }), ['none', '1px', '1px']);
    }
    const card = page.locator('[data-activity-id]').first();
    await card.focus();
    await page.keyboard.press('Enter');
    await page.getByRole('region', { name: 'Detail aktivitas' }).waitFor();
    assert.equal(await page.evaluate(() => document.activeElement?.getAttribute('aria-label')), 'Detail aktivitas');
    await page.keyboard.press('Escape');
    assert.equal(await page.getByRole('region', { name: 'Detail aktivitas' }).count(), 0);
    await page.keyboard.press('Space');
    await page.getByRole('region', { name: 'Detail aktivitas' }).waitFor();
    await page.getByRole('button', { name: 'Tutup detail' }).click();
    const actor = assessment.actors[0]?.id ?? 'none';
    await page.getByLabel('Filter pelaksana', { exact: true }).selectOption(String(actor));
    assert.equal(await page.locator('[data-subdued="true"]').count(), assessment.stages.flatMap(s => s.steps).filter(s => String(s.actorId ?? 'none') !== String(actor)).length);
    assert.equal(await page.locator('[data-activity-id]').count(), count);
    await page.getByLabel('Filter pelaksana', { exact: true }).selectOption('');
    await page.getByLabel('Filter tahap', { exact: true }).selectOption({ index: 1 });
    assert.equal(await page.locator('[data-subdued="true"]').count(), assessment.stages.slice(1).flatMap(s => s.steps).length);
    await page.getByLabel('Filter tahap', { exact: true }).selectOption('');
    const viewport = page.locator('[data-testid="assessment-diagram"] .react-flow__viewport');
    const before = await viewport.getAttribute('style');
    await page.getByRole('button', { name: 'Perbesar alur', exact: true }).click();
    await page.waitForTimeout(350);
    assert.notEqual(await viewport.getAttribute('style'), before);
    await page.getByRole('button', { name: 'Perkecil alur', exact: true }).click();
    await page.getByRole('button', { name: 'Reset tampilan', exact: true }).click();
    await page.getByRole('button', { name: 'Lihat seluruh alur', exact: true }).click();
    await page.getByRole('button', { name: 'Layar penuh', exact: true }).click();
    assert.ok(await page.evaluate(() => !!document.fullscreenElement));
    await page.getByRole('button', { name: 'Keluar layar penuh', exact: true }).click();
    assert.equal(await page.evaluate(() => !!document.fullscreenElement), false);
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    assert.equal(await page.getByTestId('assessment-list').count(), 0);
    assert.equal(await page.locator('#alur-asesmen ol, #alur-asesmen details').count(), 0);
    assert.deepEqual(errors, []);
    await diagram.scrollIntoViewIfNeeded();
    if (width === 375 || width === 1440) {
      await mkdir(new URL('../artifacts/', import.meta.url), { recursive: true });
      await page.getByRole('button', {name:'Reset tampilan',exact:true}).click();
      await page.waitForTimeout(250);
      await diagram.screenshot({ path: `artifacts/assessment-process-${width}.png`, style:'body > header, .skip-link { visibility: hidden !important; }' });
    }
    await page.close();
  }
  const fixtureDir = new URL('../src/app/assessment-check-qa/', import.meta.url);
  await mkdir(fixtureDir);
  try {
    const step = id => ({ id, order: id, title: `Activity ${id}`, description: 'Detail QA', isActive: true, actorId: null, type: 'normal', documentReference: null, informationUrl: null });
    const fixture = { id: 1, order: 1, title: 'QA', slug: 'qa', description: null, isActive: true, actors: [], stages: [{ id: 1, order: 1, title: 'QA stage', description: null, isActive: true, steps: [step(1), step(2)] }], connections: [{ id: 1, order: 1, sourceStepId: 1, targetStepId: 2, type: 'normal', label: null }] };
    await writeFile(new URL('page.tsx', fixtureDir), `'use client';
import { useState } from 'react';
import { AssessmentProcessView } from '@/components/information/assessment-process-view';
import type { AssessmentProcess } from '@/lib/assessment-process';
const initial: AssessmentProcess = ${JSON.stringify(fixture)};
export default function Check() { const [process, setProcess] = useState(initial); return <>
<button onClick={() => setProcess(p => ({...p, title:'Updated', stages:p.stages.map(s => ({...s,steps:s.steps.map(st => ({...st,title:'Updated '+st.id}))}))}))}>Update process</button>
<button onClick={() => setProcess(p => ({...p,stages:p.stages.map(s => ({...s,title:'Tahap panjang '.repeat(18),steps:s.steps.map(st => ({...st,title:'Aktivitas panjang '.repeat(14)}))})),connections:[{...p.connections[0],label:'Persyaratan panjang '.repeat(6)}]}))}>Long content</button>
<button onClick={() => setProcess({...initial,stages:[],connections:[]})}>Empty stages</button>
<AssessmentProcessView process={process} unavailable={false}/></>; }
`);
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const fixtureErrors = [];
    page.on('pageerror', e => fixtureErrors.push(e.message));
    for (let attempt = 0; attempt < 5; attempt++) {
      await page.goto('http://localhost:3000/assessment-check-qa');
      if (await page.getByRole('button', {name:'Update process'}).count()) break;
      await page.waitForTimeout(1000);
    }
    await page.getByRole('button', {name:'Update process'}).waitFor();
    await page.locator('[data-activity-id]').first().waitFor();
    assert.deepEqual(fixtureErrors, []);
    await page.getByRole('button', { name: 'Perbesar alur' }).click();
    await page.waitForTimeout(350);
    const box = await page.locator('.react-flow__pane').boundingBox();
    const viewport = page.locator('.react-flow__viewport');
    const original = await viewport.getAttribute('style');
    await page.mouse.move(box.x + box.width - 30, box.y + 200);
    await page.mouse.down(); await page.mouse.move(box.x + box.width - 230, box.y + 300, { steps: 10 }); await page.mouse.up();
    const before = await viewport.getAttribute('style');
    assert.notEqual(before, original, 'mouse pan');
    await page.getByRole('button', { name: 'Update process' }).click();
    await page.locator('[data-activity-id="1"] strong').getByText('Updated 1').waitFor();
    await page.waitForTimeout(300);
    assert.equal(await viewport.getAttribute('style'), before, 'data update preserves viewport');
    await page.getByRole('button', { name: 'Long content' }).click();
    await page.locator('.react-flow__node-connectionLabel').waitFor();
    const intersects = (a,b) => a.x < b.x+b.width && a.x+a.width>b.x && a.y<b.y+b.height && a.y+a.height>b.y;
    const label = await page.locator('.react-flow__node-connectionLabel').boundingBox();
    for (const card of await page.locator('[data-activity-id]').all()) assert.equal(intersects(label, await card.boundingBox()), false, 'long label clears cards');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    await page.getByRole('button', { name: 'Empty stages' }).click();
    await page.getByText('Belum ada tahap dalam alur ini.').waitFor();
    await page.close();
    const touch = await browser.newPage({ viewport: { width: 375, height: 900 }, hasTouch: true, isMobile: true });
    await touch.goto('http://localhost:3000/assessment-check-qa');
    await touch.locator('[data-activity-id]').first().waitFor();
    await touch.getByTestId('assessment-diagram').scrollIntoViewIfNeeded();
    const pane = await touch.locator('.react-flow__pane').boundingBox();
    const prior = await touch.locator('.react-flow__viewport').getAttribute('style');
    const session = await touch.context().newCDPSession(touch);
    const y = Math.max(130, pane.y + 180);
    await session.send('Input.dispatchTouchEvent',{type:'touchStart',touchPoints:[{x:320,y}]});
    for (let x=300;x>=140;x-=20) await session.send('Input.dispatchTouchEvent',{type:'touchMove',touchPoints:[{x,y}]});
    await session.send('Input.dispatchTouchEvent',{type:'touchEnd',touchPoints:[]});
    assert.notEqual(await touch.locator('.react-flow__viewport').getAttribute('style'),prior,'touch pan');
    await session.send('Emulation.setPageScaleFactor',{pageScaleFactor:2});
    assert.equal(await touch.evaluate(() => visualViewport.scale),2,'native page magnification');
    await touch.close();
  } finally { await rm(fixtureDir,{recursive:true}); }
  console.log('Assessment flow: responsive, keyboard, filters, controls, fullscreen, pan/touch, long content and data updates passed.');
} finally { await browser.close(); }

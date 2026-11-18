import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import ts from 'typescript';

const require = createRequire(import.meta.url);
const source = readFileSync(new URL('../src/components/schemes/scheme-detail-tabs.tsx', import.meta.url), 'utf8')
  .replace('import styles from "./schemes.module.css";', 'const styles = {};');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, jsx: ts.JsxEmit.ReactJSX } }).outputText;
const transpiledModule = { exports: {} };
new Function('require', 'module', 'exports', compiled)(require, transpiledModule, transpiledModule.exports);

test('requirements is the first selected tab and visible panel, with units linked to the second tab', () => {
  const headings = Object.fromEntries(['scheme_units', 'scheme_requirements', 'scheme_basic', 'scheme_administrative'].map(key => [key, { title: key, subtitle: '' }]));
  const html = renderToStaticMarkup(createElement(transpiledModule.exports.SchemeDetailTabs, {
    units: [{ code: 'CODE-1', title: 'Competency unit' }],
    basicRequirements: ['Eligibility requirement'], administrativeRequirements: ['Identity document'], headings,
  }));
  const tabs = [...html.matchAll(/<button[^>]+role="tab"[^>]*>(.*?)<\/button>/g)];
  assert.deepEqual(tabs.map(match => match[1]), ['scheme_requirements', 'scheme_units']);
  assert.match(tabs[0][0], /aria-selected="true"/);
  assert.match(tabs[1][0], /aria-selected="false"/);
  const panels = [...html.matchAll(/<div[^>]+role="tabpanel"[^>]*>/g)];
  assert.doesNotMatch(panels[0][0], /hidden/);
  assert.match(panels[1][0], /hidden/);
  assert.ok(html.indexOf('Eligibility requirement') < html.indexOf('Competency unit'));
});

import assert from 'node:assert/strict';
import { mkdir, writeFile } from 'node:fs/promises';
import ts from 'typescript';

const origin = 'https://lspglobal.id';
const output = new URL('../../backend/resources/data/lspglobal/', import.meta.url);
async function request(url) {
  const response = await fetch(url, { signal: AbortSignal.timeout(30000) });
  if (!response.ok) throw new Error(`${response.status}: ${url}`);
  return response;
}
const html = await (await request(origin)).text();
const asset = html.match(/src="(\/assets\/[^"\s]+\.js)"/)?.[1];
assert.ok(asset, 'Public JavaScript bundle was not found');
const text = await (await request(origin + asset)).text();
const source = ts.createSourceFile('bundle.js', text, ts.ScriptTarget.Latest, true, ts.ScriptKind.JS);
function literal(node) {
  if (ts.isStringLiteral(node) || ts.isNoSubstitutionTemplateLiteral(node)) return node.text;
  if (ts.isNumericLiteral(node)) return Number(node.text);
  if (ts.isArrayLiteralExpression(node)) return node.elements.map(literal);
  if (ts.isObjectLiteralExpression(node)) {
    assert.ok(node.properties.every(ts.isPropertyAssignment), 'Nonliteral data property');
    return Object.fromEntries(node.properties.map(property => [property.name.text, literal(property.initializer)]));
  }
  if (ts.isPrefixUnaryExpression(node)) {
    const value = literal(node.operand);
    if (node.operator === ts.SyntaxKind.ExclamationToken) return !value;
    if (node.operator === ts.SyntaxKind.MinusToken) return -value;
  }
  if (node.kind === ts.SyntaxKind.NullKeyword) return null;
  throw new Error(`Unsupported data expression: ${node.getText(source).slice(0, 80)}`);
}
const data = { source: origin, extracted_at: new Date().toISOString(), bundle: asset };
function visit(node) {
  if (ts.isArrayLiteralExpression(node) && node.elements.length && ts.isObjectLiteralExpression(node.elements[0])) {
    const keys = node.elements[0].properties.map(property => property.name?.text);
    if (keys.includes('namaTuk')) data.tuks = literal(node);
    if (keys.includes('schemaName') && keys.includes('content')) data.schemes = literal(node);
    if (keys.includes('met') && keys.includes('nama')) data.assessors = literal(node);
  }
  ts.forEachChild(node, visit);
}
visit(source);
for (const key of ['tuks', 'schemes', 'assessors']) assert.ok(data[key]?.length, `${key} data missing`);
data.administrative_requirements = ['Pas foto 3x4 (Background Merah)', 'Copy KTP'];
assert.ok(data.administrative_requirements.every(value => text.includes(value)), 'Published administrative requirements changed');
await mkdir(new URL('schemes/', output), { recursive: true });
for (const scheme of data.schemes) {
  assert.match(scheme.id, /^[a-f0-9-]{36}$/);
  assert.ok(scheme.thumbnail.startsWith('/certificate/'));
  const response = await request(origin + encodeURI(scheme.thumbnail));
  assert.ok(response.headers.get('content-type')?.startsWith('image/'), 'Source thumbnail is not an image');
  const bytes = Buffer.from(await response.arrayBuffer());
  assert.ok(bytes.length && bytes.length <= 5 * 1024 * 1024, 'Source thumbnail exceeds upload limit');
  await writeFile(new URL(`schemes/${scheme.id}.webp`, output), bytes);
}
await writeFile(new URL('snapshot.json', output), JSON.stringify(data, null, 2) + '\n');
console.log(`Extracted ${data.tuks.length} TUK, ${data.schemes.length} schemes with images, ${data.assessors.length} assessors`);

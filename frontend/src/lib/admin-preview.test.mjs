import test from 'node:test';
import assert from 'node:assert/strict';
import { adminPreviewOrigin, parsePreviewUpdate, trustedPreviewEvent } from './admin-preview.ts';

const tuk = { id: '1', name: 'TUK', url: '', logo_url: '', code: '', city: '', province: '', address: '', maps_url: '', latitude: null, longitude: null, is_active: false };
const message = (data = [tuk]) => ({ type: 'admin-preview:update', kind: 'tuks', revision: 1, draft: true, data });

test('preview trusts only the configured origin and parent window', () => {
  const parent = {};
  const origin = adminPreviewOrigin('http://localhost:8000/admin/');
  assert.equal(origin, 'http://localhost:8000');
  assert.equal(trustedPreviewEvent({ origin, source: parent }, parent, origin), true);
  assert.equal(trustedPreviewEvent({ origin: 'https://other.example', source: parent }, parent, origin), false);
  assert.equal(trustedPreviewEvent({ origin, source: {} }, parent, origin), false);
  assert.throws(() => adminPreviewOrigin('javascript:alert(1)'));
});

test('preview validates transport and rejects stale revisions', () => {
  assert.equal(parsePreviewUpdate(message(), 'tuks', -1).data[0].is_active, false);
  assert.equal(parsePreviewUpdate(message(), 'tuks', 1), null);
  for (const patch of [{ revision: NaN }, { revision: Infinity }, { revision: -1 }, { revision: 1.5 }, { revision: Number.MAX_SAFE_INTEGER + 1 }, { kind: 'organization' }, { type: 'other' }, { draft: 'yes' }]) {
    assert.throws(() => parsePreviewUpdate({ ...message(), ...patch }, 'tuks', -1));
  }
});

test('TUK validation accepts paired null coordinates, preserves inactive rows, and rejects malformed coordinates and IDs', () => {
  assert.equal(parsePreviewUpdate(message([{ ...tuk, latitude: -6, longitude: 107 }]), 'tuks', -1).data.length, 1);
  for (const patch of [{ latitude: 1 }, { latitude: '1', longitude: 2 }, { latitude: 91, longitude: 2 }, { latitude: 1, longitude: 181 }, { latitude: NaN, longitude: 2 }, { is_active: 0 }, { name: null }]) {
    assert.throws(() => parsePreviewUpdate(message([{ ...tuk, ...patch }]), 'tuks', -1));
  }
  assert.throws(() => parsePreviewUpdate(message([tuk, tuk]), 'tuks', -1));
});

test('organization validation retains inactive nodes and rejects invalid hierarchy', () => {
  const node = { id: 1, parent_id: null, title: 'Jabatan', person_name: 'Nama', credentials: null, order: 1, is_active: false };
  const update = { ...message(), kind: 'organization', data: [node] };
  assert.equal(parsePreviewUpdate(update, 'organization', -1).data[0].is_active, false);
  for (const patch of [{ id: -1 }, { parent_id: 1 }, { parent_id: 7 }, { title: null }]) assert.throws(() => parsePreviewUpdate({ ...update, data: [{ ...node, ...patch }] }, 'organization', -1));
});

test('process validation reuses the public process contract', () => {
  const process = { id: 1, order: 1, title: 'Proses', slug: 'proses', description: null, isActive: false, actors: [], stages: [], connections: [] };
  assert.equal(parsePreviewUpdate({ ...message(), kind: 'process', data: process }, 'process', -1).data.isActive, false);
  assert.throws(() => parsePreviewUpdate({ ...message(), kind: 'process', data: { ...process, id: -1 } }, 'process', -1));
});

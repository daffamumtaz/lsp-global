import test from 'node:test';
import assert from 'node:assert/strict';
import { buildOrganizationTree } from './organization.ts';

const node = (id, parent_id = null, order = 1, is_active = true) => ({ id, parent_id, order, is_active, title: `Jabatan ${id}`, person_name: `Nama ${id}`, credentials: null });

test('builds hierarchy from parents, orders siblings, and hides inactive branches', () => {
  const roots = buildOrganizationTree([node(1), node(2,1,2), node(3,1,1), node(4,3), node(5,1,3,false), node(6,5)]);
  assert.deepEqual(roots[0].children.map(n=>n.id), [3,2]);
  assert.equal(roots[0].children[0].children[0].id, 4);
});

test('changing parent changes hierarchy without coordinates', () => {
  const roots = buildOrganizationTree([node(1), node(2,1), node(3,2)]);
  assert.equal(roots[0].children.length, 1);
  assert.equal(roots[0].children[0].children[0].id, 3);
  assert.deepEqual(buildOrganizationTree([]), []);
  assert.equal(buildOrganizationTree([node(1),node(2)]).length, 2);
});

test('rejects orphaned nodes, duplicates, cycles and malformed API fields', () => {
  for (const nodes of [[node(1,9)], [node(1),node(1)], [node(1,1)], [node(1,2),node(2,1)], [{...node(1),is_active:'true'}], [{...node(1),credentials:32}]]) {
    assert.throws(()=>buildOrganizationTree(nodes));
  }
});

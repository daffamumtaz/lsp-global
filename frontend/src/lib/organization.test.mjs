import test from "node:test";
import assert from "node:assert/strict";
import { layoutOrganization, NODE_WIDTH, NODE_HEIGHT } from "./organization.ts";

const node = (id, parent_id = null, order = 1) => ({ id, parent_id, order, title: `Jabatan ${id}`, name: `Nama ${id}` });

test("places parents above children, respects sibling order, and prevents overlap", () => {
  const result = layoutOrganization([node(1), node(2, 1, 2), node(3, 1, 1), node(4, 3), node(5, 3, 2)]);
  const byId = new Map(result.nodes.map(item => [item.id, item]));
  assert.ok(byId.get(1).y + NODE_HEIGHT < byId.get(2).y);
  assert.ok(byId.get(3).x < byId.get(2).x);
  assert.equal(result.edges.length, 4);
  for (const a of result.nodes) for (const b of result.nodes) {
    if (a.id < b.id && a.y === b.y) assert.ok(Math.abs(a.x - b.x) >= NODE_WIDTH);
    assert.ok(a.x + NODE_WIDTH <= result.width);
    assert.ok(a.y + NODE_HEIGHT <= result.height);
  }
});

test("handles empty organizations and multiple roots", () => {
  assert.equal(layoutOrganization([]).nodes.length, 0);
  const result = layoutOrganization([node(1), node(2)]);
  assert.equal(result.edges.length, 0);
  assert.ok(result.nodes[1].x >= result.nodes[0].x + NODE_WIDTH);
});

test("rejects orphaned nodes, duplicate IDs and cycles before rendering", () => {
  assert.throws(() => layoutOrganization([node(1, 9)]), /Invalid/);
  assert.throws(() => layoutOrganization([node(1), node(1)]), /Duplicate/);
  assert.throws(() => layoutOrganization([node(1, 1)]), /cycle/);
  assert.throws(() => layoutOrganization([node(1, 2), node(2, 1)]), /cycle/);
});

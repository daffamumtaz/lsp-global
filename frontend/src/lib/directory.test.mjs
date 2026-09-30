import test from "node:test";
import assert from "node:assert/strict";
import { selectDirectory } from "./directory.ts";

const entries = [
  { id: "1", name: "TUK Zeta", province: "Banten", city: "Serang", code: "TUK-10" },
  { id: "2", name: "TUK Alpha", province: "Jawa Timur", city: "Nganjuk", code: "TUK-2" },
  { id: "3", name: "TUK Beta", province: "Banten", city: "Serang", code: "TUK-3" },
];
const options = { query: "", filterKey: "province", filter: "", sortKey: "name", direction: "asc", page: 1, pageSize: 1 };

test("combines trimmed case-insensitive search and province filter before pagination", () => {
  const result = selectDirectory(entries, { ...options, query: " SERANG ", filter: "Banten" });
  assert.equal(result.total, 2);
  assert.equal(result.pageCount, 2);
  assert.equal(result.rows[0].name, "TUK Beta");
  assert.equal(result.start, 1);
  assert.equal(result.end, 1);
  assert.equal(selectDirectory(entries, { ...options, query: "Nganjuk", filter: "Banten" }).total, 0);
});

test("sorts codes numerically, paginates accurately, and does not mutate source data", () => {
  const original = structuredClone(entries);
  assert.deepEqual(selectDirectory(entries, { ...options, sortKey: "code", pageSize: 3 }).rows.map(row => row.code), ["TUK-2", "TUK-3", "TUK-10"]);
  const result = selectDirectory(entries, { ...options, direction: "desc", page: 2, pageSize: 2 });
  assert.deepEqual(result.rows.map(row => row.name), ["TUK Alpha"]);
  assert.equal(result.start, 3);
  assert.equal(result.end, 3);
  assert.deepEqual(entries, original);
});

test("clamps a stale page after filtering and reports empty results without phantom rows", () => {
  const result = selectDirectory(entries, { ...options, filter: "Jawa Timur", page: 3 });
  assert.equal(result.page, 1);
  assert.equal(result.rows[0].name, "TUK Alpha");
  const empty = selectDirectory(entries, { ...options, query: "missing", page: 3 });
  assert.equal(empty.pageCount, 0);
  assert.equal(empty.start, 0);
  assert.equal(empty.end, 0);
  assert.deepEqual(empty.rows, []);
});

import test from "node:test";
import assert from "node:assert/strict";
import { hasCoordinates, tukDirectoryEntry } from "./tuks.ts";
import { selectDirectory } from "./directory.ts";
const tuk = { id: "1", name: "Sama", code: "", city: "Surabaya", province: "Jawa Timur", address: "", maps_url: "", url: "", logo_url: "", latitude: -7.2574719, longitude: 112.7520883 };
const options = { query: "surabaya", filterKey: "province", filter: "Jawa Timur", sortKey: "name", direction: "asc", page: 2, pageSize: 1 };
test("coordinates stay out of text search and selection uses IDs before pagination", () => {
  const rows = [tuk, { ...tuk, id: "2" }, { ...tuk, id: "3", latitude: null, longitude: null }].map(tukDirectoryEntry);
  assert.ok(Object.values(rows[0]).every(value => typeof value === "string"));
  assert.equal(selectDirectory(rows, options).total, 3);
  const selected = selectDirectory(rows, { ...options, selectedIds: ["2"] });
  assert.equal(selected.total, 1);
  assert.equal(selected.page, 1);
  assert.equal(selected.rows[0].id, "2");
  assert.equal(selectDirectory(rows, { ...options, selectedIds: ["1", "2"] }).total, 2);
});
test("missing and invalid coordinates do not create markers; zero is a location", () => {
  assert.ok(hasCoordinates(tuk));
  assert.ok(hasCoordinates({ ...tuk, latitude: 0, longitude: 0 }));
  for (const latitude of [null, undefined, NaN, Infinity, 91, -91, "-7"]) assert.equal(hasCoordinates({ ...tuk, latitude }), false);
  for (const longitude of [null, undefined, 181, -181]) assert.equal(hasCoordinates({ ...tuk, longitude }), false);
});

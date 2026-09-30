import test from "node:test";
import assert from "node:assert/strict";
import { readSchemeState, schemeHref, selectSchemes, selectUnits, formatSchemePrice, registrationHref } from "./schemes.ts";
import { sampleSchemes } from "./scheme-samples.ts";

const defaults = { query: "", methods: [], minLevel: 1, maxLevel: 9, categories: [], sort: "name-asc", page: 1 };
const schemes = [
  { id: "a", slug: "alpha", name: "Alpha", category: "Software", level: 2, methods: ["luring", "jarak-jauh"], price: 1000000 },
  { id: "b", slug: "beta", name: "Beta", category: "Data", level: 5, methods: ["luring"], price: null },
  { id: "c", slug: "gamma", name: "Gamma", category: "Data", level: 3, methods: ["jarak-jauh"], price: 500000 },
  { id: "d", slug: "delta", name: "Delta", category: "Software", level: 3, methods: ["luring"], price: 500000 },
];

test("URL state round-trips, normalizes reversed levels and ignores invalid parameters", () => {
  const state = { ...defaults, query: "Riset & TIK", methods: ["luring", "jarak-jauh"], categories: ["Data", "Software"], minLevel: 2, maxLevel: 5, sort: "price-desc", page: 2 };
  assert.deepEqual(readSchemeState(new URL(schemeHref(state), "https://example.test").searchParams, ["Data", "Software"]), state);
  assert.deepEqual(readSchemeState(new URLSearchParams("metode=invalid&level_min=99&halaman=1.5&urutan=bad")), defaults);
  const reversed = readSchemeState(new URLSearchParams("level_min=5&level_max=2"));
  assert.equal(reversed.minLevel, 2); assert.equal(reversed.maxLevel, 5);
  assert.equal(schemeHref(defaults), "/skema-sertifikasi");
  assert.deepEqual(readSchemeState(new URLSearchParams("kategori=unknown&kategori=Data"), ["Data", "Software"]).categories, ["Data"]);
});

test("categories and methods use OR within groups, AND across groups, inclusive levels and trimmed search", () => {
  const result = selectSchemes(schemes, { ...defaults, categories: ["Data", "Software"], methods: ["jarak-jauh"], minLevel: 2, maxLevel: 3 });
  assert.deepEqual(result.items.map(s => s.id), ["a", "c"]);
  assert.equal(selectSchemes(schemes, { ...defaults, query: " DATA ", methods: ["luring", "jarak-jauh"] }).total, 2);
  assert.equal(selectSchemes(schemes, { ...defaults, categories: ["unknown"] }).total, 0);
});

test("pagination clamps stale pages, empty ranges are zero, sorting ties are stable and unknown prices are last", () => {
  const before = structuredClone(schemes);
  assert.deepEqual(selectSchemes(schemes, { ...defaults, sort: "price-asc" }).items.map(s => s.id), ["d", "c", "a", "b"]);
  assert.deepEqual(selectSchemes(schemes, { ...defaults, sort: "price-desc" }).items.map(s => s.id), ["a", "d", "c", "b"]);
  const page = selectSchemes(schemes, { ...defaults, page: 99 }, 2);
  assert.equal(page.page, 2); assert.equal(page.pageCount, 2); assert.equal(page.start, 3); assert.equal(page.end, 4);
  const empty = selectSchemes(schemes, { ...defaults, query: "absent" });
  assert.equal(empty.start, 0); assert.equal(empty.end, 0); assert.equal(empty.pageCount, 0);
  assert.deepEqual(schemes, before);
});

test("unit searches preserve original numbering and match code or title", () => {
  const units = [{ code: "DEMO-01", title: "Algoritma" }, { code: "DEMO-02", title: "Basis Data" }];
  assert.deepEqual(selectUnits(units, " DATA "), [{ ...units[1], number: 2 }]);
  assert.equal(selectUnits(units, "demo-01")[0].number, 1);
  assert.deepEqual(selectUnits(units, "absent"), []);
});

test("prices and registration destinations handle missing data and unsafe URLs", () => {
  assert.match(formatSchemePrice(1000000), /1\.000\.000/);
  assert.equal(formatSchemePrice(null), "Hubungi kami untuk biaya");
  assert.equal(registrationHref("javascript:alert(1)"), null);
  assert.equal(registrationHref("https://example.test/daftar"), "https://example.test/daftar");
  assert.equal(registrationHref(null), null);
});

test("sample catalogue has distinct records and units, covers multiple pages and missing prices", () => {
  assert.equal(sampleSchemes.length, 9);
  assert.equal(new Set(sampleSchemes.map(scheme => scheme.slug)).size, 9);
  for (const scheme of sampleSchemes) assert.equal(new Set(scheme.units.map(unit => unit.code)).size, scheme.units.length);
  assert.equal(sampleSchemes.find(scheme => scheme.slug === "junior-coder").units.length, 8);
  assert.ok(sampleSchemes.some(scheme => scheme.price === null));
  assert.equal(selectSchemes(sampleSchemes, defaults).pageCount, 2);
  const invalid = readSchemeState(new URLSearchParams("kategori=not-a-real-category"), sampleSchemes.map(scheme => scheme.category));
  assert.equal(selectSchemes(sampleSchemes, invalid).total, 9);
});

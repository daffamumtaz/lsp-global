import test from "node:test";
import assert from "node:assert/strict";
import { normalizePartners, searchPartners } from "./partners.ts";

test("deduplicates repeated partners, preserves distinct names and rejects unsafe links/logos", () => {
  const input = [
    { name: " Jobhun ", url: "https://jobhun.id", logo_url: null },
    { name: "jobhun", url: "https://jobhun.id/", logo_url: "/figma/imgImage20.png" },
    { name: "Partner Lain", url: "javascript:alert(1)", logo_url: "data:image/svg+xml,unsafe" },
    { name: "", url: "https://example.test", logo_url: null },
  ];
  const before = structuredClone(input);
  const result = normalizePartners(input);
  assert.equal(result.length, 2);
  assert.equal(result[0].logo_url, "/figma/imgImage20.png");
  assert.equal(result[1].url, null); assert.equal(result[1].logo_url, null);
  assert.deepEqual(input, before);
});
test("search is trimmed, case insensitive and reports actual results including empty lists", () => {
  const input = normalizePartners([{ name: "Jobhun", url: "https://jobhun.id", logo_url: null }]);
  assert.equal(searchPartners(input, " JOB ").length, 1);
  assert.deepEqual(searchPartners(input, "missing"), []);
  assert.deepEqual(searchPartners([], ""), []);
});

import test from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { defaultInformationContent, informationText, normalizeInformationContent } from "./information-copy.ts";

test("saved information copy merges with defaults and preserves empty descriptions", () => {
  const content = normalizeInformationContent({ hero: { title: "Baru", description: "" }, flow: { hint: null } });
  assert.equal(content.hero.title, "Baru");
  assert.equal(content.hero.description, "");
  assert.equal(content.flow.hint, defaultInformationContent.flow.hint);
  assert.equal(content.tuk.name, "Nama TUK");
  assert.equal(defaultInformationContent.hero.title, "Informasi");
});

test("placeholders insert literal values without evaluating HTML or replacement tokens", () => {
  assert.equal(informationText("{title}: {start}–{end} / {total} {unknown}", { title: "$& <b>", start: 1, end: 3, total: 8 }), "$& <b>: 1–3 / 8 {unknown}");
});

test("API and frontend defaults stay identical", () => {
  assert.deepEqual(JSON.parse(readFileSync(new URL("../../../backend/resources/data/information.json", import.meta.url))), defaultInformationContent);
});

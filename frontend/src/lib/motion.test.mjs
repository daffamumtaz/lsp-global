import test from "node:test";
import assert from "node:assert/strict";
import { shouldPauseCarousel, shouldReduceMotion } from "./motion.ts";

test("motion follows device preference until the visitor opts in", () => {
  const previousDocument = globalThis.document;
  const previousMatchMedia = globalThis.matchMedia;
  try {
    globalThis.document = { documentElement: { dataset: {} } };
    globalThis.matchMedia = query => ({ matches: query.includes("reduced-motion") });
    assert.equal(shouldReduceMotion(), true);
    assert.equal(shouldPauseCarousel(null), true);
    document.documentElement.dataset.motion = "full";
    assert.equal(shouldReduceMotion(), false);
    assert.equal(shouldPauseCarousel(null), false);
    assert.equal(shouldPauseCarousel({ matches: query => query === ":focus-within" }), true);
    assert.equal(shouldPauseCarousel({ matches: query => query === ":hover" }), false, "touch hover must not stall the carousel");
    globalThis.matchMedia = query => ({ matches: query.includes("pointer: fine") });
    assert.equal(shouldPauseCarousel({ matches: query => query === ":hover" }), true);
    assert.equal(shouldPauseCarousel(null), false);
    delete document.documentElement.dataset.motion;
    assert.equal(shouldReduceMotion(), false);
  } finally {
    globalThis.document = previousDocument;
    globalThis.matchMedia = previousMatchMedia;
  }
});

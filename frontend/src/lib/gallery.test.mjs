import test from "node:test";
import assert from "node:assert/strict";
import { readGalleryState, galleryHref, selectGallery, yearMedia, moveMediaIndex, nextZoom } from "./gallery.ts";

const media = (id, type = "photo") => ({ id, type, src: `/${id}.jpg`, thumbnail: `/${id}.jpg`, alt: id, width: 1200, height: 800 });
const activities = [
  { id: "a", title: "Zeta", year: 2025, date: "2025-09-17", location: "Jakarta", description: "", media: [media("a1"), media("a2")] },
  { id: "b", title: "Alpha", year: 2025, date: "2025-02-01", location: "Bandung", description: "", media: [media("b1")] },
  { id: "c", title: "Beta", year: 2024, date: "2024-03-01", location: "Surabaya", description: "", media: [media("c1")] },
  { id: "v", title: "Video", year: 2023, date: null, location: "", description: "", media: [media("v1", "video")] },
];
const defaults = { type: "photo", year: null, query: "", sort: "newest", page: 1 };

test("URL state round-trips and rejects invalid kinds, years, sorting, and page numbers", () => {
  const state = { ...defaults, type: "video", year: 2025, query: "Riset & TIK", sort: "title-desc", page: 2 };
  assert.deepEqual(readGalleryState(new URL(galleryHref(state), "https://example.test").searchParams), state);
  assert.deepEqual(readGalleryState(new URLSearchParams("jenis=bad&tahun=x&urutan=bad&halaman=-2")), defaults);
  assert.equal(readGalleryState(new URLSearchParams("halaman=1.5")).page, 1);
  assert.equal(readGalleryState(new URLSearchParams("tahun=2025oops")).year, null);
});

test("groups photo albums by year and searches years without mixing in videos", () => {
  const result = selectGallery(activities, defaults);
  assert.deepEqual(result.items.map(item => item.year), [2025, 2024]);
  assert.equal(result.items[0].count, 3);
  const searched = selectGallery(activities, { ...defaults, query: "2024" });
  assert.deepEqual(searched.items.map(item => item.year), [2024]);
});

test("filters activities by type and year, searches location, and sorts by title or date", () => {
  assert.deepEqual(selectGallery(activities, { ...defaults, year: 2025 }).items.map(item => item.id), ["a", "b"]);
  assert.deepEqual(selectGallery(activities, { ...defaults, year: 2025, sort: "title-asc" }).items.map(item => item.id), ["b", "a"]);
  assert.deepEqual(selectGallery(activities, { ...defaults, year: 2025, query: " BANDUNG " }).items.map(item => item.id), ["b"]);
  assert.equal(selectGallery(activities, { ...defaults, type: "video", year: 2025 }).total, 0);
});

test("paginates using actual totals, clamps stale pages, and keeps source arrays unchanged", () => {
  const before = structuredClone(activities);
  const result = selectGallery(activities, { ...defaults, year: 2025, page: 99 }, 1);
  assert.equal(result.page, 2);
  assert.equal(result.pageCount, 2);
  assert.equal(result.items[0].id, "b");
  assert.equal(result.total, 2);
  assert.deepEqual(activities, before);
});

test("viewer includes all photos in the year despite filters and clamps navigation/zoom", () => {
  assert.deepEqual(yearMedia(activities, 2025, "photo").map(item => item.media.id), ["a1", "a2", "b1"]);
  assert.deepEqual(yearMedia(activities, 2024, "video"), []);
  assert.equal(moveMediaIndex(0, -1, 3), 0);
  assert.equal(moveMediaIndex(2, 1, 3), 2);
  assert.equal(moveMediaIndex(1, 1, 3), 2);
  assert.equal(nextZoom(100, -25), 100);
  assert.equal(nextZoom(300, 25), 300);
  assert.equal(nextZoom(100, 25), 125);
});

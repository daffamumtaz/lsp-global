import test from "node:test";
import assert from "node:assert/strict";
import { registerHooks } from "node:module";
import { existsSync, readFileSync, statSync } from "node:fs";
import { fileURLToPath, pathToFileURL } from "node:url";
import { createServer } from "node:http";
import ts from "typescript";
import { renderToReadableStream } from "react-dom/server";

async function render(element) {
  const stream = await renderToReadableStream(element);
  await stream.allReady;
  return new Response(stream).text();
}

// Load the actual TSX components in Node, with request-time connection supplied by the test.
const sourceRoot = new URL("../", import.meta.url);
registerHooks({
  resolve(specifier, context, nextResolve) {
    if (["next/link", "next/image"].includes(specifier)) {
      const target = nextResolve(specifier + ".js", context).url;
      return { url: `data:text/javascript,import component from ${JSON.stringify(target)}; export default component.default ?? component;`, shortCircuit: true };
    }
    if (specifier === "next/server") {
      return { url: "data:text/javascript,export async function connection() {}", shortCircuit: true };
    }
    if (specifier.startsWith("@/")) {
      specifier = new URL(specifier.slice(2), sourceRoot).href;
    }
    if (specifier.startsWith("file:") || specifier.startsWith(".")) {
      const url = new URL(specifier, context.parentURL);
      const path = fileURLToPath(url);
      for (const extension of ["", ".ts", ".tsx"]) {
        if (existsSync(path + extension) && statSync(path + extension).isFile()) return { url: pathToFileURL(path + extension).href, shortCircuit: true };
      }
    }
    if (["next/link", "next/image", "next/navigation"].includes(specifier)) specifier += ".js";
    return nextResolve(specifier, context);
  },
  load(url, context, nextLoad) {
    if (url.endsWith(".css")) return { format: "module", source: "export default new Proxy({}, { get: (_, name) => name });", shortCircuit: true };
    if (url.endsWith(".json")) return { format: "module", source: `export default ${readFileSync(new URL(url), "utf8")}`, shortCircuit: true };
    if (/\.tsx?$/.test(url)) {
      return { format: "module", source: ts.transpileModule(readFileSync(new URL(url), "utf8"), { compilerOptions: { module: ts.ModuleKind.ESNext, jsx: ts.JsxEmit.ReactJSX, target: ts.ScriptTarget.ES2022 } }).outputText, shortCircuit: true };
    }
    return nextLoad(url, context);
  },
});

const { defaultLandingContent } = await import("./landing.ts");
const { default: Home } = await import("../app/page.tsx");
const { Footer } = await import("../components/landing/chrome.tsx");
const { default: SchemePage, generateMetadata } = await import("../app/skema-sertifikasi/[slug]/page.tsx");
const directory = { tuks: [], assessors: [], schemes: [], gallery: [] };

async function withApi(t, landing, content, callback) {
  const previous = { landing: process.env.LANDING_API_URL, content: process.env.CONTENT_API_URL };
  const server = createServer((request, response) => {
    if (request.url === "/landing") {
      response.setHeader("Content-Type", "application/json");
      response.end(JSON.stringify({ data: landing }));
    } else {
      response.writeHead(content === null ? 503 : 200, { "Content-Type": "application/json" });
      response.end(JSON.stringify({ data: content }));
    }
  });
  await new Promise(resolve => server.listen(0, "127.0.0.1", resolve));
  const base = `http://127.0.0.1:${server.address().port}`;
  process.env.LANDING_API_URL = `${base}/landing`;
  process.env.CONTENT_API_URL = `${base}/content`;
  t.after(() => {
    for (const [key, value] of [["LANDING_API_URL", previous.landing], ["CONTENT_API_URL", previous.content]]) {
      if (value === undefined) delete process.env[key]; else process.env[key] = value;
    }
    server.closeAllConnections();
    server.close();
  });
  return callback();
}

const props = { params: Promise.resolve({ slug: "missing" }), searchParams: Promise.resolve({}) };

test("home renders CMS reviews and respects removal of every review", async t => {
  const content = structuredClone(defaultLandingContent);
  content.reviews.items = [{ name: "CMS participant", occupation: "Engineer", quote: "My published CMS review", photo_url: null }];
  await withApi(t, content, directory, async () => {
    const html = await render(await Home());
    assert.match(html, /My published CMS review/);
    content.reviews.items = [];
    const empty = await render(await Home());
    assert.doesNotMatch(empty, /aria-label="Review kami"|My published CMS review/);
  });
});

test("footer uses CMS Instagram URL and hides it when removed", async t => {
  const content = structuredClone(defaultLandingContent);
  content.social_links.Instagram = "https://www.instagram.com/cms-account/";
  await withApi(t, content, directory, async () => {
    assert.match(await render(await Footer()), /href="https:\/\/www.instagram.com\/cms-account\/"/);
    content.social_links.Instagram = null;
    assert.doesNotMatch(await render(await Footer()), /Instagram/);
  });
});

test("scheme API outage renders a retry destination and nonindexable metadata instead of a 404", async t => {
  t.mock.method(console, "error", () => {});
  await withApi(t, defaultLandingContent, null, async () => {
    const metadata = await generateMetadata(props);
    assert.equal(metadata.robots?.index, false);
    const html = await render(await SchemePage(props));
    assert.match(html, /sementara|Coba lagi/i);
    assert.match(html, /href="\/skema-sertifikasi\/missing"/);
  });
});

test("a successful directory response without the requested scheme remains a 404", async t => {
  await withApi(t, defaultLandingContent, directory, async () => {
    await assert.rejects(SchemePage(props), error => error.digest === "NEXT_HTTP_ERROR_FALLBACK;404");
  });
});

test("landing API aborts a stalled body within five seconds and falls back without invented reviews", async t => {
  const { getLandingContent } = await import("./landing.ts");
  t.mock.method(console, "error", () => {});
  const previous = process.env.LANDING_API_URL;
  const server = createServer((request, response) => {
    response.writeHead(200, { "Content-Type": "application/json" });
    response.write('{"data":');
  });
  await new Promise(resolve => server.listen(0, "127.0.0.1", resolve));
  process.env.LANDING_API_URL = `http://127.0.0.1:${server.address().port}`;
  const safetyClose = setTimeout(() => server.closeAllConnections(), 5500);
  t.after(() => {
    clearTimeout(safetyClose);
    server.closeAllConnections();
    server.close();
    if (previous === undefined) delete process.env.LANDING_API_URL; else process.env.LANDING_API_URL = previous;
  });
  const started = performance.now();
  const content = await getLandingContent();
  assert.ok(performance.now() - started < 5300, "stalled landing content exceeded the five-second request budget");
  assert.deepEqual(content.reviews.items, []);
});

test("malformed CMS review data falls back to a usable home without testimonials", async t => {
  t.mock.method(console, "error", () => {});
  const content = structuredClone(defaultLandingContent);
  content.reviews.items = null;
  await withApi(t, content, directory, async () => {
    const html = await render(await Home());
    assert.match(html, /id="hero-title"/);
    assert.doesNotMatch(html, /aria-label="Review kami"/);
  });
});

test("a published scheme still renders its details and descriptive metadata", async t => {
  const { sampleSchemes } = await import("./scheme-samples.ts");
  const scheme = sampleSchemes[0];
  const schemeProps = { ...props, params: Promise.resolve({ slug: scheme.slug }) };
  await withApi(t, defaultLandingContent, { ...directory, schemes: [scheme] }, async () => {
    const metadata = await generateMetadata(schemeProps);
    assert.equal(metadata.title, `${scheme.name} | LSP TIK Global`);
    assert.ok(metadata.description.includes(scheme.name));
    assert.equal(metadata.robots, undefined);
    const html = await render(await SchemePage(schemeProps));
    assert.ok(html.includes(scheme.name));
    assert.match(html, /Daftar Sekarang/);
  });
});

const validReview = { name: "CMS participant", occupation: "Engineer", quote: "Published participant feedback", photo_url: null };
for (const [name, review] of [
  ["null entry", null],
  ["object name", { ...validReview, name: { text: "Name" } }],
  ["object occupation", { ...validReview, occupation: { text: "Engineer" } }],
  ["object quote", { ...validReview, quote: { text: "Feedback" } }],
  ["object photo", { ...validReview, photo_url: { src: "/photo.jpg" } }],
  ["unsafe photo scheme", { ...validReview, photo_url: "javascript:alert(1)" }],
]) {
  test(`malformed CMS review entry (${name}) never breaks or populates the homepage`, async t => {
    t.mock.method(console, "error", () => {});
    const content = structuredClone(defaultLandingContent);
    content.reviews.items = [review];
    await withApi(t, content, directory, async () => {
      const html = await render(await Home());
      assert.match(html, /id="hero-title"/);
      assert.doesNotMatch(html, /aria-label="Review kami"|Published participant feedback/);
    });
  });
}

test("valid CMS reviews retain relative and HTTPS photo URLs", async t => {
  const content = structuredClone(defaultLandingContent);
  content.reviews.items = [
    { ...validReview, photo_url: "/storage/reviews/participant.jpg" },
    { ...validReview, name: "Second participant", photo_url: "https://media.example.test/reviews/participant.jpg" },
  ];
  await withApi(t, content, directory, async () => {
    const html = await render(await Home());
    assert.match(html, /Published participant feedback/);
    assert.match(html, /src="\/storage\/reviews\/participant.jpg"/);
    assert.match(html, /src="https:\/\/media.example.test\/reviews\/participant.jpg"/);
  });
});

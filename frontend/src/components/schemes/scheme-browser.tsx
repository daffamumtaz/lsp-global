"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { methodLabels, readSchemeState, schemeHref, schemeSorts, selectSchemes, type Scheme, type SchemeMethod, type SchemeState } from "@/lib/schemes";
import { SchemeCard } from "./scheme-card";
import styles from "./schemes.module.css";

export function SchemeBrowser({ schemes }: { schemes: Scheme[] }) {
  const params = useSearchParams();
  const categories = [...new Set(schemes.map(scheme => scheme.category))].sort((a, b) => a.localeCompare(b, "id"));
  const state = readSchemeState(params, categories);
  const result = selectSchemes(schemes, state);
  const resultsView = useRef<HTMLDivElement>(null);
  const queryString = params.toString();
  const previousQuery = useRef(queryString);
  useEffect(() => {
    if (previousQuery.current === queryString) return;
    previousQuery.current = queryString;
    resultsView.current?.scrollIntoView({ block: "start", behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "instant" : "smooth" });
  }, [queryString]);
  const [categoryQuery, setCategoryQuery] = useState("");
  const visibleCategories = categories.filter(category => category.toLocaleLowerCase("id").includes(categoryQuery.trim().toLocaleLowerCase("id")));
  const active = [
    ...(state.query ? [`Pencarian: ${state.query}`] : []), ...state.methods.map(method => methodLabels[method]),
    ...(state.minLevel !== 1 || state.maxLevel !== 9 ? [`KKNI ${state.minLevel}–${state.maxLevel}`] : []), ...state.categories,
  ];
  function update(patch: Partial<SchemeState>) {
    const current = readSchemeState(new URLSearchParams(window.location.search), categories);
    const next = readSchemeState(new URL(schemeHref({ ...current, ...patch, page: 1 }), window.location.origin).searchParams, categories);
    window.history.pushState(null, "", schemeHref(next));
  }
  function reset() { setCategoryQuery(""); window.history.pushState(null, "", "/skema-sertifikasi"); }

  return <section className={styles.catalog} aria-label="Katalog skema sertifikasi">
    <div className={styles.inner}>
      <div className={styles.catalogLayout}>
        <aside><details className={styles.filters} open>
          <summary>Filter skema</summary>
          <div className={styles.filterBody}>
            <fieldset><legend>Metode pelaksanaan</legend>{(Object.keys(methodLabels) as SchemeMethod[]).map(method => <label className={styles.check} key={method}><input type="checkbox" checked={state.methods.includes(method)} onChange={event => update({ methods: event.target.checked ? [...state.methods, method] : state.methods.filter(value => value !== method) })} />{methodLabels[method]}</label>)}</fieldset>
            <fieldset><legend>Level KKNI</legend><div className={styles.levelRange}>
              <label>Minimum<select value={state.minLevel} onChange={event => update({ minLevel: Number(event.target.value) })}>{Array.from({ length: 9 }, (_, i) => <option value={i + 1} key={i}>{i + 1}</option>)}</select></label>
              <label>Maksimum<select value={state.maxLevel} onChange={event => update({ maxLevel: Number(event.target.value) })}>{Array.from({ length: 9 }, (_, i) => <option value={i + 1} key={i}>{i + 1}</option>)}</select></label>
            </div></fieldset>
            <fieldset><legend>Kategori</legend><label className={styles.field}>Cari kategori<input type="search" name="category-search" autoComplete="off" value={categoryQuery} placeholder="Contoh: Data…" onChange={event => setCategoryQuery(event.target.value)} /></label>
              <div className={styles.categoryOptions}>{visibleCategories.map(category => <label className={styles.check} key={category}><input type="checkbox" checked={state.categories.includes(category)} onChange={event => update({ categories: event.target.checked ? [...state.categories, category] : state.categories.filter(value => value !== category) })} />{category}</label>)}</div>
              {!visibleCategories.length && <p className={styles.helper}>Kategori tidak ditemukan. Coba kata kunci lain.</p>}
              {categoryQuery && <button className={styles.textButton} type="button" onClick={() => setCategoryQuery("")}>Tampilkan semua kategori</button>}
            </fieldset>
            <button className={styles.secondary} type="button" onClick={reset}>Reset filter</button>
          </div>
        </details></aside>
        <div ref={resultsView} className={styles.results}>
          <h2 className="sr-only">Daftar skema sertifikasi</h2>
          <form className={styles.searchForm} role="search" aria-label="Cari skema sertifikasi" onSubmit={event => { event.preventDefault(); update({ query: String(new FormData(event.currentTarget).get("q") ?? "").trim() }); }}>
            <label className={styles.field}><span className="sr-only">Cari skema</span><input key={state.query} type="search" name="q" autoComplete="off" defaultValue={state.query} placeholder="Contoh: Junior Coder…" /></label><button type="submit" className={styles.primary}>Cari skema</button>
          </form>
          <div className={styles.resultToolbar}><p role="status" aria-live="polite">{result.total ? <>Menampilkan <strong>{result.start}–{result.end}</strong> dari <strong>{result.total}</strong> skema</> : "Tidak ada skema yang sesuai"}</p>
            <label className={styles.field}>Urutkan<select value={state.sort} onChange={event => update({ sort: event.target.value as SchemeState["sort"] })}>{Object.entries(schemeSorts).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
          </div>
          {active.length > 0 && <div className={styles.activeFilters} aria-label="Filter aktif">{active.map((label, index) => <span key={`${label}-${index}`}>{label}</span>)}<button className={styles.textButton} type="button" onClick={reset}>Hapus semua</button></div>}
          <div className={styles.grid}>{result.items.map(scheme => <SchemeCard key={scheme.id} scheme={scheme} href={schemeHref({ ...state, page: result.page }, scheme.slug)} />)}</div>
          {!result.total && <div className={styles.empty}><h2>Skema tidak ditemukan</h2><p>Coba kata kunci lain atau hapus filter untuk melihat seluruh skema.</p><button type="button" className={styles.primary} onClick={reset}>Lihat semua skema</button></div>}
          {result.pageCount > 1 && <nav className={styles.pagination} aria-label="Halaman skema">
            {result.page > 1 ? <Link href={schemeHref({ ...state, page: result.page - 1 })} scroll={false} aria-label="Halaman sebelumnya">←</Link> : <span aria-disabled="true">←</span>}
            {Array.from({ length: result.pageCount }, (_, i) => i + 1).map(page => <Link key={page} href={schemeHref({ ...state, page })} scroll={false} aria-current={page === result.page ? "page" : undefined} aria-label={`Halaman ${page}`}>{page}</Link>)}
            {result.page < result.pageCount ? <Link href={schemeHref({ ...state, page: result.page + 1 })} scroll={false} aria-label="Halaman berikutnya">→</Link> : <span aria-disabled="true">→</span>}
          </nav>}
        </div>
      </div>
    </div>
  </section>;
}

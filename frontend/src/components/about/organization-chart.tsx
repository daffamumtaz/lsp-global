"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { layoutOrganization, NODE_HEIGHT, NODE_WIDTH, type OrganizationNode } from "@/lib/organization";
import styles from "./organization-chart.module.css";

export function OrganizationChart({ nodes }: { nodes: OrganizationNode[] }) {
  const layout = useMemo(() => layoutOrganization(nodes), [nodes]);
  const viewport = useRef<HTMLDivElement>(null);
  const [zoom, setZoom] = useState(1);
  const [showList, setShowList] = useState(false);
  const names = new Map(nodes.map(node => [node.id, node.title]));

  useEffect(() => {
    const root = layout.nodes.find(node => node.parent_id === null);
    if (viewport.current && root) {
      viewport.current.scrollLeft = Math.max(0, (root.x + NODE_WIDTH / 2) * zoom - viewport.current.clientWidth / 2);
    }
  }, [layout, zoom, showList]);

  if (nodes.length === 0) return <p className={styles.empty}>Struktur organisasi belum tersedia.</p>;

  return (
    <div className={styles.chart}>
      <div className={styles.toolbar}>
        <div><strong>Struktur Organisasi</strong><span>{nodes.length} jabatan</span></div>
        <div className={styles.controls}>
          {!showList && <>
            <button type="button" onClick={() => setZoom(value => Math.max(.25, value - .25))} disabled={zoom <= .25} aria-label="Perkecil bagan">−</button>
            <output aria-live="polite">{Math.round(zoom * 100)}%</output>
            <button type="button" onClick={() => setZoom(value => Math.min(2, value + .25))} disabled={zoom >= 2} aria-label="Perbesar bagan">+</button>
            <button type="button" onClick={() => setZoom(Math.max(.1, Math.min(1, (viewport.current?.clientWidth ?? layout.width) / layout.width)))}>Sesuaikan</button>
          </>}
          <button type="button" aria-pressed={showList} onClick={() => setShowList(value => !value)}>{showList ? "Lihat bagan" : "Lihat daftar"}</button>
        </div>
      </div>
      {showList ? (
        <ul className={styles.list}>
          {layout.nodes.map(node => <li key={node.id}><strong>{node.title}</strong><span>{node.name}</span><small>Atasan: {node.parent_id === null ? "—" : names.get(node.parent_id)}</small></li>)}
        </ul>
      ) : (
        <>
          <p className={styles.hint} id="organization-help">Geser area bagan untuk melihat seluruh struktur, atau pilih Sesuaikan. Gunakan Lihat daftar untuk membaca semua jabatan.</p>
          <div ref={viewport} className={styles.viewport} tabIndex={0} role="region" aria-label="Bagan struktur organisasi" aria-describedby="organization-help">
            <div className={styles.canvas} style={{ width: layout.width * zoom, height: layout.height * zoom }}>
              <div className={styles.plane} style={{ width: layout.width, height: layout.height, transform: `scale(${zoom})` }}>
                <svg className={styles.edges} width={layout.width} height={layout.height} aria-hidden="true">
                  {layout.edges.map(edge => <path key={edge.id} d={edge.path} fill="none" stroke="#80b4d2" strokeWidth="2" />)}
                </svg>
                {layout.nodes.map(node => (
                  <article key={node.id} className={`${styles.node} ${node.parent_id === null ? styles.root : ""}`} style={{ left: node.x, top: node.y, width: NODE_WIDTH, height: NODE_HEIGHT }}>
                    <h3>{node.title}</h3><p>{node.name}</p><span className={styles.srOnly}>Atasan: {node.parent_id === null ? "Tidak ada" : names.get(node.parent_id)}</span>
                  </article>
                ))}
              </div>
            </div>
          </div>
        </>
      )}
    </div>
  );
}

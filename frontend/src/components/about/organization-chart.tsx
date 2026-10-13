"use client";

import dynamic from 'next/dynamic';
import { useMemo } from 'react';
import { buildOrganizationTree, type OrganizationBranch, type OrganizationNode } from '@/lib/organization';
import styles from './organization-chart.module.css';

const OrganizationFlow = dynamic(() => import('./organization-flow'), { ssr: false, loading: () => <p className={styles.empty} role="status">Memuat bagan organisasi…</p> });

export function OrganizationChart({ nodes, unavailable = false, preview = false }: { nodes: OrganizationNode[]; unavailable?: boolean; preview?: boolean }) {
  const roots = useMemo(() => {
    const tree = buildOrganizationTree(preview ? nodes.map(node => ({ ...node, is_active: true })) : nodes);
    if (preview) {
      const inactive = new Set(nodes.filter(node => !node.is_active).map(node => node.id));
      function label(branches: OrganizationBranch[]) {
        for (const branch of branches) { if (inactive.has(branch.id)) branch.title += ' (Nonaktif)'; label(branch.children); }
      }
      label(tree);
    }
    return tree;
  }, [nodes, preview]);
  if (unavailable) return <p className={styles.empty} role="status">Struktur organisasi belum dapat dimuat. Silakan muat ulang halaman.</p>;
  if (!roots.length) return <p className={styles.empty}>Struktur organisasi belum tersedia.</p>;
  return <div className={styles.chart}>
    <div className={styles.toolbar}>
      <div><strong>Struktur Organisasi</strong><span>{preview ? nodes.length : nodes.filter(node => node.is_active).length} jabatan</span></div>
    </div>
    <p className={styles.hint}>Geser bagan untuk menjelajahi struktur. Gunakan kontrol zoom untuk membaca semua jabatan.</p>
    <OrganizationFlow roots={roots} />
  </div>;
}

"use client";

import dynamic from 'next/dynamic';
import { useMemo, useState } from 'react';
import { buildOrganizationTree, type OrganizationBranch, type OrganizationNode } from '@/lib/organization';
import styles from './organization-chart.module.css';

const OrganizationFlow = dynamic(() => import('./organization-flow'), { ssr: false, loading: () => <p className={styles.empty} role="status">Memuat bagan organisasi…</p> });

function OrganizationList({ branches }: { branches: OrganizationBranch[] }) {
  return <ul className={styles.list}>{branches.map(node => <li key={node.id}>
    <div><strong>{node.title}</strong><span>{node.person_name}{node.credentials ? `, ${node.credentials}` : ''}</span></div>
    {node.children.length > 0 && <OrganizationList branches={node.children} />}
  </li>)}</ul>;
}

export function OrganizationChart({ nodes, unavailable = false }: { nodes: OrganizationNode[]; unavailable?: boolean }) {
  const roots = useMemo(() => buildOrganizationTree(nodes), [nodes]);
  const [showList, setShowList] = useState(false);
  if (unavailable) return <p className={styles.empty} role="status">Struktur organisasi belum dapat dimuat. Silakan muat ulang halaman.</p>;
  if (!roots.length) return <p className={styles.empty}>Struktur organisasi belum tersedia.</p>;
  return <div className={styles.chart}>
    <div className={styles.toolbar}>
      <div><strong>Struktur Organisasi</strong><span>{nodes.filter(node => node.is_active).length} jabatan</span></div>
      <button type="button" className={styles.viewButton} aria-pressed={showList} onClick={() => setShowList(value => !value)}>{showList ? 'Lihat bagan' : 'Lihat daftar'}</button>
    </div>
    {showList ? <OrganizationList branches={roots} /> : <>
      <p className={styles.hint}>Geser bagan untuk menjelajahi struktur. Gunakan kontrol zoom atau Lihat daftar untuk membaca semua jabatan.</p>
      <OrganizationFlow roots={roots} />
    </>}
  </div>;
}

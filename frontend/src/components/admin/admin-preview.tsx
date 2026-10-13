'use client';

import dynamic from 'next/dynamic';
import { Component, useCallback, useEffect, useRef, useState, type ReactNode } from 'react';
import { parsePreviewUpdate, trustedPreviewEvent, type PreviewKind, type PreviewUpdate, type TukPreview } from '@/lib/admin-preview';

const OrganizationChart = dynamic(() => import('../about/organization-chart').then(module => module.OrganizationChart), { ssr: false, loading: () => <p role="status">Memuat bagan organisasi…</p> });
const AssessmentProcessView = dynamic(() => import('../information/assessment-process-view').then(module => module.AssessmentProcessView), { ssr: false, loading: () => <p role="status">Memuat alur asesmen…</p> });
const TukMap = dynamic(() => import('../information/tuk-map'), { ssr: false, loading: () => <p role="status">Memuat peta… Daftar TUK tetap tersedia.</p> });

class PreviewBoundary extends Component<{ children: ReactNode; fallback: ReactNode }, { failed: boolean }> {
  state = { failed: false };
  static getDerivedStateFromError() { return { failed: true }; }
  render() { return this.state.failed ? <><p role="alert">Preview tidak dapat ditampilkan. Silakan gunakan daftar.</p>{this.props.fallback}</> : this.props.children; }
}

function TukPreviewView({ tuks }: { tuks: TukPreview[] }) {
  const [selected, setSelected] = useState<string[] | null>(null);
  const select = useCallback((ids: string[]) => setSelected(ids), []);
  const entries = tuks.map(tuk => ({ ...tuk, name: `${tuk.name}${tuk.is_active ? '' : ' (Nonaktif)'}` }));
  return <>
    <PreviewBoundary fallback={null}><TukMap entries={entries} onSelect={select} /></PreviewBoundary>
    <button type="button" disabled={!selected} onClick={() => setSelected(null)}>Tampilkan semua</button>
    {!tuks.length && <p role="status">TUK belum tersedia.</p>}
    <ul>{tuks.filter(tuk => !selected || selected.includes(tuk.id)).map(tuk => <li key={tuk.id}><strong>{tuk.name}</strong> ({tuk.is_active ? 'Aktif' : 'Nonaktif'})<p>{tuk.address || 'Alamat belum tersedia'}</p><p>{[tuk.city, tuk.province].filter(Boolean).join(', ')}</p></li>)}</ul>
  </>;
}

function PreviewContent({ update }: { update: PreviewUpdate }) {
  if (update.kind === 'organization') {
    return <OrganizationChart nodes={update.data} preview />;
  }
  if (update.kind === 'process') {
    const process = { ...update.data, title: `${update.data.title}${update.data.isActive ? '' : ' (Nonaktif)'}`, actors: update.data.actors.map(actor => ({ ...actor, label: `${actor.label}${actor.isActive ? '' : ' (Nonaktif)'}` })) };
    return <AssessmentProcessView process={process} unavailable={false} preview />;
  }
  return <TukPreviewView tuks={update.data} />;
}

function PreviewList({ update }: { update: PreviewUpdate }) {
  if (update.kind === 'tuks') return <TukPreviewView tuks={update.data} />;
  const rows = update.kind === 'organization' ? update.data.map(node => ({ id: node.id, title: node.title, active: node.is_active })) : update.data.stages.map(stage => ({ id: stage.id, title: stage.title, active: stage.isActive }));
  return <ul>{rows.map(row => <li key={row.id}>{row.title}{!row.active && ' (Nonaktif)'}</li>)}</ul>;
}

export function AdminPreview({ kind, adminOrigin }: { kind: PreviewKind; adminOrigin: string }) {
  const [update, setUpdate] = useState<PreviewUpdate | null>(null);
  const [error, setError] = useState('');
  const revision = useRef(-1);
  useEffect(() => {
    function receive(event: MessageEvent) {
      if (!trustedPreviewEvent(event, window.parent, adminOrigin)) return;
      try {
        const next = parsePreviewUpdate(event.data, kind, revision.current);
        if (!next) return;
        revision.current = next.revision;
        setUpdate(next);
        setError('');
      } catch (error) {
        setError(error instanceof Error ? error.message : 'Data preview tidak valid.');
      }
    }
    window.addEventListener('message', receive);
    window.parent.postMessage({ type: 'admin-preview:ready', kind }, adminOrigin);
    return () => window.removeEventListener('message', receive);
  }, [kind, adminOrigin]);
  return <main id="main" style={{ padding: '1rem' }}>
    {update?.draft && <p role="status"><strong>Belum disimpan</strong></p>}
    {error && <p role="alert">{error}</p>}
    {update ? <PreviewBoundary key={update.revision} fallback={<PreviewList update={update} />}><PreviewContent update={update} /></PreviewBoundary> : <p role="status">Menunggu data preview dari admin…</p>}
  </main>;
}

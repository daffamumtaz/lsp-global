"use client";

import { useEffect, useMemo } from 'react';
import { ReactFlow, ReactFlowProvider, Handle, Position, useReactFlow, useNodesInitialized, useNodesState, type NodeProps } from '@xyflow/react';
import { MinusIcon, PlusIcon, ArrowsPointingOutIcon } from '@heroicons/react/24/outline';
import '@xyflow/react/dist/style.css';
import { layoutOrganization, type OrganizationCard } from '@/lib/organization-layout';
import type { OrganizationBranch } from '@/lib/organization';
import styles from './organization-chart.module.css';

function PositionCard({ data }: NodeProps<OrganizationCard>) {
  const node = data.position;
  return <article className={styles.node}>
    {node.parent_id !== null && <Handle type="target" position={Position.Top} className={styles.handle} />}
    <h3>{node.title}</h3>
    <p>{node.person_name}{node.credentials ? `, ${node.credentials}` : ''}</p>
    {node.children.length > 0 && <Handle type="source" position={Position.Bottom} className={styles.handle} />}
  </article>;
}
const nodeTypes = { position: PositionCard };

function Tree({ roots }: { roots: OrganizationBranch[] }) {
  const { nodes, edges } = useMemo(() => layoutOrganization(roots), [roots]);
  const [positioned, setNodes, onNodesChange] = useNodesState(nodes);
  const initialized = useNodesInitialized();
  const flow = useReactFlow();
  useEffect(() => {
    if (!initialized) return;
    const heights = new Map(flow.getNodes().map(node => [node.id, node.measured?.height ?? 132]));
    const measured = new Map(flow.getNodes().map(node => [node.id, node.measured]));
    setNodes(layoutOrganization(roots, heights).nodes.map(node => ({ ...node, measured: measured.get(node.id) })));
    const frame = requestAnimationFrame(() => flow.fitView({ padding: .12, minZoom: .15, maxZoom: 1 }));
    return () => cancelAnimationFrame(frame);
  }, [initialized, roots, flow, setNodes]);
  return <div className={styles.viewport}>
    <div className={styles.controls} aria-label="Kontrol bagan">
      <button type="button" aria-label="Perkecil bagan" onClick={() => flow.zoomOut()}><MinusIcon aria-hidden="true" /></button>
      <button type="button" aria-label="Perbesar bagan" onClick={() => flow.zoomIn()}><PlusIcon aria-hidden="true" /></button>
      <button type="button" aria-label="Sesuaikan bagan" onClick={() => flow.fitView({ padding: .12, minZoom: .15, maxZoom: 1 })}><ArrowsPointingOutIcon aria-hidden="true" /><span>Sesuaikan</span></button>
    </div>
    <ReactFlow nodes={positioned} onNodesChange={onNodesChange} edges={edges} nodeTypes={nodeTypes} fitView fitViewOptions={{ padding: .12, minZoom: .15, maxZoom: 1 }}
      minZoom={.15} maxZoom={2} nodesDraggable={false} nodesConnectable={false} elementsSelectable={false}
      nodesFocusable={false} edgesFocusable={false} deleteKeyCode={null} zoomOnScroll={false} zoomOnDoubleClick={false}
      aria-label="Bagan struktur organisasi" />
  </div>;
}

export default function OrganizationFlow({ roots }: { roots: OrganizationBranch[] }) {
  return <ReactFlowProvider><Tree roots={roots} /></ReactFlowProvider>;
}

"use client";

import { useEffect, useMemo } from 'react';
import { ReactFlow, ReactFlowProvider, Handle, Position, useReactFlow, useNodesInitialized, useNodesState, useEdgesState, BaseEdge, type EdgeProps, type Edge, type NodeProps } from '@xyflow/react';
import { MinusIcon, PlusIcon, ArrowsPointingOutIcon } from '@heroicons/react/24/outline';
import '@xyflow/react/dist/style.css';
import { layoutOrganization, type OrganizationCard } from '@/lib/organization-layout';
import { organizationMembers, type OrganizationBranch } from '@/lib/organization';
import type { Point } from '@/lib/organization-routing';
import styles from './organization-chart.module.css';

function PositionCard({ data }: NodeProps<OrganizationCard>) {
  const node = data.position;
  return <article className={styles.node}>
    {node.parent_id !== null && <><Handle id="in-top" type="target" position={Position.Top} className={styles.handle} /><Handle id="in-left" type="target" position={Position.Left} className={styles.handle} /><Handle id="in-right" type="target" position={Position.Right} className={styles.handle} /></>}
    <h3>{node.title}</h3>
    <div className={styles.members}>{organizationMembers(node).map((member, index) => <p key={index}>{member.person_name}{member.credentials ? `, ${member.credentials}` : ''}</p>)}</div>
    {node.children.length > 0 && <><Handle id="out-bottom" type="source" position={Position.Bottom} className={styles.handle} /><Handle id="out-right" type="source" position={Position.Right} className={styles.handle} /></>}
  </article>;
}
function OrganizationEdge({ id, data, style }: EdgeProps<Edge<{ points: Point[] }>>) {
  const path = data?.points.map((point, index) => `${index ? 'L' : 'M'} ${point.x} ${point.y}`).join(' ') ?? '';
  return <BaseEdge id={id} path={path} style={style} />;
}
const edgeTypes = { organization: OrganizationEdge };
const nodeTypes = { position: PositionCard };

function Tree({ roots }: { roots: OrganizationBranch[] }) {
  const { nodes, edges: initialEdges } = useMemo(() => layoutOrganization(roots), [roots]);
  const [positioned, setNodes, onNodesChange] = useNodesState(nodes);
  const [edges, setEdges] = useEdgesState(initialEdges);
  const initialized = useNodesInitialized();
  const flow = useReactFlow();
  useEffect(() => {
    if (!initialized) return;
    const heights = new Map(flow.getNodes().map(node => [node.id, node.measured?.height ?? 132]));
    const measured = new Map(flow.getNodes().map(node => [node.id, node.measured]));
    const layout = layoutOrganization(roots, heights);
    setNodes(layout.nodes.map(node => ({ ...node, measured: measured.get(node.id) })));
    setEdges(layout.edges);
    const frame = requestAnimationFrame(() => flow.fitView({ padding: .12, minZoom: .15, maxZoom: 1 }));
    return () => cancelAnimationFrame(frame);
  }, [initialized, roots, flow, setNodes, setEdges]);
  return <div className={styles.viewport}>
    <div className={styles.controls} aria-label="Kontrol bagan">
      <button type="button" aria-label="Perkecil bagan" onClick={() => flow.zoomOut()}><MinusIcon aria-hidden="true" /></button>
      <button type="button" aria-label="Perbesar bagan" onClick={() => flow.zoomIn()}><PlusIcon aria-hidden="true" /></button>
      <button type="button" aria-label="Sesuaikan bagan" onClick={() => flow.fitView({ padding: .12, minZoom: .15, maxZoom: 1 })}><ArrowsPointingOutIcon aria-hidden="true" /><span>Sesuaikan</span></button>
    </div>
    <ReactFlow nodes={positioned} onNodesChange={onNodesChange} edges={edges} nodeTypes={nodeTypes} edgeTypes={edgeTypes} fitView fitViewOptions={{ padding: .12, minZoom: .15, maxZoom: 1 }}
      minZoom={.15} maxZoom={2} nodesDraggable={false} nodesConnectable={false} elementsSelectable={false}
      nodesFocusable={false} edgesFocusable={false} deleteKeyCode={null} zoomOnScroll={false} zoomOnDoubleClick={false} preventScrolling={false}
      aria-label="Bagan struktur organisasi" />
  </div>;
}

export default function OrganizationFlow({ roots }: { roots: OrganizationBranch[] }) {
  return <ReactFlowProvider><Tree roots={roots} /></ReactFlowProvider>;
}

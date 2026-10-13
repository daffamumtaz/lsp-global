'use client';

import { createContext, useContext, useEffect, useRef, useState, useCallback } from 'react';
import { ReactFlow, ReactFlowProvider, Handle, Position, BaseEdge, useReactFlow, type NodeProps, type EdgeProps } from '@xyflow/react';
import { MinusIcon, PlusIcon, ArrowsPointingOutIcon, ArrowsPointingInIcon, ArrowPathIcon, XMarkIcon } from '@heroicons/react/24/outline';
import '@xyflow/react/dist/style.css';
import type { AssessmentProcess, ProcessStep, ProcessStage, StepType } from '@/lib/assessment-process';
import { defaultInformationContent, informationText, type InformationContent } from '@/lib/information-copy';
import { layoutAssessmentProcess, type ActivityNode, type StageNode, type LaneNode, type ConnectionLabelNode, type ProcessNode, type ProcessEdge, type NodeSize } from '@/lib/assessment-process-layout';
import styles from './assessment-process-flow.module.css';

const Copy = createContext(defaultInformationContent.flow);
const Interaction = createContext<{ select: (id: number, element: HTMLButtonElement) => void; actor: string; stage: string; selected: number | null }>({ select: () => {}, actor: '', stage: '', selected: null });
function CardContent({ step }: { step: ProcessStep }) {
  const copy = useContext(Copy);
  return <><strong>{step.title}</strong>{!step.isActive && <span className={styles.inactive}>{copy.inactive}</span>}</>;
}
function Activity({ data }: NodeProps<ActivityNode>) {
  const interaction = useContext(Interaction);
  const copy = useContext(Copy);
  const subdued = (!!interaction.actor && String(data.actor?.id ?? 'none') !== interaction.actor) || (!!interaction.stage && String(data.stage.id) !== interaction.stage);
  return <>
    {([['top', Position.Top], ['left', Position.Left], ['right', Position.Right]] as const).map(([side, position]) => <Handle key={side} id={`in-${side}`} type="target" position={position} style={side === 'top' ? undefined : { top: '35%' }} className={styles.handle} />)}
    <button type="button" data-activity-id={data.step.id} data-subdued={subdued} aria-pressed={interaction.selected === data.step.id}
      aria-label={informationText(copy.activity_label, { title: data.step.title, actor: data.actor?.label ?? copy.no_actor })}
      className={`${styles.card} ${data.status === 'normal' ? '' : styles[data.status]} ${data.step.type === 'decision' ? styles.decision : ''} nodrag nopan`}
      onClick={event => interaction.select(data.step.id, event.currentTarget)}>
      <CardContent step={data.step} />
    </button>
    {([['bottom', Position.Bottom], ['left', Position.Left], ['right', Position.Right]] as const).map(([side, position]) => <Handle key={side} id={`out-${side}`} type="source" position={position} style={side === 'bottom' ? undefined : { top: '65%' }} className={styles.handle} />)}
  </>;
}
function StageHeader({ stage, number }: { stage: ProcessStage; number: number }) {
  const copy = useContext(Copy);
  return <header><span>{String(number).padStart(2, '0')}</span><h4>{stage.title}{!stage.isActive && ` · ${copy.inactive}`}</h4></header>;
}
function Stage({ data }: NodeProps<StageNode>) {
  const copy = useContext(Copy);
  return <section className={styles.stage}><StageHeader {...data} />{!data.stage.steps.length && <p className={styles.emptyStage}>{copy.empty_stage}</p>}</section>;
}
function Lane({ data, id }: NodeProps<LaneNode>) {
  const copy = useContext(Copy);
  return <div className={styles.lane}><span>{id.endsWith(':none') ? copy.no_actor : data.label}</span></div>;
}
function Connector({ data, id }: EdgeProps<ProcessEdge>) {
  if (!data) return null;
  const color = data.connection.type === 'success' ? '#137333' : data.connection.type === 'revision' ? '#b42318' : '#205c87';
  const path = data.points.map((point, i) => `${i ? 'L' : 'M'} ${point.x} ${point.y}`).join(' ');
  return <>
    <defs><marker id={`arrow-${id}`} viewBox="0 0 10 10" refX="10" refY="5" markerUnits="userSpaceOnUse" markerWidth="16" markerHeight="16" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill={color} /></marker></defs>
    <path d={path} fill="none" stroke="#fff" strokeWidth={6} strokeLinejoin="round" pointerEvents="none" />
    <BaseEdge id={id} path={path} markerEnd={`url(#arrow-${id})`} style={{ stroke: color, strokeWidth: 2.8, strokeLinejoin: 'round', strokeLinecap: 'round', strokeDasharray: data.connection.type === 'revision' ? '7 4' : undefined }} />
  </>;
}
function ConnectionLabel({ data }: NodeProps<ConnectionLabelNode>) {
  const color = data.connection.type === 'success' ? '#137333' : data.connection.type === 'revision' ? '#b42318' : '#205c87';
  return <span className={styles.edgeLabel} data-connection-type={data.connection.type} style={{ color }}>{data.connection.label}</span>;
}
const nodeTypes = { activity: Activity, stage: Stage, lane: Lane, connectionLabel: ConnectionLabel };
const edgeTypes = { process: Connector };

function Diagram({ process }: { process: AssessmentProcess }) {
  const copy = useContext(Copy);
  const typeLabels: Record<StepType, string> = { normal: copy.normal, decision: copy.decision, outcome: copy.outcome, document: copy.document, start: copy.start, end: copy.end };
  const flow = useReactFlow<ProcessNode, ProcessEdge>();
  const initialView = useRef(false);
  const container = useRef<HTMLDivElement>(null), measurement = useRef<HTMLDivElement>(null), detail = useRef<HTMLElement>(null), lastButton = useRef<HTMLButtonElement>(null);
  const [graph, setGraph] = useState<{ nodes: ProcessNode[]; edges: ProcessEdge[] }>({ nodes: [], edges: [] });
  const [error, setError] = useState(false), [fullscreen, setFullscreen] = useState(false), [supportsFullscreen, setSupportsFullscreen] = useState(() => typeof document !== 'undefined' && !!document.fullscreenEnabled);
  const [actor, setActor] = useState(''), [stage, setStage] = useState(''), [selected, setSelected] = useState<number | null>(null);
  const steps = process.stages.flatMap(s => s.steps.map(step => ({ step, stage: s, actor: process.actors.find(a => a.id === step.actorId) ?? null })));
  const selection = steps.find(item => item.step.id === selected);
  const reset = useCallback(() => {
    const canvas = container.current?.querySelector<HTMLElement>(`.${styles.canvas}`);
    const width = canvas?.clientWidth ?? 375;
    const roots = flow.getNodes().filter(node => !node.parentId);
    const extent = Math.max(1, ...roots.map(node => node.position.x + (node.width ?? 0)));
    const zoom = width < 768 ? 1 : Math.max(.65, Math.min(1, (width - 48) / extent));
    void flow.setViewport({ x: width < 768 ? 0 : Math.max(0, (width - extent * zoom) / 2), y: 80, zoom });
  }, [flow]);
  const closeDetail = useCallback(() => { setSelected(null); lastButton.current?.focus({ preventScroll: true }); }, []);
  useEffect(() => {
    const changed = () => { setFullscreen(document.fullscreenElement === container.current); };
    document.addEventListener('fullscreenchange', changed);
    return () => document.removeEventListener('fullscreenchange', changed);
  }, []);
  useEffect(() => {
    if (selected !== null) detail.current?.focus({ preventScroll: true });
  }, [selected]);
  useEffect(() => {
    let cancelled = false;
    const sizes = new Map<string, NodeSize>();
    const measure = () => {
      measurement.current?.querySelectorAll<HTMLElement>('[data-measure-id]').forEach(element => { sizes.set(element.dataset.measureId!, { width: element.offsetWidth, height: element.offsetHeight }); });
    };
    const layout = async () => {
      await document.fonts.ready;
      if (cancelled) return;
      measure();
      try {
        const result = await layoutAssessmentProcess(process, sizes, 1800);
        if (cancelled) return;
        setGraph(result); setError(false);
        if (result.nodes.length && !initialView.current) requestAnimationFrame(() => { if (!cancelled) { reset(); initialView.current = true; } });
      } catch { if (!cancelled) setError(true); }
    };
    void layout();
    return () => { cancelled = true; };
  }, [process, reset]);
  const toggleFullscreen = async () => {
    try { if (document.fullscreenElement === container.current) await document.exitFullscreen(); else await container.current?.requestFullscreen(); }
    catch { setSupportsFullscreen(false); }
  };
  return <div ref={container} className={styles.diagram} data-testid="assessment-diagram" onKeyDown={event => { if (event.key === 'Escape' && selection) { event.stopPropagation(); closeDetail(); } }}>
    <div className={styles.filters}>
      <label>{copy.actor}<select aria-label={copy.actor_filter} value={actor} onChange={event => setActor(event.target.value)}><option value="">{copy.all_actors}</option>{process.actors.map(a => <option key={a.id} value={a.id}>{a.label}</option>)}<option value="none">{copy.no_actor}</option></select></label>
      <label>{copy.stage}<select aria-label={copy.stage_filter} value={stage} onChange={event => setStage(event.target.value)}><option value="">{copy.all_stages}</option>{process.stages.map(s => <option key={s.id} value={s.id}>{s.title}</option>)}</select></label>
      <p>{copy.hint}</p>
    </div>
    <div className={`${styles.workspace} ${selection ? styles.withDetail : ''}`}>
      <div className={styles.canvas}>
        <div className={styles.controls} aria-label={copy.controls}>
          <button type="button" aria-label={copy.zoom_out} onClick={() => flow.zoomOut()}><MinusIcon /></button>
          <button type="button" aria-label={copy.zoom_in} onClick={() => flow.zoomIn()}><PlusIcon /></button>
          <button type="button" aria-label={copy.reset} onClick={reset}><ArrowPathIcon /></button>
          <button type="button" aria-label={copy.fit_label} onClick={() => flow.fitView({ padding: { top: '110px', bottom: '24px', left: '24px', right: '24px' }, minZoom: .08, maxZoom: 1 })}><ArrowsPointingOutIcon /><span>{copy.fit}</span></button>
          {supportsFullscreen && <button type="button" aria-label={fullscreen ? copy.exit_fullscreen : copy.fullscreen} onClick={toggleFullscreen}>{fullscreen ? <ArrowsPointingInIcon /> : <ArrowsPointingOutIcon />}</button>}
        </div>
        {error ? <p className={styles.notice} role="alert">{copy.error}</p> : !graph.nodes.length ? <p className={styles.notice} role="status">{process.stages.length ? copy.layout_loading : copy.no_stages}</p> : <Interaction value={{ actor, stage, selected, select: (id, button) => { lastButton.current = button; setSelected(id); } }}>
          <ReactFlow nodes={graph.nodes} edges={graph.edges} nodeTypes={nodeTypes} edgeTypes={edgeTypes} minZoom={.08} maxZoom={2.5}
            nodesDraggable={false} nodesConnectable={false} elementsSelectable={false} nodesFocusable={false} edgesFocusable={false} deleteKeyCode={null}
            zoomOnScroll={false} zoomOnDoubleClick={false} preventScrolling={false} aria-label={copy.diagram_label} />
        </Interaction>}
      </div>
      {selection && <aside ref={detail} className={styles.detail} role="region" aria-label={copy.detail_label} tabIndex={-1}>
        <button type="button" aria-label={copy.close_detail} className={styles.close} onClick={closeDetail}><XMarkIcon /></button>
        <h4>{selection.step.title}</h4>
        <dl><dt>{copy.stage}</dt><dd>{selection.stage.title}</dd><dt>{copy.actor}</dt><dd>{selection.actor?.label ?? copy.no_actor}</dd><dt>{copy.type}</dt><dd>{typeLabels[selection.step.type]}</dd><dt>{copy.description}</dt><dd>{selection.step.description || copy.no_description}</dd>{selection.step.documentReference && <><dt>{copy.related_document}</dt><dd>{selection.step.documentReference}</dd></>}{selection.step.informationUrl && <><dt>{copy.related_information}</dt><dd><a href={selection.step.informationUrl} target="_blank" rel="noopener noreferrer">{copy.open_information}</a></dd></>}</dl>
      </aside>}
    </div>
    <div ref={measurement} className={styles.measurement} aria-hidden="true" inert>
      {process.stages.map((s, i) => <div key={s.id} data-measure-id={`stage:${s.id}`} className={styles.stage} style={{ width: 224, height: 'auto' }}><StageHeader stage={s} number={i + 1} /></div>)}
      {process.stages.flatMap(s => [...new Set(s.steps.map(step => step.actorId))].map(id => <div key={`${s.id}:${id}`} className={styles.lane} data-measure-id={`actor:${s.id}:${id ?? 'none'}`} style={{ width: 208, height: 'auto', paddingBottom: 10 }}><span>{process.actors.find(a => a.id === id)?.label ?? copy.no_actor}</span></div>))}
      {process.connections.filter(e => e.label).map(e => <span key={e.id} className={styles.edgeLabel} data-measure-id={`connection:${e.id}`} style={{ width: 'max-content', height: 'auto', maxWidth: 180 }}>{e.label}</span>)}
      {steps.map(({ step }) => <div key={step.id} data-measure-id={`step:${step.id}`} className={styles.card}><CardContent step={step} /></div>)}</div>
  </div>;
}
export default function AssessmentFlow({ process, copy = defaultInformationContent.flow }: { process: AssessmentProcess; copy?: InformationContent['flow'] }) { return <Copy value={copy}><ReactFlowProvider><Diagram process={process} /></ReactFlowProvider></Copy>; }

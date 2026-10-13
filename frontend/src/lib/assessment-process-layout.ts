import ELK from 'elkjs/lib/elk.bundled.js';
import type { ElkNode } from 'elkjs/lib/elk-api';
import type { Node, Edge } from '@xyflow/react';
import type { AssessmentProcess, ProcessActor, ProcessStage, ProcessStep, ProcessConnection } from './assessment-process';

export type Point = { x: number; y: number };
export type NodeSize = { width: number; height: number };
export type ActivityData = { step: ProcessStep; stage: ProcessStage; actor: ProcessActor | null; status: 'normal' | 'success' | 'revision' };
export type ActivityNode = Node<ActivityData, 'activity'>;
export type StageNode = Node<{ stage: ProcessStage; number: number }, 'stage'>;
export type LaneNode = Node<{ label: string }, 'lane'>;
export type ConnectionLabelNode = Node<{ connection: ProcessConnection }, 'connectionLabel'>;
export type ProcessNode = ActivityNode | StageNode | LaneNode | ConnectionLabelNode;
export type ProcessEdge = Edge<{ points: Point[]; connection: ProcessConnection; labelPoint: Point }, 'process'>;
type Rect = Point & NodeSize;
const cardWidth = 184;
const padding = 20;
const header = 80;
const gutter = 64;

function blocked(a: Point, b: Point, rectangles: Rect[]) {
  return rectangles.some(r => a.x === b.x
    ? a.x > r.x + .1 && a.x < r.x + r.width - .1 && Math.max(a.y, b.y) > r.y + .1 && Math.min(a.y, b.y) < r.y + r.height - .1
    : a.y > r.y + .1 && a.y < r.y + r.height - .1 && Math.max(a.x, b.x) > r.x + .1 && Math.min(a.x, b.x) < r.x + r.width - .1);
}
function simplify(points: Point[]): Point[] {
  return points.filter((p, i) => i === 0 || i === points.length - 1 || !((points[i - 1].x === p.x && p.x === points[i + 1].x) || (points[i - 1].y === p.y && p.y === points[i + 1].y)));
}
// ponytail: visibility grid is quadratic in card count; use a routing worker if large processes become slow.
function route(start: Point, end: Point, rectangles: Rect[], occupied: Point[][] = []): Point[] {
  const xs = [...new Set([start.x, end.x, ...rectangles.flatMap(r => [r.x - 14, r.x + r.width + 14])])].sort((a, b) => a - b);
  const ys = [...new Set([start.y, end.y, ...rectangles.flatMap(r => [r.y - 14, r.y + r.height + 14])])].sort((a, b) => a - b);
  const width = xs.length;
  const origin = ys.indexOf(start.y) * width + xs.indexOf(start.x);
  const target = ys.indexOf(end.y) * width + xs.indexOf(end.x);
  const point = (id: number): Point => ({ x: xs[id % width], y: ys[Math.floor(id / width)] });
  const distance = (a: Point, b: Point) => Math.abs(a.x - b.x) + Math.abs(a.y - b.y);
  const cost = new Map([[origin, 0]]);
  const previous = new Map<number, number>();
  const open = new Set([origin]);
  while (open.size) {
    let current = -1;
    let best = Infinity;
    for (const id of open) {
      const score = cost.get(id)! + distance(point(id), end);
      if (score < best) {
        best = score;
        current = id;
      }
    }
    if (current === target) {
      const ids = [target];
      while (ids[ids.length - 1] !== origin) ids.push(previous.get(ids[ids.length - 1])!);
      return simplify(ids.reverse().map(point));
    }
    open.delete(current);
    const x = current % width;
    const y = Math.floor(current / width);
    const a = point(current);
    const neighbors = [x ? current - 1 : -1, x < width - 1 ? current + 1 : -1, y ? current - width : -1, y < ys.length - 1 ? current + width : -1];
    for (const next of neighbors) {
      if (next < 0 || blocked(a, point(next), rectangles)) continue;
      const b = point(next);
      const overlap = occupied.reduce((sum, segment) => {
        const [c, d] = segment;
        if (a.x === b.x && c.x === d.x && a.x === c.x) return sum + Math.max(0, Math.min(Math.max(a.y,b.y),Math.max(c.y,d.y)) - Math.max(Math.min(a.y,b.y),Math.min(c.y,d.y)));
        if (a.y === b.y && c.y === d.y && a.y === c.y) return sum + Math.max(0, Math.min(Math.max(a.x,b.x),Math.max(c.x,d.x)) - Math.max(Math.min(a.x,b.x),Math.min(c.x,d.x)));
        return sum;
      }, 0);
      const before = previous.has(current) ? point(previous.get(current)!) : null;
      const bend = before && ((before.x === a.x) !== (a.x === b.x)) ? 16 : 0;
      const candidate = cost.get(current)! + distance(a, b) + overlap * .2 + bend;
      if (candidate < (cost.get(next) ?? Infinity)) {
        cost.set(next, candidate);
        previous.set(next, current);
        open.add(next);
      }
    }
  }
  throw new Error('Cannot route process connector');
}
function intersects(a: Rect, b: Rect): boolean {
  return a.x < b.x + b.width && a.x + a.width > b.x && a.y < b.y + b.height && a.y + a.height > b.y;
}
function labelPosition(points: Point[], size: NodeSize, obstacles: Rect[]): Point | null {
  const candidates = points.slice(1).flatMap((point, i) => [.5, .25, .75].map(ratio => ({ x: points[i].x + (point.x - points[i].x) * ratio, y: points[i].y + (point.y - points[i].y) * ratio })));
  return candidates.find(p => !obstacles.some(r => intersects({ x: p.x - size.width / 2 - 6, y: p.y - size.height / 2 - 6, width: size.width + 12, height: size.height + 12 }, r))) ?? null;
}

export async function layoutAssessmentProcess(process: AssessmentProcess, sizes = new Map<string, NodeSize>(), compositionWidth = 1440): Promise<{ nodes: ProcessNode[]; edges: ProcessEdge[] }> {
  if (!process.stages.length) return { nodes: [], edges: [] };
  const elk = new ELK();
  const actors = new Map(process.actors.map(actor => [actor.id, actor]));
  const stages = [...process.stages].sort((a, b) => a.order - b.order || a.id - b.id);
  const layouts = await Promise.all(stages.map(async stage => {
    const headerHeight = Math.max(header, sizes.get(`stage:${stage.id}`)?.height ?? header);
    const actorIds = [...new Set(stage.steps.map(s => s.actorId))].sort((a, b) => a === null ? 1 : b === null ? -1 : actors.get(a)!.order - actors.get(b)!.order || a - b);
    const stageIds = new Set(stage.steps.map(step => step.id));
    const outgoing = new Map(stage.steps.map(step => [step.id, process.connections.filter(edge => edge.sourceStepId === step.id).map(edge => edge.targetStepId)]));
    const lanes = await Promise.all(actorIds.map(async (actorId): Promise<ElkNode> => {
      const laneSteps = stage.steps.filter(s => s.actorId === actorId).sort((a, b) => a.order - b.order || a.id - b.id);
      const laneIds = new Set(laneSteps.map(s => s.id));
      // Jalur lewat pelaksana lain hanya membantu posisi kartu; panah akhir tetap berasal dari API.
      const projected = new Map<string, { source: number; target: number }>();
      for (const source of laneIds) {
        const visited = new Set([source]);
        const queue = [...(outgoing.get(source) ?? [])];
        while (queue.length) {
          const target = queue.shift()!;
          if (visited.has(target) || !stageIds.has(target)) continue;
          visited.add(target);
          if (laneIds.has(target)) projected.set(`${source}:${target}`, { source, target });
          else queue.push(...(outgoing.get(target) ?? []));
        }
      }
      return elk.layout({
        id: `actor:${stage.id}:${actorId ?? 'none'}`, layoutOptions: {
          'elk.algorithm': 'layered', 'elk.direction': 'DOWN', 'elk.edgeRouting': 'ORTHOGONAL',
          'elk.spacing.nodeNode': '32', 'elk.layered.spacing.nodeNodeBetweenLayers': String(Math.max(32, ...process.connections.filter(e => e.label).map(e => (sizes.get(`connection:${e.id}`)?.height ?? 24) + 24))),
          'elk.layered.considerModelOrder.strategy': 'NODES_AND_EDGES',
          'elk.padding': `[top=${Math.max(60, (sizes.get(`actor:${stage.id}:${actorId ?? 'none'}`)?.height ?? 28) + 32)},left=12,bottom=12,right=12]`,
        },
        children: laneSteps.map(step => {
          const size = sizes.get(`step:${step.id}`) ?? { width: cardWidth, height: 110 };
          return { id: `step:${step.id}`, ...size, layoutOptions: { 'elk.portConstraints': 'FIXED_SIDE' }, ports: [
            { id: `step:${step.id}:in`, layoutOptions: { 'elk.port.side': 'NORTH' } },
            { id: `step:${step.id}:out`, layoutOptions: { 'elk.port.side': 'SOUTH' } },
          ] };
        }),
        edges: [...projected].map(([id, e]) => ({ id: `layout:${id}`, sources: [`step:${e.source}:out`], targets: [`step:${e.target}:in`] })),
      });
    }));
    let laneX = padding;
    for (const lane of lanes) {
      lane.x = laneX;
      lane.y = headerHeight;
      laneX += (lane.width ?? 208) + 24;
    }
    const graph: ElkNode = { id: `stage:${stage.id}`, children: lanes, width: lanes.length ? laneX - 24 + padding : 224, height: headerHeight + Math.max(40, ...lanes.map(lane => lane.height ?? 0)) + padding };
    return { stage, headerHeight, graph };
  }));
  const budget = Math.max(500, compositionWidth);
  const rows: (typeof layouts)[] = [];
  let row: typeof layouts = [];
  let used = 0;
  for (const item of layouts) {
    const width = Math.max(224, item.graph.width ?? 224);
    if (row.length && used + gutter + width > budget) {
      rows.push(row);
      row = [];
      used = 0;
    }
    used += (row.length ? gutter : 0) + width;
    row.push(item);
  }
  if (row.length) rows.push(row);
  const totalWidth = Math.max(...rows.map(r => r.reduce((sum, item) => sum + Math.max(224, item.graph.width ?? 224), 0) + gutter * (r.length - 1)));
  const origins = new Map<number, Point>();
  let y = 24;
  // Baris bergantian arah agar urutan tahap mengikuti alur saat diagram membungkus ke bawah.
  rows.forEach((items, index) => {
    let x = index % 2 ? totalWidth + 24 : 24;
    for (const item of items) {
      const width = Math.max(224, item.graph.width ?? 224);
      if (index % 2) x -= width;
      origins.set(item.stage.id, { x, y });
      x += index % 2 ? -gutter : width + gutter;
    }
    y += Math.max(...items.map(item => item.graph.height ?? 140)) + gutter + 40;
  });
  const nodes: ProcessNode[] = [];
  const rectangles: Rect[] = [];
  const stageRects = new Map<number, Rect>();
  const positions = new Map<number, Rect>();
  const stepStages = new Map<number, number>();
  layouts.forEach(({ stage, graph, headerHeight }, stageIndex) => {
    const origin = origins.get(stage.id)!;
    const width = Math.max(224, graph.width ?? 224);
    const height = graph.height ?? 140;
    const stageRect = { ...origin, width, height };
    stageRects.set(stage.id, stageRect);
    nodes.push({ id: `stage:${stage.id}`, type: 'stage', position: origin, width, height, data: { stage, number: stageIndex + 1 }, selectable: false, focusable: false });
    rectangles.push({ ...origin, width, height: headerHeight - 16 });
    const steps = new Map(stage.steps.map(step => [step.id, step]));
    for (const lane of graph.children ?? []) {
      const lanePosition = { x: lane.x ?? 0, y: lane.y ?? 0 };
      const laneActor = lane.id.endsWith(':none') ? null : actors.get(Number(lane.id.split(':')[2]))!;
      nodes.push({ id: lane.id, type: 'lane', parentId: `stage:${stage.id}`, position: lanePosition, width: lane.width, height: lane.height, data: { label: laneActor?.label ?? 'Tanpa pelaksana' }, selectable: false, focusable: false });
      rectangles.push({ x: origin.x + lanePosition.x, y: origin.y + lanePosition.y, width: lane.width ?? cardWidth, height: Math.max(28, sizes.get(lane.id)?.height ?? 28) });
      for (const child of lane.children ?? []) {
        const step = steps.get(Number(child.id.split(':')[1]))!;
        const position = { x: child.x ?? 0, y: child.y ?? 0 };
        const absolute = { x: origin.x + lanePosition.x + position.x, y: origin.y + lanePosition.y + position.y, width: child.width!, height: child.height! };
        positions.set(step.id, absolute);
        stepStages.set(step.id, stage.id);
        rectangles.push(absolute);
        const inbound = process.connections.filter(e => e.targetStepId === step.id);
        const status = step.type === 'outcome' ? inbound.find(e => e.type !== 'normal')?.type ?? 'normal' : 'normal';
        nodes.push({ id: child.id, type: 'activity', parentId: lane.id, position, width: child.width, height: child.height, data: { step, stage, actor: laneActor ?? null, status }, ariaLabel: `${step.title}, ${laneActor?.label ?? 'Tanpa pelaksana'}, tahap ${stage.title}` });
      }
    }
  });
  const stepsById = new Map(process.stages.flatMap(stage => stage.steps.map(step => [step.id, step] as const)));
  const occupied: Point[][] = [];
  const labelRects: Rect[] = [];
  const parallel = new Map<string, number>();
  const edges: ProcessEdge[] = process.connections.map(connection => {
    const source = positions.get(connection.sourceStepId)!;
    const target = positions.get(connection.targetStepId)!;
    const sourceStage = stepStages.get(connection.sourceStepId)!;
    const targetStage = stepStages.get(connection.targetStepId)!;
    const sourceStep = stepsById.get(connection.sourceStepId)!;
    const targetStep = stepsById.get(connection.targetStepId)!;
    const horizontal = sourceStage === targetStage ? sourceStep.actorId !== targetStep.actorId : origins.get(sourceStage)!.y === origins.get(targetStage)!.y;
    const forward = target.x + target.width / 2 > source.x + source.width / 2;
    const sourceSide = horizontal ? (forward ? 'right' : 'left') : target.y > source.y ? 'bottom' : 'right';
    const targetSide = horizontal ? (forward ? 'left' : 'right') : target.y > source.y ? 'top' : 'right';
    const port = (r: Rect, side: string, output: boolean): Point => ({ x: side === 'left' ? r.x : side === 'right' ? r.x + r.width : r.x + r.width / 2, y: side === 'top' ? r.y : side === 'bottom' ? r.y + r.height : r.y + r.height * (output ? .65 : .35) });
    const start = port(source, sourceSide, true);
    const end = port(target, targetSide, false);
    const clearance = horizontal ? Math.min(24, Math.max(10, Math.abs(end.x - start.x) / 3)) : Math.min(24, Math.max(10, Math.abs(end.y - start.y) / 3));
    const stub = (p: Point, side: string): Point => ({ x: p.x + (side === 'left' ? -clearance : side === 'right' ? clearance : 0), y: p.y + (side === 'top' ? -clearance : side === 'bottom' ? clearance : 0) });
    const departure = stub(start, sourceSide);
    const approach = stub(end, targetSide);

    const obstacles = sourceStage === targetStage ? rectangles : [...rectangles, ...[...stageRects].filter(([id]) => id !== sourceStage && id !== targetStage).map(([,r]) => r)];
    const clearObstacles = obstacles.map(r => ({x:r.x-6,y:r.y-6,width:r.width+12,height:r.height+12}));
    const routeLabels = labelRects.filter(r => ![departure, approach].some(p => p.x >= r.x && p.x <= r.x+r.width && p.y >= r.y && p.y <= r.y+r.height));
    const connect = (via?: Point) => simplify([start, ... (via ? [...route(departure, via, [...clearObstacles, ...routeLabels], occupied), ...route(via, approach, [...clearObstacles, ...routeLabels], occupied).slice(1)] : route(departure, approach, clearObstacles, occupied)), end]);
    const key = `${connection.sourceStepId}:${connection.targetStepId}`;
    const index = parallel.get(key) ?? 0;
    parallel.set(key, index + 1);
    const size = sizes.get(`connection:${connection.id}`) ?? { width: Math.min(180, (connection.label?.length ?? 0) * 7 + 12), height: 24 };
    let points = connect();
    let center = labelPosition(points, size, [...rectangles, ...labelRects]);
    if (index || (connection.label && !center)) {
      const x = Math.max(...rectangles.map(r => r.x + r.width), ...labelRects.map(r => r.x + r.width)) + size.width / 2 + 32 + index * 32;
      center = { x, y: (start.y + end.y) / 2 };
      points = connect(center);
    }
    center ??= { x: start.x, y: start.y };
    if (connection.label) {
      const position = { x: center.x - size.width / 2, y: center.y - size.height / 2 };
      labelRects.push({ ...position, ...size });
      nodes.push({ id: `label:${connection.id}`, type: 'connectionLabel', position, ...size, data: { connection }, selectable: false, focusable: false, zIndex: 2 });
    }
    points.slice(1).forEach((p, i) => occupied.push([points[i], p]));
    return { id: `connection:${connection.id}`, source: `step:${connection.sourceStepId}`, target: `step:${connection.targetStepId}`, sourceHandle: `out-${sourceSide}`, targetHandle: `in-${targetSide}`, type: 'process', data: { points, connection, labelPoint: center }, focusable: false, selectable: false };
  });
  return { nodes, edges };
}

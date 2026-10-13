import { graphlib, layout } from '@dagrejs/dagre';
import { organizationMembers, type OrganizationBranch } from './organization.ts';
import { crossesRectangle, routeOrthogonal, simplifyPath, type Point } from './organization-routing.ts';
import type { Node, Edge } from '@xyflow/react';

export type OrganizationCard = Node<{ position: OrganizationBranch; layoutHeight: number }, 'position'>;

export function layoutOrganization(roots: OrganizationBranch[], heights = new Map<string, number>()): { nodes: OrganizationCard[]; edges: Edge[] } {
  const graph = new graphlib.Graph().setGraph({ rankdir: 'TB', nodesep: 32, ranksep: 64, marginx: 24, marginy: 24 }).setDefaultEdgeLabel(() => ({}));
  const nodes: OrganizationCard[] = [];
  const edges: Edge[] = [];
  const constraints: { left: string; right: string }[] = [];
  function order(siblings: OrganizationBranch[]) {
    for (let i = 1; i < siblings.length; i++) constraints.push({ left: String(siblings[i - 1].id), right: String(siblings[i].id) });
  }
  function visit(branch: OrganizationBranch) {
    const width = 280;
    const id = String(branch.id);
    const height = heights.get(id) ?? Math.max(132, 86 + organizationMembers(branch).length * 28);
    graph.setNode(id, { width, height });
    nodes.push({ id, type: 'position', position: { x: 0, y: 0 }, width, data: { position: branch, layoutHeight: height }, ariaLabel: `${branch.title}: ${organizationMembers(branch).map(member => member.person_name + (member.credentials ? ', ' + member.credentials : '')).join('; ')}` });
    order(branch.children);
    for (const child of branch.children) {
      visit(child);
      graph.setEdge(id, String(child.id));
      edges.push({ id: `${id}-${child.id}`, source: id, target: String(child.id), type: 'step', style: { stroke: '#91a5b2', strokeWidth: 1.5 }, focusable: false });
    }
  }
  for (const root of roots) visit(root);
  order(roots);
  layout(graph, { constraints });
  for (const node of nodes) {
    const point = graph.node(node.id);
    node.position = { x: point.x - point.width / 2, y: point.y - point.height / 2 };
  }
  const hasGrid = nodes.some(node => node.data.position.layout_row != null);
  if (hasGrid) placeGrid(nodes);
  const byId = new Map(nodes.map(node => [node.id, node]));
  const rectangles = nodes.map(node => ({ ...node.position, width: node.width!, height: node.data.layoutHeight }));
  for (const edge of edges) {
    const source = byId.get(edge.source)!, target = byId.get(edge.target)!;
    const siblings = source.data.position.children.map(child => byId.get(String(child.id))!);
    const staggered = hasGrid && siblings.length > 1 && new Set(siblings.map(child => child.position.y)).size > 1;
    const departmentBus = hasGrid && siblings.length > 1 && new Set(siblings.map(child => child.position.y)).size === 1 && target.position.y > source.position.y + source.data.layoutHeight + 100;
    edge.sourceHandle = departmentBus ? 'out-right' : 'out-bottom';
    edge.targetHandle = 'in-top';
    const start: Point = departmentBus
      ? { x: source.position.x + source.width!, y: source.position.y + source.data.layoutHeight / 2 }
      : { x: source.position.x + source.width! / 2, y: source.position.y + source.data.layoutHeight };
    const end: Point = { x: target.position.x + target.width! / 2, y: target.position.y };
    const exit = departmentBus ? { x: start.x + 16, y: start.y } : { x: start.x, y: start.y + 20 };
    const entry = { x: end.x, y: end.y - 32 };
    let path: Point[];
    const bus = [{ x: exit.x, y: entry.y }, entry];
    const trunk = [exit, { x: start.x, y: entry.y }, entry];
    if (staggered && trunk.slice(1).every((point, i) => !rectangles.some(box => crossesRectangle(trunk[i], point, { x: box.x - 12, y: box.y - 12, width: box.width + 24, height: box.height + 24 })))) {
      edge.type = 'organization';
      edge.data = { points: simplifyPath([start, ...trunk, end]) };
      continue;
    }
    if (departmentBus && [exit, ...bus].slice(1).every((p, i) => !rectangles.some(box => crossesRectangle([exit, ...bus][i], p, { x: box.x - 12, y: box.y - 12, width: box.width + 24, height: box.height + 24 })))) {
      path = [exit, ...bus];
    } else {
      path = routeOrthogonal(exit, entry, rectangles);
    }
    edge.type = 'organization';
    edge.data = { points: simplifyPath([start, ...path, end]) };
  }
  return { nodes, edges };
}

function placeGrid(nodes: OrganizationCard[]): void {
  const slots = new Map<string, { row: number; column: number }>();
  const occupied = new Set<string>();
  for (const node of nodes) {
    const { layout_row: row, layout_column: column } = node.data.position;
    if (row != null && column != null) {
      slots.set(node.id, { row, column });
      occupied.add(`${row}:${column}`);
    }
  }
  const siblings = new Map<number | null, OrganizationCard[]>();
  for (const node of nodes) {
    const parent = node.data.position.parent_id;
    siblings.set(parent, [...(siblings.get(parent) ?? []), node]);
  }
  const lastAutomaticColumn = new Map<number | null, number>();
  for (const node of nodes) {
    if (slots.has(node.id)) continue;
    const parent = slots.get(String(node.data.position.parent_id));
    const row = (parent?.row ?? 0) + 1;
    const group = siblings.get(node.data.position.parent_id)!;
    const preferred = Math.max((parent?.column ?? 5) + 2 * group.indexOf(node) - (group.length - 1), (lastAutomaticColumn.get(node.data.position.parent_id) ?? -Infinity) + 1);
    let column = preferred;
    for (let offset = 0; occupied.has(`${row}:${column}`); offset++) {
      column = preferred + offset + 1;
    }
    slots.set(node.id, { row, column });
    occupied.add(`${row}:${column}`);
    lastAutomaticColumn.set(node.data.position.parent_id, column);
  }
  const rowHeights = new Map<number, number>();
  const columnsByRow = new Map<number, number[]>();
  const nodesByRow = new Map<number, OrganizationCard[]>();
  for (const node of nodes) {
    const slot = slots.get(node.id)!;
    nodesByRow.set(slot.row, [...(nodesByRow.get(slot.row) ?? []), node]);
    rowHeights.set(slot.row, Math.max(rowHeights.get(slot.row) ?? 0, node.data.layoutHeight));
    columnsByRow.set(slot.row, [...(columnsByRow.get(slot.row) ?? []), slot.column]);
  }
  let pitch = 200;
  for (const columns of columnsByRow.values()) {
    columns.sort((a, b) => a - b);
    for (let i = 1; i < columns.length; i++) pitch = Math.max(pitch, 312 / (columns[i] - columns[i - 1]));
  }
  const minColumn = Math.min(...Array.from(slots.values(), slot => slot.column));
  const rowY = new Map<number, number>();
  let y = 24;
  const maxRow = Math.max(...rowHeights.keys());
  for (let row = 1; row <= maxRow; row++) {
    rowY.set(row, y);
    const top = nodesByRow.get(row), middle = nodesByRow.get(row + 1), bottom = nodesByRow.get(row + 2);
    if (top?.length === 1 && middle?.length === 1 && bottom?.length === 1) {
      const a = slots.get(top[0].id)!, b = slots.get(middle[0].id)!, c = slots.get(bottom[0].id)!;
      const sameParent = top[0].data.position.parent_id === middle[0].data.position.parent_id && top[0].data.position.parent_id === bottom[0].data.position.parent_id;
      const bottomY = y + top[0].data.layoutHeight + 48;
      const middleY = (y + top[0].data.layoutHeight / 2 + bottomY + bottom[0].data.layoutHeight / 2) / 2 - middle[0].data.layoutHeight / 2;
      if (sameParent && a.column === c.column && Math.abs(a.column - b.column) * pitch >= 312 && middleY >= y) {
        rowY.set(row + 1, middleY);
        rowY.set(row + 2, bottomY);
        y = Math.max(bottomY + bottom[0].data.layoutHeight, middleY + middle[0].data.layoutHeight) + 48;
        row += 2;
        continue;
      }
    }
    y += (rowHeights.get(row) ?? 64) + 48;
  }
  for (const node of nodes) {
    const slot = slots.get(node.id)!;
    node.position = { x: 24 + (slot.column - minColumn) * pitch, y: rowY.get(slot.row)! };
  }
}

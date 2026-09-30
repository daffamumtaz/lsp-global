import { graphlib, layout } from '@dagrejs/dagre';
import type { OrganizationBranch } from './organization';
import type { Node, Edge } from '@xyflow/react';

export type OrganizationCard = Node<{ position: OrganizationBranch }, 'position'>;

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
    const height = heights.get(id) ?? 132;
    graph.setNode(id, { width, height });
    nodes.push({ id, type: 'position', position: { x: 0, y: 0 }, width, data: { position: branch }, ariaLabel: `${branch.title}: ${branch.person_name}${branch.credentials ? ', ' + branch.credentials : ''}` });
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
  return { nodes, edges };
}

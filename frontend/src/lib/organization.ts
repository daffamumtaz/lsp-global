export type OrganizationNode = {
  id: number;
  parent_id: number | null;
  order: number;
  title: string;
  name: string;
};

export const NODE_WIDTH = 216;
export const NODE_HEIGHT = 132;
const GAP_X = 28;
const GAP_Y = 70;
const PADDING = 32;

export type PositionedNode = OrganizationNode & { x: number; y: number };

export function layoutOrganization(nodes: OrganizationNode[]) {
  if (nodes.length > 100) throw new Error("Organization exceeds 100 nodes");
  const byId = new Map(nodes.map(node => [node.id, node]));
  if (byId.size !== nodes.length) throw new Error("Duplicate organization ID");
  const children = new Map<number | null, OrganizationNode[]>();

  for (const node of nodes) {
    if (!Number.isInteger(node.id) || node.id < 1 || !Number.isInteger(node.order) || node.order < 1 ||
      typeof node.title !== "string" || typeof node.name !== "string" ||
      (node.parent_id !== null && !byId.has(node.parent_id))) {
      throw new Error("Invalid organization node");
    }
    const seen = new Set([node.id]);
    let parent = node.parent_id;
    while (parent !== null) {
      if (seen.has(parent)) throw new Error("Organization contains a cycle");
      seen.add(parent);
      parent = byId.get(parent)!.parent_id;
    }
    children.set(node.parent_id, [...(children.get(node.parent_id) ?? []), node]);
  }
  for (const siblings of children.values()) siblings.sort((a, b) => a.order - b.order || a.id - b.id);

  const widths = new Map<number, number>();
  function measure(node: OrganizationNode): number {
    const descendants = children.get(node.id) ?? [];
    const width = Math.max(NODE_WIDTH, descendants.reduce((sum, child) => sum + measure(child), 0) + Math.max(0, descendants.length - 1) * GAP_X);
    widths.set(node.id, width);
    return width;
  }
  const positioned: PositionedNode[] = [];
  function place(node: OrganizationNode, left: number, depth: number) {
    positioned.push({ ...node, x: left + (widths.get(node.id)! - NODE_WIDTH) / 2, y: PADDING + depth * (NODE_HEIGHT + GAP_Y) });
    for (const child of children.get(node.id) ?? []) {
      place(child, left, depth + 1);
      left += widths.get(child.id)! + GAP_X;
    }
  }
  let left = PADDING;
  for (const root of children.get(null) ?? []) {
    measure(root);
    place(root, left, 0);
    left += widths.get(root.id)! + GAP_X;
  }
  const positions = new Map(positioned.map(node => [node.id, node]));
  const edges = positioned.filter(node => node.parent_id !== null).map(node => {
    const parent = positions.get(node.parent_id!)!;
    const fromX = parent.x + NODE_WIDTH / 2;
    const fromY = parent.y + NODE_HEIGHT;
    const toX = node.x + NODE_WIDTH / 2;
    const midY = fromY + GAP_Y / 2;
    return { id: node.id, path: `M ${fromX} ${fromY} V ${midY} H ${toX} V ${node.y}` };
  });
  return {
    nodes: positioned, edges,
    width: Math.max(NODE_WIDTH + PADDING * 2, left - GAP_X + PADDING),
    height: Math.max(0, ...positioned.map(node => node.y + NODE_HEIGHT)) + PADDING,
  };
}

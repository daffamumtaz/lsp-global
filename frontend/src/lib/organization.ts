export type OrganizationNode = {
  id: number;
  parent_id: number | null;
  title: string;
  person_name: string;
  credentials: string | null;
  order: number;
  is_active: boolean;
};

export type OrganizationBranch = OrganizationNode & { children: OrganizationBranch[] };

export function buildOrganizationTree(nodes: OrganizationNode[]): OrganizationBranch[] {
  if (!Array.isArray(nodes) || nodes.length > 1000) throw new Error('Invalid organization size');
  const byId = new Map(nodes.map(node => [node.id, node]));
  if (byId.size !== nodes.length) throw new Error('Duplicate organization ID');
  const branches = new Map<number, OrganizationBranch>();
  for (const node of nodes) {
    if (!Number.isSafeInteger(node.id) || node.id < 1 || !Number.isSafeInteger(node.order) || node.order < 1 ||
      typeof node.title !== 'string' || !node.title.trim() || node.title.length > 120 ||
      typeof node.person_name !== 'string' || node.person_name.length > 160 ||
      (node.credentials !== null && (typeof node.credentials !== 'string' || node.credentials.length > 160)) ||
      typeof node.is_active !== 'boolean' || (node.parent_id !== null && !byId.has(node.parent_id))) {
      throw new Error('Invalid organization node');
    }
    const seen = new Set([node.id]);
    let parent = node.parent_id;
    let active = node.is_active;
    while (parent !== null) {
      if (seen.has(parent)) throw new Error('Organization contains a cycle');
      seen.add(parent);
      const ancestor = byId.get(parent)!;
      active = active && ancestor.is_active;
      parent = ancestor.parent_id;
    }
    if (active) branches.set(node.id, { ...node, children: [] });
  }
  const roots: OrganizationBranch[] = [];
  for (const branch of branches.values()) {
    if (branch.parent_id === null) roots.push(branch);
    else branches.get(branch.parent_id)!.children.push(branch);
  }
  function sort(siblings: OrganizationBranch[]) {
    siblings.sort((a, b) => a.order - b.order || a.id - b.id);
    for (const branch of siblings) sort(branch.children);
  }
  sort(roots);
  return roots;
}

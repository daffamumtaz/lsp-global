export type OrganizationMember = { person_name: string; credentials: string | null };

export type OrganizationNode = {
  id: number;
  parent_id: number | null;
  title: string;
  person_name: string;
  credentials: string | null;
  order: number;
  is_active: boolean;
  members?: OrganizationMember[];
  layout_row?: number | null;
  layout_column?: number | null;
};

export type OrganizationBranch = OrganizationNode & { children: OrganizationBranch[] };

export function buildOrganizationTree(nodes: OrganizationNode[]): OrganizationBranch[] {
  if (!Array.isArray(nodes) || nodes.length > 1000) throw new Error('Invalid organization size');
  const byId = new Map(nodes.map(node => [node.id, node]));
  if (byId.size !== nodes.length) throw new Error('Duplicate organization ID');
  const slots = new Set<string>();
  const branches = new Map<number, OrganizationBranch>();
  for (const node of nodes) {
    if (!Number.isSafeInteger(node.id) || node.id < 1 || !Number.isSafeInteger(node.order) || node.order < 1 ||
      typeof node.title !== 'string' || !node.title.trim() || node.title.length > 120 ||
      typeof node.person_name !== 'string' || node.person_name.length > 160 ||
      (node.credentials !== null && (typeof node.credentials !== 'string' || node.credentials.length > 160)) ||
      typeof node.is_active !== 'boolean' || (node.parent_id !== null && !byId.has(node.parent_id))) {
      throw new Error('Invalid organization node');
    }
    const members = node.members ?? [{ person_name: node.person_name, credentials: node.credentials }];
    if (!Array.isArray(members) || members.length < 1 || members.length > 100 || members.some(member =>
      !member || typeof member.person_name !== 'string' || !member.person_name.trim() || member.person_name.length > 160 ||
      (member.credentials !== null && (typeof member.credentials !== 'string' || member.credentials.length > 160)))) {
      throw new Error('Invalid organization members');
    }
    const row = node.layout_row ?? null;
    const column = node.layout_column ?? null;
    if ((row === null) !== (column === null) || (row !== null && (!Number.isSafeInteger(row) || row < 1 || row > 1000 || !Number.isSafeInteger(column) || column! < 1 || column! > 9))) {
      throw new Error('Invalid organization position');
    }
    if (row !== null) {
      const slot = `${row}:${column}`;
      if (slots.has(slot)) throw new Error('Duplicate organization position');
      slots.add(slot);
    }
    const seen = new Set([node.id]);
    let parent = node.parent_id;
    let active = node.is_active;
    let distance = 0;
    while (parent !== null) {
      if (seen.has(parent)) throw new Error('Organization contains a cycle');
      seen.add(parent);
      const ancestor = byId.get(parent)!;
      distance++;
      if (row !== null && row < (ancestor.layout_row ?? 1) + distance) throw new Error('Organization position inverts hierarchy');
      active = active && ancestor.is_active;
      parent = ancestor.parent_id;
    }
    if (active) branches.set(node.id, { ...node, members, layout_row: row, layout_column: column, children: [] });
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

export function organizationMembers(node: OrganizationNode): OrganizationMember[] {
  return node.members ?? [{ person_name: node.person_name, credentials: node.credentials }];
}

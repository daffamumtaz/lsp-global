import test from 'node:test';
import assert from 'node:assert/strict';
import { buildOrganizationTree } from './organization.ts';
import { layoutOrganization } from './organization-layout.ts';
const node=(id,parent_id=null,order=1)=>({id,parent_id,order,title:'Jabatan '+id,person_name:'Nama '+id,credentials:null,is_active:true});
test('automatic layout maintains hierarchy, sibling order, and nonoverlapping cards after reparenting',()=>{
  for(const nodes of [[node(1),node(2,1),node(3,1,2),node(4,1,3),node(5,2),node(6,2,2),node(7,2,3),node(8,2,4),node(9,5)], [node(1),node(2,1),node(3,2,2),node(4,1,3),node(5,2),node(6,2,2)]]) {
    const layout=layoutOrganization(buildOrganizationTree(nodes));
    const map=new Map(layout.nodes.map(n=>[Number(n.id),n]));
    assert.equal(layout.edges.length,nodes.length-1);
    for(const n of nodes) if(n.parent_id!==null) assert.ok(map.get(n.parent_id).position.y<map.get(n.id).position.y);
    for(const a of layout.nodes) for(const b of layout.nodes) if(a.id!==b.id) assert.ok(a.position.x+a.width<=b.position.x || b.position.x+b.width<=a.position.x || a.position.y+132<=b.position.y || b.position.y+132<=a.position.y);
    for(const a of nodes) for(const b of nodes) if(a.parent_id===b.parent_id && a.order<b.order) assert.ok(map.get(a.id).position.x<map.get(b.id).position.x);
  }
});

test('uses measured card heights to avoid overlapping long titles',()=>{
  const roots=buildOrganizationTree([node(1),node(2,1)]);
  const result=layoutOrganization(roots,new Map([['1',320],['2',210]]));
  assert.equal(result.nodes[0].height,undefined);
  assert.equal(result.nodes[0].initialHeight,undefined);
  assert.ok(result.nodes[1].position.y>=result.nodes[0].position.y+320);
});

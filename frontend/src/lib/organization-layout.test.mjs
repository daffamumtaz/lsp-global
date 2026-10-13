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


test('explicit grid preserves side positions, measured heights and true reporting lines', () => {
  const nodes = [
    {...node(1),layout_row:1,layout_column:5},
    {...node(2,1),layout_row:2,layout_column:3},
    {...node(3,1,2),layout_row:3,layout_column:7},
    {...node(4,1,3),layout_row:4,layout_column:3},
    ...[1,3,7,9].map((column,index)=>({...node(index+5,2,index+1),layout_row:5,layout_column:column})),
    {...node(9,6),layout_row:6,layout_column:3,members:[{person_name:'A',credentials:null},{person_name:'B',credentials:'M.Kom'}]},
  ];
  const result=layoutOrganization(buildOrganizationTree(nodes),new Map([['4',240],['9',200]]));
  const cards=new Map(result.nodes.map(n=>[Number(n.id),n]));
  assert.ok(cards.get(2).position.x<cards.get(1).position.x);
  assert.ok(cards.get(3).position.x>cards.get(1).position.x);
  assert.equal(cards.get(2).position.x,cards.get(4).position.x);
  assert.equal(new Set([5,6,7,8].map(id=>cards.get(id).position.y)).size,1);
  assert.ok(cards.get(5).position.y>=cards.get(4).position.y+240);
  for(const edge of result.edges) {
    assert.equal(nodes.find(n=>String(n.id)===edge.target).parent_id,Number(edge.source));
    assert.equal(edge.type,'organization');
    assert.ok(edge.data.points.length>=2);
    for(let i=1;i<edge.data.points.length;i++) {
      const a=edge.data.points[i-1],b=edge.data.points[i];
      assert.ok(a.x===b.x || a.y===b.y);
      for(const card of result.nodes) {
        const height=card.data.layoutHeight;
        const intersects=a.x===b.x
          ? a.x>card.position.x && a.x<card.position.x+card.width && Math.max(a.y,b.y)>card.position.y && Math.min(a.y,b.y)<card.position.y+height
          : a.y>card.position.y && a.y<card.position.y+height && Math.max(a.x,b.x)>card.position.x && Math.min(a.x,b.x)<card.position.x+card.width;
        assert.equal(intersects,false,`Edge ${edge.id} crosses card ${card.id}`);
      }
    }
  }
});

test('automatic nodes occupy free slots below parents in a mixed grid', () => {
  const roots=buildOrganizationTree([{...node(1),layout_row:1,layout_column:5},node(2,1),{...node(3,1,2),layout_row:2,layout_column:5},node(4,2)]);
  const result=layoutOrganization(roots);
  const cards=new Map(result.nodes.map(n=>[Number(n.id),n]));
  assert.ok(cards.get(2).position.y>cards.get(1).position.y);
  assert.notDeepEqual(cards.get(2).position,cards.get(3).position);
  assert.ok(cards.get(4).position.y>cards.get(2).position.y);
});

test('reference organization shares a board trunk with top-entry branches and one department bus', () => {
  const rows=[
    {...node(1),layout_row:1,layout_column:4},
    {...node(2,1),layout_row:2,layout_column:3},
    {...node(3,1,2),layout_row:3,layout_column:5},
    {...node(4,1,3),layout_row:4,layout_column:3},
    ...[1,3,5,7].map((column,index)=>({...node(index+5,1,index+4),layout_row:5,layout_column:column})),
  ];
  const result=layoutOrganization(buildOrganizationTree(rows));
  const board=result.nodes.find(n=>n.id==='1');
  const center=board.position.x+board.width/2;
  for(const edge of result.edges){
    assert.equal(edge.sourceHandle,'out-bottom');
    assert.equal(edge.targetHandle,'in-top');
    assert.equal(edge.data.points[0].x,center);
    assert.equal(edge.data.points[1].x,center);
  }
  const departments=result.nodes.filter(n=>Number(n.id)>=5).sort((a,b)=>a.position.x-b.position.x);
  assert.equal(new Set(departments.slice(1).map((n,i)=>n.position.x-departments[i].position.x)).size,1);
  const bus=result.edges.filter(e=>Number(e.target)>=5);
  assert.equal(new Set(bus.map(e=>e.data.points[1].y)).size,1);
  for(const edge of result.edges) for(let i=1;i<edge.data.points.length;i++) {
    const a=edge.data.points[i-1],b=edge.data.points[i];
    for(const card of result.nodes) {
      const intersects=a.x===b.x
        ? a.x>card.position.x&&a.x<card.position.x+card.width&&Math.max(a.y,b.y)>card.position.y&&Math.min(a.y,b.y)<card.position.y+card.data.layoutHeight
        : a.y>card.position.y&&a.y<card.position.y+card.data.layoutHeight&&Math.max(a.x,b.x)>card.position.x&&Math.min(a.x,b.x)<card.position.x+card.width;
      assert.equal(intersects,false,`Edge ${edge.id} crosses ${card.id}`);
    }
  }
});

test('staggered side branches keep consecutive left cards close and center the right card', () => {
  const rows = [
    {...node(1),layout_row:1,layout_column:4},
    {...node(2,1),layout_row:2,layout_column:3},
    {...node(3,1,2),layout_row:3,layout_column:5},
    {...node(4,1,3),layout_row:4,layout_column:3},
    ...[1,3,5,7].map((column,index)=>({...node(index+5,1,index+4),layout_row:5,layout_column:column})),
  ];
  for (const heights of [new Map(), new Map([['2',180],['3',240],['4',220]])]) {
    const { nodes } = layoutOrganization(buildOrganizationTree(rows), heights);
    const cards = new Map(nodes.map(card => [Number(card.id), card]));
    const top = cards.get(2), middle = cards.get(3), bottom = cards.get(4);
    assert.equal(bottom.position.y - top.position.y - top.data.layoutHeight, 48);
    const center = card => card.position.y + card.data.layoutHeight / 2;
    assert.equal(center(middle), (center(top) + center(bottom)) / 2);
    assert.ok(cards.get(5).position.y >= Math.max(bottom.position.y + bottom.data.layoutHeight, middle.position.y + middle.data.layoutHeight) + 48);
  }
});

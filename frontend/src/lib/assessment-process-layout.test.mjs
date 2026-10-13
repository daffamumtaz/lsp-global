import test from 'node:test';
import assert from 'node:assert/strict';
import { layoutAssessmentProcess } from './assessment-process-layout.ts';
const step=(id,actorId=null,order=id)=>({id,actorId,order,title:`Aktivitas ${id}`,description:null,type:'normal',isActive:true,documentReference:null,informationUrl:null});
const stage=(id,steps)=>({id,order:id,title:`Tahap ${id}`,description:null,isActive:true,steps});
const edge=(id,sourceStepId,targetStepId,type='normal')=>({id,order:id,sourceStepId,targetStepId,type,label:type==='revision'?'Ulang':null});
const process=(stages,connections=[])=>({id:1,order:1,slug:'test',title:'Proses',description:null,isActive:true,actors:[{id:1,order:1,name:'asesi',label:'Asesi',description:null,isActive:true},{id:2,order:2,name:'admin',label:'Admin',description:null,isActive:true}],stages,connections});
const absolute=(node,nodes)=>{if(!node.parentId)return node.position;const parent=nodes.find(n=>n.id===node.parentId);const p=absolute(parent,nodes);return{x:p.x+node.position.x,y:p.y+node.position.y};};
function verify(result,p){
 const cards=result.nodes.filter(n=>n.type==='activity'); assert.equal(cards.length,p.stages.flatMap(s=>s.steps).length); assert.equal(result.edges.length,p.connections.length);
 for(const n of result.nodes){assert.ok(Number.isFinite(n.position.x)&&Number.isFinite(n.position.y));if(n.parentId)assert.ok(result.nodes.findIndex(p=>p.id===n.parentId)<result.nodes.indexOf(n));}
 for(let i=0;i<cards.length;i++)for(let j=i+1;j<cards.length;j++){const a=absolute(cards[i],result.nodes),b=absolute(cards[j],result.nodes);assert.ok(a.x+cards[i].width<=b.x || b.x+cards[j].width<=a.x || a.y+cards[i].height<=b.y || b.y+cards[j].height<=a.y,`overlap ${cards[i].id}/${cards[j].id}`);}
 for(const e of result.edges){assert.ok(cards.some(n=>n.id===e.source));assert.ok(cards.some(n=>n.id===e.target));assert.ok(e.data.points.length>=2);for(let i=1;i<e.data.points.length;i++){const a=e.data.points[i-1],b=e.data.points[i];assert.ok(a.x===b.x||a.y===b.y,'orthogonal connector');for(const n of cards){const r=absolute(n,result.nodes);const cross=a.x===b.x ? a.x>r.x+.5&&a.x<r.x+n.width-.5&&Math.max(a.y,b.y)>r.y+.5&&Math.min(a.y,b.y)<r.y+n.height-.5 : a.y>r.y+.5&&a.y<r.y+n.height-.5&&Math.max(a.x,b.x)>r.x+.5&&Math.min(a.x,b.x)<r.x+n.width-.5;assert.equal(cross,false,`edge ${e.id} crosses ${n.id}`);}}}
}
test('layouts actor lanes, branches, convergence and revision cycles without overlap',async()=>{
 const p=process([stage(1,[step(1,1),step(2,2),step(3,1),step(4,2)])],[edge(1,1,2),edge(2,1,3),edge(3,2,4),edge(4,3,4),edge(5,4,1,'revision')]); const result=await layoutAssessmentProcess(p,new Map(),1440);verify(result,p);assert.deepEqual(p.stages[0].steps.map(s=>s.id),[1,2,3,4]);
});
test('packs changing stage counts into alternating rows, routes cross-stage/revision and respects sizes',async()=>{
 const p=process(Array.from({length:7},(_,i)=>stage(i+1,[step(i+1)])),[edge(1,1,2),edge(2,2,5),edge(3,5,7),edge(4,7,1,'revision')]);const sizes=new Map([['step:3',{width:184,height:300}]]);const result=await layoutAssessmentProcess(p,sizes,1100);verify(result,p);const stages=result.nodes.filter(n=>n.type==='stage');assert.ok(stages[4].position.y>stages[0].position.y);assert.ok(stages[4].position.x>stages[5].position.x);assert.equal(result.nodes.find(n=>n.id==='step:3').height,300);assert.deepEqual(await layoutAssessmentProcess(p,sizes,1100),result);
});
test('handles empty stages and processes',async()=>{verify(await layoutAssessmentProcess(process([stage(1,[])]),new Map(),1440),process([stage(1,[])]));assert.deepEqual(await layoutAssessmentProcess(process([]),new Map(),1440),{nodes:[],edges:[]});});
test('measured stage and actor headings reserve space; all children stay in their parent',async()=>{
 const p=process([stage(1,[step(1,1),step(2,2)])],[edge(1,1,2)]);
 const result=await layoutAssessmentProcess(p,new Map([['stage:1',{width:224,height:180}],['actor:1:1',{width:208,height:100}]]));
 assert.ok(result.nodes.find(n=>n.id==='actor:1:1').position.y>=180);
 assert.ok(result.nodes.find(n=>n.id==='step:1').position.y>=112);
 for(const n of result.nodes.filter(n=>n.parentId)){const parent=result.nodes.find(p=>p.id===n.parentId);assert.ok(n.position.x>=0&&n.position.y>=0&&n.position.x+n.width<=parent.width+.1&&n.position.y+n.height<=parent.height+.1);}
 verify(result,p);
});
test('keeps an actor lane vertical when the real flow hands work to another actor and back',async()=>{
 const p=process([stage(1,[step(1,1),step(2,1),step(3,2),step(4,1)])],[edge(1,1,2),edge(2,2,3),edge(3,3,4)]);
 const result=await layoutAssessmentProcess(p);const a=result.nodes.find(n=>n.id==='step:1'),b=result.nodes.find(n=>n.id==='step:4');assert.equal(a.position.x,b.position.x);assert.ok(b.position.y>a.position.y+a.height);verify(result,p);
});
test('parallel branches retain distinct paths and nonoverlapping labels',async()=>{
 const p=process([stage(1,[step(1),step(2)])],[{...edge(1,1,2,'success'),label:'Approved'},{...edge(2,1,2,'revision'),label:'Review'}]);
 const r=await layoutAssessmentProcess(p);assert.notDeepEqual(r.edges[0].data.points,r.edges[1].data.points);assert.notDeepEqual(r.edges[0].data.labelPoint,r.edges[1].data.labelPoint);verify(r,p);
});
test('measured long labels do not cover cards or other labels',async()=>{
 const p=process([stage(1,[step(1),step(2),step(3)])],[{...edge(1,1,2),label:'L'.repeat(120)},{...edge(2,1,3),label:'M'.repeat(120)}]);
 const sizes=new Map([['connection:1',{width:180,height:100}],['connection:2',{width:180,height:100}]]);const r=await layoutAssessmentProcess(p,sizes);
 const labels=r.edges.map(e=>({x:e.data.labelPoint.x-90,y:e.data.labelPoint.y-50,width:180,height:100}));
 const overlaps=(a,b)=>a.x<b.x+b.width&&a.x+a.width>b.x&&a.y<b.y+b.height&&a.y+a.height>b.y;
 for(const label of labels)for(const n of r.nodes.filter(n=>n.type==='activity'))assert.equal(overlaps(label,{...absolute(n,r.nodes),width:n.width,height:n.height}),false);
 assert.equal(overlaps(labels[0],labels[1]),false);verify(r,p);
});
test('three unlabeled parallel connection types also keep separate routes',async()=>{
 const p=process([stage(1,[step(1),step(2)])],['normal','success','revision'].map((type,i)=>({...edge(i+1,1,2,type),label:null})));
 const r=await layoutAssessmentProcess(p);assert.equal(new Set(r.edges.map(e=>JSON.stringify(e.data.points))).size,3);verify(r,p);
});
test('arrows leave and enter cards perpendicular to their selected side with a clear approach',async()=>{
 const p=process([stage(1,[step(1,1),step(2,2),step(3,1)]),stage(2,[step(4,2)])],[edge(1,1,2),edge(2,2,3),edge(3,3,4),edge(4,4,1,'revision')]);
 const r=await layoutAssessmentProcess(p);verify(r,p);
 for(const e of r.edges){const ps=e.data.points,a=ps[0],b=ps[1],z=ps.at(-1),y=ps.at(-2);const s=e.sourceHandle,t=e.targetHandle;assert.ok(s?.includes('-')&&t?.includes('-'));if(s.endsWith('right'))assert.ok(b.x>a.x&&b.y===a.y);if(s.endsWith('left'))assert.ok(b.x<a.x&&b.y===a.y);if(s.endsWith('bottom'))assert.ok(b.y>a.y&&b.x===a.x);if(t.endsWith('left'))assert.ok(y.x<z.x&&y.y===z.y);if(t.endsWith('right'))assert.ok(y.x>z.x&&y.y===z.y);if(t.endsWith('top'))assert.ok(y.y<z.y&&y.x===z.x);}
});

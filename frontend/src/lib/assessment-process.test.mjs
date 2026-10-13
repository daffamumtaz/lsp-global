import test from 'node:test';
import assert from 'node:assert/strict';
import { parseAssessmentProcess } from './assessment-process.ts';
const raw = () => ({id:1,title:'Alur CMS',slug:'alur-cms',description:null,order:1,actors:[{id:10,name:'asesor',label:'Asesor',description:null,order:1}],stages:[{id:20,title:'Tahap',description:null,order:1,steps:[{id:30,title:'Keputusan',description:'Isi CMS',actorId:10,type:'decision',order:1,documentReference:null,informationUrl:null},{id:31,title:'Hasil',description:null,actorId:null,type:'outcome',order:2,documentReference:null,informationUrl:'javascript:alert(1)'}]}],connections:[{id:40,sourceStepId:30,targetStepId:31,label:'Kompeten',type:'success',order:1},{id:41,sourceStepId:31,targetStepId:30,label:'Ulang',type:'revision',order:2}]});
test('parses database branching, revision cycles, nullable actor and safe information links',()=>{
 const p=parseAssessmentProcess(raw()); assert.equal(p.stages[0].steps[0].description,'Isi CMS'); assert.equal(p.connections.length,2); assert.equal(p.stages[0].steps[1].informationUrl,null);
 for(const url of ['https://example.test/info','https://user:pass@example.test']) { const data=raw(); data.stages[0].steps[1].informationUrl=url; assert.equal(parseAssessmentProcess(data).stages[0].steps[1].informationUrl,url.includes('user:') ? null : url); }
});
test('rejects duplicate IDs, dangling endpoints/actors and malformed or unknown fields',()=>{
 for(const mutate of [p=>p.stages[0].steps[1].id=30,p=>p.connections[0].targetStepId=999,p=>p.stages[0].steps[0].actorId=999,p=>p.stages[0].steps[0].type='evil',p=>p.id='1',p=>p.stages=null,p=>p.actors[0].id=0,p=>p.stages[0].steps[0].title=null,p=>p.order=-1]) { const data=raw(); mutate(data); assert.throws(()=>parseAssessmentProcess(data)); }
});
test('normalizes order without mutating input and handles empty process/stages',()=>{
 const data=raw(); data.stages[0].steps.reverse(); const before=structuredClone(data); assert.deepEqual(parseAssessmentProcess(data).stages[0].steps.map(s=>s.id),[30,31]); assert.deepEqual(data,before);
 const empty={...raw(),actors:[],stages:[],connections:[]}; assert.deepEqual(parseAssessmentProcess(empty).stages,[]);
});

test('server fetching reads selected database process, empty API and isolates invalid responses',async()=>{
 const { createServer }=await import('node:http');
 const { fetchAssessmentProcess }=await import('./assessment-process.ts');
 let mode='ok';
 const server=createServer((req,res)=>{res.setHeader('Content-Type','application/json');
  if(mode==='error'){res.writeHead(500);res.end('{}');return;}
  res.end(JSON.stringify({data:req.url==='/api/processes' ? mode==='empty'?[]:[{id:1,order:1,slug:'alur-cms'}] : mode==='invalid'?{id:1}:raw()}));
 });
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 try {const url=`http://127.0.0.1:${server.address().port}/api/processes`;
  assert.equal((await fetchAssessmentProcess(url)).process.title,'Alur CMS');
  mode='empty';assert.deepEqual(await fetchAssessmentProcess(url),{process:null,unavailable:false});
  for(mode of ['error','invalid']) assert.deepEqual(await fetchAssessmentProcess(url),{process:null,unavailable:true});
 } finally {await new Promise(resolve=>server.close(resolve));}
});

test('preview stays on the configured endpoint and timed-out APIs become unavailable',async()=>{
 const { createServer }=await import('node:http'); const { fetchAssessmentProcess }=await import('./assessment-process.ts');
 let requested=null, slow=false;
 const server=createServer((req,res)=>{requested=req.url;if(!slow)res.end(JSON.stringify({data:{...raw(),isActive:false}}));});
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 try {const base=`http://127.0.0.1:${server.address().port}/api/processes`,token={slug:'alur-cms',expires:'9999999999',signature:'a'.repeat(64)};
  assert.equal((await fetchAssessmentProcess(base,token)).process.isActive,false);
  assert.equal(requested,`/api/processes/alur-cms/preview?expires=${token.expires}&signature=${token.signature}`);
  requested=null;assert.equal((await fetchAssessmentProcess(base,{...token,slug:'../other'})).unavailable,true);assert.equal(requested,null);
  slow=true;const started=Date.now();assert.equal((await fetchAssessmentProcess(base,token)).unavailable,true);assert.ok(Date.now()-started<6000);
 } finally {server.closeAllConnections();await new Promise(resolve=>server.close(resolve));}
});

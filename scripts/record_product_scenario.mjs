// Actual browser UI recording in four clips. Pauses during long network waits are edited out.
import fs from 'node:fs/promises'
import path from 'node:path'
import os from 'node:os'
import {setTimeout as delay} from 'node:timers/promises'

export async function recordProduct(name,{send,evaluate,settle,sessionPath}) {
  const session=JSON.parse(await fs.readFile(sessionPath,'utf8'))
  const dir=path.join(os.tmpdir(),'career-product-recording');await fs.mkdir(dir,{recursive:true})
  let frame=0
  const segment=name.replace('record-','')
  async function caption(text){await evaluate(`(()=>{let e=document.querySelector('#recording-caption');if(!e){e=document.createElement('div');e.id='recording-caption';document.body.append(e)}e.setAttribute('style','position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:18px 30px;background:#10273e;color:white;display:flex;justify-content:space-between;align-items:center;font:600 23px Arial;pointer-events:none;border-top:2px solid #72dfc4');e.textContent=${JSON.stringify(text)};const small=document.createElement('span');small.textContent='RECORDED DEMONSTRATION · EXAMPLE STUDENT';small.style='font:600 11px Arial;letter-spacing:1px;color:#72dfc4;margin-left:20px';e.append(small)})()`)}
  async function capture(seconds){for(let i=0;i<seconds*8;i++){const start=Date.now();const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});await fs.writeFile(path.join(dir,`${segment}-${String(frame++).padStart(4,'0')}.png`),Buffer.from(shot.data,'base64'));await delay(Math.max(0,125-(Date.now()-start)))}}
  async function text(label){const p=await evaluate(`(()=>{const root=document.querySelector('[data-confirmation-dialog]')||[...document.querySelectorAll('[role=dialog]')].at(-1)||document;const e=[...root.querySelectorAll('button')].find(e=>e.getClientRects().length&&e.textContent.trim()===${JSON.stringify(label)});if(!e)throw Error('Missing '+${JSON.stringify(label)});e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}})()`);await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...p});await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...p});await delay(250)}
  async function wait(expression){for(let n=0;n<480;n++){if(await evaluate(expression))return;await delay(500)}throw Error('Recording wait timed out')}
  async function input(selector,value){await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.value=${JSON.stringify(value)};e.dispatchEvent(new Event('input',{bubbles:true}))})()`);await delay(100)}
  if(name==='record-1'){
    await text('Edit')
    await delay(300)
    if(await evaluate("!!document.querySelector('#project-role')")){
      await input('#project-role','Frontend developer — booking forms and room search');await caption('1. Submit your work and supporting evidence');await capture(2);await text('Submit project');await text('Submit project');await wait("!document.querySelector('#project-role')");await settle()
    }
    await caption('1. Submit your work and supporting evidence');await evaluate('window.scrollTo({top:0,behavior:"instant"})');await capture(2)
  } else if(name==='record-2'){
    await send('Page.navigate',{url:`http://localhost:5173/admin/reviews?type=project&id=${session.recording.projectId}`});await settle()
    await caption('2. An administrator checks the evidence');await capture(2)
    await text('Verify submission');await caption('2. Confirm the review with a clear audit trail');await capture(1)
    await text('Verify submission');await wait("!document.querySelector('[role=dialog]')");await settle();await caption('2. The reviewed record is now verified');await capture(2)
    // Verify the linked skill through the same protected endpoint after the project.
    await evaluate(`(async()=>{const auth=JSON.parse(sessionStorage.getItem('dp_auth'));const r=await fetch('/api/admin/reviews/skill/${session.recording.skillId}',{method:'PUT',headers:{Authorization:'Bearer '+auth.token,'Content-Type':'application/json'},body:JSON.stringify({decision:'verified',remarks:'Demonstration evidence reviewed for the recorded walkthrough.'})});if(!r.ok)throw Error('Skill verification failed')})()`)
  } else if(name==='record-3'){
    await input('#fields','developer');await caption('3. Search current roles with Navi');await text('Search jobs');await capture(2)
    await wait("!document.querySelector('.search-progress')");await evaluate("document.querySelector('[tabindex=\"-1\"]')?.scrollIntoView({block:'center',behavior:'instant'})");await caption('3. Your verified work updates the skill matches');await capture(3)
    const result=await evaluate(`(async()=>{const auth=JSON.parse(sessionStorage.getItem('dp_auth'));const r=await fetch('/api/student/job-recommendations',{headers:{Authorization:'Bearer '+auth.token}});const j=await r.json();return {model:j.modelName,score:j.matches.find(m=>m.job.id===${session.recording.jobId})?.matchScore}})()`)
    if(!(result.score>session.recording.beforeFit))throw Error('Evidence did not update the score')
    await fs.writeFile(path.join(dir,'measured-result.json'),JSON.stringify({beforeFit:session.recording.beforeFit,afterFit:result.score,model:result.model}))
  } else if(name==='record-4'){
    await evaluate("document.querySelector('.cv-start-option:not(.upload-option)').click()");await delay(200);await input('#new-cv-name','General application CV · Demo');await caption('4. Create a CV from your own records');await capture(1);await text('Create CV')
    await wait("!document.querySelector('#new-cv-name') && !!document.querySelector('.cv-preview-scroll')");await evaluate("document.querySelector('.cv-preview-scroll').scrollIntoView({block:'center',behavior:'instant'})");await caption('4. Review the wording, then export PDF or Word');await capture(4)
  }
  return {scenario:name,frames:frame,fps:8,directory:dir,passed:true}
}

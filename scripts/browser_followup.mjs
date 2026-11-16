import fs from 'node:fs/promises'
import path from 'node:path'
import os from 'node:os'
import {setTimeout as delay} from 'node:timers/promises'

export async function runFollowup(name,{send,evaluate,settle,width}) {
  const checks=[]
  const check=(label,pass)=>checks.push({label,pass:!!pass})
  async function click(selector){const p=await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)throw Error('Missing '+${JSON.stringify(selector)});e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}})()`);await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...p});await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...p});await delay(180)}
  async function text(label){await evaluate(`(()=>{const e=[...document.querySelectorAll('button')].find(e=>e.getClientRects().length&&e.textContent.trim()===${JSON.stringify(label)});if(!e)throw Error('Missing button '+${JSON.stringify(label)});e.dataset.qaClick='yes'})()`);await click('[data-qa-click]');await evaluate("document.querySelector('[data-qa-click]')?.removeAttribute('data-qa-click')")}
  async function input(selector,value){await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.value=${JSON.stringify(value)};e.dispatchEvent(new Event('input',{bubbles:true}))})()`);await delay(100)}
  async function wait(expression){for(let n=0;n<480;n++){if(await evaluate(expression))return;await delay(500)}throw Error('Timed out: '+expression)}
  async function shot(label){const s=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});await fs.writeFile(path.join(os.tmpdir(),`followup-${label}-${width}.png`),Buffer.from(s.data,'base64'))}
  async function api(url){return evaluate(`(async()=>{const auth=JSON.parse(sessionStorage.getItem('dp_auth'));return(await fetch('/api'+${JSON.stringify(url)},{headers:{Authorization:'Bearer '+auth.token}})).json()})()`)}
  if(name==='followup-share') {
    await text('Share profile');await settle()
    check('Share selections and privacy explanation visible',await evaluate("document.querySelectorAll('[role=dialog] input[type=checkbox]').length===6 && document.querySelector('[role=dialog]').innerText.includes('Contact details, student ID')"))
    await text('Preview selected fields');await wait("!!document.querySelector('[role=dialog] article')")
    check('Preview appears before publication',!(await api('/student/profile-shares')).data.length)
    await text('Create share link');await wait("!!document.querySelector('#share-link') && !!document.querySelector('[alt=\"QR code for your shared profile\"]')")
    const url=await evaluate("document.querySelector('#share-link').value")
    check('Real QR generated for the owner link',await evaluate("document.querySelector('[alt=\"QR code for your shared profile\"]').src.startsWith('data:image/png')"))
    check('Dialog fits viewport',await evaluate("(()=>{const r=document.querySelector('[role=dialog]').getBoundingClientRect();return r.x>=0&&r.right<=innerWidth&&r.y>=0&&r.bottom<=innerHeight})()"))
    await shot('share-qr')
    const token=url.split('/').at(-1)
    check('Unauthenticated selected profile opens',await evaluate(`(async()=>{const r=await fetch('/api/shared-profile/${token}');const p=await r.json();return r.ok && p.name==='Interface Review' && !p.email && !p.phone && !p.matricNo})()`))
    await text('Revoke');await delay(250)
    check('Destructive modal has a red surface',await evaluate("(()=>{const e=document.querySelector('.confirmation-danger');return !!e && getComputedStyle(e).backgroundColor!=='rgba(0, 0, 0, 0)'})()"))
    await shot('red-confirm');await text('Revoke link');await wait("document.body.innerText.includes('Link revoked.')")
    check('Revoked link closes public access',await evaluate(`fetch('/api/shared-profile/${token}').then(r=>r.status===404)`))
  } else if(name==='followup-cv') {
    const before=(await api('/student/cvs')).data.length
    await click('.cv-start-option:not(.upload-option)')
    check('Create first asks for a document name',await evaluate("!!document.querySelector('#new-cv-name')"))
    check('Opening create does not create a document',(await api('/student/cvs')).data.length===before)
    await input('#new-cv-name','General application CV');await text('Create CV')
    check('Creating has visible busy feedback',await evaluate("document.body.innerText.includes('Creating…')"))
    await wait("!document.querySelector('#new-cv-name') && !!document.querySelector('#cv-title')")
    check('Chosen name belongs to the new CV',await evaluate("document.querySelector('#cv-title').value==='General application CV'"))
    await input('#cv-title','Renamed general CV');await text('Save name');await wait("!document.querySelector('#cv-title').disabled")
    check('Rename does not add another version',(await api('/student/cvs')).data.length===before+1)
    await click('.upload-option');await text('Paste text instead')
    check('Upload toggle is visibly outlined',await evaluate("(()=>{const e=[...document.querySelectorAll('button')].find(e=>e.textContent.includes('Upload a file instead'));return e&&parseFloat(getComputedStyle(e).borderTopWidth)>0})()"))
    await input('#import-name','Imported general CV')
    await input('#cv-paste','Interface Review\nreview@example.test\n+60)12-2652519\nSUMMARY\nComputing student building accessible web applications with SQL.\nSKILLS\nLaravel, SQL, JavaScript\nEDUCATION\nUTeM\nComputer Science\n2024 - 2027')
    await text('Extract text');await wait("!!document.querySelector('#cv-source')")
    check('Import retains requested title',await evaluate("document.querySelector('#cv-title').value==='Imported general CV'"))
    await text('Organise with AI');await wait("document.querySelector('.cv-workspace').getAttribute('aria-busy')==='false'")
    check('Imported phone uses readable formatting',await evaluate("[...document.querySelectorAll('input[type=tel]')].some(e=>e.value==='+(60) - 122652519')"))
    const select=await evaluate("document.querySelector('[role=combobox]').id");await click('#'+select)
    const labels=await evaluate("[...document.querySelectorAll('[role=option]')].map(e=>e.textContent.trim())")
    const versions=labels.map(s=>Number(s.match(/^v(\d+)/)?.[1])).filter(Boolean)
    check('Documents are ordered ascending',versions.length>=2&&versions.every((v,i)=>!i||v>versions[i-1]))
    await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});await delay(200)
    await shot('cv-naming')
  } else if(name==='followup-jobs') {
    check('One search action and no formula or save-preferences button',await evaluate("document.body.innerText.includes('Search jobs')&&!document.body.innerText.includes('How this is calculated')&&!document.body.innerText.includes('Save preferences')"))
    await input('#fields','developer')
    const start=Date.now();await text('Search jobs')
    check('Search immediately shows active buffering',await evaluate("!!document.querySelector('.search-progress[aria-busy=true]') && !!document.querySelector('.search-buffer')"))
    await shot('job-progress');await wait("!document.querySelector('.search-progress')")
    const elapsed=Date.now()-start;check('Results wait for a readable transition',elapsed>=1200)
    const result=await api('/student/job-recommendations')
    check('Search results are real current saved records',result.matches.length>0)
    check('AI shortlist is genuinely model-backed',result.modelName?.startsWith('ollama:')&&result.aiInsights?.picks?.length>0)
    check('Search narrows title or requirement terms',result.matches.every(m=>m.job.title.toLowerCase().includes('developer')||[...m.matchedSkills,...m.missingSkills].some(s=>s.toLowerCase().includes('developer'))))
    check('Fit scores have semantic colours and text labels',await evaluate("!!document.querySelector('.fit-low,.fit-medium,.fit-high')"))
    await shot('job-results')
    checks.push({label:'Measured search duration',pass:true,milliseconds:elapsed,model:result.modelName,matches:result.matches.length})
  } else throw Error('Unknown followup scenario')
  check('No horizontal overflow',await evaluate('document.documentElement.scrollWidth<=innerWidth'))
  return {scenario:name,width,passed:checks.every(c=>c.pass),checks}
}

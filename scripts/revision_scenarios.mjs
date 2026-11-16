import fs from 'node:fs/promises'
import os from 'node:os'
import path from 'node:path'
import {setTimeout as delay} from 'node:timers/promises'
export async function runRevision(name,{send,evaluate,settle,width,sessionPath}) {
  const checks=[]
  const check=(label,pass)=>checks.push({label,pass:!!pass})
  async function input(selector,value){await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)throw Error('Input missing');e.value=${JSON.stringify(value)};e.dispatchEvent(new Event('input',{bubbles:true}))})()`);await delay(80)}
  async function click(selector){const p=await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)throw Error('Missing '+${JSON.stringify(selector)});e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}})()`);await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...p});await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...p});await delay(200)}
  async function text(label){await evaluate(`(()=>{const e=[...document.querySelectorAll('button,[role=option]')].find(e=>e.getClientRects().length&&e.textContent.trim()===${JSON.stringify(label)});if(!e)throw Error('Missing text '+${JSON.stringify(label)});e.setAttribute('data-qa-target','1')})()`);await click('[data-qa-target]');await evaluate("document.querySelector('[data-qa-target]')?.removeAttribute('data-qa-target')")}
  async function escape(){await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});await delay(350)}
  async function nav(route){await evaluate(`document.querySelector('a[href="${route}"]').click()`);await settle()}
  async function wait(expression){for(let i=0;i<480;i++){if(await evaluate(expression))return;await delay(500)}throw Error('Timeout: '+expression)}
  async function shot(label){const s=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});await fs.writeFile(path.join(os.tmpdir(),`revision-${label}-${width}.png`),Buffer.from(s.data,'base64'))}
  async function api(url,method='GET',body){return evaluate(`(async()=>{const auth=JSON.parse(sessionStorage.getItem('dp_auth'));const r=await fetch('/api'+${JSON.stringify(url)},{method:${JSON.stringify(method)},headers:{Authorization:'Bearer '+auth.token,Accept:'application/json','Content-Type':'application/json'},body:${body===undefined?'undefined':JSON.stringify(JSON.stringify(body))}});const json=await r.json();if(!r.ok)throw Error(json.message);return json})()`)}
  if(name==='revision-cv') {
    check('General CV has no role chooser or interview action',await evaluate("!document.body.innerText.includes('Make a version for a role') && ![...document.querySelectorAll('button')].some(e=>/practi[cs]e interview/i.test(e.textContent))"))
    await text('Projects')
    check('Only two project entries appear per editor page',await evaluate("[...document.querySelectorAll('.cv-entry')].filter(e=>e.getClientRects().length).length===2"))
    await evaluate("[...document.querySelectorAll('.cv-editor-section')].find(e=>e.getClientRects().length).querySelector('[aria-label=\"Next page of entries\"]').setAttribute('data-next-entry','1')")
    await click('[data-next-entry]')
    check('Editor pagination moves to the next projects',await evaluate("[...document.querySelectorAll('.cv-entry')].filter(e=>e.getClientRects().length)[0]?.innerText.includes('Project 3')"))
    const before=await evaluate("(()=>{const e=document.querySelector('.cv-preview-scroll');e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return {top:e.scrollTop,page:scrollY,x:r.x+r.width/2,y:Math.min(r.bottom-30,innerHeight-120)}})()")
    await send('Input.dispatchMouseEvent',{type:'mouseWheel',x:before.x,y:before.y,deltaY:420,deltaX:0});await delay(300)
    check('Preview scrolls independently under the pointer',await evaluate(`document.querySelector('.cv-preview-scroll').scrollTop>${before.top} && Math.abs(scrollY-${before.page})<2`))
    await text('Personal')
    await evaluate("[...document.querySelectorAll('.cv-editor label')].find(e=>e.textContent.startsWith('Professional headline')).querySelector('input').id='qa-headline'")
    await input('#qa-headline','Draft preserved across routes')
    await nav('/student/profile');await nav('/student/cv')
    check('Unfinished CV edits survive route navigation',await evaluate("[...document.querySelectorAll('.cv-editor label')].find(e=>e.textContent.startsWith('Professional headline')).querySelector('input').value==='Draft preserved across routes'"))
    await text('Review with AI')
    check('Review immediately shows visible progress',await evaluate("!!document.querySelector('[role=progressbar]') && document.querySelector('.cv-workspace').getAttribute('aria-busy')==='true'"))
    await shot('review-progress')
    await wait("document.querySelector('.cv-workspace').getAttribute('aria-busy')==='false'")
    check('Review saves blank project descriptions and opens feedback',await evaluate("document.body.innerText.includes('Document essentials') && !document.querySelector('[role=alert]')"))
    await shot('review-result')
    await click('[aria-label="Delete selected CV"]')
    check('Delete uses an accessible confirmation modal',await evaluate("[...document.querySelectorAll('[role=dialog]')].some(e=>e.innerText.includes('Delete this CV?'))"))
    await text('Cancel')
    check('Cancel keeps the CV intact',await evaluate("!!document.querySelector('#cv-title')"))
  } else if(name==='revision-forms') {
    check('Greeting uses the full name',await evaluate("document.body.innerText.includes('Hello, Interface Review')"))
    if(width>=1024){await click('[aria-label="Collapse sidebar"]');await delay(250);check('Sidebar collapses to icons',await evaluate("document.querySelector('.portal-sidebar').getBoundingClientRect().width<=81"));await send('Page.reload');await settle();check('Sidebar preference persists',await evaluate("document.querySelector('.portal-sidebar').classList.contains('collapsed')"));await click('[aria-label="Expand sidebar"]')}
    await nav('/student/skills')
    check('Skills listing is paginated',await evaluate("document.querySelector('[aria-label=\"skills pagination\"]').innerText.includes('of 13')"))
    await click('[aria-label="Next page of skills"]')
    check('Next page has the remaining records',await evaluate("document.querySelector('[aria-label=\"skills pagination\"]').innerText.includes('9–13')"))
    await text('Add skill');await input('#skill-name','Unsaved skill draft');await input('#description','This draft should remain when switching pages.')
    await click('#category')
    check('Dropdown uses a styled menu',await evaluate("!!document.querySelector('[role=listbox]') && getComputedStyle(document.querySelector('[role=listbox]')).backgroundColor==='rgb(255, 255, 255)'"))
    // Choose via the menu's native keyboard interaction after the popup settles.
    await delay(300)
    for(const key of ['Home','ArrowDown','ArrowDown','ArrowDown','ArrowDown','Enter']) {
      await send('Input.dispatchKeyEvent',{type:'keyDown',key,code:key,windowsVirtualKeyCode:{Home:36,ArrowDown:40,Enter:13}[key]})
      await send('Input.dispatchKeyEvent',{type:'keyUp',key,code:key,windowsVirtualKeyCode:{Home:36,ArrowDown:40,Enter:13}[key]})
      await delay(50)
    }
    await delay(200);await escape()
    await nav('/student/projects');await nav('/student/skills');await text('Resume skill draft')
    check('Skill fields and chosen category survive navigation',await evaluate("document.querySelector('#skill-name').value==='Unsaved skill draft' && document.querySelector('#category').textContent.includes('Data Analysis')"))
    const colors=await evaluate("(()=>{const s=getComputedStyle(document.querySelector('#skill-name'));return {background:s.backgroundColor,color:s.color}})()")
    check('Dark theme fields are white with dark text',colors.background==='rgb(255, 255, 255)'&&colors.color==='rgb(23, 36, 59)')
    await shot('draft-dropdown');await escape()
    await nav('/student/profile');await input('#headline','Pending profile edits')
    await nav('/student/projects');await nav('/student/profile')
    check('Profile draft survives its server reload',await evaluate("document.querySelector('#headline').value==='Pending profile edits'"))
    await click('[aria-label="Switch to light theme"]')
    check('Light theme fields keep readable dark text',await evaluate("getComputedStyle(document.querySelector('#headline')).backgroundColor==='rgb(255, 255, 255)' && getComputedStyle(document.querySelector('#headline')).color==='rgb(23, 36, 59)'"))
    await click('[aria-label="Breadcrumb"] a')
    await settle();check('Breadcrumb returns to overview',await evaluate("location.pathname==='/student/dashboard'"))
    await nav('/student/assessments')
    await evaluate("document.querySelector('a[href^=\"/student/assessments/\"]').click()");await settle()
    const attempt=await evaluate("location.pathname")
    await click('input[type=radio]')
    const timerText=await evaluate("document.querySelector('[data-slot=card-header]').innerText")
    await nav('/student/skills');await nav('/student/assessments')
    await evaluate(`document.querySelector('a[href="${attempt}"]').click()`);await settle()
    check('Assessment choice survives leaving and returning',await evaluate("!!document.querySelector('input[type=radio]:checked')"))
    check('Assessment has an explicit continuing-timer notice',await evaluate("document.body.innerText.includes('The timer keeps running')"))
  } else if(name==='revision-chat') {
    await click('.navi-launcher');await wait("!!document.querySelector('#navi-message')&&!document.querySelector('#navi-message').disabled")
    if(width>=1024) {
      const p=await evaluate("(()=>{const r=document.querySelector('.navi-resize').getBoundingClientRect();return{x:r.x+r.width/2,y:innerHeight/2}})()")
      await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...p});await send('Input.dispatchMouseEvent',{type:'mouseMoved',button:'left',buttons:1,x:100,y:p.y});await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,x:100,y:p.y});await delay(300)
      check('Dragging chat stops at half of desktop viewport',await evaluate("Math.abs(document.querySelector('[role=dialog]').getBoundingClientRect().width-innerWidth/2)<=1"))
    }else check('Mobile chat has no desktop resize handle',await evaluate("!document.querySelector('.navi-resize')"))
    await click('#chat-history');await text('Markdown rendering check');await settle()
    check('Markdown labels render as real bold elements',await evaluate("[...document.querySelectorAll('.assistant-text strong')].some(e=>e.textContent==='Certifications:')"))
    check('Model text cannot execute HTML',await evaluate("!window.qaInjected && document.querySelector('.assistant-text').textContent.includes('<script>')"))
    await shot('chat-markdown')
    await click('[aria-label="New conversation"]');await input('#navi-message','What is the capital of France?');await click('[aria-label="Send message"]')
    check('Chat shows an active buffering indicator',await evaluate("!!document.querySelector('.navi-buffer')"))
    await wait("!document.querySelector('#navi-message').disabled")
    check('Real AI scope classifier redirects unrelated questions',await evaluate("document.querySelector('[role=log]').innerText.includes('Which part would you like help with?') && !document.querySelector('[role=log]').innerText.includes('Paris')"))
    await click('[aria-label="New conversation"]');await input('#navi-message','How do I upload my CV in this system?');await click('[aria-label="Send message"]');await wait("!document.querySelector('#navi-message').disabled")
    check('In-scope request receives an AI response',await evaluate("document.querySelector('[role=log]').innerText.toLowerCase().includes('ai response') && !document.querySelector('[role=log]').innerText.includes('unavailable')"))
    await click('[aria-label="Delete conversation"]');check('Chat deletion uses the shared confirmation',await evaluate("[...document.querySelectorAll('[role=dialog]')].some(e=>e.innerText.includes('Delete this conversation?'))"));await text('Cancel')
  } else if(name==='revision-confirmations') {
    await text('Add skill');await input('#skill-name','QA confirmation flow');await input('#description','Temporary record used to check confirmation and cleanup.')
    await text('Submit skill')
    check('Portfolio submission opens a shared confirmation',await evaluate("document.querySelector('[data-confirmation-dialog]').innerText.includes('Submit this skill for review?')"))
    await evaluate("document.querySelector('[data-confirmation-dialog]').querySelectorAll('button')[1].setAttribute('data-confirm-submit','1')")
    await click('[data-confirm-submit]');await wait("!document.querySelector('[role=dialog]')");await settle()
    const records=await api('/student/skills');check('Confirm submits the real record',records.data.some(s=>s.name==='QA confirmation flow'))
    await evaluate("[...document.querySelectorAll('[data-slot=card]')].find(e=>e.innerText.includes('QA confirmation flow')).querySelector('[aria-label=\"Delete skill\"]').setAttribute('data-delete-check','1')");await click('[data-delete-check]');await text('Delete skill');await wait("!document.querySelector('[role=dialog]')")
    const after=await api('/student/skills');check('Confirmed deletion removes only the test record',!after.data.some(s=>s.name==='QA confirmation flow') && after.data.length===13)
    await text('Add skill');await input('#skill-name','Draft to discard');await text('Discard draft');await text('Cancel')
    check('Cancel discard preserves the unfinished form',await evaluate("document.querySelector('#skill-name').value==='Draft to discard'"))
    await text('Discard draft');await evaluate("document.querySelector('[data-confirmation-dialog]').querySelectorAll('button')[1].setAttribute('data-confirm-discard','1')");await click('[data-confirm-discard]')
    check('Confirmed discard clears the form',await evaluate("document.querySelector('#skill-name').value===''"))
  } else if(name==='revision-admin') {
    await text('New assessment');await input('#assessment-title','Unfinished assessment details');await input('#assessment-description','This draft must survive route changes.');await escape()
    await nav('/admin/users');await nav('/admin/assessments');await text('New assessment')
    check('New assessment draft survives navigation',await evaluate("document.querySelector('#assessment-title').value==='Unfinished assessment details'"));await escape()
    await evaluate("[...document.querySelectorAll('button')].find(e=>e.textContent.trim()==='Details').setAttribute('data-details','1')");await click('[data-details]');await input('#edit-assessment-title','Unfinished metadata edit');await escape()
    await text('New assessment');check('Create and edit assessment drafts stay separate',await evaluate("document.querySelector('#assessment-title').value==='Unfinished assessment details'"));await escape()
    await evaluate("[...document.querySelectorAll('button')].find(e=>e.textContent.trim()==='Questions').setAttribute('data-questions','1')");await click('[data-questions]');await wait("!!document.querySelector('#question-text')")
    check('Question bank has pagination',await evaluate("!!document.querySelector('[aria-label=\"questions pagination\"]')"))
    await click('[aria-label="Edit question"]');await input('#question-text','Unfinished question edit');await escape()
    await nav('/admin/users');await nav('/admin/assessments');await evaluate("[...document.querySelectorAll('button')].find(e=>e.textContent.trim()==='Questions').setAttribute('data-questions','1')");await click('[data-questions]');await wait("!!document.querySelector('#question-text')")
    check('Question edit and identity survive navigation',await evaluate("document.querySelector('#question-text').value==='Unfinished question edit' && document.querySelector('[role=dialog]').innerText.includes('Edit question')"))
    await shot('admin-draft');await escape()
    await nav('/admin/users')
    await evaluate("[...document.querySelectorAll('button')].find(e=>e.textContent.trim()==='Suspend').setAttribute('data-suspend','1')");await click('[data-suspend]')
    check('Account status action uses the shared confirmation',await evaluate("document.querySelector('[role=dialog]').innerText.includes('Suspend student account?')"));await text('Cancel')
  } else if(name==='revision-jobs') {
    await text('Refresh matches');await wait("![...document.querySelectorAll('button')].some(e=>e.textContent.includes('Matching jobs'))")
    check('Job matches render live score breakdowns',await evaluate("document.body.innerText.includes('How this is calculated')"))
    check('No verified evidence gives a red zero score',await evaluate("[...document.querySelectorAll('.fit-low')].some(e=>e.textContent==='0/100')"))
    check('Job listings have pagination',await evaluate("!!document.querySelector('[aria-label=\"roles pagination\"]')"))
    await text('Tailor CV');await wait("!document.querySelector('#tailor-base').disabled")
    check('Tailoring starts from a clear job-specific dialog',await evaluate("document.querySelector('[role=dialog]').innerText.includes('Tailor a CV for this role') && document.querySelector('#tailor-base').textContent.includes('Interface Review CV')"))
    await shot('tailor-dialog');await text('Create & review version');await wait("location.pathname==='/student/cv' && !!document.querySelector('#cv-title')")
    check('Tailoring opens the separate CV review',await evaluate("document.body.innerText.includes('Role requirements in your CV') && document.body.innerText.includes('Document essentials')"))
    const cvs=await api('/student/cvs');check('Original general CV remains alongside the tailored version',cvs.data.some(c=>!c.targetJob)&&cvs.data.some(c=>c.targetJob))
  }
  return {scenario:name,width,checks,passed:checks.every(c=>c.pass)}
}

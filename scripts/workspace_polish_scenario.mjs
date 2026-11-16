// Real browser/API coverage for the revised surfaces and blocking task overlay.
import fs from 'node:fs/promises'
import os from 'node:os'
import path from 'node:path'
import { setTimeout as delay } from 'node:timers/promises'

export async function checkWorkspacePolish({send,evaluate,settle,width,recheck=false}) {
  const checks=[]
  const check=(label,pass,details)=>checks.push({label,pass:!!pass,...(details?{details}:{})})
  const axe=await fs.readFile(new URL('../frontend/node_modules/axe-core/axe.min.js',import.meta.url),'utf8')
  async function wait(expression,seconds=150) {
    for(let n=0;n<seconds*5;n++){if(await evaluate(expression))return;await delay(200)}
    throw Error('Timed out: '+expression)
  }
  async function shot(label) {
    const image=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false})
    await fs.writeFile(path.join(os.tmpdir(),`career-polish-${label}-${width}.png`),Buffer.from(image.data,'base64'))
  }
  async function click(selector) {
    const point=await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)throw Error('Missing '+${JSON.stringify(selector)});e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}})()`)
    await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...point})
    await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...point})
  }
  async function button(label) {
    await evaluate(`(()=>{const e=[...document.querySelectorAll('button')].find(e=>e.textContent.trim()===${JSON.stringify(label)});if(!e)throw Error('Missing '+${JSON.stringify(label)});e.dataset.polishClick='true'})()`)
    await click('[data-polish-click]')
    await evaluate("document.querySelector('[data-polish-click]')?.removeAttribute('data-polish-click')")
  }
  async function input(selector,value) {await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.value=${JSON.stringify(value)};e.dispatchEvent(new Event('input',{bubbles:true}))})()`)}
  async function navigate(page) {await send('Page.navigate',{url:'http://localhost:5173/student/'+page});await settle();await wait("!document.querySelector('.task-progress')");await evaluate(axe)}
  async function theme(value) {await evaluate(`document.documentElement.classList.toggle('dark',${value==='dark'});localStorage.setItem('digitalpassport-theme',${JSON.stringify(value)})`);await delay(100)}
  async function audit(label) {
    // Do not sample a theme/disabled-state colour halfway through its CSS transition.
    await delay(400)
    const violations=await evaluate("axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}}).then(r=>r.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>({target:n.target,summary:n.failureSummary}))})))")
    check(label+' accessibility',!violations.length,violations.length?violations:undefined)
    check(label+' fits viewport',await evaluate("document.documentElement.scrollWidth<=innerWidth && [...document.querySelectorAll('[role=dialog]')].filter(e=>e.getBoundingClientRect().width).every(e=>{const r=e.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth+1&&r.top>=0&&r.bottom<=innerHeight+1})"))
  }
  async function key(value,code) {await send('Input.dispatchKeyEvent',{type:'keyDown',key:value,code:value,windowsVirtualKeyCode:code});await send('Input.dispatchKeyEvent',{type:'keyUp',key:value,code:value,windowsVirtualKeyCode:code})}
  async function drop(name,content,type='text/plain') {
    await evaluate(`(()=>{const dt=new DataTransfer();dt.items.add(new File([${JSON.stringify(content)}],${JSON.stringify(name)},{type:${JSON.stringify(type)}}));document.querySelector('.file-upload').dispatchEvent(new DragEvent('drop',{bubbles:true,dataTransfer:dt}))})()`)
    await delay(100)
  }
  async function overlay(label) {
    await wait("!!document.querySelector('.task-progress')",15)
    check(label+' visible blur and modal',await evaluate("(()=>{const d=document.querySelector('.task-progress'),o=[...document.querySelectorAll('[data-slot=dialog-overlay]')].find(e=>Number(getComputedStyle(e).zIndex)>=90);return d?.getAttribute('role')==='dialog'&&o&&getComputedStyle(o).backdropFilter.includes('blur')})()"))
    await key('Tab',9)
    check(label+' traps keyboard focus',await evaluate("document.querySelector('.task-progress')?.contains(document.activeElement)"))
    await key('Escape',27)
    check(label+' cannot dismiss unfinished work',await evaluate("!!document.querySelector('.task-progress')"))
    await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,x:8,y:80})
    await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,x:8,y:80})
    check(label+' outside click stays blocked',await evaluate("!!document.querySelector('.task-progress')"))
    await shot(label)
  }

  async function finish(suffix='') {
    const report={checkedAt:new Date().toISOString(),scenario:'workspace-polish'+suffix,width,checks,passed:checks.every(c=>c.pass)}
    await fs.writeFile(new URL(`../design-system/skills-career/workspace-polish${suffix}-${width}.json`,import.meta.url),JSON.stringify(report,null,2))
    return report
  }
  if(recheck) {
    for(const page of ['profile','cv','jobs']) {
      await navigate(page)
      for(const mode of ['light','dark']) {await theme(mode);await audit(page+' '+mode+' final');await shot(page+'-'+mode+'-final')}
    }
    await navigate('cv');await theme('dark')
    await click('.upload-option');await delay(150)
    await drop('invalid.pdf','This is not a PDF document.','application/pdf');await button('Extract text')
    await wait("!!document.querySelector('[role=alert]') && !document.querySelector('.task-progress')",30)
    check('Failed extraction releases page and keeps selection',await evaluate("!!document.querySelector('.file-upload.selected') && getComputedStyle(document.body).pointerEvents!=='none' && !document.querySelector('#import-name').disabled"))
    await drop('Readable CV.txt','Interface Review\nreview@example.test\nSkills: Vue, Laravel, SQL.\nEducation: UTeM Computer Science.\nProjects: Built a room booking tool for a university project.')
    await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]})
    await button('Extract text');await overlay('dark-reduced-overlay')
    check('Reduced motion stops avatar and progress sweep',await evaluate("[...document.querySelectorAll('.task-progress .navi-avatar *, .task-progress-track span,.task-orbit')].every(e=>getComputedStyle(e).animationName==='none')"))
    await audit('Dark progress overlay')
    await wait("!document.querySelector('.task-progress')")
    check('Success releases page and shows source text',await evaluate("!!document.querySelector('#cv-source') && getComputedStyle(document.body).pointerEvents!=='none' && !document.querySelector('#cv-source').disabled"))
    await send('Emulation.setEmulatedMedia',{features:[]})
    if(width>=1024) {
      await click('.cv-start-option:not(.upload-option)');await wait("!!document.querySelector('#new-cv-name')")
      await input('#new-cv-name','Nested dialog check')
      // Real response with transport latency; no mocked data or API body.
      await send('Network.emulateNetworkConditions',{offline:false,latency:1800,downloadThroughput:-1,uploadThroughput:-1})
      try {await button('Create CV');await overlay('nested-create-overlay');await wait("!document.querySelector('.task-progress')");check('Nested dialog completion unlocks the page',await evaluate("!document.querySelector('[role=dialog]') && !!document.querySelector('.cv-editor') && getComputedStyle(document.body).pointerEvents!=='none'"))}
      finally {await send('Network.emulateNetworkConditions',{offline:false,latency:0,downloadThroughput:-1,uploadThroughput:-1})}
    }
    return finish('-recheck')
  }

  await navigate('dashboard')
  for(const mode of ['light','dark']) {
    await theme(mode)
    check(mode+' four distinct metric colours',await evaluate("new Set([...document.querySelectorAll('.metric-card')].map(e=>getComputedStyle(e).backgroundColor)).size===4"))
    if(width>=1024) check(mode+' navy sidebar',await evaluate("getComputedStyle(document.querySelector('.portal-sidebar')).backgroundColor==='rgb(13, 20, 34)'"))
    await audit('Overview '+mode);await shot('overview-'+mode)
  }
  await theme('light')
  if(width<1024) {
    await click('[aria-label="Open navigation"]');await delay(250)
    check('Mobile navy navigation',await evaluate("getComputedStyle(document.querySelector('.portal-mobile-nav')).backgroundColor==='rgb(13, 20, 34)'"))
    await audit('Mobile navigation');await shot('mobile-navigation');await key('Escape',27);await delay(200)
  } else {
    await click('[aria-label="Collapse sidebar"]');await delay(250)
    check('Sidebar collapse still works',await evaluate("document.querySelector('.portal-sidebar').getBoundingClientRect().width===80"))
    await click('[aria-label="Expand sidebar"]');await delay(250)
  }
  await navigate('profile')
  for(const mode of ['light','dark']) {await theme(mode);await audit('Personal details '+mode);check(mode+' white readable fields',await evaluate("getComputedStyle(document.querySelector('#name')).backgroundColor==='rgb(255, 255, 255)' && getComputedStyle(document.querySelector('#name')).color==='rgb(23, 36, 59)'"));await shot('profile-'+mode)}
  await theme('light')
  for(const [page,label] of [['projects','Add project'],['certifications','Add certification']]) {
    await navigate(page);await button(label);await delay(250)
    await audit(page+' upload');await shot(page+'-upload')
    await drop('unsupported.exe','bad')
    check(page+' invalid type rejected',await evaluate("document.querySelector('.file-upload [role=alert]')?.textContent.includes('Choose')"))
    await drop('review.png','image fixture selection','image/png')
    check(page+' file selection and size visible',await evaluate("document.querySelector('.file-upload').innerText.includes('review.png') && document.querySelector('.file-upload').innerText.includes('KB')"))
    await button('Remove')
    check(page+' remove selection',await evaluate("!document.querySelector('.file-upload').classList.contains('selected')"))
    await key('Escape',27);await delay(200)
  }
  await navigate('cv')
  for(const mode of ['light','dark']) {await theme(mode);await audit('CV start '+mode);await shot('cv-start-'+mode)}
  await theme('light');await click('.upload-option');await delay(100)
  const source='Interface Review\nreview@example.test\nMelaka\nSUMMARY\nComputing student building accessible applications with Laravel and Vue.\nSKILLS\nLaravel, Vue, SQL\nEDUCATION\nUTeM\nBachelor of Computer Science\n2024 - 2027\nPROJECTS\nLibrary Booking\nBuilt a Vue interface with SQL storage for campus room reservations.'
  await drop('My CV.txt',source)
  check('CV name defaults from selected file',await evaluate("document.querySelector('#import-name').value==='My CV'"))
  await drop('bad.exe','bad')
  check('Rejected replacement keeps valid selection',await evaluate("document.querySelector('.file-upload').innerText.includes('My CV.txt')"))
  // Clear the validation message by selecting the same valid file again.
  await drop('My CV.txt',source)
  await audit('CV upload');await shot('cv-upload')
  if(width>=1024) {
    const started=Date.now();await button('Extract text');await overlay('extract-overlay')
    await wait("!document.querySelector('.task-progress')")
    check('Extraction progress remains readable for minimum duration',Date.now()-started>=2750)
    check('Actual file text extracted',await evaluate("document.querySelector('#cv-source')?.value.includes('Library Booking')"))
    await button('Organise with AI');await overlay('organise-overlay');await wait("!document.querySelector('.task-progress')")
    check('Real Ollama structure response',await evaluate("document.body.innerText.includes('AI organised your CV')"))
    await audit('CV editor');await shot('cv-editor')
    await button('Review with AI');await overlay('review-overlay');await wait("!document.querySelector('.task-progress')")
    check('Real Ollama CV review response',await evaluate("document.body.innerText.includes('Review ready. You choose')"))
    await shot('cv-review')
    await navigate('jobs');await input('#fields','developer');await button('Search jobs');await overlay('jobs-overlay')
    await wait("!document.querySelector('.task-progress')")
    check('Real AI search returns shortlist or honest availability notice',await evaluate("document.body.innerText.includes('Navi’s shortlist') || document.body.innerText.includes('AI shortlist is unavailable')"))
    await audit('Job search result');await shot('jobs-result')
    await navigate('dashboard')
    check('Single skills insight spans available width',await evaluate("(()=>{const h=[...document.querySelectorAll('h2')].find(e=>e.textContent==='Skills appearing in your matches');if(!h)return false;const c=h.parentElement,g=c.parentElement;return g.children.length>1||c.clientWidth>=g.clientWidth-2})()"))
    await shot('overview-with-insights')
  }
  await click('.navi-launcher');await wait("!!document.querySelector('#navi-message') && !document.querySelector('#navi-message').disabled")
  await input('#navi-message','what is CV?');await click('[aria-label="Send message"]')
  await wait("document.querySelector('[role=log]')?.innerText.includes('curriculum vitae')")
  check('CV definition and clickable actions',await evaluate("document.querySelector('[role=log]').innerText.includes('curriculum vitae') && !!document.querySelector('[role=log] a[href=\"/student/cv?view=upload\"]')"))
  check('No Context used disclosure',await evaluate("!document.querySelector('[role=log]').innerText.includes('Context used')"))
  await shot('navi-definition')
  if(width>=1024) {
    await wait("!document.querySelector('#navi-message').disabled");await input('#navi-message','What is ATS and why does it matter when applying for an internship?');await click('[aria-label="Send message"]')
    await wait("!document.querySelector('#navi-message').disabled")
    check('Career adjacent question gets real AI response',await evaluate("document.querySelector('[role=log]').innerText.toLowerCase().includes('ai response') && /applicant tracking/i.test(document.querySelector('[role=log]').innerText)"))
    await shot('navi-ats')
  }
  await audit('Navi response')
  return finish()
}

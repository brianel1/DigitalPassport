// Optional browser checks. Uses actual application APIs and temporary accounts.
import fs from 'node:fs/promises'
import os from 'node:os'
import path from 'node:path'
import { setTimeout as delay } from 'node:timers/promises'

export async function runScenario(name, {send,evaluate,settle,width,sessionPath}) {
  if(name==='refined-workspace') {const {reviewRefinedWorkspace}=await import('./refined_workspace_scenario.mjs');return reviewRefinedWorkspace({send,evaluate,settle,width})}
  if(name.startsWith('workspace-polish')) {const {checkWorkspacePolish}=await import('./workspace_polish_scenario.mjs');return checkWorkspacePolish({send,evaluate,settle,width,recheck:name.endsWith('-recheck')})}
  if(name==='navigation-auth') {const {checkNavigationAuth}=await import('./navigation_auth_scenario.mjs');return checkNavigationAuth({send,evaluate,settle,width,sessionPath})}
  if(name==='job-sources') {const {checkJobSources}=await import('./job_sources_scenario.mjs');return checkJobSources({send,evaluate,settle,width})}
  if(name==='media-performance') {const {checkMedia}=await import('./media_performance_scenario.mjs');return checkMedia({send,evaluate,width})}
  if(name==='accessibility') {const {checkAccessibility}=await import('./accessibility_scenarios.mjs');return checkAccessibility(name,{send,evaluate,settle,width,sessionPath})}
  if(name.startsWith('record-')) { const {recordProduct}=await import('./record_product_scenario.mjs');return recordProduct(name,{send,evaluate,settle,width,sessionPath}) }
  if(name.startsWith('followup-')) { const {runFollowup}=await import('./browser_followup.mjs');return runFollowup(name,{send,evaluate,settle,width,sessionPath}) }
  if(name.startsWith('revision-')) { const {runRevision}=await import('./revision_scenarios.mjs');return runRevision(name,{send,evaluate,settle,width,sessionPath}) }
  const checks=[]
  function check(label, pass) { checks.push({label,pass:!!pass}) }
  async function click(selector) {
    const point=await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)throw Error('Missing control: '+${JSON.stringify(selector)});e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}})()`)
    await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...point})
    await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...point})
    await delay(150)
  }
  async function shot(label) {
    const data=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false})
    await fs.writeFile(path.join(os.tmpdir(),`skills-${label}-${width}.png`),Buffer.from(data.data,'base64'))
  }
  async function clickText(label) {
    await evaluate(`(()=>{const e=[...document.querySelectorAll('button')].find(e=>e.textContent.trim()===${JSON.stringify(label)});if(!e)throw Error('Missing button '+${JSON.stringify(label)});e.setAttribute('data-review-click','true')})()`)
    await click('[data-review-click]')
    await evaluate("document.querySelector('[data-review-click]')?.removeAttribute('data-review-click')")
  }
  async function input(selector, value) {
    await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.value=${JSON.stringify(value)};e.dispatchEvent(new Event('input',{bubbles:true}))})()`)
  }
  async function waitFor(expression) {
    for(let n=0;n<240;n++){if(await evaluate(expression))return;await delay(500)}
    throw Error('Timed out waiting for '+expression)
  }
  if(name==='cv-flow') {
    await click('.upload-option')
    await clickText('Paste text instead')
    const source='Aina Review\naina@example.test\n+60 12 345 6789\nMelaka\nSUMMARY\nComputing student building accessible web applications with Laravel and Vue.\nSKILLS\nLaravel, Vue, SQL\nEDUCATION\nUTeM\nBachelor of Computer Science\n2024 - 2027\nEXPERIENCE\nWeb Intern\nCampus Lab\nJune 2026\nBuilt a booking application with Laravel used by 20 students.\nPROJECTS\nLibrary Booking\nCreated a Vue interface with SQL storage for room reservations.'
    await input('#cv-paste',source)
    await clickText('Extract text')
    await waitFor("!!document.querySelector('#cv-source')")
    check('Pasted source is retained',await evaluate("document.querySelector('#cv-source').value.includes('Library Booking')"))
    await shot('cv-source')
    await clickText('Organise with AI')
    await waitFor("document.querySelector('.cv-workspace').getAttribute('aria-busy')==='false'")
    check('Real AI organisation completed',await evaluate("document.body.innerText.includes('AI organised your CV')"))
    check('Experience and education appear in preview',await evaluate("document.body.innerText.includes('Campus Lab') && document.body.innerText.includes('Bachelor of Computer Science')"))
    await shot('cv-editor')
    await clickText('Review with AI')
    await waitFor("document.querySelector('.cv-workspace').getAttribute('aria-busy')==='false'")
    check('Real AI review completed',await evaluate("document.body.innerText.includes('Review ready. You choose')"))
    await shot('cv-review')
    const result = await evaluate("(async()=>{const auth=JSON.parse(sessionStorage.getItem('dp_auth'));const r=await fetch('/api/student/cvs',{headers:{Authorization:'Bearer '+auth.token,Accept:'application/json'}});const data=await r.json();const cv=data.data[0];return {id:cv.id,engine:cv.modelName,reviewEngine:cv.analysis?.engine,experience:cv.content.experience.length,rewrites:cv.analysis?.rewrites.length}})()")
    check('Structured content and review saved in database',result.engine?.startsWith('ollama:') && result.reviewEngine?.startsWith('ollama:') && result.experience>0)
    checks.push({label:'Saved CV result',pass:true,details:result})
    await clickText('Talk it through with Navi')
    await waitFor("!!document.querySelector('#navi-message') && !document.querySelector('#navi-message').disabled")
    check('CV context passed to Navi platform guide',await evaluate("document.querySelector('#chat-mode').textContent.includes('Platform guide') && document.querySelector('#chat-cv').textContent.includes('v')"))
    await click('[aria-label="Send message"]')
    await waitFor("!document.querySelector('#navi-message').disabled")
    check('Navi returned a real AI reply',await evaluate("document.querySelector('[role=log]').innerText.includes('AI RESPONSE') || document.querySelector('[role=log]').innerText.includes('AI response')"))
    await shot('navi-interview')
  } else if(name==='cv-edit') {
    const summary='Computing student building accessible applications with Laravel and Vue. Built a booking application used by 20 students.'
    await input('.cv-editor textarea',summary)
    await clickText('Save changes')
    await waitFor("document.querySelector('.cv-workspace').getAttribute('aria-busy')==='false'")
    await send('Page.reload');await settle()
    check('Edited CV survives reload',await evaluate(`document.querySelector('.cv-editor textarea')?.value===${JSON.stringify(summary)} && !document.querySelector('[role=alert]')`))
    await clickText('Projects')
    await evaluate("[...document.querySelectorAll('.cv-editor label')].find(e=>e.textContent.startsWith('Technologies')).querySelector('input').setAttribute('data-review-tech','true')")
    await input('[data-review-tech]','Vue, SQL, Laravel')
    check('Technology field keeps comma-separated input while typing',await evaluate("document.querySelector('[data-review-tech]').value==='Vue, SQL, Laravel'"))
    await evaluate("document.querySelector('[data-review-tech]').dispatchEvent(new Event('change',{bubbles:true}))")
    await clickText('Save changes')
    await waitFor("document.querySelector('.cv-workspace').getAttribute('aria-busy')==='false'")
    await clickText('Source text')
    const source=await evaluate("document.querySelector('#cv-source').value")
    await input('#cv-source',source+'\nLanguages: English and Malay')
    await clickText('Save')
    await waitFor("document.querySelector('.cv-workspace').getAttribute('aria-busy')==='false'")
    check('Saving content does not erase pending source corrections',await evaluate("document.querySelector('#cv-source').value.endsWith('Languages: English and Malay')"))
    await input('#cv-source',source)
    await clickText('Edit & preview')
    await evaluate("document.querySelector('.cv-editor').scrollIntoView({block:'start',behavior:'instant'})")
    await shot('cv-editor-refined')
    check('Editor and preview fit without horizontal overflow',await evaluate("document.documentElement.scrollWidth<=innerWidth"))
  } else if(name==='assistant') {
    check('Workspace loads without an error',await evaluate("!document.querySelector('[role=alert]')"))
    await click('.navi-launcher')
    await waitFor("!!document.querySelector('#navi-message') && !document.querySelector('#navi-message').disabled")
    check('Chat drawer fits viewport',await evaluate("(()=>{const r=document.querySelector('[role=dialog]').getBoundingClientRect();return r.x>=-1&&r.right<=innerWidth+1&&r.bottom<=innerHeight+1})()"))
    check('Focus moves into chat',await evaluate("document.querySelector('[role=dialog]').contains(document.activeElement)"))
    await click('[aria-label="Pause character animation"]')
    check('Motion pause stops mascot animations',await evaluate("[...document.querySelectorAll('[role=dialog] .navi-avatar *')].every(e=>getComputedStyle(e).animationName==='none')"))
    await click('[aria-label="Animate character"]')
    await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]})
    check('Reduced motion stops mascot animations',await evaluate("[...document.querySelectorAll('.navi-avatar *')].every(e=>getComputedStyle(e).animationName==='none')"))
    await shot('navi-drawer')
    await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27})
    await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27})
    await delay(350)
    check('Escape closes chat and restores launcher focus',await evaluate("!document.querySelector('[role=dialog]') && document.activeElement.classList.contains('navi-launcher')"))
  } else if(name==='landing') {
    if(width<1024) {
      await click('[aria-label="Open menu"]')
      check('Mobile menu expands',await evaluate("document.querySelector('#landing-menu').getBoundingClientRect().height >= 120"))
      await shot('menu')
      await click('#landing-menu a')
      check('Mobile menu closes after navigation',await evaluate("!document.querySelector('#landing-menu')"))
    }
    await click('#feature-tab-0')
    await send('Input.dispatchKeyEvent',{type:'keyDown',key:'ArrowDown',code:'ArrowDown',windowsVirtualKeyCode:40})
    await send('Input.dispatchKeyEvent',{type:'keyUp',key:'ArrowDown',code:'ArrowDown',windowsVirtualKeyCode:40})
    check('Feature tabs support keyboard',await evaluate("document.querySelector('#feature-tab-1').getAttribute('aria-selected')==='true' && document.activeElement.id==='feature-tab-1'"))
    await shot('features')
    await evaluate("document.querySelector('video').scrollIntoView({block:'center',behavior:'instant'})")
    await delay(1800)
    const playing=await evaluate("({playing:!document.querySelector('video').paused,ready:document.querySelector('video').readyState,currentTime:document.querySelector('video').currentTime})")
    check('Video plays when visible',playing.playing && playing.ready>=2 && playing.currentTime>0)
    await shot('video')
    await click('[aria-label="Pause product walkthrough"]')
    check('Video pause control',await evaluate("document.querySelector('video').paused"))
    await click('[aria-label="Play product walkthrough"]')
    check('Video play control',await evaluate("!document.querySelector('video').paused"))
    await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]})
    await delay(200)
    check('Reduced motion pauses video',await evaluate("document.querySelector('video').paused"))
    await click('.faq-list summary')
    check('FAQ opens',await evaluate("document.querySelector('.faq-list details').open"))
    await send('Page.reload')
    await settle()
    await evaluate("document.querySelector('video').scrollIntoView({block:'center',behavior:'instant'})")
    await delay(500)
    check('Reduced motion initial load uses poster',await evaluate("document.querySelector('video').paused && !document.querySelector('video source')"))
    await evaluate("window.scrollTo({top:0,behavior:'instant'})")
  } else if(name==='pages') {
    const role=await evaluate("location.pathname.startsWith('/admin')?'admin':'student'")
    const routes=role==='admin'?['dashboard','reviews','assessments','users']:['dashboard','skills-profile','skills','projects','certifications','assessments','jobs','cv','profile']
    for (const page of routes) {
      await send('Page.navigate',{url:`http://localhost:5173/${role}/${page}`})
      await settle()
      const result=await evaluate("({path:location.pathname,heading:document.querySelector('h1')?.innerText,overflow:document.documentElement.scrollWidth>innerWidth,gradients:[...document.querySelectorAll('*')].some(e=>getComputedStyle(e).backgroundImage.includes('gradient(')),oldWording:/passport/i.test(document.body.innerText)})")
      check(`${role}/${page} layout, title and wording`,!!result.heading && !result.overflow && !result.gradients && !result.oldWording && result.path.endsWith('/'+page))
      if(!checks.at(-1).pass) checks.at(-1).details=result
      await shot(`${role}-${page}`)
    }
    if(width<1024) {
      await click('[aria-label="Open navigation"]')
      check('Workspace mobile navigation opens',await evaluate("!!document.querySelector('[aria-label=\"Mobile navigation\"]')"))
      await shot(`${role}-navigation`)
      await click(`[aria-label="Mobile navigation"] a[href="/${role}/dashboard"]`)
      await settle()
      check('Workspace mobile navigation closes and navigates',await evaluate(`location.pathname==='/${role}/dashboard' && !document.querySelector('[role="dialog"]')`))
    }
    if(role==='admin') {
      const session=JSON.parse(await fs.readFile(sessionPath,'utf8'))
      await send('Page.navigate',{url:`http://localhost:5173/admin/reviews?type=project&id=${session.projectId}`})
      await settle()
      check('Review deep link opens evidence dialog',await evaluate("!!document.querySelector('[role=dialog]') && document.querySelector('[role=dialog]').innerText.includes('Temporary interface check')"))
      check('Review dialog fits viewport',await evaluate("(()=>{const r=document.querySelector('[role=dialog]')?.getBoundingClientRect();return r && r.x>=0 && r.right<=innerWidth && r.y>=0 && r.bottom<=innerHeight})()"))
      await shot('review-dialog')
      await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27})
      await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27})
      await delay(300)
      check('Review dialog closes with Escape',await evaluate("!document.querySelector('[role=dialog]')"))
    }
  } else if(name==='theme') {
    await click('[aria-label="Switch to light theme"]')
    check('Theme switches to light',await evaluate("!document.documentElement.classList.contains('dark')"))
    await send('Page.reload')
    await settle()
    check('Theme preference survives reload',await evaluate("!document.documentElement.classList.contains('dark')"))
    await shot('light-theme')
  } else throw Error('Unknown review scenario')
  return {scenario:name,width,checks,passed:checks.every(c=>c.pass)}
}

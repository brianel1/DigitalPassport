import fs from 'node:fs/promises'
import os from 'node:os'
import path from 'node:path'
import {setTimeout as delay} from 'node:timers/promises'

export async function checkNavigationAuth({send,evaluate,settle,width,sessionPath}) {
  const checks=[]
  const check=(name,passed)=>{checks.push({name,passed:!!passed});if(!passed)throw Error(name)}
  async function wait(expression){for(let i=0;i<160;i++){if(await evaluate(expression))return;await delay(250)}throw Error('Timed out: '+expression)}
  async function click(selector){const point=await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)throw Error('Missing control');e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}})()`);await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...point});await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...point})}
  async function ask(message){await click('.navi-launcher');await wait("!!document.querySelector('#navi-message')&&!document.querySelector('#navi-message').disabled");await evaluate(`document.querySelector('#navi-message').value=${JSON.stringify(message)};document.querySelector('#navi-message').dispatchEvent(new Event('input',{bubbles:true}))`);await click('[aria-label="Send message"]');await wait("!!document.querySelector('[role=log] a[href=\"/student/cv?view=upload\"]') && !document.querySelector('#navi-message').disabled")}
  async function shot(label){const image=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});await fs.writeFile(path.join(os.tmpdir(),`career-${label}-${width}.png`),Buffer.from(image.data,'base64'))}
  await ask('where do i upload my CV?')
  check('Exact reported question has an Upload your CV link',await evaluate("document.querySelector('[role=log] a[href=\"/student/cv?view=upload\"]').textContent.includes('Upload your CV')"))
  check('Context used is absent',await evaluate("!document.body.innerText.includes('Context used')"))
  const axe=await fs.readFile(new URL('../frontend/node_modules/axe-core/axe.min.js',import.meta.url),'utf8');await evaluate(axe)
  for(const theme of ['light','dark']){
    await evaluate(`document.documentElement.classList.toggle('dark',${theme==='dark'})`);await delay(400)
    const violations=await evaluate("axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}}).then(r=>r.violations.map(v=>({id:v.id,targets:v.nodes.map(n=>n.target)})))")
    check(theme+' chat accessibility rules pass',violations.length===0)
    await shot('navi-navigation-'+theme)
  }
  await click('[role=log] a[href="/student/cv?view=upload"]');await wait("!!document.querySelector('#cv-upload') && !document.querySelector('#navi-message')");await delay(500)
  check('Link opens and reveals upload panel',await evaluate("location.pathname==='/student/cv' && document.querySelector('#cv-upload').getBoundingClientRect().top>=0 && document.querySelector('#cv-upload').getBoundingClientRect().top<innerHeight"))
  check('No horizontal overflow',await evaluate('document.documentElement.scrollWidth<=innerWidth'))
  await shot('cv-upload-destination')
  await click('.upload-option');await wait("!document.querySelector('#cv-upload') && !location.search.includes('upload')")
  await ask('kat mana aku boleh upload CV?');await click('[role=log] a[href="/student/cv?view=upload"]');await wait("!!document.querySelector('#cv-upload') && !document.querySelector('#navi-message')")
  check('The same destination can be reopened from the CV page',true)
  await send('Page.navigate',{url:'http://localhost:5173/student/cv?view=create'});await settle();await wait("!!document.querySelector('[role=dialog]')")
  check('Create link opens naming dialog',await evaluate("document.querySelector('[role=dialog]').textContent.includes('Create')"))
  const cvCount=await evaluate("(async()=>{const auth=JSON.parse(sessionStorage.getItem('dp_auth'));const r=await fetch('/api/student/cvs',{headers:{Authorization:'Bearer '+auth.token}});return(await r.json()).data.length})()")
  check('Navigation alone creates no CV records',cvCount===0)
  await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});await delay(300)

  if(width>=1024){
    await send('Page.navigate',{url:'http://localhost:5173/student/dashboard'});await settle()
    await send('Page.reload');await settle()
    check('Same-tab reload validates and restores the active session',await evaluate("location.pathname==='/student/dashboard' && !!sessionStorage.getItem('dp_auth') && !localStorage.getItem('dp_auth')"))
    await evaluate("localStorage.setItem('dp_auth',sessionStorage.getItem('dp_auth'));sessionStorage.removeItem('dp_auth')")
    await send('Page.navigate',{url:'http://localhost:5173/student/dashboard'});await wait("location.pathname==='/login' && !!document.querySelector('#email')")
    check('Legacy persistent login is not silently restored',await evaluate("!localStorage.getItem('dp_auth') && !sessionStorage.getItem('dp_auth')"))
    const session=JSON.parse(await fs.readFile(sessionPath,'utf8'))
    await evaluate(`document.querySelector('#email').value=${JSON.stringify(session.login.email)};document.querySelector('#email').dispatchEvent(new Event('input',{bubbles:true}));document.querySelector('#password').value=${JSON.stringify(session.login.password)};document.querySelector('#password').dispatchEvent(new Event('input',{bubbles:true}))`)
    await evaluate("document.querySelector('form').requestSubmit()");await wait("location.pathname==='/student/dashboard' && !!document.querySelector('h1')");await settle()
    check('Normal sign-in works with session storage',await evaluate("!!sessionStorage.getItem('dp_auth')&&!localStorage.getItem('dp_auth')"))
    const signedIn=await evaluate("sessionStorage.getItem('dp_auth')") // Private fixture token stays only in memory.
    await evaluate("sessionStorage.setItem('skills-draft:qa:profile','temporary draft')")
    await send('Page.navigate',{url:'http://localhost:5173/login'});await wait("location.pathname==='/login' && !!document.querySelector('#email')");await settle()
    check('Explicit login URL shows login and clears the previous session',await evaluate("!sessionStorage.getItem('dp_auth') && !!document.querySelector('#password')"))
    check('Explicit sign-in clears previous local drafts',await evaluate("!sessionStorage.getItem('skills-draft:qa:profile')"))
    const revoked=await evaluate(`fetch('/api/user',{headers:{Accept:'application/json',Authorization:'Bearer '+JSON.parse(${JSON.stringify(signedIn)}).token}}).then(r=>r.status)`)
    check('Explicit login also revokes the previous token on the server',revoked===401)
    await evaluate(`sessionStorage.setItem('dp_auth',${JSON.stringify(signedIn)});sessionStorage.setItem('skills-draft:qa:profile','temporary draft')`)
    await send('Page.navigate',{url:'http://localhost:5173/student/dashboard'});await wait("location.pathname==='/login' && !!document.querySelector('#email')")
    check('A revoked token cannot enter a protected page after reload',true)
    check('Expiry preserves local drafts for reauthentication',await evaluate("sessionStorage.getItem('skills-draft:qa:profile')==='temporary draft'"))
    await shot('login-after-expired-session')
  }
  const report={checkedAt:new Date().toISOString(),width,checks,passed:checks.every(c=>c.passed)}
  await fs.writeFile(new URL(`../design-system/skills-career/navigation-auth-${width}.json`,import.meta.url),JSON.stringify(report,null,2))
  return report
}

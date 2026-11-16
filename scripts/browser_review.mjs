// Headless Chrome + DevTools. No browser packages or mocked API responses.
import { spawn } from 'node:child_process'
import fs from 'node:fs/promises'
import os from 'node:os'
import path from 'node:path'
import { setTimeout as delay } from 'node:timers/promises'

const profile = await fs.mkdtemp(path.join(os.tmpdir(), 'skills-browser-'))
await fs.mkdir(new URL('../design-system/skills-career/', import.meta.url), {recursive:true})
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--disable-gpu', '--no-sandbox', '--no-first-run',
  '--no-default-browser-check', '--disable-background-networking',
  '--remote-debugging-port=0', '--user-data-dir=' + profile, 'about:blank',
], { stdio: 'ignore', windowsHide: true })
let ws
let authBootstrapId
const pending = new Map()
let id = 0
const errors = []
const requests = new Set()
const httpErrors = []
try {
  let port
  for (let i=0;i<100;i++) {
    try { port = (await fs.readFile(path.join(profile,'DevToolsActivePort'),'utf8')).split('\n')[0]; break } catch { await delay(100) }
  }
  if (!port) throw Error('Chrome did not start')
  const tabs = await (await fetch('http://127.0.0.1:'+port+'/json')).json()
  ws = new WebSocket(tabs.find(t=>t.type==='page').webSocketDebuggerUrl)
  await new Promise((resolve,reject)=>{ws.onopen=resolve;ws.onerror=reject})
  ws.onmessage = event => {
    const message=JSON.parse(event.data)
    if (message.id) { const call=pending.get(message.id); pending.delete(message.id); message.error?call?.reject(Error(message.error.message)):call?.resolve(message.result) }
    if (message.method==='Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text)
    if (message.method==='Network.requestWillBeSent' && ['XHR','Fetch'].includes(message.params.type)) requests.add(message.params.requestId)
    if (['Network.loadingFinished','Network.loadingFailed'].includes(message.method)) requests.delete(message.params.requestId)
    if (message.method==='Network.responseReceived' && message.params.response.status >= 400) httpErrors.push({url:message.params.response.url.split('?')[0],status:message.params.response.status})
  }
  const send = (method,params={})=>new Promise((resolve,reject)=>{const key=++id;pending.set(key,{resolve,reject});ws.send(JSON.stringify({id:key,method,params}))})
  const evaluate = async expression => {
    const result=await send('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true})
    if (result.exceptionDetails) throw Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text)
    return result.result.value
  }
  await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable')
  const settle = async () => {
    await delay(1000)
    for(let n=0;n<100;n++) {
      const ready=await evaluate("Boolean(document.querySelector('h1')) && ![...document.querySelectorAll('[role=status]')].some(e=>e.innerText.includes('Loading'))")
      if(!requests.size && ready){await delay(350);if(!requests.size)break}
      await delay(200)
    }
  }
  const width=Number(process.argv[3]||1440)
  if (process.argv[5] && process.argv[5] !== '-') {
    const sessions = JSON.parse(await fs.readFile(process.argv[5], 'utf8'))
    const role = process.argv[2].startsWith('/admin') ? 'admin' : 'student'
    const source = 'sessionStorage.setItem("dp_auth", '+JSON.stringify(JSON.stringify(sessions[role]))+');'
    authBootstrapId=(await send('Page.addScriptToEvaluateOnNewDocument', {source})).identifier
  }
  await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:width<700})
  await send('Page.navigate',{url:'http://localhost:5173'+(process.argv[2]||'/')})
  await delay(2200)
  for (let attempt=0; attempt<40; attempt++) {
    if (await evaluate("Boolean(document.querySelector('h1')) && ![...document.querySelectorAll('[role=status]')].some(e=>e.innerText.includes('Loading'))")) break
    await delay(250)
  }
  await evaluate('document.fonts.ready.then(()=>true)')
  await settle()
  if(authBootstrapId) await send('Page.removeScriptToEvaluateOnNewDocument',{identifier:authBootstrapId})
  if (process.argv[6] === 'light') { await evaluate("document.documentElement.classList.remove('dark')"); await delay(100) }
  if (process.argv[7]) {
    const { runScenario } = await import('./ui_review_scenarios.mjs')
    console.log(JSON.stringify(await runScenario(process.argv[7], {send,evaluate,settle,width,sessionPath:process.argv[5]})))
  }
  const result=await evaluate("({title:document.title,width:innerWidth,clientWidth:document.documentElement.clientWidth,scrollWidth:document.documentElement.scrollWidth,oldWording:/passport/i.test(document.body.innerText),heading:document.querySelector('h1')?.innerText,gradients:[...document.querySelectorAll('*')].filter(e=>getComputedStyle(e).backgroundImage.includes('gradient(')).length,video:document.querySelector('video')?{ready:document.querySelector('video').readyState,paused:document.querySelector('video').paused}:null})")
  const filename=path.join(os.tmpdir(),(process.argv[4]||'skills-review')+'.png')
  const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false})
  await fs.writeFile(filename,Buffer.from(shot.data,'base64'))
  console.log(JSON.stringify({...result,errors,httpErrors,screenshot:filename}))
  await send('Browser.close')
} finally { ws?.close(); chrome.kill() }

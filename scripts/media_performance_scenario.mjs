// Compare actual asset delivery on the existing Vite service; no application records change.
import fs from 'node:fs/promises'

export async function checkMedia({send,evaluate,width}) {
  await evaluate("(()=>{const v=document.querySelector('video');v.pause();v.removeAttribute('src');v.querySelectorAll('source').forEach(s=>s.remove());v.load()})()")
  await send('Network.setCacheDisabled',{cacheDisabled:true})
  const network={offline:false,latency:150,downloadThroughput:200000,uploadThroughput:93750,connectionType:'cellular3g'}
  await send('Network.emulateNetworkConditions',network)
  const files=['skills-journey-small.webm','product-walkthrough-small.webm','product-walkthrough-poster.webp']
  const transfers=[]
  for(const file of files) {
    transfers.push(await evaluate(`(async()=>{const start=performance.now();const r=await fetch('/media/'+${JSON.stringify(file)}+'?measurement='+Date.now(),{cache:'no-store'});if(!r.ok)throw Error('Media HTTP '+r.status);const data=await r.arrayBuffer();return {file:${JSON.stringify(file)},bytes:data.byteLength,transferMs:Math.round(performance.now()-start)}})()`))
  }
  const playback=await evaluate(`new Promise((resolve,reject)=>{const v=document.createElement('video');v.muted=true;v.playsInline=true;v.style='position:fixed;left:0;top:0;width:375px;z-index:9999';const start=performance.now();const timeout=setTimeout(()=>{v.remove();reject(Error('Video startup timed out'))},30000);v.onerror=()=>{clearTimeout(timeout);v.remove();reject(Error('Video decode failed'))};v.onplaying=()=>{clearTimeout(timeout);resolve({firstPlaybackMs:Math.round(performance.now()-start),readyState:v.readyState});v.pause();v.remove()};document.body.append(v);v.src='/media/product-walkthrough-small.webm?playback='+Date.now();v.play().catch(reject)})`)
  await send('Network.emulateNetworkConditions',{offline:false,latency:0,downloadThroughput:-1,uploadThroughput:-1})
  await send('Network.setCacheDisabled',{cacheDisabled:false})
  const result={checkedAt:new Date().toISOString(),width,network,transfers,playback,scope:'One cold-cache local development-server sample per asset with emulated network conditions, not a mobile-hardware or production page-load benchmark.',passed:playback.readyState>=2}
  await fs.writeFile(new URL('../design-system/skills-career/media-performance.json',import.meta.url),JSON.stringify(result,null,2))
  return result
}

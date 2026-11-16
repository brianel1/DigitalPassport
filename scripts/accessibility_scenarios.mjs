import fs from 'node:fs/promises'
import {setTimeout as delay} from 'node:timers/promises'

export async function checkAccessibility(name,{send,evaluate,settle,width}){
  const role=await evaluate("location.pathname.startsWith('/admin')?'admin':location.pathname.startsWith('/student')?'student':'public'")
  const pages=role==='admin'?['dashboard','reviews','assessments','users']:role==='student'?['dashboard','skills-profile','skills','projects','certifications','assessments','jobs','cv','profile']:['','login','register']
  const axe=await fs.readFile(new URL('../frontend/node_modules/axe-core/axe.min.js',import.meta.url),'utf8')
  const results=[]
  for(const page of pages){
    await send('Page.navigate',{url:`http://localhost:5173/${role==='public'?'':role+'/'}${page}`});await settle()
    await evaluate(axe)
    for(const theme of ['dark','light']){
      await evaluate(`document.documentElement.classList.toggle('dark',${theme==='dark'})`);await delay(150)
      const violations=await evaluate("axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}}).then(r=>r.violations.map(v=>({id:v.id,impact:v.impact,nodes:v.nodes.slice(0,5).map(n=>({target:n.target,summary:n.failureSummary}))})))")
      results.push({page:role+'/'+page,theme,violations})
    }
    // 200% text enlargement verifies reflow without a pinch zoom hiding overflowing content.
    await evaluate("document.documentElement.style.fontSize='200%'");await delay(150)
    results.push({page:role+'/'+page,zoom:'200% text',overflow:await evaluate('document.documentElement.scrollWidth>innerWidth')})
    await evaluate("document.documentElement.style.fontSize=''")
  }
  const passed=results.every(r=>!r.violations?.length&&!r.overflow)
  const destination=new URL(`../design-system/skills-career/accessibility-${role}-${width}.json`,import.meta.url)
  await fs.writeFile(destination,JSON.stringify({checkedAt:new Date().toISOString(),width,results},null,2))
  return {scenario:name,passed,pages:pages.length,width,issues:results.filter(r=>r.violations?.length||r.overflow)}
}

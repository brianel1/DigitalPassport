import {setTimeout as delay} from 'node:timers/promises'
import fs from 'node:fs/promises'

export async function checkJobSources({send,evaluate,settle,width}) {
  const checks=[]
  const check=(name,passed)=>checks.push({name,passed:!!passed})
  async function click(selector){const point=await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)throw Error('Missing '+${JSON.stringify(selector)});e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return{x:r.x+r.width/2,y:r.y+r.height/2}})()`);await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...point});await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...point})}
  const role=await evaluate("location.pathname.startsWith('/admin')?'admin':'student'")
  if(role==='admin') {
    await settle()
    check('All public source names are visible',await evaluate("['hiredly','jobstore','ricebowl','maukerja','jobstreet'].every(name=>document.body.innerText.toLowerCase().includes(name))"))
    check('JobStreet restriction is explicit',await evaluate("document.body.innerText.includes('Source access restricted')"))
    check('No API key needed for public sources',await evaluate("document.body.innerText.includes('Public web pages · no API key')"))
  } else {
    await evaluate("document.querySelector('#fields').value='';document.querySelector('#fields').dispatchEvent(new Event('input',{bubbles:true}));document.querySelector('form').requestSubmit()")
    await delay(200)
    check('Search progress appears immediately',await evaluate("!!document.querySelector('.search-progress')"))
    for(let i=0;i<360;i++){if(await evaluate("!!document.querySelector('#job-source') && !document.querySelector('.search-progress')"))break;await delay(500)}
    const result=await evaluate("(async()=>{const auth=JSON.parse(sessionStorage.getItem('dp_auth'));const r=await fetch('/api/student/job-recommendations',{headers:{Authorization:'Bearer '+auth.token}});if(!r.ok)throw Error('Latest results failed');return r.json()})()")
    check('Real catalogue has hundreds of results',result.matches.length>=500)
    check('Results include at least four sources',new Set(result.matches.map(m=>m.job.source)).size>=4)
    check('Actual local AI shortlist ran',result.modelName?.startsWith('ollama:') && result.aiInsights?.picks?.length>0)
    check('Ten cards per page',await evaluate("document.querySelectorAll('.provider-badge').length===10"))
    check('JobStreet opens a real source URL',await evaluate("[...document.querySelectorAll('a')].some(a=>a.textContent.includes('Open JobStreet') && a.href.startsWith('https://my.jobstreet.com/'))"))
    await click('#job-source')
    await delay(200)
    check('Styled source menu opens',await evaluate("document.querySelectorAll('[role=option]').length>=5"))
    await evaluate("[...document.querySelectorAll('[role=option]')].find(e=>e.textContent.trim()==='Jobstore').setAttribute('data-check-source','true')")
    await click('[data-check-source]')
    await delay(300)
    check('Source selection filters visible cards',await evaluate("[...document.querySelectorAll('.provider-badge')].every(e=>e.textContent.trim()==='Jobstore')"))
    const next=await evaluate("(()=>{const e=document.querySelector('button[aria-label=\"Next page of roles\"]');if(!e)return false;e.click();return true})()")
    check('Pagination advances',next)
    for(const theme of ['light','dark']) {
      await evaluate(`document.documentElement.classList.toggle('dark',${theme==='dark'})`)
      check(theme+' has no horizontal page overflow',await evaluate('document.documentElement.scrollWidth<=innerWidth'))
    }
  }
  const axe=await fs.readFile(new URL('../frontend/node_modules/axe-core/axe.min.js',import.meta.url),'utf8')
  await evaluate(axe)
  for(const theme of ['light','dark']) {
    await evaluate(`document.documentElement.classList.toggle('dark',${theme==='dark'})`)
    await delay(400) // Let the app's colour transition finish before measuring contrast.
    const issues=await evaluate("axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}}).then(r=>r.violations.map(v=>({id:v.id,nodes:v.nodes.slice(0,2).map(n=>({target:n.target,message:n.failureSummary}))})))")
    check(theme+' accessibility rules pass',!issues.length)
    if(issues.length)checks.at(-1).issues=issues
  }
  const result={checkedAt:new Date().toISOString(),role,width,checks,passed:checks.every(c=>c.passed)}
  await fs.writeFile(new URL(`../design-system/skills-career/job-sources-${role}-${width}.json`,import.meta.url),JSON.stringify(result,null,2))
  return result
}

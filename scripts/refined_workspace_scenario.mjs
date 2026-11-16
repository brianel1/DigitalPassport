import fs from 'node:fs/promises'
import os from 'node:os'
import path from 'node:path'
import {setTimeout as delay} from 'node:timers/promises'

export async function reviewRefinedWorkspace({send,evaluate,settle,width}) {
 const checks=[]
 const check=(label,pass,details)=>checks.push({label,pass:!!pass,...(details?{details}:{})})
 const axe=await fs.readFile(new URL('../frontend/node_modules/axe-core/axe.min.js',import.meta.url),'utf8')
 async function wait(test){for(let i=0;i<200;i++){if(await evaluate(test))return;await delay(300)}throw Error('Timed out '+test)}
 async function button(text){await evaluate(`(()=>{const e=[...document.querySelectorAll('button')].find(e=>e.textContent.trim()===${JSON.stringify(text)});if(!e)throw Error('Missing '+${JSON.stringify(text)});e.click()})()`);await delay(200)}
 async function fill(selector,value){await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.value=${JSON.stringify(value)};e.dispatchEvent(new Event('input',{bubbles:true}))})()`);await delay(50)}
 async function shot(name){await evaluate('window.scrollTo(0,0)');const data=await send('Page.captureScreenshot',{format:'png'});await fs.writeFile(path.join(os.tmpdir(),`career-refined-${name}-${width}.png`),Buffer.from(data.data,'base64'))}
 async function audit(name){await evaluate(axe);await delay(400);const issues=await evaluate("axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}}).then(r=>r.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>({target:n.target,summary:n.failureSummary}))})))");check(name+' accessibility',issues.length===0,issues.length?issues:undefined);check(name+' no overflow',await evaluate('document.documentElement.scrollWidth<=innerWidth'))}
 for(const theme of ['light','dark']){
  await evaluate(`localStorage.setItem('digitalpassport-theme','${theme}')`)
  for(const page of ['profile','cv','jobs']){
   await send('Page.navigate',{url:'http://localhost:5173/student/'+page});await settle();await wait("!document.querySelector('.task-progress')")
   await audit(page+' '+theme)
   if(page==='cv'){await evaluate("document.querySelector('.upload-option').click()");await delay(200);await audit('CV upload '+theme)}
   if(page==='jobs'){
    check('Salary initially optional',await evaluate("!document.querySelector('#salary-filters')"))
    await button('Salary range')
    await fill('#minimum-salary','3250.75')
    check('Salary text input preserves two decimals',await evaluate("document.querySelector('#minimum-salary').type==='text' && document.querySelector('#minimum-salary').value==='3250.75'"))
    for(const bad of ['3250.755','e12','-45','1,000','abc']){await fill('#minimum-salary',bad);check('Reject '+bad,await evaluate("document.querySelector('#minimum-salary').value==='3250.75'"))}
    await fill('#minimum-salary','')
    check('Salary can be cleared',await evaluate("document.querySelector('#minimum-salary').value===''"))
    check('Remote is an accessible toggle',await evaluate("!![...document.querySelectorAll('button[aria-pressed]')].find(e=>e.textContent.includes('Remote opportunities')) && !document.querySelector('input[type=checkbox]')"))
    await audit('Salary filters '+theme)
   }
   await shot(page+'-'+theme)
  }
 }
 await evaluate("document.querySelector('.navi-launcher').click()")
 await wait("document.body.innerText.includes('Choose your language.')")
 check('Language choice gates composer',await evaluate("!document.querySelector('#navi-message')"))
 await audit('Navi language choice');await shot('navi-language')
 await button('Teruskan dalam Bahasa Melayu')
 await fill('#navi-message','bro apa tu cv?')
 await evaluate("document.querySelector('[aria-label=\"Send message\"]').click()")
 await wait("document.querySelector('[role=log]')?.innerText.includes('ialah') && !document.querySelector('#navi-message').disabled")
 check('Malay definition answers meaning',await evaluate("document.querySelector('[role=log]').innerText.includes('curriculum vitae')"))
 check('No mode or context selection',await evaluate("!document.querySelector('[role=dialog] [role=combobox]') && !document.querySelector('[role=dialog]').innerText.includes('Context used')"))
 check('Markdown bold is rendered',await evaluate("!!document.querySelector('[role=log] strong')"))
 await audit('Navi Malay');await shot('navi-malay')
 await send('Page.reload');await settle();await evaluate("document.querySelector('.navi-launcher').click()")
 await wait("document.querySelector('[role=log]')?.innerText.includes('curriculum vitae')")
 check('Current chat and language survive reload',await evaluate("document.querySelector('[role=dialog]').innerText.includes('Bahasa Melayu')"))
 await button('Chat baharu');await button('Mula semula')
 await wait("document.body.innerText.includes('Choose your language.')")
 await button('Continue in English');await fill('#navi-message','apa tu cv?');await evaluate("document.querySelector('[aria-label=\"Send message\"]').click()")
 await wait("document.querySelector('[role=log]')?.innerText.includes('document that introduces')")
 check('Chosen English wins over Malay input',true)
 await audit('Navi English');await shot('navi-english')
 const report={width,checks,passed:checks.every(c=>c.pass)}
 await fs.writeFile(`design-system/skills-career/refined-ui-${width}.json`,JSON.stringify(report,null,2))
 return report
}

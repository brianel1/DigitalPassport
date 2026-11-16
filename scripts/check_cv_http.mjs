// Uses only the explicitly created temporary browser-review account.
import fs from 'node:fs/promises'
const data=JSON.parse(await fs.readFile(process.argv[2],'utf8'))
if(!/^ui-review-[a-f0-9]{12}@example\.test$/.test(data.cleanup.email)) throw Error('Expected temporary review account')
const auth={Authorization:'Bearer '+data.student.token,Accept:'application/json'}
async function api(path,method='GET',body) {
  const response=await fetch('http://127.0.0.1:8000/api'+path,{method,headers:{...auth,...(body instanceof FormData?{}:body?{'Content-Type':'application/json'}:{})},body:body instanceof FormData?body:body?JSON.stringify(body):undefined})
  if(!response.ok) throw Error(path+': '+response.status+' '+await response.text())
  return response
}
const documents=await (await api('/student/cvs')).json()
const cv=documents.data.find(c=>c.modelName?.startsWith('ollama:'))
if(!cv) throw Error('Run the browser CV flow first')
for(const [format,extension,mime] of [['pdf','pdf','application/pdf'],['word','docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document']]) {
  const response=await api(`/student/cvs/${cv.id}/export/${format}`)
  const bytes=await response.arrayBuffer()
  if(bytes.byteLength<500) throw Error('Empty export')
  const body=new FormData();body.append('file',new Blob([bytes],{type:mime}),'review-cv.'+extension)
  const imported=await (await api('/student/cvs/upload','POST',body)).json()
  if(!imported.sourceText.includes('Campus Lab') || !imported.hasSourceFile) throw Error('Export/upload round trip lost experience')
  await api(`/student/cvs/${imported.id}/source`)
  await api(`/student/cvs/${imported.id}`,'DELETE')
  console.log(JSON.stringify({check:format+' export → upload → private original → delete',passed:true,bytes:bytes.byteLength}))
}
cv.content.summary='I am a computing student. I make web apps. I use Laravel and Vue. I built a booking application used by 20 students.'
await api(`/student/cvs/${cv.id}`,'PUT',{title:cv.title,content:cv.content})
const reviewed=await (await api(`/student/cvs/${cv.id}/review`,'POST')).json()
console.log(JSON.stringify({check:'Real AI review after editing',engine:reviewed.analysis.engine,rewrites:reviewed.analysis.rewrites}))
const copy=await (await api(`/student/cvs/${cv.id}/duplicate`,'POST',{})).json()
if(copy.id===cv.id || copy.version<=cv.version) throw Error('Copy did not create a separate version')
await api(`/student/cvs/${copy.id}`,'DELETE')
const session=await (await api('/assistant/sessions','POST',{cv_id:cv.id,mode:'coach'})).json()
const chat=await (await api(`/assistant/sessions/${session.id}/messages`,'POST',{message:'Berdasarkan CV saya, apa satu langkah paling berguna untuk saya buat sekarang? Jawab dalam Bahasa Melayu.'})).json()
if(!chat.messages.at(-1)?.engine?.startsWith('ollama:')) throw Error('Chat fell back to guide mode')
const saved=await (await api(`/assistant/sessions/${session.id}`)).json()
if(saved.messages.length!==2) throw Error('Chat history did not persist')
console.log(JSON.stringify({check:'Malay career coach and persisted history',passed:true,answer:saved.messages.at(-1).content}))
console.log('HTTP checks passed. Original temporary CV retained for browser checks; cleanup removes it.')

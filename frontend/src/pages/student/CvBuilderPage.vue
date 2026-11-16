<script setup lang="ts">
import { computed, onMounted, ref, reactive, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowRight, ArrowUpRight, Check, CheckCheck, Download, FileText, FileUp, Layers3, Save, ShieldCheck, Sparkles, Trash2, Undo2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { formatPhone } from '@/lib/phone'
import { Textarea } from '@/components/ui/textarea'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import CvContentEditor from '@/components/cv/CvContentEditor.vue'
import CvDocumentPreview from '@/components/cv/CvDocumentPreview.vue'
import AppSelect from '@/components/shared/AppSelect.vue'
import TaskProgress from '@/components/shared/TaskProgress.vue'
import FileUpload from '@/components/shared/FileUpload.vue'
import { presentTaskResult } from '@/lib/taskTiming'
import { useConfirm } from '@/composables/useConfirm'
import { useFormDraft } from '@/composables/useFormDraft'
import NaviAvatar from '@/components/shared/NaviAvatar.vue'
import { useAssistant } from '@/composables/useAssistant'
import { ApiError, downloadCv, downloadCvSource, generateCv, getGeneratedCvs, removeCv, renameCv, reviewCv, structureCv, updateCv, uploadCv } from '@/lib/api'
import type { GeneratedCvResponse } from '@/lib/api'

const route = useRoute()
const router = useRouter()
const { openAssistant } = useAssistant()
const cv = ref<GeneratedCvResponse | null>(null)
const versions = ref<GeneratedCvResponse[]>([])
const loading = ref(true)
const phase = ref('')
const error = ref('')
const notice = ref('')
const panel = ref<'write' | 'review' | 'source'>('write')
const showUpload = ref(false)
const createOpen = ref(false)
const pasteMode = ref(false)
const pasted = ref('')
const file = ref<File | null>(null)
const taskComplete = ref(false)
const sourceText = ref('')
const snapshot = ref('')
const applied = ref<string[]>([])
const busy = computed(() => !!phase.value)
const dirty = computed(() => !!cv.value && fingerprint(cv.value) !== snapshot.value)
const sourceDirty = computed(() => !!cv.value && sourceText.value !== (cv.value.sourceText ?? ''))
const activeAnalysis = computed(() => cv.value?.analysis)
const { confirmAction } = useConfirm()
const editor = ref<InstanceType<typeof CvContentEditor>>()
const feedback = ref<HTMLElement>()
const progressStep = ref(0)
const progressSteps = ref<string[]>([])
const local = reactive({ documents: {} as Record<string,{cv:GeneratedCvResponse;source:string}>, pasted:'', file:null as File|null, pasteMode:false, showUpload:false, newTitle:'', importTitle:'' })
useFormDraft('cv-workspace',local)
pasted.value=local.pasted; file.value=local.file; pasteMode.value=local.pasteMode; showUpload.value=local.showUpload
watch([cv,sourceText], () => {
  if (cv.value && !loading.value) {
    if (dirty.value || sourceDirty.value) local.documents[cv.value.id] = {cv:JSON.parse(JSON.stringify(cv.value)),source:sourceText.value}
    else delete local.documents[cv.value.id]
  }
}, {deep:true})
watch([pasted,file,pasteMode,showUpload],()=>{local.pasted=pasted.value;local.file=file.value;local.pasteMode=pasteMode.value;local.showUpload=showUpload.value})
watch([()=>route.query.view,()=>route.query.cv,loading], async ([view,documentId,isLoading]) => {
  if (isLoading || busy.value) return
  const selected=Number(documentId)
  if (selected && selected!==cv.value?.id) chooseCv(selected)
  if (view==='create') createOpen.value=true
  if (view==='review' && cv.value) panel.value='review'
  if (view==='upload') {
    showUpload.value=true
    await nextTick()
    const upload=document.getElementById('cv-upload')
    upload?.scrollIntoView({block:'start',behavior:'instant'})
    upload?.focus({preventScroll:true})
  }
}, {flush:'post'})
watch([showUpload,createOpen,panel], ([upload,create,view]) => {
  if ((route.query.view==='upload' && !upload) || (route.query.view==='create' && !create) || (route.query.view==='review' && view!=='review')) {
    const query={...route.query};delete query.view;void router.replace({query})
  }
})
async function revealFeedback() { await nextTick(); feedback.value?.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'center'}); feedback.value?.focus({preventScroll:true}) }
function fingerprint(item: GeneratedCvResponse) { return JSON.stringify([item.title, item.content]) }
function normalise(item: GeneratedCvResponse) {
  item.content.experience ??= []
  item.content.additional ??= ''
  item.content.personal.website ??= ''
  item.content.personal.phone = formatPhone(item.content.personal.phone)
  return item
}
function accept(item: GeneratedCvResponse, preserveSource = false) {
  const editedSource = preserveSource && sourceDirty.value ? sourceText.value : null
  const clean = normalise(JSON.parse(JSON.stringify(item)) as GeneratedCvResponse)
  versions.value = [clean, ...versions.value.filter(v => v.id !== clean.id)].sort((a,b) => a.version-b.version)
  cv.value = structuredClone(clean)
  sourceText.value = clean.sourceText ?? ''
  if (editedSource !== null) sourceText.value = editedSource
  snapshot.value = fingerprint(clean)
}
function restoreDraft(id:number) {
  const saved=local.documents[id]
  if(saved && cv.value) { cv.value=normalise(JSON.parse(JSON.stringify(saved.cv)));sourceText.value=saved.source; if(dirty.value||sourceDirty.value) notice.value='Your unfinished draft was restored. Save changes when you are ready.' }
}
function chooseCv(id:number) {
  const item=versions.value.find(v=>v.id===id)
  if(item) { accept(item); restoreDraft(id); panel.value='write';applied.value=[];error.value='' }
}
function problem(cause:unknown) {
  error.value=cause instanceof ApiError ? cause.message : 'Something interrupted the request. Your draft is kept here; please try again.'
  if(cause instanceof ApiError) {
    const field=Object.keys(cause.fields)[0]
    if(field?.startsWith('content.')) { panel.value='write'; nextTick(()=>editor.value?.focusSection(field.split('.')[1] === 'summary' ? 'personal' : field.split('.')[1] ?? 'personal')) }
  }
}
async function work(label: string, action: () => Promise<void>, readable = false) {
  if (busy.value) return
  const started=performance.now(); taskComplete.value=false
  phase.value = label; progressStep.value=0; progressSteps.value=[]; error.value = ''; notice.value = ''
  try {
    await action()
    if (readable) { taskComplete.value=true; phase.value='Ready. Opening your result…'; progressStep.value=progressSteps.value.length; await presentTaskResult(started) }
  } catch (cause) { problem(cause) } finally { phase.value = ''; taskComplete.value=false; await revealFeedback() }
}
async function saveName() {
  if (!cv.value?.title.trim()) return
  await work('Saving the document name…', async()=>{
    const renamed=await renameCv(cv.value!.id, cv.value!.title.trim())
    versions.value=versions.value.map(item=>item.id===renamed.id?{...item,title:renamed.title}:item)
    const saved=JSON.parse(snapshot.value) as [string,unknown]
    snapshot.value=JSON.stringify([renamed.title,saved[1]])
    cv.value!.title=renamed.title
    notice.value='Document renamed. Your content edits are kept.'
  })
}
async function saveCurrent() {
  if (!cv.value) return
  const editedSource = sourceDirty.value ? sourceText.value : null
  accept(await updateCv(cv.value))
  if (editedSource !== null) sourceText.value = editedSource
}
async function create() {
  if (!local.newTitle.trim()) return
  await work('Preparing a draft from your verified records…', async () => {
    accept(await generateCv(undefined, local.newTitle.trim())); createOpen.value=false; local.newTitle=''; panel.value = 'write'; showUpload.value = false; applied.value = []
    notice.value = 'Your draft is ready. Check the details, then ask for an AI review.'
  })
}
function pickFile(value: File) {
  error.value = ''
  if (!local.importTitle.trim()) local.importTitle=value.name.replace(/\.[^.]+$/, '').slice(0,160)
}
async function importDocument() {
  if (!local.importTitle.trim()) {error.value='Give your document a name before importing.';return}
  await work('Reading the document and extracting its text…', async () => {
    progressSteps.value=['Read & extract text','Text ready']
    accept(await uploadCv(pasteMode.value ? null : file.value, pasted.value, local.importTitle.trim()))
    local.importTitle=''; panel.value = 'source'; showUpload.value = false; file.value = null; pasted.value = ''; applied.value = []
    notice.value = 'Text extracted. Check it below, then let AI organise it into an editable CV.'
  }, true)
}
async function organise() {
  if (!cv.value) return
  if ((cv.value.modelName?.startsWith('ollama:') || dirty.value) && !await confirmAction({title:'Organise this draft again?',description:'AI will replace the editable sections using your corrected source text. Your original uploaded file is preserved.',confirmLabel:'Organise again'})) return
  await work('Navi is organising the text into CV sections…', async () => {
    progressSteps.value=['Organise your text','Editable draft ready']
    accept(await structureCv(cv.value!.id, sourceText.value))
    panel.value = 'write'; applied.value = []
    notice.value = cv.value?.modelName?.startsWith('ollama:') ? 'AI organised your CV. Compare it with the source and fill any gaps before exporting.' : 'Basic extraction is ready. AI is unavailable or could not structure the document; you can edit it manually or retry from Source text.'
  }, true)
}
async function review() {
  if (!cv.value) return
  await work('Saving your draft before the review…', async () => {
    progressSteps.value=['Save draft','Analyse content','Review ready']
    await saveCurrent()
    progressStep.value=1; phase.value='Navi is checking your CV and preparing suggestions…'
    accept(await reviewCv(cv.value!.id), true)
    panel.value = 'review'; applied.value = []
    notice.value = cv.value?.analysis?.engine.startsWith('ollama:') ? 'Review ready. You choose which suggestions to use.' : 'Document checks are ready. AI suggestions are unavailable right now; you can retry.'
  }, true)
}
function valueAt(field: string): string {
  if (!cv.value) return ''
  if (field === 'summary') return cv.value.content.summary
  const match = /^(projects|experience)\.(\d+)\.description$/.exec(field)
  if (!match) return ''
  return cv.value.content[match[1] as 'projects' | 'experience'][Number(match[2])]?.description ?? ''
}
function setField(field: string, text: string) {
  if (!cv.value) return
  if (field === 'summary') cv.value.content.summary = text
  else {
    const match = /^(projects|experience)\.(\d+)\.description$/.exec(field)
    if (match) { const row = cv.value.content[match[1] as 'projects' | 'experience'][Number(match[2])]; if (row) row.description = text }
  }
}
function applyRewrite(item: {field:string;original:string;suggested:string}) {
  if (valueAt(item.field) !== item.original) { error.value = 'This text changed after the review. Run a fresh review before applying this suggestion.'; return }
  setField(item.field, item.suggested); applied.value.push(item.field); notice.value = 'Suggestion applied to the draft. Save when you are happy with it.'
}
function undo(item: {field:string;original:string;suggested:string}) {
  if (valueAt(item.field) !== item.suggested) { error.value = 'This text has been edited again. Check it in the editor before changing it.'; return }
  setField(item.field, item.original); applied.value = applied.value.filter(f => f !== item.field)
}
async function exportFile(format: 'pdf' | 'word') {
  await work('Saving and preparing your download…', async () => { await saveCurrent(); await downloadCv(cv.value!, format); notice.value = 'Your download is ready.' })
}
async function deleteDocument() {
  if (!cv.value || !await confirmAction({title:'Delete this CV?',description:'This permanently removes this CV and its original uploaded file. Other versions will be kept.',confirmLabel:'Delete CV',destructive:true})) return
  await work('Removing this document…', async () => {
    const id = cv.value!.id
    await removeCv(id); delete local.documents[id]; versions.value = versions.value.filter(v => v.id !== id); cv.value = null; snapshot.value = ''
    if (versions.value[0]) accept(versions.value[0])
    notice.value = 'CV removed.'
  })
}
function chat() {
  if (dirty.value) { error.value = 'Save your changes first so Navi can use the latest version.'; return }
  openAssistant({ cvId: cv.value?.id, mode:'coach', prompt: 'Help me improve this CV. What is the most useful next change?' })
}
onMounted(async () => {
  try {
    versions.value = (await getGeneratedCvs()).map(normalise)
    const chosen = Number(route.query.cv)
    versions.value.sort((a,b)=>a.version-b.version)
    const current = versions.value.find(v => v.id === chosen) ?? versions.value.at(-1)
    if (current) { accept(current);restoreDraft(current.id);if(route.query.view==='review') panel.value='review' }
  } catch (cause) { problem(cause) } finally { loading.value = false }
})
</script>

<template>
  <div class="cv-workspace space-y-6" :aria-busy="busy">
    <header class="flex flex-wrap items-end justify-between gap-5"><div><p class="eyebrow mb-2">Your next opportunity</p><h1 class="page-heading">Create / upload CV</h1><p class="page-intro">Start with your story. Leave with a CV you can make your own.</p></div><div v-if="cv" class="flex flex-wrap gap-2"><Button variant="outline" :disabled="busy" @click="work('Saving your changes…', async()=>{await saveCurrent();notice='CV saved.'})"><Save class="size-4" />{{ dirty ? 'Save changes' : 'Save' }}</Button><DropdownMenu><DropdownMenuTrigger as-child><Button :disabled="busy"><Download class="size-4" />Download</Button></DropdownMenuTrigger><DropdownMenuContent align="end"><DropdownMenuItem @click="exportFile('pdf')">Download PDF</DropdownMenuItem><DropdownMenuItem @click="exportFile('word')">Download Word</DropdownMenuItem></DropdownMenuContent></DropdownMenu></div></header>
    <div ref="feedback" tabindex="-1" class="scroll-mt-24 outline-none"><p v-if="error" role="alert" class="rounded-xl border border-destructive/20 bg-destructive/10 p-4 text-sm text-destructive">{{ error }}</p>
    <p v-if="notice" role="status" class="rounded-xl border border-teal/20 bg-teal-soft p-4 text-sm leading-6 text-teal">{{ notice }}</p></div>
    <TaskProgress :active="busy" :title="phase" :steps="progressSteps" :step="progressStep" :complete="taskComplete" detail="Keep this page open while the request finishes. Your draft stays here if a request fails." />
    <div v-if="loading" role="status" class="py-20 text-center text-muted-foreground">Loading your CV workspace…</div>
    <Dialog v-model:open="createOpen"><DialogContent :show-close-button="!busy" @interact-outside="event => {if(busy) event.preventDefault()}" @escape-key-down="event => {if(busy) event.preventDefault()}"><DialogHeader><DialogTitle>Create a new CV</DialogTitle><DialogDescription>Name this document, then we will fill it with your verified records. Existing CVs stay in Your documents.</DialogDescription></DialogHeader><form class="space-y-5" @submit.prevent="create"><label class="block text-sm font-medium" for="new-cv-name">Document name<Input id="new-cv-name" v-model="local.newTitle" class="mt-2" maxlength="160" required placeholder="e.g. My general CV" :disabled="busy" /></label><DialogFooter><Button type="button" variant="outline" :disabled="busy" @click="createOpen=false">Cancel</Button><Button type="submit" :disabled="busy || !local.newTitle.trim()">{{busy?'Creating…':'Create CV'}}</Button></DialogFooter><p v-if="error" role="alert" class="text-sm text-destructive">{{error}}</p></form></DialogContent></Dialog>
    <template v-if="!loading">
      <section class="cv-start-grid" :class="{ compact: !!cv }">
        <button class="cv-start-option tone-card tone-blue" :disabled="busy" @click="createOpen = true"><span class="cv-option-icon"><Layers3 class="size-6" /></span><div><span class="cv-kicker">START FROM YOUR RECORD</span><h2>Create a CV</h2><p>Bring your verified skills and achievements into an editable draft.</p></div><ArrowUpRight class="size-5 shrink-0" /></button>
        <button class="cv-start-option upload-option tone-card tone-teal" :class="{selected:showUpload}" :disabled="busy" :aria-expanded="showUpload" aria-controls="cv-upload" @click="showUpload = !showUpload"><span class="cv-option-icon"><FileUp class="size-6" /></span><div><span class="cv-kicker">ALREADY HAVE ONE?</span><h2>Upload your CV</h2><p>Extract the text, organise it with AI and give your existing CV a fresh start.</p></div><ArrowUpRight class="size-5 shrink-0" /></button>
      </section>
      <section v-if="showUpload" id="cv-upload" tabindex="-1" aria-label="Upload your CV" class="tone-card tone-teal scroll-mt-24 rounded-2xl border p-5 outline-none focus-visible:ring-2 focus-visible:ring-ring sm:p-7">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-heading text-xl">Bring your experience with you.</h2><p class="mt-2 max-w-xl text-sm leading-6 text-muted-foreground">Your file stays private to your account. Review the extracted text before AI turns it into CV sections.</p></div><Button variant="outline" class="border-primary bg-accent font-semibold text-primary" size="sm" :disabled="busy" @click="pasteMode = !pasteMode">{{ pasteMode ? 'Upload a file instead' : 'Paste text instead' }}</Button></div>
        <div class="mt-5 max-w-xl space-y-2"><label for="import-name" class="text-sm font-medium">Document name</label><Input id="import-name" v-model="local.importTitle" maxlength="160" placeholder="e.g. My general CV" :disabled="busy" /></div>
        <FileUpload v-if="!pasteMode" id="cv-file" v-model="file" class="mt-5" label="Your CV file" accept=".pdf,.docx,.txt" :max-mb="2" formats="PDF, DOCX or TXT" :disabled="busy" hint="PDF up to 10 pages. Text-based files work best; scanned PDFs use local text recognition and may take longer." @selected="pickFile" />
        <div v-else class="mt-5"><label for="cv-paste" class="text-sm font-medium">Paste your CV text</label><Textarea id="cv-paste" v-model="pasted" rows="10" maxlength="20000" class="mt-2" :disabled="busy" placeholder="Paste the text from your CV here…" /><p class="mt-2 text-xs text-muted-foreground">50–20,000 characters. Keep the section headings and line breaks.</p></div>
        <div class="mt-5 flex flex-wrap items-center justify-between gap-4"><p class="max-w-lg text-xs leading-6 text-muted-foreground">Uploading a CV does not verify its claims or change your skills record.</p><Button :disabled="busy || !local.importTitle.trim() || (pasteMode ? pasted.trim().length < 50 : !file)" @click="importDocument">Extract text <ArrowRight class="size-4" /></Button></div>
      </section>
      <section v-if="!cv && !showUpload" class="cv-welcome tone-card tone-blue"><div><p class="eyebrow text-teal">More than a document</p><h2>Your experience.<br />A clearer story.</h2><p>Create or upload a CV to get actionable feedback, improve the wording with Navi and export it when you are ready.</p><div class="mt-5 flex flex-wrap gap-3 text-xs text-muted-foreground"><span class="flex items-center gap-2"><CheckCheck class="size-4 text-teal" />Review your content</span><span class="flex items-center gap-2"><FileText class="size-4 text-teal" />PDF & Word exports</span></div></div><NaviAvatar :size="190" /></section>
      <template v-if="cv">
        <section class="tone-card tone-blue rounded-2xl border p-5">
          <div class="grid gap-4 lg:grid-cols-[1fr_1fr_auto]"><div class="min-w-0"><label for="cv-version" class="mb-2 block text-xs font-medium text-muted-foreground">Your documents</label><AppSelect id="cv-version" :model-value="cv.id" :disabled="busy" :options="versions.map(item=>({value:item.id,label:'v'+item.version+' · '+item.title}))" @update:model-value="chooseCv(Number($event))" /></div><div><label for="cv-title" class="mb-2 block text-xs font-medium text-muted-foreground">Document name</label><div class="flex gap-2"><Input id="cv-title" v-model="cv.title" :disabled="busy" maxlength="160" class="min-h-11" @keydown.enter.prevent="saveName" /><Button variant="outline" :disabled="busy || !cv.title.trim() || cv.title === versions.find(v=>v.id===cv?.id)?.title" @click="saveName">Save name</Button></div></div><div class="flex items-end gap-2"><Button variant="ghost" size="icon" :disabled="busy" aria-label="Delete selected CV" @click="deleteDocument"><Trash2 class="size-4" /></Button></div></div>
          <div class="mt-4 flex flex-wrap items-center gap-3 text-xs"><span class="rounded-md bg-accent px-2 py-1 text-primary">{{ cv.sourceType === 'profile' ? 'Started from verified records' : 'Imported · user-provided information' }}</span><span class="text-muted-foreground">{{ cv.modelName?.startsWith('ollama:') ? 'AI-assisted draft' : 'Editable draft' }}</span><span v-if="dirty" class="text-warning">Draft kept in this tab · not yet saved to your account</span><span v-if="cv.targetJob" class="text-teal">{{ cv.targetJob.title }} · {{ cv.targetJob.company }}</span></div>
        </section>
        <nav class="flex flex-wrap items-center gap-2 border-b pb-3" aria-label="CV workspace views"><Button :variant="panel === 'write' ? 'default' : 'ghost'" :aria-pressed="panel === 'write'" @click="panel = 'write'">Edit & preview</Button><Button :variant="panel === 'review' ? 'default' : 'ghost'" :aria-pressed="panel === 'review'" @click="panel = 'review'"><Sparkles class="size-4" />AI review</Button><Button v-if="cv.sourceText" :variant="panel === 'source' ? 'default' : 'ghost'" :aria-pressed="panel === 'source'" @click="panel = 'source'">Source text</Button></nav>
        <div v-if="panel === 'write'" class="grid min-w-0 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
          <fieldset :disabled="busy" class="min-w-0"><CvContentEditor ref="editor" v-model="cv.content" /></fieldset>
          <div class="min-w-0 xl:sticky xl:top-24"><div class="mb-4 flex items-center justify-between gap-3"><p class="eyebrow">Live document preview</p><Button variant="outline" size="sm" :disabled="busy" @click="review"><Sparkles class="size-4" />Review with AI</Button></div><div class="cv-preview-scroll" tabindex="0" role="region" aria-label="Scrollable live CV preview"><CvDocumentPreview :content="cv.content" /></div><p class="mt-3 text-xs leading-6 text-muted-foreground">Preview updates as you type. PDF and Word use a simple, readable single-column layout.</p></div>
        </div>
        <section v-if="panel === 'source'" class="tone-card tone-teal rounded-2xl border p-5 sm:p-7"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-heading text-xl">First, check what we read.</h2><p class="mt-2 text-sm text-muted-foreground">{{ cv.sourceFilename }} · {{ sourceText.length.toLocaleString() }} characters</p></div><Button v-if="cv.hasSourceFile" variant="outline" size="sm" :disabled="busy" @click="work('Preparing the original file…', async()=>{await downloadCvSource(cv!)})"><Download class="size-4" />Original file</Button></div><p class="my-5 text-sm leading-7 text-muted-foreground">Correct any extraction errors here, then use Organise with AI to save and apply them. AI will organise this text; missing or unsupported fields stay blank. Check all sections against your original document afterwards. Your account name/email may fill missing contact details.</p><p v-if="cv.extractionMethod === 'local_ocr'" class="mb-4 rounded-lg bg-warning-soft p-3 text-sm text-warning">Text was read from a scanned PDF. Check names, dates and numbers carefully before continuing.</p><label for="cv-source" class="sr-only">Extracted CV text</label><Textarea id="cv-source" v-model="sourceText" rows="18" maxlength="20000" :disabled="busy" class="font-mono text-xs leading-6" /><div class="mt-5 flex flex-wrap items-center justify-between gap-4"><p class="max-w-lg text-xs leading-6 text-muted-foreground">Reorganising replaces the editable contents of this draft. Save or download your current version first if you want to keep a copy.</p><Button :disabled="busy || sourceText.trim().length < 50" @click="organise"><Sparkles class="size-4" />Organise with AI</Button></div></section>
        <section v-if="panel === 'review'" class="space-y-5">
          <div class="cv-review-intro tone-card tone-teal"><div><p class="eyebrow text-teal">A second pair of eyes</p><h2>A stronger CV starts<br />with a useful next step.</h2><p>Check the essentials, see what is backed by your records, and choose the wording that feels right.</p><div class="mt-5 flex flex-wrap gap-2"><Button :disabled="busy" @click="review"><Sparkles class="size-4" />{{ activeAnalysis ? 'Review again' : 'Review my CV' }}</Button><Button variant="outline" :disabled="busy" @click="chat">Talk it through with Navi <ArrowUpRight class="size-4" /></Button></div></div><NaviAvatar :size="150" :state="busy ? 'thinking' : 'idle'" /></div>
          <p v-if="activeAnalysis && dirty" role="status" class="rounded-lg bg-warning-soft p-3 text-xs text-warning">This review describes the saved version. You have edits since then; save and review again for updated checks.</p>
          <template v-if="activeAnalysis">
            <div class="grid gap-5 xl:grid-cols-2"><div class="rounded-2xl border bg-card p-6"><div class="flex justify-between gap-3"><h3 class="font-heading text-lg">Document essentials</h3><span class="text-sm text-primary">{{ activeAnalysis.passedChecks }} / {{ activeAnalysis.checks.length }} present</span></div><p class="mt-2 text-xs leading-6 text-muted-foreground">A content checklist, not an ATS score or hiring prediction.</p><div v-for="item in activeAnalysis.checks" :key="item.key" class="flex gap-3 border-b py-4 last:border-0"><span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full" :class="item.passed ? 'bg-teal-soft text-teal' : 'border text-muted-foreground'"><Check v-if="item.passed" class="size-3" /><span v-else class="size-1 rounded-full bg-current"></span></span><div><p class="text-sm">{{ item.label }}</p><p v-if="!item.passed" class="mt-1 text-xs leading-6 text-muted-foreground">{{ item.tip }}</p></div></div></div>
            <div class="space-y-5"><div class="rounded-2xl border bg-card p-6"><h3 class="flex items-center gap-2 font-heading text-lg"><ShieldCheck class="size-5 text-teal" />Evidence behind your skills</h3><p class="mt-3 text-xs leading-6 text-muted-foreground">Matched against current verified work, certificates and passed assessment topics. Uploaded or edited claims are not automatically verified.</p><p class="mt-4 text-xs font-medium text-teal">Backed by your evidence</p><p class="mt-2 text-sm">{{ activeAnalysis.verifiedSkills.join(' · ') || 'No matching verified skills yet.' }}</p><p class="mt-4 text-xs font-medium text-muted-foreground">Not matched to verified records</p><p class="mt-2 text-sm">{{ activeAnalysis.unverifiedSkills.join(' · ') || 'No unmatched skills listed.' }}</p></div><div v-if="cv.targetJob" class="rounded-2xl border bg-card p-6"><h3 class="font-heading text-lg">Role requirements in your CV</h3><p class="mt-2 text-xs leading-6 text-muted-foreground">{{ activeAnalysis.jobCoverage.mentioned.length }} of {{ activeAnalysis.jobCoverage.total }} listed requirements mentioned. This checks words in your CV, not proficiency.</p><p class="mt-4 text-sm text-teal">{{ activeAnalysis.jobCoverage.mentioned.join(' · ') || 'No requirement terms matched.' }}</p><p class="mt-4 text-xs leading-6 text-muted-foreground"><strong>Not mentioned:</strong> {{ activeAnalysis.jobCoverage.missing.join(' · ') || 'None from the listing.' }} Only add a skill if you actually have it.</p></div></div></div>
            <div class="rounded-2xl border bg-card p-6"><div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-heading text-xl">Your next improvements</h3><span class="text-xs text-muted-foreground">{{ activeAnalysis.engine.startsWith('ollama:') ? 'AI suggestions · check before using' : 'Document checks · AI unavailable' }}</span></div><div v-if="activeAnalysis.strengths.length" class="mt-5 rounded-xl bg-teal-soft p-4"><p class="text-xs font-semibold text-teal">WHAT IS WORKING</p><ul class="mt-2 list-disc space-y-2 pl-4 text-sm leading-6"><li v-for="strength in activeAnalysis.strengths" :key="strength">{{ strength }}</li></ul></div><ol v-if="activeAnalysis.suggestions.length" class="mt-5 list-decimal space-y-3 pl-5 text-sm leading-7"><li v-for="suggestion in activeAnalysis.suggestions" :key="suggestion">{{ suggestion }}</li></ol><p v-else class="mt-4 text-sm leading-7 text-muted-foreground">Use the document checks above to improve your CV. You can retry AI review when the model is available.</p></div>
            <article v-for="item in activeAnalysis.rewrites" :key="item.field" class="rounded-2xl border bg-card p-5 sm:p-6"><div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-heading text-lg">{{ item.field === 'summary' ? 'Professional summary' : item.field.startsWith('projects') ? 'Project description' : 'Experience description' }}</h3><Button v-if="applied.includes(item.field)" variant="outline" size="sm" :disabled="busy" @click="undo(item)"><Undo2 class="size-4" />Undo</Button><Button v-else variant="outline" size="sm" :disabled="busy" @click="applyRewrite(item)"><Check class="size-4" />Use this wording</Button></div><div class="mt-5 grid gap-4 md:grid-cols-2"><div class="rounded-xl border p-4"><p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Your original</p><p class="whitespace-pre-wrap text-sm leading-7">{{ item.original }}</p></div><div class="rounded-xl border border-teal/25 bg-teal-soft p-4"><p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-teal">AI suggestion</p><p class="whitespace-pre-wrap text-sm leading-7">{{ item.suggested }}</p></div></div><p class="mt-4 text-xs leading-6 text-muted-foreground">{{ item.reason }} Review the facts before applying.</p></article>
          </template>
        </section>
      </template>
    </template>
  </div>
</template>

<style scoped>
.cv-preview-scroll{max-height:70dvh;overflow:auto;overscroll-behavior:contain;border:1px solid var(--border);border-radius:14px;background:#fff;scrollbar-gutter:stable}.cv-preview-scroll:focus-visible{outline:2px solid var(--primary);outline-offset:3px}@media(min-width:1280px){.cv-preview-scroll{max-height:calc(100dvh - 200px)}}
.cv-start-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.cv-start-option{display:flex;align-items:center;gap:18px;padding:25px;text-align:left;border:1px solid var(--border);border-radius:16px;background:var(--tone-surface);border-color:var(--tone-border);transition:border-color .2s,box-shadow .2s}.cv-start-option:hover,.cv-start-option.selected{border-color:var(--primary);box-shadow:0 4px 16px #0000000a}.cv-start-option:disabled{opacity:.5;cursor:wait}.cv-option-icon{display:grid;place-items:center;width:50px;height:54px;flex-shrink:0;border-radius:12px;background:var(--accent);color:var(--primary)}.upload-option .cv-option-icon{background:var(--accent);color:var(--primary)}.cv-kicker{font-size:9px;letter-spacing:.12em;color:var(--muted-foreground)}.cv-start-option h2{font-size:23px;font-weight:500;margin-top:5px}.cv-start-option p{font-size:12px;line-height:1.8;color:var(--muted-foreground);margin-top:7px}.cv-welcome,.cv-review-intro{display:flex;align-items:center;justify-content:space-between;gap:25px;padding:35px;border:1px solid var(--border);border-radius:18px;background:var(--tone-surface);border-color:var(--tone-border)}.cv-welcome h2,.cv-review-intro h2{margin-top:14px;font-size:clamp(28px,3vw,40px);line-height:1.15;font-weight:500;letter-spacing:-.035em}.cv-welcome>div>p:last-of-type,.cv-review-intro>div>p:last-of-type{max-width:540px;margin-top:17px;color:var(--muted-foreground);font-size:14px;line-height:1.85}@media(max-width:1100px){.cv-start-grid{grid-template-columns:1fr}.cv-start-option{padding:22px}}@media(max-width:640px){.cv-start-option{gap:12px;padding:18px}.cv-option-icon{width:42px;height:46px}.cv-start-option h2{font-size:21px}.cv-start-option>svg{display:none}.cv-welcome,.cv-review-intro{padding:24px;align-items:start;flex-direction:column-reverse;gap:5px}.cv-welcome :deep(.navi-avatar),.cv-review-intro :deep(.navi-avatar){width:110px!important;height:110px!important}}
.cv-start-grid.compact{gap:12px}.compact .cv-start-option{padding:14px 18px;min-height:72px}.compact .cv-kicker,.compact .cv-start-option p{display:none}.compact .cv-start-option h2{font-size:18px;margin:0}.compact .cv-option-icon{width:40px;height:40px}
</style>

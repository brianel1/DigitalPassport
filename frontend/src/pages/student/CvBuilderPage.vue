<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Download, FileText, Plus, Save, Sparkles, Trash2, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { ApiError, downloadCv, generateCv, getGeneratedCvs, updateCv } from '@/lib/api'
import type { GeneratedCvResponse } from '@/lib/api'

const cv = ref<GeneratedCvResponse | null>(null)
const loading = ref(true)
const working = ref(false)
const saved = ref(false)
const error = ref('')
const skillInput = ref('')

onMounted(async () => {
  try {
    const items = await getGeneratedCvs()
    cv.value = items[0] ?? null
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'CV data could not be loaded.'
  } finally {
    loading.value = false
  }
})

async function createNewCv() {
  working.value = true
  error.value = ''
  try {
    cv.value = await generateCv()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The CV could not be generated.'
  } finally {
    working.value = false
  }
}

async function saveCv() {
  if (!cv.value) return
  addSkillTag()
  working.value = true
  error.value = ''
  try {
    cv.value = await updateCv(cv.value)
    saved.value = true
    window.setTimeout(() => { saved.value = false }, 2000)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The CV could not be saved.'
  } finally {
    working.value = false
  }
}

async function exportCv(format: 'pdf' | 'word') {
  if (!cv.value) return
  addSkillTag()
  working.value = true
  error.value = ''
  try {
    cv.value = await updateCv(cv.value)
    await downloadCv(cv.value, format)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The CV could not be downloaded.'
  } finally {
    working.value = false
  }
}

function addProject() {
  cv.value?.content.projects.push({ title: '', description: '', technologies: [] })
}

function addCertification() {
  cv.value?.content.certifications.push({ title: '', issuer: '', issueDate: '' })
}

function addAssessment() {
  cv.value?.content.assessments.push({ title: '', percentage: 0, completedAt: '' })
}

function addSkillTag() {
  if (!cv.value) return
  const skill = skillInput.value.trim()
  if (!skill) return

  const alreadyAdded = cv.value.content.skills.some((item) => item.toLowerCase() === skill.toLowerCase())
  if (!alreadyAdded) cv.value.content.skills.push(skill)
  skillInput.value = ''
}

function removeSkillTag(index: number) {
  cv.value?.content.skills.splice(index, 1)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="text-2xl font-semibold tracking-tight">ATS CV builder</h1><p class="mt-1 text-muted-foreground">Prepare an editable CV using verified information from your profile.</p></div>
      <div v-if="cv" class="flex gap-2">
        <Button variant="success" :disabled="working" @click="saveCv"><Save class="size-4" /> Save</Button>
        <DropdownMenu>
          <DropdownMenuTrigger as-child><Button :disabled="working"><Download class="size-4" /> Download</Button></DropdownMenuTrigger>
          <DropdownMenuContent align="end"><DropdownMenuItem @click="exportCv('pdf')"><FileText class="size-4" /> Download PDF</DropdownMenuItem><DropdownMenuItem @click="exportCv('word')"><FileText class="size-4" /> Download Word</DropdownMenuItem></DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <p v-if="error" class="rounded-lg bg-destructive/10 p-3 text-sm font-medium text-destructive">{{ error }}</p>
    <p v-if="saved" class="text-sm font-medium text-emerald-600">CV saved successfully.</p>
    <div v-if="loading" class="py-12 text-center text-muted-foreground">Loading CV builder...</div>

    <Card v-else-if="!cv">
      <CardContent class="flex flex-col items-center p-12 text-center"><div class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"><Sparkles class="size-7" /></div><h2 class="mt-5 text-xl font-semibold">Create your first ATS CV</h2><p class="mt-2 max-w-lg text-sm text-muted-foreground">The builder uses your verified skills, projects, certifications, and profile details. You can edit every section before downloading.</p><Button class="mt-6" :disabled="working" @click="createNewCv">{{ working ? 'Preparing CV...' : 'Generate CV' }}</Button></CardContent>
    </Card>

    <div v-else class="grid gap-6 xl:grid-cols-[minmax(360px,0.85fr)_minmax(500px,1.15fr)]">
      <div class="space-y-5">
        <Card>
          <CardHeader><CardTitle>Document details</CardTitle><CardDescription>Edit the content shown in the preview.</CardDescription></CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2"><Label>CV title</Label><Input v-model="cv.title" /></div>
            <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label>Full name</Label><Input v-model="cv.content.personal.name" /></div><div class="space-y-2"><Label>Professional headline</Label><Input v-model="cv.content.personal.headline" /></div></div>
            <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label>Email</Label><Input v-model="cv.content.personal.email" type="email" /></div><div class="space-y-2"><Label>Phone</Label><Input v-model="cv.content.personal.phone" /></div></div>
            <div class="space-y-2"><Label>Location</Label><Input v-model="cv.content.personal.location" /></div>
            <div class="space-y-2"><Label>Professional summary</Label><Textarea v-model="cv.content.summary" rows="5" /></div>
            <div class="space-y-2"><Label>Skills</Label><div class="flex min-h-10 flex-wrap items-center gap-2 rounded-lg border border-input bg-background px-3 py-2 focus-within:ring-2 focus-within:ring-ring/50"><span v-for="(skill, index) in cv.content.skills" :key="`${skill}-${index}`" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">{{ skill }}<button type="button" class="inline-flex size-4 items-center justify-center rounded-full text-primary/70 transition-colors hover:bg-primary/20 hover:text-primary" :aria-label="`Remove ${skill}`" @click="removeSkillTag(index)"><X class="size-3" /></button></span><input v-model="skillInput" class="min-w-32 flex-1 border-0 bg-transparent p-1 text-sm outline-none placeholder:text-muted-foreground" placeholder="Type a skill and press Enter" @keydown.enter.prevent="addSkillTag" /></div><p class="text-xs text-muted-foreground">Press Enter after each skill.</p></div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><div class="flex items-center justify-between"><div><CardTitle>Projects</CardTitle><CardDescription>Edit or remove project entries.</CardDescription></div><Button size="sm" variant="outline" @click="addProject"><Plus class="size-4" /> Add</Button></div></CardHeader>
          <CardContent class="space-y-4">
            <div v-for="(project, index) in cv.content.projects" :key="index" class="space-y-3 rounded-xl border border-border p-4"><div class="flex gap-2"><Input v-model="project.title" placeholder="Project title" /><Button size="icon-sm" variant="destructive" @click="cv.content.projects.splice(index, 1)"><Trash2 class="size-4" /></Button></div><Textarea v-model="project.description" rows="3" placeholder="Project contribution and outcome" /><Input :model-value="project.technologies.join(', ')" placeholder="Technologies" @update:model-value="project.technologies = String($event).split(',').map((item) => item.trim()).filter(Boolean)" /></div>
            <p v-if="!cv.content.projects.length" class="text-sm text-muted-foreground">No project entries selected.</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><div class="flex items-center justify-between"><div><CardTitle>Certifications</CardTitle><CardDescription>Keep credentials relevant to the role.</CardDescription></div><Button size="sm" variant="outline" @click="addCertification"><Plus class="size-4" /> Add</Button></div></CardHeader>
          <CardContent class="space-y-4"><div v-for="(certification, index) in cv.content.certifications" :key="index" class="grid gap-3 rounded-xl border border-border p-4 sm:grid-cols-2"><Input v-model="certification.title" placeholder="Certification title" /><div class="flex gap-2"><Input v-model="certification.issuer" placeholder="Issuer" /><Button size="icon-sm" variant="destructive" @click="cv.content.certifications.splice(index, 1)"><Trash2 class="size-4" /></Button></div><Input v-model="certification.issueDate" class="sm:col-span-2" placeholder="Issue date" /></div><p v-if="!cv.content.certifications.length" class="text-sm text-muted-foreground">No certification entries selected.</p></CardContent>
        </Card>

        <Card>
          <CardHeader><div class="flex items-center justify-between"><div><CardTitle>Assessments</CardTitle><CardDescription>Include completed assessment results.</CardDescription></div><Button size="sm" variant="outline" @click="addAssessment"><Plus class="size-4" /> Add</Button></div></CardHeader>
          <CardContent class="space-y-4"><div v-for="(assessment, index) in cv.content.assessments" :key="index" class="grid gap-3 rounded-xl border border-border p-4 sm:grid-cols-[1fr_7rem_8rem_auto]"><Input v-model="assessment.title" placeholder="Assessment title" /><Input v-model.number="assessment.percentage" type="number" min="0" max="100" placeholder="Score" /><Input v-model="assessment.completedAt" placeholder="Completed date" /><Button size="icon-sm" variant="destructive" @click="cv.content.assessments.splice(index, 1)"><Trash2 class="size-4" /></Button></div><p v-if="!cv.content.assessments.length" class="text-sm text-muted-foreground">No assessment entries selected.</p></CardContent>
        </Card>
      </div>

      <div class="xl:sticky xl:top-6 xl:self-start">
        <div class="min-h-[980px] bg-white p-8 text-[11px] leading-relaxed text-slate-900 shadow-xl ring-1 ring-black/5 sm:p-11">
          <header class="border-b-[1.5px] border-slate-900 pb-4"><h2 class="text-2xl font-bold uppercase tracking-tight">{{ cv.content.personal.name }}</h2><p v-if="cv.content.personal.headline" class="mt-1 text-[11px] font-semibold text-slate-700">{{ cv.content.personal.headline }}</p><p class="mt-1 text-[9px] leading-relaxed text-slate-600">{{ [cv.content.personal.email, cv.content.personal.phone, cv.content.personal.location].filter(Boolean).join(' | ') }}</p></header>
          <div class="mt-5 grid grid-cols-[1.55fr_0.95fr] gap-6">
            <div class="min-w-0">
              <section v-if="cv.content.summary"><h3 class="border-b border-slate-900 pb-1 text-[10px] font-bold tracking-wider">SUMMARY</h3><p class="mt-2">{{ cv.content.summary }}</p></section>
              <section v-if="cv.content.projects.length" class="mt-5"><h3 class="border-b border-slate-900 pb-1 text-[10px] font-bold tracking-wider">PROJECT EXPERIENCE</h3><div v-for="(project, index) in cv.content.projects" :key="index" class="mt-3"><p class="font-bold">{{ project.title }}</p><p>{{ project.description }}</p><p v-if="project.technologies.length" class="text-[9px] italic text-slate-600">Technologies: {{ project.technologies.join(', ') }}</p></div></section>
            </div>
            <div class="min-w-0 border-l border-slate-300 pl-5">
              <section v-if="cv.content.skills.length"><h3 class="border-b border-slate-900 pb-1 text-[10px] font-bold tracking-wider">SKILLS</h3><ul class="mt-2 list-disc space-y-1 pl-4"><li v-for="skill in cv.content.skills" :key="skill">{{ skill }}</li></ul></section>
              <section v-if="cv.content.education.length" class="mt-5"><h3 class="border-b border-slate-900 pb-1 text-[10px] font-bold tracking-wider">EDUCATION</h3><div v-for="(education, index) in cv.content.education" :key="index" class="mt-2"><p class="font-bold">{{ education.qualification }}</p><p class="text-[9px] text-slate-600">{{ [education.institution, education.period].filter(Boolean).join(' | ') }}</p></div></section>
              <section v-if="cv.content.certifications.length" class="mt-5"><h3 class="border-b border-slate-900 pb-1 text-[10px] font-bold tracking-wider">CERTIFICATIONS</h3><ul class="mt-2 list-disc space-y-1 pl-4"><li v-for="(certification, index) in cv.content.certifications" :key="index">{{ [certification.title, certification.issuer, certification.issueDate].filter(Boolean).join(' | ') }}</li></ul></section>
              <section v-if="cv.content.assessments.length" class="mt-5"><h3 class="border-b border-slate-900 pb-1 text-[10px] font-bold tracking-wider">ASSESSMENTS</h3><ul class="mt-2 list-disc space-y-1 pl-4"><li v-for="(assessment, index) in cv.content.assessments" :key="index">{{ [assessment.title, assessment.percentage + '%', assessment.completedAt].filter(Boolean).join(' | ') }}</li></ul></section>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

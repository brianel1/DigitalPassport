<script setup lang="ts">
import FileUpload from '@/components/shared/FileUpload.vue'
import EvidencePreview from '@/components/shared/EvidencePreview.vue'
import ReviewHistory from '@/components/shared/ReviewHistory.vue'
import type { EvidenceFileSummary } from '@/types'

import { useConfirm } from '@/composables/useConfirm'
import ListPagination from '@/components/shared/ListPagination.vue'
import { usePagination } from '@/composables/usePagination'
import { useFormDraft } from '@/composables/useFormDraft'

import { computed, onMounted, reactive, ref } from 'vue'
import { ExternalLink, FolderKanban, Plus, Pencil, Eye, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue'
import type { Project, Skill } from '@/types'
import { ApiError, createProject, getProjects, getSkills, removeProject } from '@/lib/api'

const projects = ref<Project[]>([])
const skills = ref<Skill[]>([])
const dialogOpen = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const {confirmAction}=useConfirm()
const fileKey=ref(0)
const previewFile=ref<EvidenceFileSummary|null>(null)
const deleteTarget = ref<Project | null>(null)
const deleteDialogOpen = computed({
  get: () => Boolean(deleteTarget.value),
  set: (open: boolean) => { if (!open) deleteTarget.value = null },
})
const form = reactive<{ editingId:number|null; role: string; repositoryUrl: string; title: string; description: string; technologies: string; projectUrl: string; completionDate: string; evidenceFile: File | null; skillIds: number[] }>({
  editingId:null,role:'',repositoryUrl:'',title: '', description: '', technologies: '', projectUrl: '', completionDate: '', evidenceFile: null, skillIds: [],
})


const {page,items:pagedItems,pageSize}=usePagination(projects)
const savedDrafts=reactive({items:{} as Record<string,typeof form>})
useFormDraft('projects-other-drafts',savedDrafts)
const draftStore=useFormDraft('ProjectsPage',form)
onMounted(async () => {
  try {
    ;[projects.value, skills.value] = await Promise.all([getProjects(), getSkills()])
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Projects could not be loaded.'
  } finally {
    loading.value = false
  }
})

function rememberDraft() { savedDrafts.items[String(form.editingId??'new')]={...form,skillIds:[...form.skillIds]} }
function openNew() {
  rememberDraft()
  const saved=savedDrafts.items.new
  Object.assign(form,saved??emptyForm())
  fileKey.value++;error.value='';dialogOpen.value=true
}
function emptyForm():typeof form { return {editingId:null,role:'',repositoryUrl:'',title:'',description:'',technologies:'',projectUrl:'',completionDate:'',evidenceFile:null,skillIds:[]}}
function editRecord(item:Project) {
  rememberDraft()
  Object.assign(form,savedDrafts.items[String(item.id)]??{editingId:item.id,title:item.title,description:item.description,technologies:item.technologies.join(', '),projectUrl:item.projectUrl??'',completionDate:item.completionDate,role:item.role??'',repositoryUrl:item.repositoryUrl??'',evidenceFile:null,skillIds:[...item.linkedSkills]})
  fileKey.value++;error.value='';dialogOpen.value=true
}

async function discardDraft() {
  if(!await confirmAction({title:'Discard this draft?',description:'Unsubmitted details in this form will be cleared.',confirmLabel:'Discard draft',destructive:true}))return
  delete savedDrafts.items[String(form.editingId??'new')];Object.assign(form,emptyForm());fileKey.value++;draftStore.clear();error.value=''
}

async function submit() {
  if (!form.title.trim() || !form.description.trim() || !form.technologies.trim() || !form.completionDate) {
    error.value = 'Complete all required project fields before submitting.'
    return
  }
  if (!await confirmAction({title:'Submit this project for review?',description:'This record and its supporting evidence will be submitted for administrator review.',confirmLabel:'Submit project'}))return
  submitting.value = true
  error.value = ''
  try {
    const project = await createProject({
      role:form.role, repositoryUrl:form.repositoryUrl,
      title: form.title,
      description: form.description,
      technologies: form.technologies.split(',').map((item) => item.trim()).filter(Boolean),
      projectUrl: form.projectUrl || undefined,
      completionDate: form.completionDate,
      evidence: form.evidenceFile,
      skillIds: form.skillIds,
    },form.editingId)
    projects.value=[project,...projects.value.filter(item=>item.id!==project.id)]
    delete savedDrafts.items[String(form.editingId??'new')]
    Object.assign(form, emptyForm())
    fileKey.value++
    draftStore.clear()
    dialogOpen.value = false
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The project could not be saved.'
  } finally {
    submitting.value = false
  }
}

async function deleteProject() {
  if (!deleteTarget.value || submitting.value) return
  submitting.value=true
  error.value=''
  try {
    await removeProject(deleteTarget.value.id)
    projects.value = projects.value.filter((project) => project.id !== deleteTarget.value?.id)
    deleteTarget.value = null
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The project could not be deleted.'
  } finally { submitting.value=false }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="page-heading">Project portfolio</h1><p class="mt-1 text-muted-foreground">Showcase completed work and the technologies behind it.</p></div>
      <Dialog v-model:open="dialogOpen">
        <Button @click="openNew"><Plus class="size-4" /> Add project</Button>
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
          <DialogHeader><DialogTitle>{{ form.editingId?'Edit & resubmit project':'Add a project' }}</DialogTitle><DialogDescription>Submit project details and supporting evidence.</DialogDescription></DialogHeader>
          <form class="space-y-4" @submit.prevent="submit"><p class="text-xs leading-5 text-muted-foreground">{{form.editingId?'Changes go back to your administrator for review. Existing evidence is kept.':'Unfinished details are kept in this browser tab until you submit.'}}</p><p v-if="error" role="alert" class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive">{{ error }}</p>
            <div class="space-y-2"><Label for="project-title">Project title</Label><Input id="project-title" v-model="form.title" required /></div>
            <div class="space-y-2"><Label for="project-description">Description</Label><Textarea id="project-description" v-model="form.description" rows="4" required /></div>
            <div class="space-y-2"><Label for="project-role">Your role / contribution</Label><Input id="project-role" v-model="form.role" maxlength="255" placeholder="e.g. Backend developer — built the reporting API" /></div><div class="space-y-2"><Label for="repository">Repository URL</Label><Input id="repository" v-model="form.repositoryUrl" type="url" /></div>
            <div class="space-y-2"><Label for="technologies">Technologies</Label><Input id="technologies" v-model="form.technologies" placeholder="Vue.js, Laravel, MySQL" required /></div>
            <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="project-url">Project URL</Label><Input id="project-url" v-model="form.projectUrl" type="url" /></div><div class="space-y-2"><Label for="completion-date">Completion date</Label><Input id="completion-date" v-model="form.completionDate" type="date" required /></div></div>
            <FileUpload id="evidence" :key="fileKey" v-model="form.evidenceFile" label="Evidence file" accept=".pdf,.jpg,.jpeg,.png" :max-mb="10" formats="PDF, JPG or PNG" :disabled="submitting" />
            <fieldset v-if="skills.length" class="space-y-2"><legend class="text-sm font-medium">Skills demonstrated</legend><div class="flex flex-wrap gap-2"><label v-for="skill in skills" :key="skill.id" class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-border px-3 py-1.5 text-xs"><input v-model="form.skillIds" type="checkbox" :value="skill.id" class="accent-primary" />{{ skill.name }}</label></div><p class="text-xs text-muted-foreground">Link skills to this project so the reviewer can see supporting evidence.</p></fieldset>
            <DialogFooter><Button type="button" variant="ghost" :disabled="submitting" @click="discardDraft">Discard draft</Button><Button type="submit" :disabled="submitting">{{ submitting ? 'Saving...' : 'Submit project' }}</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <p v-if="error" role="alert" class="text-sm font-medium text-destructive">{{ error }}</p>
    <p v-if="loading" class="text-sm text-muted-foreground">Loading projects...</p>
    <p v-else-if="projects.length === 0" class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">No projects yet. Add completed work to build your portfolio.</p>

    <div class="grid gap-5 lg:grid-cols-2">
      <Card v-for="project in pagedItems" :key="project.id" class="overflow-hidden">
        <div class="h-1.5 bg-teal"></div>
        <CardHeader>
          <div class="flex items-start justify-between gap-4"><div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary"><FolderKanban class="size-5" /></div><StatusBadge :status="project.status" /></div>
          <CardTitle class="pt-3">{{ project.title }}</CardTitle>
          <CardDescription class="line-clamp-2 leading-relaxed">{{ project.description }}</CardDescription>
        </CardHeader>
        <CardContent>
<p v-if="project.role" class="mb-3 text-sm"><strong>My contribution:</strong> {{project.role}}</p>
          <div class="flex flex-wrap gap-2"><span v-for="technology in project.technologies" :key="technology" class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">{{ technology }}</span></div>
          <p v-if="project.linkedSkills.length" class="mt-3 text-xs text-muted-foreground">Supports: {{ skills.filter((skill) => project.linkedSkills.includes(skill.id)).map((skill) => skill.name).join(', ') }}</p>
          <p v-if="project.adminRemarks" class="mt-4 rounded-lg bg-warning-soft p-3 text-xs leading-6 text-warning"><strong>Review feedback:</strong> {{ project.adminRemarks }}</p><div class="mt-5 flex flex-wrap items-center gap-4 border-t border-border pt-4 text-sm text-muted-foreground">
            <Button v-for="file in project.evidence" :key="file.id" variant="outline" size="sm" @click="previewFile=file"><Eye class="size-4" /><span class="max-w-40 truncate">{{file.name}}</span></Button>
            <a v-if="project.projectUrl" :href="project.projectUrl" target="_blank" rel="noreferrer" class="flex items-center gap-1.5 text-primary hover:underline"><ExternalLink class="size-4" /> View project</a>
            <Button variant="outline" size="sm" @click="editRecord(project)"><Pencil class="size-3.5" />{{project.status==='rejected'?'Update & resubmit':'Edit'}}</Button><Button variant="destructive" size="icon-sm" class="ml-auto" aria-label="Delete project" @click="deleteTarget = project"><Trash2 class="size-4" /></Button>
          </div>
          <p v-if="project.reviewerName" class="mt-3 text-xs text-muted-foreground">Reviewed by {{project.reviewerName}} · {{new Date(project.reviewedAt!).toLocaleDateString('en-MY')}}</p><ReviewHistory :items="project.reviewHistory" />
        </CardContent>
      </Card>
    </div>
    <ListPagination v-model:page="page" :total="projects.length" :page-size="pageSize" label="projects" />
  </div>
  <EvidencePreview v-model="previewFile" />
  <ConfirmDialog v-model:open="deleteDialogOpen" title="Delete this project?" description="This project and its uploaded evidence will be permanently removed from your portfolio." confirm-label="Delete project" :loading="submitting" :error="error" @confirm="deleteProject" />
</template>

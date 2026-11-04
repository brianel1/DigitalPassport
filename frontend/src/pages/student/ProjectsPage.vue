<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ExternalLink, FileText, FolderKanban, Plus, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue'
import type { Project } from '@/types'
import { ApiError, createProject, getProjects, removeProject } from '@/lib/api'

const projects = ref<Project[]>([])
const dialogOpen = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const deleteTarget = ref<Project | null>(null)
const deleteDialogOpen = computed({
  get: () => Boolean(deleteTarget.value),
  set: (open: boolean) => { if (!open) deleteTarget.value = null },
})
const form = reactive<{ title: string; description: string; technologies: string; projectUrl: string; completionDate: string; evidenceFile: File | null }>({
  title: '', description: '', technologies: '', projectUrl: '', completionDate: '', evidenceFile: null,
})

function chooseFile(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  if (file && (!['application/pdf', 'image/jpeg', 'image/png'].includes(file.type) || file.size > 10 * 1024 * 1024)) {
    form.evidenceFile = null
    error.value = 'Evidence must be a PDF, JPG or PNG file up to 10 MB.'
    input.value = ''
    return
  }
  error.value = ''
  form.evidenceFile = file
}

onMounted(async () => {
  try {
    projects.value = await getProjects()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Projects could not be loaded.'
  } finally {
    loading.value = false
  }
})

async function submit() {
  if (!form.title.trim() || !form.description.trim() || !form.technologies.trim() || !form.completionDate) {
    error.value = 'Complete all required project fields before submitting.'
    return
  }
  submitting.value = true
  error.value = ''
  try {
    const project = await createProject({
      title: form.title,
      description: form.description,
      technologies: form.technologies.split(',').map((item) => item.trim()).filter(Boolean),
      projectUrl: form.projectUrl || undefined,
      completionDate: form.completionDate,
      evidence: form.evidenceFile,
    })
    projects.value.unshift(project)
    Object.assign(form, { title: '', description: '', technologies: '', projectUrl: '', completionDate: '', evidenceFile: null })
    dialogOpen.value = false
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The project could not be saved.'
  } finally {
    submitting.value = false
  }
}

async function deleteProject() {
  if (!deleteTarget.value) return
  try {
    await removeProject(deleteTarget.value.id)
    projects.value = projects.value.filter((project) => project.id !== deleteTarget.value?.id)
    deleteTarget.value = null
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The project could not be deleted.'
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="text-2xl font-semibold tracking-tight">Project portfolio</h1><p class="mt-1 text-muted-foreground">Showcase completed work and the technologies behind it.</p></div>
      <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child><Button><Plus class="size-4" /> Add project</Button></DialogTrigger>
        <DialogContent class="sm:max-w-xl">
          <DialogHeader><DialogTitle>Add a project</DialogTitle><DialogDescription>Submit project details and supporting evidence.</DialogDescription></DialogHeader>
          <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><Label for="project-title">Project title</Label><Input id="project-title" v-model="form.title" required /></div>
            <div class="space-y-2"><Label for="project-description">Description</Label><Textarea id="project-description" v-model="form.description" rows="4" required /></div>
            <div class="space-y-2"><Label for="technologies">Technologies</Label><Input id="technologies" v-model="form.technologies" placeholder="Vue.js, Laravel, MySQL" required /></div>
            <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="project-url">Project URL</Label><Input id="project-url" v-model="form.projectUrl" type="url" /></div><div class="space-y-2"><Label for="completion-date">Completion date</Label><Input id="completion-date" v-model="form.completionDate" type="date" required /></div></div>
            <div class="space-y-2"><Label for="evidence">Evidence file</Label><Input id="evidence" type="file" accept="application/pdf,image/jpeg,image/png" @change="chooseFile" /><p class="text-xs text-muted-foreground">PDF, JPG or PNG up to 10 MB.</p></div>
            <DialogFooter><Button type="submit" :disabled="submitting">{{ submitting ? 'Saving...' : 'Submit project' }}</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <p v-if="error" class="text-sm font-medium text-destructive">{{ error }}</p>
    <p v-if="loading" class="text-sm text-muted-foreground">Loading projects...</p>
    <p v-else-if="projects.length === 0" class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">No projects yet. Add completed work to build your portfolio.</p>

    <div class="grid gap-5 lg:grid-cols-2">
      <Card v-for="project in projects" :key="project.id" class="overflow-hidden">
        <div class="h-1.5 bg-gradient-to-r from-primary to-violet-500"></div>
        <CardHeader>
          <div class="flex items-start justify-between gap-4"><div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary"><FolderKanban class="size-5" /></div><StatusBadge :status="project.status" /></div>
          <CardTitle class="pt-3">{{ project.title }}</CardTitle>
          <CardDescription class="line-clamp-2 leading-relaxed">{{ project.description }}</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2"><span v-for="technology in project.technologies" :key="technology" class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">{{ technology }}</span></div>
          <div class="mt-5 flex flex-wrap items-center gap-4 border-t border-border pt-4 text-sm text-muted-foreground">
            <span class="flex items-center gap-1.5"><FileText class="size-4" /> {{ project.evidenceFiles.length }} evidence file(s)</span>
            <a v-if="project.projectUrl" :href="project.projectUrl" target="_blank" rel="noreferrer" class="flex items-center gap-1.5 text-primary hover:underline"><ExternalLink class="size-4" /> View project</a>
            <Button variant="destructive" size="icon-sm" class="ml-auto" aria-label="Delete project" @click="deleteTarget = project"><Trash2 class="size-4" /></Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
  <ConfirmDialog v-model:open="deleteDialogOpen" title="Delete this project?" description="This project and its uploaded evidence will be permanently removed from your portfolio." confirm-label="Delete project" @confirm="deleteProject" />
</template>

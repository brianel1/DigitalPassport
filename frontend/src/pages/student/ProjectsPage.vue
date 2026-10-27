<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ExternalLink, FileText, FolderKanban, Plus, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { usePortalData } from '@/composables/usePortalData'

const { projects, addProject, deleteProject } = usePortalData()
const dialogOpen = ref(false)
const form = reactive({ title: '', description: '', technologies: '', projectUrl: '', completionDate: '', evidenceFile: '' })

function chooseFile(event: Event) {
  const input = event.target as HTMLInputElement
  form.evidenceFile = input.files?.[0]?.name || ''
}

function submit() {
  addProject({
    title: form.title,
    description: form.description,
    technologies: form.technologies.split(',').map((item) => item.trim()).filter(Boolean),
    projectUrl: form.projectUrl || undefined,
    completionDate: form.completionDate,
    evidenceFiles: form.evidenceFile ? [form.evidenceFile] : [],
    linkedSkills: [],
  })
  Object.assign(form, { title: '', description: '', technologies: '', projectUrl: '', completionDate: '', evidenceFile: '' })
  dialogOpen.value = false
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
            <div class="space-y-2"><Label for="evidence">Evidence file</Label><Input id="evidence" type="file" @change="chooseFile" /></div>
            <DialogFooter><Button type="submit">Submit project</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

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
            <Button variant="ghost" size="icon-sm" class="ml-auto" aria-label="Delete project" @click="deleteProject(project.id)"><Trash2 class="size-4" /></Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

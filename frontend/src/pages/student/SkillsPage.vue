<script setup lang="ts">
import { useConfirm } from '@/composables/useConfirm'
import ListPagination from '@/components/shared/ListPagination.vue'
import { usePagination } from '@/composables/usePagination'
import { useFormDraft } from '@/composables/useFormDraft'

import AppSelect from '@/components/shared/AppSelect.vue'
import { RouterLink } from 'vue-router'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { Pencil, Plus, Search, Sparkles, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue'
import type { ProficiencyLevel, Skill, SkillCategory } from '@/types'
import { ApiError, createSkill, getSkills, removeSkill, updateSkill } from '@/lib/api'

const skills = ref<Skill[]>([])
const query = ref('')
const dialogOpen = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const {confirmAction}=useConfirm()
const fileKey=ref(0)
const form = reactive({ name: '', category: 'programming' as SkillCategory, proficiency: 'beginner' as ProficiencyLevel, description: '', editingId: null as number|null })
const editingId = computed({get:()=>form.editingId,set:(value:number|null)=>{form.editingId=value}})
const deleteTarget = ref<Skill | null>(null)
const deleteDialogOpen = computed({
  get: () => Boolean(deleteTarget.value),
  set: (open: boolean) => { if (!open) deleteTarget.value = null },
})

const filteredSkills = computed(() => skills.value.filter((skill) =>
  `${skill.name} ${skill.category} ${skill.proficiency}`.toLowerCase().includes(query.value.toLowerCase()),
))

const {page,items:pagedItems,pageSize}=usePagination(filteredSkills)
watch(query,()=>{page.value=1})
const draftStore=useFormDraft('SkillsPage',form)
onMounted(async () => {
  try {
    skills.value = await getSkills()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Skills could not be loaded.'
  } finally {
    loading.value = false
  }
})

async function discardDraft() {
  if(!await confirmAction({title:'Discard this draft?',description:'Unsubmitted details in this form will be cleared.',confirmLabel:'Discard draft',destructive:true}))return
  Object.assign(form,{ name: '', category: 'programming', proficiency: 'beginner', description: '', editingId:null });fileKey.value++;draftStore.clear();error.value=''
}

async function submit() {
  if (!form.name.trim()) { error.value = 'Enter a skill name.'; return }
  if (!await confirmAction({title:'Submit this skill for review?',description:'Changes will be submitted for administrator review. Editing a verified skill returns it to pending review.',confirmLabel:'Submit skill'}))return
  submitting.value = true
  error.value = ''
  try {
    const saved = editingId.value ? await updateSkill(editingId.value, form) : await createSkill(form)
    if (editingId.value) skills.value = skills.value.map((skill) => skill.id === saved.id ? saved : skill)
    else skills.value.unshift(saved)
    Object.assign(form, { name: '', category: 'programming', proficiency: 'beginner', description: '' })
    editingId.value = null
    fileKey.value++
    draftStore.clear()
    dialogOpen.value = false
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The skill could not be saved.'
  } finally {
    submitting.value = false
  }
}

function openAdd() { dialogOpen.value=true;error.value='' }

function openEdit(skill: Skill) {
  editingId.value = skill.id
  Object.assign(form, { name: skill.name, category: skill.category, proficiency: skill.proficiency, description: skill.description })
  dialogOpen.value = true
}

async function deleteSkill() {
  if (!deleteTarget.value || submitting.value) return
  submitting.value=true
  error.value=''
  error.value = ''
  try {
    await removeSkill(deleteTarget.value.id)
    skills.value = skills.value.filter((skill) => skill.id !== deleteTarget.value?.id)
    deleteTarget.value = null
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The skill could not be deleted.'
  } finally { submitting.value=false }
}

function titleCase(value: string) {
  return value.split('-').map((part) => part[0].toUpperCase() + part.slice(1)).join(' ')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="page-heading">Skills portfolio</h1><p class="mt-1 text-muted-foreground">Document your competencies and connect them to evidence.</p></div>
      <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child><Button @click="openAdd"><Plus class="size-4" /> {{ form.name ? 'Resume skill draft' : 'Add skill' }}</Button></DialogTrigger>
        <DialogContent class="max-h-[90vh] overflow-y-auto content-start sm:max-w-3xl sm:min-h-[34rem] sm:p-8">
          <DialogHeader><DialogTitle>{{ editingId ? 'Edit skill' : 'Add a skill' }}</DialogTitle><DialogDescription>Keep your competency record accurate for administrator review.</DialogDescription></DialogHeader>
          <form class="space-y-4" @submit.prevent="submit"><p class="text-xs leading-5 text-muted-foreground">Unfinished details are kept in this browser tab until you save.</p><p v-if="error" role="alert" class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive">{{ error }}</p>
            <div class="space-y-2"><Label for="skill-name">Skill name</Label><Input id="skill-name" v-model="form.name" required /></div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-2"><Label for="category">Category</Label><AppSelect id="category" v-model="form.category" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm" :options='[{"value": "programming", "label": "Programming"}, {"value": "databases", "label": "Databases"}, {"value": "web-development", "label": "Web Development"}, {"value": "cybersecurity", "label": "Cybersecurity"}, {"value": "data-analysis", "label": "Data Analysis"}, {"value": "networking", "label": "Networking"}, {"value": "cloud-computing", "label": "Cloud Computing"}]' /></div>
              <div class="space-y-2"><Label for="proficiency">Proficiency</Label><AppSelect id="proficiency" v-model="form.proficiency" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm" :options='[{"value": "beginner", "label": "Beginner"}, {"value": "intermediate", "label": "Intermediate"}, {"value": "advanced", "label": "Advanced"}, {"value": "expert", "label": "Expert"}]' /></div>
            </div>
            <div class="space-y-2"><Label for="description">Description</Label><Textarea id="description" v-model="form.description" rows="7" required /></div>
            <DialogFooter><Button type="button" variant="ghost" :disabled="submitting" @click="discardDraft">Discard draft</Button><Button type="submit" :disabled="submitting">{{ submitting ? 'Saving...' : editingId ? 'Save changes' : 'Submit skill' }}</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <div class="relative max-w-md"><Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="query" class="pl-9" placeholder="Search skills" /></div>

    <p v-if="error" role="alert" class="text-sm font-medium text-destructive">{{ error }}</p>
    <p v-if="loading" class="text-sm text-muted-foreground">Loading skills...</p>
    <p v-else-if="filteredSkills.length === 0" class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">{{ query ? 'No skills match your search. Try another keyword.' : 'Add your first skill, then connect it to project or certification evidence.' }}</p>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <Card v-for="skill in pagedItems" :key="skill.id" class="group transition-shadow hover:shadow-md">
        <CardHeader class="pb-3">
          <div class="flex items-start justify-between gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><Sparkles class="size-5" /></div>
            <StatusBadge :status="skill.status" />
          </div>
          <CardTitle class="pt-3 text-lg">{{ skill.name }}</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="mb-4 flex flex-wrap gap-2 text-xs"><span class="rounded-full bg-muted px-2.5 py-1">{{ titleCase(skill.category) }}</span><span class="rounded-full bg-muted px-2.5 py-1">{{ titleCase(skill.proficiency) }}</span></div>
          <p class="min-h-12 text-sm leading-relaxed text-muted-foreground">{{ skill.description }}</p>
          <p v-if="skill.adminRemarks" class="mt-4 rounded-lg bg-warning-soft p-3 text-xs leading-6 text-warning"><strong>Review feedback:</strong> {{ skill.adminRemarks }}</p><RouterLink v-if="!skill.linkedProjects.length && !skill.linkedCertifications.length" to="/student/projects" class="mt-4 block text-xs font-medium text-primary hover:underline">Add a project to support this skill →</RouterLink><div class="mt-5 flex items-center justify-between border-t border-border pt-4 text-xs text-muted-foreground"><span>{{ skill.linkedProjects.length + skill.linkedCertifications.length }} linked evidence record(s)</span><div class="flex items-center gap-1"><Button variant="info" size="icon-sm" aria-label="Edit skill" @click="openEdit(skill)"><Pencil class="size-4" /></Button><Button variant="destructive" size="icon-sm" aria-label="Delete skill" @click="deleteTarget = skill"><Trash2 class="size-4" /></Button></div></div>
        </CardContent>
      </Card>
    </div>
    <ListPagination v-model:page="page" :total="filteredSkills.length" :page-size="pageSize" label="skills" />
  </div>
  <ConfirmDialog v-model:open="deleteDialogOpen" title="Delete this skill?" description="This skill and its verification status will be removed from your profile." confirm-label="Delete skill" :loading="submitting" :error="error" @confirm="deleteSkill" />
</template>

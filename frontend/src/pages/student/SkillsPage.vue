<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Plus, Search, Sparkles, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { usePortalData } from '@/composables/usePortalData'
import type { ProficiencyLevel, SkillCategory } from '@/types'

const { skills, addSkill, deleteSkill } = usePortalData()
const query = ref('')
const dialogOpen = ref(false)
const form = reactive({ name: '', category: 'programming' as SkillCategory, proficiency: 'beginner' as ProficiencyLevel, description: '' })

const filteredSkills = computed(() => skills.value.filter((skill) =>
  `${skill.name} ${skill.category} ${skill.proficiency}`.toLowerCase().includes(query.value.toLowerCase()),
))

function submit() {
  addSkill({ ...form, status: 'pending', linkedProjects: [], linkedCertifications: [], linkedAssessments: [] })
  Object.assign(form, { name: '', category: 'programming', proficiency: 'beginner', description: '' })
  dialogOpen.value = false
}

function titleCase(value: string) {
  return value.split('-').map((part) => part[0].toUpperCase() + part.slice(1)).join(' ')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="text-2xl font-semibold tracking-tight">Skills portfolio</h1><p class="mt-1 text-muted-foreground">Document your competencies and connect them to evidence.</p></div>
      <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child><Button><Plus class="size-4" /> Add skill</Button></DialogTrigger>
        <DialogContent>
          <DialogHeader><DialogTitle>Add a skill</DialogTitle><DialogDescription>Record a competency for administrator review.</DialogDescription></DialogHeader>
          <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><Label for="skill-name">Skill name</Label><Input id="skill-name" v-model="form.name" required /></div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-2"><Label for="category">Category</Label><select id="category" v-model="form.category" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="programming">Programming</option><option value="databases">Databases</option><option value="web-development">Web Development</option><option value="cybersecurity">Cybersecurity</option><option value="data-analysis">Data Analysis</option><option value="networking">Networking</option><option value="cloud-computing">Cloud Computing</option></select></div>
              <div class="space-y-2"><Label for="proficiency">Proficiency</Label><select id="proficiency" v-model="form.proficiency" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option><option value="expert">Expert</option></select></div>
            </div>
            <div class="space-y-2"><Label for="description">Description</Label><Textarea id="description" v-model="form.description" required /></div>
            <DialogFooter><Button type="submit">Submit skill</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <div class="relative max-w-md"><Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="query" class="pl-9" placeholder="Search skills" /></div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <Card v-for="skill in filteredSkills" :key="skill.id" class="group transition-shadow hover:shadow-md">
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
          <div class="mt-5 flex items-center justify-between border-t border-border pt-4 text-xs text-muted-foreground"><span>{{ skill.linkedProjects.length }} linked project(s)</span><Button variant="ghost" size="icon-sm" aria-label="Delete skill" @click="deleteSkill(skill.id)"><Trash2 class="size-4" /></Button></div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

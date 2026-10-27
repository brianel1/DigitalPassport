<script setup lang="ts">
import { reactive, ref } from 'vue'
import { Clock3, ListChecks, Plus, Trash2 } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { usePortalData } from '@/composables/usePortalData'
import type { SkillCategory } from '@/types'

const { assessments, addAssessment, deleteAssessment } = usePortalData()
const dialogOpen = ref(false)
const form = reactive({ title: '', category: 'programming' as SkillCategory, description: '', timeLimit: 10, passingScore: 60 })

function submit() {
  addAssessment({ ...form, questions: [] })
  Object.assign(form, { title: '', category: 'programming', description: '', timeLimit: 10, passingScore: 60 })
  dialogOpen.value = false
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="text-2xl font-semibold tracking-tight">Manage assessments</h1><p class="mt-1 text-muted-foreground">Maintain assessment categories, scores, and question sets.</p></div>
      <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child><Button><Plus class="size-4" /> New assessment</Button></DialogTrigger>
        <DialogContent>
          <DialogHeader><DialogTitle>Create assessment</DialogTitle><DialogDescription>Set the assessment details before adding questions.</DialogDescription></DialogHeader>
          <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><Label for="assessment-title">Title</Label><Input id="assessment-title" v-model="form.title" required /></div>
            <div class="space-y-2"><Label for="assessment-category">Category</Label><select id="assessment-category" v-model="form.category" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="programming">Programming</option><option value="databases">Databases</option><option value="web-development">Web Development</option><option value="cybersecurity">Cybersecurity</option><option value="data-analysis">Data Analysis</option></select></div>
            <div class="space-y-2"><Label for="assessment-description">Description</Label><Textarea id="assessment-description" v-model="form.description" required /></div>
            <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="time-limit">Time limit (minutes)</Label><Input id="time-limit" v-model.number="form.timeLimit" type="number" min="1" required /></div><div class="space-y-2"><Label for="passing-score">Passing score (%)</Label><Input id="passing-score" v-model.number="form.passingScore" type="number" min="1" max="100" required /></div></div>
            <DialogFooter><Button type="submit">Create assessment</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
      <Card v-for="assessment in assessments" :key="assessment.id">
        <CardHeader>
          <div class="flex items-start justify-between gap-3"><div class="flex size-11 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600"><ListChecks class="size-5" /></div><Badge variant="secondary">{{ assessment.category.replace('-', ' ') }}</Badge></div>
          <CardTitle class="pt-4 text-lg">{{ assessment.title }}</CardTitle>
          <CardDescription class="leading-relaxed">{{ assessment.description }}</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap items-center gap-4 border-t border-border pt-4 text-sm text-muted-foreground"><span>{{ assessment.questionCount }} questions</span><span class="flex items-center gap-1.5"><Clock3 class="size-4" /> {{ assessment.timeLimit }} minutes</span><span>Pass: {{ assessment.passingScore }}%</span><Button variant="ghost" size="icon-sm" class="ml-auto" aria-label="Delete assessment" @click="deleteAssessment(assessment.id)"><Trash2 class="size-4" /></Button></div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

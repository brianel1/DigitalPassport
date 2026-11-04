<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { CheckCircle2, Clock3, Edit3, ListChecks, Plus, Save, Trash2, XCircle } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue'
import type { Assessment, AssessmentQuestion, SkillCategory } from '@/types'
import {
  ApiError,
  createAssessment,
  createAssessmentQuestion,
  getAdminAssessment,
  getAdminAssessments,
  removeAssessment,
  removeAssessmentQuestion,
  toggleAssessmentPublish,
  updateAssessment,
  updateAssessmentQuestion,
} from '@/lib/api'

const assessments = ref<Assessment[]>([])
const dialogOpen = ref(false)
const editorOpen = ref(false)
const metadataOpen = ref(false)
const loading = ref(true)
const submitting = ref(false)
const questionSubmitting = ref(false)
const error = ref('')
const questionError = ref('')
const metadataError = ref('')
const selectedAssessment = ref<Assessment | null>(null)
const deleteTarget = ref<Assessment | null>(null)
const questionDeleteTarget = ref<AssessmentQuestion | null>(null)
const publishTarget = ref<Assessment | null>(null)
const deleteDialogOpen = computed({
  get: () => Boolean(deleteTarget.value),
  set: (open: boolean) => { if (!open) deleteTarget.value = null },
})
const questionDeleteDialogOpen = computed({
  get: () => Boolean(questionDeleteTarget.value),
  set: (open: boolean) => { if (!open) questionDeleteTarget.value = null },
})
const publishDialogOpen = computed({
  get: () => Boolean(publishTarget.value),
  set: (open: boolean) => { if (!open) publishTarget.value = null },
})
const editingQuestionId = ref<number | null>(null)
const form = reactive({ title: '', category: 'programming' as SkillCategory, description: '', timeLimit: 10, passingScore: 60 })
const questionForm = reactive({ question: '', options: ['', ''], correctOption: 0, points: 1 })

onMounted(async () => {
  try {
    assessments.value = await getAdminAssessments()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Assessments could not be loaded.'
  } finally {
    loading.value = false
  }
})

function replaceAssessment(updated: Assessment) {
  const index = assessments.value.findIndex((assessment) => assessment.id === updated.id)
  if (index >= 0) assessments.value[index] = updated
}

async function submit() {
  submitting.value = true
  error.value = ''
  try {
    assessments.value.unshift(await createAssessment(form))
    Object.assign(form, { title: '', category: 'programming', description: '', timeLimit: 10, passingScore: 60 })
    dialogOpen.value = false
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The assessment could not be created.'
  } finally {
    submitting.value = false
  }
}

async function openEditor(assessment: Assessment) {
  questionError.value = ''
  try {
    selectedAssessment.value = await getAdminAssessment(assessment.id)
    replaceAssessment(selectedAssessment.value)
    editorOpen.value = true
    resetQuestion()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The assessment could not be loaded.'
  }
}

function openMetadata(assessment: Assessment) {
  selectedAssessment.value = assessment
  Object.assign(form, {
    title: assessment.title,
    category: assessment.category,
    description: assessment.description,
    timeLimit: assessment.timeLimit,
    passingScore: assessment.passingScore,
  })
  metadataError.value = ''
  metadataOpen.value = true
}

async function saveMetadata() {
  if (!selectedAssessment.value) return
  if (!form.title.trim() || !form.description.trim() || form.timeLimit < 1 || form.passingScore < 1 || form.passingScore > 100) {
    metadataError.value = 'Complete the assessment details with valid values.'
    return
  }
  submitting.value = true
  metadataError.value = ''
  try {
    const updated = await updateAssessment(selectedAssessment.value.id, { ...form, title: form.title.trim(), description: form.description.trim() })
    replaceAssessment(updated)
    selectedAssessment.value = { ...selectedAssessment.value, ...updated }
    metadataOpen.value = false
  } catch (cause) {
    metadataError.value = cause instanceof ApiError ? cause.message : 'The assessment details could not be saved.'
  } finally {
    submitting.value = false
  }
}

function resetQuestion() {
  editingQuestionId.value = null
  questionForm.question = ''
  questionForm.options = ['', '']
  questionForm.correctOption = 0
  questionForm.points = 1
  questionError.value = ''
}

function editQuestion(question: AssessmentQuestion) {
  editingQuestionId.value = question.id
  questionForm.question = question.question
  questionForm.options = [...question.options]
  questionForm.correctOption = question.correctOption ?? 0
  questionForm.points = question.points ?? 1
  questionError.value = ''
}

function addOption() {
  if (questionForm.options.length < 6) questionForm.options.push('')
}

function removeOption(index: number) {
  if (questionForm.options.length <= 2) return
  questionForm.options.splice(index, 1)
  if (questionForm.correctOption >= questionForm.options.length) questionForm.correctOption = questionForm.options.length - 1
}

async function saveQuestion() {
  if (!selectedAssessment.value) return
  const options = questionForm.options.map((option) => option.trim())
  if (!questionForm.question.trim() || options.some((option) => !option)) {
    questionError.value = 'Enter the question and every answer option.'
    return
  }

  questionSubmitting.value = true
  questionError.value = ''
  try {
    const payload = {
      question: questionForm.question.trim(),
      options,
      correctOption: questionForm.correctOption,
      points: questionForm.points,
    }
    const saved = editingQuestionId.value
      ? await updateAssessmentQuestion(selectedAssessment.value.id, editingQuestionId.value, payload)
      : await createAssessmentQuestion(selectedAssessment.value.id, payload)
    const questions = [...(selectedAssessment.value.questions ?? [])]
    const index = questions.findIndex((question) => question.id === saved.id)
    if (index >= 0) questions[index] = saved
    else questions.push(saved)
    selectedAssessment.value = { ...selectedAssessment.value, questions, questionCount: questions.length }
    replaceAssessment(selectedAssessment.value)
    resetQuestion()
  } catch (cause) {
    questionError.value = cause instanceof ApiError ? cause.message : 'The question could not be saved.'
  } finally {
    questionSubmitting.value = false
  }
}

async function deleteQuestion() {
  if (!selectedAssessment.value) return
  if (!questionDeleteTarget.value) return
  questionError.value = ''
  try {
    await removeAssessmentQuestion(selectedAssessment.value.id, questionDeleteTarget.value.id)
    const questions = (selectedAssessment.value.questions ?? []).filter((item) => item.id !== questionDeleteTarget.value?.id)
    selectedAssessment.value = { ...selectedAssessment.value, questions, questionCount: questions.length }
    replaceAssessment(selectedAssessment.value)
    if (editingQuestionId.value === questionDeleteTarget.value?.id) resetQuestion()
    questionDeleteTarget.value = null
  } catch (cause) {
    questionError.value = cause instanceof ApiError ? cause.message : 'The question could not be deleted.'
  }
}

async function togglePublish() {
  if (!publishTarget.value) return
  error.value = ''
  try {
    const result = await toggleAssessmentPublish(publishTarget.value.id)
    publishTarget.value.isPublished = result.isPublished
    publishTarget.value = null
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The assessment status could not be updated.'
  }
}

async function deleteAssessment() {
  if (!deleteTarget.value) return
  try {
    await removeAssessment(deleteTarget.value.id)
    assessments.value = assessments.value.filter((assessment) => assessment.id !== deleteTarget.value?.id)
    if (selectedAssessment.value?.id === deleteTarget.value?.id) editorOpen.value = false
    deleteTarget.value = null
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The assessment could not be deleted.'
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Manage assessments</h1>
        <p class="mt-1 text-muted-foreground">Create assessment questions, publish available sets, and maintain scoring rules.</p>
      </div>
      <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child><Button><Plus class="size-4" /> New assessment</Button></DialogTrigger>
        <DialogContent>
          <DialogHeader><DialogTitle>Create assessment</DialogTitle><DialogDescription>Set the assessment details before adding questions.</DialogDescription></DialogHeader>
          <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><Label for="assessment-title">Title</Label><Input id="assessment-title" v-model="form.title" required /></div>
            <div class="space-y-2"><Label for="assessment-category">Category</Label><select id="assessment-category" v-model="form.category" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="programming">Programming</option><option value="databases">Databases</option><option value="web-development">Web Development</option><option value="cybersecurity">Cybersecurity</option><option value="data-analysis">Data Analysis</option><option value="networking">Networking</option><option value="cloud-computing">Cloud Computing</option><option value="other">Other</option></select></div>
            <div class="space-y-2"><Label for="assessment-description">Description</Label><Textarea id="assessment-description" v-model="form.description" required /></div>
            <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="time-limit">Time limit (minutes)</Label><Input id="time-limit" v-model.number="form.timeLimit" type="number" min="1" required /></div><div class="space-y-2"><Label for="passing-score">Passing score (%)</Label><Input id="passing-score" v-model.number="form.passingScore" type="number" min="1" max="100" required /></div></div>
            <DialogFooter><Button type="submit" :disabled="submitting">{{ submitting ? 'Creating...' : 'Create assessment' }}</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <p v-if="error" class="text-sm font-medium text-destructive">{{ error }}</p>
    <p v-if="loading" class="text-sm text-muted-foreground">Loading assessments...</p>

    <div class="grid gap-5 lg:grid-cols-2">
      <Card v-for="assessment in assessments" :key="assessment.id">
        <CardHeader>
          <div class="flex items-start justify-between gap-3">
            <div class="flex size-11 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600"><ListChecks class="size-5" /></div>
            <Badge :variant="assessment.isPublished ? 'default' : 'secondary'">{{ assessment.isPublished ? 'Published' : 'Draft' }}</Badge>
          </div>
          <CardTitle class="pt-4 text-lg">{{ assessment.title }}</CardTitle>
          <CardDescription class="leading-relaxed">{{ assessment.description }}</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap items-center gap-4 border-t border-border pt-4 text-sm text-muted-foreground">
            <span>{{ assessment.questionCount }} questions</span>
            <span class="flex items-center gap-1.5"><Clock3 class="size-4" /> {{ assessment.timeLimit }} minutes</span>
            <span>Pass: {{ assessment.passingScore }}%</span>
          </div>
          <div class="mt-4 flex flex-wrap gap-2">
            <Button variant="outline" size="sm" @click="openMetadata(assessment)"><Edit3 class="size-4" /> Details</Button>
            <Button variant="outline" size="sm" @click="openEditor(assessment)"><ListChecks class="size-4" /> Questions</Button>
            <Button variant="outline" size="sm" @click="publishTarget = assessment">
              <XCircle v-if="assessment.isPublished" class="size-4" />
              <CheckCircle2 v-else class="size-4" />
              {{ assessment.isPublished ? 'Unpublish' : 'Publish' }}
            </Button>
            <Button variant="ghost" size="icon-sm" class="ml-auto" aria-label="Delete assessment" @click="deleteTarget = assessment"><Trash2 class="size-4" /></Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <Dialog v-model:open="editorOpen">
      <DialogContent v-if="selectedAssessment" class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>Edit questions: {{ selectedAssessment.title }}</DialogTitle>
          <DialogDescription>Add or update the questions before publishing this assessment.</DialogDescription>
        </DialogHeader>

        <div class="space-y-3">
          <div v-for="question in selectedAssessment.questions" :key="question.id" class="rounded-xl border border-border p-4">
            <div class="flex items-start justify-between gap-3">
              <div><p class="font-medium">{{ question.question }}</p><p class="mt-2 text-xs text-muted-foreground">{{ question.options.length }} options · {{ question.points ?? 1 }} point(s)</p></div>
              <div class="flex gap-1"><Button variant="ghost" size="icon-sm" aria-label="Edit question" @click="editQuestion(question)"><Edit3 class="size-4" /></Button><Button variant="ghost" size="icon-sm" aria-label="Delete question" @click="questionDeleteTarget = question"><Trash2 class="size-4" /></Button></div>
            </div>
          </div>
          <p v-if="!selectedAssessment.questions?.length" class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">No questions have been added yet.</p>
        </div>

        <form class="space-y-4 border-t border-border pt-5" @submit.prevent="saveQuestion">
          <div class="flex items-center justify-between"><h3 class="font-semibold">{{ editingQuestionId ? 'Edit question' : 'Add question' }}</h3><Button v-if="editingQuestionId" type="button" variant="ghost" size="sm" @click="resetQuestion">Clear</Button></div>
          <div class="space-y-2"><Label for="question-text">Question</Label><Textarea id="question-text" v-model="questionForm.question" required /></div>
          <div class="space-y-3">
            <Label>Answer options</Label>
            <div v-for="(_, index) in questionForm.options" :key="index" class="flex items-center gap-2">
              <input v-model.number="questionForm.correctOption" type="radio" :value="index" :aria-label="'Correct option ' + (index + 1)" />
              <Input v-model="questionForm.options[index]" :placeholder="'Option ' + (index + 1)" required />
              <Button v-if="questionForm.options.length > 2" type="button" variant="ghost" size="icon-sm" aria-label="Remove option" @click="removeOption(index)"><Trash2 class="size-4" /></Button>
            </div>
            <Button v-if="questionForm.options.length < 6" type="button" variant="outline" size="sm" @click="addOption"><Plus class="size-4" /> Add option</Button>
          </div>
          <div class="max-w-[10rem] space-y-2"><Label for="question-points">Points</Label><Input id="question-points" v-model.number="questionForm.points" type="number" min="1" max="10" required /></div>
          <p v-if="questionError" class="text-sm font-medium text-destructive">{{ questionError }}</p>
          <DialogFooter><Button type="submit" :disabled="questionSubmitting"><Save class="size-4" /> {{ questionSubmitting ? 'Saving...' : 'Save question' }}</Button></DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="metadataOpen"><DialogContent v-if="selectedAssessment"><DialogHeader><DialogTitle>Edit assessment details</DialogTitle><DialogDescription>Update the information and scoring rules for {{ selectedAssessment.title }}.</DialogDescription></DialogHeader><form class="space-y-4" @submit.prevent="saveMetadata"><div class="space-y-2"><Label for="edit-assessment-title">Title</Label><Input id="edit-assessment-title" v-model="form.title" required /></div><div class="space-y-2"><Label for="edit-assessment-category">Category</Label><select id="edit-assessment-category" v-model="form.category" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="programming">Programming</option><option value="databases">Databases</option><option value="web-development">Web Development</option><option value="cybersecurity">Cybersecurity</option><option value="data-analysis">Data Analysis</option><option value="networking">Networking</option><option value="cloud-computing">Cloud Computing</option><option value="other">Other</option></select></div><div class="space-y-2"><Label for="edit-assessment-description">Description</Label><Textarea id="edit-assessment-description" v-model="form.description" required /></div><div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="edit-time-limit">Time limit (minutes)</Label><Input id="edit-time-limit" v-model.number="form.timeLimit" type="number" min="1" max="180" required /></div><div class="space-y-2"><Label for="edit-passing-score">Passing score (%)</Label><Input id="edit-passing-score" v-model.number="form.passingScore" type="number" min="1" max="100" required /></div></div><p v-if="metadataError" class="text-sm font-medium text-destructive">{{ metadataError }}</p><DialogFooter><Button type="submit" :disabled="submitting">{{ submitting ? 'Saving...' : 'Save details' }}</Button></DialogFooter></form></DialogContent></Dialog>
    <ConfirmDialog v-model:open="deleteDialogOpen" title="Delete this assessment?" description="The assessment, its questions and student attempts will be permanently removed." confirm-label="Delete assessment" @confirm="deleteAssessment" />
    <ConfirmDialog v-model:open="questionDeleteDialogOpen" title="Delete this question?" description="This question will be removed from the assessment and cannot be restored." confirm-label="Delete question" @confirm="deleteQuestion" />
    <ConfirmDialog v-model:open="publishDialogOpen" :title="publishTarget?.isPublished ? 'Unpublish this assessment?' : 'Publish this assessment?'" :description="publishTarget?.isPublished ? 'Students will no longer be able to start this assessment.' : 'Students will be able to see and take this assessment after it is published.'" :confirm-label="publishTarget?.isPublished ? 'Unpublish assessment' : 'Publish assessment'" :variant="publishTarget?.isPublished ? 'warning' : 'success'" :destructive="false" @confirm="togglePublish" />
  </div>
</template>

<script setup lang="ts">
import DecimalInput from '@/components/shared/DecimalInput.vue'
import ListPagination from '@/components/shared/ListPagination.vue'
import { usePagination } from '@/composables/usePagination'
import { useFormDraft } from '@/composables/useFormDraft'
import { useConfirm } from '@/composables/useConfirm'
import AppSelect from '@/components/shared/AppSelect.vue'
import { computed, onMounted, reactive, ref, watch } from 'vue'
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
  createAssessmentVersion,
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
const form = reactive({ title: '', category: 'programming' as SkillCategory, description: '', timeLimit: 10, passingScore: 60, skillNames:'' })
const questionForm = reactive({ question: '', options: ['', ''], correctOption: 0, points: 1 })

const {page,items:pagedAssessments,pageSize}=usePagination(assessments,6)
const {page:questionPage,items:pagedQuestions}=usePagination(()=>selectedAssessment.value?.questions??[],5)
const {confirmAction}=useConfirm()
const drafts=reactive({metadata:{} as Record<string,typeof form>,questions:{} as Record<string,{form:typeof questionForm;editingId:number|null}>})
useFormDraft('admin-assessments',drafts)
const metadataKey=ref('new')
if(drafts.metadata.new)Object.assign(form,drafts.metadata.new)
watch(form,()=>{drafts.metadata[metadataKey.value]=JSON.parse(JSON.stringify(form))},{deep:true})
watch([questionForm,editingQuestionId],()=>{if(selectedAssessment.value)drafts.questions[selectedAssessment.value.id]={form:JSON.parse(JSON.stringify(questionForm)),editingId:editingQuestionId.value}},{deep:true})
function openCreate(){ metadataKey.value='new';Object.assign(form,drafts.metadata.new??{title:'',category:'programming',description:'',timeLimit:10,passingScore:60});dialogOpen.value=true }
async function discardQuestion(){if(await confirmAction({title:'Clear this question draft?',description:'Unfinished question text and answer options will be cleared.',confirmLabel:'Clear draft',destructive:true}))resetQuestion()}
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

async function newVersion(assessment:Assessment) {
  if(!await confirmAction({title:'Create a new assessment version?',description:'Existing attempts and scores are kept. The new version starts as an unpublished draft.',confirmLabel:'Create version'}))return
  submitting.value=true;error.value=''
  try {const copy=await createAssessmentVersion(assessment.id);assessments.value.unshift(copy);await openEditor(copy)}
  catch(cause){error.value=cause instanceof ApiError?cause.message:'The version could not be created.'}
  finally{submitting.value=false}
}
async function submit() {
  submitting.value = true
  error.value = ''
  try {
    assessments.value.unshift(await createAssessment({...form,skillNames:form.skillNames.split(',').map(s=>s.trim()).filter(Boolean)}))
    Object.assign(form, { title: '', category: 'programming', description: '', timeLimit: 10, passingScore: 60, skillNames:'' })
    delete drafts.metadata.new
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
    questionPage.value=1
    const pending=drafts.questions[assessment.id]
    if(pending){Object.assign(questionForm,pending.form);editingQuestionId.value=pending.editingId}else resetQuestion()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The assessment could not be loaded.'
  }
}

function openMetadata(assessment: Assessment) {
  selectedAssessment.value = assessment
  metadataKey.value=String(assessment.id)
  Object.assign(form, {
    title: assessment.title,
    category: assessment.category,
    description: assessment.description,
    timeLimit: assessment.timeLimit,
    passingScore: assessment.passingScore,
    skillNames:assessment.skillNames?.join(', ')??'',
  })
  if(drafts.metadata[metadataKey.value])Object.assign(form,drafts.metadata[metadataKey.value])
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
    const updated = await updateAssessment(selectedAssessment.value.id, { ...form, skillNames:form.skillNames.split(',').map(s=>s.trim()).filter(Boolean), title: form.title.trim(), description: form.description.trim() })
    replaceAssessment(updated)
    selectedAssessment.value = { ...selectedAssessment.value, ...updated }
    delete drafts.metadata[metadataKey.value]
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
  if (!questionDeleteTarget.value || questionSubmitting.value) return
  questionSubmitting.value=true
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
  } finally { questionSubmitting.value=false }
}

async function togglePublish() {
  if (!publishTarget.value || submitting.value) return
  submitting.value=true
  error.value = ''
  try {
    const result = await toggleAssessmentPublish(publishTarget.value.id)
    publishTarget.value.isPublished = result.isPublished
    publishTarget.value = null
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The assessment status could not be updated.'
  } finally { submitting.value=false }
}

async function deleteAssessment() {
  if (!deleteTarget.value || submitting.value) return
  submitting.value=true
  try {
    await removeAssessment(deleteTarget.value.id)
    assessments.value = assessments.value.filter((assessment) => assessment.id !== deleteTarget.value?.id)
    if (selectedAssessment.value?.id === deleteTarget.value?.id) editorOpen.value = false
    deleteTarget.value = null
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The assessment could not be deleted.'
  } finally { submitting.value=false }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="page-heading">Manage assessments</h1>
        <p class="mt-1 text-muted-foreground">Create assessment questions, publish available sets, and maintain scoring rules.</p>
      </div>
      <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child><Button @click="openCreate"><Plus class="size-4" /> New assessment</Button></DialogTrigger>
        <DialogContent>
          <DialogHeader><DialogTitle>Create assessment</DialogTitle><DialogDescription>Set the assessment details before adding questions.</DialogDescription></DialogHeader>
          <form class="space-y-4" @submit.prevent="submit"><p v-if="error" role="alert" class="text-sm text-destructive">{{ error }}</p><p class="text-xs text-muted-foreground">Unfinished details are kept in this browser tab.</p>
            <div class="space-y-2"><Label for="assessment-title">Title</Label><Input id="assessment-title" v-model="form.title" required /></div>
            <div class="space-y-2"><Label for="assessment-category">Category</Label><AppSelect id="assessment-category" v-model="form.category" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm" :options='[{"value": "programming", "label": "Programming"}, {"value": "databases", "label": "Databases"}, {"value": "web-development", "label": "Web Development"}, {"value": "cybersecurity", "label": "Cybersecurity"}, {"value": "data-analysis", "label": "Data Analysis"}, {"value": "networking", "label": "Networking"}, {"value": "cloud-computing", "label": "Cloud Computing"}, {"value": "other", "label": "Other"}]' /></div>
            <div class="space-y-2"><Label for="assessment-description">Description</Label><Textarea id="assessment-description" v-model="form.description" required /></div>
            <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="time-limit">Time limit (minutes)</Label><DecimalInput id="time-limit" v-model="form.timeLimit" min="1" required :decimals="0" /></div><div class="space-y-2"><Label for="passing-score">Passing score (%)</Label><DecimalInput id="passing-score" v-model="form.passingScore" min="1" max="100" required :decimals="0" /></div></div>
            <label class="block text-sm font-medium">Skills assessed<Input v-model="form.skillNames" class="mt-2" placeholder="e.g. JavaScript, SQL" /><span class="mt-1 block text-xs text-muted-foreground">Only list skills directly tested by the questions. Separate with commas.</span></label><DialogFooter><Button type="submit" :disabled="submitting">{{ submitting ? 'Creating...' : 'Create assessment' }}</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <p v-if="error" class="text-sm font-medium text-destructive">{{ error }}</p>
    <p v-if="loading" class="text-sm text-muted-foreground">Loading assessments...</p>

    <div class="grid gap-5 lg:grid-cols-2">
      <Card v-for="assessment in pagedAssessments" :key="assessment.id">
        <CardHeader>
          <div class="flex items-start justify-between gap-3">
            <div class="flex size-11 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600"><ListChecks class="size-5" /></div>
            <Badge :variant="assessment.isPublished ? 'default' : 'secondary'">{{ assessment.isPublished ? 'Published' : 'Draft' }}</Badge>
          </div>
          <CardTitle class="pt-4 text-lg">{{ assessment.title }} <span class="text-xs text-muted-foreground">v{{assessment.version??1}}</span></CardTitle>
          <CardDescription class="leading-relaxed">{{ assessment.description }}</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap items-center gap-4 border-t border-border pt-4 text-sm text-muted-foreground">
            <span>{{ assessment.questionCount }} questions</span>
            <span class="flex items-center gap-1.5"><Clock3 class="size-4" /> {{ assessment.timeLimit }} minutes</span>
            <span>Pass: {{ assessment.passingScore }}%</span>
          </div>
          <p v-if="assessment.locked" class="mt-3 text-xs text-muted-foreground">This version has student attempts. Create a new version to make changes.</p><div class="mt-4 flex flex-wrap gap-2"><Button v-if="assessment.locked" variant="outline" size="sm" :disabled="submitting" @click="newVersion(assessment)">New version</Button>
            <Button variant="outline" size="sm" :disabled="assessment.locked" @click="openMetadata(assessment)"><Edit3 class="size-4" /> Details</Button>
            <Button variant="outline" size="sm" @click="openEditor(assessment)"><ListChecks class="size-4" /> Questions</Button>
            <Button variant="outline" size="sm" @click="publishTarget = assessment">
              <XCircle v-if="assessment.isPublished" class="size-4" />
              <CheckCircle2 v-else class="size-4" />
              {{ assessment.isPublished ? 'Unpublish' : 'Publish' }}
            </Button>
            <Button variant="ghost" size="icon-sm" class="ml-auto" aria-label="Delete assessment" :disabled="assessment.locked" @click="deleteTarget = assessment"><Trash2 class="size-4" /></Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <ListPagination v-model:page="page" :total="assessments.length" :page-size="pageSize" label="assessments" />
    <Dialog v-model:open="editorOpen">
      <DialogContent v-if="selectedAssessment" class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>Edit questions: {{ selectedAssessment.title }}</DialogTitle>
          <DialogDescription>Add or update the questions before publishing this assessment.</DialogDescription>
        </DialogHeader>

        <div class="space-y-3">
          <div v-for="question in pagedQuestions" :key="question.id" class="rounded-xl border border-border p-4">
            <div class="flex items-start justify-between gap-3">
              <div><p class="font-medium">{{ question.question }}</p><p class="mt-2 text-xs text-muted-foreground">{{ question.options.length }} options · {{ question.points ?? 1 }} point(s)</p></div>
              <div class="flex gap-1"><Button variant="ghost" size="icon-sm" aria-label="Edit question" :disabled="selectedAssessment.locked" @click="editQuestion(question)"><Edit3 class="size-4" /></Button><Button variant="ghost" size="icon-sm" aria-label="Delete question" :disabled="selectedAssessment.locked" @click="questionDeleteTarget = question"><Trash2 class="size-4" /></Button></div>
            </div>
          </div>
          <p v-if="!selectedAssessment.questions?.length" class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">No questions have been added yet.</p>
        </div>

        <ListPagination v-model:page="questionPage" :total="selectedAssessment.questions?.length??0" :page-size="5" label="questions" />
        <p v-if="selectedAssessment.locked" class="rounded-lg bg-accent p-4 text-sm">Questions are preserved for existing attempts. Close this view and choose New version to edit a copy.</p><form v-else class="space-y-4 border-t border-border pt-5" @submit.prevent="saveQuestion">
          <div class="flex items-center justify-between"><h3 class="font-semibold">{{ editingQuestionId ? 'Edit question' : 'Add question' }}</h3><Button v-if="editingQuestionId || questionForm.question" type="button" variant="ghost" size="sm" @click="discardQuestion">Clear</Button></div>
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
          <div class="max-w-[10rem] space-y-2"><Label for="question-points">Points</Label><DecimalInput id="question-points" v-model="questionForm.points" min="1" max="10" required :decimals="0" /></div>
          <p v-if="questionError" class="text-sm font-medium text-destructive">{{ questionError }}</p>
          <DialogFooter><Button type="submit" :disabled="questionSubmitting"><Save class="size-4" /> {{ questionSubmitting ? 'Saving...' : 'Save question' }}</Button></DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="metadataOpen"><DialogContent v-if="selectedAssessment"><DialogHeader><DialogTitle>Edit assessment details</DialogTitle><DialogDescription>Update the information and scoring rules for {{ selectedAssessment.title }}.</DialogDescription></DialogHeader><form class="space-y-4" @submit.prevent="saveMetadata"><div class="space-y-2"><Label for="edit-assessment-title">Title</Label><Input id="edit-assessment-title" v-model="form.title" required /></div><div class="space-y-2"><Label for="edit-assessment-category">Category</Label><AppSelect id="edit-assessment-category" v-model="form.category" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm" :options='[{"value": "programming", "label": "Programming"}, {"value": "databases", "label": "Databases"}, {"value": "web-development", "label": "Web Development"}, {"value": "cybersecurity", "label": "Cybersecurity"}, {"value": "data-analysis", "label": "Data Analysis"}, {"value": "networking", "label": "Networking"}, {"value": "cloud-computing", "label": "Cloud Computing"}, {"value": "other", "label": "Other"}]' /></div><div class="space-y-2"><Label for="edit-assessment-description">Description</Label><Textarea id="edit-assessment-description" v-model="form.description" required /></div><div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="edit-time-limit">Time limit (minutes)</Label><DecimalInput id="edit-time-limit" v-model="form.timeLimit" min="1" max="180" required :decimals="0" /></div><div class="space-y-2"><Label for="edit-passing-score">Passing score (%)</Label><DecimalInput id="edit-passing-score" v-model="form.passingScore" min="1" max="100" required :decimals="0" /></div></div><label class="block text-sm font-medium">Skills assessed<Input v-model="form.skillNames" class="mt-2" placeholder="JavaScript, SQL" /></label><p v-if="metadataError" class="text-sm font-medium text-destructive">{{ metadataError }}</p><DialogFooter><Button type="submit" :disabled="submitting">{{ submitting ? 'Saving...' : 'Save details' }}</Button></DialogFooter></form></DialogContent></Dialog>
    <ConfirmDialog v-model:open="deleteDialogOpen" title="Delete this assessment?" description="The assessment, its questions and student attempts will be permanently removed." confirm-label="Delete assessment" :loading="submitting" :error="error" @confirm="deleteAssessment" />
    <ConfirmDialog v-model:open="questionDeleteDialogOpen" title="Delete this question?" description="This question will be removed from the assessment and cannot be restored." confirm-label="Delete question" :loading="questionSubmitting" :error="questionError" @confirm="deleteQuestion" />
    <ConfirmDialog v-model:open="publishDialogOpen" :title="publishTarget?.isPublished ? 'Unpublish this assessment?' : 'Publish this assessment?'" :description="publishTarget?.isPublished ? 'Students will no longer be able to start this assessment.' : 'Students will be able to see and take this assessment after it is published.'" :confirm-label="publishTarget?.isPublished ? 'Unpublish assessment' : 'Publish assessment'" :variant="publishTarget?.isPublished ? 'warning' : 'success'" :destructive="false" :loading="submitting" :error="error" @confirm="togglePublish" />
  </div>
</template>

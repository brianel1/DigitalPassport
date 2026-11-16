<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, reactive } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { ArrowLeft, CheckCircle2, Clock3, Trophy } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import type { Assessment, AssessmentAttempt } from '@/types'
import { useFormDraft } from '@/composables/useFormDraft'
import { useConfirm } from '@/composables/useConfirm'
import { ApiError, startAssessment, submitAssessment } from '@/lib/api'

const route = useRoute()
const assessmentId = Number(route.params.id)
const assessment = ref<Assessment | null>(null)
const attemptId = ref<number | null>(null)
const attemptDraft=reactive({attemptId:0,currentIndex:0,answers:{} as Record<number,number>})
const draftStore=useFormDraft('assessment-'+assessmentId,attemptDraft)
const {confirmAction,pending,finish}=useConfirm()
const currentIndex=computed({get:()=>attemptDraft.currentIndex,set:(value:number)=>{attemptDraft.currentIndex=value}})
const answers=computed(()=>attemptDraft.answers)
async function requestSubmit(){if(await confirmAction({title:'Submit your assessment?',description:'Your answers will be marked and this attempt will close. You cannot edit submitted answers.',confirmLabel:'Submit assessment'}))await submit()}
const result = ref<AssessmentAttempt | null>(null)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const notice = ref('')
const secondsRemaining = ref(0)
let timer: ReturnType<typeof setInterval> | undefined

const questions = computed(() => assessment.value?.questions ?? [])
const currentQuestion = computed(() => questions.value[currentIndex.value])
const progress = computed(() => questions.value.length ? ((currentIndex.value + 1) / questions.value.length) * 100 : 0)
const formattedTime = computed(() => {
  const minutes = Math.floor(secondsRemaining.value / 60)
  const seconds = secondsRemaining.value % 60
  return `${minutes}:${seconds.toString().padStart(2, '0')}`
})

onMounted(async () => {
  try {
    const response = await startAssessment(assessmentId)
    assessment.value = response.assessment
    attemptId.value = response.attemptId
    if(attemptDraft.attemptId!==response.attemptId) {
      if(attemptDraft.attemptId)notice.value='Your previous attempt has ended. A new attempt has started; previous answers were not carried over.'
      Object.assign(attemptDraft,{attemptId:response.attemptId,currentIndex:0,answers:{}})
    }
    currentIndex.value=Math.min(currentIndex.value,Math.max(0,questions.value.length-1))
    const expires= new Date(response.expiresAt).getTime()
    secondsRemaining.value = Math.max(0, Math.floor((new Date(response.expiresAt).getTime() - Date.now()) / 1000))
    timer = setInterval(() => {
      secondsRemaining.value = Math.max(0, Math.floor((expires-Date.now())/1000))
      if (secondsRemaining.value === 0) {
        clearInterval(timer)
        void submit()
      }
    }, 1000)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The assessment could not be started.'
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(() => clearInterval(timer))

async function submit() {
  if (!attemptId.value || submitting.value || result.value) return
  submitting.value = true
  error.value = ''
  try {
    result.value = await submitAssessment(attemptId.value, answers.value)
    if(pending.value?.title==='Submit your assessment?')finish(false)
    clearInterval(timer)
    draftStore.clear()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The assessment could not be submitted.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <RouterLink to="/student/assessments" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"><ArrowLeft class="size-4" /> Back to assessments</RouterLink>

    <h1 class="page-heading">{{ assessment?.title ?? 'Skill assessment' }}</h1>
    <p v-if="notice" role="status" class="rounded-xl bg-warning-soft p-4 text-sm text-warning">{{ notice }}</p>
    <p v-if="error" role="alert" class="rounded-lg bg-destructive/10 p-3 text-sm font-medium text-destructive">{{ error }}</p>
    <Card v-if="loading"><CardContent class="p-8 text-center text-muted-foreground">Starting assessment...</CardContent></Card>

    <Card v-else-if="assessment && !result">
      <CardHeader>
        <div class="flex flex-wrap items-center justify-between gap-3"><Badge variant="secondary">Question {{ currentIndex + 1 }} of {{ questions.length }}</Badge><span class="flex items-center gap-2 text-sm font-medium" :class="secondsRemaining < 60 ? 'text-destructive' : 'text-muted-foreground'"><Clock3 class="size-4" /> {{ formattedTime }}</span></div>

        <Progress :model-value="progress" class="mt-3" /><p class="text-xs leading-6 text-muted-foreground">Answers are kept in this browser tab if you leave and return. The timer keeps running.</p>
      </CardHeader>
      <CardContent v-if="currentQuestion">
        <h2 class="text-lg font-medium leading-relaxed">{{ currentQuestion.question }}</h2>
        <div class="mt-6 space-y-3">
          <label v-for="(option, index) in currentQuestion.options" :key="option" class="flex cursor-pointer items-center gap-3 rounded-xl border border-border p-4 transition hover:border-primary/50 hover:bg-primary/5" :class="answers[currentQuestion.id] === index ? 'border-primary bg-primary/5' : ''">
            <input v-model="answers[currentQuestion.id]" type="radio" :name="`question-${currentQuestion.id}`" :value="index" class="size-4 accent-primary" />
            <span class="text-sm">{{ option }}</span>
          </label>
        </div>
        <div class="mt-7 flex justify-between">
          <Button variant="outline" :disabled="currentIndex === 0 || submitting" @click="currentIndex--">Previous</Button>
          <Button v-if="currentIndex < questions.length - 1" :disabled="answers[currentQuestion.id] === undefined || submitting" @click="currentIndex++">Next question</Button>
          <Button v-else :disabled="Object.keys(answers).length !== questions.length || submitting" @click="requestSubmit">{{ submitting ? 'Submitting...' : 'Submit assessment' }}</Button>
        </div>
      </CardContent>
    </Card>

    <Card v-else-if="result" class="overflow-hidden text-center">
      <div :class="['h-2', result.passed ? 'bg-emerald-500' : 'bg-amber-500']"></div>
      <CardHeader class="items-center pt-10">
        <div :class="['flex size-20 items-center justify-center rounded-full', result.passed ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500']"><Trophy v-if="result.passed" class="size-10" /><CheckCircle2 v-else class="size-10" /></div>
        <CardTitle class="pt-4 text-3xl">{{ result.percentage }}%</CardTitle>
        <CardDescription>{{ result.passed ? 'Assessment passed successfully' : 'Review the topic and try again' }}</CardDescription>
      </CardHeader>
      <CardContent class="pb-10"><p v-if="result.feedback" class="mb-4 text-sm">{{result.feedback}}</p><p class="mb-6 text-sm text-muted-foreground">You earned {{ result.score }} points across {{ result.totalQuestions }} questions.</p><Button as-child><RouterLink to="/student/assessments">Return to assessments</RouterLink></Button></CardContent>
    </Card>

    <Card v-else-if="!error"><CardContent class="p-8 text-center"><p>Assessment could not be found.</p></CardContent></Card>
  </div>
</template>

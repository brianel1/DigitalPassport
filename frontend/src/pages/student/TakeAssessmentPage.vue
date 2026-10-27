<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { ArrowLeft, CheckCircle2, Clock3, Trophy } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { usePortalData } from '@/composables/usePortalData'

const route = useRoute()
const { assessments, getAssessmentQuestions, addAttempt } = usePortalData()
const assessmentId = Number(route.params.id)
const assessment = computed(() => assessments.value.find((item) => item.id === assessmentId))
const questions = computed(() => getAssessmentQuestions(assessmentId))
const currentIndex = ref(0)
const answers = ref<Record<number, number>>({})
const result = ref<{ score: number; percentage: number; passed: boolean } | null>(null)

const currentQuestion = computed(() => questions.value[currentIndex.value])
const progress = computed(() => questions.value.length ? ((currentIndex.value + 1) / questions.value.length) * 100 : 0)

function submit() {
  if (!assessment.value) return
  const score = questions.value.filter((question) => answers.value[question.id] === question.correctAnswer).length
  const percentage = Math.round((score / Math.max(questions.value.length, 1)) * 100)
  const passed = percentage >= assessment.value.passingScore
  result.value = { score, percentage, passed }
  addAttempt({ id: Date.now(), assessmentId, assessmentTitle: assessment.value.title, category: assessment.value.category, score, totalQuestions: questions.value.length, percentage, passed, completedAt: new Date().toISOString() })
}
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <RouterLink to="/student/assessments" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"><ArrowLeft class="size-4" /> Back to assessments</RouterLink>

    <Card v-if="assessment && !result">
      <CardHeader>
        <div class="flex flex-wrap items-center justify-between gap-3"><Badge variant="secondary">Question {{ currentIndex + 1 }} of {{ questions.length }}</Badge><span class="flex items-center gap-2 text-sm text-muted-foreground"><Clock3 class="size-4" /> {{ assessment.timeLimit }} minutes</span></div>
        <CardTitle class="pt-3">{{ assessment.title }}</CardTitle>
        <Progress :model-value="progress" class="mt-3" />
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
          <Button variant="outline" :disabled="currentIndex === 0" @click="currentIndex--">Previous</Button>
          <Button v-if="currentIndex < questions.length - 1" :disabled="answers[currentQuestion.id] === undefined" @click="currentIndex++">Next question</Button>
          <Button v-else :disabled="Object.keys(answers).length !== questions.length" @click="submit">Submit assessment</Button>
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
      <CardContent class="pb-10"><p class="mb-6 text-sm text-muted-foreground">You answered {{ result.score }} of {{ questions.length }} questions correctly.</p><Button as-child><RouterLink to="/student/assessments">Return to assessments</RouterLink></Button></CardContent>
    </Card>

    <Card v-else><CardContent class="p-8 text-center"><p>Assessment could not be found.</p></CardContent></Card>
  </div>
</template>

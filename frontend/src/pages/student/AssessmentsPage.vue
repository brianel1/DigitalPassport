<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { BookOpenCheck, CheckCircle2, Clock3, History, Trophy } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { usePortalData } from '@/composables/usePortalData'

const { assessments, attempts } = usePortalData()
const attemptedIds = computed(() => new Set(attempts.value.map((item) => item.assessmentId)))
</script>

<template>
  <div class="space-y-8">
    <div><h1 class="text-2xl font-semibold tracking-tight">Skill assessments</h1><p class="mt-1 text-muted-foreground">Validate your knowledge through structured objective assessments.</p></div>

    <section>
      <div class="mb-4 flex items-center gap-2"><BookOpenCheck class="size-5 text-primary" /><h2 class="font-semibold">Available assessments</h2></div>
      <div class="grid gap-5 lg:grid-cols-3">
        <Card v-for="assessment in assessments" :key="assessment.id" class="flex flex-col">
          <CardHeader>
            <div class="mb-3 flex items-center justify-between"><Badge variant="secondary">{{ assessment.category.replace('-', ' ') }}</Badge><CheckCircle2 v-if="attemptedIds.has(assessment.id)" class="size-5 text-emerald-500" /></div>
            <CardTitle class="text-lg">{{ assessment.title }}</CardTitle>
            <CardDescription class="leading-relaxed">{{ assessment.description }}</CardDescription>
          </CardHeader>
          <CardContent class="mt-auto">
            <div class="mb-5 flex items-center gap-4 text-xs text-muted-foreground"><span class="flex items-center gap-1.5"><Clock3 class="size-4" /> {{ assessment.timeLimit }} minutes</span><span>{{ assessment.questionCount }} questions</span></div>
            <Button as-child class="w-full"><RouterLink :to="`/student/assessments/${assessment.id}`">{{ attemptedIds.has(assessment.id) ? 'Retake assessment' : 'Start assessment' }}</RouterLink></Button>
          </CardContent>
        </Card>
      </div>
    </section>

    <section>
      <div class="mb-4 flex items-center gap-2"><History class="size-5 text-primary" /><h2 class="font-semibold">Assessment history</h2></div>
      <Card>
        <CardContent class="divide-y divide-border p-0">
          <div v-for="attempt in attempts" :key="attempt.id" class="grid gap-4 p-5 sm:grid-cols-[1fr_auto_180px] sm:items-center">
            <div><p class="font-medium">{{ attempt.assessmentTitle }}</p><p class="mt-1 text-xs text-muted-foreground">Completed {{ new Date(attempt.completedAt).toLocaleDateString('en-MY', { dateStyle: 'medium' }) }}</p></div>
            <Badge :class="attempt.passed ? 'bg-emerald-500/10 text-emerald-600' : 'bg-destructive/10 text-destructive'" variant="outline"><Trophy class="mr-1 size-3.5" /> {{ attempt.passed ? 'Passed' : 'Retry recommended' }}</Badge>
            <div><div class="mb-2 flex justify-between text-sm"><span>Score</span><strong>{{ attempt.percentage }}%</strong></div><Progress :model-value="attempt.percentage" /></div>
          </div>
        </CardContent>
      </Card>
    </section>
  </div>
</template>

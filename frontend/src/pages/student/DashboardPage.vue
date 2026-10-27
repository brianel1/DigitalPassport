<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Award, BookOpenCheck, CheckCircle2, FolderKanban, Sparkles, TrendingUp } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { useAuth } from '@/composables/useAuth'
import { usePortalData } from '@/composables/usePortalData'

const { user } = useAuth()
const { skills, projects, certifications, attempts, activity } = usePortalData()

const stats = computed(() => [
  { label: 'Skills', value: skills.value.length, detail: `${skills.value.filter((item) => item.status === 'verified').length} verified`, icon: Sparkles, color: 'text-violet-500 bg-violet-500/10' },
  { label: 'Projects', value: projects.value.length, detail: `${projects.value.filter((item) => item.status === 'verified').length} verified`, icon: FolderKanban, color: 'text-blue-500 bg-blue-500/10' },
  { label: 'Certifications', value: certifications.value.length, detail: `${certifications.value.filter((item) => item.status === 'verified').length} verified`, icon: Award, color: 'text-amber-500 bg-amber-500/10' },
  { label: 'Assessments', value: attempts.value.length, detail: `${Math.round(attempts.value.reduce((sum, item) => sum + item.percentage, 0) / Math.max(attempts.value.length, 1))}% average`, icon: BookOpenCheck, color: 'text-emerald-500 bg-emerald-500/10' },
])

const completion = computed(() => {
  const completed = [user.value?.programme, user.value?.matricNo, skills.value.length, projects.value.length, certifications.value.length].filter(Boolean).length
  return completed * 20
})

function formatDate(value: string) {
  return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium' }).format(new Date(value))
}
</script>

<template>
  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-2xl border border-border bg-card p-6 sm:p-8">
      <div class="absolute -right-20 -top-20 size-64 rounded-full bg-primary/10 blur-3xl"></div>
      <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <Badge variant="secondary" class="mb-4">Student Portal</Badge>
          <h1 class="text-3xl font-semibold tracking-tight">Welcome back, {{ user?.name?.split(' ')[0] }}</h1>
          <p class="mt-2 max-w-2xl text-muted-foreground">Your achievements are growing. Keep your records current and ready to share.</p>
        </div>
        <Button as-child>
          <RouterLink to="/student/passport">View Digital Passport</RouterLink>
        </Button>
      </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="item in stats" :key="item.label">
        <CardContent class="flex items-center justify-between p-5">
          <div>
            <p class="text-sm text-muted-foreground">{{ item.label }}</p>
            <p class="mt-1 text-3xl font-semibold">{{ item.value }}</p>
            <p class="mt-1 text-xs text-muted-foreground">{{ item.detail }}</p>
          </div>
          <div :class="['flex size-11 items-center justify-center rounded-xl', item.color]">
            <component :is="item.icon" class="size-5" />
          </div>
        </CardContent>
      </Card>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
      <Card>
        <CardHeader>
          <CardTitle>Recent activity</CardTitle>
          <CardDescription>Latest updates across your DigitalPassport.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-1">
          <div v-for="item in activity" :key="item.id" class="flex gap-4 border-b border-border py-4 last:border-0">
            <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
              <CheckCircle2 class="size-4" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium leading-relaxed">{{ item.description }}</p>
              <p class="mt-1 text-xs text-muted-foreground">{{ formatDate(item.timestamp) }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2"><TrendingUp class="size-5 text-primary" /> Passport readiness</CardTitle>
            <CardDescription>Complete your records to strengthen your profile.</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="mb-3 flex items-center justify-between text-sm">
              <span>Profile completion</span><strong>{{ completion }}%</strong>
            </div>
            <Progress :model-value="completion" />
            <Button as-child variant="outline" class="mt-5 w-full">
              <RouterLink to="/student/profile">Update profile</RouterLink>
            </Button>
          </CardContent>
        </Card>
        <Card class="border-primary/20 bg-primary/5">
          <CardHeader>
            <CardTitle class="text-base">Next recommended step</CardTitle>
            <CardDescription>Complete an assessment to add another verified result.</CardDescription>
          </CardHeader>
          <CardContent>
            <Button as-child class="w-full"><RouterLink to="/student/assessments">Browse assessments</RouterLink></Button>
          </CardContent>
        </Card>
      </div>
    </section>
  </div>
</template>

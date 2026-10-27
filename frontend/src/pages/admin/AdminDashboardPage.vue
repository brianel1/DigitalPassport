<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { CheckCheck, ClipboardCheck, GraduationCap, ListChecks, UsersRound } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { usePortalData } from '@/composables/usePortalData'

const { reviewItems, assessments } = usePortalData()
const pending = computed(() => reviewItems.value.filter((item) => item.status === 'pending'))
const summary = computed(() => [
  { label: 'Pending reviews', value: pending.value.length, icon: ClipboardCheck, tone: 'bg-amber-500/10 text-amber-600' },
  { label: 'Registered students', value: 24, icon: UsersRound, tone: 'bg-blue-500/10 text-blue-600' },
  { label: 'Assessments', value: assessments.value.length, icon: ListChecks, tone: 'bg-violet-500/10 text-violet-600' },
  { label: 'Reviewed this week', value: 5, icon: CheckCheck, tone: 'bg-emerald-500/10 text-emerald-600' },
])
</script>

<template>
  <div class="space-y-8">
    <section class="rounded-2xl border border-border bg-gradient-to-br from-card to-destructive/5 p-7">
      <Badge variant="outline" class="mb-4">Administration</Badge>
      <h1 class="text-3xl font-semibold tracking-tight">DigitalPassport overview</h1>
      <p class="mt-2 text-muted-foreground">Review student evidence and maintain assessment content.</p>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="item in summary" :key="item.label"><CardContent class="flex items-center justify-between p-5"><div><p class="text-sm text-muted-foreground">{{ item.label }}</p><p class="mt-1 text-3xl font-semibold">{{ item.value }}</p></div><div :class="['flex size-11 items-center justify-center rounded-xl', item.tone]"><component :is="item.icon" class="size-5" /></div></CardContent></Card>
    </section>

    <Card>
      <CardHeader class="flex-row items-center justify-between"><div><CardTitle>Pending submissions</CardTitle><CardDescription class="mt-1">Student evidence waiting for a decision.</CardDescription></div><Button as-child variant="outline"><RouterLink to="/admin/reviews">View all</RouterLink></Button></CardHeader>
      <CardContent class="divide-y divide-border">
        <div v-for="item in pending.slice(0, 4)" :key="item.id" class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center">
          <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><GraduationCap class="size-5" /></div>
          <div class="min-w-0 flex-1"><p class="font-medium">{{ item.title }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.studentName }} | {{ item.studentMatricNo }}</p></div>
          <Badge variant="secondary">{{ item.type }}</Badge>
          <Button as-child size="sm"><RouterLink to="/admin/reviews">Review</RouterLink></Button>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

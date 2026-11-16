<script setup lang="ts">
import SystemHealthPanel from '@/components/shared/SystemHealthPanel.vue'
import { computed, onMounted, ref } from 'vue'
import { ArrowRight, BarChart3, CheckCheck, ClipboardCheck, FileText, Loader2, UsersRound, ShieldCheck } from '@lucide/vue'
import { RouterLink } from 'vue-router'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { usePortalData } from '@/composables/usePortalData'
const { reviewItems, adminStats, adminError, refreshAdminData } = usePortalData()
const loading = ref(true)
onMounted(async () => { await refreshAdminData(); loading.value = false })
const pending = computed(() => reviewItems.value.filter(item => item.status === 'pending'))
const maxGrowth = computed(() => Math.max(...adminStats.value.userGrowth.map(item => item.value), 1))
const totalSubmissionCount = computed(() => Object.values(adminStats.value.submissionCounts).reduce((sum, value) => sum + value, 0))
const summary = computed(() => [
  { label: 'Pending reviews', value: adminStats.value.pendingReviews, icon: ClipboardCheck, tone: 'text-warning bg-warning-soft' },
  { label: 'Active students', value: adminStats.value.activeStudents, icon: UsersRound, tone: 'text-primary bg-accent' },
  { label: 'Assessment pass rate', value: adminStats.value.assessmentAttempts ? adminStats.value.assessmentPassRate + '%' : '—', icon: CheckCheck, tone: 'text-teal bg-teal-soft' },
  { label: 'CVs generated', value: adminStats.value.totalCvGenerated, icon: FileText, tone: 'text-primary bg-accent' },
])
</script>
<template>
  <div v-if="loading" role="status" class="flex items-center justify-center gap-3 py-20 text-muted-foreground"><Loader2 class="size-5 animate-spin text-primary" /> Loading your workspace…</div>
  <div v-else class="space-y-7">
    <p v-if="adminError" role="alert" class="rounded-xl bg-destructive/10 p-4 text-sm text-destructive">{{ adminError }} <button class="ml-2 underline" @click="refreshAdminData">Retry</button></p>
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-2">Administration</p><h1 class="page-heading">Keep progress moving.</h1><p class="page-intro">Review student evidence and see what's happening across your workspace.</p></div><Button as-child><RouterLink to="/admin/reviews">Open review queue <ArrowRight class="size-4" /></RouterLink></Button></div>
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><Card v-for="item in summary" :key="item.label"><CardContent class="p-5"><div class="flex items-center justify-between gap-2"><p class="text-sm text-muted-foreground">{{ item.label }}</p><span :class="['flex size-8 shrink-0 items-center justify-center rounded-lg', item.tone]"><component :is="item.icon" class="size-4" /></span></div><p class="mt-4 text-3xl font-semibold tabular-nums">{{ item.value }}</p></CardContent></Card></section>
    <RouterLink v-if="adminStats.waitingOverWeek" :to="{path:'/admin/reviews',query:{ageDays:7}}" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-warning/30 bg-warning-soft p-4 text-sm text-warning"><strong>{{adminStats.waitingOverWeek}} submissions have waited over a week</strong><span>Oldest: {{adminStats.oldestPendingDays}} days · Review now →</span></RouterLink>
    <section class="grid items-start gap-6 xl:grid-cols-[1.5fr_1fr]">
      <Card><CardHeader class="flex flex-row flex-wrap items-center justify-between gap-4"><div><CardTitle>Ready for your review</CardTitle><CardDescription class="mt-2">Submitted work waiting for a decision.</CardDescription></div><Button as-child variant="ghost" size="sm"><RouterLink to="/admin/reviews">View all <ArrowRight class="size-3.5" /></RouterLink></Button></CardHeader><CardContent><div v-if="!pending.length" class="empty-state"><ShieldCheck class="mx-auto size-8 text-teal" /><h3 class="mt-4 font-semibold">The review queue is clear.</h3><p class="mx-auto mt-2 max-w-xs text-sm leading-6 text-muted-foreground">New student submissions will appear here when they are ready for review.</p></div><div v-for="item in pending.slice(0, 5)" :key="item.type + '-' + item.id" class="flex flex-wrap items-center gap-3 border-b py-4 first:pt-0 last:border-0"><div class="min-w-0 flex-1"><p class="text-sm font-semibold">{{ item.title }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.studentName }} · {{ item.studentMatricNo }}</p></div><Badge variant="secondary">{{ item.type }}</Badge><Button as-child size="sm" variant="outline"><RouterLink :to="{ path: '/admin/reviews', query: { type: item.type, id: item.id } }">Review</RouterLink></Button></div></CardContent></Card>
      <Card><CardHeader><CardTitle>Evidence at a glance</CardTitle><CardDescription>{{ totalSubmissionCount }} records across the workspace.</CardDescription></CardHeader><CardContent class="space-y-5"><div v-for="item in [{ label: 'Awaiting review', value: adminStats.submissionCounts.pending, tone: 'bg-warning' }, { label: 'Verified', value: adminStats.submissionCounts.verified, tone: 'bg-teal' }, { label: 'Needs update', value: adminStats.submissionCounts.rejected, tone: 'bg-destructive' }]" :key="item.label"><div class="mb-2 flex justify-between text-sm"><span class="text-muted-foreground">{{ item.label }}</span><strong>{{ item.value }}</strong></div><div class="h-1.5 overflow-hidden rounded-full bg-muted"><div :class="['h-full rounded-full', item.tone]" :style="{ width: (totalSubmissionCount ? item.value / totalSubmissionCount * 100 : 0) + '%' }"></div></div></div><p class="border-t pt-4 text-xs leading-6 text-muted-foreground">Verify the supporting evidence before approving a skill. Clear feedback helps students improve their records.</p></CardContent></Card>
    </section>
    <section class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
      <Card><CardHeader><CardTitle class="flex items-center gap-2"><BarChart3 class="size-4 text-primary" /> Student registrations</CardTitle><CardDescription>New accounts in the last six months.</CardDescription></CardHeader><CardContent><div class="flex h-48 items-end gap-3 border-b px-2" role="img" :aria-label="adminStats.userGrowth.map(i => i.label + ': ' + i.value).join(', ')"><div v-for="item in adminStats.userGrowth" :key="item.label" class="flex h-full flex-1 flex-col items-center justify-end"><span class="mb-2 text-xs text-muted-foreground">{{ item.value }}</span><div class="w-full max-w-10 rounded-t-md bg-primary" :style="{ height: item.value / maxGrowth * 130 + 'px' }"></div><span class="my-3 text-[10px] text-muted-foreground">{{ item.label.split(' ')[0] }}</span></div></div></CardContent></Card>
      <Card><CardHeader><CardTitle>Learning & career activity</CardTitle><CardDescription>Activity recorded by the system.</CardDescription></CardHeader><CardContent class="space-y-5"><div class="flex items-center justify-between border-b pb-4"><span class="text-sm text-muted-foreground">Assessment attempts</span><strong class="text-xl">{{ adminStats.assessmentAttempts }}</strong></div><div class="flex items-center justify-between border-b pb-4"><span class="text-sm text-muted-foreground">Job matching runs</span><strong class="text-xl">{{ adminStats.totalRecommendationRuns }}</strong></div><div class="flex items-center justify-between"><span class="text-sm text-muted-foreground">Reviews this week</span><strong class="text-xl">{{ adminStats.reviewsThisWeek }}</strong></div></CardContent></Card>
    </section>
    <SystemHealthPanel />
  </div>
</template>

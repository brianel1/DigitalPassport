<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Activity, BarChart3, CheckCheck, ClipboardCheck, FileText, Gauge, ListChecks, Loader2, UsersRound } from '@lucide/vue'
import { RouterLink } from 'vue-router'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { usePortalData } from '@/composables/usePortalData'

const { reviewItems, adminStats, loadAdminData } = usePortalData()
const loading = ref(true)

onMounted(async () => {
  await loadAdminData()
  loading.value = false
})

const pending = computed(() => reviewItems.value.filter((item) => item.status === 'pending'))
const maxGrowth = computed(() => Math.max(...adminStats.value.userGrowth.map((item) => item.value), 1))
const totalSubmissionCount = computed(() => Object.values(adminStats.value.submissionCounts).reduce((sum, value) => sum + value, 0))
const summary = computed(() => [
  { label: 'Pending reviews', value: adminStats.value.pendingReviews, icon: ClipboardCheck, tone: 'bg-amber-500/10 text-amber-600' },
  { label: 'Active students', value: adminStats.value.activeStudents, icon: UsersRound, tone: 'bg-blue-500/10 text-blue-600' },
  { label: 'Assessment pass rate', value: `${adminStats.value.assessmentPassRate}%`, icon: CheckCheck, tone: 'bg-emerald-500/10 text-emerald-600' },
  { label: 'CVs generated', value: adminStats.value.totalCvGenerated, icon: FileText, tone: 'bg-violet-500/10 text-violet-600' },
])
</script>

<template>
  <div v-if="loading" class="flex items-center justify-center py-20"><Loader2 class="size-8 animate-spin text-primary" /></div>
  <div v-else class="space-y-8">
    <section class="rounded-2xl border border-border bg-gradient-to-br from-card to-primary/5 p-7">
      <div class="flex flex-wrap items-start justify-between gap-4"><div><Badge variant="outline" class="mb-4">Administration</Badge><h1 class="text-3xl font-semibold tracking-tight">DigitalPassport overview</h1><p class="mt-2 text-muted-foreground">Monitor student activity, verification work and portal usage.</p></div><div class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><Gauge class="size-6" /></div></div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="item in summary" :key="item.label"><CardContent class="flex items-center justify-between p-5"><div><p class="text-sm text-muted-foreground">{{ item.label }}</p><p class="mt-1 text-3xl font-semibold">{{ item.value }}</p></div><div :class="['flex size-11 items-center justify-center rounded-xl', item.tone]"><component :is="item.icon" class="size-5" /></div></CardContent></Card>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
      <Card>
        <CardHeader><CardTitle class="flex items-center gap-2"><BarChart3 class="size-5 text-primary" /> Student registrations</CardTitle><CardDescription>New student accounts created during the last six months.</CardDescription></CardHeader>
        <CardContent>
          <div class="flex h-56 items-end gap-3 border-b border-l border-border px-3 pb-0 pt-6">
            <div v-for="item in adminStats.userGrowth" :key="item.label" class="flex h-full flex-1 flex-col items-center justify-end gap-2"><span class="text-xs font-medium text-muted-foreground">{{ item.value }}</span><div class="w-full max-w-12 rounded-t-lg bg-primary/80 transition-all" :style="{ height: `${Math.max((item.value / maxGrowth) * 100, item.value ? 8 : 2)}%` }"></div><span class="text-[10px] text-muted-foreground">{{ item.label.split(' ')[0] }}</span></div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle class="flex items-center gap-2"><Activity class="size-5 text-primary" /> Submission status</CardTitle><CardDescription>{{ totalSubmissionCount }} portfolio records in the system.</CardDescription></CardHeader>
        <CardContent class="space-y-5">
          <div v-for="item in [{ label: 'Pending', value: adminStats.submissionCounts.pending, tone: 'bg-amber-500' }, { label: 'Verified', value: adminStats.submissionCounts.verified, tone: 'bg-emerald-500' }, { label: 'Rejected', value: adminStats.submissionCounts.rejected, tone: 'bg-destructive' }]" :key="item.label" class="space-y-2"><div class="flex justify-between text-sm"><span>{{ item.label }}</span><span class="font-semibold">{{ item.value }}</span></div><div class="h-2 overflow-hidden rounded-full bg-muted"><div :class="['h-full rounded-full transition-all', item.tone]" :style="{ width: `${totalSubmissionCount ? (item.value / totalSubmissionCount) * 100 : 0}%` }"></div></div></div>
          <div class="grid grid-cols-2 gap-3 border-t border-border pt-4 text-sm"><div><p class="text-muted-foreground">Assessment attempts</p><p class="mt-1 text-xl font-semibold">{{ adminStats.assessmentAttempts }}</p></div><div><p class="text-muted-foreground">Job recommendation runs</p><p class="mt-1 text-xl font-semibold">{{ adminStats.totalRecommendationRuns }}</p></div></div>
        </CardContent>
      </Card>
    </section>

    <Card>
      <CardHeader class="flex-row items-center justify-between"><div><CardTitle>Pending submissions</CardTitle><CardDescription class="mt-1">Student evidence waiting for an administrator decision.</CardDescription></div><Button as-child variant="outline"><RouterLink to="/admin/reviews">View all</RouterLink></Button></CardHeader>
      <CardContent class="divide-y divide-border">
        <div v-if="pending.length === 0" class="py-8 text-center text-sm text-muted-foreground">No pending submissions.</div>
        <div v-for="item in pending.slice(0, 5)" :key="`${item.type}-${item.id}`" class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center"><div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><ListChecks class="size-5" /></div><div class="min-w-0 flex-1"><p class="font-medium">{{ item.title }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.studentName }} · {{ item.studentMatricNo }}</p></div><Badge variant="secondary">{{ item.type }}</Badge><Button as-child size="sm"><RouterLink to="/admin/reviews">Review</RouterLink></Button></div>
      </CardContent>
    </Card>
  </div>
</template>

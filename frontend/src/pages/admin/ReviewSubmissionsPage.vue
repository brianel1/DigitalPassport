<script setup lang="ts">
import EvidencePreview from '@/components/shared/EvidencePreview.vue'
import ReviewHistory from '@/components/shared/ReviewHistory.vue'
import type { EvidenceFileSummary } from '@/types'

import { useFormDraft } from '@/composables/useFormDraft'

import ListPagination from '@/components/shared/ListPagination.vue'
import { usePagination } from '@/composables/usePagination'

import ConfirmDialog from '@/components/shared/ConfirmDialog.vue'
import AppSelect from '@/components/shared/AppSelect.vue'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ArrowUpRight, Check, Download, Eye, FileText, Loader2, Search, X } from '@lucide/vue'
import { useRoute } from 'vue-router'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import type { ReviewItem } from '@/types'
import { ApiError, downloadAdminEvidence, getAdminReviewsFiltered, updateAdminReview } from '@/lib/api'

const route = useRoute()
const reviewItems = ref<ReviewItem[]>([])
const selected = ref<ReviewItem | null>(null)
const dialogOpen = ref(false)
const confirmOpen = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const previewFile=ref<EvidenceFileSummary|null>(null)
const ageDays=ref(Number(route.query.ageDays)||0)
const search = ref('')
const statusFilter = ref<'pending' | 'verified' | 'rejected' | 'all'>('pending')
const typeFilter = ref<'project' | 'certification' | 'skill' | 'all'>('all')
const pendingDecision = ref<'verified' | 'rejected' | null>(null)
const form = reactive({ remarks: '' })
const draftRemarks=reactive({items:{} as Record<string,string>})
useFormDraft('review-remarks',draftRemarks)
watch(()=>form.remarks,value=>{if(selected.value)draftRemarks.items[selected.value.type+'-'+selected.value.id]=value})

const {page,items:pagedItems,pageSize}=usePagination(reviewItems)
async function loadReviews() {
  loading.value = true
  page.value=1
  error.value = ''
  try {
    reviewItems.value = await getAdminReviewsFiltered({ status: statusFilter.value, type: typeFilter.value, search: search.value.trim() || undefined,ageDays:ageDays.value||undefined })
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Submissions could not be loaded.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadReviews()
  const item = reviewItems.value.find(item => item.id === Number(route.query.id) && item.type === route.query.type)
  if (item) openReview(item)
})
watch([statusFilter, typeFilter, ageDays], loadReviews)

const canVerify = computed(() => {
  if (!selected.value) return false
  if (selected.value.type === 'skill') return !!selected.value.supportingAssessments?.length || (selected.value.supportingEvidence?.some(p => p.status === 'verified' && !p.expired && (p.files.length > 0 || p.projectUrl)) ?? false)
  if (selected.value.type === 'project') return selected.value.evidenceFiles.length > 0 || Boolean('projectUrl' in selected.value.details && selected.value.details.projectUrl)
  return selected.value.evidenceFiles.length > 0 && !('expired' in selected.value.details && selected.value.details.expired)
})

function openReview(item: ReviewItem) {
  error.value = ''
  selected.value = item
  form.remarks = draftRemarks.items[item.type+'-'+item.id] ?? item.adminRemarks ?? ''
  pendingDecision.value = null
  dialogOpen.value = true
}

function requestDecision(status: 'verified' | 'rejected') {
  if (!selected.value) return
  if (status === 'rejected' && form.remarks.trim().length < 5) {
    error.value = 'Add a clear reason before rejecting a submission.'
    return
  }
  error.value = ''
  pendingDecision.value = status
  confirmOpen.value = true
}

async function confirmDecision() {
  if (!selected.value || !pendingDecision.value) return
  submitting.value = true
  error.value = ''
  try {
    await updateAdminReview(selected.value, pendingDecision.value, form.remarks)
    delete draftRemarks.items[selected.value.type+'-'+selected.value.id]
    confirmOpen.value = false
    dialogOpen.value = false
    await loadReviews()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The review could not be saved.'
  } finally {
    submitting.value = false
  }
}

async function downloadFile(file: { id: number; name: string }, source?: { type: 'project' | 'certification'; id: number }) {
  if (!selected.value) return
  try {
    await downloadAdminEvidence(source ?? selected.value, file)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The evidence file could not be downloaded.'
  }
}

function detailsSummary(item: ReviewItem) {
  if ('description' in item.details) return item.details.description
  return `${item.details.title} issued by ${item.details.issuingOrganization}.`
}

function formatFileSize(size: number) {
  if (size < 1024 * 1024) return `${Math.max(1, Math.round(size / 1024))} KB`
  return `${(size / (1024 * 1024)).toFixed(1)} MB`
}
</script>

<template>
  <div class="space-y-6">
    <div><h1 class="page-heading">Review submissions</h1><p class="mt-1 text-muted-foreground">Verify student skills, projects and certification evidence with a clear review trail.</p></div>

    <Card>
      <CardContent class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-[1fr_150px_150px_160px_auto]">
        <div class="relative"><Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="search" aria-label="Search submissions" class="pl-9" placeholder="Search student or submission" @keyup.enter="loadReviews" /></div>
        <AppSelect v-model="statusFilter" aria-label="Filter by review status" class="h-9 rounded-lg border border-input bg-background px-3 text-sm" :options='[{"value": "pending", "label": "Pending"}, {"value": "verified", "label": "Verified"}, {"value": "rejected", "label": "Rejected"}, {"value": "all", "label": "All statuses"}]' />
        <AppSelect v-model="typeFilter" aria-label="Filter by submission type" class="h-9 rounded-lg border border-input bg-background px-3 text-sm" :options='[{"value": "all", "label": "All types"}, {"value": "skill", "label": "Skills"}, {"value": "project", "label": "Projects"}, {"value": "certification", "label": "Certifications"}]' />
        <AppSelect v-model="ageDays" aria-label="Filter by waiting time" :options="[{value:0,label:'Any waiting time'},{value:3,label:'3+ days'},{value:7,label:'7+ days'},{value:14,label:'14+ days'}]" /><Button variant="outline" @click="loadReviews">Search</Button>
      </CardContent>
    </Card>

    <p v-if="error" class="text-sm font-medium text-destructive">{{ error }}</p>
    <div v-if="loading" class="flex items-center justify-center py-16 text-muted-foreground"><Loader2 class="mr-2 size-5 animate-spin" /> Loading submissions...</div>

    <Card v-else>
      <CardHeader><CardTitle>Submission queue</CardTitle><CardDescription>{{ reviewItems.length }} record(s) match the current filters.</CardDescription></CardHeader>
      <CardContent class="p-0"><div class="overflow-x-auto"><table class="w-full min-w-[820px] text-left text-sm"><thead class="border-y border-border bg-muted/40 text-xs uppercase text-muted-foreground"><tr><th class="px-6 py-3">Student</th><th class="px-6 py-3">Submission</th><th class="px-6 py-3">Type</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-border"><tr v-for="item in pagedItems" :key="`${item.type}-${item.id}`" class="hover:bg-muted/20"><td class="px-6 py-4"><p class="font-medium">{{ item.studentName }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.studentMatricNo }}</p></td><td class="px-6 py-4"><p class="font-medium">{{ item.title }}</p><p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"><FileText class="size-3.5" /> {{ item.evidenceFiles.length }} file(s)</p></td><td class="px-6 py-4"><Badge variant="secondary">{{ item.type }}</Badge></td><td class="px-6 py-4"><StatusBadge :status="item.status" /></td><td class="px-6 py-4 text-right"><Button variant="outline" size="sm" @click="openReview(item)"><Eye class="size-4" /> View</Button></td></tr><tr v-if="reviewItems.length === 0"><td colspan="5" class="px-6 py-10 text-center text-muted-foreground">No submissions match the current filters.</td></tr></tbody></table></div></CardContent>
    </Card>

    <ListPagination v-model:page="page" :total="reviewItems.length" :page-size="pageSize" label="records" />

    <Dialog v-model:open="dialogOpen">
      <DialogContent v-if="selected" class="max-h-[90dvh] overflow-y-auto sm:max-w-4xl">
        <DialogHeader class="pr-6"><div class="mb-2 flex flex-wrap gap-2"><Badge variant="secondary">{{ selected.type }}</Badge><StatusBadge :status="selected.status" /></div><DialogTitle class="text-xl">{{ selected.title }}</DialogTitle><DialogDescription>{{ selected.studentName }} · {{ selected.studentMatricNo }}</DialogDescription></DialogHeader>
        <div class="grid items-start gap-6 pt-2 md:grid-cols-[1.2fr_1fr]">
          <section class="space-y-5">
            <div v-if="selected.changes?.length" class="rounded-xl border border-primary/30 bg-accent p-4"><h3 class="font-semibold">Changed since the last review</h3><details v-for="change in selected.changes" :key="change.field" class="mt-3 text-sm"><summary class="cursor-pointer font-medium">{{change.field}}</summary><p class="mt-2 break-words text-xs text-muted-foreground">Before: {{typeof change.before==='object'?JSON.stringify(change.before):change.before||'—'}}</p><p class="mt-2 break-words text-xs">Now: {{typeof change.after==='object'?JSON.stringify(change.after):change.after||'—'}}</p></details></div>
            <div><h3 class="eyebrow mb-3">About this submission · Revision {{selected.revision??1}}</h3><p class="whitespace-pre-wrap text-sm leading-7">{{ detailsSummary(selected) }}</p></div>
            <a v-if="'projectUrl' in selected.details && selected.details.projectUrl" :href="selected.details.projectUrl" target="_blank" rel="noreferrer" class="flex items-center justify-between gap-3 rounded-lg border bg-accent p-4 text-sm font-medium text-primary">Open project or repository <ArrowUpRight class="size-4 shrink-0" /></a>
            <div v-if="selected.evidenceFiles.length" class="space-y-2"><h3 class="eyebrow mb-3">Evidence files</h3><div v-for="file in selected.evidenceFiles" :key="file.id" class="flex items-center gap-3 rounded-lg border p-3"><FileText class="size-5 shrink-0 text-primary" /><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ file.name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</p></div><Button variant="outline" size="sm" @click="previewFile=file"><Eye class="size-4" />Preview</Button><Button variant="outline" size="icon" :aria-label="'Download ' + file.name" @click="downloadFile(file)"><Download class="size-4" /></Button></div></div>
            <div v-if="selected.supportingEvidence?.length" class="space-y-3"><h3 class="eyebrow">Evidence linked to this skill</h3><div v-for="proof in selected.supportingEvidence" :key="proof.type + '-' + proof.id" class="rounded-xl border p-4"><div class="flex flex-wrap items-center justify-between gap-2"><span class="text-sm font-medium">{{ proof.title }}</span><StatusBadge :status="proof.status" /></div><a v-if="proof.projectUrl" :href="proof.projectUrl" target="_blank" rel="noreferrer" class="mt-3 flex items-center gap-1 text-xs font-medium text-primary hover:underline">Open project <ArrowUpRight class="size-3" /></a><Button v-for="file in proof.files" :key="file.id" variant="outline" size="sm" class="mt-3 max-w-full" @click="previewFile=file"><Eye class="size-4" /><span class="truncate">{{ file.name }}</span></Button></div></div>
            <div v-if="selected.supportingAssessments?.length" class="rounded-xl border bg-teal-soft p-4"><h3 class="font-semibold text-teal">Passed assessments supporting this skill</h3><p v-for="attempt in selected.supportingAssessments" :key="attempt.title" class="mt-2 text-sm">{{attempt.title}} · {{attempt.percentage}}%</p></div><p v-if="!canVerify && selected.status === 'pending'" class="rounded-lg bg-warning-soft p-4 text-sm leading-6 text-warning">{{ selected.type === 'skill' ? 'This skill needs a linked, verified project or certification with evidence before it can be approved.' : 'Supporting evidence is required before this record can be verified.' }}</p>
          </section>
          <section class="rounded-xl border bg-background p-5">
            <h3 class="eyebrow mb-4">Review decision</h3><p v-if="'expired' in selected.details && selected.details.expired" class="mb-4 text-sm font-semibold text-destructive">This certificate has expired.</p>
            <div v-if="selected.status === 'pending'" class="space-y-4"><p class="text-sm leading-6 text-muted-foreground">Check the supporting work, then leave clear feedback for the student.</p><div class="space-y-2"><Label for="remarks">Feedback to the student</Label><Textarea id="remarks" v-model="form.remarks" rows="5" maxlength="2000" placeholder="Explain what is verified or what needs to change." /><p class="text-xs leading-5 text-muted-foreground">At least 5 characters are required when requesting an update.</p></div><p v-if="error" role="alert" class="text-sm text-destructive">{{ error }}</p><Button class="w-full" :disabled="submitting || !canVerify" @click="requestDecision('verified')"><Check class="size-4" /> Verify submission</Button><Button variant="outline" class="w-full text-destructive" :disabled="submitting" @click="requestDecision('rejected')"><X class="size-4" /> Request update</Button></div>
            <div v-else class="text-sm leading-6"><p class="font-medium">Reviewed by {{ selected.reviewerName || 'Administrator' }}</p><p v-if="selected.reviewedAt" class="mt-1 text-xs text-muted-foreground">{{ new Date(selected.reviewedAt).toLocaleDateString('en-MY') }}</p><p class="mt-4 whitespace-pre-wrap text-muted-foreground">{{ selected.adminRemarks || 'No additional remarks.' }}</p></div>
            <ReviewHistory :items="selected.reviewHistory" />
          </section>
        </div>
      </DialogContent>
    </Dialog>

    <EvidencePreview v-model="previewFile" />
    <ConfirmDialog v-model:open="confirmOpen" :title="pendingDecision === 'verified' ? 'Verify this submission?' : 'Request an update?'" :description="pendingDecision === 'verified' ? 'This record will become verified and appear as approved portfolio evidence.' : 'This record will be marked as needing an update and the student will see your feedback.'" :confirm-label="pendingDecision === 'verified' ? 'Verify submission' : 'Request update'" :variant="pendingDecision === 'verified' ? 'success' : 'destructive'" :loading="submitting" :error="error" @confirm="confirmDecision"><div v-if="selected" class="rounded-xl border bg-muted/30 p-4"><p class="font-medium">{{ selected.title }}</p><p class="mt-1 text-sm text-muted-foreground">{{ selected.studentName }}</p></div></ConfirmDialog>
  </div>
</template>

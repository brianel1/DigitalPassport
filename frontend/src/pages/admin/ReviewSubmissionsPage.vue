<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { Check, Download, Eye, FileText, Loader2, Search, X } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import type { ReviewItem } from '@/types'
import { ApiError, downloadAdminEvidence, getAdminReviewsFiltered, updateAdminReview } from '@/lib/api'

const reviewItems = ref<ReviewItem[]>([])
const selected = ref<ReviewItem | null>(null)
const dialogOpen = ref(false)
const confirmOpen = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const search = ref('')
const statusFilter = ref<'pending' | 'verified' | 'rejected' | 'all'>('pending')
const typeFilter = ref<'project' | 'certification' | 'skill' | 'all'>('all')
const pendingDecision = ref<'verified' | 'rejected' | null>(null)
const form = reactive({ remarks: '' })

async function loadReviews() {
  loading.value = true
  error.value = ''
  try {
    reviewItems.value = await getAdminReviewsFiltered({ status: statusFilter.value, type: typeFilter.value, search: search.value.trim() || undefined })
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Submissions could not be loaded.'
  } finally {
    loading.value = false
  }
}

onMounted(loadReviews)
watch([statusFilter, typeFilter], loadReviews)

function openReview(item: ReviewItem) {
  selected.value = item
  form.remarks = item.adminRemarks ?? ''
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
    confirmOpen.value = false
    dialogOpen.value = false
    await loadReviews()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The review could not be saved.'
  } finally {
    submitting.value = false
  }
}

async function downloadFile(file: { id: number; name: string }) {
  if (!selected.value) return
  try {
    await downloadAdminEvidence(selected.value, file)
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
    <div><h1 class="text-2xl font-semibold tracking-tight">Review submissions</h1><p class="mt-1 text-muted-foreground">Verify student skills, projects and certification evidence with a clear review trail.</p></div>

    <Card>
      <CardContent class="grid gap-3 p-4 md:grid-cols-[1fr_170px_190px_auto]">
        <div class="relative"><Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="search" class="pl-9" placeholder="Search student or submission" @keyup.enter="loadReviews" /></div>
        <select v-model="statusFilter" class="h-9 rounded-lg border border-input bg-background px-3 text-sm"><option value="pending">Pending</option><option value="verified">Verified</option><option value="rejected">Rejected</option><option value="all">All statuses</option></select>
        <select v-model="typeFilter" class="h-9 rounded-lg border border-input bg-background px-3 text-sm"><option value="all">All types</option><option value="skill">Skills</option><option value="project">Projects</option><option value="certification">Certifications</option></select>
        <Button variant="outline" @click="loadReviews">Search</Button>
      </CardContent>
    </Card>

    <p v-if="error" class="text-sm font-medium text-destructive">{{ error }}</p>
    <div v-if="loading" class="flex items-center justify-center py-16 text-muted-foreground"><Loader2 class="mr-2 size-5 animate-spin" /> Loading submissions...</div>

    <Card v-else>
      <CardHeader><CardTitle>Submission queue</CardTitle><CardDescription>{{ reviewItems.length }} record(s) match the current filters.</CardDescription></CardHeader>
      <CardContent class="p-0"><div class="overflow-x-auto"><table class="w-full min-w-[820px] text-left text-sm"><thead class="border-y border-border bg-muted/40 text-xs uppercase text-muted-foreground"><tr><th class="px-6 py-3">Student</th><th class="px-6 py-3">Submission</th><th class="px-6 py-3">Type</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-border"><tr v-for="item in reviewItems" :key="`${item.type}-${item.id}`" class="hover:bg-muted/20"><td class="px-6 py-4"><p class="font-medium">{{ item.studentName }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.studentMatricNo }}</p></td><td class="px-6 py-4"><p class="font-medium">{{ item.title }}</p><p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"><FileText class="size-3.5" /> {{ item.evidenceFiles.length }} file(s)</p></td><td class="px-6 py-4"><Badge variant="secondary">{{ item.type }}</Badge></td><td class="px-6 py-4"><StatusBadge :status="item.status" /></td><td class="px-6 py-4 text-right"><Button variant="outline" size="sm" @click="openReview(item)"><Eye class="size-4" /> View</Button></td></tr><tr v-if="reviewItems.length === 0"><td colspan="5" class="px-6 py-10 text-center text-muted-foreground">No submissions match the current filters.</td></tr></tbody></table></div></CardContent>
    </Card>

    <Dialog v-model:open="dialogOpen"><DialogContent v-if="selected" class="sm:max-w-xl"><DialogHeader><DialogTitle>{{ selected.title }}</DialogTitle><DialogDescription>Submitted by {{ selected.studentName }} ({{ selected.studentMatricNo }})</DialogDescription></DialogHeader><div class="space-y-4"><div class="rounded-xl border border-border bg-muted/30 p-4"><p class="text-sm leading-relaxed">{{ detailsSummary(selected) }}</p><div v-if="selected.evidenceFiles.length" class="mt-4 space-y-2"><p class="text-xs font-medium uppercase text-muted-foreground">Evidence files</p><div v-for="file in selected.evidenceFiles" :key="file.id" class="flex items-center justify-between gap-3 rounded-lg border border-border bg-background px-3 py-2"><span class="flex min-w-0 items-center gap-2 text-sm"><FileText class="size-4 shrink-0 text-muted-foreground" /><span class="truncate">{{ file.name }}</span><span class="shrink-0 text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</span></span><Button variant="ghost" size="sm" @click="downloadFile(file)"><Download class="size-4" /> Download</Button></div></div></div><div v-if="selected.status === 'pending'" class="space-y-2"><Label for="remarks">Review remarks</Label><Textarea id="remarks" v-model="form.remarks" placeholder="Add a clear note for the student" /><p class="text-xs text-muted-foreground">A reason is required when requesting an update.</p></div><div v-else class="rounded-lg bg-muted/40 p-3 text-sm"><p class="font-medium">{{ selected.reviewerName || 'Administrator' }} reviewed this submission</p><p v-if="selected.adminRemarks" class="mt-1 text-muted-foreground">{{ selected.adminRemarks }}</p></div></div><DialogFooter v-if="selected.status === 'pending'" class="gap-2"><Button variant="destructive" :disabled="submitting" @click="requestDecision('rejected')"><X class="size-4" /> Request update</Button><Button :disabled="submitting" @click="requestDecision('verified')"><Check class="size-4" /> Verify submission</Button></DialogFooter></DialogContent></Dialog>

    <Dialog v-model:open="confirmOpen"><DialogContent v-if="selected && pendingDecision" class="sm:max-w-md"><DialogHeader><DialogTitle>{{ pendingDecision === 'verified' ? 'Verify this submission?' : 'Request an update?' }}</DialogTitle><DialogDescription>{{ pendingDecision === 'verified' ? 'This record will become verified and appear as approved portfolio evidence.' : 'This record will be marked as rejected and the student will see the review remarks.' }}</DialogDescription></DialogHeader><div class="rounded-xl border border-border bg-muted/30 p-4"><p class="font-medium">{{ selected.title }}</p><p class="mt-1 text-sm text-muted-foreground">{{ selected.studentName }}</p></div><DialogFooter><Button variant="outline" :disabled="submitting" @click="confirmOpen = false">Cancel</Button><Button :variant="pendingDecision === 'verified' ? 'success' : 'destructive'" :disabled="submitting" @click="confirmDecision">{{ submitting ? 'Saving...' : pendingDecision === 'verified' ? 'Verify submission' : 'Request update' }}</Button></DialogFooter></DialogContent></Dialog>
  </div>
</template>

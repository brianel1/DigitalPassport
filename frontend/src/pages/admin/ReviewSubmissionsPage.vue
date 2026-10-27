<script setup lang="ts">
import { reactive, ref } from 'vue'
import { Check, Eye, FileText, X } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { usePortalData } from '@/composables/usePortalData'
import type { ReviewItem } from '@/types'

const { reviewItems, updateReviewStatus } = usePortalData()
const selected = ref<ReviewItem | null>(null)
const dialogOpen = ref(false)
const form = reactive({ remarks: '' })

function openReview(item: ReviewItem) {
  selected.value = item
  form.remarks = ''
  dialogOpen.value = true
}

function decide(status: 'verified' | 'rejected') {
  if (!selected.value) return
  updateReviewStatus(selected.value.id, status, form.remarks)
  dialogOpen.value = false
}

function detailsSummary(item: ReviewItem) {
  if ('description' in item.details) return item.details.description
  return `${item.details.title} issued by ${item.details.issuingOrganization}.`
}
</script>

<template>
  <div class="space-y-6">
    <div><h1 class="text-2xl font-semibold tracking-tight">Review submissions</h1><p class="mt-1 text-muted-foreground">Verify student projects and certification evidence.</p></div>

    <Card>
      <CardHeader><CardTitle>Submission queue</CardTitle><CardDescription>{{ reviewItems.filter((item) => item.status === 'pending').length }} record(s) require attention.</CardDescription></CardHeader>
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-y border-border bg-muted/40 text-xs uppercase text-muted-foreground"><tr><th class="px-6 py-3">Student</th><th class="px-6 py-3">Submission</th><th class="px-6 py-3">Type</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Action</th></tr></thead>
            <tbody class="divide-y divide-border">
              <tr v-for="item in reviewItems" :key="item.id" class="hover:bg-muted/20">
                <td class="px-6 py-4"><p class="font-medium">{{ item.studentName }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.studentMatricNo }}</p></td>
                <td class="px-6 py-4"><p class="font-medium">{{ item.title }}</p><p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"><FileText class="size-3.5" /> {{ item.evidenceFiles.length }} file(s)</p></td>
                <td class="px-6 py-4"><Badge variant="secondary">{{ item.type }}</Badge></td>
                <td class="px-6 py-4"><StatusBadge :status="item.status" /></td>
                <td class="px-6 py-4 text-right"><Button variant="outline" size="sm" @click="openReview(item)"><Eye class="size-4" /> Review</Button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <Dialog v-model:open="dialogOpen">
      <DialogContent v-if="selected" class="sm:max-w-xl">
        <DialogHeader><DialogTitle>{{ selected.title }}</DialogTitle><DialogDescription>Submitted by {{ selected.studentName }} ({{ selected.studentMatricNo }})</DialogDescription></DialogHeader>
        <div class="rounded-xl border border-border bg-muted/30 p-4"><p class="text-sm leading-relaxed">{{ detailsSummary(selected) }}</p><div class="mt-4 flex flex-wrap gap-2"><Badge v-for="file in selected.evidenceFiles" :key="file" variant="outline"><FileText class="mr-1 size-3.5" /> {{ file }}</Badge></div></div>
        <div class="space-y-2"><Label for="remarks">Review remarks</Label><Textarea id="remarks" v-model="form.remarks" placeholder="Add a clear note for the student" /></div>
        <DialogFooter class="gap-2"><Button variant="destructive" @click="decide('rejected')"><X class="size-4" /> Request update</Button><Button @click="decide('verified')"><Check class="size-4" /> Verify submission</Button></DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

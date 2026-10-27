<script setup lang="ts">
import { reactive, ref } from 'vue'
import { Award, CalendarDays, FileCheck2, Plus, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { usePortalData } from '@/composables/usePortalData'

const { certifications, addCertification, deleteCertification } = usePortalData()
const dialogOpen = ref(false)
const form = reactive({ title: '', issuingOrganization: '', issueDate: '', expiryDate: '', referenceNumber: '', evidenceFile: '' })

function chooseFile(event: Event) {
  const input = event.target as HTMLInputElement
  form.evidenceFile = input.files?.[0]?.name || ''
}

function submit() {
  addCertification({
    title: form.title,
    issuingOrganization: form.issuingOrganization,
    issueDate: form.issueDate,
    expiryDate: form.expiryDate || undefined,
    referenceNumber: form.referenceNumber || undefined,
    evidenceFiles: form.evidenceFile ? [form.evidenceFile] : [],
    linkedSkills: [],
  })
  Object.assign(form, { title: '', issuingOrganization: '', issueDate: '', expiryDate: '', referenceNumber: '', evidenceFile: '' })
  dialogOpen.value = false
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="text-2xl font-semibold tracking-tight">Certifications</h1><p class="mt-1 text-muted-foreground">Maintain verified professional and academic credentials.</p></div>
      <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child><Button><Plus class="size-4" /> Add certification</Button></DialogTrigger>
        <DialogContent>
          <DialogHeader><DialogTitle>Add certification</DialogTitle><DialogDescription>Submit credential information for verification.</DialogDescription></DialogHeader>
          <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><Label for="cert-title">Certification title</Label><Input id="cert-title" v-model="form.title" required /></div>
            <div class="space-y-2"><Label for="issuer">Issuing organization</Label><Input id="issuer" v-model="form.issuingOrganization" required /></div>
            <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="issued">Issue date</Label><Input id="issued" v-model="form.issueDate" type="date" required /></div><div class="space-y-2"><Label for="expires">Expiry date</Label><Input id="expires" v-model="form.expiryDate" type="date" /></div></div>
            <div class="space-y-2"><Label for="reference">Reference number</Label><Input id="reference" v-model="form.referenceNumber" /></div>
            <div class="space-y-2"><Label for="certificate-file">Certificate file</Label><Input id="certificate-file" type="file" required @change="chooseFile" /></div>
            <DialogFooter><Button type="submit">Submit certification</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
      <Card v-for="certification in certifications" :key="certification.id" class="relative overflow-hidden">
        <div class="absolute right-0 top-0 size-28 rounded-bl-full bg-amber-500/5"></div>
        <CardHeader class="relative">
          <div class="flex items-start justify-between gap-3"><div class="flex size-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"><Award class="size-6" /></div><StatusBadge :status="certification.status" /></div>
          <CardTitle class="pt-4 text-lg">{{ certification.title }}</CardTitle>
          <CardDescription>{{ certification.issuingOrganization }}</CardDescription>
        </CardHeader>
        <CardContent class="relative space-y-3 text-sm text-muted-foreground">
          <p class="flex items-center gap-2"><CalendarDays class="size-4" /> Issued {{ certification.issueDate }}</p>
          <p class="flex items-center gap-2"><FileCheck2 class="size-4" /> {{ certification.referenceNumber || 'Reference available' }}</p>
          <p v-if="certification.adminRemarks" class="rounded-lg bg-destructive/10 p-3 text-xs leading-relaxed text-destructive">{{ certification.adminRemarks }}</p>
          <div class="flex items-center justify-between border-t border-border pt-4"><span class="text-xs">{{ certification.evidenceFiles[0] || 'Credential record' }}</span><Button variant="ghost" size="icon-sm" aria-label="Delete certification" @click="deleteCertification(certification.id)"><Trash2 class="size-4" /></Button></div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

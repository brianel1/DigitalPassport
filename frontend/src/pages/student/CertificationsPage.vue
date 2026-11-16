<script setup lang="ts">
import FileUpload from '@/components/shared/FileUpload.vue'
import EvidencePreview from '@/components/shared/EvidencePreview.vue'
import ReviewHistory from '@/components/shared/ReviewHistory.vue'
import type { EvidenceFileSummary } from '@/types'

import { useConfirm } from '@/composables/useConfirm'
import ListPagination from '@/components/shared/ListPagination.vue'
import { usePagination } from '@/composables/usePagination'
import { useFormDraft } from '@/composables/useFormDraft'

import { computed, onMounted, reactive, ref } from 'vue'
import { Award, CalendarDays, FileCheck2, Plus, Pencil, Eye, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue'
import type { Certification, Skill } from '@/types'
import { ApiError, createCertification, getCertifications, getSkills, removeCertification } from '@/lib/api'

const certifications = ref<Certification[]>([])
const skills = ref<Skill[]>([])
const dialogOpen = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const {confirmAction}=useConfirm()
const fileKey=ref(0)
const previewFile=ref<EvidenceFileSummary|null>(null)
const deleteTarget = ref<Certification | null>(null)
const deleteDialogOpen = computed({
  get: () => Boolean(deleteTarget.value),
  set: (open: boolean) => { if (!open) deleteTarget.value = null },
})
const form = reactive<{ editingId:number|null; credentialUrl:string; title: string; issuingOrganization: string; issueDate: string; expiryDate: string; referenceNumber: string; evidenceFile: File | null; skillIds: number[] }>({
  editingId:null,credentialUrl:'',title: '', issuingOrganization: '', issueDate: '', expiryDate: '', referenceNumber: '', evidenceFile: null, skillIds: [],
})


const {page,items:pagedItems,pageSize}=usePagination(certifications)
const savedDrafts=reactive({items:{} as Record<string,typeof form>})
useFormDraft('certifications-other-drafts',savedDrafts)
const draftStore=useFormDraft('CertificationsPage',form)
onMounted(async () => {
  try {
    ;[certifications.value, skills.value] = await Promise.all([getCertifications(), getSkills()])
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Certifications could not be loaded.'
  } finally {
    loading.value = false
  }
})

function rememberDraft() { savedDrafts.items[String(form.editingId??'new')]={...form,skillIds:[...form.skillIds]} }
function openNew() {
  rememberDraft()
  const saved=savedDrafts.items.new
  Object.assign(form,saved??emptyForm())
  fileKey.value++;error.value='';dialogOpen.value=true
}
function emptyForm():typeof form { return {editingId:null,credentialUrl:'',title:'',issuingOrganization:'',issueDate:'',expiryDate:'',referenceNumber:'',evidenceFile:null,skillIds:[]}}
function editRecord(item:Certification) {
  rememberDraft()
  Object.assign(form,savedDrafts.items[String(item.id)]??{editingId:item.id,title:item.title,issuingOrganization:item.issuingOrganization,issueDate:item.issueDate,expiryDate:item.expiryDate??'',referenceNumber:item.referenceNumber??'',credentialUrl:item.credentialUrl??'',evidenceFile:null,skillIds:[...item.linkedSkills]})
  fileKey.value++;error.value='';dialogOpen.value=true
}

async function discardDraft() {
  if(!await confirmAction({title:'Discard this draft?',description:'Unsubmitted details in this form will be cleared.',confirmLabel:'Discard draft',destructive:true}))return
  delete savedDrafts.items[String(form.editingId??'new')];Object.assign(form,emptyForm());fileKey.value++;draftStore.clear();error.value=''
}

async function submit() {
  if (!form.title.trim() || !form.issuingOrganization.trim() || !form.issueDate) {
    error.value = 'Complete the title, issuing organization and issue date.'
    return
  }
  if (!form.evidenceFile && !form.editingId) {
    error.value = 'Select a certificate file before submitting.'
    return
  }
  if (!await confirmAction({title:'Submit this certification for review?',description:'This record and its supporting evidence will be submitted for administrator review.',confirmLabel:'Submit certification'}))return
  submitting.value = true
  error.value = ''
  try {
    const certification = await createCertification({
      credentialUrl:form.credentialUrl,
      title: form.title,
      issuingOrganization: form.issuingOrganization,
      issueDate: form.issueDate,
      expiryDate: form.expiryDate || undefined,
      referenceNumber: form.referenceNumber || undefined,
      evidence: form.evidenceFile,
      skillIds: form.skillIds,
    },form.editingId)
    certifications.value=[certification,...certifications.value.filter(item=>item.id!==certification.id)]
    delete savedDrafts.items[String(form.editingId??'new')]
    Object.assign(form, emptyForm())
    fileKey.value++
    draftStore.clear()
    dialogOpen.value = false
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The certification could not be saved.'
  } finally {
    submitting.value = false
  }
}

async function deleteCertification() {
  if (!deleteTarget.value || submitting.value) return
  submitting.value=true
  error.value=''
  try {
    await removeCertification(deleteTarget.value.id)
    certifications.value = certifications.value.filter((certification) => certification.id !== deleteTarget.value?.id)
    deleteTarget.value = null
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The certification could not be deleted.'
  } finally { submitting.value=false }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="page-heading">Certifications</h1><p class="mt-1 text-muted-foreground">Maintain verified professional and academic credentials.</p></div>
      <Dialog v-model:open="dialogOpen">
        <Button @click="openNew"><Plus class="size-4" /> Add certification</Button>
        <DialogContent class="max-h-[90vh] overflow-y-auto">
          <DialogHeader><DialogTitle>{{ form.editingId?'Edit & resubmit certification':'Add certification' }}</DialogTitle><DialogDescription>Submit credential information for verification.</DialogDescription></DialogHeader>
          <form class="space-y-4" @submit.prevent="submit"><p class="text-xs leading-5 text-muted-foreground">{{form.editingId?'Changes go back to your administrator for review. Existing evidence is kept.':'Unfinished details are kept in this browser tab until you submit.'}}</p><p v-if="error" role="alert" class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive">{{ error }}</p>
            <div class="space-y-2"><Label for="cert-title">Certification title</Label><Input id="cert-title" v-model="form.title" required /></div>
            <div class="space-y-2"><Label for="issuer">Issuing organization</Label><Input id="issuer" v-model="form.issuingOrganization" required /></div>
            <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="issued">Issue date</Label><Input id="issued" v-model="form.issueDate" type="date" required /></div><div class="space-y-2"><Label for="expires">Expiry date</Label><Input id="expires" v-model="form.expiryDate" type="date" /></div></div>
            <div class="space-y-2"><Label for="credential-url">Credential URL</Label><Input id="credential-url" v-model="form.credentialUrl" type="url" /></div>
            <div class="space-y-2"><Label for="reference">Reference number</Label><Input id="reference" v-model="form.referenceNumber" /></div>
            <FileUpload id="certificate-file" :key="fileKey" v-model="form.evidenceFile" label="Certificate file" accept=".pdf,.jpg,.jpeg,.png" :max-mb="10" formats="PDF, JPG or PNG" :disabled="submitting" :required="!form.editingId" />
            <fieldset v-if="skills.length" class="space-y-2"><legend class="text-sm font-medium">Skills supported</legend><div class="flex flex-wrap gap-2"><label v-for="skill in skills" :key="skill.id" class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-border px-3 py-1.5 text-xs"><input v-model="form.skillIds" type="checkbox" :value="skill.id" class="accent-primary" />{{ skill.name }}</label></div></fieldset>
            <DialogFooter><Button type="button" variant="ghost" :disabled="submitting" @click="discardDraft">Discard draft</Button><Button type="submit" :disabled="submitting">{{ submitting ? 'Saving...' : 'Submit certification' }}</Button></DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <p v-if="error" role="alert" class="text-sm font-medium text-destructive">{{ error }}</p>
    <p v-if="loading" class="text-sm text-muted-foreground">Loading certifications...</p>
    <p v-else-if="certifications.length === 0" class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">No certifications yet. Add a credential for administrator verification.</p>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
      <Card v-for="certification in pagedItems" :key="certification.id" class="relative overflow-hidden">
        <div class="absolute right-0 top-0 size-28 rounded-bl-full bg-amber-500/5"></div>
        <CardHeader class="relative">
          <div class="flex items-start justify-between gap-3"><div class="flex size-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"><Award class="size-6" /></div><StatusBadge :status="certification.status" /></div>
          <CardTitle class="pt-4 text-lg">{{ certification.title }}</CardTitle>
          <CardDescription>{{ certification.issuingOrganization }}</CardDescription>
        </CardHeader>
        <CardContent class="relative space-y-3 text-sm text-muted-foreground">
          <p class="flex items-center gap-2"><CalendarDays class="size-4" /> Issued {{ certification.issueDate }}</p>
          <p v-if="certification.expiryDate" :class="certification.expired?'font-semibold text-destructive':'text-muted-foreground'">{{certification.expired?'Expired':'Valid until'}} {{certification.expiryDate}}</p><p class="flex items-center gap-2"><FileCheck2 class="size-4" /> {{ certification.referenceNumber || 'Reference available' }}</p>
          <p v-if="certification.linkedSkills.length" class="text-xs">Supports: {{ skills.filter((skill) => certification.linkedSkills.includes(skill.id)).map((skill) => skill.name).join(', ') }}</p>
          <p v-if="certification.adminRemarks" class="rounded-lg bg-destructive/10 p-3 text-xs leading-relaxed text-destructive">{{ certification.adminRemarks }}</p>
          <div class="flex items-center justify-between border-t border-border pt-4"><div class="flex flex-wrap gap-2"><Button v-for="file in certification.evidence" :key="file.id" size="sm" variant="outline" @click="previewFile=file"><Eye class="size-3.5" />View evidence</Button><Button variant="outline" size="sm" @click="editRecord(certification)"><Pencil class="size-3.5" />{{certification.status==='rejected'?'Update & resubmit':'Edit'}}</Button></div><Button variant="destructive" size="icon-sm" aria-label="Delete certification" @click="deleteTarget = certification"><Trash2 class="size-4" /></Button></div>
          <p v-if="certification.reviewerName" class="mt-3 text-xs text-muted-foreground">Reviewed by {{certification.reviewerName}} · {{new Date(certification.reviewedAt!).toLocaleDateString('en-MY')}}</p><ReviewHistory :items="certification.reviewHistory" />
        </CardContent>
      </Card>
    </div>
    <ListPagination v-model:page="page" :total="certifications.length" :page-size="pageSize" label="certifications" />
  </div>
  <EvidencePreview v-model="previewFile" />
  <ConfirmDialog v-model:open="deleteDialogOpen" title="Delete this certification?" description="This certification and its uploaded evidence will be permanently removed from your portfolio." confirm-label="Delete certification" :loading="submitting" :error="error" @confirm="deleteCertification" />
</template>

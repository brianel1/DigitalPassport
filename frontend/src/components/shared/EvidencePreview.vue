<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'
import { Download, Loader2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { getEvidenceBlob } from '@/lib/api'
import type { EvidenceFileSummary } from '@/types'
const file=defineModel<EvidenceFileSummary|null>({required:true})
const url=ref(''), error=ref(''), busy=ref(false)
let request=0
function cleanup(){if(url.value) URL.revokeObjectURL(url.value);url.value=''}
watch(file,async selected=>{
  const current=++request;cleanup();error.value='';busy.value=!!selected
  if(!selected)return
  try {const blob=await getEvidenceBlob(selected.id);if(current===request)url.value=URL.createObjectURL(blob)}
  catch(cause){if(current===request)error.value=cause instanceof Error?cause.message:'The file could not be opened.'}
  finally{if(current===request)busy.value=false}
})
onBeforeUnmount(()=>{request++;cleanup()})
</script>
<template><Dialog :open="!!file" @update:open="value=>{if(!value)file=null}"><DialogContent class="z-[71] max-h-[92dvh] overflow-y-auto sm:max-w-4xl" overlay-class="z-[70]"><DialogHeader><DialogTitle>{{file?.name}}</DialogTitle><DialogDescription>Supporting evidence</DialogDescription></DialogHeader><div v-if="busy" role="status" class="flex items-center justify-center gap-3 py-16"><Loader2 class="size-5 animate-spin" />Opening evidence…</div><p v-if="error" role="alert" class="rounded-lg bg-destructive/10 p-4 text-sm text-destructive">{{error}}</p><template v-if="url && file"><img v-if="file.mimeType.startsWith('image/')" :src="url" :alt="file.name" class="max-h-[65dvh] w-full rounded-lg bg-white object-contain" /><iframe v-else-if="file.mimeType==='application/pdf'" :src="url" :title="'Preview of '+file.name" class="h-[65dvh] w-full rounded-lg bg-white"></iframe><Button as-child variant="outline"><a :href="url" :download="file.name"><Download class="size-4" />Download file</a></Button></template></DialogContent></Dialog></template>

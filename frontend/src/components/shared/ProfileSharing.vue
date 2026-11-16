<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Copy, Eye, Link2, Loader2, Share2, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import AppSelect from './AppSelect.vue'
import SharedProfileView from './SharedProfileView.vue'
import ListPagination from './ListPagination.vue'
import { usePagination } from '@/composables/usePagination'
import { useConfirm } from '@/composables/useConfirm'
import { createShare, listShares, previewShare, revokeShare, shareFields, type ProfileShare, type SharedProfileData } from '@/lib/sharing'

const open=ref(false), busy=ref(false), error=ref(''), notice=ref(''), days=ref('7')
const selection=ref(['skills','projects','certifications','assessments'])
const shares=ref<ProfileShare[]>([]), preview=ref<SharedProfileData|null>(null), qr=ref(''), selected=ref<ProfileShare|null>(null)
const {items:pageShares,page}=usePagination(shares,4)
const {confirmAction}=useConfirm()
const url=computed(()=>selected.value?.path ? new URL(selected.value.path,location.origin).href : '')
function selectionLabel(fields:string[]){return fields.map(value=>shareFields.find(field=>field.value===value)?.label).join(', ')}
const localSite=['localhost','127.0.0.1'].includes(location.hostname)
watch(selection,()=>{preview.value=null},{deep:true})
watch(open,async value=>{if(!value)return;error.value='';busy.value=true;try{shares.value=(await listShares()).data}catch(e){error.value=(e as Error).message}finally{busy.value=false}})
async function showLink(share:ProfileShare){selected.value=share;qr.value='';try{const module=await import('qrcode');qr.value=await module.toDataURL(new URL(share.path!,location.origin).href,{width:224,margin:2,color:{dark:'#142542',light:'#ffffff'}})}catch{error.value='The QR code could not be prepared. You can still copy the link.'}}
async function prepare(){busy.value=true;error.value='';try{preview.value=await previewShare(selection.value)}catch(e){error.value=(e as Error).message}finally{busy.value=false}}
async function publish(){if(!preview.value)return;busy.value=true;error.value='';try{const share=await createShare(selection.value,Number(days.value));shares.value.unshift(share);await showLink(share);notice.value='Your link is ready.'}catch(e){error.value=(e as Error).message}finally{busy.value=false}}
async function revoke(share:ProfileShare){if(!await confirmAction({title:'Revoke this link?',description:'People with this link or QR code will no longer be able to open your profile.',confirmLabel:'Revoke link',destructive:true}))return;busy.value=true;try{await revokeShare(share.id);share.active=false;share.revoked=true;share.path=null;if(selected.value?.id===share.id){selected.value=null;qr.value=''}notice.value='Link revoked.'}catch(e){error.value=(e as Error).message}finally{busy.value=false}}
async function copy(){try{await navigator.clipboard.writeText(url.value);notice.value='Link copied.'}catch{notice.value='Select and copy the link below.'}}
</script>

<template>
  <Button variant="outline" @click="open=true"><Share2 class="size-4" /> Share profile</Button>
  <Dialog v-model:open="open"><DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-3xl" @escape-key-down="busy&&$event.preventDefault()" @pointer-down-outside="busy&&$event.preventDefault()">
    <DialogHeader><DialogTitle>Share your skills profile</DialogTitle><DialogDescription>Your profile stays private until you create a link. Anyone with that link can view the fields you choose until it expires or you revoke it.</DialogDescription></DialogHeader>
    <p v-if="error" role="alert" class="rounded-xl bg-destructive/10 p-3 text-sm text-destructive">{{error}}</p><p v-if="notice" role="status" class="text-sm text-primary">{{notice}}</p>
    <p class="text-sm text-muted-foreground">Your full name is included. Contact details, student ID and uploaded evidence files stay private. Selected records reflect future verified updates.</p>
    <fieldset :disabled="busy" class="grid gap-3 sm:grid-cols-2"><legend class="mb-3 font-semibold">Choose what to share</legend><label v-for="field in shareFields" :key="field.value" class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-border p-3 text-sm"><input v-model="selection" type="checkbox" :value="field.value" class="size-4 accent-primary" />{{field.label}}</label></fieldset>
    <div class="flex flex-wrap items-end gap-3"><div><label for="share-expiry" class="mb-2 block text-sm font-medium">Link expires after</label><AppSelect id="share-expiry" v-model="days" :options="[{value:'1',label:'1 day'},{value:'7',label:'7 days'},{value:'30',label:'30 days'}]" /></div><Button variant="outline" :disabled="busy||!selection.length" @click="prepare"><Eye class="size-4" /> Preview selected fields</Button></div>
    <SharedProfileView v-if="preview" :profile="preview" />
    <Button v-if="preview" :disabled="busy" @click="publish"><Loader2 v-if="busy" class="size-4 animate-spin" /><Link2 v-else class="size-4" /> Create share link</Button>
    <div v-if="url" class="rounded-xl border border-primary/30 bg-accent p-4"><div class="flex flex-wrap items-center gap-4"><img v-if="qr" :src="qr" width="160" height="160" alt="QR code for your shared profile" class="rounded-lg" /><div class="min-w-0 flex-1"><p class="font-semibold">Ready to share</p><label for="share-link" class="sr-only">Share link</label><input id="share-link" :value="url" readonly class="my-3 w-full rounded-lg border border-border bg-white p-3 text-sm text-navy" @focus="($event.target as HTMLInputElement).select()" /><Button variant="outline" @click="copy"><Copy class="size-4" /> Copy link</Button><p v-if="localSite" class="mt-2 text-xs text-muted-foreground">This local link opens on this computer. Use your deployed site address when sharing with someone else.</p></div></div></div>
    <section v-if="shares.length" class="space-y-3 border-t border-border pt-4"><h3 class="font-semibold">Your links</h3><div v-for="share in pageShares" :key="share.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border p-3"><div class="text-sm"><p class="font-medium">{{share.active?'Active':share.revoked?'Revoked':'Expired'}}</p><p class="text-xs text-muted-foreground">Expires {{new Date(share.expiresAt).toLocaleString('en-MY')}}</p><p class="text-xs text-muted-foreground">{{selectionLabel(share.selection)}}</p></div><div v-if="share.active" class="flex gap-2"><Button size="sm" variant="outline" :disabled="busy" @click="showLink(share)">Link & QR</Button><Button size="sm" variant="outline" :disabled="busy" @click="revoke(share)"><Trash2 class="size-4 text-destructive" /> Revoke</Button></div></div><ListPagination v-model:page="page" :total="shares.length" :page-size="4" label="links" /></section>
  </DialogContent></Dialog>
</template>

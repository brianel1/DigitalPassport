<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Activity, RefreshCw } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { apiRequest } from '@/lib/api'
import { useConfirm } from '@/composables/useConfirm'
import TaskProgress from './TaskProgress.vue'
import ListPagination from './ListPagination.vue'
import { usePagination } from '@/composables/usePagination'
type Sync={id:number;source:string;status:string;saved_count:number;message:string|null;completed_at:string|null}
type Event={id:number;kind:string;status:string;message:string;created_at:string}
type Provider={source:string;configured:boolean;freshListings:number;method:string;lastSync:Sync|null}
type Status={ai:{configured:boolean;reachable:boolean;modelAvailable:boolean;model:string|null};providers:Provider[];syncHistory:Sync[];recentEvents:Event[];exportFailures:number;unavailableLinks:number;ocrAvailable:boolean}
const data=ref<Status|null>(null),error=ref(''),loading=ref(false),syncing=ref(false)
const {confirmAction}=useConfirm()
const {page,items,pageSize}=usePagination(()=>data.value?.syncHistory??[],5)
const {page:eventPage,items:events,pageSize:eventSize}=usePagination(()=>data.value?.recentEvents??[],5)
async function refresh(){loading.value=true;error.value='';try{data.value=await apiRequest<Status>('/api/admin/system-status')}catch(cause){error.value=cause instanceof Error?cause.message:'System status could not be loaded.'}finally{loading.value=false}}
async function sync(){if(!await confirmAction({title:'Refresh job listings?',description:'Fetch current listings from configured providers and check their links.',confirmLabel:'Refresh listings'}))return;syncing.value=true;error.value='';try{await apiRequest('/api/admin/system-status/sync',{method:'POST'});await refresh()}catch(cause){error.value=cause instanceof Error?cause.message:'The sync could not finish.'}finally{syncing.value=false}}
function providerState(provider:Provider){if(!provider.configured)return 'API key needed';if(provider.lastSync?.status==='blocked')return 'Source access restricted';if(provider.lastSync?.status==='failed')return 'Last refresh failed';return provider.freshListings+' current unique listings'}
onMounted(refresh)
</script>
<template>
  <section class="rounded-2xl border bg-card p-5 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="flex items-center gap-2 font-heading text-xl"><Activity class="size-5 text-primary" />Service status</h2><Button size="sm" variant="outline" :disabled="loading||syncing" @click="refresh"><RefreshCw :class="['size-4',loading?'animate-spin':'']" />Check status</Button></div>
    <p v-if="error" role="alert" class="mt-4 text-sm text-destructive">{{error}}</p>
    <p v-if="loading&&!data" role="status" class="mt-4 text-sm text-muted-foreground">Checking services…</p>
    <template v-if="data">
      <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl p-4" :class="data.ai.modelAvailable?'bg-teal-soft text-teal':'bg-warning-soft text-warning'"><p class="text-xs font-semibold">LOCAL AI</p><p class="mt-2 font-semibold">{{data.ai.modelAvailable?'Ready':!data.ai.configured?'Model not configured':!data.ai.reachable?'Service unavailable':'Model not installed'}}</p><p class="mt-1 text-xs">{{data.ai.model||'Choose a model in local configuration'}}</p></div>
        <div v-for="provider in data.providers" :key="provider.source" class="rounded-xl border p-4" :class="['blocked','failed'].includes(provider.lastSync?.status??'')?'bg-warning-soft':'bg-accent'">
          <p class="text-xs font-semibold uppercase text-primary">{{provider.source}}</p><p class="mt-2 font-semibold">{{providerState(provider)}}</p><p class="mt-1 text-xs text-muted-foreground">{{provider.method}}</p>
          <p class="mt-2 text-xs text-muted-foreground">{{provider.lastSync?.completed_at?'Last sync '+new Date(provider.lastSync.completed_at).toLocaleString('en-MY'):'No sync recorded yet'}}</p>
          <p v-if="provider.lastSync?.message" class="mt-2 text-xs leading-5">{{provider.lastSync.message}}</p>
        </div>
        <div class="rounded-xl border p-4"><p class="text-xs font-semibold text-muted-foreground">EXPORTS · LAST 7 DAYS</p><p class="mt-2 font-semibold" :class="data.exportFailures?'text-destructive':'text-teal'">{{data.exportFailures}} failed</p><p class="mt-1 text-xs text-muted-foreground">Scanned CV reading: {{data.ocrAvailable?'installed':'not installed'}}</p></div>
      </div>
      <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><p class="text-xs text-muted-foreground">{{data.unavailableLinks}} unavailable provider links excluded. Scheduled refresh: 08:00 when the scheduler is running.</p><Button size="sm" variant="outline" :disabled="syncing" @click="sync">{{syncing?'Refreshing…':'Refresh listings'}}</Button></div>
      <details class="mt-5 border-t pt-4"><summary class="cursor-pointer text-sm font-semibold">Recent sync and service activity</summary><p class="mt-3 text-xs text-muted-foreground">Latest 30 records of each type.</p>
        <div class="mt-4 space-y-3"><div v-for="run in items" :key="run.id" class="flex flex-wrap justify-between gap-2 rounded-lg border p-3 text-xs"><strong class="capitalize">{{run.source}}</strong><span>{{run.status.replaceAll('_',' ')}} · {{run.saved_count}} listings</span><span v-if="run.message">{{run.message}}</span></div><ListPagination v-model:page="page" :total="data.syncHistory.length" :page-size="pageSize" label="syncs" />
          <div v-for="event in events" :key="event.id" class="rounded-lg border p-3 text-xs"><span :class="event.status==='failed'?'text-destructive':'text-teal'">{{event.kind}} · {{event.status}}</span><p class="mt-1">{{event.message}}</p><time class="mt-1 block text-muted-foreground">{{new Date(event.created_at).toLocaleString('en-MY')}}</time></div><ListPagination v-model:page="eventPage" :total="data.recentEvents.length" :page-size="eventSize" label="events" />
        </div>
      </details>
    </template>
    <TaskProgress :active="syncing" title="Refreshing Malaysia job sources…" detail="Several pages are being collected. Available results are kept if a source cannot finish." />
  </section>
</template>

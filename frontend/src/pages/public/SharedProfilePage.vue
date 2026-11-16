<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Loader2 } from '@lucide/vue'
import SharedProfileView from '@/components/shared/SharedProfileView.vue'
import type { SharedProfileData } from '@/lib/sharing'
const route=useRoute(), profile=ref<SharedProfileData|null>(null), error=ref(''), loading=ref(true)
const robots=document.createElement('meta');robots.name='robots';robots.content='noindex, nofollow';document.head.append(robots)
onUnmounted(()=>robots.remove())
onMounted(async()=>{try{const response=await fetch(`/api/shared-profile/${encodeURIComponent(String(route.params.token))}`,{referrerPolicy:'no-referrer'});if(!response.ok)throw new Error(response.status===404?'This link has expired, was revoked, or is unavailable. Ask the profile owner for a new link.':'The profile could not be opened. Please try again.');profile.value=await response.json()}catch(e){error.value=(e as Error).message}finally{loading.value=false}})
</script>
<template><main class="mx-auto min-h-[60vh] max-w-4xl px-4 py-12"><p v-if="loading" role="status" class="flex items-center gap-3"><Loader2 class="size-5 animate-spin" /> Opening shared profile…</p><section v-else-if="error" class="rounded-xl border border-border bg-card p-8"><h1 class="text-2xl font-semibold">Profile unavailable</h1><p role="alert" class="mt-3 text-muted-foreground">{{error}}</p></section><SharedProfileView v-else-if="profile" :profile="profile" /></main></template>

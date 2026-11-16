<script setup lang="ts">
import type { ReviewHistory } from '@/types'
defineProps<{items?:ReviewHistory[]}>()
</script>
<template><details v-if="items?.length" class="mt-4 rounded-xl border p-4 text-sm"><summary class="cursor-pointer font-semibold">Review history · {{items.length}}</summary><ol class="mt-3 space-y-4"><li v-for="item in items" :key="item.id" class="border-l-2 border-primary/30 pl-3"><p class="font-medium">{{item.decision==='verified'?'Verified':'Update requested'}} <span v-if="item.revision" class="text-xs text-muted-foreground">· Revision {{item.revision}}</span></p><p class="mt-1 text-xs text-muted-foreground">{{item.reviewerName}} · {{new Date(item.reviewedAt).toLocaleString('en-MY')}}</p><p v-if="item.remarks" class="mt-2 whitespace-pre-wrap text-sm leading-6">{{item.remarks}}</p></li></ol></details></template>

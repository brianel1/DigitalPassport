<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight } from '@lucide/vue'
const props = withDefaults(defineProps<{ total: number; pageSize?: number; label?: string }>(), { pageSize: 8, label: 'records' })
const page = defineModel<number>('page', { required: true })
const pages = computed(() => Math.max(1, Math.ceil(props.total / props.pageSize)))
const start = computed(() => props.total ? (page.value - 1) * props.pageSize + 1 : 0)
</script>
<template><nav v-if="total" class="list-pagination mt-6 flex flex-wrap items-center justify-between gap-3 border-t px-1 pt-5 pb-2" :aria-label="label + ' pagination'"><p class="text-xs text-muted-foreground">{{ start }}–{{ Math.min(page * pageSize,total) }} of {{ total }} {{ label }}</p><div class="flex items-center gap-3"><Button variant="outline" size="icon" :disabled="page <= 1" :aria-label="'Previous page of '+label" @click="page--"><ChevronLeft class="size-4" /></Button><span class="text-xs" aria-live="polite">Page {{ page }} of {{ pages }}</span><Button variant="outline" size="icon" :disabled="page >= pages" :aria-label="'Next page of '+label" @click="page++"><ChevronRight class="size-4" /></Button></div></nav></template>

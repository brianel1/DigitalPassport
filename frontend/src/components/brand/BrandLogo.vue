<script setup lang="ts">
import { computed } from 'vue'
import { brand } from '@/lib/brand'

const props = withDefaults(defineProps<{ showWordmark?: boolean; size?: 'sm' | 'md' | 'lg' }>(), {
  showWordmark: true,
  size: 'md',
})

const iconClass = computed(() => ({ sm: 'size-8', md: 'size-10', lg: 'size-12' })[props.size])
const wordmarkClass = computed(() => ({ sm: 'text-sm', md: 'text-base', lg: 'text-lg' })[props.size])
</script>

<template>
  <span class="inline-flex items-center gap-2.5">
    <svg :class="[iconClass, 'shrink-0']" viewBox="0 0 40 40" fill="none" aria-hidden="true">
      <rect x="3" y="21" width="14" height="16" rx="4" fill="currentColor" />
      <rect x="3" y="3" width="14" height="14" rx="4" fill="currentColor" opacity=".5" />
      <rect x="21" y="3" width="16" height="34" rx="4" fill="currentColor" />
      <path d="m25 20 3 3 5-7" stroke="var(--brand-mark-ink, white)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span v-if="showWordmark" :class="['font-semibold tracking-tight whitespace-nowrap', wordmarkClass]">{{ brand.name }}</span>
  </span>
</template>

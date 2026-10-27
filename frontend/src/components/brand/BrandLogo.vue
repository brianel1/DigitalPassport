<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{ showWordmark?: boolean; size?: 'sm' | 'md' | 'lg' }>(), {
  showWordmark: true,
  size: 'md',
})

const iconClass = computed(() => ({ sm: 'size-8', md: 'size-10', lg: 'size-12' })[props.size])
const wordmarkClass = computed(() => ({ sm: 'text-sm', md: 'text-base', lg: 'text-lg' })[props.size])
</script>

<template>
  <span class="inline-flex items-center gap-2.5">
    <svg :class="iconClass" viewBox="0 0 48 48" fill="none" aria-hidden="true">
      <path d="M11 8.5h17.5A8.5 8.5 0 0 1 37 17v14a8.5 8.5 0 0 1-8.5 8.5H11" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" />
      <path d="M11 8.5v31" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" />
      <path d="m22 17.5 2.1 4.3 4.8.7-3.45 3.35.82 4.75L22 28.35l-4.27 2.25.82-4.75-3.45-3.35 4.8-.7L22 17.5Z" fill="currentColor" />
      <path d="M17 36.5h12" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" opacity=".45" />
    </svg>
    <span v-if="showWordmark" :class="['font-semibold tracking-tight', wordmarkClass]">DigitalPassport</span>
  </span>
</template>

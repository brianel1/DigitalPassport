<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import type { VerificationStatus } from '@/types'

const props = defineProps<{ status: VerificationStatus }>()

const label = computed(() => ({
  verified: 'Verified',
  pending: 'Pending Review',
  rejected: 'Needs Update',
})[props.status])

const classes = computed(() => ({
  verified: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
  pending: 'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400',
  rejected: 'border-red-500/30 bg-red-500/10 text-red-600 dark:text-red-400',
})[props.status])
</script>

<template>
  <Badge variant="outline" :class="classes">{{ label }}</Badge>
</template>

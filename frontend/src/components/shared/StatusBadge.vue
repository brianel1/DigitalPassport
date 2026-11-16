<script setup lang="ts">
import { computed } from 'vue'
import { Check, Clock3, RotateCcw } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import type { VerificationStatus } from '@/types'

const props = defineProps<{ status: VerificationStatus }>()

const label = computed(() => ({
  verified: 'Verified',
  pending: 'Pending Review',
  rejected: 'Needs Update',
})[props.status])

const classes = computed(() => ({
  verified: 'border-success/20 bg-success-soft text-success',
  pending: 'border-warning/20 bg-warning-soft text-warning',
  rejected: 'border-red-500/30 bg-red-500/10 text-red-600 dark:text-red-400',
})[props.status])
</script>

<template>
  <Badge variant="outline" :class="classes"><component :is="{ verified: Check, pending: Clock3, rejected: RotateCcw }[status]" class="mr-1 size-3" />{{ label }}</Badge>
</template>

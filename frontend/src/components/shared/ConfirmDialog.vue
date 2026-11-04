<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import type { ButtonVariants } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'

const props = withDefaults(defineProps<{
  open: boolean
  title: string
  description: string
  confirmLabel?: string
  cancelLabel?: string
  destructive?: boolean
  variant?: ButtonVariants['variant']
  loading?: boolean
}>(), {
  confirmLabel: 'Confirm',
  cancelLabel: 'Cancel',
  destructive: true,
  variant: undefined,
  loading: false,
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  confirm: []
}>()

const open = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value),
})

const confirmVariant = computed<ButtonVariants['variant']>(() => props.variant ?? (props.destructive ? 'destructive' : 'default'))
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-md">
      <DialogHeader><DialogTitle>{{ title }}</DialogTitle><DialogDescription>{{ description }}</DialogDescription></DialogHeader>
      <DialogFooter><Button variant="outline" :disabled="loading" @click="open = false">{{ cancelLabel }}</Button><Button :variant="confirmVariant" :disabled="loading" @click="emit('confirm')">{{ loading ? 'Saving...' : confirmLabel }}</Button></DialogFooter>
    </DialogContent>
  </Dialog>
</template>

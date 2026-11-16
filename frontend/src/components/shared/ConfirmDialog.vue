<script setup lang="ts">
import { computed } from 'vue'
import { TriangleAlert } from '@lucide/vue'
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
  error?: string
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
  set: (value: boolean) => { if (!props.loading) emit('update:open', value) },
})

const confirmVariant = computed<ButtonVariants['variant']>(() => props.variant ?? (props.destructive ? 'destructive' : 'default'))
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent data-confirmation-dialog class="z-[81] sm:max-w-md" :class="{'confirmation-danger': confirmVariant === 'destructive'}" overlay-class="z-[80]" :show-close-button="!loading">
      <span v-if="confirmVariant === 'destructive'" class="danger-icon" aria-hidden="true"><TriangleAlert class="size-6" /></span>
      <DialogHeader><DialogTitle>{{ title }}</DialogTitle><DialogDescription>{{ description }}</DialogDescription></DialogHeader>
      <slot />
      <p v-if="error" role="alert" class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive">{{ error }}</p>
      <DialogFooter><Button variant="outline" :disabled="loading" @click="open = false">{{ cancelLabel }}</Button><Button :variant="confirmVariant" :disabled="loading" @click="emit('confirm')">{{ loading ? 'Saving...' : confirmLabel }}</Button></DialogFooter>
    </DialogContent>
  </Dialog>
</template>
<style>
.confirmation-danger{background:#fff1f2!important;color:#881337!important;border:2px solid #fda4af!important;box-shadow:0 24px 80px #7f1d1d33}
.confirmation-danger [data-slot="dialog-description"]{color:#9f1239!important}
.confirmation-danger .danger-icon{display:grid;place-items:center;width:48px;height:48px;border-radius:14px;background:#fecdd3;color:#9f1239}
.dark .confirmation-danger{background:#351923!important;color:#ffe4e6!important;border-color:#be4763!important}
.dark .confirmation-danger [data-slot="dialog-description"]{color:#fecdd3!important}
.dark .confirmation-danger .danger-icon{background:#70253a;color:#ffe4e6}
</style>

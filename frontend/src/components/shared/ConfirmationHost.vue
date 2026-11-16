<script setup lang="ts">
import ConfirmDialog from './ConfirmDialog.vue'
import { useConfirm } from '@/composables/useConfirm'
const { pending, finish } = useConfirm()
</script>
<template><ConfirmDialog :open="!!pending" :title="pending?.title ?? ''" :description="pending?.description ?? ''" :confirm-label="pending?.confirmLabel ?? 'Continue'" :destructive="pending?.destructive ?? false" @update:open="value => { if (!value) finish(false) }" @confirm="finish(true)" /></template>

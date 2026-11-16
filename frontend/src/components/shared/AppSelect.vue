<script setup lang="ts">
import { computed } from 'vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
defineOptions({ inheritAttrs: false })
type Value = string | number | undefined | null
const props = defineProps<{ modelValue?: Value; options: { value: Value; label: string }[]; disabled?: boolean; placeholder?: string }>()
const emit = defineEmits<{ 'update:modelValue': [value: any] }>()
const selected = computed(() => String(props.options.findIndex(o => o.value === props.modelValue)))
function update(value: unknown) { const option = props.options[Number(value)]; if (option) emit('update:modelValue', option.value) }
</script>
<template><Select :model-value="selected" :disabled="disabled" @update:model-value="update"><SelectTrigger v-bind="$attrs" class="app-select w-full min-h-11"><SelectValue :placeholder="placeholder ?? 'Choose an option'" /></SelectTrigger><SelectContent position="popper" align="start" class="app-select-menu max-h-72"><SelectItem v-for="(option,index) in options" :key="index" :value="String(index)" class="min-h-10 cursor-pointer rounded-lg px-3 py-2">{{ option.label }}</SelectItem></SelectContent></Select></template>

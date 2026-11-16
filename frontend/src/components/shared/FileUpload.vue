<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Check, FileText, UploadCloud, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'

const props = withDefaults(defineProps<{
  id: string; label: string; accept: string; maxMb: number; formats: string; hint?: string; disabled?: boolean; required?: boolean
}>(), { disabled: false, required: false, hint: '' })
const file = defineModel<File | null>({ default: null })
const emit = defineEmits<{ selected: [file: File]; error: [message: string] }>()
const input = ref<HTMLInputElement>()
const dragging = ref(false)
const error = ref('')
const fileSize = computed(() => !file.value ? '' : file.value.size < 1024 * 1024 ? `${Math.max(1, Math.round(file.value.size / 1024))} KB` : `${(file.value.size / (1024 * 1024)).toFixed(1)} MB`)
watch(file, value => { if (!value && input.value) input.value.value = '' })
function choose(value?: File, count = 1) {
  if (props.disabled || !value) return
  const accepted = props.accept.split(',').map(type => type.trim().toLowerCase())
  const extension = '.' + value.name.split('.').pop()?.toLowerCase()
  const valid = accepted.some(type => type.startsWith('.') ? type === extension : type.endsWith('/*') ? value.type.startsWith(type.slice(0, -1)) : value.type === type)
  error.value = count > 1 ? 'Choose one file at a time.' : !valid ? `Choose a ${props.formats} file.` : value.size > props.maxMb * 1024 * 1024 ? `This file is too large. The limit is ${props.maxMb} MB.` : value.size === 0 ? 'This file is empty. Choose another file.' : ''
  // Keep a valid selection when an invalid replacement is rejected.
  if (input.value) input.value.value = ''
  emit('error', error.value)
  if (error.value) return
  file.value = value
  emit('selected', value)
}
function drop(event: DragEvent) { dragging.value = false; choose(event.dataTransfer?.files[0], event.dataTransfer?.files.length) }
function clear() { file.value = null; error.value = ''; emit('error', ''); input.value?.focus() }
</script>

<template>
  <div class="file-upload" :class="{dragging, selected: !!file, disabled}" @dragover.prevent="!disabled && (dragging = true)" @dragleave.prevent="dragging = false" @drop.prevent="drop">
    <label :for="id" class="block text-sm font-semibold">{{ label }}<span v-if="required" class="ml-1 text-xs font-normal text-muted-foreground">(required)</span></label>
    <div class="upload-body"><span class="upload-icon" aria-hidden="true"><FileText v-if="file" class="size-6" /><UploadCloud v-else class="size-6" /></span><div class="min-w-0 flex-1"><p class="break-words text-sm font-medium">{{ file ? file.name : 'Drop your file here' }}</p><p class="mt-1 text-xs leading-5 text-muted-foreground">{{ file ? `${fileSize} · Ready to attach` : `${formats} · Up to ${maxMb} MB` }}</p><p v-if="file" class="mt-1 flex items-center gap-1 text-xs font-medium text-teal"><Check class="size-3.5" />Selected on this device</p></div></div>
    <div class="flex flex-wrap items-center gap-2"><div class="upload-picker"><span class="upload-browse"><UploadCloud class="size-4" />{{ file ? 'Change file' : 'Browse files' }}</span><input :id="id" ref="input" type="file" :accept="accept" :disabled="disabled" :aria-label="`${file ? 'Change' : 'Choose'} ${label.toLowerCase()}`" :aria-describedby="`${id}-help${error ? ` ${id}-error` : ''}`" :aria-invalid="!!error" @change="choose(($event.target as HTMLInputElement).files?.[0])" /></div><Button v-if="file" type="button" variant="ghost" size="sm" :disabled="disabled" :aria-label="'Remove selected '+label.toLowerCase()" @click="clear"><X class="size-4" />Remove</Button><span v-if="file" class="text-xs text-muted-foreground">Not uploaded until you submit.</span></div>
    <p :id="`${id}-help`" class="mt-3 text-xs leading-5 text-muted-foreground">{{ hint || 'Your selected file stays with this draft while you navigate. After a browser reload, select it again.' }}</p>
    <p v-if="error" :id="`${id}-error`" role="alert" class="mt-3 text-sm font-medium text-destructive">{{ error }}</p>
  </div>
</template>

<style scoped>
.file-upload{padding:20px;border:1.5px dashed var(--border);border-radius:14px;background:var(--background);transition:background .2s,border-color .2s}.file-upload.dragging{background:var(--accent);border-color:var(--primary)}.file-upload.selected{border-style:solid;border-color:color-mix(in srgb,var(--teal) 45%,var(--border));background:var(--card)}.file-upload.disabled{opacity:.65}.upload-body{display:flex;align-items:center;gap:14px;margin:18px 0}.upload-icon{display:grid;place-items:center;flex-shrink:0;width:48px;height:48px;border:1px solid var(--border);border-radius:12px;color:var(--primary);background:var(--card)}.selected .upload-icon{color:var(--teal)}.upload-picker{position:relative;display:inline-flex;border-radius:9px;overflow:hidden}.upload-picker:focus-within{outline:2px solid var(--primary);outline-offset:3px}.upload-browse{display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:10px 15px;background:var(--primary);color:var(--primary-foreground);font-size:13px;font-weight:600}.upload-picker input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}.upload-picker:has(input:disabled){opacity:.6}.upload-picker input:disabled{cursor:wait}
</style>

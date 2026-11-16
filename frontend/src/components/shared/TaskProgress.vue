<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'
import { Check, CheckCircle2, Loader2 } from '@lucide/vue'
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import NaviAvatar from './NaviAvatar.vue'
const props = withDefaults(defineProps<{ active: boolean; title: string; detail?: string; step?: number; steps?: string[]; complete?: boolean }>(), { detail: 'Keep this page open. Your result will appear here when it is ready.', step: 0, steps: () => [], complete: false })
const elapsed = ref(0)
let timer: ReturnType<typeof setInterval> | undefined
watch(() => props.active, active => { clearInterval(timer); if (active) { elapsed.value = 0; timer = setInterval(() => elapsed.value++, 1000) } }, { immediate: true })
onBeforeUnmount(() => clearInterval(timer))
</script>
<template>
  <Dialog :open="active">
    <DialogContent class="task-progress z-[91] gap-0 overflow-hidden p-0 sm:max-w-lg" overlay-class="z-[90] bg-[#0d1422]/65 backdrop-blur-md" :show-close-button="false" @interact-outside.prevent @escape-key-down.prevent>
      <div class="task-progress-art" aria-hidden="true"><span class="task-orbit" :class="{complete}" /><NaviAvatar :size="132" :state="complete ? 'idle' : 'thinking'" /><span class="task-status"><CheckCircle2 v-if="complete" class="size-4" /><Loader2 v-else class="size-4 animate-spin" />{{ complete ? 'Ready' : 'Working on it' }}</span></div>
      <div class="p-6 sm:p-8">
        <div role="status" aria-live="polite" aria-atomic="true"><DialogTitle class="font-heading text-xl leading-snug sm:text-2xl">{{ title }}</DialogTitle><DialogDescription class="mt-3 text-sm leading-6 text-muted-foreground">{{ detail }}</DialogDescription></div>
        <div class="task-progress-track mt-6" :class="{complete}" role="progressbar" :aria-label="title" :aria-valuenow="complete ? 100 : undefined" :aria-valuetext="complete ? 'Complete' : 'In progress; exact completion percentage is unavailable'"><span /></div>
        <ol v-if="steps.length" class="mt-5 grid gap-3 text-sm"><li v-for="(label,index) in steps" :key="label" class="flex items-center gap-3" :class="index <= step || complete ? 'font-medium text-foreground' : 'text-muted-foreground'"><span class="grid size-6 shrink-0 place-items-center rounded-full text-xs" :class="index < step || complete ? 'bg-teal-soft text-teal' : index === step ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"><Check v-if="index < step || complete" class="size-3.5" /><span v-else>{{ index + 1 }}</span></span>{{ label }}</li></ol>
        <p v-if="elapsed >= 30 && !complete" class="mt-5 rounded-lg bg-warning-soft p-3 text-xs leading-5 text-warning">This is taking a little longer. Your request is still running; there is no need to submit it again.</p>
      </div>
    </DialogContent>
  </Dialog>
</template>
<style scoped>
.task-progress{border:1px solid var(--border);box-shadow:0 24px 100px #0005;max-height:calc(100dvh - 32px);overflow-y:auto}
.task-progress-art{display:flex;position:relative;isolation:isolate;align-items:center;justify-content:center;min-height:174px;background:var(--accent);border-bottom:1px solid var(--border)}
.task-orbit{position:absolute;z-index:-1;width:140px;height:140px;border:1px solid var(--primary);border-radius:50%;opacity:.3;animation:task-breathe 2.4s ease-in-out infinite}.task-orbit.complete{animation:none;border-color:var(--teal)}
.task-status{position:absolute;bottom:14px;right:18px;display:flex;align-items:center;gap:7px;padding:7px 10px;border-radius:20px;background:var(--card);color:var(--primary);font-size:11px;font-weight:600}
.task-progress-track{height:6px;overflow:hidden;border-radius:6px;background:var(--accent)}.task-progress-track span{display:block;width:35%;height:100%;border-radius:inherit;background:var(--primary);animation:task-buffer 1.6s ease-in-out infinite}.task-progress-track.complete span{width:100%;background:var(--teal);animation:none}
@keyframes task-buffer{from{transform:translateX(-110%)}to{transform:translateX(390%)}}@keyframes task-breathe{50%{transform:scale(1.13);opacity:.15}}
@media(prefers-reduced-motion:reduce){.task-progress-track span{animation:none;width:100%;opacity:.55}.task-orbit{animation:none}}
</style>

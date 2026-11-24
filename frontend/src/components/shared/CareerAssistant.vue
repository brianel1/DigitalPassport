<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowUpRight, MessageCircle, RotateCcw, Send, Languages, Volume2, VolumeX, Pause, Play } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetTitle, SheetDescription, SheetTrigger } from '@/components/ui/sheet'
import { Textarea } from '@/components/ui/textarea'
import AssistantText from './AssistantText.vue'
import { useConfirm } from '@/composables/useConfirm'
import NaviAvatar from './NaviAvatar.vue'
import { useAssistant } from '@/composables/useAssistant'
import { useAuth } from '@/composables/useAuth'
import {
  ApiError,
  createAssistantSession,
  deleteAssistantSession,
  getAssistantSession,
  sendAssistantMessage,
} from '@/lib/api'
import type { AssistantSession } from '@/lib/api'

const { isOpen, request, motionEnabled: animated } = useAssistant()
const { isAdmin, user } = useAuth()
const { confirmAction } = useConfirm()
const viewport = ref(window.innerWidth)
const preferredWidth = ref(Number(localStorage.getItem('navi-width')) || 460)
const desktop = computed(() => viewport.value >= 1024)
const width = computed(() =>
  desktop.value
    ? Math.max(340, Math.min(preferredWidth.value, Math.floor(viewport.value / 2)))
    : Math.min(viewport.value, 460),
)
const resizing = ref(false)
function resizeWindow() {
  viewport.value = window.innerWidth
}
function resizeStart(event: PointerEvent) {
  resizing.value = true
  ;(event.currentTarget as HTMLElement).setPointerCapture(event.pointerId)
}
function resizeMove(event: PointerEvent) {
  if (resizing.value) preferredWidth.value = Math.max(340, Math.min(viewport.value - event.clientX, viewport.value / 2))
}
function resizeEnd() {
  resizing.value = false
  localStorage.setItem('navi-width', String(width.value))
}
function resizeKey(event: KeyboardEvent) {
  if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
    event.preventDefault()
    preferredWidth.value = Math.max(
      340,
      Math.min(width.value + (event.key === 'ArrowLeft' ? 24 : -24), viewport.value / 2),
    )
    resizeEnd()
  }
}
onMounted(() => window.addEventListener('resize', resizeWindow))
const active = ref<AssistantSession | null>(null)
const language = ref<'en' | 'ms' | null>(null)
const malay = computed(() => language.value === 'ms')
const storageKey = computed(() => 'navi-chat-' + user.value?.id)
const draft = ref('')
const pendingText = ref('')
const requestedCvId = ref<number | undefined>()
const busy = ref(false)
const loading = ref(false)
const error = ref('')
const log = ref<HTMLElement>()
const speaking = ref(false)
const canSpeak = typeof window !== 'undefined' && 'speechSynthesis' in window
let generation = 0
const state = computed(() => (busy.value ? 'thinking' : speaking.value ? 'replying' : 'idle'))
const prompts = computed(() =>
  isAdmin.value
    ? malay.value
      ? ['Macam mana nak semak bukti pelajar?', 'Apa perlu disemak sebelum terbitkan penilaian?']
      : ['How should I review student evidence?', 'What should I check before publishing an assessment?']
    : malay.value
      ? ['Apa tu CV?', 'Belum ada pengalaman kerja, nak tulis apa?', 'Macam mana nak cari internship?']
      : ['What is a CV?', 'What if I have no work experience?', 'How do I find an internship?'],
)
watch(request, (value) => {
  if (value && !busy.value) {
    draft.value = value.prompt
    requestedCvId.value = value.cvId
    error.value = ''
  }
})
watch(isOpen, async (value) => {
  if (!value) {
    stopSpeech()
    return
  }
  if (active.value || language.value) return
  loading.value = true
  const current = generation
  try {
    const id = Number(sessionStorage.getItem(storageKey.value))
    if (id) {
      const saved = await getAssistantSession(id)
      if (current === generation && saved.language) {
        active.value = saved
        language.value = saved.language
      }
    }
  } catch {
    sessionStorage.removeItem(storageKey.value)
  } finally {
    loading.value = false
  }
  await scrollEnd()
})
watch(
  () => user.value?.id,
  () => {
    generation++
    active.value = null
    language.value = null
    requestedCvId.value = undefined
    draft.value = ''
    pendingText.value = ''
    isOpen.value = false
    request.value = null
    stopSpeech()
  },
)
async function selectLanguage(value: 'en' | 'ms') {
  language.value = value
  await nextTick()
  document.getElementById('navi-message')?.focus()
}
function message(cause: unknown) {
  return cause instanceof ApiError
    ? cause.message
    : 'Navi could not connect. Your message is still here; please try again.'
}
async function scrollEnd() {
  await nextTick()
  log.value?.scrollTo({ top: log.value.scrollHeight, behavior: 'auto' })
}
function sendOnEnter(event: KeyboardEvent) {
  if (event.shiftKey || event.isComposing) return
  event.preventDefault()
  send()
}

async function send() {
  if (busy.value || loading.value || !language.value || !draft.value.trim()) return
  busy.value = true
  error.value = ''
  pendingText.value = draft.value.trim()
  stopSpeech()
  await scrollEnd()
  const current = generation
  try {
    const session = active.value ?? (await createAssistantSession('coach', undefined, language.value))
    if (current !== generation) return
    active.value = session
    sessionStorage.setItem(storageKey.value, String(session.id))
    const response = await sendAssistantMessage(session.id, pendingText.value, requestedCvId.value)
    if (current !== generation) return
    active.value = response
    requestedCvId.value = undefined
    draft.value = ''
  } catch (cause) {
    if (current === generation) error.value = message(cause)
  } finally {
    busy.value = false
    pendingText.value = ''
    await scrollEnd()
  }
}
async function newChat() {
  if (busy.value) return
  if (
    active.value &&
    !(await confirmAction({
      title: malay.value ? 'Mula chat baharu?' : 'Start a new chat?',
      description: malay.value
        ? 'Chat ini akan dipadam. Pilih bahasa untuk mulakan semula.'
        : 'This chat will be cleared. Choose a language to start again.',
      confirmLabel: malay.value ? 'Mula semula' : 'Start fresh',
      destructive: true,
    }))
  )
    return
  try {
    if (active.value) await deleteAssistantSession(active.value.id)
    active.value = null
    language.value = null
    requestedCvId.value = undefined
    draft.value = ''
    error.value = ''
    sessionStorage.removeItem(storageKey.value)
    stopSpeech()
  } catch (cause) {
    error.value = message(cause)
  }
}
function stopSpeech() {
  if (canSpeak) window.speechSynthesis.cancel()
  speaking.value = false
}
function readAloud(text: string) {
  if (speaking.value) {
    stopSpeech()
    return
  }
  const utterance = new SpeechSynthesisUtterance(text)
  utterance.lang = malay.value ? 'ms-MY' : 'en-GB'
  utterance.onend = utterance.onerror = () => {
    speaking.value = false
  }
  speaking.value = true
  window.speechSynthesis.speak(utterance)
}
onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeWindow)
  generation++
  isOpen.value = false
  stopSpeech()
})
</script>

<template>
  <Sheet v-model:open="isOpen">
    <SheetTrigger as-child
      ><button class="navi-launcher" aria-label="Ask Navi, your AI career assistant"
        ><NaviAvatar :size="64" :state="state" :animated="animated" /><span
          ><strong>Ask Navi</strong><small>CV & skills guide</small></span
        ><MessageCircle class="size-4" /></button
    ></SheetTrigger>
    <SheetContent
      side="right"
      class="gap-0 p-0"
      :style="{ width: width + 'px', maxWidth: desktop ? '50vw' : '100vw', height: '100dvh' }"
    >
      <div
        v-if="desktop"
        role="separator"
        tabindex="0"
        aria-label="Resize Navi panel. Use left and right arrow keys."
        aria-orientation="vertical"
        :aria-valuenow="Math.round(width)"
        :aria-valuemin="340"
        :aria-valuemax="Math.floor(viewport / 2)"
        class="navi-resize"
        :class="{ resizing }"
        @pointerdown.prevent="resizeStart"
        @pointermove="resizeMove"
        @pointerup="resizeEnd"
        @pointercancel="resizeEnd"
        @lostpointercapture="resizeEnd"
        @keydown="resizeKey"
        ><span></span
      ></div>
      <header class="flex shrink-0 items-center gap-3 border-b px-5 py-4 pr-14"
        ><NaviAvatar :size="62" :state="state" :animated="animated" /><div
          ><SheetTitle class="font-heading text-xl">Navi</SheetTitle
          ><SheetDescription class="text-xs">{{
            malay ? 'Teman kerjaya anda' : 'Your career companion'
          }}</SheetDescription></div
        ></header
      >
      <div v-if="language" class="flex shrink-0 items-center justify-between gap-3 border-b px-5 py-2.5"
        ><span class="flex items-center gap-2 text-xs text-muted-foreground"
          ><Languages class="size-4" />{{ malay ? 'Bahasa Melayu' : 'English' }}</span
        ><Button variant="ghost" size="sm" :disabled="busy || loading" @click="newChat"
          ><RotateCcw class="size-3.5" />{{ malay ? 'Chat baharu' : 'New chat' }}</Button
        ></div
      >
      <div v-if="!language" class="flex min-h-0 flex-1 flex-col justify-center overflow-auto p-7"
        ><NaviAvatar :size="130" class="mb-4" :animated="animated" /><p class="eyebrow">LET’S TALK · JOM SEMBANG</p
        ><h2 class="mt-3 font-heading text-2xl"
          >Choose your language.<br /><span class="text-muted-foreground">Pilih bahasa anda.</span></h2
        ><p class="mt-4 text-sm leading-6 text-muted-foreground"
          >CVs, skills or your next career step.<br />Santai je, tanya apa yang anda perlukan.</p
        ><div class="mt-7 grid gap-3"
          ><Button size="lg" :disabled="loading" @click="selectLanguage('en')"
            >Continue in English <ArrowUpRight class="size-4" /></Button
          ><Button variant="outline" size="lg" :disabled="loading" @click="selectLanguage('ms')"
            >Teruskan dalam Bahasa Melayu <ArrowUpRight class="size-4" /></Button></div
        ><p v-if="error" role="alert" class="mt-3 text-sm text-destructive">{{ error }}</p></div
      >
      <div
        v-if="language"
        ref="log"
        class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5"
        role="log"
        aria-label="Conversation"
        aria-live="polite"
        :aria-busy="busy"
      >
        <div v-if="!active?.messages.length && !pendingText" class="py-3"
          ><h2 class="font-heading text-2xl">{{ malay ? 'Hai! Nak mula dari mana?' : 'Hi! Where shall we start?' }}</h2
          ><p class="mt-3 text-sm leading-7 text-muted-foreground">{{
            malay
              ? 'Tanya tentang CV, kemahiran, belajar atau persediaan kerja. Tak perlu ayat formal.'
              : 'Ask about your CV, skills, learning or preparing for work. No formal wording needed.'
          }}</p
          ><div class="mt-5 space-y-2"
            ><button
              v-for="prompt in prompts"
              :key="prompt"
              class="flex min-h-11 w-full items-center justify-between gap-3 rounded-xl border px-3 py-3 text-left text-sm hover:border-primary hover:bg-muted"
              @click="draft = prompt"
              >{{ prompt }}<ArrowUpRight class="size-3.5 shrink-0" /></button></div
        ></div>
        <article
          v-for="(entry, index) in active?.messages ?? []"
          :key="index"
          :class="entry.role === 'user' ? 'ml-8 rounded-2xl rounded-tr-sm bg-accent p-4' : 'mr-2'"
        >
          <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">{{
            entry.role === 'user' ? (malay ? 'Anda' : 'You') : 'Navi'
          }}</p
          ><AssistantText v-if="entry.role === 'assistant'" :text="entry.content" /><p
            v-else
            class="whitespace-pre-wrap break-words text-sm leading-7"
            >{{ entry.content }}</p
          >
          <div v-if="entry.actions?.length" class="mt-3 flex flex-wrap gap-2"
            ><Button
              v-for="action in entry.actions"
              :key="action.path"
              as-child
              size="sm"
              class="min-h-11 max-w-full whitespace-normal text-left"
              ><RouterLink :to="action.path" @click="isOpen = false"
                >{{ action.label }}<ArrowUpRight class="size-4 shrink-0" /></RouterLink></Button
          ></div>
          <div v-if="entry.role === 'assistant' && canSpeak" class="mt-2 flex justify-end"
            ><Button
              variant="ghost"
              size="icon-sm"
              :aria-label="speaking ? 'Stop reading aloud' : 'Read reply aloud'"
              @click="readAloud(entry.content)"
              ><VolumeX v-if="speaking" class="size-3.5" /><Volume2 v-else class="size-3.5" /></Button
          ></div>
        </article>
        <div v-if="pendingText" class="ml-8 rounded-2xl bg-accent p-4 text-sm leading-7">{{ pendingText }}</div>
        <div v-if="busy" role="status" class="flex items-center gap-2 text-sm text-muted-foreground"
          ><NaviAvatar :size="60" state="thinking" :animated="animated" /><div class="flex-1"
            ><span>{{ malay ? 'Navi sedang menyediakan jawapan…' : 'Navi is preparing a reply…' }}</span
            ><div class="navi-buffer mt-3" role="progressbar" aria-label="Preparing Navi reply"><i></i></div
            ><p class="mt-2 text-xs">{{
              malay
                ? 'Sekejap ya. Anda boleh baca mesej di atas sementara menunggu.'
                : 'One moment. You can keep reading above while you wait.'
            }}</p></div
          ></div
        >
      </div>
      <form
        v-if="language"
        class="shrink-0 space-y-3 border-t bg-card p-4 pb-[max(1rem,env(safe-area-inset-bottom))]"
        @submit.prevent="send"
      >
        <p v-if="error" role="alert" class="text-xs leading-5 text-destructive">{{ error }}</p
        ><label for="navi-message" class="sr-only">Message Navi</label
        ><div class="flex items-end gap-2"
          ><Textarea
            id="navi-message"
            v-model="draft"
            :disabled="busy || loading"
            :placeholder="malay ? 'Tanya Navi…' : 'Ask Navi…'"
            rows="2"
            maxlength="1800"
            class="max-h-36 resize-none"
            @keydown.enter="sendOnEnter" /><Button type="submit" size="icon" :disabled="busy || loading || !draft.trim()" aria-label="Send message"
            ><Send class="size-4" /></Button
        ></div>
        <div class="flex items-center justify-between gap-3"
          ><p class="text-[10px] leading-4 text-muted-foreground">{{
            malay
              ? 'AI boleh tersilap. Semak cadangan sebelum digunakan.'
              : 'AI can make mistakes. Check advice before using it.'
          }}</p
          ><button
            type="button"
            class="flex min-h-8 shrink-0 items-center gap-1 text-[10px] text-muted-foreground"
            :aria-label="animated ? 'Pause character animation' : 'Animate character'"
            @click="animated = !animated"
            ><Pause v-if="animated" class="size-3" /><Play v-else class="size-3" />Motion</button
          ></div
        >
      </form>
    </SheetContent>
  </Sheet>
</template>
<style scoped>
.navi-resize {
  position: absolute;
  left: -6px;
  top: 0;
  bottom: 0;
  width: 14px;
  z-index: 3;
  cursor: ew-resize;
  touch-action: none;
  display: grid;
  place-items: center;
}
.navi-resize span {
  width: 4px;
  height: 58px;
  border-radius: 8px;
  background: var(--border);
}
.navi-resize:hover span,
.navi-resize.resizing span,
.navi-resize:focus-visible span {
  background: var(--primary);
}
.navi-resize:focus-visible {
  outline: 2px solid var(--primary);
  outline-offset: -2px;
}
.navi-buffer {
  height: 4px;
  overflow: hidden;
  border-radius: 8px;
  background: var(--accent);
}
.navi-buffer i {
  display: block;
  width: 35%;
  height: 100%;
  background: var(--primary);
  animation: navi-buffer 1.8s ease-in-out infinite;
}
@keyframes navi-buffer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(390%);
  }
}
@media (prefers-reduced-motion: reduce) {
  .navi-buffer i {
    animation: none;
    width: 100%;
    opacity: 0.6;
  }
}

.navi-launcher {
  position: fixed;
  right: 22px;
  bottom: 20px;
  z-index: 40;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 3px 17px 3px 3px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: var(--card);
  box-shadow: 0 8px 32px #0003;
  color: var(--foreground);
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}
.navi-launcher:hover {
  border-color: var(--primary);
  box-shadow: 0 10px 35px #0004;
}
.navi-launcher strong {
  display: block;
  text-align: left;
  font-family: var(--font-heading);
  font-size: 15px;
  font-weight: 500;
}
.navi-launcher small {
  display: block;
  font-size: 10px;
  color: var(--muted-foreground);
}
@media (max-width: 640px) {
  .navi-launcher {
    right: 14px;
    bottom: 14px;
    padding: 2px 10px 2px 0;
    gap: 0;
  }
  .navi-launcher small,
  .navi-launcher > svg {
    display: none;
  }
  .navi-launcher strong {
    font-size: 12px;
  }
  .navi-launcher :deep(.navi-avatar) {
    width: 50px !important;
    height: 50px !important;
  }
}
</style>

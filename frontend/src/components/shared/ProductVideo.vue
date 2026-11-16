<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, nextTick } from 'vue'
import { Pause, Play } from '@lucide/vue'

const video = ref<HTMLVideoElement>()
const playing = ref(false)
const enabled = ref(false)
const compact = ref(false)
let userPaused = false
let observer: IntersectionObserver | undefined
let motionQuery: MediaQueryList | undefined
const onMotionChange = (event: MediaQueryListEvent) => { if (event.matches) { userPaused = true; video.value?.pause() } }
const onVisibility = () => { if (document.hidden) video.value?.pause() }

async function toggle() {
  if (playing.value) { userPaused = true; video.value?.pause(); return }
  userPaused = false
  enabled.value = true
  await nextTick()
  try { await video.value?.play() } catch { playing.value = false }
}
onMounted(async () => {
  compact.value = window.matchMedia('(max-width: 640px)').matches
  motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
  const saveData = (navigator as Navigator & { connection?: { saveData?: boolean } }).connection?.saveData
  enabled.value = !motionQuery.matches && !saveData
  userPaused = !enabled.value
  motionQuery.addEventListener('change', onMotionChange)
  document.addEventListener('visibilitychange', onVisibility)
  await nextTick()
  observer = new IntersectionObserver(entries => {
    if (!entries[0]?.isIntersecting) video.value?.pause()
    else if (!userPaused && enabled.value) video.value?.play().catch(() => {})
  }, { threshold: .25 })
  if (video.value) observer.observe(video.value)
})
onBeforeUnmount(() => { observer?.disconnect(); motionQuery?.removeEventListener('change', onMotionChange); document.removeEventListener('visibilitychange', onVisibility) })
</script>
<template>
  <figure class="overflow-hidden rounded-2xl border border-[#3c5068] bg-[#142b45]">
    <video ref="video" class="aspect-[32/25] w-full object-contain" :poster="'/media/product-walkthrough-poster.webp'" muted loop playsinline preload="none" aria-label="Recorded demonstration with an example student: submit evidence, verify it, search jobs and prepare a CV. Waiting time has been shortened." @play="playing = true" @pause="playing = false" @error="playing = false">
      <template v-if="enabled"><source :src="compact ? '/media/product-walkthrough-small.webm' : '/media/product-walkthrough.webm'" type="video/webm" /><source :src="compact ? '/media/product-walkthrough-small.mp4' : '/media/product-walkthrough.mp4'" type="video/mp4" /></template>
    </video>
    <figcaption class="flex items-center justify-between gap-3 border-t border-[#3c5068] px-5 py-3 text-xs text-[#bac9db]"><span>Recorded product walkthrough <span class="block text-[#8cdbca]">Example student · waits shortened</span></span><button class="flex min-h-11 items-center gap-2 rounded-md px-2 text-white hover:bg-[#263f5c]" :aria-label="playing ? 'Pause product walkthrough' : 'Play product walkthrough'" @click="toggle"><Pause v-if="playing" class="size-3.5" /><Play v-else class="size-3.5" />{{ playing ? 'Pause' : 'Play' }}</button></figcaption>
  </figure>
</template>

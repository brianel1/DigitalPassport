<script setup lang="ts">
import { computed } from 'vue'
import { useAssistant } from '@/composables/useAssistant'
const props = withDefaults(defineProps<{ state?: 'idle' | 'thinking' | 'replying'; size?: number; animated?: boolean }>(), { state: 'idle', size: 100, animated: undefined })
const { motionEnabled } = useAssistant()
const isAnimated = computed(() => props.animated ?? motionEnabled.value)
</script>
<template>
  <span class="navi-avatar" :class="[state, { animated: isAnimated }]" :style="{ width: size + 'px', height: size + 'px' }" aria-hidden="true">
    <span class="navi-body"><img src="/media/navi-bot.webp" width="480" height="480" alt="" /><svg viewBox="0 0 100 100" class="navi-face"><g class="navi-eyes"><rect x="39" y="29" width="7" height="10" rx="3.5" /><rect x="55" y="29" width="7" height="10" rx="3.5" /></g><path class="navi-mouth" d="M46 42 Q50.5 46 55 42" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><g v-if="state === 'thinking'" class="navi-thinking"><circle cx="43" cy="43" r="1.2"/><circle cx="50.5" cy="43" r="1.2"/><circle cx="58" cy="43" r="1.2"/></g></svg></span>
  </span>
</template>
<style scoped>
.navi-avatar{display:inline-block;position:relative;flex-shrink:0}.navi-body{display:block;width:100%;height:100%;transform-origin:50% 65%}.navi-body img{display:block;width:100%;height:100%;object-fit:contain}.navi-face{position:absolute;inset:0;width:100%;height:100%;color:#72dfc4;fill:currentColor}.navi-eyes{transform-box:fill-box;transform-origin:center}.animated .navi-body{animation:navi-float 5s ease-in-out infinite}.animated .navi-eyes{animation:navi-blink 6s infinite}.thinking.animated .navi-body{animation:navi-think 2s ease-in-out infinite}.thinking .navi-mouth{display:none}.thinking.animated .navi-thinking{animation:navi-dots 1s ease-in-out infinite}.replying.animated .navi-mouth{transform-box:fill-box;transform-origin:center;animation:navi-speak .7s ease-in-out infinite}.navi-avatar:hover .navi-body{transform:rotate(-5deg)}
@keyframes navi-float{0%,100%{transform:translateY(0) rotate(-2deg)}50%{transform:translateY(-5px) rotate(2deg)}}@keyframes navi-blink{0%,42%,46%,100%{transform:scaleY(1)}44%{transform:scaleY(.1)}}@keyframes navi-think{0%,100%{transform:rotate(-4deg)}50%{transform:rotate(4deg) translateY(-3px)}}@keyframes navi-dots{0%,100%{opacity:.3}50%{opacity:1}}@keyframes navi-speak{0%,100%{transform:scaleY(.5)}50%{transform:scaleY(1.4)}}
@media(prefers-reduced-motion:reduce){.navi-avatar *{animation:none!important;transition:none!important}}
</style>

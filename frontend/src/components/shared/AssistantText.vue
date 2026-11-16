<script setup lang="ts">
import { computed } from 'vue'
const props=defineProps<{text:string}>()
// Deliberately render text nodes, never HTML returned by the model.
function inline(text:string) {
  return text.split(/(\*\*[^*\n]+\*\*|__[^_\n]+__|`[^`\n]+`|\*[^*\n]+\*)/g).filter(Boolean).map(value=>({
    tag:value.startsWith('**')||value.startsWith('__')?'strong':value.startsWith('`')?'code':value.startsWith('*')?'em':'span',
    value:value.startsWith('**')||value.startsWith('__')?value.slice(2,-2):value.startsWith('`')||value.startsWith('*')?value.slice(1,-1):value,
  }))
}
const blocks=computed(()=>props.text.split('\n').map(line=>{
  const bullet=/^\s*(?:[-*]|\d+\.)\s+/.test(line)
  const heading=/^#{1,6}\s+/.test(line)
  return {bullet,heading,parts:inline(line.replace(/^\s*(?:#{1,6}|[-*]|\d+\.)\s+/,''))}
}))
</script>
<template><div class="assistant-text space-y-2 break-words text-sm leading-7"><div v-for="(block,index) in blocks" :key="index" :class="{'flex gap-2':block.bullet,'font-semibold':block.heading,'min-h-2':!block.parts.length}"><span v-if="block.bullet" aria-hidden="true" class="text-primary">•</span><p><component :is="part.tag" v-for="(part,i) in block.parts" :key="i">{{ part.value }}</component></p></div></div></template>
<style scoped>.assistant-text strong{font-weight:700;color:var(--foreground)}.assistant-text code{font-size:.9em;border-radius:4px;background:var(--accent);padding:2px 4px}</style>

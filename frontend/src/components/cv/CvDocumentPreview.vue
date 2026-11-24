<script setup lang="ts">
import { computed } from 'vue'
import type { CvContent } from '@/lib/api'
import { buildCvDocument } from '@/lib/cvDocument'
const props = defineProps<{ content: CvContent }>()
const doc = computed(() => buildCvDocument(props.content))
</script>
<template>
  <article class="cv-document" aria-label="CV document preview">
    <header>
      <h2>{{ doc.name || 'Your name' }}</h2>
      <p v-if="doc.headline" class="headline">{{ doc.headline }}</p>
      <p v-if="doc.contact" class="contact">{{ doc.contact }}</p>
    </header>
    <section v-for="section in doc.sections" :key="section.title">
      <h3>{{ section.title }}</h3>
      <p v-if="section.paragraph">{{ section.paragraph }}</p>
      <div v-for="(entry, index) in section.entries" :key="index" class="entry">
        <div class="row"><strong>{{ entry.title }}</strong><span v-if="entry.date" class="date">{{ entry.date }}</span></div>
        <p v-if="entry.subtitle" class="subtitle">{{ entry.subtitle }}</p>
        <ul v-if="entry.bullets.length"><li v-for="(bullet, i) in entry.bullets" :key="i">{{ bullet }}</li></ul>
      </div>
      <ul v-if="section.items.length"><li v-for="(item, i) in section.items" :key="i">{{ item }}</li></ul>
    </section>
  </article>
</template>
<style scoped>
/* Same proportions as the A4 export: 0.6in side margins, Arial, 10pt body. */
.cv-document { min-height: 780px; padding: 40px 44px; background: #fff; color: #111; box-shadow: 0 10px 35px #0002; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.3; overflow-wrap: anywhere; }
.cv-document header { text-align: center; }
.cv-document h2 { font-family: Arial, Helvetica, sans-serif; font-size: 24px; font-weight: 700; letter-spacing: .02em; text-transform: uppercase; }
.headline { font-size: 12.5px; margin-top: 4px; }
.contact { color: #333; font-size: 11px; margin-top: 4px; }
.cv-document section { margin-top: 14px; }
.cv-document h3 { font-family: Arial, Helvetica, sans-serif; font-size: 12.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid #111; padding-bottom: 2px; margin-bottom: 6px; }
.entry { margin-top: 6px; }
.row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
.date { color: #333; font-size: 11px; white-space: nowrap; }
.subtitle { color: #333; font-size: 11.5px; font-style: italic; margin-top: 1px; }
.cv-document ul { list-style: disc; margin-top: 2px; padding-left: 19px; }
.cv-document li { margin-bottom: 2px; }
@media (max-width: 640px) { .cv-document { padding: 24px; min-height: 650px; } .cv-document h2 { font-size: 20px; } }
</style>

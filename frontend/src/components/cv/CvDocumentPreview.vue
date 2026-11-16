<script setup lang="ts">
import type { CvContent } from '@/lib/api'
defineProps<{ content: CvContent }>()
</script>
<template>
  <article class="cv-document" aria-label="CV document preview">
    <header><h2>{{ content.personal.name || 'Your name' }}</h2><p v-if="content.personal.headline" class="headline">{{ content.personal.headline }}</p><p class="contact">{{ [content.personal.email,content.personal.phone,content.personal.location,content.personal.website].filter(Boolean).join(' · ') }}</p></header>
    <section v-if="content.summary"><h3>Summary</h3><p>{{ content.summary }}</p></section>
    <section v-if="content.experience.length"><h3>Work experience</h3><div v-for="(entry,index) in content.experience" :key="index"><h4>{{ entry.role }}</h4><p class="meta">{{ [entry.company,entry.period].filter(Boolean).join(' · ') }}</p><p>{{ entry.description }}</p></div></section>
    <section v-if="content.projects.length"><h3>Project experience</h3><div v-for="(entry,index) in content.projects" :key="index"><h4>{{ entry.title }}</h4><p>{{ entry.description }}</p><p v-if="entry.technologies.length" class="meta">{{ entry.technologies.join(' · ') }}</p></div></section>
    <section v-if="content.skills.length"><h3>Skills</h3><p>{{ content.skills.join(' · ') }}</p></section>
    <section v-if="content.education.length"><h3>Education</h3><div v-for="(entry,index) in content.education" :key="index"><h4>{{ entry.qualification }}</h4><p class="meta">{{ [entry.institution,entry.period].filter(Boolean).join(' · ') }}</p><p v-if="entry.details" class="whitespace-pre-line">{{entry.details}}</p></div></section>
    <section v-if="content.certifications.length"><h3>Certifications</h3><p v-for="(entry,index) in content.certifications" :key="index">{{ [entry.title,entry.issuer,entry.issueDate].filter(Boolean).join(' · ') }}</p></section>
    <section v-if="content.assessments.length"><h3>Assessments</h3><p v-for="(entry,index) in content.assessments" :key="index">{{ entry.title }} · {{ entry.percentage }}% · {{ entry.completedAt }}</p></section>
    <section v-if="content.additional"><h3>Additional information</h3><p>{{ content.additional }}</p></section>
  </article>
</template>
<style scoped>
.cv-document{min-height:780px;padding:40px;background:#fff;color:#172132;box-shadow:0 10px 35px #0002;font-family:Arial,sans-serif;font-size:11px;line-height:1.6;overflow-wrap:anywhere}.cv-document header{padding-bottom:18px;border-bottom:2px solid #172132}.cv-document h2{font-family:Arial,sans-serif;font-size:25px;font-weight:700;letter-spacing:.01em}.headline{font-weight:600;margin-top:5px}.contact,.meta{color:#526070;font-size:10px}.contact{margin-top:6px}.cv-document section{margin-top:22px}.cv-document h3{font-family:Arial,sans-serif;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;border-bottom:1px solid #b5bfca;padding-bottom:5px;margin-bottom:8px}.cv-document h4{font-family:Arial,sans-serif;font-weight:700;margin-top:12px;font-size:11px}.cv-document p{white-space:pre-line;margin-top:4px}@media(max-width:640px){.cv-document{padding:25px;min-height:650px}.cv-document h2{font-size:21px}}
</style>

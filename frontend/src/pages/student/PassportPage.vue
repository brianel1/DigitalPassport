<script setup lang="ts">
import { computed } from 'vue'
import { Award, BookOpenCheck, CheckCircle2, Download, FolderKanban, IdCard, Sparkles } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuth } from '@/composables/useAuth'
import { usePortalData } from '@/composables/usePortalData'

const { user } = useAuth()
const { skills, projects, certifications, attempts } = usePortalData()
const verifiedSkills = computed(() => skills.value.filter((item) => item.status === 'verified'))
const verifiedProjects = computed(() => projects.value.filter((item) => item.status === 'verified'))
const verifiedCertifications = computed(() => certifications.value.filter((item) => item.status === 'verified'))
const passedAttempts = computed(() => attempts.value.filter((item) => item.passed))

function downloadPassport() {
  window.print()
}
</script>

<template>
  <div class="space-y-6">
    <div class="no-print flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="text-2xl font-semibold tracking-tight">Digital Skills Passport</h1><p class="mt-1 text-muted-foreground">A verified overview of your digital competencies and achievements.</p></div>
      <Button @click="downloadPassport"><Download class="size-4" /> Download passport</Button>
    </div>

    <article id="digital-passport" class="overflow-hidden rounded-2xl border border-border bg-card shadow-xl shadow-primary/5">
      <header class="relative overflow-hidden bg-gradient-to-br from-primary via-primary to-violet-700 p-8 text-primary-foreground sm:p-10">
        <div class="absolute -right-16 -top-24 size-72 rounded-full border border-white/15"></div>
        <div class="absolute -right-2 -top-12 size-52 rounded-full border border-white/15"></div>
        <div class="relative flex flex-col gap-8 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-5">
            <div class="flex size-20 items-center justify-center rounded-2xl bg-white/15 text-2xl font-semibold backdrop-blur">
              {{ user?.name?.split(' ').map((part) => part[0]).join('').slice(0, 2) }}
            </div>
            <div><p class="mb-1 text-sm font-medium text-primary-foreground/70">DIGITAL SKILLS PASSPORT</p><h2 class="text-3xl font-semibold">{{ user?.name }}</h2><p class="mt-1 text-primary-foreground/75">{{ user?.matricNo }} | {{ user?.programme }}</p></div>
          </div>
          <div class="flex items-center gap-2 self-start rounded-full bg-white/15 px-4 py-2 text-sm font-medium backdrop-blur"><CheckCircle2 class="size-4" /> Verified profile</div>
        </div>
      </header>

      <div class="grid gap-8 p-6 sm:p-10 lg:grid-cols-[1fr_1fr]">
        <section>
          <div class="mb-4 flex items-center gap-2"><Sparkles class="size-5 text-primary" /><h3 class="font-semibold">Verified skills</h3></div>
          <div class="space-y-3">
            <div v-for="skill in verifiedSkills" :key="skill.id" class="rounded-xl border border-border p-4">
              <div class="flex items-center justify-between gap-3"><strong class="text-sm">{{ skill.name }}</strong><Badge variant="secondary">{{ skill.proficiency }}</Badge></div>
              <p class="mt-2 text-xs leading-relaxed text-muted-foreground">{{ skill.description }}</p>
            </div>
          </div>
        </section>

        <section>
          <div class="mb-4 flex items-center gap-2"><FolderKanban class="size-5 text-primary" /><h3 class="font-semibold">Verified projects</h3></div>
          <div class="space-y-3">
            <div v-for="project in verifiedProjects" :key="project.id" class="rounded-xl border border-border p-4"><strong class="text-sm">{{ project.title }}</strong><p class="mt-2 text-xs leading-relaxed text-muted-foreground">{{ project.description }}</p><div class="mt-3 flex flex-wrap gap-1.5"><span v-for="technology in project.technologies" :key="technology" class="rounded bg-muted px-2 py-1 text-[11px]">{{ technology }}</span></div></div>
          </div>
        </section>

        <section>
          <div class="mb-4 flex items-center gap-2"><Award class="size-5 text-primary" /><h3 class="font-semibold">Certifications</h3></div>
          <Card v-for="certification in verifiedCertifications" :key="certification.id" class="mb-3 shadow-none"><CardHeader class="p-4"><CardTitle class="text-sm">{{ certification.title }}</CardTitle></CardHeader><CardContent class="px-4 pb-4 text-xs text-muted-foreground">{{ certification.issuingOrganization }} | {{ certification.issueDate }}</CardContent></Card>
        </section>

        <section>
          <div class="mb-4 flex items-center gap-2"><BookOpenCheck class="size-5 text-primary" /><h3 class="font-semibold">Assessment results</h3></div>
          <div class="space-y-3"><div v-for="attempt in passedAttempts" :key="attempt.id" class="flex items-center justify-between rounded-xl border border-border p-4"><div><strong class="text-sm">{{ attempt.assessmentTitle }}</strong><p class="mt-1 text-xs text-muted-foreground">Completed assessment</p></div><span class="text-xl font-semibold text-primary">{{ attempt.percentage }}%</span></div></div>
        </section>
      </div>

      <footer class="flex flex-col gap-3 border-t border-border bg-muted/30 px-6 py-5 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-10">
        <span class="flex items-center gap-2"><IdCard class="size-4" /> DigitalPassport | Universiti Teknikal Malaysia Melaka</span>
        <span>Generated {{ new Date().toLocaleDateString('en-MY', { dateStyle: 'long' }) }}</span>
      </footer>
    </article>
  </div>
</template>

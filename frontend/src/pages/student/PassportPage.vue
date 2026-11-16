<script setup lang="ts">
import ListPagination from '@/components/shared/ListPagination.vue'
import ProfileSharing from '@/components/shared/ProfileSharing.vue'
import TaskProgress from '@/components/shared/TaskProgress.vue'
import { usePagination } from '@/composables/usePagination'
import { computed, onMounted, ref } from 'vue'
import { Award, BookOpenCheck, CheckCircle2, Download, FolderKanban, IdCard, Loader2, Sparkles } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuth } from '@/composables/useAuth'
import { usePortalData } from '@/composables/usePortalData'
import { ApiError, downloadPassport as downloadPassportFile } from '@/lib/api'

const { user } = useAuth()
const { skills, projects, certifications, attempts, studentError, refreshStudentData } = usePortalData()

const loading = ref(true)
const exporting = ref(false)
const error = ref('')

onMounted(async () => {
  await refreshStudentData()
  loading.value = false
})

const verifiedSkills = computed(() => skills.value.filter((item) => item.status === 'verified'))
const verifiedProjects = computed(() => projects.value.filter((item) => item.status === 'verified'))
const verifiedCertifications = computed(() => certifications.value.filter((item) => item.status === 'verified' && !item.expired))
const passedAttempts = computed(() => {const best=new Map<number,typeof attempts.value[number]>();for(const item of attempts.value.filter(a=>a.passed)){const key=item.familyId??item.assessmentId; if(!best.has(key)||best.get(key)!.percentage<item.percentage)best.set(key,item)}return [...best.values()]})

const {page:pageSkills,items:pagedSkills}=usePagination(verifiedSkills,6)
const {page:pageProjects,items:pagedProjects}=usePagination(verifiedProjects,6)
const {page:pageCertifications,items:pagedCertifications}=usePagination(verifiedCertifications,6)
const {page:pageResults,items:pagedResults}=usePagination(passedAttempts,6)
async function downloadPassport() {
  exporting.value = true
  error.value = ''
  try {
    await downloadPassportFile()
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The skills profile could not be downloaded.'
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <div v-if="loading" class="flex items-center justify-center py-20">
    <Loader2 class="size-8 animate-spin text-primary" />
  </div>
  <div v-else class="space-y-6">
    <p v-if="studentError" class="rounded-xl bg-destructive/10 p-4 text-sm text-destructive">{{ studentError }} <button class="ml-2 underline" @click="refreshStudentData">Retry</button></p>
    <div class="no-print flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 class="page-heading">Skills Profile</h1><p class="mt-1 text-muted-foreground">Your verified skills, supporting work and completed assessments.</p></div>
      <div class="flex flex-wrap gap-2"><ProfileSharing /><Button :disabled="exporting" @click="downloadPassport"><Download class="size-4" /> {{ exporting ? 'Preparing...' : 'Download profile' }}</Button></div>
    </div>
    <p v-if="error" class="text-sm font-medium text-destructive">{{ error }}</p>

    <TaskProgress :active="exporting" title="Preparing your skills profile download…" />
    <article id="skills-profile" class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
      <header class="relative overflow-hidden bg-navy p-8 text-white sm:p-10">
        <div class="absolute -right-16 -top-24 size-72 rounded-full border border-white/15"></div>
        <div class="absolute -right-2 -top-12 size-52 rounded-full border border-white/15"></div>
        <div class="relative flex flex-col gap-8 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-5">
            <div class="flex size-20 items-center justify-center rounded-2xl bg-white/15 text-2xl font-semibold backdrop-blur">
              {{ user?.name?.split(' ').map((part) => part[0]).join('').slice(0, 2) }}
            </div>
            <div><p class="mb-1 text-sm font-medium text-white/70">SKILLS PROFILE</p><h2 class="text-3xl font-semibold">{{ user?.name }}</h2><p class="mt-1 text-white/75">{{ user?.matricNo }} | {{ user?.programme }}</p></div>
          </div>
          <div class="flex items-center gap-2 self-start rounded-full border px-4 py-2 text-sm font-semibold" :class="verifiedSkills.length || verifiedProjects.length || verifiedCertifications.length ? 'bg-teal-soft text-teal border-teal/30' : 'bg-warning-soft text-warning border-warning/30'"><CheckCircle2 class="size-4" /> {{ verifiedSkills.length || verifiedProjects.length || verifiedCertifications.length ? 'Includes verified records' : 'Awaiting verified records' }}</div>
        </div>
      </header>

      <div class="grid gap-8 p-6 sm:p-10 lg:grid-cols-[1fr_1fr]">
        <section>
          <div class="mb-4 flex items-center gap-2"><Sparkles class="size-5 text-primary" /><h3 class="font-semibold">Verified skills</h3></div>
          <div v-if="verifiedSkills.length === 0" class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">No verified skills yet.</div>
          <div class="space-y-3">
            <div v-for="skill in pagedSkills" :key="skill.id" class="rounded-xl border border-border p-4">
              <div class="flex items-center justify-between gap-3"><strong class="text-sm">{{ skill.name }}</strong><Badge variant="secondary">{{ skill.proficiency }}</Badge></div>
              <p class="mt-2 text-xs leading-relaxed text-muted-foreground">{{ skill.description }}</p><p v-if="skill.reviewerName" class="mt-2 text-xs text-muted-foreground">Verified by {{skill.reviewerName}} · {{new Date(skill.reviewedAt!).toLocaleDateString('en-MY')}}</p>
            </div>
          </div>
        <ListPagination v-if="verifiedSkills.length>6" class="no-print mt-4" v-model:page="pageSkills" :total="verifiedSkills.length" :page-size="6" label="skills" />
        </section>

        <section>
          <div class="mb-4 flex items-center gap-2"><FolderKanban class="size-5 text-primary" /><h3 class="font-semibold">Verified projects</h3></div>
          <div v-if="verifiedProjects.length === 0" class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">No verified projects yet.</div>
          <div class="space-y-3">
            <div v-for="project in pagedProjects" :key="project.id" class="rounded-xl border border-border p-4"><strong class="text-sm">{{ project.title }}</strong><p class="mt-2 text-xs leading-relaxed text-muted-foreground">{{ project.description }}</p><p v-if="project.reviewerName" class="mt-2 text-xs text-muted-foreground">Verified by {{project.reviewerName}} · {{new Date(project.reviewedAt!).toLocaleDateString('en-MY')}}</p><div class="mt-3 flex flex-wrap gap-1.5"><span v-for="technology in project.technologies" :key="technology" class="rounded bg-muted px-2 py-1 text-[11px]">{{ technology }}</span></div></div>
          </div>
        <ListPagination v-if="verifiedProjects.length>6" class="no-print mt-4" v-model:page="pageProjects" :total="verifiedProjects.length" :page-size="6" label="projects" />
        </section>

        <section>
          <div class="mb-4 flex items-center gap-2"><Award class="size-5 text-primary" /><h3 class="font-semibold">Certifications</h3></div>
          <div v-if="verifiedCertifications.length === 0" class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">No verified certifications yet.</div>
          <Card v-for="certification in pagedCertifications" :key="certification.id" class="mb-3 shadow-none"><CardHeader class="p-4"><CardTitle class="text-sm">{{ certification.title }}</CardTitle></CardHeader><CardContent class="px-4 pb-4 text-xs text-muted-foreground">{{ certification.issuingOrganization }} | {{ certification.issueDate }}<p v-if="certification.reviewerName" class="mt-2">Verified by {{certification.reviewerName}} · {{new Date(certification.reviewedAt!).toLocaleDateString('en-MY')}}</p><p v-if="certification.expiryDate" class="mt-1">Valid until {{certification.expiryDate}}</p></CardContent></Card>
        <ListPagination v-if="verifiedCertifications.length>6" class="no-print mt-4" v-model:page="pageCertifications" :total="verifiedCertifications.length" :page-size="6" label="certifications" />
        </section>

        <section>
          <div class="mb-4 flex items-center gap-2"><BookOpenCheck class="size-5 text-primary" /><h3 class="font-semibold">Assessment results</h3></div>
          <div v-if="passedAttempts.length === 0" class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">No passed assessments yet.</div>
          <div class="space-y-3"><div v-for="attempt in pagedResults" :key="attempt.id" class="flex items-center justify-between rounded-xl border border-border p-4"><div><strong class="text-sm">{{ attempt.assessmentTitle }}</strong><p class="mt-1 text-xs text-muted-foreground">Best result</p></div><span class="text-xl font-semibold text-primary">{{ attempt.percentage }}%</span></div></div>
        <ListPagination v-if="passedAttempts.length>6" class="no-print mt-4" v-model:page="pageResults" :total="passedAttempts.length" :page-size="6" label="results" />
        </section>
      </div>

      <footer class="flex flex-col gap-3 border-t border-border bg-muted/30 px-6 py-5 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-10">
        <span class="flex items-center gap-2"><IdCard class="size-4" /> Skills & Career | Universiti Teknikal Malaysia Melaka</span>
        <span>Generated {{ new Date().toLocaleDateString('en-MY', { dateStyle: 'long' }) }}</span>
      </footer>
    </article>
  </div>
</template>

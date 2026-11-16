<script setup lang="ts">
import StudentProgressPanel from '@/components/shared/StudentProgressPanel.vue'
import { visitStudentProgress, type StudentProgressResponse } from '@/lib/api'

import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Award, BookOpenCheck, Check, ArrowRight, FolderKanban, Loader2, Sparkles, Clock3, Layers3, Circle, Plus } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { useAuth } from '@/composables/useAuth'
import { usePortalData } from '@/composables/usePortalData'

const { user } = useAuth()
const { skills, projects, certifications, attempts, activity, studentError, refreshStudentData } = usePortalData()
const loading = ref(true)
const progress=ref<StudentProgressResponse|null>(null)
const progressError=ref('')
onMounted(async () => {
  await refreshStudentData()
  try{progress.value=await visitStudentProgress()}catch(cause){progressError.value=cause instanceof Error?cause.message:'Progress could not be loaded.'}
  loading.value=false
})
const stats = computed(() => [
  { label: 'Skills', tone: 'blue', value: skills.value.length, detail: skills.value.filter(i => i.status === 'verified').length + ' verified', icon: Sparkles, path: '/student/skills' },
  { label: 'Projects', tone: 'teal', value: projects.value.length, detail: projects.value.filter(i => i.status === 'verified').length + ' verified', icon: FolderKanban, path: '/student/projects' },
  { label: 'Certifications', tone: 'amber', value: certifications.value.length, detail: certifications.value.filter(i => i.status === 'verified').length + ' verified', icon: Award, path: '/student/certifications' },
  { label: 'Assessments', tone: 'violet', value: attempts.value.length, detail: attempts.value.length ? attempts.value.filter(i => i.passed).length + ' passed' : 'No attempts yet', icon: BookOpenCheck, path: '/student/assessments' },
])
const checklist = computed(() => progress.value?.checklist ?? [
  { label: 'Complete your personal details', done: Boolean(user.value?.programme && user.value?.matricNo), path: '/student/profile' },
  { label: 'Add project or certification evidence', done: projects.value.length + certifications.value.length > 0, path: '/student/projects' },
  { label: 'Have a skill verified', done: skills.value.some(i => i.status === 'verified'), path: '/student/skills' },
  { label: 'Pass a skills assessment', done: attempts.value.some(i => i.passed), path: '/student/assessments' },
])
const completed = computed(() => checklist.value.filter(i => i.done).length)
const pending = computed(() => [...skills.value, ...projects.value, ...certifications.value].filter(i => i.status === 'pending').length)
const needsUpdate = computed(() => [...skills.value, ...projects.value, ...certifications.value].filter(i => i.status === 'rejected').length)
const nextStep = computed(() => {
  if (progress.value?.checklist[0]?.done === false || !user.value?.programme || !user.value?.matricNo) return { title: 'Start with your personal details.', text: 'Add your programme and student information so your record is ready to build on.', label: 'Complete details', path: '/student/profile' }
  if (!skills.value.length) return { title: 'What can you do?', text: 'Add your first skill, then connect it to work that shows how you use it.', label: 'Add your first skill', path: '/student/skills' }
  if (!projects.value.length && !certifications.value.length) return { title: 'Let your work do the talking.', text: 'Add a project or certification and link it to the skills it demonstrates.', label: 'Add supporting work', path: '/student/projects' }
  if (!skills.value.some(i => i.status === 'verified')) return { title: 'Build a record you can stand behind.', text: 'Check your linked evidence and review status. Verified skills help make your job matches more relevant.', label: 'Review your skills', path: '/student/skills' }
  if (!attempts.value.some(i => i.passed)) return { title: 'Put your knowledge to the test.', text: 'Complete an assessment to add a scored result to your achievements.', label: 'Explore assessments', path: '/student/assessments' }
  if (progress.value?.checklist[4]?.done && !progress.value?.checklist[5]?.done) return {title:'Bring your experience together.',text:'Create or upload your CV, review it with Navi and keep a general version ready for applications.',label:'Create / upload CV',path:'/student/cv'}
  return { title: 'Explore what comes next.', text: 'Compare your verified skills with current openings and prepare a CV for a role that interests you.', label: 'Explore job matches', path: '/student/jobs' }
})
function formatDate(value: string) { return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium' }).format(new Date(value)) }
</script>
<template>
  <div v-if="loading" role="status" class="flex items-center justify-center gap-3 py-20 text-muted-foreground"><Loader2 class="size-5 animate-spin text-primary" /> Loading your workspace…</div>
  <div v-else class="space-y-7">
    <p v-if="studentError" role="alert" class="rounded-xl bg-destructive/10 p-4 text-sm text-destructive">{{ studentError }} <button class="ml-2 underline" @click="refreshStudentData">Retry</button></p>
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-2">Your workspace</p><h1 class="page-heading">Hello, {{ user?.name }}</h1><p class="page-intro">Your skills, your progress and your next step.</p></div><Button as-child size="lg" class="shadow-sm"><RouterLink to="/student/skills-profile"><Layers3 class="size-4" /> View skills profile</RouterLink></Button></div>
    <p v-if="progressError" role="alert" class="text-sm text-destructive">{{progressError}}</p>
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Your record summary">
      <RouterLink v-for="item in stats" :key="item.label" :to="item.path" :class="'tone-'+item.tone" class="metric-card tone-card rounded-2xl border p-5"><div class="flex items-center justify-between text-muted-foreground"><span class="text-sm font-semibold tone-text">{{ item.label }}</span><span class="metric-icon"><component :is="item.icon" class="size-5" /></span></div><p class="mt-2 text-3xl font-semibold tabular-nums">{{ item.value }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.detail }}</p></RouterLink>
    </section>
    <section class="grid gap-5 xl:grid-cols-[1.3fr_1fr]">
      <div class="brand-dark flex flex-col justify-between rounded-2xl p-7 sm:p-8"><div><p class="section-label">Your next step</p><h2 class="mt-5 max-w-lg text-2xl font-semibold tracking-tight sm:text-3xl">{{ nextStep.title }}</h2><p class="muted-copy mt-4 max-w-lg text-sm leading-7">{{ nextStep.text }}</p></div><Button as-child class="mt-7 self-start"><RouterLink :to="nextStep.path">{{ nextStep.label }}<ArrowRight class="size-4" /></RouterLink></Button></div>
      <Card class="tone-card tone-blue gap-0"><CardHeader><div class="flex items-center justify-between"><CardTitle>Build your record</CardTitle><span class="text-xs font-semibold text-primary">{{ completed }} / {{ checklist.length }}</span></div><CardDescription>A few milestones to get you started.</CardDescription></CardHeader><CardContent><Progress :model-value="completed / checklist.length * 100" aria-label="Record milestones completed" class="mb-5 h-1.5" /><RouterLink v-for="item in checklist" :key="item.label" :to="item.path" class="flex items-center gap-3 rounded-lg py-2.5 text-sm hover:text-primary"><Check v-if="item.done" class="size-4 shrink-0 text-success" /><Circle v-else class="size-4 shrink-0 text-muted-foreground" /><span :class="item.done ? 'text-muted-foreground' : ''">{{ item.label }}</span><ArrowRight class="ml-auto size-3.5 shrink-0 text-muted-foreground" /></RouterLink></CardContent></Card>
    </section>

    <StudentProgressPanel :progress="progress" />
    <section class="grid items-start gap-6 xl:grid-cols-[1.3fr_1fr]">
      <Card class="tone-card tone-teal"><CardHeader><CardTitle>Recent activity</CardTitle><CardDescription>The latest changes to your skills and achievements.</CardDescription></CardHeader><CardContent><div v-if="!activity.length" class="empty-state"><FolderKanban class="mx-auto mb-3 size-7 text-primary" /><h3 class="text-sm font-semibold">Your story starts with one skill.</h3><p class="mx-auto mt-2 max-w-xs text-sm leading-6 text-muted-foreground">Add a skill or a piece of work. Your updates will appear here as you build your record.</p><Button as-child variant="outline" class="mt-5"><RouterLink to="/student/skills"><Plus class="size-4" /> Add a skill</RouterLink></Button></div><div v-for="item in activity" :key="item.id" class="flex gap-4 border-b py-4 first:pt-0 last:border-0"><span class="mt-1.5 size-2 shrink-0 rounded-full bg-teal"></span><div><p class="text-sm font-medium leading-6">{{ item.description }}</p><p class="mt-1 text-xs text-muted-foreground">{{ formatDate(item.timestamp) }}</p></div></div></CardContent></Card>
      <Card class="tone-card tone-amber"><CardHeader><CardTitle>Review status</CardTitle><CardDescription>Keep an eye on your submitted evidence.</CardDescription></CardHeader><CardContent class="space-y-4"><div class="flex items-center gap-3 rounded-lg bg-warning-soft px-4 py-3 text-warning"><Clock3 class="size-4" /><span class="flex-1 text-sm">Awaiting review</span><strong class="text-lg tabular-nums">{{ pending }}</strong></div><div class="flex items-center justify-between gap-3 px-1 text-sm"><span class="text-muted-foreground">Records needing an update</span><strong>{{ needsUpdate }}</strong></div><p class="border-t pt-4 text-xs leading-6 text-muted-foreground">An administrator reviews the evidence linked to your records. Check each record for feedback and next steps.</p></CardContent></Card>
    </section>
  </div>
</template>

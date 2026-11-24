<script setup lang="ts">
import DecimalInput from '@/components/shared/DecimalInput.vue'
import LocationSelect from '@/components/shared/LocationSelect.vue'
import { onMounted, reactive, ref, nextTick, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  BriefcaseBusiness,
  CheckCircle2,
  FileText,
  MapPin,
  Globe2,
  Search,
  Loader2,
  Sparkles,
  ThumbsUp,
  ThumbsDown,
  SlidersHorizontal,
  TriangleAlert,
  ChevronDown,
  Laptop,
} from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import AppSelect from '@/components/shared/AppSelect.vue'
import ListPagination from '@/components/shared/ListPagination.vue'
import TaskProgress from '@/components/shared/TaskProgress.vue'
import { presentTaskResult } from '@/lib/taskTiming'
import { usePagination } from '@/composables/usePagination'
import { useFormDraft } from '@/composables/useFormDraft'
import {
  ApiError,
  getGeneratedCvs,
  duplicateCv,
  generateCv,
  reviewCv,
  generateJobRecommendations,
  getJobPreferences,
  getLatestJobRecommendations,
  updateJobPreferences,
  saveJobFeedback,
} from '@/lib/api'
import type { JobRecommendationMatch, JobRecommendationResponse, GeneratedCvResponse } from '@/lib/api'

const loading = ref(true)
const loadComplete = ref(false)
const generating = ref(false)
const searchPhase = ref('')
const searchStep = ref(0)
const searchComplete = ref(false)
const modelName = ref('')
const insights = ref<JobRecommendationResponse['aiInsights']>(null)
const resultsHeading = ref<HTMLElement>()
const feedbackBusy = ref<number | null>(null)
const feedbackNotice = ref('')
const error = ref('')
const matches = ref<JobRecommendationMatch[]>([])
const catalogue = ref<JobRecommendationResponse['catalogue']>()
const sourceFilter = ref('all')
const filteredMatches = computed(() =>
  sourceFilter.value === 'all'
    ? matches.value
    : matches.value.filter((match) => match.job.source === sourceFilter.value),
)
const sourceOptions = computed(() => [
  { value: 'all', label: 'All sources' },
  ...Array.from(new Set(matches.value.map((match) => match.job.source)))
    .sort()
    .map((source) => ({ value: source, label: sourceName(source) })),
])
const { page, items: pagedMatches, pageSize } = usePagination(filteredMatches, 10)
watch(sourceFilter, () => {
  page.value = 1
})
const router = useRouter()
const tailoring = ref(false)
const tailorOpen = ref(false)
const selectedJob = ref<JobRecommendationMatch | null>(null)
const documents = ref<GeneratedCvResponse[]>([])
const baseCv = ref<number>(0)
const tailorPhase = ref('')
const tailorStep = ref(0)
const tailorComplete = ref(false)
const tailorError = ref('')
async function openTailor(match: JobRecommendationMatch) {
  selectedJob.value = match
  tailorOpen.value = true
  tailorError.value = ''
  documents.value = []
  baseCv.value = 0
  tailoring.value = true
  try {
    documents.value = (await getGeneratedCvs()).sort((a, b) => a.version - b.version)
    baseCv.value = documents.value.find((cv) => !cv.targetJob)?.id ?? 0
  } catch (cause) {
    tailorError.value =
      cause instanceof ApiError ? cause.message : 'Your CVs could not be loaded. Please reopen this dialog.'
  } finally {
    tailoring.value = false
  }
}
async function tailor() {
  if (!selectedJob.value || tailoring.value) return
  const started = performance.now()
  tailorComplete.value = false
  tailoring.value = true
  tailorError.value = ''
  tailorStep.value = 0
  tailorPhase.value = 'Creating a separate CV for this role…'
  let created: GeneratedCvResponse | null = null
  try {
    created = baseCv.value
      ? await duplicateCv(baseCv.value, selectedJob.value.job.id)
      : await generateCv(selectedJob.value.job.id)
    tailorStep.value = 1
    tailorPhase.value = 'Comparing your CV with the listing and preparing suggestions…'
    await reviewCv(created.id)
    tailorStep.value = 2
    tailorComplete.value = true
    tailorPhase.value = 'Your role version is ready. Opening the review…'
    await presentTaskResult(started)
    tailorOpen.value = false
    await router.push({ path: '/student/cv', query: { cv: created.id, view: 'review' } })
  } catch (cause) {
    tailorError.value =
      (cause instanceof ApiError ? cause.message : 'The request could not finish.') +
      (created ? ' Your new version is saved in Create / upload CV. Open it there to retry the review.' : '')
  } finally {
    tailoring.value = false
    tailorPhase.value = ''
  }
}
function scoreClass(score: number) {
  return score >= 75 ? 'fit-high' : score >= 45 ? 'fit-medium' : 'fit-low'
}
const generatedAt = ref('')
const salaryOpen = ref(false)
const locationOptions = ref<string[]>([])
const form = reactive({
  fields: '',
  locations: [] as string[],
  workTypes: 'full-time',
  acceptsRemote: false,
  minimumSalary: undefined as number | undefined,
  maximumSalary: undefined as number | undefined,
})

const preferencesDraft = useFormDraft('job-preferences', form, true)

function list(value: string) {
  return value
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
}

onMounted(async () => {
  const started = performance.now()
  try {
    const [preferences, recommendations] = await Promise.all([getJobPreferences(), getLatestJobRecommendations()])
    Object.assign(form, {
      fields: preferences.preferredFields.join(', '),
      locations: preferences.preferredLocations,
      workTypes: preferences.workTypes[0] ?? '',
      acceptsRemote: preferences.acceptsRemote,
      minimumSalary: preferences.minimumSalary ?? undefined,
      maximumSalary: preferences.maximumSalary ?? undefined,
    })
    locationOptions.value = preferences.locationOptions ?? []
    preferencesDraft.restore()
    form.locations = Array.isArray(form.locations) ? form.locations.filter((state) => locationOptions.value.includes(state)) : []
    salaryOpen.value = form.minimumSalary !== undefined || form.maximumSalary !== undefined
    matches.value = recommendations.matches
    catalogue.value = recommendations.catalogue
    generatedAt.value = recommendations.generatedAt ?? ''
    modelName.value = recommendations.modelName ?? ''
    insights.value = recommendations.aiInsights
    loadComplete.value = true
    await presentTaskResult(started, 900)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Job recommendations could not be loaded.'
  } finally {
    loading.value = false
  }
})

async function generate() {
  if (generating.value || loading.value) return
  if (form.minimumSalary !== undefined && form.maximumSalary !== undefined && form.minimumSalary > form.maximumSalary) {
    error.value = 'Maximum salary must be at least the minimum salary.'
    return
  }
  generating.value = true
  searchComplete.value = false
  error.value = ''
  searchStep.value = 0
  searchPhase.value = 'Getting your search ready…'
  const started = performance.now()
  try {
    await updateJobPreferences({
      preferredFields: list(form.fields),
      preferredLocations: form.locations,
      workTypes: list(form.workTypes),
      acceptsRemote: form.acceptsRemote,
      minimumSalary: Number.isFinite(form.minimumSalary) ? Number(form.minimumSalary) : null,
      maximumSalary: Number.isFinite(form.maximumSalary) ? Number(form.maximumSalary) : null,
    })
    preferencesDraft.clear()
    searchStep.value = 1
    searchPhase.value = 'Reviewing your skills and matching roles…'
    const response = await generateJobRecommendations(form.fields)
    searchStep.value = 2
    searchPhase.value = 'Your search is complete. Preparing the results…'
    searchComplete.value = true
    await presentTaskResult(started)
    matches.value = response.matches
    catalogue.value = response.catalogue
    sourceFilter.value = 'all'
    page.value = 1
    generatedAt.value = response.generatedAt ?? ''
    modelName.value = response.modelName ?? ''
    insights.value = response.aiInsights
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The search could not finish. Please try again.'
  } finally {
    generating.value = false
    await nextTick()
    resultsHeading.value?.focus({ preventScroll: true })
  }
}
async function feedback(match: JobRecommendationMatch, rating: 'relevant' | 'not_relevant', reason?: string) {
  if (feedbackBusy.value !== null) return
  feedbackBusy.value = match.id
  feedbackNotice.value = ''
  try {
    await saveJobFeedback(match.job.id, rating, reason)
    match.feedback = rating
    match.feedbackReason = reason ?? null
    feedbackNotice.value = 'Thanks — your feedback is saved.'
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Feedback could not be saved.'
  } finally {
    feedbackBusy.value = null
  }
}
function salary(match: JobRecommendationMatch) {
  const job = match.job
  if (job.salaryMin === null && job.salaryMax === null) return ''
  return [
    job.salaryCurrency,
    job.salaryMin?.toLocaleString(),
    job.salaryMax !== null ? '– ' + job.salaryMax.toLocaleString() : '',
  ]
    .filter(Boolean)
    .join(' ')
}
function sourceName(source: string) {
  const names: Record<string, string> = {
    jobstreet: 'JobStreet',
    jobstore: 'Jobstore',
    hiredly: 'Hiredly',
    ricebowl: 'Ricebowl',
    maukerja: 'Maukerja',
    remotive: 'Remotive',
    jooble: 'Jooble',
  }
  if (names[source]) return names[source]
  return source.charAt(0).toUpperCase() + source.slice(1)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="page-heading">Job matches</h1>
        <p class="mt-1 text-muted-foreground"
          >Find opportunities for your skills. Let Navi help you choose where to start.</p
        >
        <p v-if="catalogue?.total" class="mt-2 text-sm font-medium text-primary"
          >{{ catalogue.total.toLocaleString() }} current opportunities across {{ catalogue.sources.length }} sources ·
          Malaysia and eligible remote roles</p
        >
      </div>
    </div>

    <p v-if="error" role="alert" class="rounded-lg bg-destructive/10 p-3 text-sm font-medium text-destructive">{{
      error
    }}</p>

    <Card class="tone-card tone-blue">
      <CardHeader>
        <div class="flex items-center gap-3"
          ><div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
            ><SlidersHorizontal class="size-5" /></div
          ><div
            ><CardTitle>What are you looking for?</CardTitle
            ><CardDescription
              >Enter a role or skill. Your last search is remembered for next time.</CardDescription
            ></div
          ></div
        >
      </CardHeader>
      <CardContent>
        <form class="space-y-5" @submit.prevent="generate">
          <div class="grid items-end gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto]">
            <div class="space-y-2"
              ><Label for="fields">Role or skills</Label
              ><div class="relative"
                ><Search class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" /><Input
                  id="fields"
                  v-model="form.fields"
                  class="pl-10"
                  placeholder="Job title, skill or keyword"
                  :disabled="generating || loading" /></div
            ></div>
            <div class="space-y-2"
              ><Label for="locations">Location</Label
              ><LocationSelect
                id="locations"
                v-model="form.locations"
                :options="locationOptions"
                :disabled="generating || loading"
            /></div>
            <Button type="submit" size="lg" :disabled="generating || loading"
              ><Loader2 v-if="generating" class="size-4 animate-spin" /><Search v-else class="size-4" />{{
                generating ? 'Searching…' : 'Search jobs'
              }}</Button
            >
          </div>
          <div class="flex flex-wrap items-end gap-4 border-t pt-5">
            <div class="w-full space-y-2 sm:w-52"
              ><Label for="work-types">Employment type</Label
              ><AppSelect
                id="work-types"
                v-model="form.workTypes"
                :disabled="generating || loading"
                :options="[
                  { value: '', label: 'Any employment type' },
                  { value: 'full-time', label: 'Full time' },
                  { value: 'part-time', label: 'Part time' },
                  { value: 'contract', label: 'Contract' },
                  { value: 'internship', label: 'Internship' },
                  { value: 'freelance', label: 'Freelance' },
                ]"
            /></div>
            <button
              type="button"
              class="flex min-h-11 items-center gap-3 rounded-xl border px-4 text-sm font-medium transition-colors"
              :class="
                form.acceptsRemote
                  ? 'border-primary/40 bg-accent text-primary'
                  : 'border-border bg-card text-muted-foreground'
              "
              :aria-pressed="form.acceptsRemote"
              :disabled="generating || loading"
              @click="form.acceptsRemote = !form.acceptsRemote"
              ><Laptop class="size-4" />Include remote roles<span
                class="relative h-5 w-9 rounded-full"
                :class="form.acceptsRemote ? 'bg-primary' : 'bg-muted-foreground/30'"
                ><span
                  class="absolute left-0 top-0.5 size-4 rounded-full bg-white transition-transform"
                  :class="form.acceptsRemote ? 'translate-x-[18px]' : 'translate-x-0.5'"
                ></span></span
            ></button>
            <Button
              type="button"
              variant="outline"
              class="min-h-11"
              :aria-expanded="salaryOpen"
              aria-controls="salary-filters"
              @click="salaryOpen = !salaryOpen"
              ><SlidersHorizontal class="size-4" />Salary range<span
                v-if="form.minimumSalary !== undefined || form.maximumSalary !== undefined"
                class="size-2 rounded-full bg-primary"
              ></span
              ><ChevronDown class="size-4 transition-transform" :class="salaryOpen ? 'rotate-180' : ''"
            /></Button>
          </div>
          <div
            v-if="salaryOpen"
            id="salary-filters"
            class="grid gap-4 rounded-xl border bg-background p-4 sm:grid-cols-2"
          >
            <div class="space-y-2"
              ><Label for="minimum-salary">Minimum monthly salary (MYR)</Label
              ><DecimalInput
                id="minimum-salary"
                v-model="form.minimumSalary"
                min="0"
                placeholder="e.g. 3000.00"
                :disabled="generating || loading"
            /></div>
            <div class="space-y-2"
              ><Label for="maximum-salary">Maximum monthly salary (MYR)</Label
              ><DecimalInput
                id="maximum-salary"
                v-model="form.maximumSalary"
                min="0"
                placeholder="No maximum"
                :disabled="generating || loading"
            /></div>
            <p class="text-xs text-muted-foreground sm:col-span-2"
              >Optional. Used when a listing publishes its salary in MYR.</p
            >
          </div>
          <p class="text-xs text-muted-foreground"
            >Separate multiple roles with commas. Choose one or more states, or keep All of Malaysia. Remote roles are
            shown only when “Include remote roles” is on. Your search is remembered automatically.</p
          >
        </form>
      </CardContent>
    </Card>

    <TaskProgress
      :active="loading || generating"
      :title="loading ? (loadComplete ? 'Your saved search is ready.' : 'Opening your saved matches…') : searchPhase"
      :detail="
        loading
          ? 'Restoring your previous search and its results.'
          : searchComplete
            ? 'Your results are ready. Opening your matches now.'
            : 'Comparing available roles with your skills and asking Navi for a shortlist. Your results will appear here.'
      "
      :steps="generating ? ['Your search', 'Skills & roles', 'Results ready'] : []"
      :step="searchStep"
      :complete="loading ? loadComplete : searchComplete"
    />
    <div v-if="matches.length" class="space-y-4">
      <section
        v-if="insights?.picks?.length && modelName.startsWith('ollama:')"
        class="rounded-2xl border border-teal/30 bg-teal-soft p-5"
        ><h2 class="flex items-center gap-2 font-heading text-xl text-teal"
          ><Sparkles class="size-5" />Navi’s shortlist</h2
        ><p class="mt-2 text-sm text-muted-foreground">A few roles to explore from your last AI search.</p
        ><div class="mt-4 grid gap-3 md:grid-cols-3"
          ><article
            v-for="pick in insights.picks.filter((p) => matches.some((m) => m.job.id === p.jobId))"
            :key="pick.jobId"
            class="rounded-xl border border-teal/20 bg-card p-4"
            ><h3 class="font-semibold">{{ pick.title }}</h3
            ><p class="mt-2 text-sm leading-6 text-muted-foreground">{{ pick.note }}</p></article
          ></div
        ></section
      >
      <p v-else-if="modelName && !modelName.startsWith('ollama:')" class="rounded-xl border bg-accent p-4 text-sm"
        >Your skills-based matches are ready. Navi’s AI shortlist is unavailable for this search.</p
      >
      <div class="flex flex-wrap items-center justify-between gap-3"
        ><div
          ><h2 ref="resultsHeading" tabindex="-1" class="font-semibold outline-none"
            >{{ filteredMatches.length }} roles to explore</h2
          ><p v-if="generatedAt" class="mt-1 text-xs text-muted-foreground"
            >Last search {{ new Date(generatedAt).toLocaleString('en-MY') }}</p
          ></div
        ><div class="w-full sm:w-52"
          ><Label for="job-source" class="mb-2 block">Job source</Label
          ><AppSelect id="job-source" v-model="sourceFilter" :options="sourceOptions" /></div
      ></div>
      <Card v-for="match in pagedMatches" :key="match.id" class="overflow-hidden">
        <CardContent class="grid gap-6 p-6 lg:grid-cols-[1fr_220px]">
          <div>
            <div class="flex flex-wrap items-start justify-between gap-3"
              ><div
                ><div class="mb-2 flex items-center gap-2"
                  ><Badge variant="secondary">#{{ match.rank }}</Badge
                  ><Badge class="provider-badge" variant="outline">{{ sourceName(match.job.source) }}</Badge></div
                ><h3 class="text-lg font-semibold">{{ match.job.title }}</h3
                ><p class="mt-1 text-sm text-muted-foreground">{{ match.job.company }}</p
                ><p v-if="match.job.fetchedAt" class="mt-1 text-xs text-muted-foreground"
                  >Listed in our search {{ new Date(match.job.fetchedAt).toLocaleDateString('en-MY') }}</p
                ></div
              ><div class="flex items-center gap-1.5 text-sm text-muted-foreground"
                ><component :is="match.job.remote ? Globe2 : MapPin" class="size-4" />
                {{ match.job.locationLabel || match.job.location || 'Malaysia' }}</div
              ></div
            >
            <p class="mt-4 text-sm leading-relaxed text-muted-foreground">{{ match.explanation }}</p
            ><p v-if="match.scoreBreakdown?.levelNote" class="mt-2 flex items-center gap-2 text-sm text-warning"
              ><TriangleAlert class="size-4 shrink-0" />{{ match.scoreBreakdown.levelNote }}</p
            ><p v-if="salary(match)" class="mt-2 text-sm font-semibold">{{ salary(match) }}</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
              <div
                ><p class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-success"
                  ><CheckCircle2 class="size-4" /> Matched skills</p
                ><div class="flex flex-wrap gap-2"
                  ><Badge
                    v-for="skill in match.matchedSkills"
                    :key="skill"
                    class="bg-success-soft text-success"
                    variant="outline"
                    >{{ skill }}</Badge
                  ><span v-if="!match.matchedSkills.length" class="text-xs text-muted-foreground"
                    >No direct skill match yet</span
                  ></div
                ></div
              >
              <div
                ><p class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-warning"
                  ><TriangleAlert class="size-4" /> Skills to review</p
                ><div class="flex flex-wrap gap-2"
                  ><Badge
                    v-for="skill in match.missingSkills"
                    :key="skill"
                    class="bg-warning-soft text-warning"
                    variant="outline"
                    >{{ skill }}</Badge
                  ><span v-if="!match.missingSkills.length" class="text-xs text-muted-foreground">{{
                    match.scoreBreakdown?.assessable
                      ? 'All listed skills matched'
                      : 'Open the listing to check its full requirements'
                  }}</span></div
                ></div
              >
            </div>
          </div>
          <div class="flex flex-col justify-between rounded-xl bg-muted/40 p-5">
            <div
              ><p class="text-sm font-medium">Fit score</p
              ><p
                class="mt-1 text-3xl font-bold"
                :class="
                  match.scoreBreakdown?.assessable === false ? 'text-muted-foreground' : scoreClass(match.matchScore)
                "
                >{{
                  match.scoreBreakdown?.assessable === false ? 'Not assessed' : Math.round(match.matchScore) + '/100'
                }}</p
              ><div
                v-if="match.scoreBreakdown?.assessable !== false"
                class="fit-meter mt-3"
                :class="scoreClass(match.matchScore)"
                role="meter"
                aria-label="Verified requirement fit"
                :aria-valuenow="match.matchScore"
                aria-valuemin="0"
                aria-valuemax="100"
                ><i :style="{ width: match.matchScore + '%' }"></i></div
              ><p class="mt-2 text-xs font-semibold" :class="scoreClass(match.matchScore)">{{
                !match.scoreBreakdown?.assessable
                  ? 'Requirements unavailable'
                  : match.matchScore >= 75
                    ? 'Strong skills match'
                    : match.matchScore >= 45
                      ? 'Some skills match'
                      : 'Few skills matched'
              }}</p
              ><p class="mt-2 text-xs text-muted-foreground">Check the full role requirements before applying.</p
              ><p class="mt-4 text-xs capitalize text-muted-foreground"
                ><BriefcaseBusiness class="mr-1 inline size-3.5" />
                {{ match.job.workType || 'Work type not listed' }}</p
              ></div
            >
            <div class="mt-6 space-y-2"
              ><Button class="w-full" @click="openTailor(match)"><FileText class="size-4" /> Tailor CV</Button
              ><Button as-child variant="outline" class="w-full"
                ><a :href="match.job.applicationUrl" target="_blank" rel="noreferrer"
                  >View listing <ArrowUpRight class="size-4" /></a></Button
            ></div>
          </div>
          <div class="flex flex-wrap items-center gap-2 border-t pt-3 text-xs text-muted-foreground lg:col-span-2"
            ><span class="mr-1">Useful match?</span
            ><Button
              size="sm"
              :variant="match.feedback === 'relevant' ? 'default' : 'outline'"
              :disabled="feedbackBusy !== null"
              :aria-pressed="match.feedback === 'relevant'"
              @click="feedback(match, 'relevant')"
              ><ThumbsUp class="size-3.5" />Yes</Button
            ><Button
              size="sm"
              :variant="match.feedback === 'not_relevant' ? 'default' : 'outline'"
              :disabled="feedbackBusy !== null"
              :aria-pressed="match.feedback === 'not_relevant'"
              @click="feedback(match, 'not_relevant')"
              ><ThumbsDown class="size-3.5" />Not for me</Button
            ><div v-if="match.feedback === 'not_relevant'" class="w-full max-w-sm"
              ><label :for="'feedback-' + match.id" class="mb-2 mt-2 block">Reason (optional)</label
              ><AppSelect
                :id="'feedback-' + match.id"
                :model-value="match.feedbackReason"
                placeholder="Choose a reason"
                :disabled="feedbackBusy !== null"
                :options="[
                  { value: 'Experience requirements', label: 'Experience requirements' },
                  { value: 'Location', label: 'Location' },
                  { value: 'Skills required', label: 'Skills required' },
                  { value: 'Work type', label: 'Work type' },
                  { value: 'Not interested in this role', label: 'Not interested in this role' },
                ]"
                @update:model-value="(value) => feedback(match, 'not_relevant', String(value))" /></div
          ></div>
        </CardContent>
      </Card>
      <ListPagination v-model:page="page" :total="filteredMatches.length" :page-size="pageSize" label="roles" />
    </div>
    <Card v-else-if="!loading && !generating"
      ><CardContent class="p-10 text-center"
        ><BriefcaseBusiness class="mx-auto size-8 text-muted-foreground" /><p class="mt-3 font-medium"
          >No current matches yet</p
        ><p class="mt-1 text-sm text-muted-foreground"
          >Try Search jobs above. Broaden the role or location, or include remote opportunities if no roles match your
          search.</p
        ></CardContent
      ></Card
    >
    <p v-if="feedbackNotice" role="status" class="text-sm text-success">{{ feedbackNotice }}</p>
    <TaskProgress
      :active="!!tailorPhase"
      :title="tailorPhase || 'Comparing current listings with your verified records…'"
      :steps="tailorPhase ? ['Create role version', 'Review requirements', 'Ready to edit'] : []"
      :step="tailorStep"
      :complete="tailorComplete"
    />
    <Dialog
      :open="tailorOpen"
      @update:open="
        (value) => {
          if (!tailoring) tailorOpen = value
        }
      "
      ><DialogContent
        @interact-outside="
          (event) => {
            if (tailoring) event.preventDefault()
          }
        "
        @escape-key-down="
          (event) => {
            if (tailoring) event.preventDefault()
          }
        "
        ><DialogHeader
          ><DialogTitle>Tailor a CV for this role</DialogTitle
          ><DialogDescription
            >{{ selectedJob?.job.title }} · {{ selectedJob?.job.company }}</DialogDescription
          ></DialogHeader
        ><p class="text-sm leading-7 text-muted-foreground"
          >Create a separate version and review which requirements your experience already supports. AI suggests
          improvements for you to approve; it does not add qualifications.</p
        ><label for="tailor-base" class="text-sm font-medium">Start from</label
        ><AppSelect
          id="tailor-base"
          v-model="baseCv"
          :disabled="tailoring"
          :options="[
            { value: 0, label: 'Create from my verified records' },
            ...documents.map((cv) => ({ value: cv.id, label: 'v' + cv.version + ' · ' + cv.title })),
          ]"
        /><p class="text-xs leading-6 text-muted-foreground"
          >Existing CVs stay unchanged. Save any pending CV edits before tailoring.</p
        ><p v-if="tailorError" role="alert" class="text-sm text-destructive">{{ tailorError }}</p
        ><DialogFooter
          ><Button variant="outline" :disabled="tailoring" @click="tailorOpen = false">Cancel</Button
          ><Button :disabled="tailoring" @click="tailor">{{
            tailoring ? 'Preparing…' : 'Create & review version'
          }}</Button></DialogFooter
        ></DialogContent
      ></Dialog
    >
  </div>
</template>

<style scoped>
.provider-badge {
  background: var(--accent);
  color: var(--primary);
  border-color: var(--primary);
  font-weight: 700;
  padding: 5px 10px;
}
.fit-low {
  color: var(--destructive);
}
.fit-medium {
  color: var(--warning);
}
.fit-high {
  color: var(--success);
}
.fit-meter {
  height: 6px;
  border-radius: 20px;
  background: var(--border);
  overflow: hidden;
}
.fit-meter i {
  display: block;
  height: 100%;
  background: currentColor;
  min-width: 0;
  transition: width 0.3s ease;
}
@media (prefers-reduced-motion: reduce) {
  .fit-meter i {
    transition: none;
  }
}
</style>

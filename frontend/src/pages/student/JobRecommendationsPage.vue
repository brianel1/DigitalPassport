<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ArrowUpRight, BriefcaseBusiness, CheckCircle2, MapPin, RefreshCw, SlidersHorizontal, TriangleAlert } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Progress } from '@/components/ui/progress'
import { ApiError, generateJobRecommendations, getJobPreferences, getLatestJobRecommendations, updateJobPreferences } from '@/lib/api'
import type { JobRecommendationMatch } from '@/lib/api'

const loading = ref(true)
const generating = ref(false)
const saved = ref(false)
const error = ref('')
const matches = ref<JobRecommendationMatch[]>([])
const generatedAt = ref('')
const form = reactive({
  fields: '',
  locations: '',
  workTypes: 'full-time',
  acceptsRemote: true,
  minimumSalary: undefined as number | undefined,
  maximumSalary: undefined as number | undefined,
})

function list(value: string) {
  return value.split(',').map((item) => item.trim()).filter(Boolean)
}

onMounted(async () => {
  try {
    const [preferences, recommendations] = await Promise.all([
      getJobPreferences(),
      getLatestJobRecommendations(),
    ])
    Object.assign(form, {
      fields: preferences.preferredFields.join(', '),
      locations: preferences.preferredLocations.join(', '),
      workTypes: preferences.workTypes.join(', '),
      acceptsRemote: preferences.acceptsRemote,
      minimumSalary: preferences.minimumSalary ?? undefined,
      maximumSalary: preferences.maximumSalary ?? undefined,
    })
    matches.value = recommendations.matches
    generatedAt.value = recommendations.generatedAt ?? ''
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Job recommendations could not be loaded.'
  } finally {
    loading.value = false
  }
})

async function savePreferences() {
  error.value = ''
  await updateJobPreferences({
    preferredFields: list(form.fields),
    preferredLocations: list(form.locations),
    workTypes: list(form.workTypes),
    acceptsRemote: form.acceptsRemote,
    minimumSalary: form.minimumSalary ?? null,
    maximumSalary: form.maximumSalary ?? null,
  })
  saved.value = true
  window.setTimeout(() => { saved.value = false }, 2000)
}

async function generate() {
  generating.value = true
  error.value = ''
  try {
    await savePreferences()
    const response = await generateJobRecommendations()
    matches.value = response.matches
    generatedAt.value = response.generatedAt ?? ''
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Recommendations could not be generated.'
  } finally {
    generating.value = false
  }
}

function sourceName(source: string) {
  return source === 'local' ? 'Career fallback' : source.charAt(0).toUpperCase() + source.slice(1)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Job recommendations</h1>
        <p class="mt-1 text-muted-foreground">Explore roles ranked using your verified skills and career preferences.</p>
      </div>
      <Button variant="info" :disabled="generating || loading" @click="generate"><RefreshCw :class="['size-4', generating && 'animate-spin']" /> {{ generating ? 'Matching jobs...' : 'Refresh matches' }}</Button>
    </div>

    <p v-if="error" class="rounded-lg bg-destructive/10 p-3 text-sm font-medium text-destructive">{{ error }}</p>

    <Card>
      <CardHeader>
        <div class="flex items-center gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><SlidersHorizontal class="size-5" /></div><div><CardTitle>Career preferences</CardTitle><CardDescription>Separate multiple values with commas. These preferences guide ranking without hiding other suitable roles.</CardDescription></div></div>
      </CardHeader>
      <CardContent>
        <form class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" @submit.prevent="generate">
          <div class="space-y-2"><Label for="fields">Preferred fields</Label><Input id="fields" v-model="form.fields" placeholder="backend, full stack" /></div>
          <div class="space-y-2"><Label for="locations">Preferred locations</Label><Input id="locations" v-model="form.locations" placeholder="Melaka, Kuala Lumpur" /></div>
          <div class="space-y-2"><Label for="work-types">Work types</Label><Input id="work-types" v-model="form.workTypes" placeholder="full-time, internship" /></div>
          <div class="space-y-2"><Label for="minimum-salary">Minimum salary (MYR)</Label><Input id="minimum-salary" v-model.number="form.minimumSalary" type="number" min="0" /></div>
          <div class="space-y-2"><Label for="maximum-salary">Maximum salary (MYR)</Label><Input id="maximum-salary" v-model.number="form.maximumSalary" type="number" min="0" /></div>
          <label class="flex items-center gap-3 self-end rounded-lg border border-border px-4 py-2.5 text-sm"><input v-model="form.acceptsRemote" type="checkbox" class="size-4 accent-primary" /> Include remote roles</label>
          <div class="flex items-center gap-3 md:col-span-2 xl:col-span-3"><Button type="button" variant="success" @click="savePreferences">Save preferences</Button><span v-if="saved" class="text-sm font-medium text-emerald-600">Preferences saved.</span></div>
        </form>
      </CardContent>
    </Card>

    <div v-if="loading" class="py-10 text-center text-sm text-muted-foreground">Loading job recommendations...</div>
    <div v-else-if="matches.length" class="space-y-4">
      <div class="flex items-center justify-between"><h2 class="font-semibold">Recommended roles</h2><p v-if="generatedAt" class="text-xs text-muted-foreground">Updated {{ new Date(generatedAt).toLocaleString('en-MY') }}</p></div>
      <Card v-for="match in matches" :key="match.id" class="overflow-hidden">
        <CardContent class="grid gap-6 p-6 lg:grid-cols-[1fr_220px]">
          <div>
            <div class="flex flex-wrap items-start justify-between gap-3"><div><div class="mb-2 flex items-center gap-2"><Badge variant="secondary">#{{ match.rank }}</Badge><Badge variant="outline">{{ sourceName(match.job.source) }}</Badge></div><h3 class="text-lg font-semibold">{{ match.job.title }}</h3><p class="mt-1 text-sm text-muted-foreground">{{ match.job.company }}</p></div><div class="flex items-center gap-1.5 text-sm text-muted-foreground"><MapPin class="size-4" /> {{ match.job.location || 'Location flexible' }}</div></div>
            <p class="mt-4 text-sm leading-relaxed text-muted-foreground">{{ match.explanation }}</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
              <div><p class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-600"><CheckCircle2 class="size-4" /> Matched skills</p><div class="flex flex-wrap gap-2"><Badge v-for="skill in match.matchedSkills" :key="skill" class="bg-emerald-500/10 text-emerald-700" variant="outline">{{ skill }}</Badge><span v-if="!match.matchedSkills.length" class="text-xs text-muted-foreground">No direct skill match yet</span></div></div>
              <div><p class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-600"><TriangleAlert class="size-4" /> Skills to review</p><div class="flex flex-wrap gap-2"><Badge v-for="skill in match.missingSkills" :key="skill" class="bg-amber-500/10 text-amber-700" variant="outline">{{ skill }}</Badge><span v-if="!match.missingSkills.length" class="text-xs text-muted-foreground">All listed skills matched</span></div></div>
            </div>
          </div>
          <div class="flex flex-col justify-between rounded-xl bg-muted/40 p-5">
            <div><p class="text-sm text-muted-foreground">Profile match</p><p class="mt-1 text-3xl font-semibold">{{ Math.round(match.matchScore) }}%</p><Progress :model-value="match.matchScore" class="mt-3" /><p class="mt-4 text-xs capitalize text-muted-foreground"><BriefcaseBusiness class="mr-1 inline size-3.5" /> {{ match.job.workType || 'Work type not listed' }}</p></div>
            <Button as-child class="mt-6"><a :href="match.job.applicationUrl" target="_blank" rel="noreferrer">View current openings <ArrowUpRight class="size-4" /></a></Button>
          </div>
        </CardContent>
      </Card>
    </div>
    <Card v-else><CardContent class="p-10 text-center"><BriefcaseBusiness class="mx-auto size-8 text-muted-foreground" /><p class="mt-3 font-medium">Your job matches are ready to generate</p><p class="mt-1 text-sm text-muted-foreground">Add your preferences, then refresh matches to compare your profile with available roles.</p></CardContent></Card>
  </div>
</template>

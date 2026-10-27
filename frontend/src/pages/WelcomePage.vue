<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { getApiHealth } from '@/lib/api'

type ApiStatus = 'checking' | 'online' | 'offline'

const apiStatus = ref<ApiStatus>('checking')

async function checkApi() {
  apiStatus.value = 'checking'

  try {
    const health = await getApiHealth()
    apiStatus.value = health.status === 'ok' ? 'online' : 'offline'
  } catch {
    apiStatus.value = 'offline'
  }
}

onMounted(checkApi)

const modules = [
  {
    title: 'Student portal',
    description: 'Profiles, skills, projects, certifications, and assessments.',
  },
  {
    title: 'Admin review',
    description: 'Evidence checks, record statuses, and assessment content.',
  },
  {
    title: 'Digital passport',
    description: 'An online summary with a downloadable PDF.',
  },
]
</script>

<template>
  <main class="min-h-screen bg-background px-6 py-10 text-foreground sm:px-10 lg:px-16">
    <div class="mx-auto max-w-6xl">
      <header class="flex items-center justify-between border-b border-border pb-6">
        <div class="flex items-center gap-3">
          <div class="flex size-10 items-center justify-center rounded-xl bg-primary text-lg font-bold text-primary-foreground">
            D
          </div>
          <span class="text-lg font-semibold tracking-tight">DigitalPassport</span>
        </div>
        <Badge variant="secondary">Project setup</Badge>
      </header>

      <section class="max-w-3xl py-16 sm:py-24">
        <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-muted-foreground">
          Student digital skills passport
        </p>
        <h1 class="text-4xl font-semibold tracking-tight sm:text-6xl">
          A clear record of skills and the work behind them.
        </h1>
        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-muted-foreground">
          The project foundation is ready. Student features, administrator review, and passport generation will be built here.
        </p>
      </section>

      <section class="grid gap-4 md:grid-cols-3" aria-label="Planned modules">
        <Card v-for="module in modules" :key="module.title" class="bg-card/80">
          <CardHeader>
            <div class="mb-6 flex justify-between">
              <span class="text-xs font-medium uppercase tracking-widest text-muted-foreground">Module</span>
              <Badge variant="outline">Planned</Badge>
            </div>
            <CardTitle>{{ module.title }}</CardTitle>
            <CardDescription>{{ module.description }}</CardDescription>
          </CardHeader>
        </Card>
      </section>

      <Card class="mt-8">
        <CardContent class="flex flex-col gap-4 pt-6 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="font-medium">Backend API</p>
            <p class="mt-1 text-sm text-muted-foreground">
              <span v-if="apiStatus === 'checking'">Checking the local API...</span>
              <span v-else-if="apiStatus === 'online'">Connected to the local PHP API.</span>
              <span v-else>API is offline. Start the PHP server to test the connection.</span>
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Badge :variant="apiStatus === 'online' ? 'default' : 'secondary'">
              {{ apiStatus === 'online' ? 'Online' : apiStatus === 'checking' ? 'Checking' : 'Offline' }}
            </Badge>
            <Button variant="outline" size="sm" @click="checkApi">Check again</Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </main>
</template>

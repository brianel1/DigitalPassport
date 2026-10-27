<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Award, ArrowUpRight, CheckCircle2, ClipboardCheck, FolderKanban, GraduationCap, IdCard, Sparkles, UserRound } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import BrandLogo from '@/components/brand/BrandLogo.vue'

const features = [
  {
    icon: UserRound,
    title: 'Student Profile',
    description: 'Create a centralized profile with your programme details, bio, and digital identity.',
  },
  {
    icon: Sparkles,
    title: 'Skills Management',
    description: 'Record and categorize digital skills with proficiency levels and linked evidence.',
  },
  {
    icon: ClipboardCheck,
    title: 'Skill Assessments',
    description: 'Complete objective assessments to validate your skills with automatic scoring.',
  },
  {
    icon: Award,
    title: 'Certifications',
    description: 'Upload certificates with verification by administrators for credibility.',
  },
  {
    icon: FolderKanban,
    title: 'Project Portfolio',
    description: 'Showcase completed projects with technologies used, links, and evidence files.',
  },
  {
    icon: IdCard,
    title: 'Digital Passport',
    description: 'Generate a consolidated passport view with downloadable PDF for career events.',
  },
]

const stats = [
  { value: '7', label: 'Modules' },
  { value: '∞', label: 'Skills to Track' },
  { value: 'PDF', label: 'Passport Export' },
  { value: CheckCircle2, label: 'Admin Verified' },
]
</script>

<template>
  <div class="landing-theme min-h-screen bg-background text-foreground">
    <!-- Navigation -->
    <nav class="fixed top-0 z-50 w-full border-b border-border/50 bg-background/80 backdrop-blur-lg">
      <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
        <RouterLink to="/" class="text-primary"><BrandLogo size="sm" /></RouterLink>
        <div class="flex items-center gap-3">
          <Button variant="ghost" as-child>
            <RouterLink to="/login">Log in</RouterLink>
          </Button>
          <Button as-child>
            <RouterLink to="/register">Get started</RouterLink>
          </Button>
        </div>
      </div>
    </nav>

    <!-- Hero -->
    <section class="relative overflow-hidden bg-[var(--landing-sage)] pt-16">
      <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-primary/15 via-transparent to-transparent"></div>
      <div class="absolute -left-32 top-32 size-96 rounded-full bg-primary/15 blur-3xl"></div>
      <div class="absolute -right-32 top-12 size-[28rem] rounded-full bg-chart-4/12 blur-3xl"></div>
      <div class="absolute bottom-[-14rem] left-1/3 size-[26rem] rounded-full bg-chart-5/10 blur-3xl"></div>
      <div class="relative mx-auto max-w-6xl px-6 py-24 sm:py-32 lg:py-40">
        <div class="max-w-3xl">
          <Badge variant="secondary" class="mb-6">
            <GraduationCap class="mr-1.5 size-4" /> For University Students
          </Badge>
          <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
            Your digital skills,
            <span class="block text-primary">verified and portable.</span>
          </h1>
          <p class="mt-6 max-w-2xl text-lg leading-relaxed text-muted-foreground">
            DigitalPassport centralizes your skills, projects, certifications, and assessment results into one verified profile. Present your competencies with confidence.
          </p>
          <div class="mt-10 flex flex-wrap gap-4">
            <Button size="lg" as-child>
              <RouterLink to="/register">Create your passport <ArrowUpRight class="ml-1 size-4" /></RouterLink>
            </Button>
            <Button size="lg" variant="outline" class="text-foreground" as-child>
              <RouterLink to="/login">Sign in</RouterLink>
            </Button>
          </div>
        </div>

        <!-- Stats Row -->
        <div class="mt-20 grid grid-cols-2 gap-4 sm:grid-cols-4">
          <div
            v-for="stat in stats"
            :key="stat.label"
            class="rounded-xl border border-border bg-card/50 p-6 text-center backdrop-blur-sm"
          >
            <component v-if="typeof stat.value !== 'string'" :is="stat.value" class="mx-auto size-7 text-primary" />
            <p v-else class="text-2xl font-bold text-primary">{{ stat.value }}</p>
            <p class="mt-1 text-sm text-muted-foreground">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="border-t border-border bg-muted/30 py-24">
      <div class="mx-auto max-w-6xl px-6">
        <div class="mb-16 text-center">
          <Badge variant="outline" class="mb-4">Features</Badge>
          <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
            Everything you need in one place
          </h2>
          <p class="mt-4 text-lg text-muted-foreground">
            Seven integrated modules to document, verify, and present your digital skills.
          </p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <Card v-for="feature in features" :key="feature.title" class="border-border/50 bg-card/80 transition-shadow hover:shadow-lg">
            <CardHeader>
              <div class="mb-3 flex size-12 items-center justify-center rounded-lg bg-primary/10 text-2xl">
                <component :is="feature.icon" class="size-6 text-primary" />
              </div>
              <CardTitle class="text-lg">{{ feature.title }}</CardTitle>
              <CardDescription>{{ feature.description }}</CardDescription>
            </CardHeader>
          </Card>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="bg-[var(--landing-sage-deep)] py-24">
      <div class="mx-auto max-w-6xl px-6 text-center">
        <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
          Ready to build your digital passport?
        </h2>
        <p class="mx-auto mt-4 max-w-xl text-lg text-muted-foreground">
          Start documenting your skills today. It only takes a minute to create your profile.
        </p>
        <div class="mt-10">
          <Button size="lg" as-child>
              <RouterLink to="/register">Get started for free <ArrowUpRight class="ml-1 size-4" /></RouterLink>
          </Button>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-border py-8">
      <div class="mx-auto max-w-6xl px-6 text-center text-sm text-muted-foreground">
        <p>© 2026 DigitalPassport | BITU 3923 Workshop 2 Project, UTeM</p>
      </div>
    </footer>
  </div>
</template>

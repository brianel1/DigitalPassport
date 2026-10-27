<script setup lang="ts">
import { RouterLink } from 'vue-router'
import {
  ArrowUpRight,
  ShieldCheck,
  BarChart3,
  FileText,
  Sparkles,
  BrainCircuit,
  ChevronRight,
} from '@lucide/vue'
import { Button } from '@/components/ui/button'
import BrandLogo from '@/components/brand/BrandLogo.vue'

const highlights = [
  {
    icon: ShieldCheck,
    title: 'Verified Skills',
    description: 'Admin-verified credentials that employers can trust. Every skill backed by evidence.',
    color: 'from-emerald-500/20 to-emerald-500/5',
    iconColor: 'text-emerald-400',
    borderColor: 'border-emerald-500/20',
  },
  {
    icon: BarChart3,
    title: 'Skill Assessments',
    description: 'Objective quizzes with automatic scoring to validate your digital competencies.',
    color: 'from-cyan-500/20 to-cyan-500/5',
    iconColor: 'text-cyan-400',
    borderColor: 'border-cyan-500/20',
  },
  {
    icon: BrainCircuit,
    title: 'AI Recommendations',
    description: 'Personalised learning paths powered by Qwen3 AI to help you improve weak areas.',
    color: 'from-violet-500/20 to-violet-500/5',
    iconColor: 'text-violet-400',
    borderColor: 'border-violet-500/20',
  },
  {
    icon: FileText,
    title: 'Downloadable Passport',
    description: 'Export a PDF passport summarising your verified skills, projects, and certifications.',
    color: 'from-amber-500/20 to-amber-500/5',
    iconColor: 'text-amber-400',
    borderColor: 'border-amber-500/20',
  },
]

const steps = [
  { step: '01', title: 'Complete Assessment', description: 'Take a digital skills quiz in categories like PHP, JavaScript, or MySQL.' },
  { step: '02', title: 'AI Analyses Results', description: 'Ollama + Qwen3 processes your score to identify weak areas.' },
  { step: '03', title: 'Get Recommendations', description: 'Receive personalised learning topics, activities, and a step-by-step path.' },
]
</script>

<template>
  <div class="landing-page min-h-screen">
    <!-- Navigation -->
    <nav class="fixed top-0 z-50 w-full border-b border-white/[0.06] bg-[#080d1c]/80 backdrop-blur-xl">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
        <RouterLink to="/" class="text-white">
          <BrandLogo size="sm" />
        </RouterLink>
        <div class="flex items-center gap-3">
          <Button variant="ghost" class="text-slate-300 hover:bg-white/5 hover:text-white" as-child>
            <RouterLink to="/login">Log in</RouterLink>
          </Button>
          <Button class="bg-indigo-600 text-white shadow-lg shadow-indigo-500/25 hover:bg-indigo-500" as-child>
            <RouterLink to="/register">Get started</RouterLink>
          </Button>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-[#080d1c] pt-16">
      <!-- Background effects -->
      <div class="absolute inset-0">
        <div class="absolute left-1/4 top-0 h-[600px] w-[600px] rounded-full bg-indigo-600/15 blur-[128px]"></div>
        <div class="absolute right-0 top-1/4 h-[400px] w-[500px] rounded-full bg-violet-600/10 blur-[100px]"></div>
        <div class="absolute bottom-0 left-0 h-[300px] w-[400px] rounded-full bg-cyan-600/8 blur-[100px]"></div>
        <!-- Grid pattern -->
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:64px_64px]"></div>
      </div>

      <div class="relative mx-auto grid max-w-7xl gap-12 px-6 py-20 sm:py-28 lg:grid-cols-2 lg:gap-8 lg:py-36">
        <!-- Left: Text content -->
        <div class="flex flex-col justify-center">
          <div class="mb-6 inline-flex w-fit items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-4 py-1.5 text-sm text-indigo-300">
            <Sparkles class="size-4" />
            <span>For University Students</span>
          </div>

          <h1 class="text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-[3.5rem]">
            Your digital skills,
            <span class="bg-gradient-to-r from-indigo-400 via-violet-400 to-cyan-400 bg-clip-text text-transparent">
              verified and portable.
            </span>
          </h1>

          <p class="mt-6 max-w-lg text-lg leading-relaxed text-slate-400">
            DigitalPassport centralises your skills, projects, certifications, and assessment results into one verified profile. Present your competencies with confidence.
          </p>

          <div class="mt-10 flex flex-wrap gap-4">
            <Button size="lg" class="bg-indigo-600 px-8 text-white shadow-xl shadow-indigo-500/25 transition-all hover:bg-indigo-500 hover:shadow-indigo-500/40" as-child>
              <RouterLink to="/register">
                Create your passport
                <ArrowUpRight class="ml-1.5 size-4" />
              </RouterLink>
            </Button>
            <Button size="lg" variant="ghost" class="border border-white/10 text-slate-300 hover:bg-white/5 hover:text-white" as-child>
              <RouterLink to="/login">Sign in</RouterLink>
            </Button>
          </div>

          <!-- Mini stats -->
          <div class="mt-14 flex gap-10">
            <div>
              <p class="text-2xl font-bold text-white">PDF</p>
              <p class="mt-1 text-sm text-slate-500">Passport Export</p>
            </div>
            <div class="h-10 w-px bg-white/10"></div>
            <div>
              <p class="text-2xl font-bold text-white">AI</p>
              <p class="mt-1 text-sm text-slate-500">Learning Paths</p>
            </div>
            <div class="h-10 w-px bg-white/10"></div>
            <div>
              <p class="flex items-center gap-1 text-2xl font-bold text-white">
                <ShieldCheck class="size-5 text-emerald-400" />
                Verified
              </p>
              <p class="mt-1 text-sm text-slate-500">Admin Approved</p>
            </div>
          </div>
        </div>

        <!-- Right: Hero illustration -->
        <div class="flex items-center justify-center lg:justify-end">
          <div class="relative">
            <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-indigo-500/20 via-violet-500/10 to-cyan-500/20 blur-2xl"></div>
            <img
              src="/hero-illustration.jpg"
              alt="DigitalPassport – Verified digital skills for university students"
              class="relative w-full max-w-[540px] rounded-2xl border border-white/10 shadow-2xl shadow-indigo-500/10"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="relative bg-[#060a17] py-24">
      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.015)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.015)_1px,transparent_1px)] bg-[size:64px_64px]"></div>
      <div class="relative mx-auto max-w-7xl px-6">
        <div class="mb-16 text-center">
          <p class="mb-3 text-sm font-medium uppercase tracking-widest text-indigo-400">Key Features</p>
          <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
            Everything you need in one place
          </h2>
          <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-400">
            Document, verify, and present your digital skills with a platform designed for university students.
          </p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="feature in highlights"
            :key="feature.title"
            class="group relative overflow-hidden rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-white/10 hover:bg-white/[0.04]"
          >
            <!-- Gradient bg on hover -->
            <div :class="['absolute inset-0 bg-gradient-to-br opacity-0 transition-opacity duration-300 group-hover:opacity-100', feature.color]"></div>

            <div class="relative">
              <div :class="['mb-4 flex size-12 items-center justify-center rounded-xl border bg-white/5', feature.borderColor]">
                <component :is="feature.icon" :class="['size-6', feature.iconColor]" />
              </div>
              <h3 class="mb-2 text-lg font-semibold text-white">{{ feature.title }}</h3>
              <p class="text-sm leading-relaxed text-slate-400">{{ feature.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- AI Recommendation Section -->
    <section class="relative overflow-hidden bg-[#080d1c] py-24">
      <div class="absolute right-0 top-0 h-[500px] w-[500px] rounded-full bg-violet-600/8 blur-[120px]"></div>
      <div class="absolute bottom-0 left-0 h-[400px] w-[400px] rounded-full bg-indigo-600/8 blur-[100px]"></div>

      <div class="relative mx-auto max-w-7xl px-6">
        <div class="grid gap-16 lg:grid-cols-2 lg:items-center">
          <!-- Left: Content -->
          <div>
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-violet-500/20 bg-violet-500/10 px-4 py-1.5 text-sm text-violet-300">
              <BrainCircuit class="size-4" />
              <span>AI-Powered</span>
            </div>

            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
              Smart learning recommendations
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-400">
              After each assessment, our AI analyses your results and generates a personalised learning path to help you improve your weakest areas.
            </p>

            <div class="mt-10 space-y-6">
              <div v-for="step in steps" :key="step.step" class="flex gap-5">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-violet-500/20 bg-violet-500/10 text-sm font-bold text-violet-300">
                  {{ step.step }}
                </div>
                <div>
                  <h4 class="font-semibold text-white">{{ step.title }}</h4>
                  <p class="mt-1 text-sm text-slate-400">{{ step.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Example card -->
          <div class="relative">
            <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-violet-500/10 to-indigo-500/10 blur-xl"></div>
            <div class="relative overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0d1224]/90 p-8 backdrop-blur-xl">
              <!-- Header -->
              <div class="mb-6 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-violet-500/10">
                  <BrainCircuit class="size-5 text-violet-400" />
                </div>
                <div>
                  <p class="font-semibold text-white">AI Recommendation</p>
                  <p class="text-xs text-slate-500">JavaScript Assessment</p>
                </div>
                <div class="ml-auto rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-400">
                  Score: 40%
                </div>
              </div>

              <!-- Recommendations -->
              <div class="space-y-3">
                <p class="text-sm font-medium text-slate-300">Recommended learning path:</p>
                <div class="rounded-xl border border-emerald-500/10 bg-emerald-500/5 p-4">
                  <div class="flex items-start gap-3">
                    <ChevronRight class="mt-0.5 size-4 shrink-0 text-emerald-400" />
                    <div>
                      <p class="text-sm font-medium text-white">Learn JavaScript fundamentals</p>
                      <p class="mt-1 text-xs text-slate-500">Variables, data types, and control flow</p>
                    </div>
                  </div>
                </div>
                <div class="rounded-xl border border-cyan-500/10 bg-cyan-500/5 p-4">
                  <div class="flex items-start gap-3">
                    <ChevronRight class="mt-0.5 size-4 shrink-0 text-cyan-400" />
                    <div>
                      <p class="text-sm font-medium text-white">Practise functions and DOM manipulation</p>
                      <p class="mt-1 text-xs text-slate-500">Event handling, selectors, and dynamic content</p>
                    </div>
                  </div>
                </div>
                <div class="rounded-xl border border-violet-500/10 bg-violet-500/5 p-4">
                  <div class="flex items-start gap-3">
                    <ChevronRight class="mt-0.5 size-4 shrink-0 text-violet-400" />
                    <div>
                      <p class="text-sm font-medium text-white">Build a simple JavaScript project</p>
                      <p class="mt-1 text-xs text-slate-500">Apply your skills in a real-world scenario</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="mt-6 flex items-center gap-2 rounded-lg bg-white/[0.03] px-3 py-2 text-xs text-slate-500">
                <Sparkles class="size-3 text-violet-400" />
                Powered by Ollama + Qwen3 • Guidance only
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-violet-600 to-indigo-700 py-24">
      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.05)_1px,transparent_1px)] bg-[size:64px_64px]"></div>
      <div class="absolute right-0 top-0 h-[400px] w-[400px] rounded-full bg-white/5 blur-[100px]"></div>

      <div class="relative mx-auto max-w-7xl px-6 text-center">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
          Ready to build your digital passport?
        </h2>
        <p class="mx-auto mt-4 max-w-xl text-lg text-indigo-100/80">
          Start documenting your skills today. It only takes a minute to create your profile.
        </p>
        <div class="mt-10">
          <Button size="lg" class="bg-white px-10 text-indigo-700 shadow-xl shadow-black/20 transition-all hover:bg-indigo-50 hover:shadow-black/30" as-child>
            <RouterLink to="/register">
              Get started for free
              <ArrowUpRight class="ml-1.5 size-4" />
            </RouterLink>
          </Button>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/[0.06] bg-[#060a17] py-8">
      <div class="mx-auto max-w-7xl px-6 text-center text-sm text-slate-500">
        <p>© 2026 DigitalPassport | BITU 3923 Workshop 2 Project, UTeM</p>
      </div>
    </footer>
  </div>
</template>

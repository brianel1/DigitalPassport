<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowRight, LockKeyhole, ShieldCheck, Award, BrainCircuit, ArrowLeft } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuth } from '@/composables/useAuth'
import BrandLogo from '@/components/brand/BrandLogo.vue'

const router = useRouter()
const { login, user } = useAuth()
const email = ref('elber@student.utem.edu.my')
const password = ref('password123')
const error = ref('')
const submitting = ref(false)

function fillAdministratorAccess() {
  email.value = 'admin@utem.edu.my'
  password.value = 'admin123'
  error.value = ''
}

function submit() {
  submitting.value = true
  error.value = ''
  const success = login(email.value.trim(), password.value)
  submitting.value = false

  if (!success) {
    error.value = 'The email or password is incorrect.'
    return
  }

  router.push(user.value?.role === 'admin' ? '/admin/dashboard' : '/student/dashboard')
}
</script>

<template>
  <div class="landing-page grid min-h-screen bg-[#080d1c] text-white lg:grid-cols-[1.05fr_0.95fr]">
    <!-- Left Hero Column -->
    <section class="relative hidden overflow-hidden border-r border-white/[0.08] bg-[#060a17] p-12 lg:flex lg:flex-col lg:justify-between">
      <!-- Ambient background glow effects -->
      <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-20 -top-20 size-[500px] rounded-full bg-indigo-600/15 blur-[120px]"></div>
        <div class="absolute -right-20 bottom-10 size-[450px] rounded-full bg-violet-600/15 blur-[120px]"></div>
        <!-- Flowing curve line art -->
        <svg class="absolute inset-0 size-full opacity-[0.06]" viewBox="0 0 800 800" fill="none">
          <circle cx="400" cy="400" r="300" stroke="currentColor" stroke-width="1.2" class="text-indigo-400" />
          <circle cx="400" cy="400" r="220" stroke="currentColor" stroke-width="0.8" class="text-violet-400" />
          <path d="M100 700 C250 550 550 650 700 450" stroke="currentColor" stroke-width="1" class="text-cyan-400" />
        </svg>
      </div>

      <!-- Top Branding -->
      <div class="relative z-10">
        <RouterLink to="/" class="inline-flex items-center text-white transition-opacity hover:opacity-90">
          <BrandLogo size="md" />
        </RouterLink>
      </div>

      <!-- Middle Value Proposition -->
      <div class="relative z-10 max-w-lg">
        <div class="mb-4 flex items-center gap-2.5">
          <span class="h-3.5 w-1 rounded-full bg-gradient-to-b from-indigo-400 to-cyan-400"></span>
          <span class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-300">UTeM Digital Skills</span>
        </div>
        <h1 class="text-4xl font-bold leading-tight tracking-tight text-white xl:text-5xl">
          Build a verified record of
          <span class="bg-gradient-to-r from-indigo-400 via-violet-400 to-cyan-400 bg-clip-text text-transparent">
            what you can do.
          </span>
        </h1>
        <p class="mt-5 text-base leading-relaxed text-slate-400">
          Centralise your skills, projects, certifications, and assessment results together into one verifiable professional passport.
        </p>

        <!-- Feature Points -->
        <div class="mt-8 space-y-4">
          <div class="flex items-center gap-3.5 text-sm text-slate-300">
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-emerald-500/20 bg-emerald-500/10 text-emerald-400">
              <ShieldCheck class="size-4" />
            </div>
            <span>Faculty & administrator-verified achievements</span>
          </div>
          <div class="flex items-center gap-3.5 text-sm text-slate-300">
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-violet-500/20 bg-violet-500/10 text-violet-400">
              <BrainCircuit class="size-4" />
            </div>
            <span>Personalised AI diagnostic recommendations</span>
          </div>
          <div class="flex items-center gap-3.5 text-sm text-slate-300">
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-indigo-500/20 bg-indigo-500/10 text-indigo-400">
              <Award class="size-4" />
            </div>
            <span>Exportable, accredited digital competency PDF</span>
          </div>
        </div>
      </div>

      <!-- Bottom Trust Indicator -->
      <div class="relative z-10 flex items-center gap-2.5 text-xs text-slate-400">
        <ShieldCheck class="size-4 text-emerald-400" />
        <span>Universiti Teknikal Malaysia Melaka • Student Competency System</span>
      </div>
    </section>

    <!-- Right Form Column -->
    <main class="relative flex min-h-screen items-center justify-center bg-[#080d1c] px-6 py-12">
      <!-- Background subtle glow -->
      <div class="pointer-events-none absolute right-0 top-1/4 size-[400px] rounded-full bg-indigo-600/10 blur-[130px]"></div>

      <!-- Top bar for navigation -->
      <div class="absolute left-6 top-6 lg:left-auto lg:right-8">
        <RouterLink
          to="/"
          class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 transition-colors hover:text-white"
        >
          <ArrowLeft class="size-3.5" /> Back to home
        </RouterLink>
      </div>

      <div class="w-full max-w-md">
        <!-- Mobile Logo -->
        <div class="mb-8 flex justify-center lg:hidden">
          <RouterLink to="/" class="text-white">
            <BrandLogo size="md" />
          </RouterLink>
        </div>

        <!-- Login Card -->
        <Card class="border border-white/[0.08] bg-[#0d1224]/90 p-2 shadow-2xl shadow-indigo-950/40 backdrop-blur-xl">
          <CardHeader class="space-y-3 pb-6">
            <div class="flex size-11 items-center justify-center rounded-xl border border-indigo-500/20 bg-indigo-500/10 text-indigo-400">
              <LockKeyhole class="size-5" />
            </div>
            <div>
              <CardTitle class="text-2xl font-bold text-white">Welcome back</CardTitle>
              <CardDescription class="mt-1 text-slate-400">Sign in to continue to your DigitalPassport.</CardDescription>
            </div>
          </CardHeader>

          <CardContent class="space-y-6">
            <form class="space-y-4" @submit.prevent="submit">
              <div class="space-y-2">
                <Label for="email" class="text-sm font-medium text-slate-200">Email address</Label>
                <Input
                  id="email"
                  v-model="email"
                  type="email"
                  autocomplete="email"
                  required
                  placeholder="student@student.utem.edu.my"
                  class="h-10 border-white/10 bg-white/[0.04] text-white placeholder:text-slate-500 focus-visible:border-indigo-500 focus-visible:ring-indigo-500/20"
                />
              </div>

              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <Label for="password" class="text-sm font-medium text-slate-200">Password</Label>
                </div>
                <Input
                  id="password"
                  v-model="password"
                  type="password"
                  autocomplete="current-password"
                  required
                  placeholder="••••••••"
                  class="h-10 border-white/10 bg-white/[0.04] text-white placeholder:text-slate-500 focus-visible:border-indigo-500 focus-visible:ring-indigo-500/20"
                />
              </div>

              <p v-if="error" class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-400">
                {{ error }}
              </p>

              <Button
                class="w-full bg-indigo-600 font-medium text-white shadow-lg shadow-indigo-600/25 transition-all hover:bg-indigo-500 hover:shadow-indigo-600/40"
                size="lg"
                type="submit"
                :disabled="submitting"
              >
                Sign in <ArrowRight class="ml-1.5 size-4" />
              </Button>
            </form>

            <div class="space-y-3 pt-2 text-center text-sm">
              <button
                type="button"
                class="inline-block text-xs font-medium text-indigo-400 transition-colors hover:text-indigo-300 hover:underline"
                @click="fillAdministratorAccess"
              >
                Quick fill: Administrator demo access
              </button>

              <div class="border-t border-white/[0.06] pt-4 text-slate-400">
                New student?
                <RouterLink to="/register" class="font-medium text-indigo-400 transition-colors hover:text-indigo-300 hover:underline">
                  Create an account
                </RouterLink>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </main>
  </div>
</template>

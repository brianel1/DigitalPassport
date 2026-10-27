<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowRight, LockKeyhole, ShieldCheck } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuth } from '@/composables/useAuth'
import BrandLogo from '@/components/brand/BrandLogo.vue'
import ThemeToggle from '@/components/shared/ThemeToggle.vue'

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
  <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
    <section class="relative hidden overflow-hidden border-r border-border bg-primary p-12 text-primary-foreground lg:flex lg:flex-col lg:justify-between">
      <div class="absolute inset-0 opacity-25 [background-image:radial-gradient(circle_at_20%_10%,white_0,transparent_28%),radial-gradient(circle_at_80%_90%,white_0,transparent_22%)]"></div>
      <RouterLink to="/" class="relative text-primary-foreground"><BrandLogo size="sm" /></RouterLink>
      <div class="relative max-w-xl">
        <p class="mb-5 text-sm font-semibold uppercase tracking-[0.22em] text-primary-foreground/70">UTeM Digital Skills</p>
        <h1 class="text-4xl font-semibold leading-tight">Build a verified record of what you can do.</h1>
        <p class="mt-5 max-w-lg text-lg leading-relaxed text-primary-foreground/75">
          Keep your skills, projects, certifications, and assessment results together in one professional passport.
        </p>
      </div>
      <div class="relative flex items-center gap-3 text-sm text-primary-foreground/75">
        <ShieldCheck class="size-5" /> Evidence-backed achievements with administrator verification
      </div>
    </section>

    <main class="relative flex items-center justify-center bg-background px-6 py-12">
      <div class="absolute right-6 top-6">
        <ThemeToggle />
      </div>
      <div class="w-full max-w-md">
        <RouterLink to="/" class="mb-10 text-primary lg:hidden"><BrandLogo size="sm" /></RouterLink>

        <Card class="border-border/70 shadow-xl shadow-primary/5">
          <CardHeader class="space-y-3">
            <div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
              <LockKeyhole class="size-5" />
            </div>
            <CardTitle class="text-2xl">Welcome back</CardTitle>
            <CardDescription>Sign in to continue to your DigitalPassport.</CardDescription>
          </CardHeader>
          <CardContent>
            <form class="space-y-5" @submit.prevent="submit">
              <div class="space-y-2">
                <Label for="email">Email address</Label>
                <Input id="email" v-model="email" type="email" autocomplete="email" required />
              </div>
              <div class="space-y-2">
                <Label for="password">Password</Label>
                <Input id="password" v-model="password" type="password" autocomplete="current-password" required />
              </div>
              <p v-if="error" class="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                {{ error }}
              </p>
              <Button class="w-full" size="lg" type="submit" :disabled="submitting">
                Sign in <ArrowRight class="size-4" />
              </Button>
            </form>

            <div class="mt-5 flex flex-col gap-3 text-center text-sm text-muted-foreground">
              <button type="button" class="font-medium text-primary hover:underline" @click="fillAdministratorAccess">
                Use administrator access
              </button>
              <p>New student? <RouterLink to="/register" class="font-medium text-foreground hover:underline">Create an account</RouterLink></p>
            </div>
          </CardContent>
        </Card>
      </div>
    </main>
  </div>
</template>

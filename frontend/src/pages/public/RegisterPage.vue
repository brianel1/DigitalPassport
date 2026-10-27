<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowRight, GraduationCap } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuth } from '@/composables/useAuth'
import BrandLogo from '@/components/brand/BrandLogo.vue'
import ThemeToggle from '@/components/shared/ThemeToggle.vue'

const router = useRouter()
const { register } = useAuth()
const error = ref('')
const form = reactive({
  name: '',
  email: '',
  password: '',
  programme: '',
  matricNo: '',
})

function submit() {
  error.value = ''
  if (!register(form)) {
    error.value = 'An account already exists for this email address.'
    return
  }
  router.push('/student/dashboard')
}
</script>

<template>
  <main class="relative min-h-screen bg-[radial-gradient(circle_at_top,_var(--color-primary)/8%,_transparent_35%)] px-6 py-10">
    <div class="absolute right-6 top-6">
      <ThemeToggle />
    </div>
    <div class="mx-auto w-full max-w-2xl">
      <RouterLink to="/" class="mb-8 flex justify-center text-primary"><BrandLogo size="sm" /></RouterLink>

      <Card class="shadow-xl shadow-primary/5">
        <CardHeader class="items-center text-center">
          <div class="mb-2 flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <GraduationCap class="size-6" />
          </div>
          <CardTitle class="text-2xl">Create your student account</CardTitle>
          <CardDescription>Start building your verified digital skills profile.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="submit">
            <div class="space-y-2 sm:col-span-2">
              <Label for="name">Full name</Label>
              <Input id="name" v-model="form.name" required />
            </div>
            <div class="space-y-2">
              <Label for="matric">Matric number</Label>
              <Input id="matric" v-model="form.matricNo" required />
            </div>
            <div class="space-y-2">
              <Label for="programme">Programme</Label>
              <Input id="programme" v-model="form.programme" placeholder="e.g. BITC" required />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="email">University email</Label>
              <Input id="email" v-model="form.email" type="email" required />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="password">Password</Label>
              <Input id="password" v-model="form.password" type="password" minlength="8" required />
            </div>
            <p v-if="error" class="sm:col-span-2 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
              {{ error }}
            </p>
            <Button class="sm:col-span-2" size="lg" type="submit">
              Create account <ArrowRight class="size-4" />
            </Button>
          </form>
          <p class="mt-6 text-center text-sm text-muted-foreground">
            Already registered? <RouterLink to="/login" class="font-medium text-foreground hover:underline">Sign in</RouterLink>
          </p>
        </CardContent>
      </Card>
    </div>
  </main>
</template>

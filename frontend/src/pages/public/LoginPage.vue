<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ArrowRight, Eye, EyeOff, Loader2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuth } from '@/composables/useAuth'
import AuthShell from '@/components/layout/AuthShell.vue'
import { ApiError } from '@/lib/api'
const router = useRouter()
const route = useRoute()
const { login, user } = useAuth()
const email = ref('')
const password = ref('')
const visible = ref(false)
const error = ref('')
const submitting = ref(false)
async function submit() {
  submitting.value = true
  error.value = ''
  try {
    await login(email.value.trim(), password.value)
    const prefix = user.value?.role === 'admin' ? '/admin/' : '/student/'
    const destination = typeof route.query.redirect === 'string' && route.query.redirect.startsWith(prefix) ? route.query.redirect : prefix + 'dashboard'
    router.push(destination)
  } catch (cause) { error.value = cause instanceof ApiError ? cause.message : 'The server could not be reached.' }
  finally { submitting.value = false }
}
</script>
<template>
  <AuthShell>
    <form class="space-y-5" @submit.prevent="submit">
      <p v-if="route.query.reason==='expired'" role="status" class="rounded-lg border bg-accent p-3 text-sm">Your session has ended. Sign in again to continue.</p>
      <div class="space-y-2"><Label for="email">Email address</Label><Input id="email" v-model="email" type="email" autocomplete="email" required placeholder="you@student.utem.edu.my" class="h-12 bg-card" /></div>
      <div class="space-y-2"><Label for="password">Password</Label><div class="relative"><Input id="password" v-model="password" :type="visible ? 'text' : 'password'" autocomplete="current-password" required placeholder="Enter your password" class="h-12 bg-card pr-12" /><button type="button" :aria-label="visible ? 'Hide password' : 'Show password'" :aria-pressed="visible" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-muted-foreground" @click="visible = !visible"><EyeOff v-if="visible" class="size-4" /><Eye v-else class="size-4" /></button></div></div>
      <p v-if="error" role="alert" class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">{{ error }}</p>
      <Button type="submit" size="lg" class="w-full" :disabled="submitting"><Loader2 v-if="submitting" class="size-4 animate-spin" />{{ submitting ? 'Signing in…' : 'Sign in' }}<ArrowRight v-if="!submitting" class="size-4" /></Button>
    </form>
  </AuthShell>
</template>

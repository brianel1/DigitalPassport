<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRight, Eye, EyeOff, Loader2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuth } from '@/composables/useAuth'
import AuthShell from '@/components/layout/AuthShell.vue'
import { ApiError } from '@/lib/api'
const router = useRouter()
const { register } = useAuth()
const error = ref('')
const visible = ref(false)
const submitting = ref(false)
const form = reactive({ name: '', email: '', password: '', programme: '', matricNo: '' })
async function submit() {
  error.value = ''; submitting.value = true
  try { await register(form); router.push('/student/dashboard') }
  catch (cause) { error.value = cause instanceof ApiError ? cause.message : 'The server could not be reached.' }
  finally { submitting.value = false }
}
</script>
<template>
  <AuthShell register>
    <form class="space-y-5" @submit.prevent="submit">
      <div class="space-y-2"><Label for="name">Full name</Label><Input id="name" v-model="form.name" autocomplete="name" required placeholder="Your full name" class="h-11 bg-card" /></div>
      <div class="grid gap-4 sm:grid-cols-2"><div class="space-y-2"><Label for="matric">Matric number</Label><Input id="matric" v-model="form.matricNo" required placeholder="e.g. B032110001" class="h-11 bg-card" /></div><div class="space-y-2"><Label for="programme">Programme</Label><Input id="programme" v-model="form.programme" required placeholder="e.g. BITC" class="h-11 bg-card" /></div></div>
      <div class="space-y-2"><Label for="email">Email address</Label><Input id="email" v-model="form.email" type="email" autocomplete="email" required placeholder="you@student.utem.edu.my" class="h-11 bg-card" /></div>
      <div class="space-y-2"><Label for="password">Password</Label><div class="relative"><Input id="password" v-model="form.password" :type="visible ? 'text' : 'password'" autocomplete="new-password" minlength="8" required aria-describedby="password-help" placeholder="Create a password" class="h-11 bg-card pr-12" /><button type="button" :aria-label="visible ? 'Hide password' : 'Show password'" :aria-pressed="visible" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-muted-foreground" @click="visible = !visible"><EyeOff v-if="visible" class="size-4" /><Eye v-else class="size-4" /></button></div><p id="password-help" class="text-xs text-muted-foreground">Use at least 8 characters.</p></div>
      <p v-if="error" role="alert" class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">{{ error }}</p>
      <Button type="submit" size="lg" class="w-full" :disabled="submitting"><Loader2 v-if="submitting" class="size-4 animate-spin" />{{ submitting ? 'Creating account…' : 'Create account' }}<ArrowRight v-if="!submitting" class="size-4" /></Button>
    </form>
  </AuthShell>
</template>

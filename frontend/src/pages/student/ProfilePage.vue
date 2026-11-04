<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { BadgeCheck, Code2, Globe2, Mail, MapPin, Phone, Save, UserRound } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useAuth } from '@/composables/useAuth'
import { ApiError, getStudentProfile, updateStudentProfile } from '@/lib/api'

const { user, refreshUser } = useAuth()
const saved = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const form = reactive({
  name: user.value?.name || '',
  email: user.value?.email || '',
  matricNo: user.value?.matricNo || '',
  programme: user.value?.programme || '',
  headline: '',
  bio: '',
  phone: '',
  location: '',
  linkedinUrl: '',
  githubUrl: '',
  expectedGraduationDate: '',
})

onMounted(async () => {
  try {
    const profile = await getStudentProfile()
    Object.assign(form, {
      name: profile.name,
      email: profile.email,
      matricNo: profile.matricNo ?? '',
      programme: profile.programme ?? '',
      headline: profile.headline ?? '',
      bio: profile.bio ?? '',
      phone: profile.phone ?? '',
      location: profile.location ?? '',
      linkedinUrl: profile.linkedinUrl ?? '',
      githubUrl: profile.githubUrl ?? '',
      expectedGraduationDate: profile.expectedGraduationDate ?? '',
    })
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The profile could not be loaded.'
  } finally {
    loading.value = false
  }
})

async function save() {
  submitting.value = true
  error.value = ''
  try {
    const profile = await updateStudentProfile(form)
    Object.assign(form, {
      name: profile.name,
      email: profile.email,
      matricNo: profile.matricNo ?? '',
      programme: profile.programme ?? '',
      headline: profile.headline ?? '',
      bio: profile.bio ?? '',
      phone: profile.phone ?? '',
      location: profile.location ?? '',
      linkedinUrl: profile.linkedinUrl ?? '',
      githubUrl: profile.githubUrl ?? '',
      expectedGraduationDate: profile.expectedGraduationDate ?? '',
    })
    await refreshUser()
    saved.value = true
    window.setTimeout(() => { saved.value = false }, 2500)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The profile could not be saved.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Student profile</h1>
      <p class="mt-1 text-muted-foreground">Manage the identity shown on your DigitalPassport.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
      <Card>
        <CardContent class="flex flex-col items-center p-7 text-center">
          <div class="flex size-24 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary/60 text-3xl font-semibold text-primary-foreground shadow-lg shadow-primary/20">
            {{ form.name.split(' ').map((part) => part[0]).join('').slice(0, 2) }}
          </div>
          <h2 class="mt-5 text-xl font-semibold">{{ form.name }}</h2>
          <p class="mt-1 text-sm text-muted-foreground">{{ form.matricNo }}</p>
          <div class="mt-5 flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
            <BadgeCheck class="size-4" /> Active student
          </div>
          <div class="mt-6 w-full space-y-3 border-t border-border pt-5 text-left text-sm">
            <div class="flex items-center gap-3 text-muted-foreground"><Mail class="size-4" /> {{ form.email }}</div>
            <div class="flex items-center gap-3 text-muted-foreground"><UserRound class="size-4" /> {{ form.programme }}</div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Personal information</CardTitle>
          <CardDescription>Keep these details accurate for your generated passport.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="save">
            <div class="space-y-2 sm:col-span-2"><Label for="name">Full name</Label><Input id="name" v-model="form.name" /></div>
            <div class="space-y-2"><Label for="matric">Matric number</Label><Input id="matric" v-model="form.matricNo" /></div>
            <div class="space-y-2"><Label for="programme">Programme</Label><Input id="programme" v-model="form.programme" /></div>
            <div class="space-y-2 sm:col-span-2"><Label for="email">Email address</Label><Input id="email" v-model="form.email" type="email" disabled /></div>
            <div class="space-y-2 sm:col-span-2"><Label for="headline">Professional headline</Label><Input id="headline" v-model="form.headline" maxlength="160" placeholder="Full Stack Developer" /></div>
            <div class="space-y-2"><Label for="phone">Phone number</Label><div class="relative"><Phone class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input id="phone" v-model="form.phone" class="pl-9" /></div></div>
            <div class="space-y-2"><Label for="location">Location</Label><div class="relative"><MapPin class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input id="location" v-model="form.location" class="pl-9" /></div></div>
            <div class="space-y-2"><Label for="linkedin">LinkedIn URL</Label><div class="relative"><Globe2 class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input id="linkedin" v-model="form.linkedinUrl" type="url" class="pl-9" placeholder="https://linkedin.com/in/your-name" /></div></div>
            <div class="space-y-2"><Label for="github">GitHub URL</Label><div class="relative"><Code2 class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input id="github" v-model="form.githubUrl" type="url" class="pl-9" placeholder="https://github.com/your-name" /></div></div>
            <div class="space-y-2"><Label for="graduation">Expected graduation</Label><Input id="graduation" v-model="form.expectedGraduationDate" type="date" /></div>
            <div class="space-y-2 sm:col-span-2"><Label for="bio">Professional summary</Label><Textarea id="bio" v-model="form.bio" rows="5" maxlength="2000" /></div>
            <p v-if="error" class="text-sm font-medium text-destructive sm:col-span-2">{{ error }}</p>
            <div class="flex items-center gap-3 sm:col-span-2">
              <Button type="submit" variant="success" :disabled="loading || submitting"><Save class="size-4" /> {{ submitting ? 'Saving...' : 'Save changes' }}</Button>
              <span v-if="saved" class="text-sm font-medium text-emerald-600">Profile updated successfully.</span>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

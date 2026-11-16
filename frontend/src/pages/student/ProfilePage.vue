<script setup lang="ts">
import { formatPhone } from '@/lib/phone'
import { useFormDraft } from '@/composables/useFormDraft'

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

const profileDraft=useFormDraft('profile',form,true)
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
      phone: formatPhone(profile.phone),
      location: profile.location ?? '',
      linkedinUrl: profile.linkedinUrl ?? '',
      githubUrl: profile.githubUrl ?? '',
      expectedGraduationDate: profile.expectedGraduationDate ?? '',
    })
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The profile could not be loaded.'
  } finally {
    profileDraft.restore()
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
      phone: formatPhone(profile.phone),
      location: profile.location ?? '',
      linkedinUrl: profile.linkedinUrl ?? '',
      githubUrl: profile.githubUrl ?? '',
      expectedGraduationDate: profile.expectedGraduationDate ?? '',
    })
    profileDraft.clear()
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
      <h1 class="page-heading">Personal details</h1>
      <p class="mt-1 text-muted-foreground">Keep your contact details and academic information up to date.</p>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
      <Card class="tone-card tone-blue overflow-hidden">
        <CardContent class="flex flex-col items-center p-7 text-center">
          <div class="flex size-24 items-center justify-center rounded-2xl bg-primary text-3xl font-semibold text-primary-foreground shadow-lg shadow-primary/20">
            {{ form.name.split(' ').map((part) => part[0]).join('').slice(0, 2) }}
          </div>
          <h2 class="mt-5 text-xl font-semibold">{{ form.name }}</h2>
          <p class="mt-1 text-sm text-muted-foreground">{{ form.matricNo }}</p>
          <div class="mt-5 flex items-center gap-2 rounded-full bg-success-soft px-3 py-1.5 text-xs font-medium text-success">
            <BadgeCheck class="size-4" /> Active student
          </div>
          <div class="mt-6 w-full space-y-3 border-t border-border pt-5 text-left text-sm">
            <div class="flex items-start gap-3 break-all text-muted-foreground"><Mail class="mt-0.5 size-4 shrink-0" /> {{ form.email }}</div>
            <div class="flex items-start gap-3 break-all text-muted-foreground"><UserRound class="mt-0.5 size-4 shrink-0" /> {{ form.programme }}</div>
          </div>
        </CardContent>
      </Card>

      <Card class="tone-card tone-teal">
        <CardHeader>
          <CardTitle>Personal information</CardTitle>
          <CardDescription>These details appear in your skills profile and CV. Unfinished edits are kept in this browser tab.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="save">
            <div class="space-y-2 sm:col-span-2"><Label for="name">Full name</Label><Input id="name" v-model="form.name" /></div>
            <div class="space-y-2"><Label for="matric">Matric number</Label><Input id="matric" v-model="form.matricNo" /></div>
            <div class="space-y-2"><Label for="programme">Programme</Label><Input id="programme" v-model="form.programme" /></div>
            <div class="space-y-2 sm:col-span-2"><Label for="email">Email address</Label><Input id="email" v-model="form.email" type="email" disabled /></div>
            <div class="space-y-2 sm:col-span-2"><Label for="headline">Professional headline</Label><Input id="headline" v-model="form.headline" maxlength="160" placeholder="e.g. Computing student interested in web development" /></div>
            <div class="space-y-2"><Label for="phone">Phone number</Label><div class="relative"><Phone class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input id="phone" v-model="form.phone" type="tel" autocomplete="tel" placeholder="+(60) - 123456789" class="pl-9" @blur="form.phone=formatPhone(form.phone)" /></div></div>
            <div class="space-y-2"><Label for="location">Location</Label><div class="relative"><MapPin class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input id="location" v-model="form.location" class="pl-9" /></div></div>
            <div class="space-y-2"><Label for="linkedin">LinkedIn URL</Label><div class="relative"><Globe2 class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input id="linkedin" v-model="form.linkedinUrl" type="url" class="pl-9" placeholder="https://linkedin.com/in/your-name" /></div></div>
            <div class="space-y-2"><Label for="github">GitHub URL</Label><div class="relative"><Code2 class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input id="github" v-model="form.githubUrl" type="url" class="pl-9" placeholder="https://github.com/your-name" /></div></div>
            <div class="space-y-2"><Label for="graduation">Expected graduation</Label><Input id="graduation" v-model="form.expectedGraduationDate" type="date" /></div>
            <div class="space-y-2 sm:col-span-2"><Label for="bio">Professional summary</Label><Textarea id="bio" v-model="form.bio" rows="5" maxlength="2000" placeholder="Describe what you study, a project you contributed to, and the kind of opportunity you want. Use details you can support." /></div>
            <p v-if="error" class="text-sm font-medium text-destructive sm:col-span-2">{{ error }}</p>
            <div class="flex items-center gap-3 sm:col-span-2">
              <Button type="submit" variant="success" :disabled="loading || submitting"><Save class="size-4" /> {{ submitting ? 'Saving...' : 'Save changes' }}</Button>
              <span v-if="saved" class="text-sm font-medium text-success">Profile updated successfully.</span>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

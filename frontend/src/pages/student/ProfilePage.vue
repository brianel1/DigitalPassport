<script setup lang="ts">
import { reactive, ref } from 'vue'
import { BadgeCheck, Mail, Save, UserRound } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useAuth } from '@/composables/useAuth'

const { user } = useAuth()
const saved = ref(false)
const form = reactive({
  name: user.value?.name || '',
  email: user.value?.email || '',
  matricNo: user.value?.matricNo || '',
  programme: user.value?.programme || '',
  bio: 'Computing student interested in full-stack development, database systems, and building useful digital products.',
})

function save() {
  saved.value = true
  window.setTimeout(() => { saved.value = false }, 2500)
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
            <div class="space-y-2 sm:col-span-2"><Label for="email">Email address</Label><Input id="email" v-model="form.email" type="email" /></div>
            <div class="space-y-2 sm:col-span-2"><Label for="bio">Professional summary</Label><Textarea id="bio" v-model="form.bio" rows="5" /></div>
            <div class="flex items-center gap-3 sm:col-span-2">
              <Button type="submit"><Save class="size-4" /> Save changes</Button>
              <span v-if="saved" class="text-sm font-medium text-emerald-600">Profile updated successfully.</span>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

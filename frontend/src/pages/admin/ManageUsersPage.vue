<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { Ban, CheckCircle2, Eye, Loader2, Search, UserRound } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { ApiError, getAdminUser, getAdminUsers, updateAdminUserStatus } from '@/lib/api'
import type { AdminUserDetail, AdminUserSummary } from '@/types'

const users = ref<AdminUserSummary[]>([])
const programmes = ref<string[]>([])
const search = ref('')
const status = ref<'all' | 'active' | 'inactive'>('all')
const programme = ref('all')
const loading = ref(true)
const error = ref('')
const selected = ref<AdminUserSummary | null>(null)
const actionOpen = ref(false)
const submitting = ref(false)
const detail = ref<AdminUserDetail | null>(null)
const detailOpen = ref(false)
const detailLoading = ref(false)

async function loadUsers() {
  loading.value = true
  error.value = ''
  try {
    const response = await getAdminUsers({
      search: search.value.trim() || undefined,
      status: status.value === 'all' ? undefined : status.value,
      programme: programme.value === 'all' ? undefined : programme.value,
    })
    users.value = response.data
    programmes.value = response.meta.programmes
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Users could not be loaded.'
  } finally {
    loading.value = false
  }
}

onMounted(loadUsers)
watch([status, programme], loadUsers)

function openStatusAction(user: AdminUserSummary) {
  selected.value = user
  actionOpen.value = true
}

async function openDetails(user: AdminUserSummary) {
  detailLoading.value = true
  detailOpen.value = true
  error.value = ''
  try {
    detail.value = await getAdminUser(user.id)
  } catch (cause) {
    detailOpen.value = false
    error.value = cause instanceof ApiError ? cause.message : 'The student profile could not be loaded.'
  } finally {
    detailLoading.value = false
  }
}

async function confirmStatusChange() {
  if (!selected.value) return
  submitting.value = true
  error.value = ''
  try {
    const user = selected.value
    const response = await updateAdminUserStatus(user.id, !user.isActive)
    users.value = users.value.map((item) => item.id === user.id ? response.user : item)
    actionOpen.value = false
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'The account status could not be changed.'
  } finally {
    submitting.value = false
  }
}

function formatDate(value: string) {
  return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium' }).format(new Date(value))
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Manage users</h1>
      <p class="mt-1 text-muted-foreground">Review student accounts and control their access to the portal.</p>
    </div>

    <Card>
      <CardContent class="grid gap-3 p-4 md:grid-cols-[1fr_180px_220px_auto]">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input v-model="search" class="pl-9" placeholder="Search name, email or matric number" @keyup.enter="loadUsers" />
        </div>
        <select v-model="status" class="h-9 rounded-lg border border-input bg-background px-3 text-sm">
          <option value="all">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
        <select v-model="programme" class="h-9 rounded-lg border border-input bg-background px-3 text-sm">
          <option value="all">All programmes</option>
          <option v-for="item in programmes" :key="item" :value="item">{{ item }}</option>
        </select>
        <Button variant="outline" @click="loadUsers">Search</Button>
      </CardContent>
    </Card>

    <p v-if="error" class="text-sm font-medium text-destructive">{{ error }}</p>
    <div v-if="loading" class="flex items-center justify-center py-16 text-muted-foreground"><Loader2 class="mr-2 size-5 animate-spin" /> Loading users...</div>

    <Card v-else>
      <CardHeader>
        <CardTitle>Student accounts</CardTitle>
        <CardDescription>{{ users.length }} account(s) matched the current filters.</CardDescription>
      </CardHeader>
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[860px] text-left text-sm">
            <thead class="border-y border-border bg-muted/40 text-xs uppercase text-muted-foreground">
              <tr><th class="px-6 py-3">Student</th><th class="px-6 py-3">Programme</th><th class="px-6 py-3">Portfolio</th><th class="px-6 py-3">Joined</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Action</th></tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="user in users" :key="user.id" class="hover:bg-muted/20">
                <td class="px-6 py-4"><div class="flex items-center gap-3"><div class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary"><UserRound class="size-4" /></div><div><p class="font-medium">{{ user.name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ user.email }} · {{ user.matricNo || 'No matric number' }}</p></div></div></td>
                <td class="px-6 py-4">{{ user.programme || 'Not provided' }}</td>
                <td class="px-6 py-4 text-xs text-muted-foreground">{{ user.skillsCount }} skills · {{ user.projectsCount }} projects · {{ user.certificationsCount }} certifications</td>
                <td class="px-6 py-4 text-xs text-muted-foreground">{{ formatDate(user.createdAt) }}</td>
                <td class="px-6 py-4"><Badge :variant="user.isActive ? 'default' : 'destructive'">{{ user.isActive ? 'Active' : 'Inactive' }}</Badge></td>
                <td class="px-6 py-4 text-right"><div class="flex justify-end gap-2"><Button variant="info" size="sm" @click="openDetails(user)"><Eye class="size-4" /> View</Button><Button :variant="user.isActive ? 'destructive' : 'success'" size="sm" @click="openStatusAction(user)"><Ban v-if="user.isActive" class="size-4" /><CheckCircle2 v-else class="size-4" /> {{ user.isActive ? 'Suspend' : 'Activate' }}</Button></div></td>
              </tr>
              <tr v-if="users.length === 0"><td colspan="6" class="px-6 py-12 text-center text-muted-foreground">No student accounts match the current filters.</td></tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <Dialog v-model:open="actionOpen">
      <DialogContent v-if="selected" class="sm:max-w-md">
        <DialogHeader><DialogTitle>{{ selected.isActive ? 'Suspend student account?' : 'Activate student account?' }}</DialogTitle><DialogDescription>{{ selected.isActive ? 'This student will be signed out and cannot log in until the account is activated again.' : 'This student will be allowed to access the portal again.' }}</DialogDescription></DialogHeader>
        <div class="rounded-xl border border-border bg-muted/30 p-4"><p class="font-medium">{{ selected.name }}</p><p class="mt-1 text-sm text-muted-foreground">{{ selected.email }}</p></div>
        <DialogFooter><Button variant="outline" :disabled="submitting" @click="actionOpen = false">Cancel</Button><Button :variant="selected.isActive ? 'destructive' : 'success'" :disabled="submitting" @click="confirmStatusChange">{{ submitting ? 'Saving...' : selected.isActive ? 'Suspend account' : 'Activate account' }}</Button></DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="detailOpen"><DialogContent v-if="detail || detailLoading" class="max-h-[90vh] overflow-y-auto sm:max-w-2xl"><DialogHeader v-if="detail"><DialogTitle>{{ detail.name }}</DialogTitle><DialogDescription>{{ detail.email }} · {{ detail.matricNo || 'No matric number' }} · {{ detail.programme || 'Programme not provided' }}</DialogDescription></DialogHeader><div v-if="detailLoading" class="flex items-center justify-center py-12 text-muted-foreground"><Loader2 class="mr-2 size-5 animate-spin" /> Loading student details...</div><div v-else-if="detail" class="space-y-5"><div class="grid gap-3 sm:grid-cols-4"><div class="rounded-xl border border-border p-3"><p class="text-xs text-muted-foreground">Skills</p><p class="mt-1 text-xl font-semibold">{{ detail.skillsCount }}</p></div><div class="rounded-xl border border-border p-3"><p class="text-xs text-muted-foreground">Projects</p><p class="mt-1 text-xl font-semibold">{{ detail.projectsCount }}</p></div><div class="rounded-xl border border-border p-3"><p class="text-xs text-muted-foreground">Certifications</p><p class="mt-1 text-xl font-semibold">{{ detail.certificationsCount }}</p></div><div class="rounded-xl border border-border p-3"><p class="text-xs text-muted-foreground">Assessments</p><p class="mt-1 text-xl font-semibold">{{ detail.assessmentAttemptsCount }}</p></div></div><div class="rounded-xl border border-border bg-muted/30 p-4"><p class="font-medium">Profile</p><p class="mt-2 text-sm text-muted-foreground">{{ detail.profile?.headline || 'No headline provided.' }}</p><p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ detail.profile?.bio || 'No professional summary provided.' }}</p><p class="mt-3 text-xs text-muted-foreground">{{ detail.profile?.location || 'Location not provided' }} · {{ detail.profile?.phone || 'Phone not provided' }}</p></div><div><p class="mb-2 font-medium">Skills</p><div class="flex flex-wrap gap-2"><Badge v-for="skill in detail.skills" :key="skill.id" variant="secondary">{{ skill.name }} · {{ skill.proficiency }}</Badge><span v-if="!detail.skills.length" class="text-sm text-muted-foreground">No skills recorded.</span></div></div><div><p class="mb-2 font-medium">Recent assessment attempts</p><div class="space-y-2"><div v-for="attempt in detail.assessmentAttempts.slice(0, 5)" :key="attempt.id" class="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-sm"><span>{{ attempt.assessmentTitle || 'Assessment' }}</span><span class="text-muted-foreground">{{ attempt.percentage ?? 0 }}% · {{ attempt.passed ? 'Passed' : 'Not passed' }}</span></div><span v-if="!detail.assessmentAttempts.length" class="text-sm text-muted-foreground">No completed assessments.</span></div></div></div></DialogContent></Dialog>
  </div>
</template>

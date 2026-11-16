<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { Award, BookOpenCheck, FolderKanban, Gauge, Layers3, LogOut, Menu, Sparkles, UserRound, BriefcaseBusiness, FileUser, ClipboardCheck, ListChecks, UsersRound, ChevronRight, PanelLeftClose, PanelLeftOpen } from '@lucide/vue'
import { useAuth } from '@/composables/useAuth'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetTrigger, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import BrandLogo from '@/components/brand/BrandLogo.vue'
import ThemeToggle from '@/components/shared/ThemeToggle.vue'
import { clearFormDrafts } from '@/composables/useFormDraft'
import { useConfirm } from '@/composables/useConfirm'
import CareerAssistant from '@/components/shared/CareerAssistant.vue'

const props = defineProps<{ role: 'student' | 'admin' }>()
const { user, logout } = useAuth()
const route = useRoute()
const router = useRouter()
const mobileOpen = ref(false)
const collapsed = ref(localStorage.getItem('skills-sidebar-collapsed') === 'true')
watch(collapsed, value => localStorage.setItem('skills-sidebar-collapsed', String(value)))
const { confirmAction } = useConfirm()
const initials = computed(() => user.value?.name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() || '?')
const groups = computed(() => props.role === 'admin' ? [
  { label: 'Workspace', items: [{ label: 'Overview', path: '/admin/dashboard', icon: Gauge }, { label: 'Review submissions', path: '/admin/reviews', icon: ClipboardCheck }] },
  { label: 'Management', items: [{ label: 'Assessments', path: '/admin/assessments', icon: ListChecks }, { label: 'Students', path: '/admin/users', icon: UsersRound }] },
] : [
  { label: 'Workspace', items: [{ label: 'Overview', path: '/student/dashboard', icon: Gauge }, { label: 'Skills profile', path: '/student/skills-profile', icon: Layers3 }] },
  { label: 'Build your record', items: [{ label: 'Skills', path: '/student/skills', icon: Sparkles }, { label: 'Projects', path: '/student/projects', icon: FolderKanban }, { label: 'Certifications', path: '/student/certifications', icon: Award }, { label: 'Assessments', path: '/student/assessments', icon: BookOpenCheck }] },
  { label: 'Career tools', items: [{ label: 'Job matches', path: '/student/jobs', icon: BriefcaseBusiness }, { label: 'Create / upload CV', path: '/student/cv', icon: FileUser }] },
  { label: 'Account', items: [{ label: 'Personal details', path: '/student/profile', icon: UserRound }] },
])
function isActive(path: string) { return route.path === path || route.path.startsWith(path + '/') }
const currentLabel = computed(() => groups.value.flatMap(g => g.items).find(n => isActive(n.path))?.label || 'Workspace')
watch(() => route.path, () => { mobileOpen.value = false })
async function signOut() { if (!await confirmAction({title:'Sign out?', description:'Unsaved local drafts will be cleared from this browser.', confirmLabel:'Sign out'})) return; clearFormDrafts(); await logout(); router.push('/login') }
</script>

<template>
  <div class="portal-shell flex min-h-screen bg-background">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <aside :class="{collapsed}" class="portal-sidebar sticky top-0 hidden h-screen w-64 shrink-0 flex-col border-r bg-card lg:flex">
      <RouterLink to="/" class="flex h-22 shrink-0 items-center px-6 text-primary" aria-label="Skills and Career home"><BrandLogo v-if="!collapsed" size="sm" /><Layers3 v-else class="size-7" /></RouterLink>
      <div v-if="!collapsed" class="mx-5 mb-3 rounded-lg border bg-background px-3 py-2 text-xs font-medium text-muted-foreground">{{ role === 'admin' ? 'Administration' : 'Student workspace' }} <span class="float-right text-teal">UTeM</span></div>
      <nav class="flex-1 space-y-5 overflow-y-auto px-4 py-4" aria-label="Main navigation">
        <div v-for="group in groups" :key="group.label">
          <p v-if="!collapsed" class="mb-2 px-3 text-[11px] font-medium uppercase tracking-[.09em] text-muted-foreground">{{ group.label }}</p>
          <RouterLink v-for="item in group.items" :key="item.path" :to="item.path" :title="collapsed ? item.label : undefined" :aria-label="item.label" class="portal-nav-link" :class="{ active: isActive(item.path) }" :aria-current="isActive(item.path) ? 'page' : undefined"><component :is="item.icon" class="size-4 shrink-0" /><span v-if="!collapsed">{{ item.label }}</span></RouterLink>
        </div>
      </nav>
      <div class="flex items-center gap-3 border-t p-5"><span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-semibold text-primary">{{ initials }}</span><div v-if="!collapsed" class="min-w-0"><p class="truncate text-sm font-medium">{{ user?.name }}</p><p class="truncate text-xs text-muted-foreground">{{ role === 'admin' ? 'Administrator' : user?.matricNo }}</p></div></div>
    </aside>
    <div class="min-w-0 flex-1">
      <header class="portal-header sticky top-0 z-30 flex h-18 items-center gap-3 border-b bg-card px-5 sm:px-7 xl:px-10">
        <Button variant="ghost" size="icon" class="hidden lg:inline-flex" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'" :aria-expanded="!collapsed" @click="collapsed = !collapsed"><PanelLeftOpen v-if="collapsed" class="size-5" /><PanelLeftClose v-else class="size-5" /></Button>
        <Sheet v-model:open="mobileOpen">
          <SheetTrigger as-child><Button variant="ghost" size="icon" class="lg:hidden" aria-label="Open navigation"><Menu class="size-5" /></Button></SheetTrigger>
          <SheetContent side="left" class="portal-mobile-nav flex w-72 flex-col overflow-y-auto bg-card p-5">
            <SheetTitle class="sr-only">Main navigation</SheetTitle><SheetDescription class="sr-only">Choose a page in your workspace.</SheetDescription>
            <RouterLink to="/" class="my-4 text-primary"><BrandLogo size="sm" /></RouterLink>
            <nav class="space-y-5" aria-label="Mobile navigation"><div v-for="group in groups" :key="group.label"><p class="eyebrow mb-2 px-3">{{ group.label }}</p><RouterLink v-for="item in group.items" :key="item.path" :to="item.path" class="portal-nav-link" :class="{ active: isActive(item.path) }" :aria-current="isActive(item.path) ? 'page' : undefined"><component :is="item.icon" class="size-4" />{{ item.label }}</RouterLink></div></nav>
          </SheetContent>
        </Sheet>
        <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-2 text-sm"><RouterLink :to="'/' + role + '/dashboard'" class="hidden text-muted-foreground hover:text-primary sm:inline">{{ role === 'admin' ? 'Admin' : 'Workspace' }}</RouterLink><ChevronRight class="hidden size-3.5 text-muted-foreground sm:block" /><RouterLink v-if="route.path.includes('/assessments/')" :to="'/' + role + '/assessments'" class="text-muted-foreground hover:text-primary">Assessments</RouterLink><ChevronRight v-if="route.path.includes('/assessments/')" class="size-3.5 text-muted-foreground" /><span aria-current="page" class="truncate font-medium">{{ route.path.includes('/assessments/') ? 'Assessment' : currentLabel }}</span></nav>
        <div class="ml-auto flex items-center gap-2 sm:gap-4"><ThemeToggle />
          <DropdownMenu><DropdownMenuTrigger as-child><Button variant="ghost" size="icon" class="rounded-full bg-accent text-primary" aria-label="Account menu">{{ initials }}</Button></DropdownMenuTrigger><DropdownMenuContent align="end" class="w-52"><div class="truncate px-2 py-2 text-sm font-medium">{{ user?.name }}</div><DropdownMenuSeparator /><DropdownMenuItem v-if="role === 'student'" @click="router.push('/student/profile')"><UserRound class="size-4" /> Personal details</DropdownMenuItem><DropdownMenuItem class="text-destructive" @click="signOut"><LogOut class="size-4" /> Sign out</DropdownMenuItem></DropdownMenuContent></DropdownMenu>
        </div>
      </header>
      <main id="main-content" class="portal-content"><RouterView v-slot="{ Component }"><Transition name="workspace-page" mode="out-in"><component :is="Component" :key="route.path" /></Transition></RouterView></main>
    </div>
    <CareerAssistant />
  </div>
</template>

<style scoped>
.portal-sidebar{transition:width .2s ease}.portal-sidebar.collapsed{width:80px}.collapsed nav{padding-left:12px;padding-right:12px}.collapsed .portal-nav-link{justify-content:center;padding:10px}.collapsed>div:last-child{padding:20px;justify-content:center}
@media(prefers-reduced-motion:reduce){.portal-sidebar{transition:none}}
</style>

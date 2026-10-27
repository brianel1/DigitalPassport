<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterView, RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet'
import { ClipboardCheck, Gauge, ListChecks, LogOut, Menu } from '@lucide/vue'
import BrandLogo from '@/components/brand/BrandLogo.vue'

const { user, logout } = useAuth()
const route = useRoute()
const router = useRouter()
const mobileOpen = ref(false)

const initials = computed(() => {
  if (!user.value?.name) return 'A'
  return user.value.name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const navItems = [
  { label: 'Dashboard', path: '/admin/dashboard', icon: Gauge },
  { label: 'Review Submissions', path: '/admin/reviews', icon: ClipboardCheck },
  { label: 'Manage Assessments', path: '/admin/assessments', icon: ListChecks },
]

function isActive(path: string) {
  return route.path === path || route.path.startsWith(path + '/')
}

function handleLogout() {
  logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex min-h-screen bg-background">
    <!-- Desktop Sidebar -->
    <aside class="hidden w-64 flex-col border-r border-border bg-card lg:flex">
      <div class="flex h-16 items-center border-b border-border px-6 text-destructive">
        <BrandLogo size="sm" />
      </div>

      <nav class="flex-1 space-y-1 px-3 py-4">
        <RouterLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
          :class="isActive(item.path)
            ? 'bg-primary/10 text-primary'
            : 'text-muted-foreground hover:bg-accent hover:text-foreground'"
        >
          <component :is="item.icon" class="size-4" />
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="border-t border-border p-4">
        <div class="flex items-center gap-3">
          <Avatar class="size-8">
            <AvatarFallback class="text-xs">{{ initials }}</AvatarFallback>
          </Avatar>
          <div class="flex-1 truncate">
            <p class="truncate text-sm font-medium">{{ user?.name }}</p>
            <p class="truncate text-xs text-muted-foreground">Administrator</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-1 flex-col">
      <header class="flex h-16 items-center justify-between border-b border-border px-4 lg:px-8">
        <Sheet v-model:open="mobileOpen">
          <SheetTrigger as-child>
            <Button variant="ghost" size="sm" class="lg:hidden">
              <Menu class="size-5" />
            </Button>
          </SheetTrigger>
          <SheetContent side="left" class="w-64 p-0">
            <div class="flex h-16 items-center border-b border-border px-6 text-destructive">
              <BrandLogo size="sm" />
            </div>
            <nav class="space-y-1 px-3 py-4">
              <RouterLink
                v-for="item in navItems"
                :key="item.path"
                :to="item.path"
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                :class="isActive(item.path)
                  ? 'bg-primary/10 text-primary'
                  : 'text-muted-foreground hover:bg-accent hover:text-foreground'"
                @click="mobileOpen = false"
              >
                <component :is="item.icon" class="size-4" />
                {{ item.label }}
              </RouterLink>
            </nav>
          </SheetContent>
        </Sheet>

        <div class="hidden text-lg font-semibold lg:block">
          {{ navItems.find((n) => isActive(n.path))?.label || 'Admin Panel' }}
        </div>

        <div class="ml-auto flex items-center gap-3">
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="ghost" class="flex items-center gap-2">
                <Avatar class="size-8">
                  <AvatarFallback class="text-xs">{{ initials }}</AvatarFallback>
                </Avatar>
                <span class="hidden text-sm sm:inline">{{ user?.name }}</span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
              <DropdownMenuItem @click="handleLogout" class="text-destructive">
                <LogOut class="size-4" /> Logout
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <main class="flex-1 overflow-auto p-4 lg:p-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>

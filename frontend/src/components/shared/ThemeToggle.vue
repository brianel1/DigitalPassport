<script setup lang="ts">
import { computed } from 'vue'
import { Moon, Sun } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { useTheme } from '@/composables/useTheme'

const { theme, toggleTheme } = useTheme()

const label = computed(() => theme.value === 'dark' ? 'Switch to light theme' : 'Switch to dark theme')
</script>

<template>
  <Button
    variant="ghost"
    size="icon"
    type="button"
    :aria-label="label"
    :title="label"
    @click="toggleTheme"
  >
    <Sun v-if="theme === 'dark'" class="size-4" />
    <Moon v-else class="size-4" />
    <span class="sr-only">{{ label }}</span>
  </Button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ChevronDown, MapPin } from '@lucide/vue'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

defineOptions({ inheritAttrs: false })
const props = defineProps<{ options: string[]; disabled?: boolean }>()
const selected = defineModel<string[]>({ required: true })

// No state selected means the whole country, so "All of Malaysia" is the default rather than an extra filter.
const summary = computed(() => {
  if (!selected.value.length) return 'All of Malaysia'
  const ordered = props.options.filter((state) => selected.value.includes(state))
  return ordered.length <= 2 ? ordered.join(', ') : `${ordered.slice(0, 2).join(', ')} +${ordered.length - 2}`
})
function toggle(state: string, checked: boolean | 'indeterminate') {
  selected.value = checked === true
    ? props.options.filter((option) => option === state || selected.value.includes(option))
    : selected.value.filter((option) => option !== state)
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger
      v-bind="$attrs"
      :disabled="disabled"
      class="app-select flex w-full min-h-11 items-center gap-2 rounded-md border px-3 text-left text-sm disabled:opacity-60"
    >
      <MapPin class="size-4 shrink-0" />
      <span class="min-w-0 flex-1 truncate">{{ summary }}</span>
      <ChevronDown class="size-4 shrink-0" />
    </DropdownMenuTrigger>
    <DropdownMenuContent class="app-select-menu max-h-80 min-w-64">
      <DropdownMenuCheckboxItem
        :model-value="!selected.length"
        class="min-h-10 cursor-pointer rounded-lg px-3 font-medium"
        @update:model-value="selected = []"
        @select.prevent
        >All of Malaysia</DropdownMenuCheckboxItem
      >
      <DropdownMenuSeparator />
      <DropdownMenuLabel class="px-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground"
        >States & federal territories</DropdownMenuLabel
      >
      <DropdownMenuCheckboxItem
        v-for="state in options"
        :key="state"
        :model-value="selected.includes(state)"
        class="min-h-10 cursor-pointer rounded-lg px-3"
        @update:model-value="toggle(state, $event)"
        @select.prevent
        >{{ state }}</DropdownMenuCheckboxItem
      >
    </DropdownMenuContent>
  </DropdownMenu>
</template>

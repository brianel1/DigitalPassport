<script setup lang="ts">
import type { SharedProfileData } from '@/lib/sharing'
defineProps<{ profile: SharedProfileData }>()
</script>

<template>
  <article class="overflow-hidden rounded-2xl border border-border bg-card">
    <header class="bg-navy p-6 text-white sm:p-8"><p class="text-sm font-semibold text-white/75">SKILLS PROFILE</p><h1 class="mt-2 text-3xl font-semibold">{{ profile.name }}</h1><p v-if="profile.headline" class="mt-2">{{ profile.headline }}</p><p v-if="profile.programme" class="mt-2 text-sm text-white/75">{{ profile.programme }}</p></header>
    <div class="grid gap-7 p-6 sm:p-8">
      <section v-for="section in profile.sections" :key="section.key"><h2 class="mb-3 text-lg font-semibold">{{ section.title }}</h2><p v-if="!section.records.length" class="text-sm text-muted-foreground">No current verified records to display.</p><ul class="grid gap-3 sm:grid-cols-2"><li v-for="(item, i) in section.records" :key="i" class="rounded-xl border border-border p-4"><h3 class="font-semibold">{{ item.title }}</h3><p class="mt-1 whitespace-pre-line text-sm text-muted-foreground">{{ item.detail }}</p><p v-if="item.date" class="mt-1 text-xs text-muted-foreground">{{ item.date }}</p><div v-if="item.tags?.length" class="mt-3 flex flex-wrap gap-2"><span v-for="tag in item.tags" :key="tag" class="rounded-md bg-accent px-2 py-1 text-xs font-medium text-accent-foreground">{{ tag }}</span></div></li></ul></section>
    </div>
    <footer class="border-t border-border px-6 py-4 text-xs text-muted-foreground">Shared by the profile owner · Skills & Career</footer>
  </article>
</template>

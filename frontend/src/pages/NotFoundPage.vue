<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Button } from '@/components/ui/button'
</script>

<template>
  <main class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
    <p class="text-sm font-medium text-muted-foreground">404</p>
    <h1 class="mt-3 text-3xl font-semibold">Page not found</h1>
    <p class="mt-3 text-muted-foreground">This page is not part of DigitalPassport.</p>
    <Button as-child class="mt-8">
      <RouterLink to="/">Back to home</RouterLink>
    </Button>
  </main>
</template>

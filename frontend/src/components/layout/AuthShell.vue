<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { ArrowLeft, ArrowUpRight, ShieldCheck } from '@lucide/vue'
import BrandLogo from '@/components/brand/BrandLogo.vue'
import ThemeToggle from '@/components/shared/ThemeToggle.vue'
defineProps<{ register?: boolean }>()
</script>
<template>
  <div class="auth-page grid min-h-dvh lg:grid-cols-[1.05fr_.95fr]">
    <section class="auth-story hidden flex-col justify-between lg:flex">
      <RouterLink to="/" class="auth-brand"><BrandLogo /></RouterLink>
      <div class="auth-story-content"><p class="auth-eyebrow">Your learning. Your next chapter.</p><h2 class="auth-story-heading">You've put<br />in the work.<br /><span>Let it show.</span></h2><figure><img src="/media/student-studio.webp" alt="Illustrative scene of students working on a university project." width="1456" height="1092" /><figcaption><ShieldCheck class="size-4" /> Turn your experience into evidence.</figcaption></figure></div>
      <p class="auth-footnote">Student skills & career development · UTeM</p>
    </section>
    <main class="flex min-h-dvh flex-col px-6 py-6 sm:px-12 xl:px-16">
      <div class="flex items-center justify-between"><RouterLink to="/" class="flex min-h-11 items-center gap-2 text-sm text-muted-foreground hover:text-primary"><ArrowLeft class="size-4" /> Back to home</RouterLink><ThemeToggle /></div>
      <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
        <RouterLink to="/" class="mb-10 self-start text-primary lg:hidden"><BrandLogo size="sm" /></RouterLink>
        <p class="section-label">{{ register ? 'A new beginning' : 'Your workspace awaits' }}</p>
        <h1 class="text-4xl font-medium tracking-tight">{{ register ? 'Start your story.' : 'Welcome back.' }}</h1>
        <p class="mt-3 mb-9 text-sm leading-7 text-muted-foreground">{{ register ? 'Create an account to organise your skills and prepare for your next opportunity.' : 'Your skills, achievements and next steps. All here.' }}</p>
        <slot />
        <p class="mt-8 border-t pt-6 text-center text-sm text-muted-foreground">{{ register ? 'Already have an account?' : 'New here?' }} <RouterLink :to="register ? '/login' : '/register'" class="inline-flex min-h-11 items-center gap-1 font-semibold text-primary hover:underline">{{ register ? 'Sign in' : 'Create an account' }}<ArrowUpRight class="size-3.5" /></RouterLink></p>
      </div>
      <p class="py-2 text-center text-xs text-muted-foreground">Skills & Career · Student workspace</p>
    </main>
  </div>
</template>
<style scoped>
.auth-story { background: #0d1524; color: #f2f5fb; padding: 40px 56px; border-right: 1px solid #283448; }
.auth-brand { align-self: start; color: #f2f5fb; --brand-mark-ink: #0d1524; }
.auth-story-content { max-width: 560px; margin-block: 45px; }
.auth-eyebrow { color: #72dfc4; text-transform: uppercase; font-size: 10px; letter-spacing: .15em; }
.auth-story-heading { margin-top: 24px; font-size: clamp(46px, 4.6vw, 68px); font-weight: 500; line-height: 1.05; letter-spacing: -.045em; }
.auth-story-heading span { color: #72dfc4; }
figure { position: relative; margin-top: 35px; overflow: hidden; border: 1px solid #39475e; border-radius: 14px; transform: rotate(-1deg); }
figure img { width: 100%; height: 240px; object-fit: cover; object-position: 55% 55%; }
figcaption { display: flex; align-items: center; gap: 10px; padding: 17px 20px; background: #172236; color: #d5dfed; font-size: 12px; }
figcaption svg { color: #72dfc4; }
.auth-footnote { color: #a3b0c5; font-size: 11px; }
@media (max-width:1200px) { .auth-story { padding-inline: 36px; } }
</style>

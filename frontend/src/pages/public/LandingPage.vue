<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowUpRight, ShieldCheck, FileText, BriefcaseBusiness, Plus, FolderCheck, Layers3, Check, BookOpenCheck, Play, Sparkles, Code2, Award, Menu, X } from '@lucide/vue'
import BrandLogo from '@/components/brand/BrandLogo.vue'
import ProductVideo from '@/components/shared/ProductVideo.vue'
const mobileMenu = ref(false)
const activeFeature = ref(0)
const features = [
  { title: 'Show the work behind your skills.', label: 'Build your record', copy: 'Connect your projects and certifications to the skills they demonstrate. Keep your achievements organised as you learn.', icon: Layers3, nodes: ['Projects', 'Certifications', 'Skills', 'Achievements'], centre: 'Your skills profile', detail: 'Everything you have worked for, connected.' },
  { title: 'Give your achievements credibility.', label: 'Back it with evidence', copy: 'Submit supporting work for review. Follow each decision and see what needs attention, with clear feedback from your administrator.', icon: ShieldCheck, nodes: ['Supporting work', 'Evidence review', 'Feedback', 'Verified skills'], centre: 'Evidence that matters', detail: 'A clearer picture of what you can do.' },
  { title: 'Understand your next step.', label: 'Explore your direction', copy: 'Compare your verified skills with current job requirements. See your strengths, identify gaps and prepare an application that reflects your work.', icon: BriefcaseBusiness, nodes: ['Verified skills', 'Career preferences', 'Job requirements', 'Your next step'], centre: 'A clearer direction', detail: 'Make informed choices about your career.' },
]
const faqs = [
  { question: 'Who is this platform for?', answer: 'University students who want to organise their skills and achievements, understand job requirements and prepare their applications. Administrators review submitted evidence and manage assessments.' },
  { question: 'How are my skills verified?', answer: 'An administrator reviews your linked project or certification evidence. A skill becomes verified when its supporting work has been approved. Assessment results are shown separately.' },
  { question: 'What does AI help me with?', answer: 'AI helps organise uploaded CV text, review your writing and compare it with a role. Navi can explain your next step or run a practice interview using your selected CV. You choose which suggestions to use; uploaded claims do not become verified skills.' },
  { question: 'Can I download my information?', answer: 'Yes. Download your skills profile as a PDF, and your edited CV as a PDF or Word document. Keep a copy ready for your next application.' },
]
function selectFeature(index: number) { activeFeature.value = index }
</script>

<template>
  <div class="experience-landing">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <header class="marketing-header">
      <nav class="landing-container flex h-22 items-center justify-between gap-4" aria-label="Main navigation">
        <RouterLink to="/" class="marketing-brand" aria-label="Skills and Career home"><BrandLogo size="sm" /></RouterLink>
        <div class="hidden items-center gap-9 text-sm lg:flex"><a href="#features">The platform</a><a href="#how-it-works">How it works</a><a href="#questions">FAQs</a></div>
        <div class="flex items-center gap-3"><RouterLink to="/login" class="marketing-login">Log in</RouterLink><RouterLink to="/register" class="marketing-button small hidden sm:inline-flex">Get started <ArrowUpRight class="size-4" /></RouterLink><button class="flex size-11 items-center justify-center rounded-lg border border-white/15 lg:hidden" :aria-expanded="mobileMenu" aria-controls="landing-menu" :aria-label="mobileMenu ? 'Close menu' : 'Open menu'" @click="mobileMenu = !mobileMenu"><X v-if="mobileMenu" class="size-5" /><Menu v-else class="size-5" /></button></div>
      </nav>
      <nav v-if="mobileMenu" id="landing-menu" class="landing-container flex flex-col gap-1 border-t border-white/10 py-4 lg:hidden" aria-label="Mobile navigation"><a href="#features" @click="mobileMenu = false">The platform</a><a href="#how-it-works" @click="mobileMenu = false">How it works</a><a href="#questions" @click="mobileMenu = false">FAQs</a></nav>
    </header>

    <main id="main-content">
      <section class="editorial-hero">
        <div class="landing-container hero-layout">
          <div class="hero-copy">
            <p class="marketing-eyebrow"><span class="status-dot"></span> Built for your next chapter</p>
            <h1>Your skills<br />deserve to <br />be <span>seen.</span><svg class="hero-underline" viewBox="0 0 230 16" fill="none" aria-hidden="true"><path d="M3 10C66 1 155 1 225 8M33 14c63-5 127-5 175-2" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg></h1>
            <p class="hero-description">Turn the work you've done into a story worth showing. Build your skills profile, prove your strengths and move towards the career you want.</p>
            <div class="hero-actions"><RouterLink to="/register" class="marketing-button">Start building your profile <ArrowUpRight class="size-4" /></RouterLink><a href="#how-it-works" class="watch-link"><span><Play class="size-3.5" fill="currentColor" /></span> See it in action</a></div>
            <div class="hero-note"><ShieldCheck class="size-4" /><span>Your achievements. Backed by your work.</span></div>
          </div>
          <div class="hero-visual">
            <div class="photo-outline" aria-hidden="true"></div>
            <figure class="student-photo"><img src="/media/student-studio.webp" width="1456" height="1092" fetchpriority="high" alt="Illustrative scene of university students working on a project together." /><figcaption><span class="photo-caption-line"></span> Great things start with the work you do today.</figcaption></figure>
            <div class="evidence-float"><span class="evidence-icon"><FolderCheck class="size-5" /></span><div><p>More than a skill on a CV.</p><span>Work you can point to.</span></div><ArrowUpRight class="size-4" /></div>
            <div class="hero-corner"><Sparkles class="corner-symbol" aria-hidden="true" /><span>Learn it.<br />Build it.<br /><strong>Show it.</strong></span></div>
          </div>
        </div>
        <div class="landing-container"><div class="capability-strip"><span>ONE CONNECTED WORKSPACE</span><p><Code2 /> Skills & projects</p><p><ShieldCheck /> Evidence & verification</p><p><BriefcaseBusiness /> Career opportunities</p></div></div>
      </section>

      <section id="features" class="marketing-section">
        <div class="landing-container">
          <div class="section-heading-row"><div><p class="marketing-eyebrow">Your potential, made visible</p><h2>A lot goes into your future.<br /><span>Bring it all together.</span></h2></div><p>Your coursework, side projects and achievements deserve more than a folder on your laptop.</p></div>
          <div class="feature-explorer">
            <div class="feature-options" role="tablist" aria-label="Explore platform features" aria-orientation="vertical">
              <button v-for="(feature, index) in features" :id="'feature-tab-' + index" :key="feature.label" role="tab" :aria-selected="activeFeature === index" :aria-controls="'feature-panel-' + index" :tabindex="activeFeature === index ? 0 : -1" :class="{ selected: activeFeature === index }" @click="selectFeature(index)" @keydown.down.prevent="selectFeature((index + 1) % features.length); ($event.currentTarget as HTMLElement).parentElement?.querySelectorAll('button')[(index + 1) % features.length]?.focus()" @keydown.up.prevent="selectFeature((index + features.length - 1) % features.length); ($event.currentTarget as HTMLElement).parentElement?.querySelectorAll('button')[(index + features.length - 1) % features.length]?.focus()"><span class="feature-number">0{{ index + 1 }}</span><div><h3>{{ feature.label }}</h3><p>{{ feature.copy }}</p></div><ArrowUpRight class="size-5 shrink-0" /></button>
            </div>
            <div v-for="(feature, index) in features" v-show="activeFeature === index" :id="'feature-panel-' + index" :key="feature.title" role="tabpanel" :aria-labelledby="'feature-tab-' + index" class="connection-panel" tabindex="0">
              <div class="connection-heading"><component :is="feature.icon" class="size-5" /><span>{{ feature.title }}</span></div>
              <div class="connection-map" aria-hidden="true"><svg viewBox="0 0 560 340" preserveAspectRatio="none"><path d="M90 70 Q280 60 280 170 M470 70 Q280 60 280 170 M90 270 Q280 280 280 170 M470 270 Q280 280 280 170" fill="none" stroke="currentColor" stroke-width="1.5" /><circle cx="180" cy="98" r="4" fill="var(--marketing-teal)" /></svg><div class="map-node node-1"><Code2 />{{ feature.nodes[0] }}</div><div class="map-node node-2"><Award />{{ feature.nodes[1] }}</div><div class="map-node node-3"><BookOpenCheck />{{ feature.nodes[2] }}</div><div class="map-node node-4"><ArrowUpRight />{{ feature.nodes[3] }}</div><div class="map-centre"><Layers3 class="size-7" /><strong>{{ feature.centre }}</strong></div></div>
              <p class="connection-footnote">{{ feature.detail }}</p>
            </div>
          </div>
        </div>
      </section>

      <section id="how-it-works" class="marketing-section walkthrough-section">
        <div class="landing-container walkthrough-layout">
          <div><p class="marketing-eyebrow">See the bigger picture</p><h2>From learning<br />to your <br /><span>next opportunity.</span></h2><p class="section-description">A simple flow that connects your achievements to the opportunities ahead. Here's how it comes together.</p><ol class="walkthrough-steps"><li><span>01</span><div><h3>Add your skills & supporting work</h3><p>Projects, certifications and the evidence behind them.</p></div></li><li><span>02</span><div><h3>Build a verified record</h3><p>Follow reviews and strengthen your profile.</p></div></li><li><span>03</span><div><h3>Explore roles & prepare your CV</h3><p>Understand your fit and make your next move.</p></div></li></ol></div>
          <div class="walkthrough-media"><div class="media-label"><span class="status-dot"></span> THE PRODUCT, IN 19 SECONDS</div><ProductVideo /></div>
        </div>
      </section>

      <section class="marketing-section">
        <div class="landing-container">
          <div class="section-heading-row"><div><p class="marketing-eyebrow">Ready for what comes next</p><h2>Good skills.<br /><span>A clearer direction.</span></h2></div><p>Tools to help you make sense of job requirements and put your experience into words.</p></div>
          <div class="career-features">
            <article class="career-feature"><span class="career-index">01 / DISCOVER</span><div class="career-symbol"><BriefcaseBusiness /></div><h3>Find where your<br />skills fit.</h3><p>Explore current job listings with matched skills, areas to develop and a clear source for every role.</p><div class="feature-tags"><span><Check />Matched skills</span><span>Skill gaps</span><span>Listing source</span></div><RouterLink to="/register">Explore career tools <ArrowUpRight /></RouterLink></article>
            <article class="career-feature cv-feature"><span class="career-index">02 / PREPARE</span><div class="career-symbol"><FileText /></div><h3>Your experience.<br />Well presented.</h3><p>Create from your verified records or upload an existing CV. Organise it with AI, review suggestions with Navi and export when you’re ready.</p><div class="feature-tags"><span>Editable</span><span>PDF</span><span>Word</span></div><RouterLink to="/register">Create or upload your CV <ArrowUpRight /></RouterLink></article>
          </div>
        </div>
      </section>

      <section id="questions" class="marketing-section faq-section"><div class="landing-container faq-layout"><div><p class="marketing-eyebrow">A little more clarity</p><h2>Before<br /><span>you begin.</span></h2></div><div class="faq-list"><details v-for="faq in faqs" :key="faq.question"><summary>{{ faq.question }}<Plus class="size-5 shrink-0" /></summary><p>{{ faq.answer }}</p></details></div></div></section>
      <section class="closing-section"><div class="landing-container"><div class="closing-content"><span class="marketing-eyebrow">Your future is a work in progress.</span><h2>Make your next<br /><span>step count.</span></h2><RouterLink to="/register" class="marketing-button">Create your account <ArrowUpRight class="size-4" /></RouterLink><span class="closing-orbit" aria-hidden="true"></span></div></div></section>
    </main>
    <footer class="marketing-footer"><div class="landing-container"><div class="footer-top"><RouterLink to="/" class="marketing-brand"><BrandLogo size="sm" /></RouterLink><p>Build your skills.<br />Open up your possibilities.</p><a href="#main-content">Back to top <ArrowUpRight class="size-4" /></a></div><div class="footer-bottom"><span>Student skills & career development</span><span>BITU 3923 Workshop 2 · UTeM · {{ new Date().getFullYear() }}</span></div></div></footer>
  </div>
</template>

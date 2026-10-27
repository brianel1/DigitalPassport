import { createRouter, createWebHistory } from 'vue-router'
import NotFoundPage from '@/pages/NotFoundPage.vue'
import PublicLayout from '@/components/layout/PublicLayout.vue'
import StudentLayout from '@/components/layout/StudentLayout.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import LandingPage from '@/pages/public/LandingPage.vue'
import LoginPage from '@/pages/public/LoginPage.vue'
import RegisterPage from '@/pages/public/RegisterPage.vue'
import DashboardPage from '@/pages/student/DashboardPage.vue'
import ProfilePage from '@/pages/student/ProfilePage.vue'
import SkillsPage from '@/pages/student/SkillsPage.vue'
import ProjectsPage from '@/pages/student/ProjectsPage.vue'
import CertificationsPage from '@/pages/student/CertificationsPage.vue'
import AssessmentsPage from '@/pages/student/AssessmentsPage.vue'
import TakeAssessmentPage from '@/pages/student/TakeAssessmentPage.vue'
import PassportPage from '@/pages/student/PassportPage.vue'
import AdminDashboardPage from '@/pages/admin/AdminDashboardPage.vue'
import ReviewSubmissionsPage from '@/pages/admin/ReviewSubmissionsPage.vue'
import ManageAssessmentsPage from '@/pages/admin/ManageAssessmentsPage.vue'
import { useAuth } from '@/composables/useAuth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: PublicLayout,
      children: [
        { path: '', component: LandingPage },
        { path: 'login', component: LoginPage, meta: { guestOnly: true } },
        { path: 'register', component: RegisterPage, meta: { guestOnly: true } },
      ],
    },
    {
      path: '/student',
      component: StudentLayout,
      meta: { requiresAuth: true, role: 'student' },
      children: [
        { path: '', redirect: '/student/dashboard' },
        { path: 'dashboard', component: DashboardPage },
        { path: 'profile', component: ProfilePage },
        { path: 'skills', component: SkillsPage },
        { path: 'projects', component: ProjectsPage },
        { path: 'certifications', component: CertificationsPage },
        { path: 'assessments', component: AssessmentsPage },
        { path: 'assessments/:id', component: TakeAssessmentPage },
        { path: 'passport', component: PassportPage },
      ],
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, role: 'admin' },
      children: [
        { path: '', redirect: '/admin/dashboard' },
        { path: 'dashboard', component: AdminDashboardPage },
        { path: 'reviews', component: ReviewSubmissionsPage },
        { path: 'assessments', component: ManageAssessmentsPage },
      ],
    },
    { path: '/:pathMatch(.*)*', component: NotFoundPage },
  ],
})

router.beforeEach((to) => {
  const { isAuthenticated, isAdmin } = useAuth()

  if (to.meta.requiresAuth && !isAuthenticated.value) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (to.meta.role === 'admin' && !isAdmin.value) return '/student/dashboard'
  if (to.meta.role === 'student' && isAdmin.value) return '/admin/dashboard'

  if (to.meta.guestOnly && isAuthenticated.value) {
    return isAdmin.value ? '/admin/dashboard' : '/student/dashboard'
  }
})

export default router

import { createRouter, createWebHistory } from 'vue-router'
const NotFoundPage = () => import('@/pages/NotFoundPage.vue')
import PublicLayout from '@/components/layout/PublicLayout.vue'
import StudentLayout from '@/components/layout/StudentLayout.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
const LandingPage = () => import('@/pages/public/LandingPage.vue')
const LoginPage = () => import('@/pages/public/LoginPage.vue')
const RegisterPage = () => import('@/pages/public/RegisterPage.vue')
const DashboardPage = () => import('@/pages/student/DashboardPage.vue')
const ProfilePage = () => import('@/pages/student/ProfilePage.vue')
const SkillsPage = () => import('@/pages/student/SkillsPage.vue')
const ProjectsPage = () => import('@/pages/student/ProjectsPage.vue')
const CertificationsPage = () => import('@/pages/student/CertificationsPage.vue')
const AssessmentsPage = () => import('@/pages/student/AssessmentsPage.vue')
const TakeAssessmentPage = () => import('@/pages/student/TakeAssessmentPage.vue')
const PassportPage = () => import('@/pages/student/PassportPage.vue')
const JobRecommendationsPage = () => import('@/pages/student/JobRecommendationsPage.vue')
const CvBuilderPage = () => import('@/pages/student/CvBuilderPage.vue')
const AdminDashboardPage = () => import('@/pages/admin/AdminDashboardPage.vue')
const ReviewSubmissionsPage = () => import('@/pages/admin/ReviewSubmissionsPage.vue')
const ManageAssessmentsPage = () => import('@/pages/admin/ManageAssessmentsPage.vue')
const ManageUsersPage = () => import('@/pages/admin/ManageUsersPage.vue')
import { useAuth } from '@/composables/useAuth'
import { clearFormDrafts } from '@/composables/useFormDraft'
import { readAuth } from '@/lib/authStorage'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: PublicLayout,
      children: [
        { path: '', component: LandingPage },
        { path: 'share/:token', component: () => import('@/pages/public/SharedProfilePage.vue') },
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
        { path: 'skills-profile', component: PassportPage },
        { path: 'passport', redirect: '/student/skills-profile' },
        { path: 'jobs', component: JobRecommendationsPage },
        { path: 'cv', component: CvBuilderPage },
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
        { path: 'users', component: ManageUsersPage },
      ],
    },
    { path: '/:pathMatch(.*)*', component: NotFoundPage },
  ],
})

router.beforeEach(async (to) => {
  const { isAuthenticated, isAdmin, refreshUser, logout } = useAuth()

  // An explicit visit to Sign in always offers a fresh login, including after service restarts.
  if (to.path === '/login') {
    if (to.query.reason !== 'expired') clearFormDrafts()
    await logout()
    return
  }

  const hadSession = !!readAuth()?.token
  if (to.meta.requiresAuth || to.meta.guestOnly) await refreshUser()

  if (to.meta.requiresAuth && !isAuthenticated.value) {
    return { path: '/login', query: { redirect: to.fullPath, ...(hadSession ? {reason:'expired'} : {}) } }
  }

  if (to.meta.role === 'admin' && !isAdmin.value) return '/student/dashboard'
  if (to.meta.role === 'student' && isAdmin.value) return '/admin/dashboard'

  if (to.meta.guestOnly && isAuthenticated.value) {
    return isAdmin.value ? '/admin/dashboard' : '/student/dashboard'
  }
})

window.addEventListener('auth:expired', () => {
  const current = router.currentRoute.value
  if (current.meta.requiresAuth) void router.replace({path:'/login',query:{redirect:current.fullPath,reason:'expired'}})
})

export default router

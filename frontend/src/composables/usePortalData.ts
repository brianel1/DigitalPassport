import { ref, type Ref } from 'vue'
import type {
  Skill,
  Project,
  Certification,
  AssessmentAttempt,
  ActivityItem,
  AdminDashboardStats,
  ReviewItem,
} from '@/types'
import {
  getSkills,
  getProjects,
  getCertifications,
  getAssessments,
  getAdminReviews,
  apiRequest,
} from '@/lib/api'

// ========================
// Shared reactive state (loaded once, reused across pages)
// ========================

const skills: Ref<Skill[]> = ref([])
const projects: Ref<Project[]> = ref([])
const certifications: Ref<Certification[]> = ref([])
const attempts: Ref<AssessmentAttempt[]> = ref([])
const activity: Ref<ActivityItem[]> = ref([])
const reviewItems: Ref<ReviewItem[]> = ref([])
const emptyAdminStats: AdminDashboardStats = {
  pendingReviews: 0,
  totalStudents: 0,
  activeStudents: 0,
  inactiveStudents: 0,
  totalAssessments: 0,
  reviewsThisWeek: 0,
  totalCvGenerated: 0,
  totalRecommendationRuns: 0,
  assessmentAttempts: 0,
  assessmentPassRate: 0,
  userGrowth: [],
  submissionCounts: { pending: 0, verified: 0, rejected: 0 },
  assessmentOutcomes: { passed: 0, notPassed: 0 },
}
const adminStats = ref<AdminDashboardStats>({ ...emptyAdminStats })

let studentLoaded = false
let adminLoaded = false

// ========================
// Composable
// ========================

export function usePortalData() {
  async function loadStudentData() {
    if (studentLoaded) return
    studentLoaded = true
    try {
      const [skillsData, projectsData, certsData, assessmentData] = await Promise.all([
        getSkills(),
        getProjects(),
        getCertifications(),
        getAssessments(),
      ])
      skills.value = skillsData
      projects.value = projectsData
      certifications.value = certsData
      attempts.value = assessmentData.attempts
      activity.value = buildActivityFeed(skillsData, projectsData, certsData, assessmentData.attempts)
    } catch {
      studentLoaded = false
    }
  }

  async function loadAdminData() {
    if (adminLoaded) return
    adminLoaded = true
    try {
      const [reviewData, statsData] = await Promise.all([
        getAdminReviews(),
        apiRequest<AdminDashboardStats>('/api/admin/dashboard-stats'),
      ])
      reviewItems.value = reviewData
      adminStats.value = statsData
    } catch {
      adminLoaded = false
    }
  }

  function refreshStudentData() {
    studentLoaded = false
    return loadStudentData()
  }

  function refreshAdminData() {
    adminLoaded = false
    return loadAdminData()
  }

  return {
    skills,
    projects,
    certifications,
    attempts,
    activity,
    reviewItems,
    adminStats,
    loadStudentData,
    loadAdminData,
    refreshStudentData,
    refreshAdminData,
  }
}

// ========================
// Build activity feed from real data
// ========================

function buildActivityFeed(
  skillsList: Skill[],
  projectsList: Project[],
  certsList: Certification[],
  attemptsList: AssessmentAttempt[],
): ActivityItem[] {
  const items: ActivityItem[] = []
  let nextId = 1

  for (const s of skillsList) {
    items.push({ id: nextId++, type: 'skill_added', description: `Added "${s.name}" to skills portfolio`, timestamp: s.createdAt })
  }
  for (const p of projectsList) {
    items.push({ id: nextId++, type: 'project_submitted', description: `Submitted "${p.title}" for review`, timestamp: p.createdAt })
    if (p.status === 'verified') {
      items.push({ id: nextId++, type: 'review_completed', description: `"${p.title}" has been verified`, timestamp: p.updatedAt })
    }
  }
  for (const c of certsList) {
    items.push({ id: nextId++, type: 'cert_submitted', description: `Submitted "${c.title}" certification`, timestamp: c.createdAt })
    if (c.status === 'verified') {
      items.push({ id: nextId++, type: 'review_completed', description: `"${c.title}" has been verified`, timestamp: c.updatedAt })
    }
  }
  for (const a of attemptsList) {
    items.push({ id: nextId++, type: 'assessment_completed', description: `Completed "${a.assessmentTitle}" with ${a.percentage}% score`, timestamp: a.completedAt })
  }

  items.sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime())
  return items.slice(0, 10)
}

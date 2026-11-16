// ========================
// Core entity types for the skills and career workspace
// ========================

export type UserRole = 'student' | 'admin'

export type VerificationStatus = 'pending' | 'verified' | 'rejected'

export type SkillCategory =
  | 'programming'
  | 'databases'
  | 'web-development'
  | 'cybersecurity'
  | 'data-analysis'
  | 'networking'
  | 'cloud-computing'
  | 'other'

export type ProficiencyLevel = 'beginner' | 'intermediate' | 'advanced' | 'expert'

// ========================
// User & Auth
// ========================

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  isActive?: boolean
  programme?: string
  matricNo?: string
  avatarUrl?: string
  createdAt: string
}

export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
}

// ========================
// Student Profile
// ========================

export interface StudentProfile {
  id: number
  userId: number
  name: string
  email: string
  programme: string
  matricNo: string
  bio?: string
  avatarUrl?: string
  skillsCount: number
  projectsCount: number
  certificationsCount: number
  assessmentsCompleted: number
}

// ========================
// Skills
// ========================

export interface Skill {
  reviewerName?: string | null
  reviewedAt?: string | null
  id: number
  name: string
  category: SkillCategory
  proficiency: ProficiencyLevel
  description: string
  status: VerificationStatus
  linkedProjects: number[]
  linkedCertifications: number[]
  linkedAssessments: number[]
  adminRemarks?: string | null
  createdAt: string
  updatedAt: string
}

// ========================
// Projects
// ========================

export interface Project {
  evidence?: EvidenceFileSummary[]
  reviewerName?: string | null
  reviewedAt?: string | null
  role?: string | null
  repositoryUrl?: string | null
  reviewHistory?: ReviewHistory[]
  id: number
  title: string
  description: string
  technologies: string[]
  projectUrl?: string
  evidenceFiles: string[]
  completionDate: string
  status: VerificationStatus
  linkedSkills: number[]
  adminRemarks?: string
  createdAt: string
  updatedAt: string
}

// ========================
// Certifications
// ========================

export interface Certification {
  evidence?: EvidenceFileSummary[]
  expired?: boolean
  credentialUrl?: string | null
  reviewerName?: string | null
  reviewedAt?: string | null
  reviewHistory?: ReviewHistory[]
  id: number
  title: string
  issuingOrganization: string
  issueDate: string
  expiryDate?: string
  referenceNumber?: string
  evidenceFiles: string[]
  status: VerificationStatus
  linkedSkills: number[]
  adminRemarks?: string
  createdAt: string
  updatedAt: string
}

// ========================
// Assessments
// ========================

export interface AssessmentQuestion {
  id: number
  question: string
  options: string[]
  correctOption?: number
  points?: number
  position?: number
}

export interface Assessment {
  locked?:boolean
  version?:number
  skillNames?:string[]
  id: number
  title: string
  category: SkillCategory
  description: string
  questionCount: number
  timeLimit: number // in minutes
  passingScore: number // percentage
  isPublished?: boolean
  questions?: AssessmentQuestion[]
}

export interface AssessmentAttempt {
  familyId?:number
  feedback?:string
  expired?:boolean
  skillNames?:string[]
  id: number
  assessmentId: number
  assessmentTitle: string
  category: SkillCategory
  score: number
  totalQuestions: number
  percentage: number
  passed: boolean
  completedAt: string
}

// ========================
// Admin Review
// ========================

export interface ReviewItem {
  supportingAssessments?:Array<{title:string;percentage:number;completedAt:string}>
  revision?: number
  changes?: Array<{field:string;before:unknown;after:unknown}>
  reviewHistory?: ReviewHistory[]
  id: number
  type: 'project' | 'certification' | 'skill'
  studentName: string
  studentMatricNo: string
  title: string
  submittedAt: string
  status: VerificationStatus
  evidenceFiles: EvidenceFileSummary[]
  supportingEvidence?: Array<{ expired?:boolean; type: 'project' | 'certification'; id: number; title: string; status: VerificationStatus; projectUrl?: string | null; files: EvidenceFileSummary[] }>
  reviewerName?: string | null
  reviewedAt?: string | null
  adminRemarks?: string | null
  details: Project | Certification | Skill
}

export interface EvidenceFileSummary {
  id: number
  name: string
  mimeType: string
  size: number
}

export interface ReviewHistory { id:number; decision:string; remarks:string|null; reviewerName:string; reviewedAt:string; revision:number|null }

// ========================
// Dashboard Stats
// ========================

export interface StudentDashboardStats {
  totalSkills: number
  verifiedSkills: number
  totalProjects: number
  verifiedProjects: number
  totalCertifications: number
  verifiedCertifications: number
  assessmentsCompleted: number
  averageAssessmentScore: number
}

export interface AdminDashboardStats {
  waitingOverWeek?:number
  oldestPendingDays?:number
  pendingReviews: number
  totalStudents: number
  activeStudents: number
  inactiveStudents: number
  totalAssessments: number
  reviewsThisWeek: number
  totalCvGenerated: number
  totalRecommendationRuns: number
  assessmentAttempts: number
  assessmentPassRate: number
  userGrowth: Array<{ label: string; value: number }>
  submissionCounts: { pending: number; verified: number; rejected: number }
  assessmentOutcomes: { passed: number; notPassed: number }
}

export interface AdminUserSummary {
  id: number
  name: string
  email: string
  programme: string | null
  matricNo: string | null
  isActive: boolean
  createdAt: string
  skillsCount: number
  projectsCount: number
  certificationsCount: number
  assessmentAttemptsCount: number
}

export interface AdminUserDetail extends AdminUserSummary {
  profile: {
    headline?: string | null
    bio?: string | null
    phone?: string | null
    location?: string | null
    linkedin_url?: string | null
    github_url?: string | null
    expected_graduation_date?: string | null
  } | null
  skills: Array<{ id: number; name: string; category: string; proficiency: string; status: VerificationStatus }>
  projects: Array<{ id: number; title: string; status: VerificationStatus; createdAt: string }>
  certifications: Array<{ id: number; title: string; status: VerificationStatus; createdAt: string }>
  assessmentAttempts: Array<{ id: number; assessmentTitle: string | null; percentage: number | null; passed: boolean | null; submittedAt: string | null }>
}

// ========================
// Activity Feed
// ========================

export interface ActivityItem {
  id: number
  type: 'skill_added' | 'project_submitted' | 'cert_submitted' | 'assessment_completed' | 'review_completed'
  description: string
  timestamp: string
}

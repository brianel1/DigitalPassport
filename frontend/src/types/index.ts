// ========================
// Core entity types for DigitalPassport
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
  id: number
  name: string
  category: SkillCategory
  proficiency: ProficiencyLevel
  description: string
  status: VerificationStatus
  linkedProjects: number[]
  linkedCertifications: number[]
  linkedAssessments: number[]
  createdAt: string
  updatedAt: string
}

// ========================
// Projects
// ========================

export interface Project {
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
  correctAnswer: number // index of correct option
}

export interface Assessment {
  id: number
  title: string
  category: SkillCategory
  description: string
  questionCount: number
  timeLimit: number // in minutes
  passingScore: number // percentage
  questions?: AssessmentQuestion[]
}

export interface AssessmentAttempt {
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
  id: number
  type: 'project' | 'certification'
  studentName: string
  studentMatricNo: string
  title: string
  submittedAt: string
  status: VerificationStatus
  evidenceFiles: string[]
  details: Project | Certification
}

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
  pendingReviews: number
  totalStudents: number
  totalAssessments: number
  reviewsThisWeek: number
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

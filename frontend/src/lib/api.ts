import type { AdminUserDetail, AdminUserSummary, Assessment, AssessmentAttempt, Certification, ProficiencyLevel, Project, ReviewItem, Skill, SkillCategory, User } from '@/types'

export interface HealthResponse {
  status: string
  service: string
}

export interface AuthResponse {
  user: User
  token: string
}

export interface StudentProfileResponse extends User {
  headline: string | null
  bio: string | null
  phone: string | null
  location: string | null
  linkedinUrl: string | null
  githubUrl: string | null
  expectedGraduationDate: string | null
}

interface ErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}

export class ApiError extends Error {
  public readonly status: number

  constructor(
    message: string,
    status: number,
  ) {
    super(message)
    this.status = status
  }
}

export async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const saved = localStorage.getItem('dp_auth')
  let token: string | null = null

  if (saved) {
    try {
      token = (JSON.parse(saved) as { token?: string }).token ?? null
    } catch {
      localStorage.removeItem('dp_auth')
    }
  }

  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')
  if (options.body && !(options.body instanceof FormData)) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)

  const response = await fetch(path, { ...options, headers })

  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as ErrorResponse
    const firstValidationError = body.errors
      ? Object.values(body.errors).flat()[0]
      : undefined
    throw new ApiError(firstValidationError ?? body.message ?? 'The request could not be completed.', response.status)
  }

  return (await response.json()) as T
}

export function getApiHealth(): Promise<HealthResponse> {
  return apiRequest<HealthResponse>('/api/health')
}

export function loginUser(email: string, password: string): Promise<AuthResponse> {
  return apiRequest<AuthResponse>('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
}

export function registerUser(data: {
  name: string
  email: string
  password: string
  programme: string
  matricNo: string
}): Promise<AuthResponse> {
  return apiRequest<AuthResponse>('/api/auth/register', {
    method: 'POST',
    body: JSON.stringify({
      name: data.name,
      email: data.email,
      password: data.password,
      password_confirmation: data.password,
      programme: data.programme,
      matric_no: data.matricNo,
    }),
  })
}

export function logoutUser(): Promise<{ message: string }> {
  return apiRequest<{ message: string }>('/api/auth/logout', { method: 'POST' })
}

export function getCurrentUser(): Promise<User> {
  return apiRequest<User>('/api/user')
}

export async function downloadPassport(): Promise<void> {
  const saved = localStorage.getItem('dp_auth')
  const token = saved ? (JSON.parse(saved) as { token?: string }).token : null
  const response = await fetch('/api/student/passport/export', {
    headers: { Accept: 'application/pdf', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
  })

  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as ErrorResponse
    throw new ApiError(body.message ?? 'The passport could not be downloaded.', response.status)
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = 'digital-passport.pdf'
  anchor.click()
  URL.revokeObjectURL(url)
}

export function getStudentProfile(): Promise<StudentProfileResponse> {
  return apiRequest<StudentProfileResponse>('/api/student/profile')
}

export function updateStudentProfile(data: {
  name: string
  programme: string
  matricNo: string
  headline?: string
  bio?: string
  phone?: string
  location?: string
  linkedinUrl?: string
  githubUrl?: string
  expectedGraduationDate?: string
}): Promise<StudentProfileResponse> {
  return apiRequest<StudentProfileResponse>('/api/student/profile', {
    method: 'PUT',
    body: JSON.stringify({
      name: data.name,
      programme: data.programme,
      matric_no: data.matricNo,
      headline: data.headline || null,
      bio: data.bio || null,
      phone: data.phone || null,
      location: data.location || null,
      linkedin_url: data.linkedinUrl || null,
      github_url: data.githubUrl || null,
      expected_graduation_date: data.expectedGraduationDate || null,
    }),
  })
}

export async function getSkills(): Promise<Skill[]> {
  const response = await apiRequest<{ data: Skill[] }>('/api/student/skills')
  return response.data
}

export function createSkill(data: {
  name: string
  category: SkillCategory
  proficiency: ProficiencyLevel
  description: string
}): Promise<Skill> {
  return apiRequest<Skill>('/api/student/skills', {
    method: 'POST',
    body: JSON.stringify(data),
  })
}

export function updateSkill(id: number, data: {
  name: string
  category: SkillCategory
  proficiency: ProficiencyLevel
  description: string
}): Promise<Skill> {
  return apiRequest<Skill>(`/api/student/skills/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  })
}

export function removeSkill(id: number): Promise<{ message: string }> {
  return apiRequest<{ message: string }>(`/api/student/skills/${id}`, { method: 'DELETE' })
}

export async function getProjects(): Promise<Project[]> {
  const response = await apiRequest<{ data: Project[] }>('/api/student/projects')
  return response.data
}

export function createProject(data: {
  title: string
  description: string
  technologies: string[]
  projectUrl?: string
  completionDate: string
  evidence?: File | null
}): Promise<Project> {
  const body = new FormData()
  body.append('title', data.title)
  body.append('description', data.description)
  data.technologies.forEach((technology) => body.append('technologies[]', technology))
  if (data.projectUrl) body.append('project_url', data.projectUrl)
  body.append('completion_date', data.completionDate)
  if (data.evidence) body.append('evidence', data.evidence)

  return apiRequest<Project>('/api/student/projects', { method: 'POST', body })
}

export function removeProject(id: number): Promise<{ message: string }> {
  return apiRequest<{ message: string }>(`/api/student/projects/${id}`, { method: 'DELETE' })
}

export async function getCertifications(): Promise<Certification[]> {
  const response = await apiRequest<{ data: Certification[] }>('/api/student/certifications')
  return response.data
}

export function createCertification(data: {
  title: string
  issuingOrganization: string
  issueDate: string
  expiryDate?: string
  referenceNumber?: string
  evidence: File
}): Promise<Certification> {
  const body = new FormData()
  body.append('title', data.title)
  body.append('issuing_organization', data.issuingOrganization)
  body.append('issue_date', data.issueDate)
  if (data.expiryDate) body.append('expiry_date', data.expiryDate)
  if (data.referenceNumber) body.append('credential_id', data.referenceNumber)
  body.append('evidence', data.evidence)

  return apiRequest<Certification>('/api/student/certifications', { method: 'POST', body })
}

export function removeCertification(id: number): Promise<{ message: string }> {
  return apiRequest<{ message: string }>(`/api/student/certifications/${id}`, { method: 'DELETE' })
}

export interface AssessmentOverviewResponse {
  assessments: Assessment[]
  attempts: AssessmentAttempt[]
}

export interface StartedAssessmentResponse {
  attemptId: number
  startedAt: string
  expiresAt: string
  assessment: Assessment
}

export interface JobPreferenceResponse {
  preferredFields: string[]
  preferredLocations: string[]
  workTypes: string[]
  acceptsRemote: boolean
  minimumSalary: number | null
  maximumSalary: number | null
}

export interface JobRecommendationMatch {
  id: number
  rank: number
  matchScore: number
  matchedSkills: string[]
  missingSkills: string[]
  explanation: string
  job: {
    id: number
    title: string
    company: string
    location: string | null
    workType: string | null
    salaryMin: number | null
    salaryMax: number | null
    salaryCurrency: string | null
    description: string
    source: string
    applicationUrl: string
    publishedAt: string | null
  }
}

export interface JobRecommendationResponse {
  runId: number | null
  generatedAt?: string
  matches: JobRecommendationMatch[]
}

export interface CvContent {
  personal: { name: string; email: string; phone: string; location: string; headline: string }
  summary: string
  skills: string[]
  education: Array<{ institution: string; qualification: string; period: string }>
  projects: Array<{ title: string; description: string; technologies: string[] }>
  certifications: Array<{ title: string; issuer: string; issueDate: string }>
  assessments: Array<{ title: string; percentage: number; completedAt: string }>
}

export interface GeneratedCvResponse {
  id: number
  title: string
  content: CvContent
  version: number
  status: string
  createdAt: string
  updatedAt: string
}

export function getAssessments(): Promise<AssessmentOverviewResponse> {
  return apiRequest<AssessmentOverviewResponse>('/api/student/assessments')
}

export function startAssessment(id: number): Promise<StartedAssessmentResponse> {
  return apiRequest<StartedAssessmentResponse>(`/api/student/assessments/${id}/attempts`, { method: 'POST' })
}

export function submitAssessment(attemptId: number, answers: Record<number, number>): Promise<AssessmentAttempt> {
  return apiRequest<AssessmentAttempt>(`/api/student/assessment-attempts/${attemptId}/submit`, {
    method: 'POST',
    body: JSON.stringify({
      answers: Object.entries(answers).map(([questionId, selectedOption]) => ({
        question_id: Number(questionId),
        selected_option: selectedOption,
      })),
    }),
  })
}

export async function getAdminAssessments(): Promise<Assessment[]> {
  const response = await apiRequest<{ data: Assessment[] }>('/api/admin/assessments')
  return response.data
}

export function getAdminAssessment(id: number): Promise<Assessment> {
  return apiRequest<Assessment>(`/api/admin/assessments/${id}`)
}

export function createAssessment(data: {
  title: string
  category: SkillCategory
  description: string
  timeLimit: number
  passingScore: number
}): Promise<Assessment> {
  return apiRequest<Assessment>('/api/admin/assessments', {
    method: 'POST',
    body: JSON.stringify({
      title: data.title,
      category: data.category,
      description: data.description,
      time_limit: data.timeLimit,
      passing_score: data.passingScore,
    }),
  })
}

export function removeAssessment(id: number): Promise<{ message: string }> {
  return apiRequest<{ message: string }>(`/api/admin/assessments/${id}`, { method: 'DELETE' })
}

export function updateAssessment(id: number, data: {
  title: string
  category: SkillCategory
  description: string
  timeLimit: number
  passingScore: number
}): Promise<Assessment> {
  return apiRequest<Assessment>(`/api/admin/assessments/${id}`, {
    method: 'PUT',
    body: JSON.stringify({
      title: data.title,
      category: data.category,
      description: data.description,
      time_limit: data.timeLimit,
      passing_score: data.passingScore,
    }),
  })
}

export function toggleAssessmentPublish(id: number): Promise<{ message: string; isPublished: boolean }> {
  return apiRequest<{ message: string; isPublished: boolean }>(`/api/admin/assessments/${id}/toggle-publish`, { method: 'POST' })
}

export interface AssessmentQuestionInput {
  question: string
  options: string[]
  correctOption: number
  points: number
}

export function createAssessmentQuestion(id: number, data: AssessmentQuestionInput): Promise<NonNullable<Assessment['questions']>[number]> {
  return apiRequest<NonNullable<Assessment['questions']>[number]>(`/api/admin/assessments/${id}/questions`, {
    method: 'POST',
    body: JSON.stringify({
      question: data.question,
      options: data.options,
      correct_option: data.correctOption,
      points: data.points,
    }),
  })
}

export function updateAssessmentQuestion(
  assessmentId: number,
  questionId: number,
  data: AssessmentQuestionInput,
): Promise<NonNullable<Assessment['questions']>[number]> {
  return apiRequest<NonNullable<Assessment['questions']>[number]>(`/api/admin/assessments/${assessmentId}/questions/${questionId}`, {
    method: 'PUT',
    body: JSON.stringify({
      question: data.question,
      options: data.options,
      correct_option: data.correctOption,
      points: data.points,
    }),
  })
}

export function removeAssessmentQuestion(assessmentId: number, questionId: number): Promise<{ message: string }> {
  return apiRequest<{ message: string }>(`/api/admin/assessments/${assessmentId}/questions/${questionId}`, { method: 'DELETE' })
}

export async function getAdminReviews(): Promise<ReviewItem[]> {
  const response = await apiRequest<{ data: ReviewItem[] }>('/api/admin/reviews')
  return response.data
}

export function getAdminReviewsFiltered(params: { status?: 'pending' | 'verified' | 'rejected' | 'all'; type?: 'project' | 'certification' | 'skill' | 'all'; search?: string }): Promise<ReviewItem[]> {
  const query = new URLSearchParams()
  if (params.status) query.set('status', params.status)
  if (params.type) query.set('type', params.type)
  if (params.search) query.set('search', params.search)
  return apiRequest<{ data: ReviewItem[] }>(`/api/admin/reviews?${query.toString()}`).then((response) => response.data)
}

export async function downloadAdminEvidence(item: Pick<ReviewItem, 'type' | 'id'>, file: { id: number; name: string }): Promise<void> {
  const saved = localStorage.getItem('dp_auth')
  const token = saved ? (JSON.parse(saved) as { token?: string }).token : null
  const response = await fetch(`/api/admin/reviews/${item.type}/${item.id}/evidence/${file.id}`, {
    headers: { ...(token ? { Authorization: `Bearer ${token}` } : {}) },
  })

  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as ErrorResponse
    throw new ApiError(body.message ?? 'The evidence file could not be downloaded.', response.status)
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = file.name
  anchor.click()
  URL.revokeObjectURL(url)
}

export function getAdminUsers(params: { search?: string; status?: 'active' | 'inactive'; programme?: string } = {}): Promise<{ data: AdminUserSummary[]; meta: { total: number; programmes: string[] } }> {
  const query = new URLSearchParams()
  if (params.search) query.set('search', params.search)
  if (params.status) query.set('status', params.status)
  if (params.programme) query.set('programme', params.programme)
  return apiRequest<{ data: AdminUserSummary[]; meta: { total: number; programmes: string[] } }>(`/api/admin/users?${query.toString()}`)
}

export function updateAdminUserStatus(id: number, isActive: boolean): Promise<{ message: string; user: AdminUserSummary }> {
  return apiRequest<{ message: string; user: AdminUserSummary }>(`/api/admin/users/${id}/status`, {
    method: 'PUT',
    body: JSON.stringify({ is_active: isActive }),
  })
}

export function getAdminUser(id: number): Promise<AdminUserDetail> {
  return apiRequest<AdminUserDetail>(`/api/admin/users/${id}`)
}

export function updateAdminReview(
  item: Pick<ReviewItem, 'id' | 'type'>,
  decision: 'verified' | 'rejected',
  remarks: string,
): Promise<{ message: string }> {
  return apiRequest<{ message: string }>(`/api/admin/reviews/${item.type}/${item.id}`, {
    method: 'PUT',
    body: JSON.stringify({ decision, remarks: remarks || null }),
  })
}

export function getJobPreferences(): Promise<JobPreferenceResponse> {
  return apiRequest<JobPreferenceResponse>('/api/student/job-preferences')
}

export function updateJobPreferences(data: JobPreferenceResponse): Promise<JobPreferenceResponse> {
  return apiRequest<JobPreferenceResponse>('/api/student/job-preferences', {
    method: 'PUT',
    body: JSON.stringify({
      preferred_fields: data.preferredFields,
      preferred_locations: data.preferredLocations,
      work_types: data.workTypes,
      accepts_remote: data.acceptsRemote,
      minimum_salary: data.minimumSalary,
      maximum_salary: data.maximumSalary,
    }),
  })
}

export function getLatestJobRecommendations(): Promise<JobRecommendationResponse> {
  return apiRequest<JobRecommendationResponse>('/api/student/job-recommendations')
}

export function generateJobRecommendations(): Promise<JobRecommendationResponse> {
  return apiRequest<JobRecommendationResponse>('/api/student/job-recommendations', { method: 'POST' })
}

export async function getGeneratedCvs(): Promise<GeneratedCvResponse[]> {
  const response = await apiRequest<{ data: GeneratedCvResponse[] }>('/api/student/cvs')
  return response.data
}

export function generateCv(): Promise<GeneratedCvResponse> {
  return apiRequest<GeneratedCvResponse>('/api/student/cvs', { method: 'POST' })
}

export function updateCv(cv: Pick<GeneratedCvResponse, 'id' | 'title' | 'content'>): Promise<GeneratedCvResponse> {
  return apiRequest<GeneratedCvResponse>(`/api/student/cvs/${cv.id}`, {
    method: 'PUT',
    body: JSON.stringify({ title: cv.title, content: cv.content }),
  })
}

export async function downloadCv(cv: GeneratedCvResponse, format: 'pdf' | 'word'): Promise<void> {
  const saved = localStorage.getItem('dp_auth')
  const token = saved ? (JSON.parse(saved) as { token?: string }).token : null
  const response = await fetch(`/api/student/cvs/${cv.id}/export/${format}`, {
    headers: { Accept: 'application/octet-stream', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
  })

  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as ErrorResponse
    throw new ApiError(body.message ?? 'The CV could not be downloaded.', response.status)
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = `${cv.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}.${format === 'pdf' ? 'pdf' : 'docx'}`
  anchor.click()
  URL.revokeObjectURL(url)
}

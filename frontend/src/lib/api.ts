import type { AdminUserDetail, AdminUserSummary, Assessment, AssessmentAttempt, Certification, ProficiencyLevel, Project, ReviewItem, Skill, SkillCategory, User } from '@/types'
import { expireAuth, readAuth } from './authStorage'

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
  public readonly fields: Record<string, string[]>

  constructor(
    message: string,
    status: number,
    fields: Record<string, string[]> = {},
  ) {
    super(message)
    this.status = status
    this.fields = fields
  }
}

async function authenticatedFetch(path: string, options: RequestInit = {}): Promise<Response> {
  const token = readAuth()?.token ?? null

  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')
  if (options.body && !(options.body instanceof FormData)) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)

  const response = await fetch(path, { ...options, headers })
  if (response.status === 401) expireAuth(token)
  return response
}

export async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await authenticatedFetch(path, options)
  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as ErrorResponse
    const firstValidationError = body.errors
      ? Object.values(body.errors).flat()[0]
      : undefined
    throw new ApiError(firstValidationError ?? body.message ?? 'The request could not be completed.', response.status, body.errors ?? {})
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
  return apiRequest<{ message: string }>('/api/auth/logout', { method: 'POST', signal: AbortSignal.timeout(5000) })
}

export function getCurrentUser(): Promise<User> {
  return apiRequest<User>('/api/user', { signal: AbortSignal.timeout(10000) })
}

export async function downloadPassport(): Promise<void> {
  const response = await authenticatedFetch('/api/student/passport/export')

  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as ErrorResponse
    throw new ApiError(body.message ?? 'The skills profile could not be downloaded.', response.status)
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = 'skills-profile.pdf'
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
  role?: string
  repositoryUrl?: string
  title: string
  description: string
  technologies: string[]
  projectUrl?: string
  completionDate: string
  evidence?: File | null
  skillIds?: number[]
}, editingId?:number|null): Promise<Project> {
  const body = new FormData()
  if(editingId) body.append('_method','PUT')
  body.append('role',data.role??'')
  body.append('repository_url',data.repositoryUrl??'')
  body.append('title', data.title)
  body.append('description', data.description)
  data.technologies.forEach((technology) => body.append('technologies[]', technology))
  body.append('project_url', data.projectUrl??'')
  body.append('completion_date', data.completionDate)
  if (data.evidence) body.append('evidence', data.evidence)
  data.skillIds?.forEach((id) => body.append('skill_ids[]', String(id)))

  return apiRequest<Project>(`/api/student/projects${editingId?'/'+editingId:''}`, { method: 'POST', body })
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
  evidence: File | null
  credentialUrl?: string
  skillIds?: number[]
}, editingId?:number|null): Promise<Certification> {
  const body = new FormData()
  if(editingId)body.append('_method','PUT')
  body.append('credential_url',data.credentialUrl??'')
  body.append('title', data.title)
  body.append('issuing_organization', data.issuingOrganization)
  body.append('issue_date', data.issueDate)
  body.append('expiry_date', data.expiryDate??'')
  body.append('credential_id', data.referenceNumber??'')
  if(data.evidence)body.append('evidence', data.evidence)
  data.skillIds?.forEach((id) => body.append('skill_ids[]', String(id)))

  return apiRequest<Certification>(`/api/student/certifications${editingId?'/'+editingId:''}`, { method: 'POST', body })
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

export interface StudentProgressResponse {
  checklist:Array<{label:string;done:boolean;path:string}>
  current:Record<string,number|null>;previous:Record<string,number|null>|null;previousVisit:string|null
  gaps:Array<{skill:string;roles:number}>;comparedRoles:number
}
export function visitStudentProgress():Promise<StudentProgressResponse>{return apiRequest('/api/student/progress/visit',{method:'POST'})}

export interface JobRecommendationMatch {
  feedbackReason?: string | null
  feedback?: 'relevant' | 'not_relevant' | null
  scoreBreakdown: {
    assessable: boolean; matchedCount: number; requirementCount: number; skillCoverage: number; preferenceFactor: number
    preferences: Array<{label:string; matched:boolean; weight:number}>
    salaryCompared: boolean; formula: string; basis: string; limits: string
  }
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
    fetchedAt: string | null
    descriptionScope?: string
  }
}

export interface JobRecommendationResponse {
  catalogue?: { total:number; sources:Array<{source:string;count:number}>; lastUpdatedAt:string|null }
  aiInsights?: { picks: Array<{jobId:number;title:string;note:string}>; reviewedAt: string } | null
  runId: number | null
  generatedAt?: string
  modelName?: string | null
  matches: JobRecommendationMatch[]
}

export interface CvContent {
  personal: { name: string; email: string; phone: string; location: string; headline: string; website?: string }
  summary: string
  skills: string[]
  education: Array<{ institution: string; qualification: string; period: string; details?: string }>
  projects: Array<{ title: string; description: string; technologies: string[] }>
  certifications: Array<{ title: string; issuer: string; issueDate: string }>
  assessments: Array<{ title: string; percentage: number; completedAt: string }>
  experience: Array<{ role: string; company: string; period: string; description: string }>
  additional: string
}

export interface CvAnalysis {
  checks: Array<{ key: string; label: string; passed: boolean; tip: string }>
  passedChecks: number
  verifiedSkills: string[]
  unverifiedSkills: string[]
  jobCoverage: { mentioned: string[]; missing: string[]; total: number }
  strengths: string[]
  suggestions: string[]
  rewrites: Array<{ field: string; original: string; suggested: string; reason: string }>
  engine: string
  reviewedAt: string
}

export interface GeneratedCvResponse {
  extractionMethod?: string | null
  id: number
  title: string
  content: CvContent
  version: number
  status: string
  modelName?: string | null
  sourceType: 'profile' | 'upload' | 'paste'
  sourceFilename?: string | null
  sourceText?: string | null
  hasSourceFile: boolean
  analysis: CvAnalysis | null
  targetJob?: { id: number; title: string; company: string } | null
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
  skillNames?:string[]
}): Promise<Assessment> {
  return apiRequest<Assessment>('/api/admin/assessments', {
    method: 'POST',
    body: JSON.stringify({
      title: data.title,
      category: data.category,
      description: data.description,
      time_limit: data.timeLimit,
      passing_score: data.passingScore,
      skill_names:data.skillNames??[],
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
  skillNames?:string[]
}): Promise<Assessment> {
  return apiRequest<Assessment>(`/api/admin/assessments/${id}`, {
    method: 'PUT',
    body: JSON.stringify({
      title: data.title,
      category: data.category,
      description: data.description,
      time_limit: data.timeLimit,
      passing_score: data.passingScore,
      skill_names:data.skillNames??[],
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

export function getAdminReviewsFiltered(params: { status?: 'pending' | 'verified' | 'rejected' | 'all'; type?: 'project' | 'certification' | 'skill' | 'all'; search?: string; ageDays?: number }): Promise<ReviewItem[]> {
  const query = new URLSearchParams()
  if (params.status) query.set('status', params.status)
  if (params.ageDays) query.set('age_days',String(params.ageDays))
  if (params.type) query.set('type', params.type)
  if (params.search) query.set('search', params.search)
  return apiRequest<{ data: ReviewItem[] }>(`/api/admin/reviews?${query.toString()}`).then((response) => response.data)
}

export async function downloadAdminEvidence(item: Pick<ReviewItem, 'type' | 'id'>, file: { id: number; name: string }): Promise<void> {
  const response = await authenticatedFetch(`/api/admin/reviews/${item.type}/${item.id}/evidence/${file.id}`)

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

export async function getEvidenceBlob(id:number):Promise<Blob> {
  const response=await authenticatedFetch(`/api/evidence/${id}`)
  if(!response.ok)throw new ApiError('This file could not be opened. It may have been removed; ask the owner to upload it again.',response.status)
  return response.blob()
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

export function generateJobRecommendations(query?:string): Promise<JobRecommendationResponse> {
  return apiRequest<JobRecommendationResponse>('/api/student/job-recommendations', { method: 'POST', body:JSON.stringify({query}) })
}

export function saveJobFeedback(id:number, rating:'relevant'|'not_relevant', reason?:string):Promise<{message:string}> {
  return apiRequest(`/api/student/jobs/${id}/feedback`, {method:'PUT',body:JSON.stringify({rating,reason})})
}

export async function getGeneratedCvs(): Promise<GeneratedCvResponse[]> {
  const response = await apiRequest<{ data: GeneratedCvResponse[] }>('/api/student/cvs')
  return response.data
}

export function generateCv(targetJobId?: number, title?: string): Promise<GeneratedCvResponse> {
  return apiRequest<GeneratedCvResponse>('/api/student/cvs', {
    method: 'POST',
    body: JSON.stringify({ target_job_listing_id: targetJobId, title }),
  })
}

export function updateCv(cv: Pick<GeneratedCvResponse, 'id' | 'title' | 'content'>): Promise<GeneratedCvResponse> {
  return apiRequest<GeneratedCvResponse>(`/api/student/cvs/${cv.id}`, {
    method: 'PUT',
    body: JSON.stringify({ title: cv.title, content: cv.content }),
  })
}

export function renameCv(id: number, title: string): Promise<GeneratedCvResponse> {
  return apiRequest(`/api/student/cvs/${id}/name`, { method: 'PATCH', body: JSON.stringify({ title }) })
}

export async function downloadCv(cv: GeneratedCvResponse, format: 'pdf' | 'word'): Promise<void> {
  const response = await authenticatedFetch(`/api/student/cvs/${cv.id}/export/${format}`)

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

export function uploadCv(file: File | null, text: string, title?: string): Promise<GeneratedCvResponse> {
  const body = new FormData()
  if (title) body.append('title', title)
  if (file) body.append('file', file)
  else body.append('text', text)
  return apiRequest('/api/student/cvs/upload', { method: 'POST', body })
}
export function structureCv(id: number, sourceText: string): Promise<GeneratedCvResponse> {
  return apiRequest(`/api/student/cvs/${id}/structure`, { method: 'POST', body: JSON.stringify({ source_text: sourceText }) })
}
export function reviewCv(id: number): Promise<GeneratedCvResponse> {
  return apiRequest(`/api/student/cvs/${id}/review`, { method: 'POST' })
}
export function duplicateCv(id: number, targetJobId?: number): Promise<GeneratedCvResponse> {
  return apiRequest(`/api/student/cvs/${id}/duplicate`, { method: 'POST', body: JSON.stringify({ target_job_listing_id: targetJobId ?? null }) })
}
export function removeCv(id: number): Promise<{ message: string }> {
  return apiRequest(`/api/student/cvs/${id}`, { method: 'DELETE' })
}
export async function downloadCvSource(cv: GeneratedCvResponse): Promise<void> {
  const response = await authenticatedFetch(`/api/student/cvs/${cv.id}/source`)
  if (!response.ok) throw new ApiError('The source file could not be downloaded.', response.status)
  const url = URL.createObjectURL(await response.blob())
  const link = document.createElement('a')
  link.href = url
  link.download = cv.sourceFilename || 'original-cv'
  link.click()
  URL.revokeObjectURL(url)
}

export interface AssistantMessage {
  role: 'user' | 'assistant'
  content: string
  engine?: string
  sources?: Array<{ label: string; detail: string }>
  actions?: Array<{ label: string; path: string }>
  createdAt: string
}
export interface AssistantSession {
  id: number
  language: 'en' | 'ms' | null
  title: string
  mode: 'coach' | 'interview'
  cvId: number | null
  messages: AssistantMessage[]
  updatedAt: string
}
export async function getAssistantSessions(): Promise<AssistantSession[]> {
  return (await apiRequest<{ data: AssistantSession[] }>('/api/assistant/sessions')).data
}
export function createAssistantSession(mode: 'coach' | 'interview', cvId?: number, language?: 'en' | 'ms'): Promise<AssistantSession> {
  return apiRequest('/api/assistant/sessions', { method: 'POST', body: JSON.stringify({ mode, cv_id: cvId ?? null, language }) })
}
export function getAssistantSession(id: number): Promise<AssistantSession> {
  return apiRequest(`/api/assistant/sessions/${id}`)
}
export function sendAssistantMessage(id: number, message: string, cvId?: number): Promise<AssistantSession> {
  return apiRequest(`/api/assistant/sessions/${id}/messages`, { method: 'POST', body: JSON.stringify({ message, ...(cvId ? {cv_id:cvId} : {}) }) })
}
export function deleteAssistantSession(id: number): Promise<{ message: string }> {
  return apiRequest(`/api/assistant/sessions/${id}`, { method: 'DELETE' })
}

export function createAssessmentVersion(id:number):Promise<Assessment>{return apiRequest(`/api/admin/assessments/${id}/versions`,{method:'POST'})}

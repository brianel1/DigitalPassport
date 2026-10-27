import { ref } from 'vue'
import type {
  Skill,
  Project,
  Certification,
  Assessment,
  AssessmentAttempt,
  StudentDashboardStats,
  AdminDashboardStats,
  ActivityItem,
  ReviewItem,
  AssessmentQuestion,
} from '@/types'

// ========================
// Portal Skills
// ========================

const portalSkills = ref<Skill[]>([
  {
    id: 1,
    name: 'JavaScript',
    category: 'programming',
    proficiency: 'advanced',
    description: 'ES6+, async/await, DOM manipulation, Node.js basics',
    status: 'verified',
    linkedProjects: [1],
    linkedCertifications: [],
    linkedAssessments: [1],
    createdAt: '2026-09-15',
    updatedAt: '2026-09-20',
  },
  {
    id: 2,
    name: 'MySQL Database',
    category: 'databases',
    proficiency: 'intermediate',
    description: 'Schema design, queries, joins, indexing, stored procedures',
    status: 'verified',
    linkedProjects: [1, 2],
    linkedCertifications: [],
    linkedAssessments: [2],
    createdAt: '2026-09-16',
    updatedAt: '2026-09-20',
  },
  {
    id: 3,
    name: 'Vue.js',
    category: 'web-development',
    proficiency: 'intermediate',
    description: 'Vue 3 Composition API, Vue Router, component design',
    status: 'pending',
    linkedProjects: [1],
    linkedCertifications: [],
    linkedAssessments: [],
    createdAt: '2026-10-01',
    updatedAt: '2026-10-01',
  },
  {
    id: 4,
    name: 'Python',
    category: 'programming',
    proficiency: 'beginner',
    description: 'Basic scripting, data types, file handling',
    status: 'pending',
    linkedProjects: [],
    linkedCertifications: [2],
    linkedAssessments: [],
    createdAt: '2026-10-05',
    updatedAt: '2026-10-05',
  },
  {
    id: 5,
    name: 'Network Security',
    category: 'cybersecurity',
    proficiency: 'beginner',
    description: 'Firewalls, encryption basics, threat analysis',
    status: 'rejected',
    linkedProjects: [],
    linkedCertifications: [],
    linkedAssessments: [],
    createdAt: '2026-10-10',
    updatedAt: '2026-10-12',
  },
])

// ========================
// Portal Projects
// ========================

const portalProjects = ref<Project[]>([
  {
    id: 1,
    title: 'E-Commerce Web Application',
    description: 'Full-stack web application with product catalog, cart, checkout, and payment gateway integration. Built as a semester project.',
    technologies: ['Vue.js', 'PHP', 'MySQL', 'Tailwind CSS'],
    projectUrl: 'https://github.com/elber/ecommerce-app',
    evidenceFiles: ['screenshot-homepage.png', 'project-report.pdf'],
    completionDate: '2026-06-15',
    status: 'verified',
    linkedSkills: [1, 2, 3],
    createdAt: '2026-09-20',
    updatedAt: '2026-09-25',
  },
  {
    id: 2,
    title: 'Student Attendance System',
    description: 'QR code-based attendance tracking system for university lectures. Features dashboard analytics and export functionality.',
    technologies: ['PHP', 'MySQL', 'Bootstrap', 'JavaScript'],
    projectUrl: 'https://github.com/elber/attendance-system',
    evidenceFiles: ['demo-video.mp4'],
    completionDate: '2026-03-20',
    status: 'pending',
    linkedSkills: [1, 2],
    createdAt: '2026-10-01',
    updatedAt: '2026-10-01',
  },
  {
    id: 3,
    title: 'Weather Dashboard',
    description: 'Real-time weather monitoring dashboard consuming external API data with charts and forecasts.',
    technologies: ['JavaScript', 'HTML', 'CSS', 'REST API'],
    evidenceFiles: [],
    completionDate: '2026-08-10',
    status: 'pending',
    linkedSkills: [1],
    createdAt: '2026-10-05',
    updatedAt: '2026-10-05',
  },
])

// ========================
// Portal Certifications
// ========================

const portalCertifications = ref<Certification[]>([
  {
    id: 1,
    title: 'AWS Cloud Practitioner',
    issuingOrganization: 'Amazon Web Services',
    issueDate: '2026-05-10',
    expiryDate: '2029-05-10',
    referenceNumber: 'AWS-CP-2026-1234',
    evidenceFiles: ['aws-cert.pdf'],
    status: 'verified',
    linkedSkills: [],
    createdAt: '2026-09-15',
    updatedAt: '2026-09-20',
  },
  {
    id: 2,
    title: 'Python for Data Science',
    issuingOrganization: 'Coursera / IBM',
    issueDate: '2026-08-22',
    referenceNumber: 'COURSERA-PDS-5678',
    evidenceFiles: ['coursera-cert.pdf'],
    status: 'pending',
    linkedSkills: [4],
    createdAt: '2026-10-01',
    updatedAt: '2026-10-01',
  },
  {
    id: 3,
    title: 'Google IT Support Professional',
    issuingOrganization: 'Google / Coursera',
    issueDate: '2026-07-15',
    referenceNumber: 'GOOGLE-IT-9012',
    evidenceFiles: ['google-it-cert.pdf'],
    status: 'rejected',
    linkedSkills: [],
    adminRemarks: 'Certificate image is unclear. Please re-upload a higher resolution scan.',
    createdAt: '2026-10-05',
    updatedAt: '2026-10-08',
  },
])

// ========================
// Portal Assessments
// ========================

const portalAssessmentQuestions: Record<number, AssessmentQuestion[]> = {
  1: [
    { id: 1, question: 'What does "DOM" stand for in web development?', options: ['Document Object Model', 'Data Output Module', 'Digital Operation Manager', 'Document Oriented Middleware'], correctAnswer: 0 },
    { id: 2, question: 'Which keyword is used to declare a constant in JavaScript?', options: ['var', 'let', 'const', 'static'], correctAnswer: 2 },
    { id: 3, question: 'What is the output of typeof null in JavaScript?', options: ['"null"', '"undefined"', '"object"', '"boolean"'], correctAnswer: 2 },
    { id: 4, question: 'Which method converts a JSON string to a JavaScript object?', options: ['JSON.stringify()', 'JSON.parse()', 'JSON.convert()', 'JSON.decode()'], correctAnswer: 1 },
    { id: 5, question: 'What does the "===" operator check in JavaScript?', options: ['Value only', 'Type only', 'Value and type', 'Reference'], correctAnswer: 2 },
  ],
  2: [
    { id: 1, question: 'Which SQL statement is used to retrieve data from a database?', options: ['GET', 'FETCH', 'SELECT', 'RETRIEVE'], correctAnswer: 2 },
    { id: 2, question: 'What does the JOIN clause do in SQL?', options: ['Merges two tables into one', 'Combines rows from two or more tables', 'Creates a new table', 'Deletes duplicate rows'], correctAnswer: 1 },
    { id: 3, question: 'Which SQL keyword is used to sort results?', options: ['SORT BY', 'ORDER BY', 'GROUP BY', 'ARRANGE BY'], correctAnswer: 1 },
    { id: 4, question: 'What is a PRIMARY KEY?', options: ['The first column in a table', 'A unique identifier for each row', 'A foreign key reference', 'An auto-increment field'], correctAnswer: 1 },
    { id: 5, question: 'Which SQL clause is used to filter groups?', options: ['WHERE', 'FILTER', 'HAVING', 'GROUP FILTER'], correctAnswer: 2 },
  ],
  3: [
    { id: 1, question: 'What does HTML stand for?', options: ['Hyper Text Markup Language', 'High Tech Modern Language', 'Hyper Transfer Machine Language', 'Home Tool Markup Language'], correctAnswer: 0 },
    { id: 2, question: 'Which CSS property is used to change text color?', options: ['text-color', 'font-color', 'color', 'foreground'], correctAnswer: 2 },
    { id: 3, question: 'What does the "box-sizing: border-box" property do?', options: ['Adds a border to all elements', 'Includes padding and border in element total width', 'Removes all margins', 'Centers the element'], correctAnswer: 1 },
    { id: 4, question: 'Which HTML element is used for the largest heading?', options: ['<heading>', '<h6>', '<h1>', '<head>'], correctAnswer: 2 },
    { id: 5, question: 'What is flexbox used for?', options: ['Database queries', 'Server configuration', 'One-dimensional layout', 'Animation effects'], correctAnswer: 2 },
  ],
}

const portalAssessments = ref<Assessment[]>([
  {
    id: 1,
    title: 'JavaScript Fundamentals',
    category: 'programming',
    description: 'Test your knowledge of JavaScript basics including variables, functions, DOM, and ES6 features.',
    questionCount: 5,
    timeLimit: 10,
    passingScore: 60,
  },
  {
    id: 2,
    title: 'SQL & Database Basics',
    category: 'databases',
    description: 'Assess your understanding of SQL queries, table relationships, and database concepts.',
    questionCount: 5,
    timeLimit: 10,
    passingScore: 60,
  },
  {
    id: 3,
    title: 'Web Development Essentials',
    category: 'web-development',
    description: 'Test your knowledge of HTML, CSS, and responsive web design fundamentals.',
    questionCount: 5,
    timeLimit: 10,
    passingScore: 60,
  },
])

const portalAttempts = ref<AssessmentAttempt[]>([
  {
    id: 1,
    assessmentId: 1,
    assessmentTitle: 'JavaScript Fundamentals',
    category: 'programming',
    score: 4,
    totalQuestions: 5,
    percentage: 80,
    passed: true,
    completedAt: '2026-10-10T14:30:00',
  },
  {
    id: 2,
    assessmentId: 2,
    assessmentTitle: 'SQL & Database Basics',
    category: 'databases',
    score: 3,
    totalQuestions: 5,
    percentage: 60,
    passed: true,
    completedAt: '2026-10-12T10:15:00',
  },
])

// ========================
// Portal Activity Feed
// ========================

const portalActivity: ActivityItem[] = [
  { id: 1, type: 'assessment_completed', description: 'Completed "JavaScript Fundamentals" assessment with 80% score', timestamp: '2026-10-10T14:30:00' },
  { id: 2, type: 'project_submitted', description: 'Submitted "Student Attendance System" for review', timestamp: '2026-10-01T09:00:00' },
  { id: 3, type: 'cert_submitted', description: 'Submitted "Python for Data Science" certification', timestamp: '2026-10-01T08:30:00' },
  { id: 4, type: 'skill_added', description: 'Added "Vue.js" to skills portfolio', timestamp: '2026-10-01T08:00:00' },
  { id: 5, type: 'review_completed', description: '"E-Commerce Web Application" has been verified', timestamp: '2026-09-25T16:00:00' },
]

// ========================
// Portal Admin Review Items
// ========================

const portalReviewItems = ref<ReviewItem[]>([
  {
    id: 1,
    type: 'project',
    studentName: 'Elber Anselmus',
    studentMatricNo: 'B032510581',
    title: 'Student Attendance System',
    submittedAt: '2026-10-01',
    status: 'pending',
    evidenceFiles: ['demo-video.mp4'],
    details: portalProjects.value[1],
  },
  {
    id: 2,
    type: 'certification',
    studentName: 'Elber Anselmus',
    studentMatricNo: 'B032510581',
    title: 'Python for Data Science',
    submittedAt: '2026-10-01',
    status: 'pending',
    evidenceFiles: ['coursera-cert.pdf'],
    details: portalCertifications.value[1],
  },
  {
    id: 3,
    type: 'project',
    studentName: 'Muhammad Farhan',
    studentMatricNo: 'B032510577',
    title: 'Weather Dashboard',
    submittedAt: '2026-10-05',
    status: 'pending',
    evidenceFiles: [],
    details: portalProjects.value[2],
  },
])

// ========================
// Composable
// ========================

export function usePortalData() {
  const studentStats: StudentDashboardStats = {
    totalSkills: portalSkills.value.length,
    verifiedSkills: portalSkills.value.filter((s) => s.status === 'verified').length,
    totalProjects: portalProjects.value.length,
    verifiedProjects: portalProjects.value.filter((p) => p.status === 'verified').length,
    totalCertifications: portalCertifications.value.length,
    verifiedCertifications: portalCertifications.value.filter((c) => c.status === 'verified').length,
    assessmentsCompleted: portalAttempts.value.length,
    averageAssessmentScore: portalAttempts.value.length
      ? Math.round(portalAttempts.value.reduce((sum, a) => sum + a.percentage, 0) / portalAttempts.value.length)
      : 0,
  }

  const adminStats: AdminDashboardStats = {
    pendingReviews: portalReviewItems.value.filter((r) => r.status === 'pending').length,
    totalStudents: 24,
    totalAssessments: portalAssessments.value.length,
    reviewsThisWeek: 5,
  }

  function getAssessmentQuestions(assessmentId: number): AssessmentQuestion[] {
    return portalAssessmentQuestions[assessmentId] || []
  }

  function addAttempt(attempt: AssessmentAttempt) {
    portalAttempts.value.push(attempt)
  }

  function updateReviewStatus(id: number, status: 'verified' | 'rejected', remarks?: string) {
    const item = portalReviewItems.value.find((r) => r.id === id)
    if (item) {
      item.status = status
      if (remarks) {
        (item.details as any).adminRemarks = remarks
      }
    }
  }

  function addSkill(skill: Omit<Skill, 'id' | 'createdAt' | 'updatedAt'>) {
    const newSkill: Skill = {
      ...skill,
      id: portalSkills.value.length + 1,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
    portalSkills.value.push(newSkill)
    return newSkill
  }

  function deleteSkill(id: number) {
    const index = portalSkills.value.findIndex((s) => s.id === id)
    if (index !== -1) portalSkills.value.splice(index, 1)
  }

  function addProject(project: Omit<Project, 'id' | 'createdAt' | 'updatedAt' | 'status'>) {
    const newProject: Project = {
      ...project,
      id: portalProjects.value.length + 1,
      status: 'pending',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
    portalProjects.value.push(newProject)
    return newProject
  }

  function deleteProject(id: number) {
    const index = portalProjects.value.findIndex((p) => p.id === id)
    if (index !== -1) portalProjects.value.splice(index, 1)
  }

  function addCertification(cert: Omit<Certification, 'id' | 'createdAt' | 'updatedAt' | 'status'>) {
    const newCert: Certification = {
      ...cert,
      id: portalCertifications.value.length + 1,
      status: 'pending',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
    portalCertifications.value.push(newCert)
    return newCert
  }

  function deleteCertification(id: number) {
    const index = portalCertifications.value.findIndex((c) => c.id === id)
    if (index !== -1) portalCertifications.value.splice(index, 1)
  }

  function addAssessment(assessment: Omit<Assessment, 'id' | 'questionCount'>) {
    const newAssessment: Assessment = {
      ...assessment,
      id: Math.max(0, ...portalAssessments.value.map((item) => item.id)) + 1,
      questionCount: assessment.questions?.length || 0,
    }
    portalAssessments.value.push(newAssessment)
    return newAssessment
  }

  function deleteAssessment(id: number) {
    const index = portalAssessments.value.findIndex((item) => item.id === id)
    if (index !== -1) portalAssessments.value.splice(index, 1)
  }

  return {
    skills: portalSkills,
    projects: portalProjects,
    certifications: portalCertifications,
    assessments: portalAssessments,
    attempts: portalAttempts,
    activity: portalActivity,
    reviewItems: portalReviewItems,
    studentStats,
    adminStats,
    getAssessmentQuestions,
    addAttempt,
    updateReviewStatus,
    addSkill,
    deleteSkill,
    addProject,
    deleteProject,
    addCertification,
    deleteCertification,
    addAssessment,
    deleteAssessment,
  }
}

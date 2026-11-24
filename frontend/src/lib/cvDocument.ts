import type { CvContent } from '@/lib/api'

// Mirrors backend/app/Support/CvDocument.php so the preview matches the PDF and Word exports.
export interface CvEntry { title: string; date: string; subtitle: string; bullets: string[] }
export interface CvSection { title: string; paragraph: string; entries: CvEntry[]; items: string[] }

function lines(text: string): string[] {
  return (text ?? '').split(/\r?\n/).map((line) => line.replace(/^[\s•●▪*\-–]+/u, '').trim()).filter(Boolean)
}

export function cvBullets(text: string): string[] {
  const rows = lines(text)
  if (rows.length === 1) {
    const sentences = rows[0]!.split(/(?<=[.!?])\s+(?=[A-Z])/u).map((part) => part.trim()).filter(Boolean)
    if (sentences.length > 1) return sentences
  }
  return rows
}

const entry = (title: string, date: string, subtitle: string, description: string): CvEntry => ({
  title: (title ?? '').trim(), date: (date ?? '').trim(), subtitle: (subtitle ?? '').trim(), bullets: cvBullets(description ?? ''),
})

function percent(value: number) {
  return `${Number((Number(value) || 0).toFixed(1))}%`
}

export function buildCvDocument(content: CvContent) {
  const p = content.personal
  const sections: CvSection[] = [
    { title: 'Professional Summary', paragraph: (content.summary ?? '').trim(), entries: [], items: [] },
    { title: 'Skills', paragraph: content.skills.filter(Boolean).join(', '), entries: [], items: [] },
    { title: 'Work Experience', paragraph: '', items: [], entries: content.experience.map((r) => entry(r.role, r.period, r.company, r.description)) },
    { title: 'Projects', paragraph: '', items: [], entries: content.projects.map((r) => entry(r.title, '', r.technologies.length ? 'Technologies: ' + r.technologies.join(', ') : '', r.description)) },
    { title: 'Education', paragraph: '', items: [], entries: content.education.map((r) => entry(r.qualification, r.period, r.institution, r.details ?? '')) },
    { title: 'Certifications', paragraph: '', items: [], entries: content.certifications.map((r) => entry(r.title, r.issueDate, r.issuer, '')) },
    { title: 'Verified Assessments', paragraph: '', entries: [], items: content.assessments.map((r) => `${r.title} — ${percent(r.percentage)}${r.completedAt ? ` (${r.completedAt})` : ''}`) },
    { title: 'Additional Information', paragraph: '', entries: [], items: lines(content.additional) },
  ]
  return {
    name: (p.name ?? '').trim(),
    headline: (p.headline ?? '').trim(),
    contact: [p.email, p.phone, p.location, p.website].map((v) => (v ?? '').trim()).filter(Boolean).join(' | '),
    sections: sections.filter((s) => s.paragraph || s.entries.length || s.items.length),
  }
}

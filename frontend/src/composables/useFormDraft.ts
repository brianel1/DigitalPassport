import { watch, type Ref, unref } from 'vue'
import { useAuth } from './useAuth'
const memory = new Map<string, unknown>()
function cloneDraft(value: unknown): any {
  if (value instanceof File) return value
  if (Array.isArray(value)) return value.map(cloneDraft)
  if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).filter(([key])=>!/password|token/i.test(key)).map(([key,item])=>[key,cloneDraft(item)]))
  return value
}
export function useFormDraft<T extends object>(name: string, form: T | Ref<T>, delayed = false) {
  const { user } = useAuth()
  const key = `skills-draft:${user.value?.id}:${name}`
  let ready = false
  function restore() {
    try {
      const saved = memory.get(key) ?? JSON.parse(sessionStorage.getItem(key) ?? 'null')
      if (saved) Object.assign(unref(form), structuredClone(saved))
    } catch { /* Storage may be disabled. Keep the current form usable. */ }
    ready = true
  }
  watch(() => unref(form), value => {
    if (!ready) return
    try {
      // Retain File objects between routes; never serialise file contents or passwords.
      const copy = cloneDraft(value)
      memory.set(key, copy)
      sessionStorage.setItem(key, JSON.stringify(copy, (k,v) => /password|token/i.test(k) ? undefined : v instanceof File ? null : v))
    } catch { /* In-memory form still works when storage is full. */ }
  }, { deep: true, flush: 'sync' })
  function clear() { memory.delete(key); try { sessionStorage.removeItem(key) } catch { /* Storage unavailable. */ } }
  if (!delayed) restore()
  return { restore, clear }
}
export function clearFormDrafts() {
  memory.clear()
  try { for (const key of Object.keys(sessionStorage)) if (key.startsWith('skills-draft:')) sessionStorage.removeItem(key) } catch { /* Storage unavailable. */ }
}

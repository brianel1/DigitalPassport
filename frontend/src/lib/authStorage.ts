import type { AuthState } from '@/types'

const key = 'dp_auth'

export function readAuth(): AuthState | null {
  try {
    // Old persistent logins must not silently become a new browser session.
    localStorage.removeItem(key)
    const saved = sessionStorage.getItem(key)
    if (!saved) return null
    const auth = JSON.parse(saved) as AuthState
    if (!auth.user || typeof auth.token !== 'string' || !auth.token) {
      clearAuth()
      return null
    }
    return auth
  } catch { clearAuth(); return null }
}

export function writeAuth(auth: AuthState) {
  localStorage.removeItem(key)
  sessionStorage.setItem(key, JSON.stringify(auth))
}

export function clearAuth() {
  localStorage.removeItem(key)
  sessionStorage.removeItem(key)
}

export function expireAuth(token: string | null) {
  // A late response from a previous login must not clear a newer session.
  if (token && readAuth()?.token === token) {
    clearAuth()
    window.dispatchEvent(new Event('auth:expired'))
  }
}

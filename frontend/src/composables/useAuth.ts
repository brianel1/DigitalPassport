import { computed, ref } from 'vue'
import type { AuthState } from '@/types'
import { getCurrentUser, loginUser, logoutUser, registerUser } from '@/lib/api'
import { clearAuth, readAuth, writeAuth } from '@/lib/authStorage'

const emptyState: AuthState = {
  user: null,
  token: null,
  isAuthenticated: false,
}

const state = ref<AuthState>({ ...emptyState })

function save(auth: AuthState) {
  state.value = auth
  writeAuth(auth)
}

function clear() {
  state.value = { ...emptyState }
  clearAuth()
}

window.addEventListener('auth:expired', clear)
let validating: Promise<boolean> | null = null

export function useAuth() {
  const user = computed(() => state.value.user)
  const isAuthenticated = computed(() => state.value.isAuthenticated)
  const isAdmin = computed(() => state.value.user?.role === 'admin')
  const isStudent = computed(() => state.value.user?.role === 'student')

  async function login(email: string, password: string): Promise<void> {
    const response = await loginUser(email, password)
    save({ user: response.user, token: response.token, isAuthenticated: true })
  }

  async function register(data: {
    name: string
    email: string
    password: string
    programme: string
    matricNo: string
  }): Promise<void> {
    const response = await registerUser(data)
    save({ user: response.user, token: response.token, isAuthenticated: true })
  }

  async function logout(): Promise<void> {
    try {
      if (state.value.token) await logoutUser()
    } catch {
      // Local sign-out still succeeds if the server has been stopped.
    } finally {
      clear()
    }
  }

  function restore() {
    const saved = readAuth()
    // Cached identity is not proof of authentication. The route guard checks /api/user.
    state.value = saved ? { ...saved, isAuthenticated: false } : { ...emptyState }
  }

  async function refreshUser(): Promise<boolean> {
    if (validating) return validating
    const token = state.value.token
    if (!token) return false
    validating = (async () => {
      try {
        const currentUser = await getCurrentUser()
        if (state.value.token !== token) return false
        save({ user: currentUser, token, isAuthenticated: true })
        return true
      } catch {
        if (state.value.token === token) clear()
        return false
      } finally { validating = null }
    })()
    return validating
  }

  return { user, isAuthenticated, isAdmin, isStudent, login, register, logout, restore, refreshUser }
}

import { computed, ref } from 'vue'
import type { AuthState } from '@/types'
import { getCurrentUser, loginUser, logoutUser, registerUser } from '@/lib/api'

const emptyState: AuthState = {
  user: null,
  token: null,
  isAuthenticated: false,
}

const state = ref<AuthState>({ ...emptyState })

function save(auth: AuthState) {
  state.value = auth
  localStorage.setItem('dp_auth', JSON.stringify(auth))
}

function clear() {
  state.value = { ...emptyState }
  localStorage.removeItem('dp_auth')
}

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
    } finally {
      clear()
    }
  }

  function restore() {
    const saved = localStorage.getItem('dp_auth')
    if (!saved) return

    try {
      const parsed = JSON.parse(saved) as AuthState
      if (!parsed.user || !parsed.token) {
        clear()
        return
      }
      state.value = { ...parsed, isAuthenticated: true }
    } catch {
      clear()
    }
  }

  async function refreshUser(): Promise<void> {
    if (!state.value.token) return
    try {
      const currentUser = await getCurrentUser()
      save({ ...state.value, user: currentUser, isAuthenticated: true })
    } catch {
      clear()
    }
  }

  return { user, isAuthenticated, isAdmin, isStudent, login, register, logout, restore, refreshUser }
}

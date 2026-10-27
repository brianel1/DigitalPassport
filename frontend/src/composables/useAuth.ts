import { ref, computed } from 'vue'
import type { User, AuthState } from '@/types'

const state = ref<AuthState>({
  user: null,
  token: null,
  isAuthenticated: false,
})

const accountDirectory: (User & { password: string })[] = [
  {
    id: 1,
    name: 'Elber Anselmus',
    email: 'elber@student.utem.edu.my',
    role: 'student',
    programme: 'BITS',
    matricNo: 'B032510581',
    createdAt: '2026-10-01',
    password: 'password123',
  },
  {
    id: 2,
    name: 'Muhammad Farhan',
    email: 'farhan@student.utem.edu.my',
    role: 'student',
    programme: 'BITC',
    matricNo: 'B032510577',
    createdAt: '2026-10-01',
    password: 'password123',
  },
  {
    id: 100,
    name: 'Admin DigitalPassport',
    email: 'admin@utem.edu.my',
    role: 'admin',
    createdAt: '2026-01-01',
    password: 'admin123',
  },
]

export function useAuth() {
  const user = computed(() => state.value.user)
  const isAuthenticated = computed(() => state.value.isAuthenticated)
  const isAdmin = computed(() => state.value.user?.role === 'admin')
  const isStudent = computed(() => state.value.user?.role === 'student')

  function login(email: string, password: string): boolean {
    const found = accountDirectory.find(
      (u) => u.email === email && u.password === password,
    )

    if (!found) return false

    const { password: _, ...userData } = found
    state.value = {
      user: userData,
      token: 'session-token-' + found.id,
      isAuthenticated: true,
    }

    localStorage.setItem('dp_auth', JSON.stringify(state.value))
    return true
  }

  function register(data: {
    name: string
    email: string
    password: string
    programme: string
    matricNo: string
  }): boolean {
    const exists = accountDirectory.find((u) => u.email === data.email)
    if (exists) return false

    const newUser: User = {
      id: accountDirectory.length + 1,
      name: data.name,
      email: data.email,
      role: 'student',
      programme: data.programme,
      matricNo: data.matricNo,
      createdAt: new Date().toISOString(),
    }

    accountDirectory.push({ ...newUser, password: data.password })

    state.value = {
      user: newUser,
      token: 'session-token-' + newUser.id,
      isAuthenticated: true,
    }

    localStorage.setItem('dp_auth', JSON.stringify(state.value))
    return true
  }

  function logout() {
    state.value = { user: null, token: null, isAuthenticated: false }
    localStorage.removeItem('dp_auth')
  }

  function restore() {
    const saved = localStorage.getItem('dp_auth')
    if (saved) {
      try {
        state.value = JSON.parse(saved)
      } catch {
        localStorage.removeItem('dp_auth')
      }
    }
  }

  return { user, isAuthenticated, isAdmin, isStudent, login, register, logout, restore }
}

import { ref } from 'vue'

export type Theme = 'dark' | 'light'

const THEME_STORAGE_KEY = 'digitalpassport-theme'
const theme = ref<Theme>('dark')
let restored = false

function applyTheme(nextTheme: Theme) {
  theme.value = nextTheme

  if (typeof document === 'undefined') return

  document.documentElement.classList.toggle('dark', nextTheme === 'dark')
  document.documentElement.style.colorScheme = nextTheme
}

function restore() {
  if (restored) return

  const savedTheme = typeof window !== 'undefined'
    ? window.localStorage.getItem(THEME_STORAGE_KEY)
    : null

  applyTheme(savedTheme === 'light' ? 'light' : 'dark')
  restored = true
}

function setTheme(nextTheme: Theme) {
  applyTheme(nextTheme)

  if (typeof window !== 'undefined') {
    window.localStorage.setItem(THEME_STORAGE_KEY, nextTheme)
  }
}

function toggleTheme() {
  setTheme(theme.value === 'dark' ? 'light' : 'dark')
}

export function useTheme() {
  restore()

  return {
    theme,
    setTheme,
    toggleTheme,
    restore,
  }
}

import { ref } from 'vue'

const isOpen = ref(false)
const motionEnabled = ref(true)
const request = ref<{ cvId?: number; mode: 'coach' | 'interview'; prompt: string } | null>(null)
export function useAssistant() {
  function openAssistant(options: { cvId?: number; mode?: 'coach' | 'interview'; prompt?: string } = {}) {
    request.value = { cvId: options.cvId, mode: options.mode ?? 'coach', prompt: options.prompt ?? '' }
    isOpen.value = true
  }
  return { isOpen, request, openAssistant, motionEnabled }
}

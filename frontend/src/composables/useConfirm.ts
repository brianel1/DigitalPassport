import { shallowRef } from 'vue'
type Options = { title: string; description: string; confirmLabel?: string; destructive?: boolean }
const pending = shallowRef<(Options & { resolve: (value: boolean) => void }) | null>(null)
export function useConfirm() {
  function confirmAction(options: Options): Promise<boolean> {
    pending.value?.resolve(false)
    return new Promise(resolve => { pending.value = { ...options, resolve } })
  }
  function finish(value: boolean) { const current = pending.value; pending.value = null; current?.resolve(value) }
  return { pending, confirmAction, finish }
}

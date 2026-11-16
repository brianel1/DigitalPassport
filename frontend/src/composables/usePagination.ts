import { computed, ref, toValue, watch, type MaybeRefOrGetter } from 'vue'
export function usePagination<T>(source: MaybeRefOrGetter<T[]>, pageSize = 8) {
  const page = ref(1)
  const total = computed(() => toValue(source).length)
  const pages = computed(() => Math.max(1, Math.ceil(total.value / pageSize)))
  const items = computed(() => toValue(source).slice((page.value - 1) * pageSize, page.value * pageSize))
  watch(() => toValue(source), () => { page.value = Math.min(page.value, pages.value) }, { deep: true })
  return { page, items, total, pageSize }
}

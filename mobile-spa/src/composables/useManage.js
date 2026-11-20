import { onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import { auth } from '../services/auth.js'

export function useManage() {
  const router = useRouter()
  const toast = ref('')
  let timer = null

  function showToast(message) {
    toast.value = message
    clearTimeout(timer)
    timer = setTimeout(() => { toast.value = '' }, 3200)
  }

  async function expire() {
    auth.expire()
    await router.replace({ name: 'login', query: { expired: '1' } })
  }

  async function guard(fn, onError) {
    try {
      return await fn()
    } catch (reason) {
      if (reason?.status === 401) { await expire(); return null }
      onError(reason?.message || 'İşlem tamamlanamadı.')
      return null
    }
  }

  function formatDate(value) {
    const v = String(value || '').slice(0, 10)
    return /^\d{4}-\d{2}-\d{2}$/.test(v) ? `${v.slice(8, 10)}.${v.slice(5, 7)}.${v.slice(0, 4)}` : '—'
  }

  function initialsOf(name) {
    const parts = String(name || '?').trim().split(/\s+/)
    return ((parts[0]?.[0] || '') + (parts.length > 1 ? parts.at(-1)[0] : '')).toLocaleUpperCase('tr-TR')
  }

  onBeforeUnmount(() => clearTimeout(timer))
  return { toast, showToast, expire, guard, formatDate, initialsOf }
}

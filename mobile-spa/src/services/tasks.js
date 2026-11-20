import { auth } from './auth.js'

async function request(action, { params = {}, body = null } = {}) {
  const query = new URLSearchParams({ action, ...params }).toString()
  const options = { credentials: 'same-origin', headers: { Accept: 'application/json' } }
  if (body) {
    options.method = 'POST'
    options.headers['Content-Type'] = 'application/json'
    options.headers['X-CSRF-Token'] = auth.state.csrfToken
    options.body = JSON.stringify(body)
  }
  const response = await fetch(`/api/tasks.php?${query}`, options)
  const payload = await response.json().catch(() => ({ success: false, message: 'Sunucudan geçersiz yanıt alındı.' }))
  if (!response.ok || payload.success === false) {
    const error = new Error(payload.message || 'İşlem tamamlanamadı.')
    error.status = response.status
    throw error
  }
  return payload
}

export const PRIORITY_META = {
  high: { label: 'Yüksek', tone: 'red' },
  medium: { label: 'Orta', tone: 'amber' },
  low: { label: 'Düşük', tone: 'emerald' },
}

export const listTasks = (scope = 'all') => request('list', { params: { scope } })
export const saveTask = (task) => request(task.id ? 'update' : 'create', { body: task })
export const moveTask = (id, board_id) => request('move', { body: { id, board_id } })
export const deleteTask = (id) => request('delete', { body: { id } })

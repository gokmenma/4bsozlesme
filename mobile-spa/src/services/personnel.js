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
  const response = await fetch(`/api/personnel.php?${query}`, options)
  const payload = await response.json().catch(() => ({ success: false, message: 'Sunucudan geçersiz yanıt alındı.' }))
  if (!response.ok || payload.success === false) {
    const error = new Error(payload.message || 'İşlem tamamlanamadı.')
    error.status = response.status
    throw error
  }
  return payload
}

export const STATUS_META = {
  aktif: { label: 'Aktif', tone: 'emerald' },
  dilekce_alindi: { label: 'Dilekçe Alındı', tone: 'blue' },
  kadroya_gecmeyecek: { label: 'Kadroya Geçmeyecek', tone: 'amber' },
  kadroya_gecti: { label: 'Kadroya Geçti', tone: 'gold' },
  pasif: { label: 'Pasif', tone: 'gray' },
}

export const listPersonnel = ({ q = '', status = '', page = 1 } = {}) => request('list', { params: { q, status, page } })
export const getPersonnel = (id) => request('detail', { params: { id } })
export const getWageOptions = () => request('options')
export const savePersonnel = (person) => request(person.id ? 'update' : 'create', { body: person })
export const deletePersonnel = (id) => request('delete', { body: { id } })
export const addLeave = (personel_id, leave) => request('leave_add', { body: { personel_id, ...leave } })
export const deleteLeave = (personel_id, id) => request('leave_delete', { body: { personel_id, id } })

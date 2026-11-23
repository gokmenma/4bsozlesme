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
  const response = await fetch(`/api/evrak.php?${query}`, options)
  const payload = await response.json().catch(() => ({ success: false, message: 'Sunucudan geçersiz yanıt alındı.' }))
  if (!response.ok || payload.success === false) {
    const error = new Error(payload.message || 'İşlem tamamlanamadı.')
    error.status = response.status
    throw error
  }
  return payload
}

export const TIP_LABEL = { bilgi: 'Bilgi Formu', kvkk: 'Onay Metni', beyan: 'Beyan', belge: 'Belge Yükleme' }

export const getEvrakOptions = (personelId) => request('options', { params: { personel_id: personelId } })
export const sendEvrakPackage = (payload) => request('send', { body: payload })

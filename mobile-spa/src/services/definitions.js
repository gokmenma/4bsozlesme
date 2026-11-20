import { auth } from './auth.js'

async function request(action, body = null) {
  const options = { credentials: 'same-origin', headers: { Accept: 'application/json' } }
  if (body) {
    options.method = 'POST'
    options.headers['Content-Type'] = 'application/json'
    options.headers['X-CSRF-Token'] = auth.state.csrfToken
    options.body = JSON.stringify(body)
  }
  const response = await fetch(`/api/definitions.php?action=${action}`, options)
  const payload = await response.json().catch(() => ({ success: false, message: 'Sunucudan geçersiz yanıt alındı.' }))
  if (!response.ok || payload.success === false) {
    const error = new Error(payload.message || 'İşlem tamamlanamadı.')
    error.status = response.status
    throw error
  }
  return payload
}

export const getDefinitions = () => request('get')
export const saveDefinitions = (values) => request('save', values)

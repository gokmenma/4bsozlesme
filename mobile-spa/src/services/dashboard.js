import { auth } from './auth.js'

export async function getDashboard() {
  const response = await fetch('/api/dashboard.php', {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  })
  const payload = await response.json().catch(() => ({ success: false, message: 'Sunucudan geçersiz yanıt alındı.' }))
  if (!response.ok || payload.success === false) {
    const error = new Error(payload.message || 'Özet verileri alınamadı.')
    error.status = response.status
    throw error
  }
  return payload
}

export async function logKadroMessage(personelId) {
  const response = await fetch('/api/kadro-mesaj.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': auth.state.csrfToken },
    body: JSON.stringify({ personel_id: personelId }),
  })
  return response.ok
}

export function whatsappUrl(phone, text) {
  let digits = String(phone || '').replace(/\D/g, '')
  if (digits.length === 10) digits = `90${digits}`
  else if (digits.length === 11 && digits.startsWith('0')) digits = `90${digits.slice(1)}`
  if (digits.length < 11) return ''
  return `https://api.whatsapp.com/send?phone=${digits}&text=${encodeURIComponent(text)}`
}

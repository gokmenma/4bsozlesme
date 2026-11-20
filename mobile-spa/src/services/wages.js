import { auth } from './auth.js'

async function request(action, { params = {}, body = null } = {}) {
  const cleanParams = Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== '')
  )
  const query = new URLSearchParams({ action, ...cleanParams }).toString()
  const options = { credentials: 'same-origin', headers: { Accept: 'application/json' } }
  if (body) {
    options.method = 'POST'
    options.headers['Content-Type'] = 'application/json'
    options.headers['X-CSRF-Token'] = auth.state.csrfToken
    options.body = JSON.stringify(body)
  }
  const response = await fetch(`/api/wages.php?${query}`, options)
  const payload = await response.json().catch(() => ({ success: false, message: 'Sunucudan geçersiz yanıt alındı.' }))
  if (!response.ok || payload.success === false) {
    const error = new Error(payload.message || 'İşlem tamamlanamadı.')
    error.status = response.status
    throw error
  }
  return payload
}

export const SORT_OPTIONS = {
  unvan_asc: 'Unvan (A → Z)',
  unvan_desc: 'Unvan (Z → A)',
  ucret_desc: 'Brüt Ücret (Yüksekten Düşüğe)',
  ucret_asc: 'Brüt Ücret (Düşükten Yükseğe)',
  ogrenim_asc: 'Öğrenim Durumu (A → Z)',
}

const currencyFmt = new Intl.NumberFormat('tr-TR', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})

const compactCurrencyFmt = new Intl.NumberFormat('tr-TR', {
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
})

export function formatCurrency(value) {
  if (value === null || value === undefined || value === '') return '—'
  const num = Number(value)
  return Number.isFinite(num) ? `₺${currencyFmt.format(num)}` : '—'
}

export function formatCompactCurrency(value) {
  if (value === null || value === undefined || value === '') return '—'
  const num = Number(value)
  return Number.isFinite(num) ? `₺${compactCurrencyFmt.format(num)}` : '—'
}

export function formatInputCurrency(value) {
  if (value === null || value === undefined || value === '') return ''
  const num = Number(value)
  return Number.isFinite(num) ? currencyFmt.format(num) : ''
}

export function parseTurkishAmount(raw) {
  if (typeof raw === 'number') {
    return Number.isFinite(raw) ? Math.round(raw * 100) / 100 : null
  }
  if (typeof raw !== 'string') return null
  let clean = raw.replace(/₺|TL/gi, '').replace(/\s+/g, '')
  if (!clean || /[^0-9.,]/.test(clean)) return null

  const hasDot = clean.includes('.')
  const hasComma = clean.includes(',')
  if (hasDot && hasComma) {
    if (clean.lastIndexOf(',') > clean.lastIndexOf('.')) {
      clean = clean.replace(/\./g, '').replace(',', '.')
    } else {
      clean = clean.replace(/,/g, '')
    }
  } else if (hasComma) {
    clean = (clean.match(/,/g) || []).length > 1 ? clean.replace(/,/g, '') : clean.replace(',', '.')
  } else if (hasDot) {
    if ((clean.match(/\./g) || []).length > 1 || /^\d{1,3}\.\d{3}$/.test(clean)) {
      clean = clean.replace(/\./g, '')
    }
  }

  const num = Number(clean)
  return Number.isFinite(num) ? Math.round(num * 100) / 100 : null
}

export const listWages = ({
  donem = '',
  q = '',
  ogrenim = '',
  kidem = '',
  min_ucret = '',
  max_ucret = '',
  sort = 'unvan_asc',
  page = 1,
} = {}) =>
  request('list', {
    params: { donem, q, ogrenim, kidem, min_ucret, max_ucret, sort, page },
  })

export const getWage = (id) => request('detail', { params: { id } })
export const saveWage = (wage) => request(wage.id ? 'update' : 'create', { body: wage })
export const deleteWage = (id) => request('delete', { body: { id } })
export const copyWagePeriod = ({ from_donem, to_donem, raise_percent }) =>
  request('copy_period', { body: { from_donem, to_donem, raise_percent } })
export const deleteWagePeriod = (donem) => request('delete_period', { body: { donem } })
export const setDefaultWagePeriod = (donem) => request('set_default_period', { body: { donem } })

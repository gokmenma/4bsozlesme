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

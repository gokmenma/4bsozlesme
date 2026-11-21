/**
 * Mobil cihazlarda dokunsal geri bildirim (Web Vibration API) yardımcısı.
 * Desteklemeyen tarayıcılarda (ör. iOS Safari) sessizce no-op olarak çalışır.
 */
export function useHaptics() {
  const isSupported = typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function'

  function trigger(pattern = 10) {
    if (!isSupported) return false
    try {
      return navigator.vibrate(pattern)
    } catch {
      return false
    }
  }

  return {
    isSupported,
    tap: () => trigger(10),
    medium: () => trigger(18),
  }
}

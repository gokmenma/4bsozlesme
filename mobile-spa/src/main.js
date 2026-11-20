import { createApp } from 'vue'
import App from './App.vue'
import router from './router.js'
import './design-system.css'
import './styles.css'
import './manage.css'

const nativeFetch = window.fetch.bind(window)
window.fetch = (input, init = {}) => {
  const requestUrl = typeof input === 'string' || input instanceof URL ? input : input.url
  const url = new URL(requestUrl, window.location.href)
  if (url.origin !== window.location.origin || !url.pathname.startsWith('/api/')) {
    return nativeFetch(input, init)
  }

  const inheritedHeaders = input instanceof Request ? input.headers : undefined
  const headers = new Headers(init.headers || inheritedHeaders)
  headers.set('X-Client-Source', 'mobile-spa')
  return nativeFetch(input, { ...init, headers })
}

if ('serviceWorker' in navigator && import.meta.env.PROD) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {})
  })
}

const splashStart = performance.now()
createApp(App).use(router).mount('#app')

router.isReady().finally(() => {
  const splashEl = document.getElementById('app-splash')
  if (!splashEl) return
  const remaining = Math.max(0, 850 - (performance.now() - splashStart))
  setTimeout(() => {
    splashEl.classList.add('is-hiding')
    setTimeout(() => splashEl.remove(), 520)
  }, remaining)
})

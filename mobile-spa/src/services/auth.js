import { reactive, readonly } from 'vue'

const state = reactive({
  ready: false,
  authenticated: false,
  csrfToken: '',
  permissions: {},
  user: null,
  tenant: null,
  tenants: [],
})

function applySession(payload) {
  state.authenticated = Boolean(payload.authenticated)
  state.csrfToken = payload.csrf_token || ''
  state.permissions = payload.permissions || {}
  state.user = payload.user || null
  state.tenant = payload.tenant || null
  state.tenants = payload.tenants || []
}

async function request(action = 'session', options = {}) {
  const response = await fetch(`/api/auth.php?action=${encodeURIComponent(action)}`, {
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      ...(options.headers || {}),
    },
    ...options,
  })

  const payload = await response.json().catch(() => ({
    success: false,
    message: 'Sunucudan geçersiz yanıt alındı.',
  }))

  if (payload.csrf_token) {
    state.csrfToken = payload.csrf_token
  }

  if (!response.ok || payload.success === false) {
    const error = new Error(payload.message || 'İşlem tamamlanamadı.')
    error.status = response.status
    throw error
  }

  return payload
}

export const auth = {
  state: readonly(state),

  async bootstrap(force = false) {
    if (state.ready && !force) return state
    try {
      applySession(await request('session'))
    } finally {
      state.ready = true
    }
    return state
  },

  expire() {
    state.authenticated = false
    state.user = null
    state.permissions = {}
    state.tenant = null
    state.tenants = []
  },

  async login({ username, password, remember }) {
    if (!state.csrfToken) {
      await this.bootstrap(true)
    }
    const payload = await request('login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': state.csrfToken,
      },
      body: JSON.stringify({ username, password, remember }),
    })
    applySession(payload)
    return state
  },

  can(route) {
    if (state.user?.role === 'superadmin') return true
    return state.permissions[route] === true
  },

  async logout() {
    await request('logout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': state.csrfToken,
      },
      body: '{}',
    })
    state.authenticated = false
    state.user = null
    state.permissions = {}
    state.tenant = null
    state.tenants = []
    state.ready = false
    await this.bootstrap(true)
  },
}

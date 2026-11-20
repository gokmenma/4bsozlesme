import { auth } from './auth.js'

function createApi(endpoint) {
  return async function request(action, { params = {}, body = null } = {}) {
    const query = new URLSearchParams({ action, ...params }).toString()
    const options = { credentials: 'same-origin', headers: { Accept: 'application/json' } }
    if (body) {
      options.method = 'POST'
      options.headers['Content-Type'] = 'application/json'
      options.headers['X-CSRF-Token'] = auth.state.csrfToken
      options.body = JSON.stringify(body)
    }
    const response = await fetch(`/api/${endpoint}.php?${query}`, options)
    const payload = await response.json().catch(() => ({ success: false, message: 'Sunucudan geçersiz yanıt alındı.' }))
    if (!response.ok || payload.success === false) {
      const error = new Error(payload.message || 'İşlem tamamlanamadı.')
      error.status = response.status
      throw error
    }
    return payload
  }
}

const users = createApi('users')
const roles = createApi('roles')
const tenants = createApi('tenants')
const subscription = createApi('subscription')
const settings = createApi('settings')
const feedbacks = createApi('feedbacks')
const profile = createApi('profile')

export const profileApi = {
  changePassword: (body) => profile('password', { body }),
}

export const usersApi = {
  list: (scope = 'mine', q = '') => users('list', { params: { scope, q } }),
  save: (user) => users(user.id ? 'update' : 'create', { body: user }),
  setActive: (id, active) => users('set_active', { body: { id, active } }),
  remove: (id) => users('delete', { body: { id } }),
}

export const rolesApi = {
  list: () => roles('list'),
  permissions: (role_id) => roles('permissions', { params: { role_id } }),
  save: (role) => roles(role.id ? 'update' : 'create', { body: role }),
  remove: (id) => roles('delete', { body: { id } }),
  savePermissions: (role_id, permissions) => roles('save_permissions', { body: { role_id, permissions } }),
}

export const tenantsApi = {
  list: () => tenants('list'),
  update: (tenant) => tenants('update', { body: tenant }),
  remove: (id) => tenants('delete', { body: { id } }),
}

export const subscriptionApi = {
  list: () => subscription('list'),
  decide: (action, id) => subscription(action, { body: { id } }),
  savePlan: (plan) => subscription(plan.id ? 'plan_update' : 'plan_create', { body: plan }),
  removePlan: (id) => subscription('plan_delete', { body: { id } }),
}

export const settingsApi = {
  get: () => settings('get'),
  save: (values) => settings('save', { body: values }),
  testMail: (test_email) => settings('test_mail', { body: { test_email } }),
}

export const feedbacksApi = {
  list: (status = 'all') => feedbacks('list', { params: { status } }),
  update: (item) => feedbacks('update', { body: item }),
  remove: (id) => feedbacks('delete', { body: { id } }),
}

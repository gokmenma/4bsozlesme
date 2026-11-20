<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { AtSign, Building2, KeyRound, LogOut, Mail, ShieldCheck, Users } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import { auth } from '../services/auth.js'
import { profileApi } from '../services/system.js'

const router = useRouter()
const busy = ref(false)

const user = computed(() => auth.state.user || {})
const tenant = computed(() => auth.state.tenant || {})
const initials = computed(() => {
  const parts = String(user.value.name || user.value.username || '?').trim().split(/\s+/)
  return ((parts[0]?.[0] || '') + (parts.length > 1 ? parts.at(-1)[0] : '')).toLocaleUpperCase('tr-TR')
})
const roleLabel = computed(() => ({ superadmin: 'Süper Yönetici', admin: 'Yönetici', user: 'Kullanıcı' }[user.value.role] || user.value.role || '-'))
const tenantCount = computed(() => auth.state.tenants?.length || 0)

const rows = computed(() => [
  { icon: AtSign, label: 'Kullanıcı adı', value: user.value.username },
  { icon: Mail, label: 'E-posta', value: user.value.email },
  { icon: ShieldCheck, label: 'Rol', value: roleLabel.value },
  { icon: Building2, label: 'Kurum', value: tenant.value.name },
  ...(tenantCount.value > 1 ? [{ icon: Users, label: 'Erişilebilir kurum', value: `${tenantCount.value} kurum` }] : []),
].filter((row) => row.value))

const pw = ref({ current_password: '', new_password: '', confirm_password: '' })
const pwBusy = ref(false)
const pwMessage = ref('')
const pwError = ref(false)

async function changePassword() {
  pwMessage.value = ''
  pwError.value = false
  pwBusy.value = true
  try {
    await profileApi.changePassword({ ...pw.value })
    pw.value = { current_password: '', new_password: '', confirm_password: '' }
    pwMessage.value = 'Şifreniz güncellendi.'
  } catch (error) {
    pwError.value = true
    pwMessage.value = error.message
  } finally {
    pwBusy.value = false
  }
}

async function handleLogout() {
  busy.value = true
  try {
    await auth.logout()
    await router.replace('/giris')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="page">
    <TopBar eyebrow="HESAP" title="Profil" :back="true" />
    <main class="profile">
      <section class="hero">
        <div class="hero__avatar" aria-hidden="true">{{ initials }}</div>
        <h2>{{ user.name || 'Kullanıcı' }}</h2>
        <p>{{ tenant.name }}</p>
        <span class="hero__role"><ShieldCheck :size="14" /> {{ roleLabel }}</span>
      </section>

      <section class="info" aria-label="Hesap bilgileri">
        <h3>Hesap bilgileri</h3>
        <ul>
          <li v-for="row in rows" :key="row.label">
            <span class="info__icon"><component :is="row.icon" :size="18" /></span>
            <span class="info__text">
              <small>{{ row.label }}</small>
              <strong>{{ row.value }}</strong>
            </span>
          </li>
        </ul>
      </section>

      <section class="info" aria-label="Şifre değiştir">
        <h3>Şifre değiştir</h3>
        <form class="pw" @submit.prevent="changePassword">
          <label><span>Mevcut şifre</span><input v-model="pw.current_password" type="password" autocomplete="current-password" required /></label>
          <label><span>Yeni şifre</span><input v-model="pw.new_password" type="password" autocomplete="new-password" minlength="8" required /></label>
          <label><span>Yeni şifre (tekrar)</span><input v-model="pw.confirm_password" type="password" autocomplete="new-password" minlength="8" required /></label>
          <p v-if="pwMessage" class="pw__msg" :class="{ 'is-error': pwError }" role="status">{{ pwMessage }}</p>
          <button type="submit" class="pw__submit" :disabled="pwBusy"><KeyRound :size="17" /> {{ pwBusy ? 'Kaydediliyor…' : 'Şifreyi güncelle' }}</button>
        </form>
      </section>

      <button type="button" class="logout" :disabled="busy" @click="handleLogout">
        <LogOut :size="17" /> {{ busy ? 'Çıkış yapılıyor…' : 'Çıkış yap' }}
      </button>
    </main>
  </div>
</template>

<style scoped>
.page { min-height: 100svh; }
.profile { display: grid; gap: 14px; padding: 14px 16px calc(112px + var(--ds-safe-bottom)); max-width: 520px; margin: 0 auto; }
.hero { display: grid; justify-items: center; gap: 4px; padding: 28px 20px 22px; background: var(--ds-surface); border-radius: var(--ds-radius-card); box-shadow: var(--ds-shadow-bento); text-align: center; }
.hero__avatar { display: grid; place-items: center; width: 84px; height: 84px; margin-bottom: 8px; border-radius: 50%; background: var(--ds-surface-dark); color: #fff; font-size: 30px; font-weight: 800; letter-spacing: .02em; box-shadow: 0 0 0 5px var(--ds-surface-subtle); }
.hero h2 { margin: 0; font-size: 22px; font-weight: 800; }
.hero p { margin: 0; color: var(--ds-muted); font-size: 14px; }
.hero__role { display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; padding: 6px 12px; border-radius: 999px; background: var(--ds-surface-subtle); color: var(--ds-ink); font-size: 12px; font-weight: 700; }
.info { padding: 18px 16px; background: var(--ds-surface); border-radius: var(--ds-radius-card); box-shadow: var(--ds-shadow-bento); }
.info h3 { margin: 0 0 12px 4px; color: var(--ds-muted); font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
.info ul { display: grid; gap: 8px; margin: 0; padding: 0; list-style: none; }
.info li { display: flex; align-items: center; gap: 12px; min-height: 56px; padding: 8px 12px; border-radius: var(--ds-radius-tile); background: var(--ds-surface-subtle); }
.info__icon { display: grid; place-items: center; flex-shrink: 0; width: 38px; height: 38px; border-radius: 12px; background: var(--ds-surface); color: var(--ds-ink); box-shadow: var(--ds-shadow-tile); }
.info__text { display: grid; gap: 2px; min-width: 0; }
.info__text small { color: var(--ds-muted); font-size: 11px; font-weight: 700; }
.info__text strong { font-size: 14px; font-weight: 700; overflow-wrap: anywhere; }
.pw { display: grid; gap: 12px; }
.pw label { display: grid; gap: 6px; }
.pw label span { color: var(--ds-muted); font-size: 11px; font-weight: 700; margin-left: 4px; }
.pw input { min-height: 48px; padding: 0 16px; border: 0; border-radius: var(--ds-radius-pill); background: var(--ds-surface-subtle); color: var(--ds-ink); font-size: 16px; outline: 0; }
.pw input:focus-visible { box-shadow: 0 0 0 2px var(--ds-ink); }
.pw__msg { margin: 0; padding: 10px 14px; border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); font-size: 13px; font-weight: 600; }
.pw__msg.is-error { background: var(--ds-danger-bg); color: var(--ds-danger-ink); }
.pw__submit { display: flex; align-items: center; justify-content: center; gap: 8px; height: 50px; border: 0; border-radius: 999px; background: var(--ds-primary); color: #fff; font-weight: 700; font-size: 14px; cursor: pointer; }
.pw__submit:disabled { opacity: .6; }
.logout { display: flex; align-items: center; justify-content: center; gap: 8px; height: 50px; border: 0; border-radius: 999px; background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-weight: 700; font-size: 14px; cursor: pointer; }
.logout:disabled { opacity: .6; }
</style>

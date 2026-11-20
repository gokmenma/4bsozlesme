<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { LogOut } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import { auth } from '../services/auth.js'

const router = useRouter()
const busy = ref(false)

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
      <div class="card">
        <img src="/pwa-icons/icon-192.png?v=2" alt="" class="card__logo" />
        <h2>{{ auth.state.user?.name || 'Kullanıcı' }}</h2>
        <p>{{ auth.state.tenant?.name }}</p>
        <dl>
          <div><dt>Kullanıcı</dt><dd>{{ auth.state.user?.username }}</dd></div>
          <div><dt>E-posta</dt><dd>{{ auth.state.user?.email }}</dd></div>
          <div><dt>Rol</dt><dd>{{ auth.state.user?.role }}</dd></div>
        </dl>
        <button type="button" class="logout" :disabled="busy" @click="handleLogout">
          <LogOut :size="17" /> {{ busy ? 'Çıkış yapılıyor…' : 'Çıkış yap' }}
        </button>
      </div>
    </main>
  </div>
</template>

<style scoped>
.page { min-height: 100svh; }
.profile { padding: 14px 16px calc(112px + var(--ds-safe-bottom)); max-width: 520px; margin: 0 auto; }
.card { display: grid; gap: 6px; padding: 24px 20px; background: var(--ds-surface); border-radius: var(--ds-radius-card); box-shadow: var(--ds-shadow-bento); }
.card__logo { width: 56px; height: 56px; border-radius: 16px; }
.card h2 { margin: 8px 0 0; font-size: 22px; }
.card p { margin: 0; color: var(--ds-muted); font-size: 14px; }
dl { display: grid; gap: 8px; margin: 14px 0; padding: 14px; border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); }
dl div { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; }
dt { color: var(--ds-muted); font-weight: 700; }
dd { margin: 0; text-align: right; overflow-wrap: anywhere; }
.logout { display: flex; align-items: center; justify-content: center; gap: 8px; height: 48px; border: 0; border-radius: 999px; background: var(--ds-primary); color: #fff; font-weight: 700; font-size: 14px; cursor: pointer; }
.logout:disabled { opacity: .6; }
</style>

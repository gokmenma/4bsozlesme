<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Bell, ChevronDown } from '@lucide/vue'
import { auth } from '../services/auth.js'

const props = defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  back: { type: Boolean, default: null },
  unread: { type: Number, default: 0 },
})

const router = useRouter()
const route = useRoute()
const showBack = computed(() => (props.back !== null ? props.back : route.path !== '/'))
const username = computed(() => auth.state.user?.name || auth.state.user?.username || 'Kullanıcı')
const initials = computed(() => {
  const parts = username.value.trim().split(/\s+/).filter(Boolean)
  const letters = parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : username.value.slice(0, 2)
  return letters.toLocaleUpperCase('tr-TR')
})
const heading = computed(() => props.title || `Merhaba, ${username.value.split(' ')[0]}`)

function goBack() {
  if (window.history.state?.back) router.back()
  else router.push('/')
}
</script>

<template>
  <header class="topbar">
    <div class="topbar__row">
      <div class="topbar__lead">
        <button v-if="showBack" type="button" class="circle-btn" aria-label="Geri dön" @click="goBack">
          <ArrowLeft :size="20" />
        </button>
        <div v-else class="tenant-chip" aria-label="Aktif kurum">
          <img src="/pwa-icons/icon-192.png?v=2" alt="" class="tenant-chip__logo" />
          <span class="tenant-chip__name">{{ auth.state.tenant?.name || 'Sözleşme 4B' }}</span>
          <ChevronDown :size="14" />
        </div>
        <div v-if="showBack" class="topbar__titles">
          <span v-if="eyebrow" class="topbar__eyebrow">{{ eyebrow }}</span>
          <h1 class="topbar__title">{{ heading }}</h1>
        </div>
      </div>

      <div class="topbar__actions">
        <button type="button" class="circle-btn topbar__bell" aria-label="Bildirimler">
          <Bell :size="19" />
          <span v-if="unread > 0" class="topbar__badge">{{ unread > 99 ? '99+' : unread }}</span>
        </button>
        <button type="button" class="circle-btn circle-btn--dark" aria-label="Profil" @click="router.push('/profil')">
          {{ initials }}
        </button>
      </div>
    </div>

    <div v-if="!showBack" class="topbar__page">
      <span v-if="eyebrow" class="topbar__eyebrow">{{ eyebrow }}</span>
      <h1 class="topbar__page-title">{{ heading }}</h1>
      <p v-if="subtitle" class="topbar__subtitle">{{ subtitle }}</p>
    </div>
  </header>
</template>

<style scoped>
.topbar { padding: calc(14px + var(--ds-safe-top)) 18px 6px; display: grid; gap: 18px; }
.topbar__row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.topbar__lead { display: flex; align-items: center; gap: 12px; min-width: 0; }
.topbar__actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.tenant-chip { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; max-width: 100%; padding: 6px 12px 6px 6px; border-radius: var(--ds-radius-pill); background: var(--ds-surface); box-shadow: var(--ds-shadow-pill); color: var(--ds-ink); }
.tenant-chip__logo { width: 32px; height: 32px; border-radius: 10px; object-fit: cover; flex-shrink: 0; }
.tenant-chip__name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; font-weight: 700; }
.tenant-chip svg { flex-shrink: 0; color: var(--ds-muted); }
.circle-btn { position: relative; display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface); color: var(--ds-ink); box-shadow: var(--ds-shadow-pill); cursor: pointer; font-size: 14px; font-weight: 800; transition: transform .15s ease; }
.circle-btn:active { transform: scale(.93); }
.circle-btn--dark { background: var(--ds-surface-dark); color: var(--ds-ink-light); }
.circle-btn:focus-visible { outline: 2px solid var(--ds-focus); outline-offset: 2px; }
.topbar__badge { position: absolute; top: -3px; right: -3px; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 999px; border: 2px solid var(--ds-canvas); background: var(--ds-danger); color: #fff; font: 800 10px/14px var(--ds-font-main); text-align: center; pointer-events: none; }
.topbar__titles { min-width: 0; display: grid; gap: 1px; }
.topbar__eyebrow { color: var(--ds-muted); font-size: 10px; font-weight: 800; letter-spacing: .12em; }
.topbar__title { margin: 0; font-size: 19px; line-height: 1.15; font-weight: 800; letter-spacing: -.01em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.topbar__page { display: grid; gap: 4px; padding: 2px 4px 0; }
.topbar__page-title { margin: 0; font-size: 30px; line-height: 1.05; font-weight: 800; letter-spacing: -.02em; }
.topbar__subtitle { margin: 2px 0 0; color: var(--ds-muted); font-size: 14px; }
</style>

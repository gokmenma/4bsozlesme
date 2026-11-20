<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'

const DISMISS_KEY = 'pwa-install-dismissed-at'
const DISMISS_MS = 7 * 24 * 60 * 60 * 1000

const deferred = ref(null)
const visible = ref(false)
const iosHint = ref(false)

function isStandalone() {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true
}

function recentlyDismissed() {
  try {
    const at = Number(localStorage.getItem(DISMISS_KEY) || 0)
    return at && Date.now() - at < DISMISS_MS
  } catch {
    return false
  }
}

function onBeforeInstall(event) {
  event.preventDefault()
  if (isStandalone() || recentlyDismissed()) return
  deferred.value = event
  iosHint.value = false
  visible.value = true
}

function onInstalled() {
  visible.value = false
  deferred.value = null
}

async function install() {
  if (!deferred.value) return
  deferred.value.prompt()
  try {
    await deferred.value.userChoice
  } catch {}
  deferred.value = null
  visible.value = false
}

function dismiss() {
  visible.value = false
  try {
    localStorage.setItem(DISMISS_KEY, String(Date.now()))
  } catch {}
}

onMounted(() => {
  window.addEventListener('beforeinstallprompt', onBeforeInstall)
  window.addEventListener('appinstalled', onInstalled)
  const ua = window.navigator.userAgent
  const isIos = /iphone|ipad|ipod/i.test(ua) && !/crios|fxios/i.test(ua)
  if (isIos && !isStandalone() && !recentlyDismissed()) {
    iosHint.value = true
    visible.value = true
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeinstallprompt', onBeforeInstall)
  window.removeEventListener('appinstalled', onInstalled)
})
</script>

<template>
  <div v-if="visible" class="install-prompt" role="dialog" aria-label="Uygulamayı yükle">
    <img class="install-prompt__icon" src="/pwa-icons/icon-192.png" alt="" width="44" height="44" />
    <div class="install-prompt__text">
      <strong>Uygulamayı yükle</strong>
      <span v-if="iosHint">Paylaş menüsünden "Ana Ekrana Ekle"yi seçin.</span>
      <span v-else>Ana ekrana ekleyip tam ekran kullanın.</span>
    </div>
    <button v-if="!iosHint" type="button" class="install-prompt__btn" @click="install">Yükle</button>
    <button type="button" class="install-prompt__close" aria-label="Kapat" @click="dismiss">×</button>
  </div>
</template>

<style scoped>
.install-prompt {
  position: fixed;
  z-index: 100001;
  left: 16px;
  right: 16px;
  top: calc(12px + env(safe-area-inset-top, 0px));
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 12px 12px 14px;
  border-radius: var(--ds-radius-tile);
  background: var(--ds-surface-dark);
  color: var(--ds-ink-light);
  box-shadow: var(--ds-shadow-float);
}
.install-prompt__icon { border-radius: 12px; flex: none; }
.install-prompt__text { display: flex; flex-direction: column; min-width: 0; flex: 1; font-size: 13px; line-height: 1.25; }
.install-prompt__text strong { font-size: 15px; }
.install-prompt__text span { color: var(--ds-muted-light); }
.install-prompt__btn {
  flex: none;
  border: 0;
  border-radius: var(--ds-radius-pill);
  padding: 10px 18px;
  background: var(--ds-primary);
  color: #fff;
  font: inherit;
  font-weight: 700;
}
.install-prompt__close {
  flex: none;
  border: 0;
  background: transparent;
  color: var(--ds-muted-light);
  font-size: 24px;
  line-height: 1;
  padding: 4px 8px;
}
</style>

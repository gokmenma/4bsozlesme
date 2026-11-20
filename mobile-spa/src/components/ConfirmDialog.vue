<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

defineProps({
  title: { type: String, default: 'Emin misiniz?' },
  message: { type: String, required: true },
  confirmText: { type: String, default: 'Devam Et' },
  cancelText: { type: String, default: 'İptal' },
  busy: { type: Boolean, default: false }
})
const emit = defineEmits(['confirm', 'cancel'])
const root = ref(null)
let previousOverflow = ''
let returnFocus = null

function onKey(event) {
  if (event.key === 'Escape') { event.stopPropagation(); emit('cancel') }
}

onMounted(async () => {
  returnFocus = document.activeElement
  previousOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
  await nextTick()
  root.value?.querySelector('[data-cancel]')?.focus()
})

onBeforeUnmount(() => {
  document.body.style.overflow = previousOverflow
  if (returnFocus?.isConnected) returnFocus.focus()
})
</script>

<template>
  <Teleport to="body">
    <div class="cd-layer" @keydown="onKey" @click.self="emit('cancel')">
      <section ref="root" class="cd-box" role="alertdialog" aria-modal="true" aria-labelledby="cd-title" aria-describedby="cd-msg">
        <h2 id="cd-title">{{ title }}</h2>
        <p id="cd-msg">{{ message }}</p>
        <div class="cd-actions">
          <button type="button" class="cd-btn cd-btn--light" data-cancel @click="emit('cancel')">{{ cancelText }}</button>
          <button type="button" class="cd-btn cd-btn--danger" :disabled="busy" @click="emit('confirm')">{{ busy ? 'Siliniyor…' : confirmText }}</button>
        </div>
      </section>
    </div>
  </Teleport>
</template>

<style scoped>
.cd-layer { position: fixed; inset: 0; z-index: 120; display: grid; place-items: center; padding: 16px; background: rgba(15, 18, 24, .5); }
.cd-box { width: min(100%, 400px); padding: 22px 20px 18px; border-radius: var(--ds-radius-card); background: var(--ds-surface); box-shadow: var(--ds-shadow-float); }
.cd-box h2 { margin: 0 0 8px; font-size: 20px; font-weight: 800; color: var(--ds-ink); }
.cd-box p { margin: 0; font-size: 14px; color: var(--ds-muted); }
.cd-actions { display: flex; gap: 10px; margin-top: 20px; }
.cd-btn { flex: 1; min-height: 48px; border: 0; border-radius: 999px; font-weight: 700; font-size: 14px; cursor: pointer; }
.cd-btn--light { background: var(--ds-surface-subtle); color: var(--ds-ink); }
.cd-btn--danger { background: var(--ds-danger); color: #fff; }
.cd-btn:disabled { opacity: .6; }
</style>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'
import { Check, ChevronsUpDown, Search, X } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  wages: { type: Array, default: () => [] },
  required: { type: Boolean, default: false }
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const query = ref('')
const searchInput = ref(null)
const trigger = ref(null)
let previousOverflow = ''

const fmt = (value) => Number(value || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const norm = (value) => String(value ?? '').toLocaleLowerCase('tr').trim()

const selected = computed(() => props.wages.find((w) => String(w.id) === props.modelValue) || null)
const label = computed(() => selected.value ? `${selected.value.unvan} · ${selected.value.ogrenim} · ${selected.value.kidem_yili}` : '')

const filtered = computed(() => {
  const words = norm(query.value).split(/\s+/).filter(Boolean)
  if (!words.length) return props.wages
  return props.wages.filter((w) => {
    const text = norm(`${w.unvan} ${w.ogrenim} ${w.kidem_yili} ${fmt(w.ucret)}`)
    return words.every((word) => text.includes(word))
  })
})

async function openList() {
  query.value = ''
  open.value = true
  previousOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
  await nextTick()
  searchInput.value?.focus()
}

function close() {
  if (!open.value) return
  open.value = false
  document.body.style.overflow = previousOverflow
  trigger.value?.focus()
}

function pick(wage) {
  emit('update:modelValue', String(wage.id))
  close()
}

function onKey(event) {
  if (event.key === 'Escape') { event.stopPropagation(); close() }
}

onBeforeUnmount(() => { if (open.value) document.body.style.overflow = previousOverflow })
</script>

<template>
  <div class="ws">
    <button ref="trigger" type="button" class="ws__trigger" :class="{ 'is-empty': !selected }" aria-haspopup="dialog" :aria-expanded="open" @click="openList">
      <span class="ws__value">{{ selected ? label : (wages.length ? 'Ücret tanımı seçin...' : 'Yükleniyor…') }}</span>
      <ChevronsUpDown :size="18" class="ws__icon" />
    </button>
    <input v-if="required" class="ws__guard" tabindex="-1" aria-hidden="true" :value="modelValue" required @focus="openList" />

    <Teleport to="body">
      <div v-if="open" class="ws-layer" @keydown="onKey" @click.self="close">
        <section class="ws-sheet" role="dialog" aria-modal="true" aria-label="Unvan / Ücret seçimi">
          <header class="ws-sheet__head">
            <label class="ws-search">
              <Search :size="17" />
              <input ref="searchInput" v-model="query" type="search" placeholder="Unvan veya ücret ara..." autocomplete="off" enterkeyhint="search" />
            </label>
            <button type="button" class="ws-close" aria-label="Kapat" @click="close"><X :size="19" /></button>
          </header>
          <ul v-if="filtered.length" class="ws-list" role="listbox">
            <li v-for="w in filtered" :key="w.id" role="option" :aria-selected="String(w.id) === modelValue">
              <button type="button" class="ws-item" :class="{ 'is-selected': String(w.id) === modelValue }" @click="pick(w)">
                <span class="ws-item__main">
                  <strong>{{ w.unvan }}</strong>
                  <em>{{ fmt(w.ucret) }} TL</em>
                </span>
                <span class="ws-item__meta">{{ w.ogrenim }} · {{ w.kidem_yili }} kıdem</span>
                <Check v-if="String(w.id) === modelValue" :size="18" class="ws-item__check" />
              </button>
            </li>
          </ul>
          <p v-else class="ws-empty">Sonuç bulunamadı.</p>
        </section>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.ws { position: relative; }
.ws__trigger { display: flex; align-items: center; justify-content: space-between; gap: 10px; width: 100%; min-height: 48px; padding: 8px 14px; border: 1px solid var(--ds-border-subtle); border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); color: var(--ds-ink); font: 600 16px var(--ds-font-main); text-align: left; cursor: pointer; }
.ws__trigger:focus-visible { outline: 2px solid var(--ds-focus); outline-offset: 1px; }
.ws__trigger.is-empty { color: var(--ds-muted); }
.ws__value { overflow-wrap: anywhere; }
.ws__icon { flex-shrink: 0; opacity: .5; }
.ws__guard { position: absolute; inset: auto 0 0 0; width: 100%; height: 1px; padding: 0; border: 0; opacity: 0; pointer-events: none; }
.ws-layer { position: fixed; inset: 0; z-index: 130; display: flex; align-items: flex-end; justify-content: center; background: rgba(15, 18, 24, .5); }
.ws-sheet { display: flex; flex-direction: column; width: min(100%, 520px); height: min(80svh, 640px); border-radius: 28px 28px 0 0; background: var(--ds-surface); box-shadow: 0 -16px 40px rgba(0, 0, 0, .25); animation: ws-rise .26s cubic-bezier(.16, 1, .3, 1); }
@keyframes ws-rise { from { transform: translateY(100%); } }
.ws-sheet__head { display: flex; align-items: center; gap: 10px; padding: 14px 14px 10px; }
.ws-search { flex: 1; display: flex; align-items: center; gap: 8px; min-height: 46px; padding: 0 14px; border-radius: 999px; background: var(--ds-surface-subtle); color: var(--ds-muted); }
.ws-search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--ds-ink); font: 600 16px var(--ds-font-main); }
.ws-close { display: grid; place-items: center; flex-shrink: 0; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); cursor: pointer; }
.ws-list { flex: 1; margin: 0; padding: 0 8px calc(12px + var(--ds-safe-bottom)); overflow-y: auto; list-style: none; overscroll-behavior: contain; }
.ws-item { position: relative; display: grid; gap: 4px; width: 100%; padding: 12px 40px 12px 12px; border: 0; border-bottom: 1px solid var(--ds-border-subtle); border-radius: 0; background: transparent; color: var(--ds-ink); text-align: left; cursor: pointer; }
.ws-item.is-selected { background: var(--ds-primary-soft); border-radius: var(--ds-radius-sm); }
.ws-item__main { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; }
.ws-item__main strong { font-size: 15px; font-weight: 800; overflow-wrap: anywhere; }
.ws-item__main em { flex-shrink: 0; font-style: normal; font-weight: 800; font-size: 14px; color: var(--ds-primary); }
.ws-item__meta { font-size: 12px; color: var(--ds-muted); }
.ws-item__check { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--ds-primary); }
.ws-empty { margin: 0; padding: 32px 16px; text-align: center; color: var(--ds-muted); font-size: 14px; }
@media (prefers-reduced-motion: reduce) { .ws-sheet { animation: none; } }
</style>

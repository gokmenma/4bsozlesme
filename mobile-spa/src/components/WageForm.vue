<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { X } from '@lucide/vue'
import { formatInputCurrency, parseTurkishAmount, saveWage } from '../services/wages.js'

const props = defineProps({
  wage: { type: Object, default: null },
  prefill: { type: Object, default: null },
  period: { type: String, default: '2026-1' },
  periods: { type: Array, default: () => [] },
  ogrenimOptions: { type: Array, default: () => [] },
  kidemOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'saved', 'expired'])

const DEFAULT_OGRENIMLER = ['Lise', 'Ön Lisans', 'Lisans', 'Yüksek Lisans', 'Doktora']
const DEFAULT_KIDEMLER = ['0-5 Yıl (Dahil)', '5-10 Yıl (Dahil)', '10-15 Yıl (Dahil)', '15-20 Yıl (Dahil)', '20 Yıl ve Üzeri']

const source = props.wage || props.prefill || null
const baseOgrenimler = props.ogrenimOptions.length ? props.ogrenimOptions : DEFAULT_OGRENIMLER
const baseKidemler = props.kidemOptions.length ? props.kidemOptions : DEFAULT_KIDEMLER

const form = reactive({
  id: props.wage?.id || null,
  unvan: source?.unvan || '',
  ogrenim: source?.ogrenim || baseOgrenimler[0] || 'Lise',
  kidem_yili: source?.kidem_yili || baseKidemler[0] || '0-5 Yıl (Dahil)',
  ucret: source?.ucret !== undefined && source?.ucret !== null && source?.ucret !== ''
    ? formatInputCurrency(source.ucret)
    : '',
  donem: source?.donem || props.period || '2026-1',
})

const busy = ref(false)
const error = ref('')
const root = ref(null)

const isEdit = computed(() => Boolean(props.wage))
const isCopy = computed(() => !props.wage && Boolean(props.prefill))

const resolvedOgrenimler = computed(() => {
  const list = [...baseOgrenimler]
  if (form.ogrenim && !list.includes(form.ogrenim)) {
    list.unshift(form.ogrenim)
  }
  return list
})

const resolvedKidemler = computed(() => {
  const list = [...baseKidemler]
  if (form.kidem_yili && !list.includes(form.kidem_yili)) {
    list.unshift(form.kidem_yili)
  }
  return list
})

const periodSuggestions = computed(() => {
  const set = new Set()
  if (props.period) set.add(props.period)
  for (const item of props.periods) {
    const val = typeof item === 'string' ? item : item?.donem
    if (val) set.add(val)
  }
  return Array.from(set)
})

async function submit() {
  error.value = ''
  const parsedAmount = parseTurkishAmount(form.ucret)
  if (parsedAmount === null || parsedAmount <= 0) {
    error.value = 'Geçerli bir aylık brüt ücret girin.'
    return
  }
  if (!form.unvan.trim()) {
    error.value = 'Unvan zorunludur.'
    return
  }
  if (!form.donem.trim()) {
    error.value = 'Dönem bilgisi zorunludur.'
    return
  }

  busy.value = true
  try {
    const res = await saveWage({
      id: form.id,
      unvan: form.unvan.trim(),
      ogrenim: form.ogrenim,
      kidem_yili: form.kidem_yili,
      ucret: parsedAmount,
      donem: form.donem.trim(),
    })
    emit('saved', {
      period: res.period || form.donem.trim(),
      isEdit: isEdit.value,
      isCopy: isCopy.value,
    })
  } catch (reason) {
    if (reason?.status === 401) {
      emit('expired')
      return
    }
    error.value = reason?.message || 'Kayıt yapılamadı.'
  } finally {
    busy.value = false
  }
}

function handleKeydown(event) {
  if (event.key === 'Escape') emit('close')
}

onMounted(async () => {
  await nextTick()
  root.value?.querySelector('input')?.focus()
})
</script>

<template>
  <div class="layer" @keydown="handleKeydown" @click.self="emit('close')">
    <form
      ref="root"
      class="sheet"
      role="dialog"
      aria-modal="true"
      :aria-label="isEdit ? 'Ücret tanımını düzenle' : 'Ücret tanımı ekle'"
      @submit.prevent="submit"
    >
      <header class="sheet__head">
        <div>
          <span class="eyebrow">{{ isEdit ? 'DÜZENLE' : isCopy ? 'KOPYADAN YENİ' : 'YENİ TANIM' }}</span>
          <h2>{{ isEdit ? 'Ücret tanımını düzenle' : isCopy ? 'Kopyalayarak ekle' : 'Ücret tanımı ekle' }}</h2>
        </div>
        <button type="button" class="close" aria-label="Kapat" @click="emit('close')"><X :size="19" /></button>
      </header>

      <div class="fields">
        <label>Unvan
          <input v-model="form.unvan" type="text" required maxlength="255" placeholder="Örn: Hemşire, Mühendis, Büro Personeli" autocomplete="off" />
        </label>

        <div class="two">
          <label>Öğrenim durumu
            <select v-model="form.ogrenim" required>
              <option v-for="o in resolvedOgrenimler" :key="o" :value="o">{{ o }}</option>
            </select>
          </label>
          <label>Kıdem yılı
            <select v-model="form.kidem_yili" required>
              <option v-for="k in resolvedKidemler" :key="k" :value="k">{{ k }}</option>
            </select>
          </label>
        </div>

        <div class="two">
          <label>Aylık brüt ücret (₺)
            <input
              v-model="form.ucret"
              type="text"
              inputmode="decimal"
              required
              placeholder="0,00"
              class="mono"
              autocomplete="off"
            />
          </label>
          <label>Dönem
            <input
              v-model="form.donem"
              type="text"
              list="wage-period-suggestions"
              required
              maxlength="50"
              placeholder="Örn: 2026-1"
              autocomplete="off"
            />
            <datalist id="wage-period-suggestions">
              <option v-for="p in periodSuggestions" :key="p" :value="p" />
            </datalist>
          </label>
        </div>
      </div>

      <p v-if="error" class="error" role="alert">{{ error }}</p>

      <button type="submit" class="submit" :disabled="busy">
        {{ busy ? 'Kaydediliyor…' : isEdit ? 'Değişiklikleri kaydet' : 'Ücret tanımını ekle' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.layer { position: fixed; inset: 0; z-index: 90; display: flex; align-items: flex-end; justify-content: center; background: rgba(15, 18, 24, .5); }
.sheet { width: min(100%, 520px); max-height: 92svh; overflow-y: auto; padding: 18px 16px calc(18px + var(--ds-safe-bottom)); border-radius: 28px 28px 0 0; background: var(--ds-surface); box-shadow: 0 -16px 40px rgba(0, 0, 0, .25); animation: rise .28s cubic-bezier(.16, 1, .3, 1); }
@keyframes rise { from { transform: translateY(100%); } }
.sheet__head { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.eyebrow { color: var(--ds-muted); font-size: 10px; font-weight: 800; letter-spacing: .12em; }
h2 { margin: 2px 0 0; font-size: 21px; font-weight: 800; }
.close { display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); cursor: pointer; }
.fields { display: grid; gap: 12px; }
.two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
label { display: grid; gap: 6px; color: var(--ds-muted); font-size: 12px; font-weight: 700; min-width: 0; }
input, select { width: 100%; min-height: 48px; padding: 0 14px; border: 1px solid var(--ds-border-subtle); border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); color: var(--ds-ink); font: 600 16px var(--ds-font-main); }
input.mono { font-family: var(--ds-font-mono); font-weight: 700; }
input:focus-visible, select:focus-visible { outline: 2px solid var(--ds-focus); outline-offset: 1px; }
.error { margin: 12px 0 0; padding: 10px 12px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.submit { width: 100%; min-height: 50px; margin-top: 16px; border: 0; border-radius: 999px; background: var(--ds-primary); color: #fff; font-weight: 800; font-size: 15px; cursor: pointer; }
.submit:disabled { opacity: .6; }
@media (prefers-reduced-motion: reduce) { .sheet { animation: none; } }
</style>

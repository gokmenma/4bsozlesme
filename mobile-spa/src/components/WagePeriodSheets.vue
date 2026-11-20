<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { Check, Percent, Star, Trash2, TriangleAlert, X } from '@lucide/vue'
import { copyWagePeriod, deleteWagePeriod, formatCurrency, setDefaultWagePeriod } from '../services/wages.js'

const props = defineProps({
  mode: { type: String, required: true },
  activePeriod: { type: String, default: '2026-1' },
  defaultPeriod: { type: String, default: null },
  periods: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
  sampleBaseAmount: { type: Number, default: 50000 },
})

const emit = defineEmits(['close', 'select', 'switch-mode', 'default-set', 'copied', 'deleted', 'expired'])

const sheet = ref(null)
const busy = ref(false)
const error = ref('')
const confirmDelete = ref(false)

const copyForm = reactive({
  from_donem: props.activePeriod || '2026-1',
  to_donem: '',
  raise_percent: '',
})

const periodList = computed(() => {
  if (props.periods.length) return props.periods
  return [{ donem: props.activePeriod || '2026-1', total: 0 }]
})

const parsedPercent = computed(() => {
  const raw = String(copyForm.raise_percent ?? '').trim().replace(/%/g, '').replace(/\s+/g, '').replace(',', '.')
  if (!raw || !/^-?\d+(\.\d+)?$/.test(raw)) return null
  const num = Number(raw)
  if (!Number.isFinite(num) || num < -90 || num > 500) return null
  return Math.round(num * 100) / 100
})

const previewBase = computed(() => (props.sampleBaseAmount > 0 ? props.sampleBaseAmount : 50000))
const previewProjected = computed(() => {
  if (parsedPercent.value === null) return null
  return Math.round(previewBase.value * (1 + parsedPercent.value / 100) * 100) / 100
})

watch(
  () => [props.mode, props.activePeriod],
  () => {
    error.value = ''
    confirmDelete.value = false
    copyForm.from_donem = props.activePeriod || periodList.value[0]?.donem || '2026-1'
    if (props.mode === 'copy') {
      copyForm.to_donem = ''
      copyForm.raise_percent = ''
    }
  }
)

function handleKeydown(event) {
  if (event.key === 'Escape') emit('close')
}

async function handleSetDefault(donem) {
  if (busy.value) return
  busy.value = true
  error.value = ''
  try {
    const res = await setDefaultWagePeriod(donem)
    emit('default-set', {
      period: res.default_period || donem,
      message: res.message || `'${donem}' varsayılan dönem yapıldı.`,
    })
  } catch (reason) {
    if (reason?.status === 401) {
      emit('expired')
      return
    }
    error.value = reason?.message || 'Varsayılan dönem güncellenemedi.'
  } finally {
    busy.value = false
  }
}

async function handleDeletePeriod() {
  if (busy.value) return
  busy.value = true
  error.value = ''
  try {
    const res = await deleteWagePeriod(props.activePeriod)
    emit('deleted', {
      nextPeriod: res.next_period || '',
      message: res.message || 'Ücret dönemi silindi.',
    })
  } catch (reason) {
    if (reason?.status === 401) {
      emit('expired')
      return
    }
    error.value = reason?.message || 'Dönem silinemedi.'
  } finally {
    busy.value = false
  }
}

async function handleCopySubmit() {
  error.value = ''
  const from = copyForm.from_donem.trim()
  const to = copyForm.to_donem.trim()
  const pct = parsedPercent.value

  if (!from || !to) {
    error.value = 'Kaynak ve hedef dönem bilgilerini girin.'
    return
  }
  if (from === to) {
    error.value = 'Hedef dönem adı kaynak dönemden farklı olmalıdır.'
    return
  }
  if (pct === null) {
    error.value = 'Geçerli bir zam oranı (%) girin.'
    return
  }

  busy.value = true
  try {
    const res = await copyWagePeriod({ from_donem: from, to_donem: to, raise_percent: pct })
    emit('copied', {
      period: res.period || to,
      message: res.message || `'${to}' dönemi oluşturuldu.`,
    })
  } catch (reason) {
    if (reason?.status === 401) {
      emit('expired')
      return
    }
    error.value = reason?.message || 'Dönem kopyalama işlemi tamamlanamadı.'
  } finally {
    busy.value = false
  }
}

onMounted(async () => {
  await nextTick()
  sheet.value?.querySelector('button, input, select')?.focus()
})
</script>

<template>
  <div class="layer" @keydown="handleKeydown" @click.self="emit('close')">
    <section
      ref="sheet"
      class="sheet"
      role="dialog"
      aria-modal="true"
      :aria-label="mode === 'copy' ? 'Dönem kopyala ve toplu zam' : 'Ücret dönemleri'"
    >
      <header class="sheet__head">
        <div>
          <span class="eyebrow">{{ mode === 'copy' ? 'TOPLU ZAM & AKTARIM' : 'ÜCRET DÖNEMLERİ' }}</span>
          <h2>{{ mode === 'copy' ? 'Yeni döneme kopyala' : 'Dönem seçimi' }}</h2>
        </div>
        <button type="button" class="close" aria-label="Kapat" @click="emit('close')"><X :size="19" /></button>
      </header>

      <template v-if="mode === 'periods'">
        <ul class="period-list">
          <li v-for="item in periodList" :key="item.donem" class="period-row" :class="{ 'is-active': item.donem === activePeriod }">
            <button type="button" class="period-select" :disabled="busy" @click="emit('select', item.donem)">
              <span class="period-select__info">
                <strong>{{ item.donem }}</strong>
                <small>{{ item.total }} ücret tanımı</small>
              </span>
              <span v-if="item.donem === defaultPeriod" class="default-tag">Varsayılan</span>
              <Check v-if="item.donem === activePeriod" :size="18" class="check-icon" />
            </button>
            <button
              v-if="canManage && item.donem !== defaultPeriod"
              type="button"
              class="default-btn"
              :disabled="busy"
              :aria-label="`${item.donem} dönemini varsayılan yap`"
              @click="handleSetDefault(item.donem)"
            >
              <Star :size="14" /> Varsayılan yap
            </button>
          </li>
        </ul>

        <p v-if="error" class="error" role="alert">{{ error }}</p>

        <div v-if="canManage" class="period-actions">
          <button type="button" class="btn btn--dark" :disabled="busy" @click="emit('switch-mode', 'copy')">
            <Percent :size="16" /> Toplu zam / Yeni döneme kopyala
          </button>

          <template v-if="periodList.length > 1">
            <button
              v-if="!confirmDelete"
              type="button"
              class="btn btn--danger"
              :disabled="busy"
              @click="confirmDelete = true"
            >
              <Trash2 :size="16" /> "{{ activePeriod }}" dönemini sil
            </button>
            <div v-else class="confirm-box">
              <p><strong>{{ activePeriod }}</strong> dönemine ait kullanılmayan tüm ücret tanımları silinecektir. Bu işlem geri alınamaz.</p>
              <div class="confirm-btns">
                <button type="button" class="btn btn--light" :disabled="busy" @click="confirmDelete = false">Vazgeç</button>
                <button type="button" class="btn btn--danger-solid" :disabled="busy" @click="handleDeletePeriod">
                  {{ busy ? 'Siliniyor…' : 'Evet, dönemi sil' }}
                </button>
              </div>
            </div>
          </template>
        </div>
      </template>

      <form v-else class="copy-form" @submit.prevent="handleCopySubmit">
        <div class="warn-box">
          <TriangleAlert :size="18" />
          <p>Mevcut <strong>{{ copyForm.from_donem }}</strong> dönemindeki ücretler ve personel sözleşmeleri korunur; zamlı veriler yeni dönem altına kaydedilir.</p>
        </div>

        <label>Kaynak dönem
          <select v-model="copyForm.from_donem" required>
            <option v-for="item in periodList" :key="item.donem" :value="item.donem">
              {{ item.donem }} ({{ item.total }} tanım)
            </option>
          </select>
        </label>

        <div class="two">
          <label>Hedef dönem adı
            <input v-model="copyForm.to_donem" type="text" required maxlength="50" placeholder="Örn: 2027-1" autocomplete="off" />
          </label>
          <label>Zam oranı (%)
            <input v-model="copyForm.raise_percent" type="text" inputmode="decimal" required placeholder="Örn: 15,5" autocomplete="off" />
          </label>
        </div>

        <div v-if="previewProjected !== null" class="preview-box">
          <span>Ortalama örnek hesaplama</span>
          <strong>
            {{ formatCurrency(previewBase) }} → {{ formatCurrency(previewProjected) }}
            <em>({{ parsedPercent >= 0 ? '+' : '' }}%{{ parsedPercent.toLocaleString('tr-TR') }})</em>
          </strong>
        </div>

        <p v-if="error" class="error" role="alert">{{ error }}</p>

        <div class="confirm-btns">
          <button type="button" class="btn btn--light" :disabled="busy" @click="emit('switch-mode', 'periods')">Geri</button>
          <button type="submit" class="btn btn--primary" :disabled="busy">
            {{ busy ? 'Aktarılıyor…' : 'Kopyalamayı başlat' }}
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

<style scoped>
.layer { position: fixed; inset: 0; z-index: 90; display: flex; align-items: flex-end; justify-content: center; background: rgba(15, 18, 24, .5); }
.sheet { width: min(100%, 520px); max-height: 90svh; overflow-y: auto; padding: 18px 16px calc(18px + var(--ds-safe-bottom)); border-radius: 28px 28px 0 0; background: var(--ds-surface); box-shadow: 0 -16px 40px rgba(0, 0, 0, .25); animation: rise .28s cubic-bezier(.16, 1, .3, 1); display: grid; gap: 14px; }
@keyframes rise { from { transform: translateY(100%); } }
.sheet__head { display: flex; align-items: flex-start; justify-content: space-between; }
.eyebrow { color: var(--ds-muted); font-size: 10px; font-weight: 800; letter-spacing: .12em; }
h2 { margin: 2px 0 0; font-size: 21px; font-weight: 800; }
.close { display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); cursor: pointer; }

.period-list { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; }
.period-row { display: flex; align-items: center; gap: 8px; padding: 6px 10px 6px 14px; border-radius: var(--ds-radius-tile); background: var(--ds-surface-subtle); border: 1.5px solid transparent; }
.period-row.is-active { background: var(--ds-primary-soft); border-color: rgba(29, 78, 216, .25); }
.period-select { flex: 1; min-width: 0; display: flex; align-items: center; justify-content: space-between; gap: 10px; min-height: 52px; padding: 4px 0; border: 0; background: transparent; text-align: left; cursor: pointer; color: var(--ds-ink); }
.period-select__info { display: grid; gap: 2px; min-width: 0; }
.period-select__info strong { font-size: 15px; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.period-select__info small { color: var(--ds-muted); font-size: 12px; font-weight: 600; }
.default-tag { flex-shrink: 0; padding: 4px 9px; border-radius: 999px; background: #fef3c7; color: #b45309; font-size: 10px; font-weight: 800; }
.check-icon { flex-shrink: 0; color: var(--ds-primary); }
.default-btn { flex-shrink: 0; display: inline-flex; align-items: center; gap: 5px; min-height: 36px; padding: 0 10px; border: 0; border-radius: 999px; background: var(--ds-surface); color: var(--ds-ink); font-size: 11px; font-weight: 700; cursor: pointer; box-shadow: var(--ds-shadow-pill); }
.default-btn:active { transform: scale(.96); }

.period-actions { display: grid; gap: 8px; padding-top: 4px; }
.confirm-box { display: grid; gap: 10px; padding: 12px 14px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); }
.confirm-box p { margin: 0; font-size: 13px; line-height: 1.45; }
.confirm-btns { display: flex; gap: 8px; }

.copy-form { display: grid; gap: 12px; }
.warn-box { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border-radius: var(--ds-radius-sm); background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.warn-box svg { flex-shrink: 0; margin-top: 1px; color: #d97706; }
.warn-box p { margin: 0; font-size: 12px; line-height: 1.45; }

.two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
label { display: grid; gap: 6px; color: var(--ds-muted); font-size: 12px; font-weight: 700; min-width: 0; }
input, select { width: 100%; min-height: 48px; padding: 0 14px; border: 1px solid var(--ds-border-subtle); border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); color: var(--ds-ink); font: 600 16px var(--ds-font-main); }
input:focus-visible, select:focus-visible { outline: 2px solid var(--ds-focus); outline-offset: 1px; }

.preview-box { display: grid; gap: 4px; padding: 12px 14px; border-radius: var(--ds-radius-sm); background: var(--ds-primary-soft); color: var(--ds-primary-ink); }
.preview-box span { font-size: 11px; font-weight: 700; opacity: .8; }
.preview-box strong { font: 700 15px/1.3 var(--ds-font-mono); }
.preview-box em { font-style: normal; font-size: 13px; margin-left: 4px; }

.error { margin: 0; padding: 10px 12px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 48px; padding: 0 16px; border: 0; border-radius: 999px; font-weight: 700; font-size: 14px; cursor: pointer; }
.btn:disabled { opacity: .6; }
.btn--primary { background: var(--ds-primary); color: #fff; }
.btn--dark { background: var(--ds-surface-dark); color: #fff; }
.btn--light { background: var(--ds-surface-subtle); color: var(--ds-ink); }
.btn--danger { background: var(--ds-danger-bg); color: var(--ds-danger-ink); }
.btn--danger-solid { background: var(--ds-danger); color: #fff; }
@media (prefers-reduced-motion: reduce) { .sheet { animation: none; } }
</style>

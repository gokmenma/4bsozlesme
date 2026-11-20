<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { AlertCircle, CircleCheck, Download, RefreshCw, TriangleAlert } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import { auth } from '../services/auth.js'
import { STATUS_META } from '../services/personnel.js'
import { buildDoner, downloadXlsx, getDonerDefaults } from '../services/doner.js'

const router = useRouter()
const MONTHS = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık']
const OPS = [
  ['tum', 'Tümü'], ['empty', 'Boş (ayrılmamış)'], ['not_empty', 'Dolu (ayrılmış)'],
  ['gt', 'Büyüktür'], ['lt', 'Küçüktür'], ['gte', 'Büyük eşit'], ['lte', 'Küçük eşit'], ['equals', 'Eşittir'],
]

const now = new Date()
const prev = new Date(now.getFullYear(), now.getMonth() - 1, 1)
const years = [now.getFullYear() - 2, now.getFullYear() - 1, now.getFullYear(), now.getFullYear() + 1]

const loading = ref(true)
const busy = ref(false)
const error = ref('')
const formError = ref('')
const toast = ref('')
const unmatched = ref([])
const form = ref({
  donem_ay: prev.getMonth() + 1,
  donem_yil: prev.getFullYear(),
  personel_durum: ['aktif'],
  ayrilma_op: 'tum',
  ayrilma_tarihi: '',
  maas_katsayisi: '',
  yan_odeme_katsayisi: '',
})
let toastTimer = null

const needsDate = computed(() => ['gt', 'lt', 'gte', 'lte', 'equals'].includes(form.value.ayrilma_op))

function showToast(message) {
  toast.value = message
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = '' }, 3500)
}

async function handleExpired() {
  auth.expire()
  await router.replace({ name: 'login', query: { expired: '1' } })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const d = await getDonerDefaults()
    form.value.maas_katsayisi = d.maas_katsayisi || ''
    form.value.yan_odeme_katsayisi = d.yan_odeme_katsayisi || ''
  } catch (reason) {
    if (reason?.status === 401) return handleExpired()
    error.value = reason?.message || 'Varsayılanlar alınamadı.'
  } finally {
    loading.value = false
  }
}

function toggleStatus(key) {
  const list = form.value.personel_durum
  form.value.personel_durum = list.includes(key) ? list.filter((s) => s !== key) : [...list, key]
}

async function submit() {
  if (busy.value) return
  busy.value = true
  formError.value = ''
  unmatched.value = []
  try {
    const data = await buildDoner({ ...form.value, donem_ay: Number(form.value.donem_ay), donem_yil: Number(form.value.donem_yil) })
    unmatched.value = data.unmatched
    if (!data.data.length) {
      formError.value = 'Seçilen kriterlere uyan personel bulunamadı.'
      return
    }
    await downloadXlsx(data.data, form.value.donem_ay, form.value.donem_yil)
    showToast(`${data.data.length} kayıtlı Excel hazırlandı.`)
  } catch (reason) {
    if (reason?.status === 401) return handleExpired()
    formError.value = reason?.message || 'Matrah oluşturulamadı.'
  } finally {
    busy.value = false
  }
}

onMounted(load)
onBeforeUnmount(() => clearTimeout(toastTimer))
</script>

<template>
  <div class="page">
    <TopBar eyebrow="DİĞER İŞLEMLER" title="Döner Matrahı" :back="true" />

    <main class="doner">
      <div v-if="error" class="notice" role="alert">
        <AlertCircle :size="18" /><span>{{ error }}</span>
        <button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button>
      </div>

      <div v-if="loading" class="skeleton" aria-hidden="true"></div>

      <form v-else-if="!error" class="form" @submit.prevent="submit">
        <section class="card">
          <h2>Dönem</h2>
          <div class="two">
            <label>Ay<select v-model.number="form.donem_ay"><option v-for="(m, i) in MONTHS" :key="m" :value="i + 1">{{ m }}</option></select></label>
            <label>Yıl<select v-model.number="form.donem_yil"><option v-for="y in years" :key="y" :value="y">{{ y }}</option></select></label>
          </div>
        </section>

        <section class="card">
          <h2>Personel filtresi</h2>
          <fieldset>
            <legend>Personel durumu <small>(boş = tümü)</small></legend>
            <div class="chips">
              <button v-for="(meta, key) in STATUS_META" :key="key" type="button" class="chip" :class="{ 'is-active': form.personel_durum.includes(key) }" :aria-pressed="form.personel_durum.includes(key)" @click="toggleStatus(key)">{{ meta.label }}</button>
            </div>
          </fieldset>
          <label>Ayrılış tarihi koşulu<select v-model="form.ayrilma_op"><option v-for="[v, l] in OPS" :key="v" :value="v">{{ l }}</option></select></label>
          <label v-if="needsDate">Ayrılış tarihi<input v-model="form.ayrilma_tarihi" type="date" required /></label>
        </section>

        <section class="card">
          <h2>Katsayılar</h2>
          <label>Maaş katsayısı<input v-model="form.maas_katsayisi" type="text" inputmode="decimal" placeholder="0.907796" required /></label>
          <label>Yan ödeme katsayısı<input v-model="form.yan_odeme_katsayisi" type="text" inputmode="decimal" placeholder="0.287912" required /></label>
        </section>

        <p v-if="formError" class="notice notice--inline" role="alert">{{ formError }}</p>

        <section v-if="unmatched.length" class="warn" role="alert">
          <h3><TriangleAlert :size="16" /> Matrah eşleşmeyen {{ unmatched.length }} personel</h3>
          <p>Bu personel için matrah değerleri 0 olarak yazıldı:</p>
          <ul><li v-for="n in unmatched" :key="n">{{ n }}</li></ul>
        </section>

        <button type="submit" class="save" :disabled="busy"><Download :size="18" /> {{ busy ? 'Hazırlanıyor…' : 'Matrahı oluştur ve indir' }}</button>
      </form>
    </main>

    <Teleport to="body">
      <Transition name="island">
        <div v-if="toast" class="island" role="status" aria-live="polite"><CircleCheck :size="18" /><span>{{ toast }}</span></div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.page { min-height: 100svh; }
.doner { display: grid; gap: 14px; padding: 14px 16px calc(112px + var(--ds-safe-bottom)); max-width: 520px; margin: 0 auto; }
.form { display: grid; gap: 14px; }
.card { display: grid; gap: 12px; padding: 16px; border-radius: var(--ds-radius-card); background: var(--ds-surface); box-shadow: var(--ds-shadow-bento); }
.card h2 { margin: 0; font-size: 15px; }
.card label, .card legend { display: grid; gap: 4px; color: var(--ds-muted); font-size: 11px; font-weight: 700; min-width: 0; }
.card legend small { font-weight: 600; }
.card input, .card select { width: 100%; min-height: 46px; padding: 0 12px; border: 1px solid var(--ds-border-subtle); border-radius: 12px; background: var(--ds-surface-subtle); font: 600 16px var(--ds-font-main); color: var(--ds-ink); box-sizing: border-box; }
.card input:focus, .card select:focus { outline: 2px solid var(--ds-focus); }
.card fieldset { margin: 0; padding: 0; border: 0; display: grid; gap: 6px; }
.two { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.chips { display: flex; flex-wrap: wrap; gap: 6px; }
.chip { min-height: 38px; padding: 0 14px; border: 0; border-radius: 999px; background: var(--ds-surface-subtle); font: 700 13px var(--ds-font-main); color: var(--ds-ink); cursor: pointer; }
.chip.is-active { background: var(--ds-surface-dark); color: #fff; }
.warn { padding: 14px; border-radius: var(--ds-radius-sm); background: #fef3c7; color: #92400e; font-size: 13px; }
.warn h3 { display: flex; align-items: center; gap: 6px; margin: 0 0 4px; font-size: 14px; }
.warn p { margin: 0 0 6px; }
.warn ul { margin: 0; padding-left: 18px; max-height: 160px; overflow-y: auto; }
.save { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 52px; border: 0; border-radius: 999px; background: var(--ds-primary); color: #fff; font: 800 15px var(--ds-font-main); box-shadow: var(--ds-shadow-btn); cursor: pointer; }
.save:disabled { opacity: .6; box-shadow: none; cursor: default; }
.notice { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.notice span { flex: 1; }
.notice button { display: inline-flex; align-items: center; gap: 5px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 999px; background: var(--ds-surface); color: var(--ds-ink); font-weight: 700; cursor: pointer; }
.notice--inline { margin: 0; }
.skeleton { height: 320px; border-radius: var(--ds-radius-card); background: linear-gradient(90deg, #dde3ea 25%, #eef1f5 50%, #dde3ea 75%); background-size: 200% 100%; animation: shimmer 1.3s infinite; }
@keyframes shimmer { to { background-position: -200% 0; } }
.island { position: fixed; z-index: 200; left: 50%; top: calc(10px + var(--ds-safe-top, 0px)); transform: translateX(-50%); display: inline-flex; align-items: center; gap: 8px; max-width: calc(100% - 32px); padding: 12px 22px; border-radius: 999px; background: #000; color: #fff; font-size: 14px; font-weight: 700; white-space: nowrap; box-shadow: 0 10px 30px rgba(0, 0, 0, .35); }
.island svg { flex-shrink: 0; color: #34d399; }
.island-enter-active { animation: island-in .55s cubic-bezier(.34, 1.4, .5, 1) both; }
.island-leave-active { animation: island-out .35s cubic-bezier(.4, 0, 1, 1) both; }
@keyframes island-in { from { opacity: 0; transform: translateX(-50%) translateY(-24px) scale(.3, .5); } to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); } }
@keyframes island-out { to { opacity: 0; transform: translateX(-50%) translateY(-18px) scale(.4, .6); } }
@media (prefers-reduced-motion: reduce) { .skeleton { animation: none; } .island-enter-active, .island-leave-active { animation: none; } }
</style>

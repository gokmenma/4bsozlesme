<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { AlertCircle, Building2, CalendarRange, CircleCheck, Percent, RefreshCw, Save, UserRound } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import { auth } from '../services/auth.js'
import { getDefinitions, saveDefinitions } from '../services/definitions.js'

const router = useRouter()
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const toast = ref('')
const periods = ref([])
const form = ref({})
const original = ref('')
let toastTimer = null

const dirty = computed(() => JSON.stringify(form.value) !== original.value)

function showToast(message) {
  toast.value = message
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = '' }, 3000)
}

async function handleExpired() {
  auth.expire()
  await router.replace({ name: 'login', query: { expired: '1' } })
}

function apply(values) {
  form.value = { ...values }
  original.value = JSON.stringify(form.value)
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await getDefinitions()
    periods.value = data.periods
    apply(data.values)
  } catch (reason) {
    if (reason?.status === 401) return handleExpired()
    error.value = reason?.message || 'Tanımlamalar alınamadı.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  if (saving.value) return
  saving.value = true
  formError.value = ''
  try {
    const data = await saveDefinitions({ ...form.value })
    apply(data.values)
    showToast('Değişiklikler kaydedildi.')
  } catch (reason) {
    if (reason?.status === 401) return handleExpired()
    formError.value = reason?.message || 'Kaydedilemedi.'
  } finally {
    saving.value = false
  }
}

onMounted(load)
onBeforeUnmount(() => clearTimeout(toastTimer))
</script>

<template>
  <div class="page">
    <TopBar eyebrow="DİĞER İŞLEMLER" title="Tanımlamalar" :back="true" />

    <main class="defs">
      <div v-if="error" class="notice" role="alert">
        <AlertCircle :size="18" /><span>{{ error }}</span>
        <button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button>
      </div>

      <div v-if="loading" class="skeletons" aria-hidden="true"><i v-for="n in 3" :key="n"></i></div>

      <form v-else-if="!error" class="form" @submit.prevent="submit">
        <section class="card">
          <h2><CalendarRange :size="17" /> Ücret dönemi</h2>
          <label>Varsayılan ücret dönemi
            <select v-model="form.default_wage_period" required>
              <option v-for="p in periods" :key="p" :value="p">{{ p }}</option>
            </select>
          </label>
        </section>

        <section class="card">
          <h2><Building2 :size="17" /> Kurum bilgileri</h2>
          <label>Kurum adı<input v-model="form.kurum_adi" type="text" maxlength="255" autocomplete="off" placeholder="Örn: ABC Teknolojileri A.Ş." /></label>
          <label>Birim adı<input v-model="form.birim_adi" type="text" maxlength="255" autocomplete="off" placeholder="Personellerin görev yaptığı birim" /></label>
        </section>

        <section class="card">
          <h2><UserRound :size="17" /> Yetkili bilgileri</h2>
          <label>Ad soyad<input v-model="form.yetkili_ad_soyad" type="text" maxlength="255" autocomplete="off" /></label>
          <label>Ünvan<input v-model="form.yetkili_unvan" type="text" maxlength="255" autocomplete="off" /></label>
        </section>

        <section class="card">
          <h2><Percent :size="17" /> Katsayılar</h2>
          <label>Maaş katsayısı<input v-model="form.maas_katsayisi" type="text" inputmode="decimal" placeholder="0.907796" /></label>
          <label>Yan ödeme katsayısı<input v-model="form.yan_odeme_katsayisi" type="text" inputmode="decimal" placeholder="0.287912" /></label>
        </section>

        <p v-if="formError" class="notice notice--inline" role="alert">{{ formError }}</p>
        <button type="submit" class="save" :disabled="saving || !dirty"><Save :size="18" /> {{ saving ? 'Kaydediliyor…' : 'Kaydet' }}</button>
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
.defs { display: grid; gap: 14px; padding: 14px 16px calc(112px + var(--ds-safe-bottom)); max-width: 520px; margin: 0 auto; }
.form { display: grid; gap: 14px; }
.card { display: grid; gap: 12px; padding: 16px; border-radius: var(--ds-radius-card); background: var(--ds-surface); box-shadow: var(--ds-shadow-bento); }
.card h2 { display: flex; align-items: center; gap: 8px; margin: 0; font-size: 15px; }
.card label { display: grid; gap: 4px; color: var(--ds-muted); font-size: 11px; font-weight: 700; min-width: 0; }
.card input, .card select { width: 100%; min-height: 46px; padding: 0 12px; border: 1px solid var(--ds-border-subtle); border-radius: 12px; background: var(--ds-surface-subtle); font: 600 16px var(--ds-font-main); color: var(--ds-ink); box-sizing: border-box; }
.card input:focus, .card select:focus { outline: 2px solid var(--ds-focus); }
.save { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 52px; border: 0; border-radius: 999px; background: var(--ds-primary); color: #fff; font: 800 15px var(--ds-font-main); box-shadow: var(--ds-shadow-btn); cursor: pointer; }
.save:disabled { opacity: .5; box-shadow: none; cursor: default; }
.notice { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.notice span { flex: 1; }
.notice button { display: inline-flex; align-items: center; gap: 5px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 999px; background: var(--ds-surface); color: var(--ds-ink); font-weight: 700; cursor: pointer; }
.notice--inline { margin: 0; }
.skeletons { display: grid; gap: 14px; }
.skeletons i { height: 130px; border-radius: var(--ds-radius-card); background: linear-gradient(90deg, #dde3ea 25%, #eef1f5 50%, #dde3ea 75%); background-size: 200% 100%; animation: shimmer 1.3s infinite; }
@keyframes shimmer { to { background-position: -200% 0; } }
.island { position: fixed; z-index: 200; left: 50%; top: calc(10px + var(--ds-safe-top, 0px)); transform: translateX(-50%); display: inline-flex; align-items: center; gap: 8px; max-width: calc(100% - 32px); padding: 12px 22px; border-radius: 999px; background: #000; color: #fff; font-size: 14px; font-weight: 700; white-space: nowrap; box-shadow: 0 10px 30px rgba(0, 0, 0, .35); }
.island svg { flex-shrink: 0; color: #34d399; }
.island-enter-active { animation: island-in .55s cubic-bezier(.34, 1.4, .5, 1) both; }
.island-leave-active { animation: island-out .35s cubic-bezier(.4, 0, 1, 1) both; }
@keyframes island-in { from { opacity: 0; transform: translateX(-50%) translateY(-24px) scale(.3, .5); } to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); } }
@keyframes island-out { to { opacity: 0; transform: translateX(-50%) translateY(-18px) scale(.4, .6); } }
@media (prefers-reduced-motion: reduce) { .skeletons i { animation: none; } .island-enter-active, .island-leave-active { animation: none; } }
</style>

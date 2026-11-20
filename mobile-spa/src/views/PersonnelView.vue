<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { AlertCircle, ArrowUp, CalendarOff, ScanLine, CircleCheck, Pencil, Phone, Plus, RefreshCw, Search, Trash2, UsersRound, X } from '@lucide/vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import PersonnelForm from '../components/PersonnelForm.vue'
import { releaseScanner, scanIdCard } from '../services/idScan.js'
import IdCameraScanner from '../components/IdCameraScanner.vue'
import TopBar from '../components/TopBar.vue'
import { auth } from '../services/auth.js'
import { STATUS_META, addLeave, deleteLeave, deletePersonnel, getPersonnel, listPersonnel } from '../services/personnel.js'

const router = useRouter()
const items = ref([])
const total = ref(0)
const counts = ref({})
const page = ref(1)
const hasMore = ref(false)
const loading = ref(true)
const loadingMore = ref(false)
const error = ref('')
const query = ref('')
const status = ref('')
let debounce = null
let requestSeq = 0

const detail = ref(null)
const detailLoading = ref(false)
const detailError = ref('')
const sheet = ref(null)
const leaves = ref([])
const formOpen = ref(false)
const formPerson = ref(null)
const confirmDelete = ref(false)
const actionBusy = ref(false)
const actionError = ref('')
const leaveForm = ref({ baslangic_tarihi: '', bitis_tarihi: '', aciklama: '' })
const toast = ref('')
const showTop = ref(false)
const scanInput = ref(null)
const scanning = ref(false)
const cameraOpen = ref(false)
const scanStep = ref('')
const formPrefill = ref(null)
let toastTimer = null
let previousOverflow = ''
let returnFocus = null

const allCount = computed(() => Object.values(counts.value).reduce((a, b) => a + b, 0))
const filters = computed(() => [
  { key: '', label: 'Tümü', count: allCount.value },
  ...Object.entries(STATUS_META).map(([key, meta]) => ({ key, label: meta.label, count: counts.value[key] || 0 })),
])

function shortDate(value) {
  const v = String(value || '').slice(0, 10)
  if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return `${v.slice(8, 10)}.${v.slice(5, 7)}.${v.slice(0, 4)}`
  return v || '—'
}

function initialsOf(name) {
  const parts = String(name || '?').trim().split(/\s+/)
  return ((parts[0]?.[0] || '') + (parts.length > 1 ? parts.at(-1)[0] : '')).toLocaleUpperCase('tr-TR')
}

function formatDate(value) {
  if (!value) return '—'
  const d = new Date(`${value.slice(0, 10)}T00:00:00`)
  return Number.isNaN(d.getTime()) ? '—' : new Intl.DateTimeFormat('tr-TR', { day: 'numeric', month: 'long', year: 'numeric' }).format(d)
}

async function handleError(reason) {
  if (reason?.status === 401) {
    auth.expire()
    await router.replace({ name: 'login', query: { expired: '1' } })
    return true
  }
  error.value = reason?.message || 'Personel verileri alınamadı.'
  return false
}

async function load(reset = true) {
  const seq = ++requestSeq
  if (reset) {
    loading.value = true
    page.value = 1
  } else {
    loadingMore.value = true
  }
  error.value = ''
  try {
    const data = await listPersonnel({ q: query.value.trim(), status: status.value, page: reset ? 1 : page.value + 1 })
    if (seq !== requestSeq) return
    items.value = reset ? data.items : [...items.value, ...data.items]
    total.value = data.total
    counts.value = data.counts || {}
    page.value = data.page
    hasMore.value = data.has_more
  } catch (reason) {
    if (seq === requestSeq) await handleError(reason)
  } finally {
    if (seq === requestSeq) {
      loading.value = false
      loadingMore.value = false
    }
  }
}

watch(query, () => {
  clearTimeout(debounce)
  debounce = setTimeout(() => load(true), 350)
})

function setStatus(key) {
  if (status.value === key) return
  status.value = key
  load(true)
}

async function openDetail(person) {
  returnFocus = document.activeElement
  previousOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
  detail.value = { ...person }
  leaves.value = []
  actionError.value = ''
  leaveForm.value = { baslangic_tarihi: '', bitis_tarihi: '', aciklama: '' }
  detailLoading.value = true
  detailError.value = ''
  await nextTick()
  sheet.value?.querySelector('button')?.focus()
  try {
    const data = await getPersonnel(person.id)
    detail.value = data.person
    leaves.value = data.leaves || []
  } catch (reason) {
    detailError.value = reason?.message || 'Detay alınamadı.'
  } finally {
    detailLoading.value = false
  }
}

function showToast(message) {
  toast.value = message
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = '' }, 3200)
}

async function editPerson(person) {
  if (actionBusy.value) return
  actionBusy.value = true
  try {
    const data = await getPersonnel(person.id)
    openForm(data.person)
  } catch (reason) {
    if (reason?.status === 401) await handleExpired()
    else showToast(reason?.message || 'Personel açılamadı.')
  } finally {
    actionBusy.value = false
  }
}

function onScroll() {
  showTop.value = window.scrollY > 400
}

function scrollTop() {
  window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })
}

function startScan() {
  if (scanning.value) return
  if (navigator.mediaDevices?.getUserMedia) { cameraOpen.value = true; return }
  scanInput.value?.click()
}

function handleCameraFallback() {
  cameraOpen.value = false
  scanInput.value?.click()
}

function handleCameraResult(result) {
  cameraOpen.value = false
  applyScanResult(result)
}

function applyScanResult(result) {
  if (!result.tc && !result.ad_soyad) {
    showToast('Kimlik okunamadı, daha net çekin.')
    return
  }
  formPrefill.value = { tc_kimlik: result.tc, ad_soyad: result.ad_soyad }
  openForm(null, true)
  showToast(result.tc && result.ad_soyad ? 'Kimlik okundu, kontrol edin.' : 'Kısmen okundu, eksikleri tamamlayın.')
}

async function handleScanFile(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  scanning.value = true
  scanStep.value = 'Hazırlanıyor…'
  try {
    const result = await scanIdCard(file, (m) => {
      if (m.status === 'recognizing text') scanStep.value = `Okunuyor… %${Math.round((m.progress || 0) * 100)}`
      else if (m.status) scanStep.value = 'Okuyucu yükleniyor…'
    })
    applyScanResult(result)
  } catch {
    showToast('Kimlik taraması başarısız oldu.')
  } finally {
    scanning.value = false
  }
}

function openForm(person = null, keepPrefill = false) {
  if (!keepPrefill) formPrefill.value = null
  formPerson.value = person
  formOpen.value = true
}

async function handleSaved() {
  const wasEdit = Boolean(formPerson.value)
  const editedId = formPerson.value?.id
  formOpen.value = false
  showToast(wasEdit ? 'Personel güncellendi.' : 'Personel eklendi.')
  await load(true)
  if (wasEdit && detail.value && detail.value.id === editedId) {
    try {
      const data = await getPersonnel(editedId)
      detail.value = data.person
      leaves.value = data.leaves || []
    } catch { closeDetail() }
  }
}

async function handleExpired() {
  formOpen.value = false
  closeDetail()
  auth.expire()
  await router.replace({ name: 'login', query: { expired: '1' } })
}

async function runAction(fn) {
  actionError.value = ''
  actionBusy.value = true
  try {
    return await fn()
  } catch (reason) {
    if (reason?.status === 401) { await handleExpired(); return null }
    actionError.value = reason?.message || 'İşlem tamamlanamadı.'
    return null
  } finally {
    actionBusy.value = false
  }
}

async function removePerson() {
  confirmDelete.value = false
  const id = detail.value.id
  const result = await runAction(() => deletePersonnel(id))
  if (!result) return
  closeDetail()
  showToast('Personel silindi.')
  await load(true)
}

async function submitLeave() {
  const result = await runAction(() => addLeave(detail.value.id, leaveForm.value))
  if (!result) return
  leaves.value = result.leaves
  leaveForm.value = { baslangic_tarihi: '', bitis_tarihi: '', aciklama: '' }
  showToast('Ücretsiz izin eklendi.')
}

async function removeLeave(id) {
  const result = await runAction(() => deleteLeave(detail.value.id, id))
  if (result) leaves.value = result.leaves
}

function closeDetail() {
  if (!detail.value) return
  detail.value = null
  document.body.style.overflow = previousOverflow
  if (returnFocus?.isConnected) returnFocus.focus()
}

function handleKeydown(event) {
  if (event.key === 'Escape' && !formOpen.value) closeDetail()
}

const detailRows = computed(() => {
  const p = detail.value
  if (!p) return []
  return [
    { label: 'TC Kimlik No', value: p.tc_kimlik || '—' },
    { label: 'Unvan', value: p.unvan || '—' },
    { label: 'Öğrenim', value: p.ogrenim || '—' },
    { label: 'Cinsiyet', value: { erkek: 'Erkek', kadin: 'Kadın', diger: 'Diğer' }[p.cinsiyet] || '—' },
    { label: 'Meslek kodu', value: p.meslek_kodu || '—' },
    { label: 'Göreve başlama', value: formatDate(p.goreve_baslama_tarihi) },
    { label: 'Ayrılma tarihi', value: formatDate(p.ayrilma_tarihi) },
  ]
})

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  load(true)
})
onBeforeUnmount(() => {
  releaseScanner()
  window.removeEventListener('scroll', onScroll)
  clearTimeout(debounce)
  clearTimeout(toastTimer)
  if (detail.value) document.body.style.overflow = previousOverflow
})
</script>

<template>
  <div class="page">
    <TopBar eyebrow="İNSAN KAYNAKLARI" title="Personel" :back="true" />

    <main class="people">
      <label class="search">
        <Search :size="18" />
        <input v-model="query" type="search" placeholder="Ad, TC, unvan veya telefon ara…" aria-label="Personel ara" autocomplete="off" />
      </label>

      <div class="chips" role="group" aria-label="Durum filtresi">
        <button v-for="f in filters" :key="f.key" type="button" class="chip" :class="{ 'is-active': status === f.key }" :aria-pressed="status === f.key" @click="setStatus(f.key)">
          {{ f.label }} <b>{{ f.count }}</b>
        </button>
      </div>

      <div v-if="error" class="notice" role="alert">
        <AlertCircle :size="18" /><span>{{ error }}</span>
        <button type="button" @click="load(true)"><RefreshCw :size="15" /> Yenile</button>
      </div>

      <div v-if="loading" class="skeletons" aria-hidden="true"><i v-for="n in 6" :key="n"></i></div>

      <template v-else>
        <p v-if="items.length" class="count">{{ total }} kayıt</p>
        <ul v-if="items.length" class="cards">
          <li v-for="person in items" :key="person.id" class="row">
            <button type="button" class="person" @click="openDetail(person)">
              <span class="avatar">{{ initialsOf(person.ad_soyad) }}</span>
              <span class="person__text">
                <strong>{{ person.ad_soyad }}</strong>
                <small>{{ person.unvan || 'Unvan tanımsız' }} · {{ shortDate(person.goreve_baslama_tarihi) }}</small>
              </span>
              <span class="badge" :class="`tone-${STATUS_META[person.durum]?.tone || 'gray'}`">{{ STATUS_META[person.durum]?.label || person.durum }}</span>
            </button>
            <button type="button" class="info-btn" :aria-label="`${person.ad_soyad} düzenle`" :disabled="actionBusy" @click="editPerson(person)"><Pencil :size="17" /></button>
          </li>
        </ul>
        <div v-else-if="!error" class="empty"><UsersRound :size="28" /><p>{{ query || status ? 'Aramanıza uyan personel bulunamadı.' : 'Henüz personel kaydı yok.' }}</p></div>

        <button v-if="hasMore" type="button" class="more" :disabled="loadingMore" @click="load(false)">{{ loadingMore ? 'Yükleniyor…' : 'Daha fazla göster' }}</button>
      </template>
    </main>

    <Teleport to="body">
      <Transition name="pop">
        <button v-if="showTop && !formOpen && !detail" type="button" class="to-top" aria-label="Yukarı çık" @click="scrollTop"><ArrowUp :size="20" /></button>
      </Transition>
      <button v-if="!formOpen && !detail" type="button" class="scan-fab" aria-label="Kimlikten tara" :disabled="scanning" @click="startScan"><ScanLine :size="20" /> Kimlik tara</button>
      <button v-if="!formOpen && !detail" type="button" class="fab" aria-label="Personel ekle" @click="openForm()"><Plus :size="22" /> Ekle</button>
      <Transition name="island">
        <div v-if="toast" class="island" role="status" aria-live="polite">
          <CircleCheck :size="18" /><span>{{ toast }}</span>
        </div>
      </Transition>
    </Teleport>
    <IdCameraScanner v-if="cameraOpen" @result="handleCameraResult" @close="cameraOpen = false" @fallback="handleCameraFallback" />
    <input ref="scanInput" type="file" accept="image/*" capture="environment" class="sr-file" tabindex="-1" aria-hidden="true" @change="handleScanFile" />
    <Teleport to="body">
      <div v-if="scanning" class="scan-layer" role="status" aria-live="polite"><div class="scan-card"><span class="spinner"></span><strong>{{ scanStep }}</strong><small>Görüntü cihazınızda işlenir, dışarı gönderilmez.</small></div></div>
    </Teleport>
    <PersonnelForm v-if="formOpen" :person="formPerson" :prefill="formPrefill" @close="formOpen = false" @saved="handleSaved" @expired="handleExpired" />

    <Transition name="sheet">
      <div v-if="detail" class="layer" @keydown="handleKeydown" @click.self="closeDetail">
        <section ref="sheet" class="sheet" role="dialog" aria-modal="true" aria-label="Personel detayı">
          <header class="sheet__head">
            <span class="avatar avatar--lg">{{ initialsOf(detail.ad_soyad) }}</span>
            <div class="sheet__title">
              <h2>{{ detail.ad_soyad }}</h2>
              <span class="badge" :class="`tone-${STATUS_META[detail.durum]?.tone || 'gray'}`">{{ STATUS_META[detail.durum]?.label || detail.durum }}</span>
            </div>
            <button type="button" class="close" aria-label="Kapat" @click="closeDetail"><X :size="19" /></button>
          </header>

          <p v-if="detailError" class="notice notice--inline" role="alert">{{ detailError }}</p>
          <dl v-else class="facts" :aria-busy="detailLoading">
            <div v-for="row in detailRows" :key="row.label"><dt>{{ row.label }}</dt><dd>{{ row.value }}</dd></div>
          </dl>

          <section class="leaves" aria-label="Ücretsiz izinler">
            <h3><CalendarOff :size="16" /> Ücretsiz izinler</h3>
            <ul v-if="leaves.length">
              <li v-for="leave in leaves" :key="leave.id">
                <span><strong>{{ formatDate(leave.baslangic_tarihi) }} – {{ formatDate(leave.bitis_tarihi) }}</strong><small>{{ leave.gun_sayisi }} gün<template v-if="leave.aciklama"> · {{ leave.aciklama }}</template></small></span>
                <button type="button" class="icon-btn" :aria-label="`${leave.gun_sayisi} günlük izni sil`" :disabled="actionBusy" @click="removeLeave(leave.id)"><Trash2 :size="16" /></button>
              </li>
            </ul>
            <p v-else class="muted">Kayıtlı ücretsiz izin yok.</p>
            <form class="leave-form" @submit.prevent="submitLeave">
              <div class="two">
                <label>Başlangıç<input v-model="leaveForm.baslangic_tarihi" type="date" required /></label>
                <label>Bitiş<input v-model="leaveForm.bitis_tarihi" type="date" required /></label>
              </div>
              <label>Açıklama<input v-model="leaveForm.aciklama" type="text" maxlength="255" placeholder="İsteğe bağlı" /></label>
              <button type="submit" class="secondary" :disabled="actionBusy">İzin ekle</button>
            </form>
          </section>

          <p v-if="actionError" class="notice notice--inline" role="alert">{{ actionError }}</p>

          <div class="actions">
            <button type="button" class="btn btn--dark" @click="openForm(detail)"><Pencil :size="16" /> Düzenle</button>
            <button type="button" class="btn btn--danger" :disabled="actionBusy" @click="confirmDelete = true"><Trash2 :size="16" /> Sil</button>
          </div>

          <ConfirmDialog v-if="confirmDelete" message="Bu personeli silmek istediğinize emin misiniz? Bu işlem geri alınamaz." confirm-text="Evet, sil" :busy="actionBusy" @confirm="removePerson" @cancel="confirmDelete = false" />

          <a v-if="detail.telefon" class="call" :href="`tel:${detail.telefon}`"><Phone :size="17" /> {{ detail.telefon }}</a>
        </section>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.page { min-height: 100svh; }
.people { display: grid; gap: 14px; padding: 14px 16px calc(112px + var(--ds-safe-bottom)); max-width: 520px; margin: 0 auto; }
.search { position: sticky; top: calc(10px + var(--ds-safe-top, 0px)); z-index: 40; box-shadow: 0 0 0 10px var(--ds-canvas), var(--ds-shadow-pill); display: flex; align-items: center; gap: 10px; min-height: 48px; padding: 0 16px; border-radius: var(--ds-radius-pill); background: var(--ds-surface); color: var(--ds-muted); }
.search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font-size: 16px; color: var(--ds-ink); }
.search:focus-within { outline: 2px solid var(--ds-focus); }
.chips { display: flex; gap: 8px; overflow-x: auto; margin: 0 -16px; padding: 2px 16px; scrollbar-width: none; }
.chips::-webkit-scrollbar { display: none; }
.chip { flex: 0 0 auto; min-height: 40px; padding: 0 14px; border: 0; border-radius: 999px; background: var(--ds-surface); box-shadow: var(--ds-shadow-pill); font-size: 13px; font-weight: 700; color: var(--ds-ink); cursor: pointer; white-space: nowrap; }
.chip b { margin-left: 4px; color: var(--ds-muted); font-weight: 700; }
.chip.is-active { background: var(--ds-surface-dark); color: #fff; }
.chip.is-active b { color: var(--ds-muted-light); }
.count { margin: 0 4px; color: var(--ds-muted); font-size: 12px; font-weight: 700; }
.cards { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; }
.person { display: flex; align-items: center; gap: 12px; width: 100%; min-height: 68px; padding: 10px 12px; border: 0; border-radius: var(--ds-radius-tile); background: var(--ds-surface); box-shadow: var(--ds-shadow-tile); text-align: left; cursor: pointer; color: var(--ds-ink); }
.person:active { transform: scale(.985); }
.person__text { flex: 1; min-width: 0; display: grid; gap: 3px; }
.person__text strong { font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.person__text small { color: var(--ds-muted); font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.avatar { display: grid; place-items: center; flex-shrink: 0; width: 42px; height: 42px; border-radius: 50%; background: var(--ds-surface-dark); color: #fff; font-size: 14px; font-weight: 800; }
.avatar--lg { width: 56px; height: 56px; font-size: 18px; }
.badge { flex-shrink: 0; max-width: 112px; padding: 4px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; text-align: center; line-height: 1.2; }
.tone-emerald { background: #d1fae5; color: #047857; } .tone-blue { background: #e0f2fe; color: #0369a1; } .tone-amber { background: #fef3c7; color: #b45309; } .tone-gold { background: #dbeafe; color: #1e40af; } .tone-gray { background: #e2e8f0; color: #475569; }
.more { min-height: 48px; border: 0; border-radius: 999px; background: var(--ds-surface); box-shadow: var(--ds-shadow-pill); font-weight: 700; font-size: 14px; cursor: pointer; }
.more:disabled { opacity: .6; }
.empty { display: grid; justify-items: center; gap: 6px; padding: 40px 20px; border-radius: var(--ds-radius-card); background: var(--ds-surface); color: var(--ds-muted); text-align: center; }
.empty p { margin: 0; font-size: 14px; }
.notice { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.notice span { flex: 1; }
.notice button { display: inline-flex; align-items: center; gap: 5px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 999px; background: var(--ds-surface); color: var(--ds-ink); font-weight: 700; cursor: pointer; }
.notice--inline { margin: 12px 0; }
.skeletons { display: grid; gap: 8px; }
.skeletons i { height: 68px; border-radius: var(--ds-radius-tile); background: linear-gradient(90deg, #dde3ea 25%, #eef1f5 50%, #dde3ea 75%); background-size: 200% 100%; animation: shimmer 1.3s infinite; }
@keyframes shimmer { to { background-position: -200% 0; } }

.layer { position: fixed; inset: 0; z-index: 80; display: flex; align-items: flex-end; justify-content: center; background: rgba(15, 18, 24, .5); }
.sheet { width: min(100%, 520px); max-height: 88svh; overflow-y: auto; padding: 18px 16px calc(18px + var(--ds-safe-bottom)); border-radius: 28px 28px 0 0; background: var(--ds-surface); box-shadow: 0 -16px 40px rgba(0, 0, 0, .25); }
.sheet__head { display: flex; align-items: center; gap: 12px; }
.sheet__title { flex: 1; min-width: 0; display: grid; gap: 6px; justify-items: start; }
.sheet__title h2 { margin: 0; font-size: 19px; line-height: 1.2; overflow-wrap: anywhere; }
.close { display: grid; place-items: center; flex-shrink: 0; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); cursor: pointer; }
.facts { display: grid; gap: 2px; margin: 16px 0; padding: 6px 14px; border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); }
.facts div { display: flex; justify-content: space-between; gap: 14px; padding: 11px 0; border-top: 1px solid var(--ds-border-subtle); font-size: 14px; }
.facts div:first-child { border-top: 0; }
dt { color: var(--ds-muted); font-weight: 700; }
dd { margin: 0; text-align: right; font-weight: 600; overflow-wrap: anywhere; }
.call { display: flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; border-radius: 999px; background: var(--ds-primary); color: #fff; font-weight: 700; font-size: 14px; text-decoration: none; }
.sheet-enter-active, .sheet-leave-active { transition: opacity .2s ease; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .28s cubic-bezier(.16, 1, .3, 1); }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
@media (prefers-reduced-motion: reduce) { .skeletons i { animation: none; } .sheet-enter-active, .sheet-leave-active, .sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: none; } }

.fab { position: fixed; z-index: 60; right: max(16px, calc(50% - 244px)); bottom: calc(108px + var(--ds-safe-bottom)); display: inline-flex; align-items: center; gap: 6px; min-height: 52px; padding: 0 20px 0 16px; border: 0; border-radius: 999px; background: #1d4ed8; color: #fff; font-weight: 800; font-size: 15px; box-shadow: 0 12px 24px -6px rgba(29, 78, 216, .5); cursor: pointer; }
.fab:active { transform: scale(.95); }
.scan-fab { position: fixed; z-index: 60; right: max(16px, calc(50% - 244px)); bottom: calc(172px + var(--ds-safe-bottom)); display: inline-flex; align-items: center; gap: 6px; min-height: 46px; padding: 0 16px 0 13px; border: 0; border-radius: 999px; background: var(--ds-surface-dark); color: #fff; font-weight: 800; font-size: 14px; box-shadow: var(--ds-shadow-float); cursor: pointer; }
.scan-fab:active { transform: scale(.95); }
.scan-fab:disabled { opacity: .6; }
.sr-file { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
.scan-layer { position: fixed; inset: 0; z-index: 150; display: grid; place-items: center; background: rgba(15, 18, 24, .55); }
.scan-card { display: grid; justify-items: center; gap: 10px; padding: 24px 28px; border-radius: 24px; background: var(--ds-surface); text-align: center; max-width: 280px; }
.scan-card small { color: var(--ds-muted); font-size: 12px; }
.spinner { width: 30px; height: 30px; border: 3px solid var(--ds-surface-subtle); border-top-color: var(--ds-primary); border-radius: 50%; animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.to-top { position: fixed; z-index: 60; right: max(16px, calc(50% - 244px)); bottom: calc(232px + var(--ds-safe-bottom)); display: grid; place-items: center; width: 52px; height: 52px; border: 0; border-radius: 50%; background: var(--ds-surface-dark); color: #fff; box-shadow: var(--ds-shadow-float); cursor: pointer; }
.to-top:active { transform: scale(.92); }
.pop-enter-active, .pop-leave-active { transition: opacity .2s ease, transform .25s cubic-bezier(.16, 1, .3, 1); }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(14px) scale(.8); }
.island { position: fixed; z-index: 200; left: 50%; top: calc(10px + var(--ds-safe-top, 0px)); transform: translateX(-50%); display: inline-flex; align-items: center; gap: 8px; max-width: calc(100% - 32px); padding: 12px 22px; border-radius: 999px; background: #000; color: #fff; font-size: 14px; font-weight: 700; white-space: nowrap; box-shadow: 0 10px 30px rgba(0, 0, 0, .35); }
.island svg { flex-shrink: 0; color: #34d399; }
.island span { overflow: hidden; text-overflow: ellipsis; }
.island-enter-active { animation: island-in .55s cubic-bezier(.34, 1.4, .5, 1) both; }
.island-leave-active { animation: island-out .35s cubic-bezier(.4, 0, 1, 1) both; }
@keyframes island-in { from { opacity: 0; transform: translateX(-50%) translateY(-24px) scale(.3, .5); } to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); } }
@keyframes island-out { to { opacity: 0; transform: translateX(-50%) translateY(-18px) scale(.4, .6); } }
@media (prefers-reduced-motion: reduce) { .island-enter-active, .island-leave-active, .pop-enter-active, .pop-leave-active { animation: none; transition: none; } }
.row { display: flex; align-items: center; gap: 4px; min-height: 68px; padding-right: 10px; border-radius: var(--ds-radius-tile); background: var(--ds-surface); box-shadow: var(--ds-shadow-tile); }
.row .person { flex: 1; min-width: 0; background: transparent; box-shadow: none; border-radius: var(--ds-radius-tile); }
.info-btn { display: grid; place-items: center; flex-shrink: 0; width: 40px; height: 40px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); color: var(--ds-ink); cursor: pointer; }
.info-btn:active { transform: scale(.92); }

.leaves { margin: 4px 0 14px; display: grid; gap: 8px; }
.leaves h3 { display: flex; align-items: center; gap: 6px; margin: 0; font-size: 14px; }
.leaves ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 6px; }
.leaves li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 12px; border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); }
.leaves li span { display: grid; gap: 2px; min-width: 0; font-size: 13px; }
.leaves li small, .muted { color: var(--ds-muted); font-size: 12px; margin: 0; }
.icon-btn { display: grid; place-items: center; flex-shrink: 0; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface); color: var(--ds-danger-ink); cursor: pointer; }
.leave-form { display: grid; gap: 8px; padding: 12px; border-radius: var(--ds-radius-sm); border: 1px dashed var(--ds-border-subtle); }
.leave-form label { display: grid; gap: 4px; color: var(--ds-muted); font-size: 11px; font-weight: 700; min-width: 0; }
.leave-form input { width: 100%; min-height: 44px; padding: 0 10px; border: 1px solid var(--ds-border-subtle); border-radius: 12px; background: var(--ds-surface-subtle); font: 600 16px var(--ds-font-main); color: var(--ds-ink); }
.two { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.secondary { min-height: 44px; border: 0; border-radius: 999px; background: var(--ds-surface-subtle); font-weight: 700; font-size: 13px; cursor: pointer; }
.actions { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
.btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 48px; border: 0; border-radius: 999px; font-weight: 700; font-size: 14px; cursor: pointer; }
.btn--dark { background: var(--ds-surface-dark); color: #fff; }
.btn--danger { background: var(--ds-danger-bg); color: var(--ds-danger-ink); }
.btn--danger-solid { background: var(--ds-danger); color: #fff; }
.btn--light { background: var(--ds-surface-subtle); }
.btn:disabled { opacity: .6; }
</style>

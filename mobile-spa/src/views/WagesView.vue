<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  AlertCircle,
  ArrowUp,
  ArrowUpDown,
  Calendar,
  Check,
  ChevronDown,
  CircleCheck,
  Coins,
  Copy,
  Pencil,
  Percent,
  Plus,
  RefreshCw,
  Search,
  SlidersHorizontal,
  Trash2,
  UsersRound,
  X,
} from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import WageForm from '../components/WageForm.vue'
import WagePeriodSheets from '../components/WagePeriodSheets.vue'
import { auth } from '../services/auth.js'
import {
  SORT_OPTIONS,
  deleteWage,
  formatCompactCurrency,
  formatCurrency,
  getWage,
  listWages,
  parseTurkishAmount,
} from '../services/wages.js'

const router = useRouter()

const items = ref([])
const total = ref(0)
const page = ref(1)
const hasMore = ref(false)
const loading = ref(true)
const loadingMore = ref(false)
const error = ref('')

const activePeriod = ref('')
const defaultPeriod = ref(null)
const periods = ref([])
const summary = ref({
  total: 0,
  min_ucret: null,
  max_ucret: null,
  avg_ucret: null,
  distinct_unvan: 0,
  ogrenim_counts: [],
  kidem_options: [],
})
const formOptions = ref({ ogrenimler: [], kidemler: [] })

const query = ref('')
const selectedOgrenim = ref('')
const selectedKidem = ref('')
const minUcret = ref('')
const maxUcret = ref('')
const sort = ref('unvan_asc')

const filterOpen = ref(false)
const filterSheet = ref(null)
const filterDraft = reactive({ kidem: '', min: '', max: '' })

const sortOpen = ref(false)
const sortSheet = ref(null)

const periodSheetMode = ref(null)

const detail = ref(null)
const detailSheet = ref(null)
const detailLoading = ref(false)
const detailError = ref('')
const confirmDelete = ref(false)
const actionBusy = ref(false)
const actionError = ref('')

const formOpen = ref(false)
const formWage = ref(null)
const formPrefill = ref(null)

const toast = ref('')
const showTop = ref(false)

let debounce = null
let requestSeq = 0
let toastTimer = null
let previousOverflow = ''
let returnFocus = null

const canManage = computed(() => ['admin', 'superadmin'].includes(auth.state.user?.role))

const anySheetOpen = computed(() =>
  Boolean(detail.value || formOpen.value || periodSheetMode.value || sortOpen.value || filterOpen.value)
)

const isDefaultPeriod = computed(() => Boolean(activePeriod.value && activePeriod.value === defaultPeriod.value))

const hasAdvancedFilters = computed(() =>
  Boolean(selectedKidem.value || minUcret.value !== '' || maxUcret.value !== '')
)

const hasAnyFilter = computed(() =>
  Boolean(query.value.trim() || selectedOgrenim.value || hasAdvancedFilters.value)
)

const ogrenimChips = computed(() => {
  const counts = summary.value.ogrenim_counts || []
  return [
    { key: '', label: 'Tümü', count: summary.value.total || 0 },
    ...counts.map((c) => ({ key: c.ogrenim, label: c.ogrenim, count: c.total })),
  ]
})

const filterKidemChoices = computed(() => {
  const list = summary.value.kidem_options?.length ? summary.value.kidem_options : formOptions.value.kidemler
  return list || []
})

const activeFilterBadges = computed(() => {
  const badges = []
  if (selectedKidem.value) {
    badges.push({ key: 'kidem', label: `Kıdem: ${selectedKidem.value}` })
  }
  if (minUcret.value !== '') {
    badges.push({ key: 'min', label: `En az ${formatCurrency(minUcret.value)}` })
  }
  if (maxUcret.value !== '') {
    badges.push({ key: 'max', label: `En fazla ${formatCurrency(maxUcret.value)}` })
  }
  if (sort.value !== 'unvan_asc') {
    badges.push({ key: 'sort', label: `Sıra: ${SORT_OPTIONS[sort.value]}` })
  }
  return badges
})

const detailRows = computed(() => {
  const w = detail.value
  if (!w) return []
  return [
    { label: 'Dönem', value: w.donem || '—' },
    { label: 'Öğrenim durumu', value: w.ogrenim || '—' },
    { label: 'Kıdem yılı', value: w.kidem_yili || '—' },
    {
      label: 'Bağlı personel',
      value: w.personel_count > 0 ? `${w.personel_count} aktif personel` : 'Kullanılmıyor',
    },
    { label: 'Son güncelleme', value: formatDate(w.updated_at || w.created_at) },
  ]
})

function ogrenimTone(ogrenim) {
  const norm = String(ogrenim || '').toLocaleLowerCase('tr-TR').replace(/\s+/g, '')
  if (norm === 'lise') return 'tone-gray'
  if (norm === 'önlisans') return 'tone-blue'
  if (norm === 'lisans') return 'tone-emerald'
  if (norm === 'yükseklisans') return 'tone-amber'
  if (norm === 'doktora') return 'tone-purple'
  return 'tone-gray'
}

function formatDate(value) {
  if (!value) return '—'
  const d = new Date(`${String(value).slice(0, 10)}T00:00:00`)
  return Number.isNaN(d.getTime())
    ? '—'
    : new Intl.DateTimeFormat('tr-TR', { day: 'numeric', month: 'long', year: 'numeric' }).format(d)
}

function showToast(message) {
  toast.value = message
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    toast.value = ''
  }, 3200)
}

async function handleExpired() {
  detail.value = null
  formOpen.value = false
  periodSheetMode.value = null
  filterOpen.value = false
  sortOpen.value = false
  auth.expire()
  await router.replace({ name: 'login', query: { expired: '1' } })
}

async function handleError(reason) {
  if (reason?.status === 401) {
    await handleExpired()
    return true
  }
  error.value = reason?.message || 'Ücret verileri alınamadı.'
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
    const data = await listWages({
      donem: activePeriod.value,
      q: query.value.trim(),
      ogrenim: selectedOgrenim.value,
      kidem: selectedKidem.value,
      min_ucret: minUcret.value,
      max_ucret: maxUcret.value,
      sort: sort.value,
      page: reset ? 1 : page.value + 1,
    })
    if (seq !== requestSeq) return

    activePeriod.value = data.period
    defaultPeriod.value = data.default_period
    periods.value = data.periods || []
    summary.value = data.summary || summary.value
    formOptions.value = data.form_options || formOptions.value
    items.value = reset ? data.items : [...items.value, ...data.items]
    total.value = data.total
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

watch(anySheetOpen, (open, wasOpen) => {
  if (open && !wasOpen) {
    returnFocus = document.activeElement
    previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
  } else if (!open && wasOpen) {
    document.body.style.overflow = previousOverflow
    if (returnFocus?.isConnected) returnFocus.focus()
  }
})

watch(query, () => {
  clearTimeout(debounce)
  debounce = setTimeout(() => load(true), 300)
})

function setOgrenim(key) {
  if (selectedOgrenim.value === key) return
  selectedOgrenim.value = key
  load(true)
}

function clearBadge(key) {
  if (key === 'kidem') selectedKidem.value = ''
  else if (key === 'min') minUcret.value = ''
  else if (key === 'max') maxUcret.value = ''
  else if (key === 'sort') sort.value = 'unvan_asc'
  load(true)
}

function clearAllFilters() {
  query.value = ''
  selectedOgrenim.value = ''
  selectedKidem.value = ''
  minUcret.value = ''
  maxUcret.value = ''
  load(true)
}

async function openFilterSheet() {
  filterDraft.kidem = selectedKidem.value
  filterDraft.min = minUcret.value !== '' ? String(minUcret.value) : ''
  filterDraft.max = maxUcret.value !== '' ? String(maxUcret.value) : ''
  filterOpen.value = true
  await nextTick()
  filterSheet.value?.querySelector('button, select, input')?.focus()
}

function applyFilterDraft() {
  selectedKidem.value = filterDraft.kidem
  const parsedMin = filterDraft.min.trim() !== '' ? parseTurkishAmount(filterDraft.min) : null
  const parsedMax = filterDraft.max.trim() !== '' ? parseTurkishAmount(filterDraft.max) : null
  minUcret.value = parsedMin !== null && parsedMin >= 0 ? parsedMin : ''
  maxUcret.value = parsedMax !== null && parsedMax >= 0 ? parsedMax : ''
  filterOpen.value = false
  load(true)
}

function resetFilterDraft() {
  filterDraft.kidem = ''
  filterDraft.min = ''
  filterDraft.max = ''
  selectedKidem.value = ''
  minUcret.value = ''
  maxUcret.value = ''
  filterOpen.value = false
  load(true)
}

async function openSortSheet() {
  sortOpen.value = true
  await nextTick()
  sortSheet.value?.querySelector('button')?.focus()
}

function applySort(key) {
  sortOpen.value = false
  if (sort.value === key) return
  sort.value = key
  load(true)
}

function openPeriodSheet(mode = 'periods') {
  periodSheetMode.value = mode
}

function handlePeriodSelect(donem) {
  periodSheetMode.value = null
  if (activePeriod.value === donem) return
  activePeriod.value = donem
  selectedOgrenim.value = ''
  selectedKidem.value = ''
  load(true)
}

function handleDefaultPeriodSet({ period, message }) {
  defaultPeriod.value = period
  showToast(message)
}

async function handlePeriodCopied({ period, message }) {
  periodSheetMode.value = null
  activePeriod.value = period
  selectedOgrenim.value = ''
  selectedKidem.value = ''
  showToast(message)
  await load(true)
}

async function handlePeriodDeleted({ nextPeriod, message }) {
  periodSheetMode.value = null
  activePeriod.value = nextPeriod || ''
  selectedOgrenim.value = ''
  selectedKidem.value = ''
  showToast(message)
  await load(true)
}

async function openDetail(wage) {
  detail.value = { ...wage }
  confirmDelete.value = false
  actionError.value = ''
  detailError.value = ''
  detailLoading.value = true
  await nextTick()
  detailSheet.value?.querySelector('button')?.focus()

  try {
    const data = await getWage(wage.id)
    if (detail.value && detail.value.id === wage.id) {
      detail.value = data.wage
    }
  } catch (reason) {
    if (reason?.status === 401) {
      await handleExpired()
      return
    }
    detailError.value = reason?.message || 'Detay alınamadı.'
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  detail.value = null
  confirmDelete.value = false
  actionError.value = ''
}

function openCreateForm() {
  formWage.value = null
  formPrefill.value = null
  formOpen.value = true
}

function openEditForm(wage) {
  formWage.value = { ...wage }
  formPrefill.value = null
  formOpen.value = true
}

function openCopyForm(wage) {
  closeDetail()
  formWage.value = null
  formPrefill.value = {
    unvan: wage.unvan,
    ogrenim: wage.ogrenim,
    kidem_yili: wage.kidem_yili,
    ucret: wage.ucret,
    donem: wage.donem || activePeriod.value,
  }
  formOpen.value = true
}

async function quickEdit(wage) {
  if (actionBusy.value) return
  actionBusy.value = true
  try {
    const data = await getWage(wage.id)
    openEditForm(data.wage)
  } catch (reason) {
    if (reason?.status === 401) await handleExpired()
    else showToast(reason?.message || 'Ücret tanımı açılamadı.')
  } finally {
    actionBusy.value = false
  }
}

async function handleFormSaved({ period, isEdit, isCopy }) {
  const editedId = formWage.value?.id
  formOpen.value = false
  if (period && period !== activePeriod.value) {
    activePeriod.value = period
    selectedOgrenim.value = ''
    selectedKidem.value = ''
  }
  showToast(isEdit ? 'Ücret tanımı güncellendi.' : isCopy ? 'Kopyalanarak yeni tanım eklendi.' : 'Ücret tanımı eklendi.')
  await load(true)

  if (isEdit && detail.value && detail.value.id === editedId) {
    try {
      const data = await getWage(editedId)
      detail.value = data.wage
    } catch {
      closeDetail()
    }
  }
}

function requestDeleteInDetail() {
  actionError.value = ''
  if (detail.value?.personel_count > 0) {
    actionError.value = `Bu ücret tanımı şu anda ${detail.value.personel_count} personel tarafından kullanıldığı için silinemez.`
    return
  }
  confirmDelete.value = true
}

async function removeWage() {
  if (!detail.value || actionBusy.value) return
  actionBusy.value = true
  actionError.value = ''
  try {
    await deleteWage(detail.value.id)
    closeDetail()
    showToast('Ücret tanımı silindi.')
    await load(true)
  } catch (reason) {
    if (reason?.status === 401) {
      await handleExpired()
      return
    }
    actionError.value = reason?.message || 'Silme işlemi tamamlanamadı.'
    confirmDelete.value = false
  } finally {
    actionBusy.value = false
  }
}

function handleDetailKeydown(event) {
  if (event.key === 'Escape' && !formOpen.value) closeDetail()
}

function onScroll() {
  showTop.value = window.scrollY > 400
}

function scrollTop() {
  window.scrollTo({
    top: 0,
    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
  })
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  load(true)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  clearTimeout(debounce)
  clearTimeout(toastTimer)
  if (anySheetOpen.value) document.body.style.overflow = previousOverflow
})
</script>

<template>
  <div class="page">
    <TopBar eyebrow="ÜCRET YÖNETİMİ" title="Ücretler" :back="true" />

    <main class="wages">
      <!-- Dönem ve Özet Bento Kartı -->
      <section class="card card--dark period-card" aria-label="Aktif dönem özeti">
        <div class="period-card__top">
          <div class="period-card__lead">
            <div class="period-card__kicker">
              <span class="eyebrow eyebrow--light">AKTİF DÖNEM</span>
              <span v-if="isDefaultPeriod" class="default-pill">Varsayılan</span>
            </div>
            <button
              type="button"
              class="period-trigger"
              aria-label="Ücret dönemini değiştir"
              @click="openPeriodSheet('periods')"
            >
              <Calendar :size="17" />
              <span>{{ activePeriod || '2026-1' }}</span>
              <ChevronDown :size="16" />
            </button>
          </div>

          <button
            v-if="canManage"
            type="button"
            class="raise-btn"
            aria-label="Toplu zam ve dönem kopyala"
            @click="openPeriodSheet('copy')"
          >
            <Percent :size="15" />
            <span>Toplu Zam</span>
          </button>
        </div>

        <div class="kpi-strip">
          <div class="kpi-cell">
            <span>Tanım</span>
            <strong>{{ summary.total }}</strong>
          </div>
          <div class="kpi-cell">
            <span>En Düşük</span>
            <strong>{{ formatCompactCurrency(summary.min_ucret) }}</strong>
          </div>
          <div class="kpi-cell">
            <span>En Yüksek</span>
            <strong>{{ formatCompactCurrency(summary.max_ucret) }}</strong>
          </div>
        </div>
      </section>

      <!-- Arama + Sıralama & Filtre -->
      <div class="toolbar">
        <label class="search">
          <Search :size="18" />
          <input
            v-model="query"
            type="search"
            placeholder="Unvan, öğrenim veya kıdem ara…"
            aria-label="Ücret tanımı ara"
            autocomplete="off"
          />
        </label>
        <button
          type="button"
          class="tool-btn"
          :class="{ 'is-active': sort !== 'unvan_asc' }"
          aria-label="Sırala"
          @click="openSortSheet"
        >
          <ArrowUpDown :size="18" />
        </button>
        <button
          type="button"
          class="tool-btn"
          :class="{ 'is-active': hasAdvancedFilters }"
          aria-label="Filtrele"
          @click="openFilterSheet"
        >
          <SlidersHorizontal :size="18" />
        </button>
      </div>

      <!-- Öğrenim Chip Şeridi -->
      <div v-if="ogrenimChips.length > 1" class="chips" role="group" aria-label="Öğrenim filtresi">
        <button
          v-for="chip in ogrenimChips"
          :key="chip.key"
          type="button"
          class="chip"
          :class="{ 'is-active': selectedOgrenim === chip.key }"
          :aria-pressed="selectedOgrenim === chip.key"
          @click="setOgrenim(chip.key)"
        >
          {{ chip.label }} <b>{{ chip.count }}</b>
        </button>
      </div>

      <!-- Aktif İkincil Filtre Rozetleri -->
      <div v-if="activeFilterBadges.length" class="active-badges">
        <button
          v-for="b in activeFilterBadges"
          :key="b.key"
          type="button"
          class="active-badge"
          @click="clearBadge(b.key)"
        >
          <span>{{ b.label }}</span>
          <X :size="13" />
        </button>
      </div>

      <div v-if="error" class="notice" role="alert">
        <AlertCircle :size="18" />
        <span>{{ error }}</span>
        <button type="button" @click="load(true)"><RefreshCw :size="15" /> Yenile</button>
      </div>

      <div v-if="loading" class="skeletons" aria-hidden="true">
        <i v-for="n in 6" :key="n"></i>
      </div>

      <template v-else>
        <div v-if="items.length" class="list-meta">
          <span>{{ total }} ücret tanımı</span>
          <span>{{ SORT_OPTIONS[sort] }}</span>
        </div>

        <ul v-if="items.length" class="cards">
          <li v-for="wage in items" :key="wage.id" class="row">
            <button type="button" class="wage-item" @click="openDetail(wage)">
              <div class="wage-item__main">
                <strong>{{ wage.unvan }}</strong>
                <div class="wage-item__meta">
                  <span class="badge" :class="ogrenimTone(wage.ogrenim)">{{ wage.ogrenim }}</span>
                  <span class="kidem">{{ wage.kidem_yili }}</span>
                  <span v-if="wage.personel_count > 0" class="usage">
                    <UsersRound :size="11" /> {{ wage.personel_count }}
                  </span>
                </div>
              </div>
              <div class="wage-item__price">
                <span>Brüt</span>
                <strong>{{ formatCurrency(wage.ucret) }}</strong>
              </div>
            </button>
            <button
              type="button"
              class="info-btn"
              :aria-label="`${wage.unvan} düzenle`"
              :disabled="actionBusy"
              @click="quickEdit(wage)"
            >
              <Pencil :size="17" />
            </button>
          </li>
        </ul>

        <div v-else-if="!error" class="empty">
          <Coins :size="30" />
          <p>{{ hasAnyFilter ? 'Aramanıza veya filtrelere uyan ücret tanımı bulunamadı.' : 'Bu dönemde henüz ücret tanımı bulunmuyor.' }}</p>
          <button v-if="hasAnyFilter" type="button" class="empty__btn" @click="clearAllFilters">Filtreleri temizle</button>
          <button v-else type="button" class="empty__btn empty__btn--primary" @click="openCreateForm">Yeni ücret tanımı ekle</button>
        </div>

        <button v-if="hasMore" type="button" class="more" :disabled="loadingMore" @click="load(false)">
          {{ loadingMore ? 'Yükleniyor…' : 'Daha fazla göster' }}
        </button>
      </template>
    </main>

    <!-- Teleport: FABs & Dynamic Island Toast -->
    <Teleport to="body">
      <Transition name="pop">
        <button v-if="showTop && !anySheetOpen" type="button" class="to-top" aria-label="Yukarı çık" @click="scrollTop">
          <ArrowUp :size="20" />
        </button>
      </Transition>
      <button v-if="!anySheetOpen" type="button" class="fab" aria-label="Ücret tanımı ekle" @click="openCreateForm">
        <Plus :size="22" /> Ekle
      </button>
      <Transition name="island">
        <div v-if="toast" class="island" role="status" aria-live="polite">
          <CircleCheck :size="18" /><span>{{ toast }}</span>
        </div>
      </Transition>
    </Teleport>

    <!-- Ekle / Düzenle / Kopyala Form Sheet -->
    <WageForm
      v-if="formOpen"
      :wage="formWage"
      :prefill="formPrefill"
      :period="activePeriod || '2026-1'"
      :periods="periods"
      :ogrenim-options="formOptions.ogrenimler"
      :kidem-options="formOptions.kidemler"
      @close="formOpen = false"
      @saved="handleFormSaved"
      @expired="handleExpired"
    />

    <!-- Dönem Seçimi & Toplu Zam Sheet -->
    <WagePeriodSheets
      v-if="periodSheetMode"
      :mode="periodSheetMode"
      :active-period="activePeriod || '2026-1'"
      :default-period="defaultPeriod"
      :periods="periods"
      :can-manage="canManage"
      :sample-base-amount="summary.avg_ucret || 50000"
      @close="periodSheetMode = null"
      @select="handlePeriodSelect"
      @switch-mode="periodSheetMode = $event"
      @default-set="handleDefaultPeriodSet"
      @copied="handlePeriodCopied"
      @deleted="handlePeriodDeleted"
      @expired="handleExpired"
    />

    <!-- Ücret Detay Sheet -->
    <Transition name="sheet">
      <div v-if="detail" class="layer" @keydown="handleDetailKeydown" @click.self="closeDetail">
        <section ref="detailSheet" class="sheet" role="dialog" aria-modal="true" aria-label="Ücret tanımı detayı">
          <header class="sheet__head">
            <span class="detail-icon"><Coins :size="22" /></span>
            <div class="sheet__title">
              <h2>{{ detail.unvan }}</h2>
              <div class="sheet__badges">
                <span class="badge" :class="ogrenimTone(detail.ogrenim)">{{ detail.ogrenim }}</span>
                <span class="period-chip">{{ detail.donem }}</span>
              </div>
            </div>
            <button type="button" class="close" aria-label="Kapat" @click="closeDetail"><X :size="19" /></button>
          </header>

          <div class="salary-hero">
            <span>Aylık Brüt Ücret</span>
            <strong>{{ formatCurrency(detail.ucret) }}</strong>
          </div>

          <p v-if="detailError" class="notice notice--inline" role="alert">{{ detailError }}</p>
          <dl v-else class="facts" :aria-busy="detailLoading">
            <div v-for="row in detailRows" :key="row.label">
              <dt>{{ row.label }}</dt>
              <dd>{{ row.value }}</dd>
            </div>
          </dl>

          <p v-if="actionError" class="notice notice--inline" role="alert">{{ actionError }}</p>

          <div class="actions">
            <template v-if="!confirmDelete">
              <button type="button" class="btn btn--dark" @click="openEditForm(detail)">
                <Pencil :size="16" /> Düzenle
              </button>
              <button type="button" class="btn btn--light" @click="openCopyForm(detail)">
                <Copy :size="16" /> Kopyala
              </button>
              <button type="button" class="btn btn--danger" @click="requestDeleteInDetail">
                <Trash2 :size="16" /> Sil
              </button>
            </template>
            <template v-else>
              <p class="confirm">Bu ücret tanımı silinsin mi? İşlem geri alınamaz.</p>
              <button type="button" class="btn btn--light" :disabled="actionBusy" @click="confirmDelete = false">Vazgeç</button>
              <button type="button" class="btn btn--danger-solid" :disabled="actionBusy" @click="removeWage">
                {{ actionBusy ? 'Siliniyor…' : 'Evet, sil' }}
              </button>
            </template>
          </div>
        </section>
      </div>
    </Transition>

    <!-- Sıralama Sheet -->
    <Transition name="sheet">
      <div
        v-if="sortOpen"
        class="layer"
        @keydown.escape="sortOpen = false"
        @click.self="sortOpen = false"
      >
        <section ref="sortSheet" class="sheet" role="dialog" aria-modal="true" aria-label="Sıralama seçenekleri">
          <header class="sheet__head sheet__head--space">
            <div>
              <span class="eyebrow">SIRALA</span>
              <h2>Sıralama ölçütü</h2>
            </div>
            <button type="button" class="close" aria-label="Kapat" @click="sortOpen = false"><X :size="19" /></button>
          </header>

          <ul class="sort-list">
            <li v-for="(label, key) in SORT_OPTIONS" :key="key">
              <button
                type="button"
                class="sort-option"
                :class="{ 'is-selected': sort === key }"
                @click="applySort(key)"
              >
                <span>{{ label }}</span>
                <Check v-if="sort === key" :size="18" />
              </button>
            </li>
          </ul>
        </section>
      </div>
    </Transition>

    <!-- Gelişmiş Filtre Sheet -->
    <Transition name="sheet">
      <div
        v-if="filterOpen"
        class="layer"
        @keydown.escape="filterOpen = false"
        @click.self="filterOpen = false"
      >
        <form
          ref="filterSheet"
          class="sheet"
          role="dialog"
          aria-modal="true"
          aria-label="Gelişmiş filtreleme"
          @submit.prevent="applyFilterDraft"
        >
          <header class="sheet__head sheet__head--space">
            <div>
              <span class="eyebrow">FİLTRELE</span>
              <h2>Kıdem ve ücret aralığı</h2>
            </div>
            <button type="button" class="close" aria-label="Kapat" @click="filterOpen = false"><X :size="19" /></button>
          </header>

          <div class="filter-fields">
            <label>Kıdem yılı
              <select v-model="filterDraft.kidem">
                <option value="">Tümü</option>
                <option v-for="k in filterKidemChoices" :key="k" :value="k">{{ k }}</option>
              </select>
            </label>

            <div class="two">
              <label>Minimum ücret (₺)
                <input v-model="filterDraft.min" type="text" inputmode="decimal" placeholder="Örn: 45.000" autocomplete="off" />
              </label>
              <label>Maksimum ücret (₺)
                <input v-model="filterDraft.max" type="text" inputmode="decimal" placeholder="Örn: 75.000" autocomplete="off" />
              </label>
            </div>
          </div>

          <div class="actions">
            <button type="button" class="btn btn--light" @click="resetFilterDraft">Temizle</button>
            <button type="submit" class="btn btn--primary">Filtreyi uygula</button>
          </div>
        </form>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.page { min-height: 100svh; }
.wages { display: grid; gap: 14px; padding: 14px 16px calc(112px + var(--ds-safe-bottom)); max-width: 520px; margin: 0 auto; }

.card { background: var(--ds-surface); border-radius: var(--ds-radius-card); padding: 18px; box-shadow: var(--ds-shadow-bento); }
.card--dark { background: var(--ds-surface-dark); color: var(--ds-ink-light); box-shadow: var(--ds-shadow-bento-dark); }
.eyebrow { color: var(--ds-muted); font-size: 10px; font-weight: 800; letter-spacing: .12em; }
.eyebrow--light { color: var(--ds-muted-light); }

.period-card { display: grid; gap: 16px; }
.period-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.period-card__lead { display: grid; gap: 6px; min-width: 0; }
.period-card__kicker { display: flex; align-items: center; gap: 8px; }
.default-pill { padding: 2px 8px; border-radius: 999px; background: rgba(56, 189, 248, .18); color: #7dd3fc; font-size: 10px; font-weight: 800; }
.period-trigger { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 14px 0 12px; border: 1px solid var(--ds-border-dark); border-radius: 999px; background: rgba(255, 255, 255, .08); color: #fff; font-size: 16px; font-weight: 800; cursor: pointer; width: fit-content; max-width: 100%; }
.period-trigger span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.period-trigger:active { transform: scale(.97); }
.raise-btn { flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px; min-height: 40px; padding: 0 13px; border: 1px solid rgba(255, 255, 255, .14); border-radius: 999px; background: var(--ds-surface-dark-2); color: #fff; font-size: 12px; font-weight: 700; cursor: pointer; }
.raise-btn:active { transform: scale(.96); }

.kpi-strip { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; padding-top: 12px; border-top: 1px solid var(--ds-border-dark); }
.kpi-cell { display: grid; gap: 3px; min-width: 0; }
.kpi-cell span { color: var(--ds-muted-light); font-size: 11px; font-weight: 600; }
.kpi-cell strong { font: 700 17px/1.2 var(--ds-font-mono); color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.toolbar { position: sticky; top: 0; z-index: 40; display: flex; align-items: center; gap: 8px; padding: calc(8px + var(--ds-safe-top, 0px)) 0 8px; background: var(--ds-canvas); }
.search { flex: 1; min-width: 0; display: flex; align-items: center; gap: 10px; min-height: 48px; padding: 0 16px; border-radius: var(--ds-radius-pill); background: var(--ds-surface); box-shadow: var(--ds-shadow-pill); color: var(--ds-muted); }
.search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font-size: 16px; color: var(--ds-ink); }
.search:focus-within { outline: 2px solid var(--ds-focus); }
.tool-btn { flex-shrink: 0; display: grid; place-items: center; width: 48px; height: 48px; border: 0; border-radius: 50%; background: var(--ds-surface); color: var(--ds-ink); box-shadow: var(--ds-shadow-pill); cursor: pointer; }
.tool-btn.is-active { background: var(--ds-surface-dark); color: #fff; }
.tool-btn:active { transform: scale(.94); }

.chips { display: flex; gap: 8px; overflow-x: auto; margin: 0 -16px; padding: 2px 16px; scrollbar-width: none; }
.chips::-webkit-scrollbar { display: none; }
.chip { flex: 0 0 auto; min-height: 40px; padding: 0 14px; border: 0; border-radius: 999px; background: var(--ds-surface); box-shadow: var(--ds-shadow-pill); font-size: 13px; font-weight: 700; color: var(--ds-ink); cursor: pointer; white-space: nowrap; }
.chip b { margin-left: 4px; color: var(--ds-muted); font-weight: 700; }
.chip.is-active { background: var(--ds-surface-dark); color: #fff; }
.chip.is-active b { color: var(--ds-muted-light); }

.active-badges { display: flex; flex-wrap: wrap; gap: 6px; }
.active-badge { display: inline-flex; align-items: center; gap: 6px; min-height: 32px; padding: 0 11px; border: 0; border-radius: 999px; background: var(--ds-primary-soft); color: var(--ds-primary-ink); font-size: 12px; font-weight: 700; cursor: pointer; }

.list-meta { display: flex; align-items: center; justify-content: space-between; margin: 0 4px; color: var(--ds-muted); font-size: 12px; font-weight: 700; }
.cards { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; }
.row { display: flex; align-items: center; gap: 4px; min-height: 74px; padding-right: 10px; border-radius: var(--ds-radius-tile); background: var(--ds-surface); box-shadow: var(--ds-shadow-tile); }
.wage-item { flex: 1; min-width: 0; display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px; border: 0; border-radius: var(--ds-radius-tile); background: transparent; text-align: left; cursor: pointer; color: var(--ds-ink); }
.wage-item:active { transform: scale(.985); }
.wage-item__main { flex: 1; min-width: 0; display: grid; gap: 6px; }
.wage-item__main strong { font-size: 14px; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.wage-item__meta { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
.badge { flex-shrink: 0; padding: 3px 8px; border-radius: 999px; font-size: 10px; font-weight: 800; line-height: 1.2; }
.tone-emerald { background: #d1fae5; color: #047857; }
.tone-blue { background: #e0f2fe; color: #0369a1; }
.tone-amber { background: #fef3c7; color: #b45309; }
.tone-purple { background: #ede9fe; color: #6d28d9; }
.tone-gray { background: #e2e8f0; color: #475569; }
.kidem { color: var(--ds-muted); font-size: 12px; font-weight: 600; }
.usage { display: inline-flex; align-items: center; gap: 3px; color: var(--ds-primary-ink); font-size: 11px; font-weight: 700; }
.wage-item__price { flex-shrink: 0; text-align: right; display: grid; gap: 2px; }
.wage-item__price span { color: var(--ds-muted); font-size: 10px; font-weight: 700; text-transform: uppercase; }
.wage-item__price strong { font: 700 15px/1.2 var(--ds-font-mono); color: var(--ds-ink); }

.info-btn { display: grid; place-items: center; flex-shrink: 0; width: 40px; height: 40px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); color: var(--ds-ink); cursor: pointer; }
.info-btn:active { transform: scale(.92); }

.more { min-height: 48px; border: 0; border-radius: 999px; background: var(--ds-surface); box-shadow: var(--ds-shadow-pill); font-weight: 700; font-size: 14px; cursor: pointer; }
.more:disabled { opacity: .6; }

.empty { display: grid; justify-items: center; gap: 10px; padding: 40px 20px; border-radius: var(--ds-radius-card); background: var(--ds-surface); color: var(--ds-muted); text-align: center; }
.empty p { margin: 0; font-size: 14px; }
.empty__btn { min-height: 44px; padding: 0 18px; border: 0; border-radius: 999px; background: var(--ds-surface-subtle); color: var(--ds-ink); font-size: 13px; font-weight: 700; cursor: pointer; }
.empty__btn--primary { background: var(--ds-primary); color: #fff; }

.notice { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.notice span { flex: 1; }
.notice button { display: inline-flex; align-items: center; gap: 5px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 999px; background: var(--ds-surface); color: var(--ds-ink); font-weight: 700; cursor: pointer; }
.notice--inline { margin: 0; }

.skeletons { display: grid; gap: 8px; }
.skeletons i { height: 74px; border-radius: var(--ds-radius-tile); background: linear-gradient(90deg, #dde3ea 25%, #eef1f5 50%, #dde3ea 75%); background-size: 200% 100%; animation: shimmer 1.3s infinite; }
@keyframes shimmer { to { background-position: -200% 0; } }

.layer { position: fixed; inset: 0; z-index: 80; display: flex; align-items: flex-end; justify-content: center; background: rgba(15, 18, 24, .5); }
.sheet { width: min(100%, 520px); max-height: 88svh; overflow-y: auto; padding: 18px 16px calc(18px + var(--ds-safe-bottom)); border-radius: 28px 28px 0 0; background: var(--ds-surface); box-shadow: 0 -16px 40px rgba(0, 0, 0, .25); display: grid; gap: 14px; }
.sheet__head { display: flex; align-items: center; gap: 12px; }
.sheet__head--space { justify-content: space-between; align-items: flex-start; }
.sheet__head--space h2 { margin: 2px 0 0; font-size: 21px; font-weight: 800; }
.detail-icon { display: grid; place-items: center; flex-shrink: 0; width: 52px; height: 52px; border-radius: 18px; background: var(--ds-surface-dark); color: #fff; }
.sheet__title { flex: 1; min-width: 0; display: grid; gap: 6px; justify-items: start; }
.sheet__title h2 { margin: 0; font-size: 19px; line-height: 1.2; overflow-wrap: anywhere; }
.sheet__badges { display: flex; flex-wrap: wrap; gap: 6px; }
.period-chip { padding: 3px 8px; border-radius: 999px; background: var(--ds-surface-subtle); color: var(--ds-muted); font-size: 10px; font-weight: 800; }
.close { display: grid; place-items: center; flex-shrink: 0; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); cursor: pointer; }

.salary-hero { display: grid; gap: 4px; padding: 16px; border-radius: var(--ds-radius-tile); background: var(--ds-surface-dark); color: #fff; }
.salary-hero span { color: var(--ds-muted-light); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
.salary-hero strong { font: 700 28px/1.1 var(--ds-font-mono); letter-spacing: -.02em; }

.facts { display: grid; gap: 2px; margin: 0; padding: 6px 14px; border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); }
.facts div { display: flex; justify-content: space-between; gap: 14px; padding: 11px 0; border-top: 1px solid var(--ds-border-subtle); font-size: 14px; }
.facts div:first-child { border-top: 0; }
dt { color: var(--ds-muted); font-weight: 700; }
dd { margin: 0; text-align: right; font-weight: 600; overflow-wrap: anywhere; }

.sort-list { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; }
.sort-option { width: 100%; min-height: 52px; padding: 0 16px; display: flex; align-items: center; justify-content: space-between; border: 1.5px solid transparent; border-radius: var(--ds-radius-tile); background: var(--ds-surface-subtle); color: var(--ds-ink); font-size: 14px; font-weight: 700; cursor: pointer; text-align: left; }
.sort-option.is-selected { background: var(--ds-primary-soft); color: var(--ds-primary-ink); border-color: rgba(29, 78, 216, .25); }

.filter-fields { display: grid; gap: 12px; }
.two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.filter-fields label { display: grid; gap: 6px; color: var(--ds-muted); font-size: 12px; font-weight: 700; min-width: 0; }
.filter-fields input,
.filter-fields select { width: 100%; min-height: 48px; padding: 0 14px; border: 1px solid var(--ds-border-subtle); border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); color: var(--ds-ink); font: 600 16px var(--ds-font-main); }
.filter-fields input:focus-visible,
.filter-fields select:focus-visible { outline: 2px solid var(--ds-focus); outline-offset: 1px; }

.actions { display: flex; flex-wrap: wrap; gap: 8px; }
.btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 48px; padding: 0 12px; border: 0; border-radius: 999px; font-weight: 700; font-size: 14px; cursor: pointer; }
.btn--primary { background: var(--ds-primary); color: #fff; }
.btn--dark { background: var(--ds-surface-dark); color: #fff; }
.btn--danger { background: var(--ds-danger-bg); color: var(--ds-danger-ink); }
.btn--danger-solid { background: var(--ds-danger); color: #fff; }
.btn--light { background: var(--ds-surface-subtle); color: var(--ds-ink); }
.btn:disabled { opacity: .6; }
.confirm { flex-basis: 100%; margin: 0; font-size: 13px; font-weight: 700; color: var(--ds-danger-ink); }

.fab { position: fixed; z-index: 60; right: max(16px, calc(50% - 244px)); bottom: calc(108px + var(--ds-safe-bottom)); display: inline-flex; align-items: center; gap: 6px; min-height: 52px; padding: 0 20px 0 16px; border: 0; border-radius: 999px; background: #1d4ed8; color: #fff; font-weight: 800; font-size: 15px; box-shadow: 0 12px 24px -6px rgba(29, 78, 216, .5); cursor: pointer; }
.fab:active { transform: scale(.95); }
.to-top { position: fixed; z-index: 60; right: max(16px, calc(50% - 244px)); bottom: calc(172px + var(--ds-safe-bottom)); display: grid; place-items: center; width: 52px; height: 52px; border: 0; border-radius: 50%; background: var(--ds-surface-dark); color: #fff; box-shadow: var(--ds-shadow-float); cursor: pointer; }
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

.sheet-enter-active, .sheet-leave-active { transition: opacity .2s ease; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .28s cubic-bezier(.16, 1, .3, 1); }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
@media (prefers-reduced-motion: reduce) {
  .skeletons i { animation: none; }
  .sheet-enter-active, .sheet-leave-active, .sheet-enter-active .sheet, .sheet-leave-active .sheet, .island-enter-active, .island-leave-active, .pop-enter-active, .pop-leave-active { animation: none; transition: none; }
}
</style>

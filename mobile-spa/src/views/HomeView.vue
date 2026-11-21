<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { AlertCircle, ArrowUpRight, CalendarClock, CheckCircle2, Coins, ListTodo, MessageCircle, RefreshCw, UserPlus, UsersRound } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import { auth } from '../services/auth.js'
import { getDashboard, logKadroMessage, whatsappUrl } from '../services/dashboard.js'
import { MODULES } from '../services/modules.js'

const router = useRouter()
const loading = ref(true)
const error = ref('')
const data = ref(null)

const today = new Intl.DateTimeFormat('tr-TR', { day: 'numeric', month: 'long', weekday: 'long', timeZone: 'Europe/Istanbul' }).format(new Date())

const personnel = computed(() => data.value?.personnel || null)
const tasks = computed(() => data.value?.tasks || null)
const recent = computed(() => data.value?.recent_personnel || [])
const upcoming = computed(() => data.value?.upcoming_tasks || [])
const kadro = computed(() => data.value?.kadro || null)
const kadroTab = ref('due')
const kadroExpanded = ref(false)
const kadroToast = ref('')
const kadroList = computed(() => (kadro.value ? kadro.value[kadroTab.value] : []) || [])
const kadroVisible = computed(() => (kadroExpanded.value ? kadroList.value : kadroList.value.slice(0, 5)))
const unread = computed(() => data.value?.unread_notifications || 0)

const STATUS_META = [
  { key: 'aktif', label: 'Aktif', color: 'var(--ds-emerald)' },
  { key: 'dilekce_alindi', label: 'Dilekçe Alındı', color: 'var(--ds-blue)' },
  { key: 'kadroya_gecmeyecek', label: 'Kadroya Geçmeyecek', color: 'var(--ds-amber)' },
  { key: 'kadroya_gecti', label: 'Kadroya Geçti', color: 'var(--ds-primary)' },
  { key: 'pasif', label: 'Pasif', color: 'var(--ds-muted-light)' },
]

const statusRows = computed(() => {
  if (!personnel.value) return []
  const total = personnel.value.total || 0
  return STATUS_META
    .map((s) => ({ ...s, count: personnel.value.by_status?.[s.key] || 0 }))
    .filter((s) => s.count > 0)
    .map((s) => ({ ...s, pct: total > 0 ? Math.round((s.count / total) * 100) : 0 }))
})

const activePct = computed(() => (personnel.value?.total ? Math.round((personnel.value.active / personnel.value.total) * 100) : 0))

const shortcuts = computed(() => MODULES.filter((m) => ['personnel', 'wages', 'tasks', 'doner', 'definitions'].includes(m.key) && auth.can(m.route)))

const PRIORITY = { high: 'Yüksek', medium: 'Orta', low: 'Düşük' }
const STATUS_LABEL = { todo: 'Yapılacak', in_progress: 'Devam ediyor', done: 'Tamamlandı' }

function formatDate(value) {
  if (!value) return 'Tarihsiz'
  const d = new Date(`${value.slice(0, 10)}T00:00:00`)
  return Number.isNaN(d.getTime()) ? 'Tarihsiz' : new Intl.DateTimeFormat('tr-TR', { day: 'numeric', month: 'short' }).format(d)
}

function isOverdue(task) {
  return task.due_date && task.status !== 'done' && new Date(`${task.due_date.slice(0, 10)}T23:59:59`) < new Date()
}

function formatLongDate(value) {
  const d = new Date(`${String(value).slice(0, 10)}T00:00:00`)
  return Number.isNaN(d.getTime()) ? '' : new Intl.DateTimeFormat('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(d)
}

function kadroNote(person) {
  const days = person.days_left
  if (days < 0) return `${Math.abs(days)} gün önce doldu`
  if (days === 0) return 'Bugün'
  return `${days} gün kaldı`
}

function kadroMessage(person) {
  const date = formatLongDate(person.kadro_tarihi)
  const lead = person.days_left <= 0
    ? `kadroya geçiş süreniz ${date} tarihi itibarıyla dolmuştur.`
    : `kadroya geçiş tarihiniz ${date} olarak görünmektedir.`
  return `Sayın ${person.ad_soyad},\n\n${lead} Kadroya geçiş işlemleriniz için gerekli dilekçe ve evrak sürecini başlatmak üzere tarafımızla iletişime geçmenizi rica ederiz.\n\nİyi çalışmalar dileriz.`
}

function sendKadroMessage(person) {
  const url = whatsappUrl(person.telefon, kadroMessage(person))
  if (!url) {
    kadroToast.value = `${person.ad_soyad} için geçerli telefon numarası yok.`
    setTimeout(() => { kadroToast.value = '' }, 3500)
    return
  }
  window.open(url, '_blank', 'noopener')
  logKadroMessage(person.id).catch(() => {})
}

function initialsOf(name) {
  const parts = String(name || '?').trim().split(/\s+/)
  return ((parts[0]?.[0] || '') + (parts.length > 1 ? parts.at(-1)[0] : '')).toLocaleUpperCase('tr-TR')
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    data.value = await getDashboard()
  } catch (reason) {
    if (reason?.status === 401) {
      auth.expire()
      await router.replace({ name: 'login', query: { expired: '1' } })
      return
    }
    error.value = reason?.message || 'Özet verileri alınamadı.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="page">
    <TopBar :eyebrow="today.toLocaleUpperCase('tr-TR')" :unread="unread" subtitle="Bugünkü özet ve hızlı erişim" />

    <main class="home">
      <div v-if="error" class="notice" role="alert">
        <AlertCircle :size="18" />
        <span>{{ error }}</span>
        <button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button>
      </div>

      <template v-if="loading">
        <div class="skeleton skeleton--hero" aria-hidden="true"></div>
        <div class="skeleton-row" aria-hidden="true"><div class="skeleton"></div><div class="skeleton"></div></div>
        <div class="skeleton skeleton--list" aria-hidden="true"></div>
      </template>

      <template v-else-if="data">
        <section v-if="personnel" class="card card--dark hero" aria-label="Personel özeti">
          <div class="hero__top">
            <div>
              <span class="eyebrow eyebrow--light">PERSONEL</span>
              <div class="hero__value">{{ personnel.total }}</div>
              <p class="hero__caption">kayıtlı personel · %{{ activePct }} aktif</p>
            </div>
            <button type="button" class="hero__link" aria-label="Personel listesine git" @click="router.push('/personel')"><ArrowUpRight :size="20" /></button>
          </div>
          <div v-if="personnel.total > 0" class="hero__bar" role="img" :aria-label="`Aktif oranı yüzde ${activePct}`">
            <i v-for="row in statusRows" :key="row.key" :style="{ flex: row.count, background: row.color }"></i>
          </div>
          <ul class="hero__legend">
            <li v-for="row in statusRows" :key="row.key"><i :style="{ background: row.color }"></i>{{ row.label }} <strong>{{ row.count }}</strong></li>
          </ul>
        </section>

        <section v-if="kadro && (kadro.due_count || kadro.upcoming_count)" class="card kadro" aria-label="Kadroya geçiş">
          <header class="card__head">
            <h2>Kadroya geçiş</h2>
          </header>
          <div class="kadro__tabs" role="tablist">
            <button type="button" role="tab" :aria-selected="kadroTab === 'due'" :class="{ on: kadroTab === 'due' }" @click="kadroTab = 'due'; kadroExpanded = false">Tarihi gelen <strong>{{ kadro.due_count }}</strong></button>
            <button type="button" role="tab" :aria-selected="kadroTab === 'upcoming'" :class="{ on: kadroTab === 'upcoming' }" @click="kadroTab = 'upcoming'; kadroExpanded = false">Yaklaşan <strong>{{ kadro.upcoming_count }}</strong></button>
          </div>
          <p v-if="kadroToast" class="kadro__toast" role="status">{{ kadroToast }}</p>
          <ul v-if="kadroVisible.length" class="list">
            <li v-for="person in kadroVisible" :key="person.id" class="row">
              <span class="avatar">{{ initialsOf(person.ad_soyad) }}</span>
              <div class="row__text">
                <strong>{{ person.ad_soyad }}</strong>
                <small>{{ formatLongDate(person.kadro_tarihi) }} · {{ kadroNote(person) }}</small>
              </div>
              <button type="button" class="wa" :aria-label="`${person.ad_soyad} kişisine WhatsApp mesajı gönder`" @click="sendKadroMessage(person)"><MessageCircle :size="19" /></button>
            </li>
          </ul>
          <p v-else class="empty"><CheckCircle2 :size="18" /> Bu listede personel yok.</p>
          <button v-if="kadroList.length > 5" type="button" class="link kadro__more" @click="kadroExpanded = !kadroExpanded">{{ kadroExpanded ? 'Daha az göster' : `Tümünü göster (${kadroList.length})` }}</button>
        </section>

        <section class="stat-grid" aria-label="Sayaçlar">
          <article v-if="personnel" class="card stat">
            <span class="stat__icon stat__icon--emerald"><UserPlus :size="18" /></span>
            <strong>{{ personnel.new_this_month }}</strong>
            <span>Bu ay eklenen</span>
          </article>
          <article v-if="personnel" class="card stat">
            <span class="stat__icon stat__icon--amber"><CalendarClock :size="18" /></span>
            <strong>{{ personnel.kadro_eligible }}</strong>
            <span>Kadroya uygun</span>
          </article>
          <article v-if="data.wage_count !== null" class="card stat">
            <span class="stat__icon stat__icon--blue"><Coins :size="18" /></span>
            <strong>{{ data.wage_count }}</strong>
            <span>Ücret tanımı</span>
          </article>
          <article v-if="tasks" class="card stat">
            <span class="stat__icon stat__icon--purple"><ListTodo :size="18" /></span>
            <strong>{{ tasks.todo + tasks.in_progress }}</strong>
            <span>Açık görev<template v-if="tasks.overdue"> · {{ tasks.overdue }} gecikmiş</template></span>
          </article>
        </section>

        <section v-if="shortcuts.length" aria-label="Hızlı erişim">
          <h2 class="section-title">Hızlı erişim</h2>
          <div class="shortcuts">
            <button v-for="item in shortcuts" :key="item.key" type="button" class="shortcut" :class="`theme-${item.theme}`" @click="router.push(item.to)">
              <span class="shortcut__icon"><component :is="item.icon" :size="21" /></span>
              <span>{{ item.navLabel }}</span>
            </button>
          </div>
        </section>

        <section v-if="tasks" class="card" aria-label="Görevlerim">
          <header class="card__head">
            <h2>Görevlerim</h2>
            <button type="button" class="link" @click="router.push('/yapilacaklar')">Tümü</button>
          </header>
          <ul v-if="upcoming.length" class="list">
            <li v-for="task in upcoming" :key="task.id" class="row">
              <span class="row__dot" :class="`prio-${task.priority}`" :title="PRIORITY[task.priority]"></span>
              <div class="row__text">
                <strong>{{ task.title }}</strong>
                <small>{{ STATUS_LABEL[task.status] }} · {{ PRIORITY[task.priority] }} öncelik</small>
              </div>
              <span class="row__date" :class="{ 'is-overdue': isOverdue(task) }">{{ formatDate(task.due_date) }}</span>
            </li>
          </ul>
          <p v-else class="empty"><CheckCircle2 :size="18" /> Bekleyen göreviniz yok.</p>
        </section>

        <section v-if="personnel" class="card" aria-label="Son eklenen personel">
          <header class="card__head">
            <h2>Son eklenen personel</h2>
            <button type="button" class="link" @click="router.push('/personel')">Tümü</button>
          </header>
          <ul v-if="recent.length" class="list">
            <li v-for="person in recent" :key="person.id" class="row">
              <span class="avatar">{{ initialsOf(person.ad_soyad) }}</span>
              <div class="row__text">
                <strong>{{ person.ad_soyad }}</strong>
                <small>{{ person.unvan || 'Unvan tanımsız' }}</small>
              </div>
              <span class="row__date">{{ formatDate(person.created_at) }}</span>
            </li>
          </ul>
          <p v-else class="empty"><UsersRound :size="18" /> Henüz personel kaydı yok.</p>
        </section>

        <p v-if="!personnel && !tasks && data.wage_count === null && !shortcuts.length" class="empty empty--card">
          Bu hesap için görüntülenecek özet bulunmuyor.
        </p>
      </template>
    </main>
  </div>
</template>

<style scoped>
.page { min-height: 100svh; }
.home { display: grid; gap: 16px; padding: 14px 16px calc(112px + var(--ds-safe-bottom)); max-width: 520px; margin: 0 auto; }
.card { background: var(--ds-surface); border-radius: var(--ds-radius-card); padding: 18px; box-shadow: var(--ds-shadow-bento); }
.card--dark { background: var(--ds-surface-dark); color: var(--ds-ink-light); box-shadow: var(--ds-shadow-bento-dark); }
.eyebrow { color: var(--ds-muted); font-size: 10px; font-weight: 800; letter-spacing: .12em; }
.eyebrow--light { color: var(--ds-muted-light); }
.hero { display: grid; gap: 16px; }
.hero__top { display: flex; justify-content: space-between; align-items: flex-start; }
.hero__value { font: 700 54px/1 var(--ds-font-mono); letter-spacing: -.03em; margin-top: 6px; }
.hero__caption { margin: 6px 0 0; color: var(--ds-muted-light); font-size: 13px; }
.hero__link { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--ds-border-dark); background: rgba(255, 255, 255, .08); color: #fff; cursor: pointer; }
.hero__bar { display: flex; gap: 3px; height: 10px; border-radius: 999px; overflow: hidden; }
.hero__bar i { display: block; min-width: 6px; border-radius: 999px; }
.hero__legend { display: flex; flex-wrap: wrap; gap: 8px 14px; margin: 0; padding: 0; list-style: none; font-size: 12px; color: var(--ds-muted-light); }
.hero__legend li { display: inline-flex; align-items: center; gap: 6px; }
.hero__legend i { width: 8px; height: 8px; border-radius: 50%; }
.hero__legend strong { color: #fff; }
.stat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
.stat { display: grid; gap: 4px; padding: 16px; border-radius: var(--ds-radius-tile); box-shadow: var(--ds-shadow-tile); }
.stat strong { font: 700 28px/1.1 var(--ds-font-mono); letter-spacing: -.02em; }
.stat > span:last-child { color: var(--ds-muted); font-size: 12px; font-weight: 600; }
.stat__icon { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 12px; margin-bottom: 6px; }
.stat__icon--emerald { background: #d1fae5; color: #047857; }
.stat__icon--amber { background: #fef3c7; color: #b45309; }
.stat__icon--blue { background: #e5e7ee; color: #1f2a44; }
.stat__icon--purple { background: #ede9fe; color: #6d28d9; }
.kadro__tabs { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; padding: 4px; border-radius: 999px; background: var(--ds-surface-subtle); }
.kadro__tabs button { min-height: 40px; border: 0; border-radius: 999px; background: transparent; color: var(--ds-muted); font-size: 13px; font-weight: 700; cursor: pointer; }
.kadro__tabs button.on { background: var(--ds-surface); color: var(--ds-ink); box-shadow: var(--ds-shadow-tile); }
.kadro__tabs strong { margin-left: 4px; font-family: var(--ds-font-mono); }
.kadro__toast { margin: 8px 0 0; color: var(--ds-danger-ink); font-size: 12px; font-weight: 600; }
.kadro .list { margin-top: 6px; }
.kadro__more { width: 100%; text-align: center; }
.wa { display: grid; place-items: center; flex-shrink: 0; width: 44px; height: 44px; border: 0; border-radius: 50%; background: #d1fae5; color: #047857; cursor: pointer; }
.wa:active { transform: scale(.92); }
.section-title { margin: 0 0 10px 4px; font-size: 16px; font-weight: 800; }
.shortcuts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.shortcut { display: grid; justify-items: center; gap: 8px; min-height: 88px; padding: 14px 6px 10px; border: 0; border-radius: var(--ds-radius-tile); background: var(--ds-surface); box-shadow: var(--ds-shadow-tile); cursor: pointer; font-size: 12px; font-weight: 700; color: var(--ds-ink); transition: transform .15s ease; }
.shortcut:active { transform: scale(.95); }
.shortcut__icon { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 14px; background: var(--ds-surface-subtle); color: var(--tone); }
.theme-blue { --tone: #1f2a44; } .theme-emerald { --tone: #059669; } .theme-amber { --tone: #d97706; } .theme-purple { --tone: #7c3aed; } .theme-coral { --tone: #e11d48; }
.card__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.card__head h2 { margin: 0; font-size: 16px; font-weight: 800; }
.link { min-height: 44px; padding: 0 4px; border: 0; background: transparent; color: var(--ds-muted); font-size: 13px; font-weight: 700; cursor: pointer; }
.list { margin: 0; padding: 0; list-style: none; display: grid; }
.row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-top: 1px solid var(--ds-border-subtle); }
.row:first-child { border-top: 0; }
.row__text { flex: 1; min-width: 0; display: grid; gap: 2px; }
.row__text strong { font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.row__text small { color: var(--ds-muted); font-size: 12px; }
.row__date { flex-shrink: 0; color: var(--ds-muted); font-size: 12px; font-weight: 700; }
.row__date.is-overdue { color: var(--ds-danger-ink); }
.row__dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; background: var(--ds-muted-light); }
.prio-high { background: var(--ds-danger); } .prio-medium { background: var(--ds-amber); } .prio-low { background: var(--ds-emerald); }
.avatar { display: grid; place-items: center; flex-shrink: 0; width: 38px; height: 38px; border-radius: 50%; background: var(--ds-surface-dark); color: #fff; font-size: 13px; font-weight: 800; }
.empty { display: flex; align-items: center; gap: 8px; margin: 6px 0 0; color: var(--ds-muted); font-size: 13px; }
.empty--card { justify-content: center; padding: 24px; background: var(--ds-surface); border-radius: var(--ds-radius-card); }
.notice { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.notice span { flex: 1; }
.notice button { display: inline-flex; align-items: center; gap: 5px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 999px; background: var(--ds-surface); color: var(--ds-ink); font-weight: 700; cursor: pointer; }
.skeleton { height: 96px; border-radius: var(--ds-radius-tile); background: linear-gradient(90deg, #dde3ea 25%, #eef1f5 50%, #dde3ea 75%); background-size: 200% 100%; animation: shimmer 1.3s infinite; }
.skeleton--hero { height: 190px; border-radius: var(--ds-radius-card); }
.skeleton--list { height: 220px; border-radius: var(--ds-radius-card); }
.skeleton-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@keyframes shimmer { to { background-position: -200% 0; } }
@media (prefers-reduced-motion: reduce) { .skeleton { animation: none; } .shortcut { transition: none; } }
</style>

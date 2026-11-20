<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { AlertCircle, CalendarDays, CircleCheck, ListTodo, Pencil, Plus, RefreshCw, Trash2, X } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import { auth } from '../services/auth.js'
import { PRIORITY_META, deleteTask, listTasks, moveTask, saveTask } from '../services/tasks.js'

const router = useRouter()
const boards = ref([])
const tasks = ref([])
const users = ref([])
const loading = ref(true)
const error = ref('')
const scope = ref('all')
const activeBoard = ref(null)
const toast = ref('')
const busy = ref(false)
const sheetOpen = ref(false)
const sheetError = ref('')
const confirmDelete = ref(false)
const form = ref(emptyForm())
let toastTimer = null
let previousOverflow = ''

const SCOPES = [
  { key: 'all', label: 'Tümü' },
  { key: 'mine', label: 'Eklediklerim' },
  { key: 'assigned', label: 'Bana atanan' },
]

function emptyForm() {
  return { id: 0, title: '', description: '', priority: 'medium', board_id: 0, due_date: '', assignees: [] }
}

const boardCounts = computed(() => {
  const map = {}
  for (const t of tasks.value) map[t.board_id] = (map[t.board_id] || 0) + 1
  return map
})
const visibleTasks = computed(() => tasks.value.filter((t) => t.board_id === activeBoard.value))
const activeBoardIndex = computed(() => boards.value.findIndex((b) => b.id === activeBoard.value))

function showToast(message) {
  toast.value = message
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = '' }, 3000)
}

function shortDate(value) {
  const v = String(value || '').slice(0, 10)
  return /^\d{4}-\d{2}-\d{2}$/.test(v) ? `${v.slice(8, 10)}.${v.slice(5, 7)}.${v.slice(0, 4)}` : ''
}

function isOverdue(task) {
  if (!task.due_date) return false
  const last = boards.value[boards.value.length - 1]
  if (last && task.board_id === last.id) return false
  return task.due_date.slice(0, 10) < new Date().toISOString().slice(0, 10)
}

function initialsOf(name) {
  const parts = String(name || '?').trim().split(/\s+/)
  return ((parts[0]?.[0] || '') + (parts.length > 1 ? parts.at(-1)[0] : '')).toLocaleUpperCase('tr-TR')
}

async function handleExpired() {
  auth.expire()
  await router.replace({ name: 'login', query: { expired: '1' } })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await listTasks(scope.value)
    boards.value = data.boards
    tasks.value = data.tasks
    users.value = data.users
    if (!boards.value.some((b) => b.id === activeBoard.value)) activeBoard.value = boards.value[0]?.id ?? null
  } catch (reason) {
    if (reason?.status === 401) return handleExpired()
    error.value = reason?.message || 'Görevler alınamadı.'
  } finally {
    loading.value = false
  }
}

function setScope(key) {
  if (scope.value === key) return
  scope.value = key
  load()
}

function lockScroll() {
  previousOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
}

function openForm(task = null) {
  sheetError.value = ''
  confirmDelete.value = false
  form.value = task
    ? { id: task.id, title: task.title, description: task.description || '', priority: task.priority, board_id: task.board_id, due_date: (task.due_date || '').slice(0, 10), assignees: task.assignees.map((a) => a.id) }
    : { ...emptyForm(), board_id: activeBoard.value || boards.value[0]?.id || 0 }
  if (!sheetOpen.value) lockScroll()
  sheetOpen.value = true
}

function closeForm() {
  if (!sheetOpen.value) return
  sheetOpen.value = false
  document.body.style.overflow = previousOverflow
}

async function run(fn) {
  sheetError.value = ''
  busy.value = true
  try {
    return await fn()
  } catch (reason) {
    if (reason?.status === 401) { closeForm(); await handleExpired(); return null }
    sheetError.value = reason?.message || 'İşlem tamamlanamadı.'
    return null
  } finally {
    busy.value = false
  }
}

async function submit() {
  const editing = Boolean(form.value.id)
  const result = await run(() => saveTask({ ...form.value }))
  if (!result) return
  activeBoard.value = form.value.board_id
  closeForm()
  showToast(editing ? 'Görev güncellendi.' : 'Görev eklendi.')
  await load()
}

async function remove() {
  const result = await run(() => deleteTask(form.value.id))
  if (!result) return
  closeForm()
  showToast('Görev silindi.')
  await load()
}

async function shift(task, direction) {
  const target = boards.value[boards.value.findIndex((b) => b.id === task.board_id) + direction]
  if (!target || busy.value) return
  busy.value = true
  const previous = task.board_id
  task.board_id = target.id
  try {
    await moveTask(task.id, target.id)
    showToast(`"${target.title}" sütununa taşındı.`)
  } catch (reason) {
    task.board_id = previous
    if (reason?.status === 401) await handleExpired()
    else showToast(reason?.message || 'Taşınamadı.')
  } finally {
    busy.value = false
  }
}

function toggleAssignee(id) {
  const list = form.value.assignees
  form.value.assignees = list.includes(id) ? list.filter((x) => x !== id) : [...list, id]
}

function onKey(event) {
  if (event.key === 'Escape') closeForm()
}

onMounted(load)
onBeforeUnmount(() => {
  clearTimeout(toastTimer)
  if (sheetOpen.value) document.body.style.overflow = previousOverflow
})
</script>

<template>
  <div class="page">
    <TopBar eyebrow="İŞ TAKİBİ" title="Görevler" :back="true" />

    <main class="tasks">
      <div class="chips" role="group" aria-label="Görev kapsamı">
        <button v-for="s in SCOPES" :key="s.key" type="button" class="chip" :class="{ 'is-active': scope === s.key }" :aria-pressed="scope === s.key" @click="setScope(s.key)">{{ s.label }}</button>
      </div>

      <div v-if="boards.length" class="chips" role="tablist" aria-label="Sütunlar">
        <button v-for="b in boards" :key="b.id" type="button" role="tab" class="chip chip--board" :class="{ 'is-active': activeBoard === b.id }" :aria-selected="activeBoard === b.id" @click="activeBoard = b.id">
          {{ b.title }} <b>{{ boardCounts[b.id] || 0 }}</b>
        </button>
      </div>

      <div v-if="error" class="notice" role="alert">
        <AlertCircle :size="18" /><span>{{ error }}</span>
        <button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button>
      </div>

      <div v-if="loading" class="skeletons" aria-hidden="true"><i v-for="n in 4" :key="n"></i></div>

      <template v-else>
        <ul v-if="visibleTasks.length" class="list">
          <li v-for="task in visibleTasks" :key="task.id" class="task">
            <button type="button" class="task__main" @click="openForm(task)">
              <span class="task__top">
                <strong>{{ task.title }}</strong>
                <span class="prio" :class="`tone-${PRIORITY_META[task.priority]?.tone || 'amber'}`">{{ PRIORITY_META[task.priority]?.label || task.priority }}</span>
              </span>
              <small v-if="task.description" class="task__desc">{{ task.description }}</small>
              <span class="task__meta">
                <span v-if="task.due_date" class="due" :class="{ 'is-late': isOverdue(task) }"><CalendarDays :size="13" /> {{ shortDate(task.due_date) }}</span>
                <span v-if="task.assignees.length" class="avatars">
                  <i v-for="a in task.assignees.slice(0, 4)" :key="a.id" :title="a.name">{{ initialsOf(a.name) }}</i>
                  <em v-if="task.assignees.length > 4">+{{ task.assignees.length - 4 }}</em>
                </span>
              </span>
            </button>
            <div class="task__move">
              <button type="button" :disabled="busy || activeBoardIndex <= 0" @click="shift(task, -1)">‹ {{ boards[activeBoardIndex - 1]?.title || '' }}</button>
              <button type="button" :disabled="busy || activeBoardIndex >= boards.length - 1" @click="shift(task, 1)">{{ boards[activeBoardIndex + 1]?.title || '' }} ›</button>
            </div>
          </li>
        </ul>
        <div v-else-if="!error" class="empty"><ListTodo :size="28" /><p>{{ boards.length ? 'Bu sütunda görev yok.' : 'Henüz sütun tanımlı değil.' }}</p></div>
      </template>
    </main>

    <Teleport to="body">
      <button v-if="!sheetOpen && boards.length" type="button" class="fab" aria-label="Görev ekle" @click="openForm()"><Plus :size="22" /> Görev</button>
      <Transition name="island">
        <div v-if="toast" class="island" role="status" aria-live="polite"><CircleCheck :size="18" /><span>{{ toast }}</span></div>
      </Transition>
    </Teleport>

    <Transition name="sheet">
      <div v-if="sheetOpen" class="layer" @keydown="onKey" @click.self="closeForm">
        <form class="sheet" role="dialog" aria-modal="true" :aria-label="form.id ? 'Görevi düzenle' : 'Yeni görev'" @submit.prevent="submit">
          <header class="sheet__head">
            <h2>{{ form.id ? 'Görevi düzenle' : 'Yeni görev' }}</h2>
            <button type="button" class="close" aria-label="Kapat" @click="closeForm"><X :size="19" /></button>
          </header>

          <label>Başlık<input v-model="form.title" type="text" maxlength="255" required autocomplete="off" /></label>
          <label>Açıklama<textarea v-model="form.description" rows="3" maxlength="2000"></textarea></label>
          <div class="two">
            <label>Sütun
              <select v-model.number="form.board_id" required><option v-for="b in boards" :key="b.id" :value="b.id">{{ b.title }}</option></select>
            </label>
            <label>Öncelik
              <select v-model="form.priority"><option v-for="(m, key) in PRIORITY_META" :key="key" :value="key">{{ m.label }}</option></select>
            </label>
          </div>
          <label>Bitiş tarihi<input v-model="form.due_date" type="date" /></label>

          <fieldset v-if="users.length">
            <legend>Atananlar</legend>
            <div class="assign">
              <button v-for="u in users" :key="u.id" type="button" class="chip" :class="{ 'is-active': form.assignees.includes(u.id) }" :aria-pressed="form.assignees.includes(u.id)" @click="toggleAssignee(u.id)">{{ u.name }}</button>
            </div>
          </fieldset>

          <p v-if="sheetError" class="notice notice--inline" role="alert">{{ sheetError }}</p>

          <div class="actions">
            <template v-if="!confirmDelete">
              <button type="submit" class="btn btn--dark" :disabled="busy"><Pencil :size="16" /> {{ busy ? 'Kaydediliyor…' : 'Kaydet' }}</button>
              <button v-if="form.id" type="button" class="btn btn--danger" @click="confirmDelete = true"><Trash2 :size="16" /> Sil</button>
            </template>
            <template v-else>
              <p class="confirm">Bu görev silinsin mi? İşlem geri alınamaz.</p>
              <button type="button" class="btn btn--light" @click="confirmDelete = false">Vazgeç</button>
              <button type="button" class="btn btn--danger-solid" :disabled="busy" @click="remove">{{ busy ? 'Siliniyor…' : 'Evet, sil' }}</button>
            </template>
          </div>
        </form>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.page { min-height: 100svh; }
.tasks { display: grid; gap: 12px; padding: 14px 16px calc(112px + var(--ds-safe-bottom)); max-width: 520px; margin: 0 auto; }
.chips { display: flex; gap: 8px; overflow-x: auto; margin: 0 -16px; padding: 2px 16px; scrollbar-width: none; }
.chips::-webkit-scrollbar { display: none; }
.chip { flex: 0 0 auto; min-height: 40px; padding: 0 14px; border: 0; border-radius: 999px; background: var(--ds-surface); box-shadow: var(--ds-shadow-pill); font: 700 13px var(--ds-font-main); color: var(--ds-ink); cursor: pointer; white-space: nowrap; }
.chip b { margin-left: 4px; color: var(--ds-muted); }
.chip.is-active { background: var(--ds-surface-dark); color: #fff; }
.chip.is-active b { color: var(--ds-muted-light); }
.chip--board { background: var(--ds-surface-subtle); }
.list { margin: 0; padding: 0; list-style: none; display: grid; gap: 10px; }
.task { border-radius: var(--ds-radius-tile); background: var(--ds-surface); box-shadow: var(--ds-shadow-tile); overflow: hidden; }
.task__main { display: grid; gap: 8px; width: 100%; padding: 14px; border: 0; background: transparent; text-align: left; color: var(--ds-ink); cursor: pointer; font: inherit; }
.task__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.task__top strong { font-size: 15px; line-height: 1.3; overflow-wrap: anywhere; }
.task__desc { color: var(--ds-muted); font-size: 13px; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.task__meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; min-height: 22px; }
.prio { flex-shrink: 0; padding: 3px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; }
.tone-red { background: #fee2e2; color: #b91c1c; } .tone-amber { background: #fef3c7; color: #b45309; } .tone-emerald { background: #d1fae5; color: #047857; }
.due { display: inline-flex; align-items: center; gap: 4px; color: var(--ds-muted); font-size: 12px; font-weight: 700; }
.due.is-late { color: var(--ds-danger-ink); }
.avatars { display: inline-flex; margin-left: auto; }
.avatars i, .avatars em { display: grid; place-items: center; width: 26px; height: 26px; margin-left: -6px; border: 2px solid var(--ds-surface); border-radius: 50%; background: var(--ds-surface-dark); color: #fff; font: normal 800 9px var(--ds-font-main); }
.avatars em { background: var(--ds-surface-subtle); color: var(--ds-ink); }
.task__move { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid var(--ds-border-subtle); }
.task__move button { min-height: 44px; padding: 0 10px; border: 0; background: transparent; font: 700 12px var(--ds-font-main); color: var(--ds-primary); cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.task__move button:first-child { border-right: 1px solid var(--ds-border-subtle); text-align: left; }
.task__move button:last-child { text-align: right; }
.task__move button:disabled { opacity: .3; cursor: default; }
.empty { display: grid; justify-items: center; gap: 6px; padding: 40px 20px; border-radius: var(--ds-radius-card); background: var(--ds-surface); color: var(--ds-muted); text-align: center; }
.empty p { margin: 0; font-size: 14px; }
.notice { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.notice span { flex: 1; }
.notice button { display: inline-flex; align-items: center; gap: 5px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 999px; background: var(--ds-surface); color: var(--ds-ink); font-weight: 700; cursor: pointer; }
.notice--inline { margin: 0; }
.skeletons { display: grid; gap: 10px; }
.skeletons i { height: 110px; border-radius: var(--ds-radius-tile); background: linear-gradient(90deg, #dde3ea 25%, #eef1f5 50%, #dde3ea 75%); background-size: 200% 100%; animation: shimmer 1.3s infinite; }
@keyframes shimmer { to { background-position: -200% 0; } }

.layer { position: fixed; inset: 0; z-index: 80; display: flex; align-items: flex-end; justify-content: center; background: rgba(15, 18, 24, .5); }
.sheet { display: grid; gap: 12px; width: min(100%, 520px); max-height: 90svh; overflow-y: auto; padding: 18px 16px calc(18px + var(--ds-safe-bottom)); border-radius: 28px 28px 0 0; background: var(--ds-surface); box-shadow: 0 -16px 40px rgba(0, 0, 0, .25); }
.sheet__head { display: flex; align-items: center; justify-content: space-between; }
.sheet__head h2 { margin: 0; font-size: 19px; }
.close { display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); cursor: pointer; }
.sheet label, .sheet legend { display: grid; gap: 4px; color: var(--ds-muted); font-size: 11px; font-weight: 700; min-width: 0; }
.sheet input, .sheet select, .sheet textarea { width: 100%; min-height: 44px; padding: 10px; border: 1px solid var(--ds-border-subtle); border-radius: 12px; background: var(--ds-surface-subtle); font: 600 16px var(--ds-font-main); color: var(--ds-ink); box-sizing: border-box; }
.sheet textarea { resize: vertical; }
.sheet fieldset { margin: 0; padding: 0; border: 0; display: grid; gap: 6px; }
.assign { display: flex; flex-wrap: wrap; gap: 6px; }
.assign .chip { min-height: 36px; background: var(--ds-surface-subtle); box-shadow: none; }
.assign .chip.is-active { background: var(--ds-surface-dark); }
.two { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.actions { display: flex; flex-wrap: wrap; gap: 8px; }
.btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 48px; border: 0; border-radius: 999px; font: 700 14px var(--ds-font-main); cursor: pointer; }
.btn--dark { background: var(--ds-surface-dark); color: #fff; }
.btn--danger { background: var(--ds-danger-bg); color: var(--ds-danger-ink); }
.btn--danger-solid { background: var(--ds-danger); color: #fff; }
.btn--light { background: var(--ds-surface-subtle); color: var(--ds-ink); }
.btn:disabled { opacity: .6; }
.confirm { flex-basis: 100%; margin: 0; font-size: 13px; font-weight: 700; color: var(--ds-danger-ink); }
.sheet-enter-active, .sheet-leave-active { transition: opacity .2s ease; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .28s cubic-bezier(.16, 1, .3, 1); }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
.fab { position: fixed; z-index: 60; right: max(16px, calc(50% - 244px)); bottom: calc(108px + var(--ds-safe-bottom)); display: inline-flex; align-items: center; gap: 6px; min-height: 52px; padding: 0 20px 0 16px; border: 0; border-radius: 999px; background: #1d4ed8; color: #fff; font: 800 15px var(--ds-font-main); box-shadow: 0 12px 24px -6px rgba(29, 78, 216, .5); cursor: pointer; }
.fab:active { transform: scale(.95); }
.island { position: fixed; z-index: 200; left: 50%; top: calc(10px + var(--ds-safe-top, 0px)); transform: translateX(-50%); display: inline-flex; align-items: center; gap: 8px; max-width: calc(100% - 32px); padding: 12px 22px; border-radius: 999px; background: #000; color: #fff; font-size: 14px; font-weight: 700; white-space: nowrap; box-shadow: 0 10px 30px rgba(0, 0, 0, .35); }
.island svg { flex-shrink: 0; color: #34d399; }
.island span { overflow: hidden; text-overflow: ellipsis; }
.island-enter-active { animation: island-in .55s cubic-bezier(.34, 1.4, .5, 1) both; }
.island-leave-active { animation: island-out .35s cubic-bezier(.4, 0, 1, 1) both; }
@keyframes island-in { from { opacity: 0; transform: translateX(-50%) translateY(-24px) scale(.3, .5); } to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); } }
@keyframes island-out { to { opacity: 0; transform: translateX(-50%) translateY(-18px) scale(.4, .6); } }
@media (prefers-reduced-motion: reduce) { .skeletons i { animation: none; } .sheet-enter-active, .sheet-leave-active, .sheet-enter-active .sheet, .sheet-leave-active .sheet, .island-enter-active, .island-leave-active { transition: none; animation: none; } }
</style>

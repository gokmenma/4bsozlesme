<script setup>
import { onMounted, ref } from 'vue'
import { AlertCircle, MessageSquare, RefreshCw, Trash2 } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import ManageSheet from '../components/ManageSheet.vue'
import ToastIsland from '../components/ToastIsland.vue'
import { useManage } from '../composables/useManage.js'
import { feedbacksApi } from '../services/system.js'

const { toast, showToast, guard, formatDate } = useManage()
const STATUS = { new: ['Yeni', 'blue'], in_progress: ['İnceleniyor', 'amber'], resolved: ['Çözüldü', 'emerald'] }
const TYPE = { bug: 'Hata', suggestion: 'Öneri', other: 'Diğer' }

const items = ref([])
const counts = ref({ all: 0, new: 0, in_progress: 0, resolved: 0 })
const filter = ref('all')
const loading = ref(true)
const error = ref('')
const open = ref(false)
const busy = ref(false)
const formError = ref('')
const confirmDelete = ref(false)
const item = ref(null)
const form = ref({ status: 'new', note: '' })

async function load() {
  loading.value = true
  error.value = ''
  const data = await guard(() => feedbacksApi.list(filter.value), (m) => { error.value = m })
  if (data) { items.value = data.items; counts.value = data.counts }
  loading.value = false
}

function setFilter(key) { if (filter.value !== key) { filter.value = key; load() } }

function openItem(f) {
  item.value = f
  form.value = { status: f.status, note: f.admin_note || '' }
  formError.value = ''
  confirmDelete.value = false
  open.value = true
}

async function run(fn, message) {
  busy.value = true
  formError.value = ''
  const result = await guard(fn, (m) => { formError.value = m; confirmDelete.value = false })
  busy.value = false
  if (!result) return
  open.value = false
  showToast(message)
  load()
}

const submit = () => run(() => feedbacksApi.update({ id: item.value.id, ...form.value }), 'Geri bildirim güncellendi.')
const remove = () => run(() => feedbacksApi.remove(item.value.id), 'Geri bildirim silindi.')

onMounted(load)
</script>

<template>
  <div class="m-page">
    <TopBar eyebrow="SİSTEM YÖNETİMİ" title="Geri Bildirimler" :back="true" />
    <main class="m-main">
      <div class="m-chips" role="group" aria-label="Durum filtresi">
        <button type="button" class="m-chip" :class="{ 'is-active': filter === 'all' }" :aria-pressed="filter === 'all'" @click="setFilter('all')">Tümü <b>{{ counts.all }}</b></button>
        <button v-for="(meta, key) in STATUS" :key="key" type="button" class="m-chip" :class="{ 'is-active': filter === key }" :aria-pressed="filter === key" @click="setFilter(key)">{{ meta[0] }} <b>{{ counts[key] }}</b></button>
      </div>

      <div v-if="error" class="m-notice" role="alert"><AlertCircle :size="18" /><span>{{ error }}</span><button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button></div>
      <div v-if="loading" class="m-skeletons" aria-hidden="true"><i v-for="n in 5" :key="n"></i></div>
      <template v-else>
        <ul v-if="items.length" class="m-list">
          <li v-for="f in items" :key="f.id">
            <button type="button" class="m-row" @click="openItem(f)">
              <span class="m-row__text"><strong>{{ f.subject }}</strong><small>{{ TYPE[f.type] || f.type }} · {{ f.user_name || 'Bilinmiyor' }} · {{ formatDate(f.created_at) }}</small></span>
              <span class="m-badge" :class="`m-tone-${STATUS[f.status]?.[1] || 'gray'}`">{{ STATUS[f.status]?.[0] || f.status }}</span>
            </button>
          </li>
        </ul>
        <div v-else-if="!error" class="m-empty"><MessageSquare :size="28" /><p>Geri bildirim yok.</p></div>
      </template>
    </main>

    <ManageSheet v-if="open && item" :title="item.subject" @close="open = false">
      <form class="m-card" style="box-shadow:none;padding:0" @submit.prevent="submit">
        <p class="m-hint">{{ TYPE[item.type] || item.type }} · {{ item.user_name || 'Bilinmiyor' }}<template v-if="item.tenant_name"> · {{ item.tenant_name }}</template> · {{ formatDate(item.created_at) }}</p>
        <p class="msg">{{ item.message }}</p>
        <label class="m-field">Durum
          <select v-model="form.status"><option v-for="(meta, key) in STATUS" :key="key" :value="key">{{ meta[0] }}</option></select>
        </label>
        <label class="m-field">Yanıt / not <small>(kullanıcıya bildirim gider)</small><textarea v-model="form.note" rows="3" maxlength="2000"></textarea></label>
        <p v-if="formError" class="m-notice" role="alert">{{ formError }}</p>
        <div class="m-actions">
          <template v-if="!confirmDelete">
            <button type="submit" class="m-btn m-btn--dark" :disabled="busy">{{ busy ? 'Kaydediliyor…' : 'Kaydet' }}</button>
            <button type="button" class="m-btn m-btn--danger" @click="confirmDelete = true"><Trash2 :size="16" /> Sil</button>
          </template>
          <template v-else>
            <p class="m-confirm">Bu geri bildirim silinsin mi? İşlem geri alınamaz.</p>
            <button type="button" class="m-btn m-btn--light" @click="confirmDelete = false">Vazgeç</button>
            <button type="button" class="m-btn m-btn--danger-solid" :disabled="busy" @click="remove">Evet, sil</button>
          </template>
        </div>
      </form>
    </ManageSheet>
    <ToastIsland :message="toast" />
  </div>
</template>

<style scoped>
.msg { margin: 0; padding: 12px 14px; border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); font-size: 14px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
</style>

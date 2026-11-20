<script setup>
import { onMounted, ref } from 'vue'
import { AlertCircle, Building2, RefreshCw, Trash2 } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import ManageSheet from '../components/ManageSheet.vue'
import ToastIsland from '../components/ToastIsland.vue'
import { useManage } from '../composables/useManage.js'
import { tenantsApi } from '../services/system.js'

const { toast, showToast, guard, formatDate } = useManage()
const tenants = ref([])
const current = ref(0)
const loading = ref(true)
const error = ref('')
const open = ref(false)
const busy = ref(false)
const formError = ref('')
const confirmDelete = ref(false)
const form = ref({ id: 0, name: '', is_active: true, user_count: 0 })

async function load() {
  loading.value = true
  error.value = ''
  const data = await guard(() => tenantsApi.list(), (m) => { error.value = m })
  if (data) { tenants.value = data.tenants; current.value = data.current }
  loading.value = false
}

function openForm(t) {
  formError.value = ''
  confirmDelete.value = false
  form.value = { id: t.id, name: t.name, is_active: Boolean(Number(t.is_active)), user_count: Number(t.user_count), created_at: t.created_at }
  open.value = true
}

async function submit() {
  busy.value = true
  formError.value = ''
  const result = await guard(() => tenantsApi.update({ id: form.value.id, name: form.value.name, is_active: form.value.is_active }), (m) => { formError.value = m })
  busy.value = false
  if (!result) return
  open.value = false
  showToast('Kurum güncellendi.')
  load()
}

async function remove() {
  busy.value = true
  const result = await guard(() => tenantsApi.remove(form.value.id), (m) => { formError.value = m; confirmDelete.value = false })
  busy.value = false
  if (!result) return
  open.value = false
  showToast('Kurum silindi.')
  load()
}

onMounted(load)
</script>

<template>
  <div class="m-page">
    <TopBar eyebrow="SİSTEM YÖNETİMİ" title="Kurum Yönetimi" :back="true" />
    <main class="m-main">
      <div v-if="error" class="m-notice" role="alert"><AlertCircle :size="18" /><span>{{ error }}</span><button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button></div>
      <div v-if="loading" class="m-skeletons" aria-hidden="true"><i v-for="n in 5" :key="n"></i></div>
      <template v-else>
        <p v-if="tenants.length" class="m-hint">{{ tenants.length }} kurum kayıtlı.</p>
        <ul v-if="tenants.length" class="m-list">
          <li v-for="t in tenants" :key="t.id">
            <button type="button" class="m-row" @click="openForm(t)">
              <span class="m-avatar"><Building2 :size="18" /></span>
              <span class="m-row__text"><strong>{{ t.name }}</strong><small>{{ t.user_count }} kullanıcı<template v-if="t.id === current"> · aktif kurumunuz</template></small></span>
              <span class="m-badge" :class="Number(t.is_active) ? 'm-tone-emerald' : 'm-tone-gray'">{{ Number(t.is_active) ? 'Aktif' : 'Pasif' }}</span>
            </button>
          </li>
        </ul>
        <div v-else-if="!error" class="m-empty"><Building2 :size="28" /><p>Kurum kaydı yok.</p></div>
      </template>
    </main>

    <ManageSheet v-if="open" title="Kurumu düzenle" @close="open = false">
      <form class="m-card" style="box-shadow:none;padding:0" @submit.prevent="submit">
        <label class="m-field">Kurum adı<input v-model="form.name" type="text" maxlength="255" required /></label>
        <label class="m-switch"><span>Kurum aktif</span><input v-model="form.is_active" type="checkbox" /></label>
        <p class="m-hint">{{ form.user_count }} kullanıcı · Kayıt: {{ formatDate(form.created_at) }}</p>
        <p v-if="formError" class="m-notice" role="alert">{{ formError }}</p>
        <div class="m-actions">
          <template v-if="!confirmDelete">
            <button type="submit" class="m-btn m-btn--dark" :disabled="busy">{{ busy ? 'Kaydediliyor…' : 'Kaydet' }}</button>
            <button v-if="form.id !== current" type="button" class="m-btn m-btn--danger" @click="confirmDelete = true"><Trash2 :size="16" /> Sil</button>
          </template>
          <template v-else>
            <p class="m-confirm">Bu kurum silinsin mi? İşlem geri alınamaz.</p>
            <button type="button" class="m-btn m-btn--light" @click="confirmDelete = false">Vazgeç</button>
            <button type="button" class="m-btn m-btn--danger-solid" :disabled="busy" @click="remove">{{ busy ? 'Siliniyor…' : 'Evet, sil' }}</button>
          </template>
        </div>
      </form>
    </ManageSheet>
    <ToastIsland :message="toast" />
  </div>
</template>

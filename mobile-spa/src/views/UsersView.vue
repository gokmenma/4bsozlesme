<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { AlertCircle, Plus, RefreshCw, Search, Trash2, UsersRound } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import ManageSheet from '../components/ManageSheet.vue'
import ToastIsland from '../components/ToastIsland.vue'
import { useManage } from '../composables/useManage.js'
import { usersApi } from '../services/system.js'

const { toast, showToast, guard, initialsOf } = useManage()
const users = ref([])
const roles = ref([])
const canScopeAll = ref(false)
const me = ref(0)
const scope = ref('mine')
const query = ref('')
const loading = ref(true)
const error = ref('')
const open = ref(false)
const busy = ref(false)
const formError = ref('')
const confirmDelete = ref(false)
const form = ref(blank())
let debounce = null
let seq = 0

function blank() {
  return { id: 0, name: '', email: '', username: '', password: '', role_id: 0 }
}

const roleTone = (u) => (u.role === 'superadmin' ? 'purple' : u.role === 'admin' ? 'blue' : 'gray')
const roleLabel = (u) => u.role_name || ({ superadmin: 'Süper Yönetici', admin: 'Yönetici', user: 'Kullanıcı' }[u.role] || u.role)
const defaultRole = computed(() => roles.value.find((r) => r.id !== 1 && r.id !== 2)?.id || roles.value.find((r) => r.id !== 1)?.id || 0)

async function load() {
  const current = ++seq
  loading.value = true
  error.value = ''
  const data = await guard(() => usersApi.list(scope.value, query.value.trim()), (m) => { error.value = m })
  if (current !== seq) return
  if (data) {
    users.value = data.users
    roles.value = data.roles
    canScopeAll.value = data.can_scope_all
    me.value = data.me
  }
  loading.value = false
}

watch(query, () => { clearTimeout(debounce); debounce = setTimeout(load, 350) })
function setScope(next) { if (scope.value !== next) { scope.value = next; load() } }

function openForm(user = null) {
  formError.value = ''
  confirmDelete.value = false
  form.value = user
    ? { id: user.id, name: user.name, email: user.email, username: user.username || '', password: '', role_id: user.role_id || 0 }
    : { ...blank(), role_id: defaultRole.value }
  open.value = true
}

async function submit() {
  busy.value = true
  formError.value = ''
  const editing = Boolean(form.value.id)
  const result = await guard(() => usersApi.save({ ...form.value }), (m) => { formError.value = m })
  busy.value = false
  if (!result) return
  open.value = false
  showToast(editing ? 'Kullanıcı güncellendi.' : 'Kullanıcı eklendi.')
  load()
}

async function remove() {
  busy.value = true
  const result = await guard(() => usersApi.remove(form.value.id), (m) => { formError.value = m; confirmDelete.value = false })
  busy.value = false
  if (!result) return
  open.value = false
  showToast('Kullanıcı silindi.')
  load()
}

onMounted(load)
</script>

<template>
  <div class="m-page">
    <TopBar eyebrow="SİSTEM YÖNETİMİ" title="Kullanıcılar" :back="true" />
    <main class="m-main">
      <label class="m-search"><Search :size="18" /><input v-model="query" type="search" placeholder="Ad, e-posta veya kullanıcı adı…" aria-label="Kullanıcı ara" autocomplete="off" /></label>

      <div v-if="canScopeAll" class="m-chips" role="group" aria-label="Kapsam">
        <button type="button" class="m-chip" :class="{ 'is-active': scope === 'mine' }" :aria-pressed="scope === 'mine'" @click="setScope('mine')">Bu kurum</button>
        <button type="button" class="m-chip" :class="{ 'is-active': scope === 'all' }" :aria-pressed="scope === 'all'" @click="setScope('all')">Tüm kullanıcılar</button>
      </div>

      <div v-if="error" class="m-notice" role="alert"><AlertCircle :size="18" /><span>{{ error }}</span><button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button></div>
      <div v-if="loading" class="m-skeletons" aria-hidden="true"><i v-for="n in 5" :key="n"></i></div>
      <template v-else>
        <ul v-if="users.length" class="m-list">
          <li v-for="u in users" :key="u.id">
            <button type="button" class="m-row" @click="openForm(u)">
              <span class="m-avatar">{{ initialsOf(u.name) }}</span>
              <span class="m-row__text"><strong>{{ u.name }}</strong><small>{{ u.email }}<template v-if="scope === 'all' && u.tenant_name"> · {{ u.tenant_name }}</template></small></span>
              <span class="m-badge" :class="`m-tone-${roleTone(u)}`">{{ roleLabel(u) }}</span>
            </button>
          </li>
        </ul>
        <div v-else-if="!error" class="m-empty"><UsersRound :size="28" /><p>{{ query ? 'Aramanıza uyan kullanıcı yok.' : 'Kullanıcı bulunamadı.' }}</p></div>
      </template>
    </main>

    <button v-if="!open" type="button" class="m-fab" aria-label="Kullanıcı ekle" @click="openForm()"><Plus :size="22" /> Ekle</button>

    <ManageSheet v-if="open" :title="form.id ? 'Kullanıcıyı düzenle' : 'Yeni kullanıcı'" @close="open = false">
      <form class="m-card" style="box-shadow:none;padding:0" @submit.prevent="submit">
        <label class="m-field">Ad soyad<input v-model="form.name" type="text" maxlength="255" required autocomplete="off" /></label>
        <label class="m-field">E-posta<input v-model="form.email" type="email" maxlength="255" required autocomplete="off" /></label>
        <label class="m-field">Kullanıcı adı <small>(boşsa e-postadan türetilir)</small><input v-model="form.username" type="text" maxlength="100" autocomplete="off" /></label>
        <label class="m-field">{{ form.id ? 'Yeni şifre (değiştirmek için)' : 'Şifre (en az 8 karakter)' }}<input v-model="form.password" type="password" minlength="8" :required="!form.id" autocomplete="new-password" /></label>
        <label class="m-field">Yetki grubu
          <select v-model.number="form.role_id" required><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option></select>
        </label>
        <p v-if="formError" class="m-notice" role="alert">{{ formError }}</p>
        <div class="m-actions">
          <template v-if="!confirmDelete">
            <button type="submit" class="m-btn m-btn--dark" :disabled="busy">{{ busy ? 'Kaydediliyor…' : 'Kaydet' }}</button>
            <button v-if="form.id && form.id !== me && form.id !== 1" type="button" class="m-btn m-btn--danger" @click="confirmDelete = true"><Trash2 :size="16" /> Sil</button>
          </template>
          <template v-else>
            <p class="m-confirm">Bu kullanıcı silinsin mi? İşlem geri alınamaz.</p>
            <button type="button" class="m-btn m-btn--light" @click="confirmDelete = false">Vazgeç</button>
            <button type="button" class="m-btn m-btn--danger-solid" :disabled="busy" @click="remove">{{ busy ? 'Siliniyor…' : 'Evet, sil' }}</button>
          </template>
        </div>
      </form>
    </ManageSheet>
    <ToastIsland :message="toast" />
  </div>
</template>

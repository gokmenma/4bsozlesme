<script setup>
import { computed, onMounted, ref } from 'vue'
import { AlertCircle, Lock, Plus, RefreshCw, ShieldCheck, Trash2 } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import ManageSheet from '../components/ManageSheet.vue'
import ToastIsland from '../components/ToastIsland.vue'
import { useManage } from '../composables/useManage.js'
import { rolesApi } from '../services/system.js'

const { toast, showToast, guard } = useManage()
const roles = ref([])
const pages = ref([])
const loading = ref(true)
const error = ref('')
const open = ref(false)
const busy = ref(false)
const formError = ref('')
const confirmDelete = ref(false)
const form = ref({ id: 0, name: '', description: '', editable: true, is_system: 0 })
const perms = ref({})
const permsLoading = ref(false)

const groups = computed(() => {
  const map = new Map()
  for (const p of pages.value) {
    if (!map.has(p.category)) map.set(p.category, [])
    map.get(p.category).push(p)
  }
  return [...map.entries()]
})
const lockedPerms = computed(() => form.value.id === 1)

async function load() {
  loading.value = true
  error.value = ''
  const data = await guard(() => rolesApi.list(), (m) => { error.value = m })
  if (data) { roles.value = data.roles; pages.value = data.pages }
  loading.value = false
}

async function openForm(role = null) {
  formError.value = ''
  confirmDelete.value = false
  perms.value = {}
  form.value = role
    ? { id: role.id, name: role.name, description: role.description || '', editable: role.editable, is_system: role.is_system, user_count: role.user_count }
    : { id: 0, name: '', description: '', editable: true, is_system: 0, user_count: 0 }
  open.value = true
  if (role) {
    permsLoading.value = true
    const data = await guard(() => rolesApi.permissions(role.id), (m) => { formError.value = m })
    if (data) perms.value = data.permissions
    permsLoading.value = false
  }
}

async function submit() {
  busy.value = true
  formError.value = ''
  const editing = Boolean(form.value.id)
  const result = await guard(async () => {
    const saved = await rolesApi.save({ id: form.value.id, name: form.value.name, description: form.value.description })
    if (editing && form.value.editable && form.value.id !== 1) {
      await rolesApi.savePermissions(form.value.id, perms.value)
    }
    return saved
  }, (m) => { formError.value = m })
  busy.value = false
  if (!result) return
  open.value = false
  showToast(editing ? 'Yetki grubu kaydedildi.' : 'Yetki grubu eklendi.')
  load()
}

async function remove() {
  busy.value = true
  const result = await guard(() => rolesApi.remove(form.value.id), (m) => { formError.value = m; confirmDelete.value = false })
  busy.value = false
  if (!result) return
  open.value = false
  showToast('Yetki grubu silindi.')
  load()
}

onMounted(load)
</script>

<template>
  <div class="m-page">
    <TopBar eyebrow="SİSTEM YÖNETİMİ" title="Yetki Grupları" :back="true" />
    <main class="m-main">
      <div v-if="error" class="m-notice" role="alert"><AlertCircle :size="18" /><span>{{ error }}</span><button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button></div>
      <div v-if="loading" class="m-skeletons" aria-hidden="true"><i v-for="n in 4" :key="n"></i></div>
      <ul v-else-if="roles.length" class="m-list">
        <li v-for="r in roles" :key="r.id">
          <button type="button" class="m-row" @click="openForm(r)">
            <span class="m-avatar"><component :is="r.is_system ? Lock : ShieldCheck" :size="18" /></span>
            <span class="m-row__text"><strong>{{ r.name }}</strong><small>{{ r.description || 'Açıklama yok' }}</small></span>
            <span class="m-badge m-tone-gray">{{ r.user_count }} kişi</span>
          </button>
        </li>
      </ul>
      <div v-else-if="!error" class="m-empty"><ShieldCheck :size="28" /><p>Yetki grubu yok.</p></div>
    </main>

    <button v-if="!open" type="button" class="m-fab" aria-label="Yetki grubu ekle" @click="openForm()"><Plus :size="22" /> Ekle</button>

    <ManageSheet v-if="open" :title="form.id ? form.name || 'Yetki grubu' : 'Yeni yetki grubu'" @close="open = false">
      <form class="m-card" style="box-shadow:none;padding:0" @submit.prevent="submit">
        <p v-if="form.id && !form.editable" class="m-hint">Bu sistem grubunu yalnızca süper yönetici düzenleyebilir.</p>
        <label class="m-field">Grup adı<input v-model="form.name" type="text" maxlength="100" required :disabled="!form.editable || form.id === 1" /></label>
        <label class="m-field">Açıklama<input v-model="form.description" type="text" maxlength="255" :disabled="!form.editable" /></label>

        <template v-if="form.id">
          <p v-if="lockedPerms" class="m-hint">Süper yönetici her sayfaya erişir, izinleri değiştirilemez.</p>
          <p v-else-if="permsLoading" class="m-hint">İzinler yükleniyor…</p>
          <template v-else>
            <section v-for="[category, items] in groups" :key="category" class="perm-group">
              <h3>{{ category }}</h3>
              <label v-for="p in items" :key="p.route" class="m-switch"><span>{{ p.title }}</span><input v-model="perms[p.route]" type="checkbox" :true-value="1" :false-value="0" :disabled="!form.editable" /></label>
            </section>
          </template>
        </template>

        <p v-if="formError" class="m-notice" role="alert">{{ formError }}</p>
        <div class="m-actions">
          <template v-if="!confirmDelete">
            <button type="submit" class="m-btn m-btn--dark" :disabled="busy || (form.id && !form.editable)">{{ busy ? 'Kaydediliyor…' : 'Kaydet' }}</button>
            <button v-if="form.id && form.editable && !form.is_system && form.id !== 1" type="button" class="m-btn m-btn--danger" @click="confirmDelete = true"><Trash2 :size="16" /> Sil</button>
          </template>
          <template v-else>
            <p class="m-confirm">Bu yetki grubu silinsin mi? İşlem geri alınamaz.</p>
            <button type="button" class="m-btn m-btn--light" @click="confirmDelete = false">Vazgeç</button>
            <button type="button" class="m-btn m-btn--danger-solid" :disabled="busy" @click="remove">{{ busy ? 'Siliniyor…' : 'Evet, sil' }}</button>
          </template>
        </div>
      </form>
    </ManageSheet>
    <ToastIsland :message="toast" />
  </div>
</template>

<style scoped>
.perm-group { display: grid; gap: 2px; padding: 8px 14px; border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); }
.perm-group h3 { margin: 4px 0; color: var(--ds-muted); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; }
</style>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { AlertCircle, CreditCard, Plus, RefreshCw, Trash2 } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import ManageSheet from '../components/ManageSheet.vue'
import ToastIsland from '../components/ToastIsland.vue'
import { useManage } from '../composables/useManage.js'
import { subscriptionApi } from '../services/system.js'

const { toast, showToast, guard, formatDate } = useManage()
const plans = ref([])
const history = ref([])
const stats = ref({ total_revenue: 0, active_count: 0, pending_count: 0 })
const tab = ref('requests')
const loading = ref(true)
const error = ref('')
const sheet = ref(null)
const busy = ref(false)
const formError = ref('')
const confirmDelete = ref(false)
const plan = ref({ id: 0, name: '', price: '', duration_days: 30, features: '' })

const money = (v) => new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(Number(v) || 0)
const pending = computed(() => history.value.filter((h) => h.payment_status === 'pending'))
const rows = computed(() => (tab.value === 'requests' ? pending.value : history.value))
const stateOf = (h) => (h.payment_status === 'pending' ? ['Onay bekliyor', 'amber'] : h.status === 'active' ? ['Aktif', 'emerald'] : h.payment_status === 'failed' ? ['Reddedildi', 'red'] : ['Pasif', 'gray'])

async function load() {
  loading.value = true
  error.value = ''
  const data = await guard(() => subscriptionApi.list(), (m) => { error.value = m })
  if (data) { plans.value = data.plans; history.value = data.history; stats.value = data.stats }
  loading.value = false
}

function openItem(h) {
  formError.value = ''
  confirmDelete.value = false
  sheet.value = { type: 'sub', item: h }
}

function openPlan(p = null) {
  formError.value = ''
  confirmDelete.value = false
  plan.value = p ? { id: p.id, name: p.name, price: String(p.price), duration_days: Number(p.duration_days), features: p.features || '' } : { id: 0, name: '', price: '', duration_days: 30, features: '' }
  sheet.value = { type: 'plan' }
}

async function run(fn, message) {
  busy.value = true
  formError.value = ''
  const result = await guard(fn, (m) => { formError.value = m; confirmDelete.value = false })
  busy.value = false
  if (!result) return
  sheet.value = null
  showToast(message)
  load()
}

const decide = (action, message) => run(() => subscriptionApi.decide(action, sheet.value.item.id), message)
const savePlan = () => run(() => subscriptionApi.savePlan({ ...plan.value }), plan.value.id ? 'Paket güncellendi.' : 'Paket eklendi.')
const removePlan = () => run(() => subscriptionApi.removePlan(plan.value.id), 'Paket kaldırıldı.')

onMounted(load)
</script>

<template>
  <div class="m-page">
    <TopBar eyebrow="SİSTEM YÖNETİMİ" title="Abonelik" :back="true" />
    <main class="m-main">
      <div v-if="error" class="m-notice" role="alert"><AlertCircle :size="18" /><span>{{ error }}</span><button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button></div>
      <div v-if="loading" class="m-skeletons" aria-hidden="true"><i v-for="n in 4" :key="n"></i></div>
      <template v-else-if="!error">
        <div class="m-stats">
          <div class="m-stat"><strong>{{ money(stats.total_revenue) }}</strong><small>Tahsil edilen</small></div>
          <div class="m-stat"><strong>{{ stats.active_count }}</strong><small>Aktif</small></div>
          <div class="m-stat"><strong>{{ stats.pending_count }}</strong><small>Bekleyen</small></div>
        </div>

        <div class="m-chips" role="tablist">
          <button type="button" role="tab" class="m-chip" :class="{ 'is-active': tab === 'requests' }" :aria-selected="tab === 'requests'" @click="tab = 'requests'">Talepler <b>{{ pending.length }}</b></button>
          <button type="button" role="tab" class="m-chip" :class="{ 'is-active': tab === 'history' }" :aria-selected="tab === 'history'" @click="tab = 'history'">Geçmiş <b>{{ history.length }}</b></button>
          <button type="button" role="tab" class="m-chip" :class="{ 'is-active': tab === 'plans' }" :aria-selected="tab === 'plans'" @click="tab = 'plans'">Paketler <b>{{ plans.length }}</b></button>
        </div>

        <ul v-if="tab === 'plans'" class="m-list">
          <li v-for="p in plans" :key="p.id">
            <button type="button" class="m-row" @click="openPlan(p)">
              <span class="m-avatar"><CreditCard :size="18" /></span>
              <span class="m-row__text"><strong>{{ p.name }}</strong><small>{{ p.duration_days }} gün · {{ p.features || 'Özellik tanımsız' }}</small></span>
              <span class="m-badge m-tone-blue">{{ money(p.price) }}</span>
            </button>
          </li>
          <li v-if="!plans.length" class="m-empty"><CreditCard :size="28" /><p>Aktif paket yok.</p></li>
        </ul>
        <ul v-else-if="rows.length" class="m-list">
          <li v-for="h in rows" :key="h.id">
            <button type="button" class="m-row" @click="openItem(h)">
              <span class="m-row__text"><strong>{{ h.tenant_name || 'Kurum yok' }} · {{ h.plan_name }}</strong><small>{{ money(h.amount) }} · {{ formatDate(h.start_date) }} – {{ formatDate(h.end_date) }}</small></span>
              <span class="m-badge" :class="`m-tone-${stateOf(h)[1]}`">{{ stateOf(h)[0] }}</span>
            </button>
          </li>
        </ul>
        <div v-else class="m-empty"><CreditCard :size="28" /><p>{{ tab === 'requests' ? 'Onay bekleyen talep yok.' : 'Abonelik kaydı yok.' }}</p></div>
      </template>
    </main>

    <button v-if="tab === 'plans' && !sheet" type="button" class="m-fab" aria-label="Paket ekle" @click="openPlan()"><Plus :size="22" /> Paket</button>

    <ManageSheet v-if="sheet?.type === 'sub'" title="Abonelik talebi" @close="sheet = null">
      <div class="m-card" style="box-shadow:none;padding:0">
        <p class="m-hint">{{ sheet.item.tenant_name || 'Kurum yok' }} · {{ sheet.item.plan_name }} · {{ money(sheet.item.amount) }}<br />Talep eden: {{ sheet.item.user_name || '—' }} · {{ formatDate(sheet.item.created_at) }}</p>
        <p v-if="formError" class="m-notice" role="alert">{{ formError }}</p>
        <div class="m-actions">
          <template v-if="!confirmDelete">
            <button v-if="sheet.item.payment_status === 'pending'" type="button" class="m-btn m-btn--ok" :disabled="busy" @click="decide('approve', 'Abonelik onaylandı.')">Onayla</button>
            <button v-if="sheet.item.payment_status === 'pending'" type="button" class="m-btn m-btn--light" :disabled="busy" @click="decide('reject', 'Talep reddedildi.')">Reddet</button>
            <button type="button" class="m-btn m-btn--danger" @click="confirmDelete = true"><Trash2 :size="16" /> Sil</button>
          </template>
          <template v-else>
            <p class="m-confirm">Bu kayıt silinsin mi? İşlem geri alınamaz.</p>
            <button type="button" class="m-btn m-btn--light" @click="confirmDelete = false">Vazgeç</button>
            <button type="button" class="m-btn m-btn--danger-solid" :disabled="busy" @click="decide('delete', 'Kayıt silindi.')">Evet, sil</button>
          </template>
        </div>
      </div>
    </ManageSheet>

    <ManageSheet v-if="sheet?.type === 'plan'" :title="plan.id ? 'Paketi düzenle' : 'Yeni paket'" @close="sheet = null">
      <form class="m-card" style="box-shadow:none;padding:0" @submit.prevent="savePlan">
        <label class="m-field">Paket adı<input v-model="plan.name" type="text" maxlength="100" required /></label>
        <div class="m-two">
          <label class="m-field">Fiyat (TL)<input v-model="plan.price" type="text" inputmode="decimal" required /></label>
          <label class="m-field">Süre (gün)<input v-model.number="plan.duration_days" type="number" min="1" max="3650" required /></label>
        </div>
        <label class="m-field">Özellikler <small>(virgülle ayırın)</small><textarea v-model="plan.features" rows="3" maxlength="1000"></textarea></label>
        <p v-if="formError" class="m-notice" role="alert">{{ formError }}</p>
        <div class="m-actions">
          <template v-if="!confirmDelete">
            <button type="submit" class="m-btn m-btn--dark" :disabled="busy">{{ busy ? 'Kaydediliyor…' : 'Kaydet' }}</button>
            <button v-if="plan.id" type="button" class="m-btn m-btn--danger" @click="confirmDelete = true"><Trash2 :size="16" /> Kaldır</button>
          </template>
          <template v-else>
            <p class="m-confirm">Bu paket kaldırılsın mı? Yeni satın alımlara kapanır.</p>
            <button type="button" class="m-btn m-btn--light" @click="confirmDelete = false">Vazgeç</button>
            <button type="button" class="m-btn m-btn--danger-solid" :disabled="busy" @click="removePlan">Evet, kaldır</button>
          </template>
        </div>
      </form>
    </ManageSheet>
    <ToastIsland :message="toast" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { AlertCircle, Bell, Mail, MessageSquareText, RefreshCw, Save, Send } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import ToastIsland from '../components/ToastIsland.vue'
import { useManage } from '../composables/useManage.js'
import { settingsApi } from '../services/system.js'

const { toast, showToast, guard } = useManage()
const loading = ref(true)
const saving = ref(false)
const testing = ref(false)
const error = ref('')
const formError = ref('')
const form = ref({})
const flags = ref({ has_sms_api_key: false, has_smtp_pass: false })
const original = ref('')
const testEmail = ref('')

const dirty = computed(() => JSON.stringify(form.value) !== original.value)

function apply(s) {
  const { has_sms_api_key, has_smtp_pass, ...rest } = s
  flags.value = { has_sms_api_key, has_smtp_pass }
  form.value = { ...rest, sms_api_key: '', smtp_pass: '' }
  original.value = JSON.stringify(form.value)
}

async function load() {
  loading.value = true
  error.value = ''
  const data = await guard(() => settingsApi.get(), (m) => { error.value = m })
  if (data) apply(data.settings)
  loading.value = false
}

async function submit() {
  saving.value = true
  formError.value = ''
  const result = await guard(() => settingsApi.save({ ...form.value }), (m) => { formError.value = m })
  saving.value = false
  if (!result) return
  apply(result.settings)
  showToast('Ayarlar kaydedildi.')
}

async function sendTest() {
  testing.value = true
  formError.value = ''
  const result = await guard(() => settingsApi.testMail(testEmail.value), (m) => { formError.value = m })
  testing.value = false
  if (result) showToast('Test e-postası gönderildi.')
}

onMounted(load)
</script>

<template>
  <div class="m-page">
    <TopBar eyebrow="SİSTEM YÖNETİMİ" title="Sistem Ayarları" :back="true" />
    <main class="m-main">
      <div v-if="error" class="m-notice" role="alert"><AlertCircle :size="18" /><span>{{ error }}</span><button type="button" @click="load"><RefreshCw :size="15" /> Yenile</button></div>
      <div v-if="loading" class="m-skeletons" aria-hidden="true"><i v-for="n in 3" :key="n" style="height:140px"></i></div>

      <form v-else-if="!error" style="display:grid;gap:14px" @submit.prevent="submit">
        <section class="m-card">
          <h2><Bell :size="17" /> Bildirimler</h2>
          <label class="m-switch"><span>Kadro bildirimleri aktif</span><input v-model="form.kadro_bildirim_aktif" type="checkbox" /></label>
        </section>

        <section class="m-card">
          <h2><MessageSquareText :size="17" /> SMS</h2>
          <label class="m-switch"><span>SMS gönderimi aktif</span><input v-model="form.sms_active" type="checkbox" /></label>
          <label class="m-field">Entegratör<select v-model="form.sms_entegrator"><option value="NETGSM">NETGSM</option><option value="SMSMAX">SMSMAX</option><option value="DIGER">Diğer</option></select></label>
          <label class="m-field">API adresi<input v-model="form.sms_api_url" type="url" maxlength="500" placeholder="https://" autocomplete="off" /></label>
          <label class="m-field">API anahtarı <small>{{ flags.has_sms_api_key ? '(kayıtlı — değiştirmek için yazın)' : '' }}</small><input v-model="form.sms_api_key" type="password" maxlength="500" autocomplete="new-password" :placeholder="flags.has_sms_api_key ? '••••••••' : ''" /></label>
          <label class="m-field">Gönderici başlığı<input v-model="form.sms_sender" type="text" maxlength="50" autocomplete="off" /></label>
        </section>

        <section class="m-card">
          <h2><Mail :size="17" /> E-posta (SMTP)</h2>
          <div class="m-two">
            <label class="m-field">Sunucu<input v-model="form.smtp_host" type="text" maxlength="255" autocomplete="off" /></label>
            <label class="m-field">Port<input v-model="form.smtp_port" type="text" inputmode="numeric" placeholder="587" /></label>
          </div>
          <label class="m-field">Kullanıcı adı<input v-model="form.smtp_user" type="text" maxlength="255" autocomplete="off" /></label>
          <label class="m-field">Şifre <small>{{ flags.has_smtp_pass ? '(kayıtlı — değiştirmek için yazın)' : '' }}</small><input v-model="form.smtp_pass" type="password" maxlength="500" autocomplete="new-password" :placeholder="flags.has_smtp_pass ? '••••••••' : ''" /></label>
          <label class="m-field">Gönderen e-posta<input v-model="form.smtp_from_email" type="email" maxlength="255" autocomplete="off" /></label>
          <label class="m-field">Gönderen adı<input v-model="form.smtp_from_name" type="text" maxlength="255" autocomplete="off" /></label>
          <div class="m-two" style="align-items:end">
            <label class="m-field">Test alıcısı<input v-model="testEmail" type="email" autocomplete="off" placeholder="ornek@mail.com" /></label>
            <button type="button" class="m-btn m-btn--light" :disabled="testing || dirty || !testEmail" :title="dirty ? 'Önce kaydedin' : ''" @click="sendTest"><Send :size="16" /> {{ testing ? 'Gönderiliyor…' : 'Test gönder' }}</button>
          </div>
          <small v-if="dirty" style="color:var(--ds-muted)">Test için önce değişiklikleri kaydedin.</small>
        </section>

        <p v-if="formError" class="m-notice" role="alert">{{ formError }}</p>
        <button type="submit" class="m-btn m-btn--primary" style="flex:none;min-height:52px" :disabled="saving || !dirty"><Save :size="18" /> {{ saving ? 'Kaydediliyor…' : 'Kaydet' }}</button>
      </form>
    </main>
    <ToastIsland :message="toast" />
  </div>
</template>

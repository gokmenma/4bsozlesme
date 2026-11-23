<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { Check, Copy, MessageCircle, Send, X } from '@lucide/vue'
import { TIP_LABEL, getEvrakOptions, sendEvrakPackage } from '../services/evrak.js'

const props = defineProps({ person: { type: Object, required: true } })
const emit = defineEmits(['close', 'sent'])

const root = ref(null)
const loading = ref(true)
const sending = ref(false)
const error = ref('')
const forms = ref([])
const selected = ref([])
const phone = ref(props.person.telefon || '')
const channel = ref(props.person.telefon ? 'sms' : 'link')
const email = ref('')
const days = ref(7)
const result = ref(null)
const copied = ref(false)
let previousOverflow = ''

const channels = computed(() => [
  { key: 'sms', label: 'SMS', hint: phone.value || 'Telefon yok' },
  { key: 'mail', label: 'E-posta', hint: 'Adres girilecek' },
  { key: 'link', label: 'Bağlantı', hint: 'Kopyala / WhatsApp' },
])
const whatsappUrl = computed(() => result.value ? `https://wa.me/?text=${encodeURIComponent('İşe giriş evraklarınızı doldurmak için bağlantı: ' + result.value.link)}` : '#')

function toggle(id) {
  selected.value = selected.value.includes(id) ? selected.value.filter((x) => x !== id) : [...selected.value, id]
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await getEvrakOptions(props.person.id)
    forms.value = data.formlar
    selected.value = data.formlar.map((f) => f.id)
    phone.value = data.personel.telefon || ''
    if (!phone.value && channel.value === 'sms') channel.value = 'link'
  } catch (reason) {
    error.value = reason?.message || 'Formlar yüklenemedi.'
  } finally {
    loading.value = false
  }
}

async function send() {
  if (!selected.value.length) { error.value = 'En az bir form seçmelisiniz.'; return }
  sending.value = true
  error.value = ''
  try {
    result.value = await sendEvrakPackage({
      personel_id: props.person.id,
      form_ids: selected.value,
      kanal: channel.value,
      email: channel.value === 'mail' ? email.value.trim() : '',
      gun: Number(days.value),
    })
    emit('sent', result.value)
  } catch (reason) {
    error.value = reason?.message || 'Gönderilemedi.'
  } finally {
    sending.value = false
  }
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(result.value.link)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    error.value = 'Panoya kopyalanamadı, bağlantıyı elle seçin.'
  }
}

function onKey(event) {
  if (event.key === 'Escape') { event.stopPropagation(); emit('close') }
}

onMounted(async () => {
  previousOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
  await nextTick()
  root.value?.focus()
  load()
})
onBeforeUnmount(() => { document.body.style.overflow = previousOverflow })
</script>

<template>
  <Teleport to="body">
    <div class="ev-layer" @keydown="onKey" @click.self="emit('close')">
      <section ref="root" class="ev-sheet" role="dialog" aria-modal="true" aria-label="İşe giriş evrakı gönder" tabindex="-1">
        <header class="ev-head">
          <div>
            <h2>İşe Giriş Evrakı Gönder</h2>
            <p>{{ person.ad_soyad }}</p>
          </div>
          <button type="button" class="ev-x" aria-label="Kapat" @click="emit('close')"><X :size="18" /></button>
        </header>

        <div class="ev-body">
          <template v-if="result">
            <p class="ev-note" :class="{ 'ev-note--ok': result.gonderim?.success }">{{ result.message }}</p>
            <input class="ev-input" type="text" readonly :value="result.link" aria-label="Evrak bağlantısı" @focus="$event.target.select()">
            <div class="ev-row">
              <button type="button" class="ev-btn ev-btn--light" @click="copyLink"><component :is="copied ? Check : Copy" :size="16" /> {{ copied ? 'Kopyalandı' : 'Kopyala' }}</button>
              <a class="ev-btn ev-btn--light" :href="whatsappUrl" target="_blank" rel="noopener noreferrer"><MessageCircle :size="16" /> WhatsApp</a>
            </div>
          </template>

          <template v-else>
            <p v-if="loading" class="ev-muted">Formlar yükleniyor…</p>
            <p v-else-if="error && !forms.length" class="ev-note" role="alert">{{ error }}</p>
            <p v-else-if="!forms.length" class="ev-note">Aktif evrak formu yok. Masaüstünden Evrak Formları sayfasında hazır şablonları ekleyebilirsiniz.</p>
            <template v-else>
              <fieldset class="ev-group">
                <legend>Doldurulacak Formlar</legend>
                <label v-for="f in forms" :key="f.id" class="ev-check">
                  <input type="checkbox" :checked="selected.includes(f.id)" @change="toggle(f.id)">
                  <span><strong>{{ f.ad }}</strong><small>{{ TIP_LABEL[f.tip] || f.tip }} · {{ f.alan_sayisi }} alan</small></span>
                </label>
              </fieldset>

              <fieldset class="ev-group">
                <legend>Gönderim Şekli</legend>
                <div class="ev-channels">
                  <label v-for="c in channels" :key="c.key" class="ev-chan" :class="{ 'is-on': channel === c.key }">
                    <input v-model="channel" type="radio" name="kanal" :value="c.key" :disabled="c.key === 'sms' && !phone">
                    <strong>{{ c.label }}</strong><small>{{ c.hint }}</small>
                  </label>
                </div>
              </fieldset>

              <label v-if="channel === 'mail'" class="ev-field">E-posta Adresi
                <input v-model="email" class="ev-input" type="email" inputmode="email" autocomplete="off" placeholder="ornek@eposta.com">
              </label>

              <label class="ev-field">Bağlantı Geçerlilik Süresi
                <select v-model="days" class="ev-input">
                  <option :value="3">3 gün</option><option :value="7">7 gün</option><option :value="14">14 gün</option><option :value="30">30 gün</option>
                </select>
              </label>

              <p class="ev-muted">Personel bağlantıyı açtığında T.C. Kimlik numarasının son 4 hanesiyle doğrulama yapar. Bu personele ait açık bir paket varsa iptal edilip yenisi oluşturulur.</p>
              <p v-if="error" class="ev-note" role="alert">{{ error }}</p>
            </template>
          </template>
        </div>

        <footer class="ev-foot">
          <button type="button" class="ev-btn ev-btn--light" @click="emit('close')">Kapat</button>
          <button v-if="!result && forms.length" type="button" class="ev-btn ev-btn--dark" :disabled="sending || !selected.length" @click="send"><Send :size="16" /> {{ sending ? 'Gönderiliyor…' : 'Gönder' }}</button>
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<style scoped>
.ev-layer { position: fixed; inset: 0; z-index: 130; display: flex; align-items: flex-end; justify-content: center; background: rgba(15, 18, 24, .5); }
.ev-sheet { width: min(100%, 520px); max-height: 90svh; display: flex; flex-direction: column; border-radius: 28px 28px 0 0; background: var(--ds-surface); box-shadow: 0 -16px 40px rgba(0, 0, 0, .25); outline: 0; }
.ev-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 18px 16px 8px; }
.ev-head h2 { margin: 0; font-size: 18px; font-weight: 800; color: var(--ds-ink); }
.ev-head p { margin: 2px 0 0; font-size: 13px; color: var(--ds-muted); }
.ev-x { display: grid; place-items: center; width: 40px; height: 40px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); color: var(--ds-ink); cursor: pointer; }
.ev-body { flex: 1; min-height: 0; overflow-y: auto; display: grid; gap: 14px; align-content: start; padding: 8px 16px 12px; }
.ev-foot { display: flex; gap: 8px; padding: 12px 16px calc(18px + var(--ds-safe-bottom, 0px)); border-top: 1px solid var(--ds-border-subtle); }
.ev-group { margin: 0; padding: 0; border: 0; display: grid; gap: 8px; min-width: 0; }
.ev-group legend { padding: 0; margin-bottom: 6px; font-size: 12px; font-weight: 800; color: var(--ds-muted); }
.ev-check { display: flex; gap: 10px; align-items: flex-start; padding: 10px 12px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-border-subtle); background: var(--ds-surface-subtle); }
.ev-check input { width: 20px; height: 20px; margin-top: 2px; flex-shrink: 0; }
.ev-check span { display: grid; gap: 2px; min-width: 0; }
.ev-check strong { font-size: 14px; }
.ev-check small, .ev-muted { color: var(--ds-muted); font-size: 12px; margin: 0; }
.ev-channels { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.ev-chan { display: grid; gap: 2px; justify-items: center; text-align: center; padding: 10px 6px; border-radius: var(--ds-radius-sm); border: 2px solid var(--ds-border-subtle); cursor: pointer; }
.ev-chan.is-on { border-color: var(--ds-surface-dark); }
.ev-chan input { position: absolute; opacity: 0; pointer-events: none; }
.ev-chan strong { font-size: 13px; }
.ev-chan small { font-size: 10px; color: var(--ds-muted); overflow-wrap: anywhere; }
.ev-field { display: grid; gap: 6px; font-size: 12px; font-weight: 700; color: var(--ds-muted); }
.ev-input { width: 100%; min-height: 48px; padding: 0 12px; border: 1px solid var(--ds-border-subtle); border-radius: 12px; background: var(--ds-surface-subtle); font: 600 16px var(--ds-font-main); color: var(--ds-ink); }
.ev-note { margin: 0; padding: 12px 14px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.ev-note--ok { border-color: #a7f3d0; background: #ecfdf5; color: #047857; }
.ev-row { display: flex; gap: 8px; }
.ev-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 48px; border: 0; border-radius: 999px; font-weight: 700; font-size: 14px; cursor: pointer; text-decoration: none; }
.ev-btn--dark { background: var(--ds-surface-dark); color: #fff; }
.ev-btn--light { background: var(--ds-surface-subtle); color: var(--ds-ink); }
.ev-btn:disabled { opacity: .6; }
</style>

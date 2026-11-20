<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { Mic, Square, X } from '@lucide/vue'
import { STATUS_META, getWageOptions, savePersonnel } from '../services/personnel.js'
import { parsePersonnelSpeech, useVoiceInput } from '../composables/useVoiceInput.js'

const props = defineProps({ person: { type: Object, default: null }, prefill: { type: Object, default: null } })
const emit = defineEmits(['close', 'saved', 'expired'])

const today = new Date().toISOString().slice(0, 10)
const form = reactive({
  id: props.person?.id || null,
  ad_soyad: props.person?.ad_soyad || props.prefill?.ad_soyad || '',
  tc_kimlik: props.person?.tc_kimlik || props.prefill?.tc_kimlik || '',
  ucret_id: props.person?.ucret_id ? String(props.person.ucret_id) : '',
  durum: props.person?.durum || 'aktif',
  cinsiyet: props.person?.cinsiyet || 'erkek',
  telefon: props.person?.telefon || '',
  meslek_kodu: props.person?.meslek_kodu || '',
  goreve_baslama_tarihi: props.person?.goreve_baslama_tarihi?.slice(0, 10) || today,
  ayrilma_tarihi: props.person?.ayrilma_tarihi?.slice(0, 10) || '',
})
const wages = ref([])
const busy = ref(false)
const error = ref('')
const root = ref(null)
const isEdit = computed(() => Boolean(props.person))

const voice = useVoiceInput((speech) => {
  const parsed = parsePersonnelSpeech(speech, wages.value)
  Object.assign(form, parsed)
  const filled = Object.keys(parsed).length
  voiceNote.value = filled ? `${filled} alan dolduruldu, kontrol edip kaydedin.` : 'Alan anlaşılamadı, tekrar deneyin.'
})
const voiceNote = ref('')

function toggleVoice() {
  voiceNote.value = ''
  if (voice.preparing.value) return
  if (voice.listening.value) voice.stop()
  else voice.start()
}

const wageLabel =(w) => `${w.unvan} · ${w.ogrenim} · ${w.kidem_yili}`

async function submit() {
  error.value = ''
  busy.value = true
  try {
    await savePersonnel({ ...form, ucret_id: Number(form.ucret_id) })
    emit('saved')
  } catch (reason) {
    if (reason?.status === 401) emit('expired')
    error.value = reason?.message || 'Kayıt yapılamadı.'
  } finally {
    busy.value = false
  }
}

function handleKeydown(event) {
  if (event.key === 'Escape') emit('close')
}

onMounted(async () => {
  await nextTick()
  root.value?.querySelector('input')?.focus()
  try {
    wages.value = (await getWageOptions()).wages
  } catch (reason) {
    if (reason?.status === 401) emit('expired')
    error.value = reason?.message || 'Ücret listesi alınamadı.'
  }
})
</script>

<template>
  <div class="layer" @keydown="handleKeydown" @click.self="emit('close')">
    <form ref="root" class="sheet" role="dialog" aria-modal="true" :aria-label="isEdit ? 'Personel düzenle' : 'Personel ekle'" @submit.prevent="submit">
      <header class="sheet__head">
        <div>
          <span class="eyebrow">{{ isEdit ? 'DÜZENLE' : 'YENİ KAYIT' }}</span>
          <h2>{{ isEdit ? 'Personeli düzenle' : 'Personel ekle' }}</h2>
        </div>
        <button type="button" class="close" aria-label="Kapat" @click="emit('close')"><X :size="19" /></button>
      </header>

      <div v-if="!isEdit && voice.supported" class="voice">
        <button type="button" class="voice__btn" :class="{ 'is-on': voice.listening.value }" :aria-pressed="voice.listening.value" @click="toggleVoice">
          <component :is="voice.listening.value ? Square : Mic" :size="18" />
          {{ voice.preparing.value ? 'Ses paketi indiriliyor…' : voice.listening.value ? 'Dinleniyor… durdur' : 'Sesli doldur' }}
        </button>
        <p class="voice__hint" aria-live="polite">{{ voice.problem.value || voice.transcript.value || voiceNote || 'Ses cihazda işlenir. TC kimlik numarasını elle yazın veya kimlik tarayın. Örn: “Ahmet Yılmaz, telefon 05321234567, erkek”' }}</p>
      </div>

      <div class="fields">
        <label>Ad soyad
          <input v-model="form.ad_soyad" type="text" required maxlength="255" autocomplete="off" />
        </label>
        <label>TC Kimlik No
          <input v-model="form.tc_kimlik" type="text" inputmode="numeric" pattern="\d{11}" maxlength="11" required autocomplete="off" />
        </label>
        <label>Unvan / Ücret
          <select v-model="form.ucret_id" required>
            <option value="" disabled>{{ wages.length ? 'Seçin' : 'Yükleniyor…' }}</option>
            <option v-for="w in wages" :key="w.id" :value="String(w.id)">{{ wageLabel(w) }}</option>
          </select>
        </label>
        <div class="two">
          <label>Durum
            <select v-model="form.durum">
              <option v-for="(meta, key) in STATUS_META" :key="key" :value="key">{{ meta.label }}</option>
            </select>
          </label>
          <label>Cinsiyet
            <select v-model="form.cinsiyet">
              <option value="erkek">Erkek</option>
              <option value="kadin">Kadın</option>
              <option value="diger">Diğer</option>
            </select>
          </label>
        </div>
        <label>Telefon
          <input v-model="form.telefon" type="tel" inputmode="tel" maxlength="30" autocomplete="off" />
        </label>
        <label>Meslek kodu
          <input v-model="form.meslek_kodu" type="text" maxlength="50" autocomplete="off" />
        </label>
        <div class="two">
          <label>Göreve başlama
            <input v-model="form.goreve_baslama_tarihi" type="date" required />
          </label>
          <label>Ayrılma tarihi
            <input v-model="form.ayrilma_tarihi" type="date" />
          </label>
        </div>
      </div>

      <p v-if="error" class="error" role="alert">{{ error }}</p>

      <button type="submit" class="submit" :disabled="busy">{{ busy ? 'Kaydediliyor…' : isEdit ? 'Değişiklikleri kaydet' : 'Personeli ekle' }}</button>
    </form>
  </div>
</template>

<style scoped>
.layer { position: fixed; inset: 0; z-index: 90; display: flex; align-items: flex-end; justify-content: center; background: rgba(15, 18, 24, .5); }
.sheet { width: min(100%, 520px); max-height: 92svh; overflow-y: auto; padding: 18px 16px calc(18px + var(--ds-safe-bottom)); border-radius: 28px 28px 0 0; background: var(--ds-surface); box-shadow: 0 -16px 40px rgba(0, 0, 0, .25); animation: rise .28s cubic-bezier(.16, 1, .3, 1); }
@keyframes rise { from { transform: translateY(100%); } }
.sheet__head { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.eyebrow { color: var(--ds-muted); font-size: 10px; font-weight: 800; letter-spacing: .12em; }
h2 { margin: 2px 0 0; font-size: 21px; font-weight: 800; }
.close { display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); cursor: pointer; }
.voice { display: grid; gap: 8px; margin-bottom: 14px; }
.voice__btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; border: 1px solid var(--ds-border-subtle); border-radius: 999px; background: var(--ds-surface-subtle); color: var(--ds-ink); font: 800 14px var(--ds-font-main); cursor: pointer; }
.voice__btn.is-on { border-color: var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); }
.voice__hint { margin: 0; color: var(--ds-muted); font-size: 12px; line-height: 1.4; }
.fields { display: grid; gap: 12px; }
.two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
label { display: grid; gap: 6px; color: var(--ds-muted); font-size: 12px; font-weight: 700; min-width: 0; }
input, select { width: 100%; min-height: 48px; padding: 0 14px; border: 1px solid var(--ds-border-subtle); border-radius: var(--ds-radius-sm); background: var(--ds-surface-subtle); color: var(--ds-ink); font: 600 16px var(--ds-font-main); }
input:focus-visible, select:focus-visible { outline: 2px solid var(--ds-focus); outline-offset: 1px; }
.error { margin: 12px 0 0; padding: 10px 12px; border-radius: var(--ds-radius-sm); border: 1px solid var(--ds-danger-border); background: var(--ds-danger-bg); color: var(--ds-danger-ink); font-size: 13px; }
.submit { width: 100%; min-height: 50px; margin-top: 16px; border: 0; border-radius: 999px; background: var(--ds-primary); color: #fff; font-weight: 800; font-size: 15px; cursor: pointer; }
.submit:disabled { opacity: .6; }
@media (prefers-reduced-motion: reduce) { .sheet { animation: none; } }
</style>

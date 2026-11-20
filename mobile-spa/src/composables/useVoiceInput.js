import { onBeforeUnmount, ref } from 'vue'

const Recognition = typeof window !== 'undefined' ? window.SpeechRecognition || window.webkitSpeechRecognition : null

const LANG = 'tr-TR'
const localCapable = Boolean(Recognition) && 'processLocally' in Recognition.prototype && typeof Recognition.available === 'function'

export function useVoiceInput(onFinal) {
  const supported = localCapable
  const preparing = ref(false)
  const listening = ref(false)
  const transcript = ref('')
  const problem = ref('')
  let recognition = null

  async function ensureLocalPack() {
    const options = { langs: [LANG], processLocally: true }
    let state = await Recognition.available(options)
    if (state === 'unavailable') return false
    if (state !== 'available') {
      preparing.value = true
      try {
        await Recognition.install(options)
        state = await Recognition.available(options)
      } finally {
        preparing.value = false
      }
    }
    return state === 'available'
  }

  async function start() {
    if (!supported || listening.value || preparing.value) return
    problem.value = ''
    transcript.value = ''
    try {
      if (!(await ensureLocalPack())) {
        problem.value = 'Cihaz içi Türkçe ses tanıma bu cihazda desteklenmiyor.'
        return
      }
    } catch {
      problem.value = 'Türkçe ses paketi indirilemedi.'
      return
    }
    recognition = new Recognition()
    recognition.lang = LANG
    recognition.processLocally = true
    recognition.interimResults = true
    recognition.continuous = false
    recognition.onresult = (event) => {
      let text = ''
      for (const result of event.results) text += result[0].transcript
      transcript.value = text.trim()
    }
    recognition.onerror = (event) => {
      if (event.error === 'not-allowed' || event.error === 'service-not-allowed') problem.value = 'Mikrofon izni verilmedi.'
      else if (event.error === 'no-speech') problem.value = 'Ses algılanmadı, tekrar deneyin.'
      else if (event.error !== 'aborted') problem.value = 'Ses tanıma çalışmadı.'
    }
    recognition.onend = () => {
      listening.value = false
      if (transcript.value && !problem.value) onFinal(transcript.value)
    }
    listening.value = true
    try {
      recognition.start()
    } catch {
      listening.value = false
      problem.value = 'Ses tanıma başlatılamadı.'
    }
  }

  function stop() {
    recognition?.stop()
  }

  onBeforeUnmount(() => recognition?.abort())

  return { supported, preparing, listening, transcript, problem, start, stop }
}

const normalize = (value) => value.toLocaleLowerCase('tr-TR')

function digitsAfter(text, keywords, length) {
  const pattern = new RegExp(`(?:${keywords.join('|')})[^\\d]{0,12}((?:\\d[\\s.-]*){1,${length}})`, 'i')
  const match = text.match(pattern)
  return match ? match[1].replace(/\D/g, '') : ''
}

export function parsePersonnelSpeech(speech, wages = []) {
  const text = normalize(speech)
  const result = {}

  const phone = digitsAfter(text, ['telefon', 'numara', 'gsm', 'cep'], 11)
  if (phone.length >= 10) result.telefon = phone

  if (/\b(kadın|bayan)\b/.test(text)) result.cinsiyet = 'kadin'
  else if (/\berkek\b/.test(text)) result.cinsiyet = 'erkek'

  const wage = wages.find((w) => w.unvan && text.includes(normalize(w.unvan)))
  if (wage) result.ucret_id = String(wage.id)

  const stop = text.search(/\b(tc|t\.c\.|kimlik|telefon|numara|gsm|cep|kadın|bayan|erkek|ünvan|unvan|görev)\b|\d/)
  const nameSource = stop === -1 ? speech : speech.slice(0, stop)
  const name = nameSource.replace(/^\s*(ad(ı|i)?\s*soyad(ı|i)?|ad(ı|i)|isim|personel)\s*/i, '').trim()
  if (name) {
    result.ad_soyad = name
      .split(/\s+/)
      .map((part) => part.charAt(0).toLocaleUpperCase('tr-TR') + part.slice(1).toLocaleLowerCase('tr-TR'))
      .join(' ')
  }

  return result
}

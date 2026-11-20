<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { X, Image as ImageIcon } from '@lucide/vue'
import { recognizeFrame, warmUpScanner } from '../services/idScan.js'

const emit = defineEmits(['result', 'close', 'fallback'])

const CARD_RATIO = 1.586
const GUIDE_WIDTH = 0.92
const OCR_WIDTH = 1000
const CONFIRM_COUNT = 2
const PARTIAL_AFTER = 8
const TIMEOUT_MS = 40000

const video = ref(null)
const status = ref('Kamera açılıyor…')
const hint = ref('')
const guideStyle = ref({})

let stream = null
let running = false
let canvas = null
const votes = new Map()

function updateGuide() {
  const el = video.value
  if (!el) return
  const gw = el.clientWidth * GUIDE_WIDTH
  guideStyle.value = { width: `${gw}px`, height: `${gw / CARD_RATIO}px` }
}

function cropFrame() {
  const el = video.value
  const vw = el.videoWidth
  const vh = el.videoHeight
  const cw = el.clientWidth
  const ch = el.clientHeight
  if (!vw || !vh || !cw || !ch) return null
  const scale = Math.max(cw / vw, ch / vh)
  const ox = (vw * scale - cw) / 2
  const oy = (vh * scale - ch) / 2
  const gw = cw * GUIDE_WIDTH
  const gh = gw / CARD_RATIO
  const gx = (cw - gw) / 2
  const gy = (ch - gh) / 2
  const margin = 0.04
  const sx = Math.max(0, (gx + ox) / scale - gw * margin / scale)
  const sy = Math.max(0, (gy + oy) / scale - gh * margin / scale)
  const sw = Math.min(vw - sx, gw * (1 + margin * 2) / scale)
  const sh = Math.min(vh - sy, gh * (1 + margin * 2) / scale)
  const outW = Math.min(OCR_WIDTH, Math.round(sw))
  const outH = Math.round(outW * sh / sw)
  canvas = canvas || document.createElement('canvas')
  canvas.width = outW
  canvas.height = outH
  const ctx = canvas.getContext('2d', { willReadFrequently: true })
  ctx.filter = 'grayscale(1) contrast(1.4)'
  ctx.drawImage(el, sx, sy, sw, sh, 0, 0, outW, outH)
  return canvas
}

function pause(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

async function loop() {
  const startedAt = Date.now()
  let frames = 0
  while (running) {
    const frame = cropFrame()
    if (!frame) { await pause(200); continue }
    status.value = 'Okunuyor…'
    let result
    try {
      result = await recognizeFrame(frame)
    } catch {
      if (running) { running = false; emit('fallback') }
      return
    }
    if (!running) return
    frames += 1
    if (result.tc) {
      const key = `${result.tc}|${result.ad_soyad}`
      const count = (votes.get(key) || 0) + 1
      votes.set(key, count)
      const complete = Boolean(result.ad_soyad)
      if ((complete && count >= CONFIRM_COUNT) || (!complete && frames >= PARTIAL_AFTER)) {
        running = false
        emit('result', result)
        return
      }
    }
    status.value = 'Kimliği çerçeveye yerleştirin'
    if (Date.now() - startedAt > TIMEOUT_MS) hint.value = 'Okunmuyor mu? Fotoğraf çekerek deneyin.'
    await pause(120)
  }
}

async function start() {
  if (!navigator.mediaDevices?.getUserMedia) { emit('fallback'); return }
  warmUpScanner()
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
      audio: false,
    })
  } catch {
    emit('fallback')
    return
  }
  const el = video.value
  if (!el) { stop(); return }
  el.srcObject = stream
  try { await el.play() } catch { /* yoksay */ }
  updateGuide()
  status.value = 'Kimliği çerçeveye yerleştirin'
  running = true
  loop()
}

function stop() {
  running = false
  stream?.getTracks().forEach((t) => t.stop())
  stream = null
}

onMounted(() => {
  start()
  window.addEventListener('resize', updateGuide)
})

onBeforeUnmount(() => {
  stop()
  window.removeEventListener('resize', updateGuide)
})
</script>

<template>
  <div class="cam-layer" role="dialog" aria-modal="true" aria-label="Kimlik tarayıcı">
    <video ref="video" class="cam-video" playsinline muted autoplay @loadedmetadata="updateGuide"></video>
    <div class="cam-guide" :style="guideStyle" aria-hidden="true"></div>
    <header class="cam-top">
      <button type="button" class="cam-btn" aria-label="Kapat" @click="emit('close')"><X :size="22" /></button>
    </header>
    <footer class="cam-bottom" role="status" aria-live="polite">
      <strong>{{ status }}</strong>
      <small>{{ hint || 'Görüntü cihazınızda işlenir, dışarı gönderilmez.' }}</small>
      <button type="button" class="cam-photo" @click="emit('fallback')"><ImageIcon :size="18" /> Fotoğraf çekerek tara</button>
    </footer>
  </div>
</template>

<style scoped>
.cam-layer { position: fixed; inset: 0; z-index: 200; background: #000; display: grid; place-items: center; }
.cam-video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.cam-guide { position: relative; border: 3px solid #fff; border-radius: 16px; box-shadow: 0 0 0 100vmax rgba(0, 0, 0, .55); pointer-events: none; }
.cam-top { position: absolute; top: 0; left: 0; right: 0; display: flex; justify-content: flex-end; padding: calc(12px + env(safe-area-inset-top)) 16px 12px; }
.cam-btn { display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; background: rgba(0, 0, 0, .55); color: #fff; cursor: pointer; }
.cam-bottom { position: absolute; left: 0; right: 0; bottom: 0; display: grid; justify-items: center; gap: 8px; padding: 16px 16px calc(20px + env(safe-area-inset-bottom)); color: #fff; text-align: center; }
.cam-bottom small { color: rgba(255, 255, 255, .8); font-size: 12px; }
.cam-photo { display: inline-flex; align-items: center; gap: 6px; min-height: 44px; padding: 0 18px; border: 1px solid rgba(255, 255, 255, .6); border-radius: 999px; background: rgba(0, 0, 0, .4); color: #fff; font-weight: 700; font-size: 14px; cursor: pointer; }
</style>

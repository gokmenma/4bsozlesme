<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { X, Image as ImageIcon } from '@lucide/vue'
import { extractTc, parseNameText, recognizeRegion, warmUpScanner } from '../services/idScan.js'

const emit = defineEmits(['result', 'close', 'fallback'])

const CARD_RATIO = 1.586
const GUIDE_WIDTH = 0.92
const CONFIRM_COUNT = 2
const PARTIAL_AFTER = 5
const TIMEOUT_MS = 40000

const video = ref(null)
const status = ref('Kamera açılıyor…')
const hint = ref('')
const guideStyle = ref({})

let stream = null
let running = false
const tcCanvas = document.createElement('canvas')
const nameCanvas = document.createElement('canvas')
const votes = new Map()

function updateGuide() {
  const el = video.value
  if (!el) return
  const gw = el.clientWidth * GUIDE_WIDTH
  guideStyle.value = { width: `${gw}px`, height: `${gw / CARD_RATIO}px` }
}

const TC_REGION = { x0: 0, y0: 0.14, x1: 0.5, y1: 0.38, width: 560 }
const NAME_REGION = { x0: 0.26, y0: 0.3, x1: 0.78, y1: 0.63, width: 680 }

function cropRegion(region, target) {
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
  const gx = (cw - gw) / 2 + ox
  const gy = (ch - gh) / 2 + oy
  const sx = (gx + gw * region.x0) / scale
  const sy = (gy + gh * region.y0) / scale
  const sw = gw * (region.x1 - region.x0) / scale
  const sh = gh * (region.y1 - region.y0) / scale
  const outW = Math.min(region.width, Math.round(sw))
  const outH = Math.max(1, Math.round(outW * sh / sw))
  target.width = outW
  target.height = outH
  const ctx = target.getContext('2d', { willReadFrequently: true })
  ctx.filter = 'grayscale(1) contrast(1.3)'
  ctx.drawImage(el, sx, sy, sw, sh, 0, 0, outW, outH)
  return target
}

function pause(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

async function loop() {
  const startedAt = Date.now()
  let tcFrames = 0
  while (running) {
    try {
      const tcShot = cropRegion(TC_REGION, tcCanvas)
      if (!tcShot) { await pause(200); continue }
      const tc = extractTc(await recognizeRegion(tcShot))
      if (!running) return
      if (tc) {
        status.value = 'Okunuyor…'
        tcFrames += 1
        const nameShot = cropRegion(NAME_REGION, nameCanvas)
        const name = parseNameText(await recognizeRegion(nameShot))
        if (!running) return
        const key = `${tc}|${name}`
        const count = (votes.get(key) || 0) + 1
        votes.set(key, count)
        if ((name && count >= CONFIRM_COUNT) || (!name && tcFrames >= PARTIAL_AFTER)) {
          running = false
          emit('result', { tc, ad_soyad: name })
          return
        }
      } else {
        status.value = 'Kimliği kırmızı çerçeveye sığdırın'
      }
    } catch {
      if (running) { running = false; emit('fallback') }
      return
    }
    if (Date.now() - startedAt > TIMEOUT_MS) hint.value = 'Okunmuyor mu? Fotoğraf çekerek deneyin.'
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
  status.value = 'Kimliği kırmızı çerçeveye sığdırın'
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
.cam-guide { position: relative; border: 3px solid #e5251d; border-radius: 16px; box-shadow: 0 0 0 100vmax rgba(0, 0, 0, .55), inset 0 0 0 1px rgba(255, 255, 255, .35); pointer-events: none; }
.cam-top { position: absolute; top: 0; left: 0; right: 0; display: flex; justify-content: flex-end; padding: calc(12px + env(safe-area-inset-top)) 16px 12px; }
.cam-btn { display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; background: rgba(0, 0, 0, .55); color: #fff; cursor: pointer; }
.cam-bottom { position: absolute; left: 0; right: 0; bottom: 0; display: grid; justify-items: center; gap: 8px; padding: 16px 16px calc(20px + env(safe-area-inset-bottom)); color: #fff; text-align: center; }
.cam-bottom small { color: rgba(255, 255, 255, .8); font-size: 12px; }
.cam-photo { display: inline-flex; align-items: center; gap: 6px; min-height: 44px; padding: 0 18px; border: 1px solid rgba(255, 255, 255, .6); border-radius: 999px; background: rgba(0, 0, 0, .4); color: #fff; font-weight: 700; font-size: 14px; cursor: pointer; }
</style>

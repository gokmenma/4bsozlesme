<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { X, Image as ImageIcon } from '@lucide/vue'
import { warmUpScanner } from '../services/idScan.js'

const emit = defineEmits(['capture', 'close', 'fallback'])

const CARD_RATIO = 1.586
const GUIDE_WIDTH = 0.92
const CAPTURE_WIDTH = 1400

const video = ref(null)
const ready = ref(false)
const guideStyle = ref({})

let stream = null

function updateGuide() {
  const el = video.value
  if (!el) return
  const gw = el.clientWidth * GUIDE_WIDTH
  guideStyle.value = { width: `${gw}px`, height: `${gw / CARD_RATIO}px` }
}

function capture() {
  const el = video.value
  const vw = el?.videoWidth
  const vh = el?.videoHeight
  const cw = el?.clientWidth
  const ch = el?.clientHeight
  if (!vw || !vh || !cw || !ch) return
  const scale = Math.max(cw / vw, ch / vh)
  const gw = cw * GUIDE_WIDTH
  const gh = gw / CARD_RATIO
  const sx = ((cw - gw) / 2 + (vw * scale - cw) / 2) / scale
  const sy = ((ch - gh) / 2 + (vh * scale - ch) / 2) / scale
  const sw = gw / scale
  const sh = gh / scale
  const outW = Math.min(CAPTURE_WIDTH, Math.round(sw))
  const canvas = document.createElement('canvas')
  canvas.width = outW
  canvas.height = Math.round(outW * sh / sw)
  canvas.getContext('2d').drawImage(el, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height)
  stop()
  emit('capture', canvas)
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
  ready.value = true
}

function stop() {
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
    <footer class="cam-bottom">
      <strong>{{ ready ? 'Kimliği kırmızı çerçeveye sığdırıp çekin' : 'Kamera açılıyor…' }}</strong>
      <button type="button" class="cam-shutter" aria-label="Fotoğraf çek" :disabled="!ready" @click="capture"><span></span></button>
      <button type="button" class="cam-photo" @click="emit('fallback')"><ImageIcon :size="18" /> Galeriden / sistem kamerasından</button>
    </footer>
  </div>
</template>

<style scoped>
.cam-layer { position: fixed; inset: 0; z-index: 200; background: #000; display: grid; place-items: center; }
.cam-video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.cam-guide { position: relative; border: 3px solid #e5251d; border-radius: 16px; box-shadow: 0 0 0 100vmax rgba(0, 0, 0, .55), inset 0 0 0 1px rgba(255, 255, 255, .35); pointer-events: none; }
.cam-top { position: absolute; top: 0; left: 0; right: 0; display: flex; justify-content: flex-end; padding: calc(12px + env(safe-area-inset-top)) 16px 12px; }
.cam-btn { display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; background: rgba(0, 0, 0, .55); color: #fff; cursor: pointer; }
.cam-bottom { position: absolute; left: 0; right: 0; bottom: 0; display: grid; justify-items: center; gap: 12px; padding: 16px 16px calc(20px + env(safe-area-inset-bottom)); color: #fff; text-align: center; }
.cam-shutter { display: grid; place-items: center; width: 72px; height: 72px; border: 4px solid #fff; border-radius: 50%; background: transparent; cursor: pointer; }
.cam-shutter span { width: 54px; height: 54px; border-radius: 50%; background: #fff; }
.cam-shutter:active span { transform: scale(.9); }
.cam-shutter:disabled { opacity: .4; }
.cam-photo { display: inline-flex; align-items: center; gap: 6px; min-height: 40px; padding: 0 16px; border: 1px solid rgba(255, 255, 255, .5); border-radius: 999px; background: rgba(0, 0, 0, .4); color: #fff; font-weight: 600; font-size: 13px; cursor: pointer; }
</style>

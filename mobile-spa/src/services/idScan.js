let workerPromise = null

function getWorker(onProgress) {
  if (!workerPromise) {
    workerPromise = import('tesseract.js').then(({ createWorker }) =>
      createWorker('tur', 1, {
        workerPath: '/ocr/worker.min.js',
        corePath: '/ocr/core',
        langPath: '/ocr/lang',
        workerBlobURL: false,
        gzip: true,
        cacheMethod: 'none',
        logger: (m) => onProgress?.(m),
      }),
    ).catch((reason) => { workerPromise = null; throw reason })
  }
  return workerPromise
}

async function prepareImage(file, maxSide) {
  const bitmap = await createImageBitmap(file)
  const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height))
  const canvas = document.createElement('canvas')
  canvas.width = Math.round(bitmap.width * scale)
  canvas.height = Math.round(bitmap.height * scale)
  const ctx = canvas.getContext('2d')
  ctx.filter = 'grayscale(1) contrast(1.35)'
  ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height)
  bitmap.close?.()
  return canvas
}

export function isValidTc(tc) {
  if (!/^[1-9]\d{10}$/.test(tc)) return false
  const d = tc.split('').map(Number)
  const odd = d[0] + d[2] + d[4] + d[6] + d[8]
  const even = d[1] + d[3] + d[5] + d[7]
  if (((odd * 7 - even) % 10 + 10) % 10 !== d[9]) return false
  return d.slice(0, 10).reduce((a, b) => a + b, 0) % 10 === d[10]
}

function nameLine(line) {
  if (/[a-zçğıöşü]/.test(line.replace(/[iİ]/g, 'I'))) return ''
  const tokens = line.split(/[^A-ZÇĞİÖŞÜ]+/).filter(Boolean)
  if (!tokens.length || tokens.some((t) => t.length < 2)) return ''
  const value = tokens.join(' ')
  return /SURNAME|GIVEN|SOYAD|NAME|IDENTITY|KART|CARD|CUMHUR|T\.?C|TÜRK|REPUBLIC|TUR/.test(value) ? '' : value
}

export function extractTc(text) {
  const digitRuns = String(text).replace(/[Oo]/g, '0').match(/\d[\d\s]{9,14}\d/g) || []
  for (const run of digitRuns) {
    const compact = run.replace(/\s/g, '')
    for (let i = 0; i + 11 <= compact.length; i++) {
      if (isValidTc(compact.slice(i, i + 11))) return compact.slice(i, i + 11)
    }
  }
  return ''
}

export function parseNameBlock(lines) {
  const upper = lines.map((l) => l.toLocaleUpperCase('tr-TR'))
  const valueAfter = (index) => {
    for (let i = index + 1; i < Math.min(lines.length, index + 3); i++) {
      const v = nameLine(lines[i])
      if (v) return v
    }
    return ''
  }
  const surnameIdx = upper.findIndex((l) => /SOYAD|SURNAME/.test(l))
  const givenIdx = upper.findIndex((l, i) => i !== surnameIdx && /(^|[\s/])ADI(\s|\/|$)|GIVEN\s*NAME/.test(l) && !/SOYAD/.test(l))
  let surname = surnameIdx >= 0 ? valueAfter(surnameIdx) : ''
  let given = givenIdx >= 0 ? valueAfter(givenIdx) : ''
  const rest = lines.map(nameLine).filter((v) => v && v !== surname && v !== given)
  if (!surname && !given && rest.length >= 2) {
    surname = rest[rest.length - 2]
    given = rest[rest.length - 1]
  } else if (!surname && given) {
    surname = rest[0] || ''
  } else if (surname && !given) {
    given = rest[rest.length - 1] || ''
  }
  return [given, surname].filter(Boolean).join(' ')
}

export function parseNameText(text) {
  return parseNameBlock(String(text).split(/\r?\n/).map((l) => l.trim()).filter(Boolean))
}

export function parseIdText(text) {
  const lines = String(text).split(/\r?\n/).map((l) => l.trim()).filter(Boolean)
  const tc = extractTc(text)
  const tcLine = tc ? lines.findIndex((l) => l.replace(/[Oo]/g, '0').replace(/\s/g, '').includes(tc)) : -1
  const dateLine = lines.findIndex((l, i) => i > tcLine && /\d{2}[.\-/]\d{2}[.\-/]\d{4}/.test(l))
  const slice = lines.slice(tcLine + 1, dateLine > tcLine ? dateLine : tcLine + 8)
  return { tc, ad_soyad: parseNameBlock(slice) }
}

export async function scanIdCard(file, onProgress) {
  const worker = await getWorker(onProgress)
  await worker.setParameters({ tessedit_pageseg_mode: '3' })
  let best = { tc: '', ad_soyad: '' }
  for (const maxSide of [1100, 1800]) {
    const canvas = await prepareImage(file, maxSide)
    const { data } = await worker.recognize(canvas)
    const result = parseIdText(data.text)
    if (result.tc && result.ad_soyad) return result
    if (!best.tc && result.tc) best.tc = result.tc
    if (!best.ad_soyad && result.ad_soyad) best.ad_soyad = result.ad_soyad
  }
  return best
}

export async function releaseScanner() {
  if (!workerPromise) return
  const pending = workerPromise
  workerPromise = null
  try { await (await pending).terminate() } catch { /* yoksay */ }
}

export async function recognizeRegion(canvas, pageSegMode = '6') {
  const worker = await getWorker()
  await worker.setParameters({ tessedit_pageseg_mode: pageSegMode })
  const { data } = await worker.recognize(canvas)
  return data.text
}

const TC_REGION = { x0: 0, y0: 0.14, x1: 0.5, y1: 0.38, width: 560 }
const NAME_REGION = { x0: 0.26, y0: 0.3, x1: 0.78, y1: 0.63, width: 680 }

function cropCanvas(source, region) {
  const sx = source.width * region.x0
  const sy = source.height * region.y0
  const sw = source.width * (region.x1 - region.x0)
  const sh = source.height * (region.y1 - region.y0)
  const outW = Math.min(region.width, Math.round(sw))
  const outH = Math.max(1, Math.round(outW * sh / sw))
  const target = document.createElement('canvas')
  target.width = outW
  target.height = outH
  const ctx = target.getContext('2d')
  ctx.filter = 'grayscale(1) contrast(1.3)'
  ctx.drawImage(source, sx, sy, sw, sh, 0, 0, outW, outH)
  return target
}

export async function scanCardCanvas(card, onStep) {
  const report = (label, percent) => onStep?.({ label, percent })
  report('Okuyucu hazırlanıyor…', 8)
  await getWorker()
  report('TC kimlik no okunuyor…', 30)
  const tc = extractTc(await recognizeRegion(cropCanvas(card, TC_REGION)))
  report('Ad soyad okunuyor…', 65)
  const name = parseNameText(await recognizeRegion(cropCanvas(card, NAME_REGION)))
  if (tc && name) { report('Tamamlandı', 100); return { tc, ad_soyad: name } }
  report('Kimlik tamamı okunuyor…', 85)
  const full = parseIdText(await recognizeRegion(cropCanvas(card, { x0: 0, y0: 0, x1: 1, y1: 1, width: 900 })))
  report('Tamamlandı', 100)
  return { tc: tc || full.tc, ad_soyad: name || full.ad_soyad }
}

export function warmUpScanner() {
  return getWorker().catch(() => null)
}

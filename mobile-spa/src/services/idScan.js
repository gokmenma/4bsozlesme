import { genderFromName, snapGivenToken } from './trNames.js'

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

const NAME_NOISE = /SURNAME|GIVEN|SOYAD|IDENTITY|CUMHUR|REPUBLIC|DO[ĞG]UM|TAR[İI]H|BIRTH|GENDER|C[İI]NS|DOCUMENT|VALID|UNTIL|GE[ÇC]ERL|K[İI]ML[İI]K|(^|\s)(NAMES?|TUR|TC|T C|KART|KARTI|CARD|SER[İI]|SON|DATE|OF|NO|ADI)(\s|$)|TÜRK[İI]YE|TURK[İI]YE/

function fixDigits(line) {
  const letters = (line.match(/[A-Za-zÇĞİıÖŞÜçğöşü]/g) || []).length
  const digits = (line.match(/[0-9]/g) || []).length
  if (letters < 4 || digits > letters / 2) return line
  return line.replace(/0/g, 'O').replace(/1/g, 'I').replace(/5/g, 'S').replace(/8/g, 'B').replace(/6/g, 'G').replace(/\|/g, 'I')
}

function nameLine(raw) {
  const line = fixDigits(raw)
  const lower = (line.match(/[a-zçğıöşü]/g) || []).length
  if (lower > 1) return ''
  const upperLine = line.toLocaleUpperCase('tr-TR')
  const tokens = upperLine.split(/[^A-ZÇĞİÖŞÜ]+/).filter(Boolean)
  if (!tokens.length || tokens.some((t) => t.length < 2)) return ''
  const value = tokens.join(' ')
  return NAME_NOISE.test(value) ? '' : value
}

export function parseGender(text) {
  const lines = String(text).split(/\r?\n/).map((l) => l.toLocaleUpperCase('tr-TR'))
  const pair = /(?:^|[^A-ZÇĞİÖŞÜ])([EK])\s*[\/|\\]\s*([MF])(?![A-ZÇĞİÖŞÜ])/
  const fromMatch = (m) => {
    if (!m) return ''
    if (m[1] === 'E') return 'erkek'
    if (m[1] === 'K') return 'kadin'
    return ''
  }
  const dated = lines.filter((l) => /\d{2}[.\-/]\d{2}[.\-/]\d{4}/.test(l))
  for (const l of dated) {
    const g = fromMatch(l.match(pair))
    if (g) return g
  }
  for (let i = 0; i < lines.length; i++) {
    if (!/C[İI]NS[İI]YET|GENDER/.test(lines[i])) continue
    for (let j = i; j < Math.min(lines.length, i + 3); j++) {
      const g = fromMatch(lines[j].match(pair))
      if (g) return g
    }
  }
  for (const l of lines) {
    const g = fromMatch(l.match(pair))
    if (g) return g
  }
  return ''
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
  const snapped = given.split(' ').map(snapGivenToken).join(' ')
  return [snapped, surname].filter(Boolean).join(' ')
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
  return { tc, ad_soyad: parseNameBlock(slice), cinsiyet: parseGender(text) }
}

function genderFromFullName(fullName) {
  const tokens = String(fullName || '').split(/\s+/).filter(Boolean)
  return tokens.length > 1 ? genderFromName(tokens.slice(0, -1).join(' ')) : ''
}

export async function scanIdCard(file, onProgress) {
  const worker = await getWorker(onProgress)
  await worker.setParameters({ tessedit_pageseg_mode: '3', tessedit_char_whitelist: '' })
  const best = { tc: '', ad_soyad: '', cinsiyet: '' }
  for (const maxSide of [1100, 1800]) {
    const canvas = await prepareImage(file, maxSide)
    const { data } = await worker.recognize(canvas)
    const result = parseIdText(data.text)
    if (!best.tc && result.tc) best.tc = result.tc
    if (!best.ad_soyad && result.ad_soyad) best.ad_soyad = result.ad_soyad
    if (!best.cinsiyet && result.cinsiyet) best.cinsiyet = result.cinsiyet
    if (best.tc && best.ad_soyad && best.cinsiyet) break
  }
  if (!best.cinsiyet) best.cinsiyet = genderFromFullName(best.ad_soyad)
  return best
}

export async function releaseScanner() {
  if (!workerPromise) return
  const pending = workerPromise
  workerPromise = null
  try { await (await pending).terminate() } catch { /* yoksay */ }
}

export async function recognizeRegion(canvas, pageSegMode = '6', whitelist = '') {
  const worker = await getWorker()
  await worker.setParameters({ tessedit_pageseg_mode: pageSegMode, tessedit_char_whitelist: whitelist })
  const { data } = await worker.recognize(canvas)
  return data.text
}

const TC_REGION = { x0: 0, y0: 0.14, x1: 0.5, y1: 0.38, width: 560 }
const NAME_REGION = { x0: 0.26, y0: 0.3, x1: 0.78, y1: 0.63, width: 680 }
const NAME_REGION_HI = { x0: 0.26, y0: 0.3, x1: 0.78, y1: 0.63, width: 1000 }
const GENDER_REGION = { x0: 0.26, y0: 0.45, x1: 1, y1: 0.9, width: 760 }
const NAME_WHITELIST = 'ABCÇDEFGHIİJKLMNOÖPQRSŞTUÜVWXYZ '

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

function pickName(candidates, fullParse) {
  const names = candidates.filter(Boolean)
  if (!names.length) return fullParse()
  if (names.length === 1) return names[0]
  if (names[0] === names[1]) return names[0]
  const third = fullParse()
  const votes = names.filter((n) => n === third).length
  return votes ? third : names[0]
}

function titleCaseTr(value) {
  return String(value || '')
    .split(/\s+/)
    .filter(Boolean)
    .map((w) => w.charAt(0).toLocaleUpperCase('tr-TR') + w.slice(1).toLocaleLowerCase('tr-TR'))
    .join(' ')
}

export async function scanCardCanvas(card, onStep) {
  const report = (label, percent) => onStep?.({ label, percent })
  report('Okuyucu hazırlanıyor…', 8)
  await getWorker()
  report('TC kimlik no okunuyor…', 25)
  const tc = extractTc(await recognizeRegion(cropCanvas(card, TC_REGION)))
  report('Ad soyad okunuyor…', 45)
  const nameA = parseNameText(await recognizeRegion(cropCanvas(card, NAME_REGION)))
  report('Ad soyad doğrulanıyor…', 62)
  const nameB = parseNameText(await recognizeRegion(cropCanvas(card, NAME_REGION_HI), '6', NAME_WHITELIST))
  report('Cinsiyet okunuyor…', 78)
  let cinsiyet = parseGender(await recognizeRegion(cropCanvas(card, GENDER_REGION)))
  let full = null
  const readFull = async () => {
    if (!full) full = parseIdText(await recognizeRegion(cropCanvas(card, { x0: 0, y0: 0, x1: 1, y1: 1, width: 900 })))
    return full
  }
  if (!tc || (!nameA && !nameB) || !cinsiyet || (nameA && nameB && nameA !== nameB)) {
    report('Kimlik tamamı okunuyor…', 88)
    await readFull()
  }
  const fullName = full?.ad_soyad || ''
  const name = pickName([nameA, nameB], () => fullName)
  if (!cinsiyet) cinsiyet = full?.cinsiyet || genderFromFullName(name)
  report('Tamamlandı', 100)
  return { tc: tc || full?.tc || '', ad_soyad: titleCaseTr(name), cinsiyet }
}

export function warmUpScanner() {
  return getWorker().catch(() => null)
}

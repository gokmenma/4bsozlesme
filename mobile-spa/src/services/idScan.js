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

async function prepareImage(file) {
  const bitmap = await createImageBitmap(file)
  const scale = Math.min(1, 1800 / Math.max(bitmap.width, bitmap.height))
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

const LETTERS = /[^A-ZÇĞİÖŞÜ ]/g

function cleanName(value) {
  return String(value || '').toLocaleUpperCase('tr-TR').replace(LETTERS, ' ').replace(/\s+/g, ' ').trim()
}

export function parseIdText(text) {
  const lines = String(text).split(/\r?\n/).map((l) => l.trim()).filter(Boolean)
  let tc = ''
  const digitRuns = String(text).replace(/[Oo]/g, '0').match(/\d[\d\s]{9,14}\d/g) || []
  for (const run of digitRuns) {
    const compact = run.replace(/\s/g, '')
    for (let i = 0; i + 11 <= compact.length; i++) {
      if (isValidTc(compact.slice(i, i + 11))) { tc = compact.slice(i, i + 11); break }
    }
    if (tc) break
  }

  const upper = lines.map((l) => l.toLocaleUpperCase('tr-TR'))
  const valueAfter = (index) => {
    for (let i = index + 1; i < Math.min(lines.length, index + 3); i++) {
      const v = cleanName(lines[i])
      if (v.length >= 2 && !/SURNAME|GIVEN|SOYAD|NAME|T\.?C|SERI|DOĞUM|CİNSİYET/.test(v)) return v
    }
    return ''
  }
  const surnameIdx = upper.findIndex((l) => /SOYAD|SURNAME/.test(l))
  const givenIdx = upper.findIndex((l, i) => i !== surnameIdx && /(^|[\s/])ADI(\s|\/|$)|GIVEN\s*NAME/.test(l) && !/SOYAD/.test(l))
  const surname = surnameIdx >= 0 ? valueAfter(surnameIdx) : ''
  const given = givenIdx >= 0 ? valueAfter(givenIdx) : ''
  const name = [given, surname].filter(Boolean).join(' ')

  return { tc, ad_soyad: name }
}

export async function scanIdCard(file, onProgress) {
  const [canvas, worker] = await Promise.all([prepareImage(file), getWorker(onProgress)])
  const { data } = await worker.recognize(canvas)
  return parseIdText(data.text)
}

export async function releaseScanner() {
  if (!workerPromise) return
  const pending = workerPromise
  workerPromise = null
  try { await (await pending).terminate() } catch { /* yoksay */ }
}

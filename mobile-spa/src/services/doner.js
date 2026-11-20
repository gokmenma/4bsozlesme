import { auth } from './auth.js'

async function request(action, body = null) {
  const options = { credentials: 'same-origin', headers: { Accept: 'application/json' } }
  if (body) {
    options.method = 'POST'
    options.headers['Content-Type'] = 'application/json'
    options.headers['X-CSRF-Token'] = auth.state.csrfToken
    options.body = JSON.stringify(body)
  }
  const response = await fetch(`/api/doner.php?action=${action}`, options)
  const payload = await response.json().catch(() => ({ success: false, message: 'Sunucudan geçersiz yanıt alındı.' }))
  if (!response.ok || payload.success === false) {
    const error = new Error(payload.message || 'İşlem tamamlanamadı.')
    error.status = response.status
    throw error
  }
  return payload
}

export const getDonerDefaults = () => request('defaults')
export const buildDoner = (params) => request('build', params)

const HEADERS = [
  'SIRA NO', 'TC KİMLİK NO', 'AYLIK', 'EK GÖST.', 'İNİ.ÖDE.', 'EĞ.ÖR.ÖDE.', 'İDA.GÖR.ÖDE.', 'YAN ÖDE',
  'ÖZ.HİZ.TAZ.', 'EK ÖZ.HİZM.', 'MAL.KESİ.', 'Dİ.KES.', 'DERECE', 'SGGVM', 'DENGE', 'EK ÖD.MATR.',
  'TEO', 'GV.İSTİSNA', 'ADI SOYADI', 'HİZMET YILI',
]

export async function downloadXlsx(rows, month, year) {
  const { default: ExcelJS } = await import('exceljs')
  const workbook = new ExcelJS.Workbook()
  const sheet = workbook.addWorksheet('Doner Matrahi')
  const border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } }

  const head = sheet.addRow(HEADERS)
  head.font = { bold: true, name: 'Arial', size: 10 }
  head.eachCell((cell) => {
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF3F4F6' } }
    cell.alignment = { vertical: 'middle', horizontal: 'center' }
    cell.border = border
  })

  rows.forEach((item) => {
    const r = sheet.rowCount + 1
    const row = sheet.addRow([
      item.sira, String(item.tc), item.aylik, item.ek_gost, 0, 0, 0, item.yan_ode, item.oz_hiz, 0, 0, 0,
      item.derece || '', 0, 0,
      { formula: `C${r}+D${r}+E${r}+F${r}+G${r}+H${r}+I${r}+J${r}`, result: item.toplam },
      0, 0, item.ad_soyad, item.kidem,
    ])
    row.eachCell((cell, col) => {
      cell.font = { name: 'Arial', size: 10 }
      cell.border = border
      if (col === 2 || col === 13) cell.numFmt = '@'
      else if ([3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 14, 15, 16, 17, 18].includes(col)) {
        cell.numFmt = '#,##0.00'
        cell.alignment = { horizontal: 'right' }
      }
    })
  })

  sheet.columns.forEach((column) => {
    let max = 0
    column.eachCell({ includeEmpty: true }, (cell) => {
      let v = cell.value
      if (v && typeof v === 'object' && v.formula) v = v.result
      if (v !== undefined && v !== null) max = Math.max(max, String(v).length)
    })
    column.width = max < 12 ? 12 : max + 4
  })

  const buffer = await workbook.xlsx.writeBuffer()
  const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `Sozlesmeli_Tesvik_Ek_Odeme_Matrahi_${month}_${year}.xlsx`
  document.body.appendChild(link)
  link.click()
  link.remove()
  setTimeout(() => URL.revokeObjectURL(url), 10000)
}

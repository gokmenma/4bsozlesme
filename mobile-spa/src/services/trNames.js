const FEMALE = `AYŞE FATMA EMİNE HATİCE ZEYNEP ELİF MERYEM ÖZLEM SEDA SEVGİ SEVİM SELMA SEMA SİBEL SULTAN HÜLYA GÜL GÜLSÜM GÜLŞEN GÜLAY GÜLCAN NURAY NURCAN NURGÜL NUR NİLÜFER NİLAY NİHAL NESLİHAN NERMİN NEVİN MELEK MELİKE MERVE MÜGE MÜZEYYEN BÜŞRA BETÜL BURCU BİRGÜL CANAN CEYLAN DERYA DİLEK DİLARA DENİZ EBRU ESRA ECE EMEL EMİNE FADİME FERİDE FİLİZ FUNDA GAMZE GÖKÇE GÖNÜL HALİME HANİFE HAVVA HİLAL İREM İPEK İLKNUR KADRİYE KEZBAN KÜBRA LEYLA LEMAN MELEK MERAL MİNE NAZLI NURSEL PINAR PERİHAN RABİA REYHAN RUKİYE SAADET SELEN SENA SERAP SEVDA SEVİL SONGÜL SÜMEYYE ŞEYMA ŞEYDA ŞENAY TUBA TÜLAY ÜMMÜ YASEMİN YELDA YILDIZ ZEHRA ZELİHA ZÜBEYDE ZÜLEYHA AYTEN AYLİN AYSEL AYSUN AYSEL BEDİA BERİVAN BEYZA DİDEM DUYGU EDA ELA ESİN GÜLER HACER HAYRİYE HATUN İLAYDA KEVSER MERYEM MEDİNE NAZAN NEŞE NURİYE ÖZGE RAZİYE RÜYA SAMİYE SEHER SELİN SERPİL SONAY ŞEBNEM ŞERİFE TÜRKAN VEDİA YAĞMUR YASEMİN ZÜMRÜT`.split(/\s+/)

const MALE = `MEHMET MUSTAFA AHMET ALİ HÜSEYİN HASAN İBRAHİM İSMAİL YUSUF OSMAN MURAT ÖMER RAMAZAN HALİL SÜLEYMAN ABDULLAH MAHMUT KENAN KEMAL RECEP YAŞAR CEMAL CENGİZ SELÇUK SERKAN VOLKAN EMRE BURAK ONUR OĞUZ OKAN ORHAN OKTAY ERDAL ERKAN ERHAN ERCAN ENGİN ENES FATİH FARUK FERHAT FİKRET GÖKHAN GÖKMEN HAKAN HALUK HAMZA HARUN HAYDAR HÜSNÜ İLHAN İLKER İSA KADİR KAZIM KORAY LEVENT MEHMET MESUT METİN MUHAMMET MUHAMMED MUSA MÜMİN NECATİ NECDET NEVZAT NİHAT NURİ OĞUZHAN ÖZCAN ÖZGÜR POLAT RIZA SABRİ SADIK SALİH SAMET SAVAŞ SEDAT SEFA SELİM SERDAR SERHAT SİNAN SONER ŞABAN ŞAHİN ŞENOL TAHİR TALHA TAMER TANER TARIK TAYFUN TOLGA TUNCAY TUĞRUL TURGUT UĞUR ULAŞ UMUT VEDAT YAKUP YASİN YAVUZ YILMAZ YUNUS ZEKİ ZEKERİYA ZİYA ADEM ADNAN AHMED AKIN ALPER ARİF ASLAN ATİLLA AYHAN BAHATTIN BARIŞ BAYRAM BEKİR BEKİR BİLAL BÜNYAMİN CAFER CAN CANER CEM CEMİL CİHAN DAVUT DOĞAN DURSUN EFE EMİN EMİR ERDOĞAN EREN ERSİN FEVZİ FURKAN GÜRKAN HİKMET İDRİS İRFAN KAMİL KERİM KUDRET LATİF MEHDİ MİRAÇ MUHARREM MUZAFFER NAZIM NEDİM NUH OSMAN RAHMİ RIDVAN SAİT SUAT TURAN YALÇIN YÜCEL`.split(/\s+/)

export const FEMALE_NAMES = new Set(FEMALE)
export const MALE_NAMES = new Set(MALE)
export const ALL_NAMES = [...new Set([...FEMALE, ...MALE])]

export function genderFromName(given) {
  const first = String(given || '').toLocaleUpperCase('tr-TR').split(/\s+/).filter(Boolean)
  if (!first.length) return ''
  const f = first.some((t) => FEMALE_NAMES.has(t))
  const m = first.some((t) => MALE_NAMES.has(t))
  if (f && !m) return 'kadin'
  if (m && !f) return 'erkek'
  return ''
}

const CONFUSABLE = ['ILT1', 'OQD0', 'UVY', 'CGO', 'EF', 'BR8', 'SZ5', 'NHM', 'KX']

export function foldName(value) {
  return String(value)
    .replace(/İ/g, 'I')
    .toLocaleUpperCase('tr-TR')
    .replace(/Ç/g, 'C').replace(/Ğ/g, 'G').replace(/İ/g, 'I').replace(/Ö/g, 'O').replace(/Ş/g, 'S').replace(/Ü/g, 'U')
}

function confusableMatch(a, b) {
  if (a.length !== b.length) return false
  let diff = 0
  for (let i = 0; i < a.length; i++) {
    if (a[i] === b[i]) continue
    if (!CONFUSABLE.some((g) => g.includes(a[i]) && g.includes(b[i]))) return false
    if (++diff > 1) return false
  }
  return diff === 1
}

const FOLDED = ALL_NAMES.map((name) => ({ name, folded: foldName(name) }))

export function snapGivenToken(token) {
  if (token.length < 3 || FEMALE_NAMES.has(token) || MALE_NAMES.has(token)) return token
  const folded = foldName(token)
  const exact = FOLDED.filter((n) => n.folded === folded)
  if (exact.length === 1) return exact[0].name
  if (exact.length > 1 || token.length < 5) return token
  const near = FOLDED.filter((n) => confusableMatch(folded, n.folded))
  return near.length === 1 ? near[0].name : token
}

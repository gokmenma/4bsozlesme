<?php
$pageTitle = 'Evrak Formları';
$pageSubtitle = 'Personelin işe girişte dolduracağı formları tanımlayın';
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<link rel="stylesheet" href="<?php echo routeUrl('assets/css/evrak.css'); ?>">
<script src="<?php echo routeUrl('assets/js/evrak-ui.js'); ?>"></script>

<div class="ev-scope">
<div class="flex items-center justify-between gap-4 mb-5">
    <div>
        <h1 class="text-xl font-semibold">Evrak Formları</h1>
        <p class="ev-muted">Formlar personele SMS/e-posta bağlantısıyla gönderilir; personel telefonundan doldurur, siz çıktı alıp ıslak imzaya sunarsınız.</p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" class="btn-outline" onclick="evSablonYukle()">Hazır Şablonları Ekle</button>
        <button type="button" class="btn" onclick="evYeniForm()">Yeni Form</button>
    </div>
</div>

<div class="ev-grid">
    <div class="ev-card">
        <div class="ev-card-head"><h2 class="ev-title">Formlar</h2><span class="ev-muted" id="ev-form-count"></span></div>
        <div class="ev-form-list" id="ev-form-list"></div>
    </div>

    <div class="ev-card" id="ev-editor-card">
        <div id="ev-editor-empty" class="ev-empty">
            <h3>Bir form seçin veya yeni form oluşturun</h3>
            <p class="ev-muted">Başlamak için “Hazır Şablonları Ekle” ile Kişisel Bilgi, KVKK, Aile/Vergi Beyanı ve Belge Yükleme formlarını ekleyebilirsiniz.</p>
        </div>

        <form id="ev-editor" class="hidden" onsubmit="evKaydet(event)">
            <div class="ev-card-head">
                <h2 class="ev-title" id="ev-editor-title">Yeni Form</h2>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-outline text-red-600 hidden" id="ev-sil-btn" onclick="evSil()">Sil</button>
                    <button type="submit" class="btn" id="ev-kaydet-btn">Kaydet</button>
                </div>
            </div>
            <div class="ev-card-body" style="display:flex;flex-direction:column;gap:1rem;">
                <div class="ev-two">
                    <div class="ev-stack">
                        <label for="ev-ad">Form Adı</label>
                        <input type="text" id="ev-ad" maxlength="255" required>
                    </div>
                    <div class="ev-stack">
                        <label for="ev-tip">Form Tipi</label>
                        <div id="ev-tip-wrap"></div>
                    </div>
                </div>
                <div class="ev-stack hidden" id="ev-cikti-wrap">
                    <label>Basılı Şablon</label>
                    <div id="ev-cikti-sel"></div>
                    <p class="ev-muted">Personelin adı soyadı, T.C. kimlik no ve unvanı otomatik dolar; diğer alanlar ıslak imza için boş çıkar. Personele dijital gönderilmez, yalnızca çıktı alınır.</p>
                </div>
                <div class="ev-stack">
                    <label for="ev-aciklama">Kısa Açıklama</label>
                    <input type="text" id="ev-aciklama" maxlength="500" placeholder="Personelin formun başında göreceği kısa açıklama">
                </div>
                <div class="ev-stack" id="ev-metin-wrap">
                    <label for="ev-metin">Bilgilendirme / Beyan Metni <span class="ev-muted">(isteğe bağlı)</span></label>
                    <textarea id="ev-metin" rows="6" placeholder="KVKK aydınlatma metni, taahhütname vb. Paragrafları boş satırla ayırın."></textarea>
                    <div class="ev-muted ev-hint-vars">Değişkenler:
                        <code data-var="{{KURUM_ADI}}">{{KURUM_ADI}}</code>
                        <code data-var="{{PERSONEL_AD}}">{{PERSONEL_AD}}</code>
                        <code data-var="{{TC_NO}}">{{TC_NO}}</code>
                        <code data-var="{{GOREV}}">{{GOREV}}</code>
                        <code data-var="{{TARIH}}">{{TARIH}}</code>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <label class="ev-switch"><input type="checkbox" id="ev-aktif" checked><i></i> Aktif (gönderimde seçilebilir)</label>
                    <label class="ev-check">Sıra <input type="number" id="ev-sira" class="ev-sm" style="width:80px" min="0" value="0"></label>
                </div>

                <div id="ev-alan-wrap">
                    <div class="ev-section-title">
                        <h3>Alanlar <span class="ev-badge-count" id="ev-alan-sayi"></span></h3>
                        <span class="ev-muted">Onay metinlerinde “Onay Kutusu”, belgelerde “Dosya Yükleme” tipini kullanın.</span>
                    </div>
                    <div class="ev-fields" id="ev-fields"></div>
                    <p class="ev-muted mb-2" id="ev-fields-empty">Henüz alan eklenmedi.</p>
                    <button type="button" class="ev-add" style="margin-top:.625rem" onclick="evAlanEkle()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        Alan Ekle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>

<script>
(function () {
    const formlar = <?php echo json_encode(array_values($evrakFormlari), $jsonFlags); ?>;
    const TIPLER = <?php echo json_encode($formTipleri, $jsonFlags); ?>;
    const ALAN_TIPLERI = <?php echo json_encode($alanTipleri, $jsonFlags); ?>;
    const PERSONEL_ALANLARI = <?php echo json_encode($personelAlanlari, $jsonFlags); ?>;
    const CIKTI_SABLONLARI = <?php echo json_encode($ciktiSablonlari, $jsonFlags); ?>;
    const base = <?php echo json_encode(appBasePath(), $jsonFlags); ?>;
    const pillRenk = { bilgi: 'blue', kvkk: 'violet', beyan: 'amber', belge: 'green', cikti: 'gray' };
    let aktifId = null;
    let alanlar = [];

    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const toast = (category, title, description) => window.showToast ? showToast({ category, title, description }) : alert(description);

    const ciktiMi = () => evSelectValue('ev-tip') === 'cikti';
    window.evTipDegisti = () => {
        const c = ciktiMi();
        $('ev-cikti-wrap').classList.toggle('hidden', !c);
        $('ev-metin-wrap').classList.toggle('hidden', c);
        $('ev-alan-wrap').classList.toggle('hidden', c);
    };
    const tipSec = (v) => { $('ev-tip-wrap').innerHTML = evSelectHtml('ev-tip', TIPLER, v, 'evTipDegisti()'); evTipDegisti(); };
    const ciktiSec = (v) => { $('ev-cikti-sel').innerHTML = evSelectHtml('ev-cikti', CIKTI_SABLONLARI, v); };
    tipSec('bilgi');
    ciktiSec('');

    document.querySelectorAll('.ev-hint-vars code').forEach((c) => c.addEventListener('click', () => {
        const t = $('ev-metin');
        const pos = t.selectionStart ?? t.value.length;
        t.value = t.value.slice(0, pos) + c.dataset.var + t.value.slice(t.selectionEnd ?? pos);
        t.focus();
    }));

    function renderList() {
        $('ev-form-count').textContent = formlar.length ? formlar.length + ' form' : '';
        if (!formlar.length) {
            $('ev-form-list').innerHTML = `<div class="ev-empty"><p class="ev-muted">Henüz form yok.</p><button type="button" class="btn-sm mt-2" onclick="evSablonYukle()">Hazır Şablonları Ekle</button></div>`;
            return;
        }
        $('ev-form-list').innerHTML = formlar.map((f) => `
            <button type="button" class="ev-form-item ${Number(f.id) === aktifId ? 'active' : ''}" onclick="evAc(${Number(f.id)})">
                <div class="ev-row"><strong>${esc(f.ad)}</strong>${Number(f.aktif) ? '' : '<span class="ev-pill gray">Pasif</span>'}</div>
                <div class="ev-row"><span class="ev-pill ${pillRenk[f.tip] || 'gray'}">${esc(TIPLER[f.tip] || f.tip)}</span><span class="ev-muted">${Number(f.alan_sayisi)} alan</span></div>
            </button>`).join('');
    }

    const ICON = {
        up: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>',
        down: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
        del: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
    };
    const AKTARILABILIR = ['text', 'tel', 'select', 'radio'];

    function alanHtml(a, i) {
        const tipSel = evSelectHtml(`ev-alan-tip-${i}`, ALAN_TIPLERI, a.tip, `evAlanSet(${i}, 'tip', this.value, true)`);
        const pSel = evSelectHtml(`ev-alan-pa-${i}`, Object.assign({ '': 'Aktarılmasın' }, PERSONEL_ALANLARI), a.personel_alani, `evAlanSet(${i}, 'personel_alani', this.value)`, 'ev-sm');
        const secenekli = a.tip === 'select' || a.tip === 'radio';
        const aktarilir = AKTARILABILIR.includes(a.tip);
        return `
        <div class="ev-field" data-i="${i}">
            <div class="ev-field-main">
                <span class="ev-field-num">${i + 1}</span>
                <input type="text" placeholder="Alan etiketi (ör. Doğum Tarihi)" value="${esc(a.etiket)}" oninput="evAlanSet(${i}, 'etiket', this.value)">
                ${tipSel}
                <label class="ev-switch"><input type="checkbox" ${Number(a.zorunlu) ? 'checked' : ''} onchange="evAlanSet(${i}, 'zorunlu', this.checked ? 1 : 0)"><i></i> Zorunlu</label>
                <div class="ev-field-actions">
                    <button type="button" class="ev-icon-btn" title="Yukarı taşı" ${i === 0 ? 'disabled' : ''} onclick="evAlanTasi(${i}, -1)">${ICON.up}</button>
                    <button type="button" class="ev-icon-btn" title="Aşağı taşı" ${i === alanlar.length - 1 ? 'disabled' : ''} onclick="evAlanTasi(${i}, 1)">${ICON.down}</button>
                    <button type="button" class="ev-icon-btn danger" title="Alanı sil" onclick="evAlanSil(${i})">${ICON.del}</button>
                </div>
            </div>
            <div class="ev-field-extra">
                ${secenekli ? `<label class="ev-mini"><span>Seçenekler</span><input type="text" class="ev-sm" placeholder="Virgülle ayırın: Evet, Hayır" value="${esc(a.secenekler)}" oninput="evAlanSet(${i}, 'secenekler', this.value)"></label>` : ''}
                <label class="ev-mini"><span>Yardım metni</span><input type="text" class="ev-sm" placeholder="Personele gösterilecek kısa açıklama" value="${esc(a.yardim)}" oninput="evAlanSet(${i}, 'yardim', this.value)"></label>
                ${aktarilir ? `<label class="ev-mini"><span>Personel kartına aktar</span>${pSel}</label>` : ''}
            </div>
        </div>`;
    }

    function renderAlanlar() {
        $('ev-fields').innerHTML = alanlar.map(alanHtml).join('');
        $('ev-fields-empty').classList.toggle('hidden', alanlar.length > 0);
        $('ev-alan-sayi').textContent = alanlar.length ? `· ${alanlar.length}` : '';
    }

    function doldur(f) {
        aktifId = f ? Number(f.id) : null;
        $('ev-editor-empty').classList.add('hidden');
        $('ev-editor').classList.remove('hidden');
        $('ev-editor-title').textContent = f ? f.ad : 'Yeni Form';
        $('ev-sil-btn').classList.toggle('hidden', !f);
        $('ev-ad').value = f ? f.ad : '';
        tipSec(f ? f.tip : 'bilgi');
        ciktiSec(f ? (f.cikti_sablon || '') : '');
        $('ev-aciklama').value = f ? (f.aciklama || '') : '';
        $('ev-metin').value = f ? (f.metin || '') : '';
        $('ev-aktif').checked = f ? Number(f.aktif) === 1 : true;
        $('ev-sira').value = f ? Number(f.sira) : formlar.length + 1;
        alanlar = f ? f.alanlar.map((a) => ({ etiket: a.etiket, anahtar: a.anahtar, tip: a.tip, secenekler: a.secenekler || '', yardim: a.yardim || '', zorunlu: Number(a.zorunlu), personel_alani: a.personel_alani || '' })) : [];
        renderAlanlar();
        renderList();
    }

    window.evYeniForm = () => doldur(null);

    window.evAc = async (id) => {
        try {
            const r = await fetch(`${base}/evrak-form-get?id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const d = await r.json();
            if (!d.success) throw new Error(d.message);
            doldur(d.form);
        } catch (e) {
            toast('error', 'Hata', e.message || 'Form yüklenemedi.');
        }
    };

    window.evAlanEkle = () => {
        alanlar.push({ etiket: '', anahtar: '', tip: 'text', secenekler: '', yardim: '', zorunlu: 0, personel_alani: '' });
        renderAlanlar();
        const son = $('ev-fields').querySelector('.ev-field:last-child');
        if (son) { son.scrollIntoView({ behavior: 'smooth', block: 'center' }); son.querySelector('input').focus(); }
    };
    window.evAlanSet = (i, k, v, rerender) => {
        alanlar[i][k] = v;
        if (k === 'tip' && !AKTARILABILIR.includes(v)) alanlar[i].personel_alani = '';
        if (rerender) renderAlanlar();
    };
    window.evAlanSil = (i) => { alanlar.splice(i, 1); renderAlanlar(); };
    window.evAlanTasi = (i, yon) => {
        const j = i + yon;
        if (j < 0 || j >= alanlar.length) return;
        [alanlar[i], alanlar[j]] = [alanlar[j], alanlar[i]];
        renderAlanlar();
    };

    async function post(url, body, isJson) {
        const r = await fetch(base + url, {
            method: 'POST',
            headers: Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, isJson ? { 'Content-Type': 'application/json' } : {}),
            body: isJson ? JSON.stringify(body) : body,
        });
        const d = await r.json().catch(() => ({ success: false, message: 'Sunucu yanıtı okunamadı.' }));
        if (!d.success) throw new Error(d.message || 'İşlem başarısız.');
        return d;
    }

    window.evKaydet = async (e) => {
        e.preventDefault();
        const btn = $('ev-kaydet-btn');
        btn.disabled = true;
        try {
            const d = await post('/evrak-form-kaydet', {
                id: aktifId,
                ad: $('ev-ad').value,
                tip: evSelectValue('ev-tip'),
                cikti_sablon: evSelectValue('ev-cikti'),
                aciklama: $('ev-aciklama').value,
                metin: $('ev-metin').value,
                aktif: $('ev-aktif').checked ? 1 : 0,
                sira: Number($('ev-sira').value) || 0,
                alanlar,
            }, true);
            toast('success', 'Kaydedildi', d.message);
            setTimeout(() => { window.location.href = `${base}/evrak-formlari?id=${d.id}`; }, 400);
        } catch (err) {
            toast('error', 'Kaydedilemedi', err.message);
        } finally {
            btn.disabled = false;
        }
    };

    window.evSil = async () => {
        if (!aktifId || !confirm('Bu form silinsin mi? Daha önce gönderilmiş evrak paketleri etkilenmez.')) return;
        const fd = new FormData();
        fd.append('id', aktifId);
        try {
            const d = await post('/evrak-form-sil', fd);
            toast('success', 'Silindi', d.message);
            setTimeout(() => { window.location.href = `${base}/evrak-formlari`; }, 400);
        } catch (err) {
            toast('error', 'Hata', err.message);
        }
    };

    window.evSablonYukle = async () => {
        if (formlar.length && !confirm('Hazır 4 form şablonu mevcut formlarınıza ek olarak eklenecek. Devam edilsin mi?')) return;
        try {
            const d = await post('/evrak-sablon-yukle', new FormData());
            toast('success', 'Şablonlar eklendi', d.message);
            setTimeout(() => window.location.reload(), 400);
        } catch (err) {
            toast('error', 'Hata', err.message);
        }
    };

    renderList();
    const qid = Number(new URLSearchParams(location.search).get('id'));
    if (qid) evAc(qid);
})();
</script>

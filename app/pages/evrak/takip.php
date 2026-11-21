<?php
$pageTitle = 'Evrak Takibi';
$pageSubtitle = 'Personele gönderilen işe giriş evraklarının durumu';
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$durumRenk = ['gonderildi' => 'blue', 'acildi' => 'violet', 'dolduruldu' => 'amber', 'iade' => 'red', 'onaylandi' => 'green', 'tamamlandi' => 'gray', 'iptal' => 'gray'];
$toplam = array_sum($durumSayilari);
$tabUrl = function ($d) use ($arama) {
    $q = [];
    if ($d !== '') $q['durum'] = $d;
    if ($arama !== '') $q['q'] = $arama;
    return routeUrl('/evrak-takip') . ($q ? '?' . http_build_query($q) : '');
};
?>
<link rel="stylesheet" href="<?php echo routeUrl('assets/css/evrak.css'); ?>">

<div class="ev-scope">
<div class="flex items-center justify-between gap-4 mb-5">
    <div>
        <h1 class="text-xl font-semibold">Evrak Takibi</h1>
        <p class="ev-muted">Personel bağlantıdan formları doldurur → siz inceleyip onaylarsınız → çıktı alıp ıslak imzaya sunarsınız → imzalı nüshayı yüklersiniz.</p>
    </div>
    <div class="flex items-center gap-2">
        <input type="text" list="ev-personel-list" id="ev-personel-ara" placeholder="Personel adı yazın..." style="width:260px">
        <datalist id="ev-personel-list">
            <?php foreach ($personeller as $p): ?>
            <option value="<?php echo $h($p['ad_soyad'] . ' (***' . $p['tc_son4'] . ')'); ?>" data-id="<?php echo (int)$p['id']; ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <button type="button" class="btn" onclick="evYeniGonder()">Evrak Gönder</button>
    </div>
</div>

<div class="flex items-center justify-between gap-4 mb-4">
    <nav class="ev-tabs">
        <a class="ev-tab <?php echo $seciliDurum === '' ? 'active' : ''; ?>" href="<?php echo $h($tabUrl('')); ?>">Tümü <span><?php echo (int)$toplam; ?></span></a>
        <?php foreach ($durumlar as $k => $ad): ?>
        <a class="ev-tab <?php echo $seciliDurum === $k ? 'active' : ''; ?>" href="<?php echo $h($tabUrl($k)); ?>"><?php echo $h($ad); ?> <span><?php echo (int)($durumSayilari[$k] ?? 0); ?></span></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" action="<?php echo $h(routeUrl('/evrak-takip')); ?>" class="flex items-center gap-2">
        <?php if ($seciliDurum !== ''): ?><input type="hidden" name="durum" value="<?php echo $h($seciliDurum); ?>"><?php endif; ?>
        <input type="search" name="q" value="<?php echo $h($arama); ?>" placeholder="Ad soyad veya T.C. ara" style="width:220px">
    </form>
</div>

<div class="ev-card">
    <?php if (empty($paketler)): ?>
    <div class="ev-empty">
        <h3>Henüz evrak paketi yok</h3>
        <p class="ev-muted">Sağ üstten personel seçip “Evrak Gönder” ile başlayabilir veya Personeller sayfasındaki satır menüsünü kullanabilirsiniz.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="ev-table">
        <thead>
            <tr>
                <th>Personel</th>
                <th>Durum</th>
                <th>Formlar</th>
                <th>Gönderen</th>
                <th>Oluşturma</th>
                <th>Son Geçerlilik</th>
                <th>Personel Gönderimi</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($paketler as $p):
            $suresiDoldu = in_array($p['durum'], ['gonderildi', 'acildi', 'iade'], true) && strtotime($p['son_gecerlilik']) < time();
        ?>
            <tr>
                <td>
                    <div class="font-medium"><?php echo $h($p['ad_soyad']); ?></div>
                    <div class="ev-muted">***<?php echo $h(substr($p['tc_kimlik'], -4)); ?></div>
                </td>
                <td>
                    <span class="ev-pill <?php echo $durumRenk[$p['durum']] ?? 'gray'; ?>"><?php echo $h($durumlar[$p['durum']] ?? $p['durum']); ?></span>
                    <?php if ($suresiDoldu): ?><span class="ev-pill red">Süresi doldu</span><?php endif; ?>
                </td>
                <td><?php echo (int)$p['form_sayisi']; ?></td>
                <td class="ev-muted"><?php echo $h($p['gonderen_ad'] ?? '-'); ?> · <?php echo $h(strtoupper($p['gonderim_kanali'])); ?></td>
                <td class="ev-muted"><?php echo $h(date('d.m.Y H:i', strtotime($p['created_at']))); ?></td>
                <td class="ev-muted"><?php echo $h(date('d.m.Y', strtotime($p['son_gecerlilik']))); ?></td>
                <td class="ev-muted"><?php echo $p['gonderim_tarihi'] ? $h(date('d.m.Y H:i', strtotime($p['gonderim_tarihi']))) : '-'; ?></td>
                <td style="text-align:right;white-space:nowrap">
                    <div class="ev-row-actions">
                        <button type="button" class="btn-sm-outline" onclick="evDetay(<?php echo (int)$p['id']; ?>)">Detay</button>
                        <button type="button" class="ev-icon-btn danger" title="Paketi sil" onclick="evSil(<?php echo (int)$p['id']; ?>, <?php echo $h(json_encode($p['ad_soyad'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div>

<dialog id="dialog-evrak-detay" class="dialog" style="max-width: 860px; width: 94vw;" onclick="if (event.target === this) this.close()">
  <div class="dialog-content ev-scope bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" style="width: 860px; max-width: 100%; max-height: 90vh; overflow:auto;" onclick="event.stopPropagation()">
    <header class="flex items-start justify-between gap-4 mb-4">
      <div>
        <h2 class="text-lg font-semibold" id="evd-ad"></h2>
        <p class="ev-muted" id="evd-alt"></p>
      </div>
      <span class="ev-pill" id="evd-durum"></span>
    </header>
    <div id="evd-icerik"></div>
    <footer class="ev-actions mt-4" id="evd-aksiyonlar"></footer>
  </div>
</dialog>

<?php include __DIR__ . '/_gonder-dialog.php'; ?>

<script>
(function () {
    const base = <?php echo json_encode(appBasePath(), $jsonFlags); ?>;
    const DURUM_RENK = <?php echo json_encode($durumRenk, $jsonFlags); ?>;
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const toast = (category, title, description) => window.showToast ? showToast({ category, title, description }) : alert(description);
    const tarih = (s) => s ? new Date(s.replace(' ', 'T')).toLocaleString('tr-TR', { dateStyle: 'short', timeStyle: 'short' }) : '-';
    let aktif = null;

    window.evYeniGonder = () => {
        const v = $('ev-personel-ara').value;
        const opt = Array.from(document.querySelectorAll('#ev-personel-list option')).find((o) => o.value === v);
        if (!opt) {
            toast('warning', 'Personel seçin', 'Listeden bir personel seçiniz.');
            $('ev-personel-ara').focus();
            return;
        }
        evrakGonderAc(opt.dataset.id);
    };

    function degerGoster(alan, v, dosyalar, formId) {
        if (alan.tip === 'file') {
            const d = dosyalar.filter((x) => Number(x.form_id) === formId && x.alan_anahtar === alan.anahtar && x.tur === 'yukleme');
            if (!d.length) return '<span class="ev-muted">Yüklenmedi</span>';
            return d.map((x) => `<a href="${base}/evrak-dosya-indir?paket=${aktif.paket.id}&id=${x.id}&inline=1" target="_blank" rel="noopener" style="text-decoration:underline">${esc(x.orijinal_ad)}</a> <span class="ev-muted">(${Math.round(x.boyut / 1024)} KB)</span>`).join('<br>');
        }
        if (alan.tip === 'checkbox') return v === '1' ? '✔ Onaylandı' : '<span class="ev-muted">Onaylanmadı</span>';
        if (v === undefined || v === null || v === '') return '<span class="ev-muted">-</span>';
        if (alan.tip === 'date') {
            const p = String(v).split('-');
            return p.length === 3 ? `${p[2]}.${p[1]}.${p[0]}` : esc(v);
        }
        return esc(v).replace(/\n/g, '<br>');
    }

    function render() {
        const { paket, formlar, yanitlar, dosyalar, durumAd } = aktif;
        $('evd-ad').textContent = paket.ad_soyad;
        $('evd-alt').textContent = `T.C. ***${String(paket.tc_kimlik).slice(-4)} · Gönderen: ${paket.gonderen_ad || '-'} · Son geçerlilik: ${tarih(paket.son_gecerlilik)}`;
        $('evd-durum').className = 'ev-pill ' + (DURUM_RENK[paket.durum] || 'gray');
        $('evd-durum').textContent = durumAd;

        let html = '';
        if (paket.iade_notu && paket.durum === 'iade') html += `<div class="ev-note mb-3"><strong>Düzeltme notu:</strong> ${esc(paket.iade_notu)}</div>`;
        if (['gonderildi', 'acildi'].includes(paket.durum)) html += `<div class="ev-note mb-3">Personel henüz formları göndermedi. ${paket.acilma_tarihi ? 'Bağlantıyı ' + tarih(paket.acilma_tarihi) + ' tarihinde açtı.' : 'Bağlantı henüz açılmadı.'}</div>`;

        formlar.forEach((f) => {
            const y = yanitlar[f.id] || {};
            html += `<div class="ev-section"><div class="ev-section-head">${esc(f.ad)}</div><table class="ev-answers"><tbody>`;
            f.alanlar.forEach((a) => {
                html += `<tr><td>${esc(a.etiket)}${Number(a.zorunlu) ? ' *' : ''}</td><td>${degerGoster(a, y[a.anahtar], dosyalar, Number(f.id))}</td></tr>`;
            });
            if (!f.alanlar.length) html += '<tr><td colspan="2" class="ev-muted">Bu formda alan yok.</td></tr>';
            html += '</tbody></table></div>';
        });

        const imzali = dosyalar.filter((x) => x.tur === 'imzali_nusha');
        if (imzali.length) {
            html += `<div class="ev-section"><div class="ev-section-head">İmzalı Nüshalar</div><table class="ev-answers"><tbody>` +
                imzali.map((x) => `<tr><td>${tarih(x.created_at)}</td><td><a href="${base}/evrak-dosya-indir?paket=${paket.id}&id=${x.id}&inline=1" target="_blank" rel="noopener" style="text-decoration:underline">${esc(x.orijinal_ad)}</a></td></tr>`).join('') +
                '</tbody></table></div>';
        }

        if (paket.durum === 'dolduruldu') {
            html += `<div class="ev-section"><div class="ev-section-head">İnceleme</div><div class="ev-card-body ev-col">
                <label class="ev-check"><input type="checkbox" id="evd-aktar" checked> Onaylarken eşleşen bilgileri (telefon, cinsiyet) personel kartına aktar</label>
                <div class="ev-stack"><label for="evd-not">Düzeltme gerekiyorsa personele iletilecek not</label><textarea id="evd-not" rows="2" maxlength="1000" placeholder="Örn: IBAN hatalı, diploma fotoğrafı okunmuyor"></textarea></div>
            </div></div>`;
        }
        if (['onaylandi', 'tamamlandi'].includes(paket.durum)) {
            html += `<div class="ev-section"><div class="ev-section-head">İmzalı Nüsha Yükle</div><div class="ev-card-body ev-col">
                <p class="ev-muted">Çıktıyı personele imzalattıktan sonra taranmış halini (PDF veya fotoğraf) yükleyin.</p>
                <input type="file" id="evd-imzali" accept="application/pdf,image/*">
            </div></div>`;
        }
        $('evd-icerik').innerHTML = html;

        const btn = (label, fn, cls = 'btn-outline') => `<button type="button" class="${cls}" onclick="${fn}">${label}</button>`;
        let akt = '';
        if (!['gonderildi', 'acildi', 'iade', 'iptal'].includes(paket.durum)) akt += `<a class="btn-outline" href="${base}/evrak-yazdir?id=${paket.id}" target="_blank" rel="noopener">Çıktı Al (Islak İmza)</a>`;
        if (paket.durum === 'dolduruldu') {
            akt += btn('Düzeltme İste', 'evIade()');
            akt += btn('Onayla', 'evOnayla()', 'btn');
        }
        if (paket.durum === 'onaylandi') akt += btn('Düzeltme İste', 'evIadeOnayli()');
        if (['onaylandi', 'tamamlandi'].includes(paket.durum)) akt += btn('İmzalı Nüshayı Yükle', 'evImzaliYukle()', 'btn');
        if (['gonderildi', 'acildi', 'iade'].includes(paket.durum)) akt += btn('Yeni Bağlantı Gönder', 'evYenile()', 'btn');
        if (!['tamamlandi', 'iptal'].includes(paket.durum)) akt += btn('Paketi İptal Et', 'evIptal()', 'btn-outline text-red-600');
        akt += btn('Sil', `evSil(${Number(paket.id)}, ${JSON.stringify(paket.ad_soyad).replace(/"/g, '&quot;')})`, 'btn-outline text-red-600');
        $('evd-aksiyonlar').innerHTML = akt;
    }

    window.evDetay = async (id) => {
        try {
            const r = await fetch(`${base}/evrak-paket-detay?id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const d = await r.json();
            if (!d.success) throw new Error(d.message);
            aktif = d;
            render();
            const dlg = $('dialog-evrak-detay');
            if (!dlg.open) dlg.showModal();
        } catch (e) {
            toast('error', 'Hata', e.message || 'Detay yüklenemedi.');
        }
    };

    async function post(url, fd) {
        const r = await fetch(base + url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const d = await r.json().catch(() => ({ success: false, message: 'Sunucu yanıtı okunamadı.' }));
        if (!d.success) throw new Error(d.message || 'İşlem başarısız.');
        return d;
    }

    async function islem(url, extra, onaySorusu) {
        if (onaySorusu && !confirm(onaySorusu)) return null;
        const fd = new FormData();
        fd.append('id', aktif.paket.id);
        Object.entries(extra || {}).forEach(([k, v]) => fd.append(k, v));
        try {
            const d = await post(url, fd);
            toast('success', 'Başarılı', d.message);
            return d;
        } catch (e) {
            toast('error', 'Hata', e.message);
            return null;
        }
    }

    const yenile = () => setTimeout(() => location.reload(), 1200);

    window.evOnayla = async () => {
        const d = await islem('/evrak-paket-onayla', { aktar: $('evd-aktar') && $('evd-aktar').checked ? 1 : 0 }, 'Evraklar onaylansın mı?');
        if (d) evDetay(aktif.paket.id).then(yenileListeSonra);
    };
    window.evIade = async () => {
        const not = ($('evd-not') || {}).value || '';
        if (!not.trim()) { toast('warning', 'Not gerekli', 'Personele iletilecek düzeltme notunu yazınız.'); $('evd-not') && $('evd-not').focus(); return; }
        const d = await islem('/evrak-paket-iade', { not });
        if (d) { $('dialog-evrak-detay').close(); yenile(); }
    };
    window.evIadeOnayli = async () => {
        const not = prompt('Personele iletilecek düzeltme notu:');
        if (!not || !not.trim()) return;
        const d = await islem('/evrak-paket-iade', { not });
        if (d) { $('dialog-evrak-detay').close(); yenile(); }
    };
    window.evIptal = async () => {
        const d = await islem('/evrak-paket-iptal', {}, 'Paket iptal edilsin mi? Personelin bağlantısı çalışmayacak.');
        if (d) { $('dialog-evrak-detay').close(); yenile(); }
    };
    window.evSil = async (id, ad) => {
        if (!confirm(`${ad} için evrak paketi; tüm yanıtları, yüklenen belgeleri ve imzalı nüshalarıyla birlikte KALICI olarak silinecek. Bu işlem geri alınamaz. Devam edilsin mi?`)) return;
        const fd = new FormData();
        fd.append('id', id);
        try {
            const d = await post('/evrak-paket-sil', fd);
            toast('success', 'Silindi', d.message);
            const dlg = $('dialog-evrak-detay');
            if (dlg.open) dlg.close();
            yenile();
        } catch (e) {
            toast('error', 'Hata', e.message);
        }
    };

    window.evYenile = async () => {
        const kanal = prompt('Gönderim şekli: sms, mail veya link', aktif.paket.gonderim_kanali || 'sms');
        if (!kanal) return;
        const extra = { kanal: kanal.trim().toLowerCase(), gun: 7 };
        if (extra.kanal === 'mail') {
            const email = prompt('E-posta adresi:', aktif.paket.alici_email || '');
            if (!email) return;
            extra.email = email.trim();
        }
        const d = await islem('/evrak-paket-yenile', extra);
        if (d) {
            $('dialog-evrak-detay').close();
            evrakSonucGoster(d.link, d.message, d.gonderim && d.gonderim.success);
        }
    };
    window.evImzaliYukle = async () => {
        const inp = $('evd-imzali');
        if (!inp || !inp.files.length) { toast('warning', 'Dosya seçin', 'Taranmış imzalı nüshayı seçiniz.'); return; }
        const fd = new FormData();
        fd.append('id', aktif.paket.id);
        fd.append('dosya', inp.files[0]);
        try {
            const d = await post('/evrak-imzali-yukle', fd);
            toast('success', 'Yüklendi', d.message);
            evDetay(aktif.paket.id).then(yenileListeSonra);
        } catch (e) {
            toast('error', 'Hata', e.message);
        }
    };

    let yenilemeBekliyor = false;
    function yenileListeSonra() { yenilemeBekliyor = true; }
    $('dialog-evrak-detay').addEventListener('close', () => { if (yenilemeBekliyor) location.reload(); });
    $('dialog-evrak-gonder').addEventListener('close', () => { if ($('evg-sonuc') && !$('evg-sonuc').classList.contains('hidden')) location.reload(); });

    const qid = Number(new URLSearchParams(location.search).get('paket'));
    if (qid) evDetay(qid);
})();
</script>

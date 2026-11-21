<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$kurumBasHarf = mb_strtoupper(mb_substr(trim($kurumAdi) ?: 'K', 0, 1, 'UTF-8'), 'UTF-8');

$onDoldur = [];
if ($dogrulandi && $personel) {
    foreach ($formlar as $f) {
        foreach ($f['alanlar'] as $a) {
            $mevcut = $yanitlar[$f['id']][$a['anahtar']] ?? '';
            if ($mevcut !== '') {
                continue;
            }
            if (($a['personel_alani'] ?? '') === 'telefon' && !empty($personel['telefon'])) {
                $onDoldur[$f['id']][$a['anahtar']] = preg_replace('/\D+/', '', $personel['telefon']);
            } elseif (($a['personel_alani'] ?? '') === 'cinsiyet' && !empty($personel['cinsiyet'])) {
                $map = ['kadin' => 'Kadın', 'erkek' => 'Erkek'];
                if (isset($map[$personel['cinsiyet']]) && in_array($map[$personel['cinsiyet']], $a['secenekler'] ?? [], true)) {
                    $onDoldur[$f['id']][$a['anahtar']] = $map[$personel['cinsiyet']];
                }
            }
        }
    }
}

$alanRender = function (array $form, array $alan) use ($h, $yanitlar, $dosyalar, $onDoldur) {
    $fid = (int)$form['id'];
    $k = $alan['anahtar'];
    $v = (string)($yanitlar[$fid][$k] ?? ($onDoldur[$fid][$k] ?? ''));
    $name = 'alan[' . $h($k) . ']';
    $id = 'f' . $fid . '_' . $h($k);
    $req = (int)$alan['zorunlu'] === 1;
    $etiket = $h($alan['etiket']) . ($req ? ' <span class="ep-req">*</span>' : '');
    $yardim = !empty($alan['yardim']) ? '<div class="ep-help">' . $h($alan['yardim']) . '</div>' : '';
    $err = '<div class="ep-err" data-err="' . $h($k) . '"></div>';
    $out = '<div class="ep-field" data-key="' . $h($k) . '">';

    switch ($alan['tip']) {
        case 'textarea':
            $out .= "<label for=\"{$id}\">{$etiket}</label><textarea class=\"ep-input\" id=\"{$id}\" name=\"{$name}\" maxlength=\"5000\">" . $h($v) . '</textarea>';
            break;
        case 'select':
            $out .= "<label for=\"{$id}\">{$etiket}</label><select class=\"ep-input\" id=\"{$id}\" name=\"{$name}\"><option value=\"\">Seçiniz</option>";
            foreach ($alan['secenekler'] ?? [] as $o) {
                $out .= '<option value="' . $h($o) . '"' . ($o === $v ? ' selected' : '') . '>' . $h($o) . '</option>';
            }
            $out .= '</select>';
            break;
        case 'radio':
            $out .= "<span class=\"ep-label\">{$etiket}</span><div class=\"ep-options\">";
            foreach ($alan['secenekler'] ?? [] as $o) {
                $out .= '<label class="ep-option"><input type="radio" name="' . $name . '" value="' . $h($o) . '"' . ($o === $v ? ' checked' : '') . '> ' . $h($o) . '</label>';
            }
            $out .= '</div>';
            break;
        case 'checkbox':
            $out .= '<label class="ep-check"><input type="checkbox" name="' . $name . '" value="1"' . ($v === '1' ? ' checked' : '') . '><span>' . $etiket . '</span></label>';
            break;
        case 'file':
            $yuklu = $dosyalar[$fid][$k] ?? null;
            $out .= "<span class=\"ep-label\">{$etiket}</span>"
                . '<div class="ep-file' . ($yuklu ? ' ok' : '') . '" data-file="' . $h($k) . '">'
                . '<div class="ep-file-icon">' . ($yuklu ? '✓' : '📄') . '</div>'
                . '<div class="ep-file-info"><div class="name">' . ($yuklu ? $h($yuklu) : 'Henüz yüklenmedi') . '</div><div class="ep-help">Fotoğraf çekin veya PDF seçin (en fazla 10 MB)</div></div>'
                . '<label class="ep-btn small">' . ($yuklu ? 'Değiştir' : 'Yükle') . '<input type="file" accept="image/*,application/pdf" class="ep-hidden" data-upload="' . $h($k) . '"></label>'
                . '</div>';
            break;
        default:
            $tipMap = ['text' => 'text', 'number' => 'text', 'date' => 'date', 'tel' => 'tel', 'email' => 'email', 'tc' => 'text', 'iban' => 'text'];
            $mode = ['number' => 'numeric', 'tc' => 'numeric', 'tel' => 'tel', 'email' => 'email'][$alan['tip']] ?? '';
            $ek = $mode ? " inputmode=\"{$mode}\"" : '';
            if ($alan['tip'] === 'tc') $ek .= ' maxlength="11"';
            if ($alan['tip'] === 'iban') $ek .= ' maxlength="32" placeholder="TR00 0000 0000 0000 0000 0000 00" style="text-transform:uppercase"';
            if ($alan['tip'] === 'tel') $ek .= ' placeholder="05XX XXX XX XX"';
            if ($alan['tip'] === 'email') $ek .= ' autocomplete="email"';
            $out .= "<label for=\"{$id}\">{$etiket}</label><input class=\"ep-input\" type=\"" . ($tipMap[$alan['tip']] ?? 'text') . "\" id=\"{$id}\" name=\"{$name}\" value=\"" . $h($v) . "\"{$ek}>";
    }
    return $out . $yardim . $err . '</div>';
};
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <?php echo csrf_meta(); ?>
    <title>İşe Giriş Evrakları<?php echo $kurumAdi ? ' - ' . $h($kurumAdi) : ''; ?></title>
    <link rel="icon" href="<?php echo routeUrl('/assets/images/favicon.svg'); ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?php echo routeUrl('assets/css/evrak-public.css'); ?>">
    <script src="<?php echo routeUrl('assets/js/csrf.js'); ?>"></script>
</head>
<body>
<div class="ep-wrap">
    <div class="ep-brand">
        <div class="ep-brand-logo" style="display:flex;align-items:center;justify-content:center;font-weight:700"><?php echo $h($kurumBasHarf); ?></div>
        <div><strong><?php echo $h($kurumAdi ?: 'İşe Giriş Evrakları'); ?></strong><span>İşe Giriş Evrakları</span></div>
    </div>

<?php if ($durumMesaji): ?>
    <div class="ep-card ep-center">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
        <h1>Bilgilendirme</h1>
        <p class="ep-muted"><?php echo $h($durumMesaji); ?></p>
    </div>

<?php elseif (!$dogrulandi): ?>
    <div class="ep-card">
        <h1>Merhaba<?php echo $personel ? ' ' . $h($personel['ad_soyad']) : ''; ?></h1>
        <p class="ep-muted">İşe giriş evraklarınızı bu sayfadan doldurabilirsiniz. Devam etmek için kimliğinizi doğrulayın.</p>
        <form id="ep-dogrula" style="margin-top:18px">
            <label class="ep-label" for="ep-tc">T.C. Kimlik Numaranızın son 4 hanesi</label>
            <input class="ep-input ep-otp" id="ep-tc" name="tc_son4" inputmode="numeric" pattern="\d{4}" maxlength="4" autocomplete="off" required <?php echo $kilitli ? 'disabled' : 'autofocus'; ?>>
            <div class="ep-alert <?php echo $kilitli ? 'show' : ''; ?>" id="ep-dogrula-hata"><?php echo $kilitli ? 'Çok fazla hatalı deneme yapıldı. Lütfen 30 dakika sonra tekrar deneyiniz.' : ''; ?></div>
            <button class="ep-btn primary block" style="margin-top:16px" type="submit" <?php echo $kilitli ? 'disabled' : ''; ?>>Devam Et</button>
        </form>
        <p class="ep-help" style="margin-top:14px">Bilgileriniz şifreli olarak saklanır ve yalnızca kurumunuzun yetkili personeli tarafından görüntülenir.</p>
    </div>
    <script>
    (function () {
        const base = <?php echo json_encode(appBasePath(), $jsonFlags); ?>;
        const token = <?php echo json_encode($token, $jsonFlags); ?>;
        const f = document.getElementById('ep-dogrula');
        const hata = document.getElementById('ep-dogrula-hata');
        f.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = f.querySelector('button');
            btn.disabled = true;
            hata.classList.remove('show');
            const fd = new FormData(f);
            fd.append('t', token);
            try {
                const r = await fetch(base + '/evrak-dogrula', { method: 'POST', body: fd, credentials: 'same-origin' });
                const d = await r.json();
                if (d.success) { location.reload(); return; }
                hata.textContent = d.message || 'Doğrulama başarısız.';
                hata.classList.add('show');
                if (r.status === 429) return;
            } catch (err) {
                hata.textContent = 'Bağlantı hatası. Lütfen tekrar deneyiniz.';
                hata.classList.add('show');
            }
            btn.disabled = false;
        });
    })();
    </script>

<?php else: ?>
    <?php if ($paket['durum'] === 'iade' && !empty($paket['iade_notu'])): ?>
    <div class="ep-note"><strong>Kurumunuzdan düzeltme talebi:</strong><br><?php echo nl2br($h($paket['iade_notu'])); ?></div>
    <?php endif; ?>

    <div class="ep-card" style="padding:14px 20px">
        <div class="ep-info-row"><span>Ad Soyad</span><strong><?php echo $h($personel['ad_soyad'] ?? ''); ?></strong></div>
        <div class="ep-info-row"><span>T.C. Kimlik No</span><strong>*******<?php echo $h(substr((string)($personel['tc_kimlik'] ?? ''), -4)); ?></strong></div>
        <?php if (!empty($personel['unvan'])): ?><div class="ep-info-row"><span>Görev</span><strong><?php echo $h($personel['unvan']); ?></strong></div><?php endif; ?>
        <div class="ep-info-row"><span>Son gün</span><strong><?php echo $h(date('d.m.Y', strtotime($paket['son_gecerlilik']))); ?></strong></div>
    </div>

    <div class="ep-progress" id="ep-progress"><?php for ($i = 0; $i <= count($formlar); $i++): ?><i></i><?php endfor; ?></div>

    <?php foreach ($formlar as $i => $form): ?>
    <form class="ep-card ep-step <?php echo $i === 0 ? '' : 'ep-hidden'; ?>" data-step="<?php echo $i; ?>" data-form="<?php echo (int)$form['id']; ?>" novalidate>
        <div class="ep-step-label">Adım <?php echo $i + 1; ?> / <?php echo count($formlar) + 1; ?></div>
        <h1><?php echo $h($form['ad']); ?></h1>
        <?php if (!empty($form['aciklama'])): ?><p class="ep-muted"><?php echo $h($form['aciklama']); ?></p><?php endif; ?>
        <?php if (!empty($form['metin'])): ?><div class="ep-text"><?php echo EvrakForm::renderText($form['metin'], $vars); ?></div><?php endif; ?>
        <?php foreach ($form['alanlar'] as $alan) echo $alanRender($form, $alan); ?>
        <div class="ep-alert" data-form-alert></div>
    </form>
    <?php endforeach; ?>

    <div class="ep-card ep-step ep-hidden" data-step="<?php echo count($formlar); ?>" data-ozet>
        <div class="ep-step-label">Son adım</div>
        <h1>Kontrol ve Gönderim</h1>
        <p class="ep-muted">Formlarınızı kontrol edin ve kuruma gönderin. Gönderdikten sonra kurumunuz bilgileri inceleyecek ve ıslak imza için çıktıyı size sunacaktır.</p>
        <ul class="ep-summary" id="ep-ozet-liste"></ul>
        <div class="ep-alert" id="ep-gonder-hata"></div>
    </div>

    <div class="ep-card ep-center ep-hidden" id="ep-bitti">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ok)"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <h1>Teşekkürler!</h1>
        <p class="ep-muted">Evraklarınız kuruma iletildi. Islak imza için kurumunuz sizinle iletişime geçecektir. Bu sayfayı kapatabilirsiniz.</p>
    </div>

    <div class="ep-bar" id="ep-bar">
        <div class="ep-bar-inner">
            <button type="button" class="ep-btn" id="ep-geri">Geri</button>
            <button type="button" class="ep-btn primary" id="ep-ileri">Kaydet ve Devam</button>
        </div>
    </div>

    <script>
    (function () {
        const base = <?php echo json_encode(appBasePath(), $jsonFlags); ?>;
        const formAdlari = <?php echo json_encode(array_map(fn($f) => ['id' => (int)$f['id'], 'ad' => $f['ad']], $formlar), $jsonFlags); ?>;
        const steps = Array.from(document.querySelectorAll('.ep-step'));
        const toplam = steps.length;
        const durum = {};
        let adim = 0;

        const btnGeri = document.getElementById('ep-geri');
        const btnIleri = document.getElementById('ep-ileri');

        async function postFd(url, fd) {
            const r = await fetch(base + url, { method: 'POST', body: fd, credentials: 'same-origin' });
            const d = await r.json().catch(() => ({ success: false, message: 'Sunucuya ulaşılamadı.' }));
            if (d.reload) { alert(d.message); location.reload(); throw new Error(d.message); }
            return { ok: r.ok, d };
        }

        function hatalariGoster(formEl, hatalar) {
            formEl.querySelectorAll('.ep-field').forEach((f) => {
                const m = hatalar && hatalar[f.dataset.key];
                f.classList.toggle('has-err', !!m);
                const e = f.querySelector('.ep-err');
                if (e) e.textContent = m || '';
            });
            const ilk = formEl.querySelector('.ep-field.has-err');
            if (ilk) ilk.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return !ilk;
        }

        function goster(i) {
            adim = Math.max(0, Math.min(toplam - 1, i));
            steps.forEach((s, j) => s.classList.toggle('ep-hidden', j !== adim));
            document.querySelectorAll('#ep-progress i').forEach((b, j) => b.classList.toggle('done', j <= adim));
            btnGeri.classList.toggle('ep-hidden', adim === 0);
            const ozet = steps[adim].hasAttribute('data-ozet');
            btnIleri.textContent = ozet ? 'Kuruma Gönder' : (adim === toplam - 2 ? 'Kaydet ve Kontrol Et' : 'Kaydet ve Devam');
            if (ozet) ozetCiz();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function ozetCiz() {
            document.getElementById('ep-ozet-liste').innerHTML = formAdlari.map((f, i) => {
                const ok = durum[f.id] === true;
                const ad = f.ad.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                return `<li><span>${ad}</span><button type="button" class="ep-badge ${ok ? 'ok' : 'err'}" style="border:0;cursor:pointer" data-git="${i}">${ok ? 'Tamam' : 'Eksik var'}</button></li>`;
            }).join('');
            document.querySelectorAll('[data-git]').forEach((b) => b.addEventListener('click', () => goster(Number(b.dataset.git))));
        }

        async function formKaydet(formEl) {
            const fd = new FormData(formEl);
            fd.append('form_id', formEl.dataset.form);
            formEl.querySelectorAll('input[type=checkbox][name^="alan["]').forEach((c) => { if (!c.checked) fd.append(c.name, ''); });
            const { ok, d } = await postFd('/evrak-kaydet', fd);
            if (!ok || !d.success) throw new Error(d.message || 'Kaydedilemedi.');
            const temiz = Object.keys(d.hatalar || {}).length === 0;
            durum[formEl.dataset.form] = temiz;
            return d.hatalar || {};
        }

        btnGeri.addEventListener('click', () => goster(adim - 1));

        btnIleri.addEventListener('click', async () => {
            const el = steps[adim];
            btnIleri.disabled = true;
            try {
                if (el.hasAttribute('data-ozet')) {
                    await gonder();
                    return;
                }
                const alert = el.querySelector('[data-form-alert]');
                alert.classList.remove('show');
                const hatalar = await formKaydet(el);
                if (hatalariGoster(el, hatalar)) goster(adim + 1);
            } catch (e) {
                const alert = el.querySelector('[data-form-alert]') || document.getElementById('ep-gonder-hata');
                alert.textContent = e.message || 'Bir hata oluştu.';
                alert.classList.add('show');
            } finally {
                btnIleri.disabled = false;
            }
        });

        async function gonder() {
            const hataEl = document.getElementById('ep-gonder-hata');
            hataEl.classList.remove('show');
            const { ok, d } = await postFd('/evrak-tamamla', new FormData());
            if (ok && d.success) {
                steps.forEach((s) => s.classList.add('ep-hidden'));
                document.getElementById('ep-bar').classList.add('ep-hidden');
                document.getElementById('ep-progress').classList.add('ep-hidden');
                document.getElementById('ep-bitti').classList.remove('ep-hidden');
                window.scrollTo({ top: 0 });
                return;
            }
            if (d.hatalar) {
                const idler = Object.keys(d.hatalar);
                formAdlari.forEach((f) => { durum[f.id] = !idler.includes(String(f.id)); });
                const ilkIndex = formAdlari.findIndex((f) => idler.includes(String(f.id)));
                if (ilkIndex >= 0) {
                    goster(ilkIndex);
                    hatalariGoster(steps[ilkIndex], d.hatalar[formAdlari[ilkIndex].id]);
                    return;
                }
            }
            hataEl.textContent = d.message || 'Gönderilemedi.';
            hataEl.classList.add('show');
        }

        async function sikistir(file) {
            if (!file.type.startsWith('image/') || file.size < 1.5 * 1024 * 1024 || !/jpeg|png|webp/.test(file.type)) return file;
            try {
                const bmp = await createImageBitmap(file);
                const oran = Math.min(1, 2200 / Math.max(bmp.width, bmp.height));
                const c = document.createElement('canvas');
                c.width = Math.round(bmp.width * oran);
                c.height = Math.round(bmp.height * oran);
                c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
                const blob = await new Promise((res) => c.toBlob(res, 'image/jpeg', 0.85));
                if (!blob || blob.size >= file.size) return file;
                return new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
            } catch (e) {
                return file;
            }
        }

        document.querySelectorAll('input[data-upload]').forEach((inp) => inp.addEventListener('change', async () => {
            if (!inp.files.length) return;
            const kutu = inp.closest('.ep-file');
            const formEl = inp.closest('form');
            const alan = inp.closest('.ep-field');
            const ad = kutu.querySelector('.name');
            const eski = ad.textContent;
            ad.textContent = 'Yükleniyor...';
            const fd = new FormData();
            fd.append('form_id', formEl.dataset.form);
            fd.append('alan', inp.dataset.upload);
            fd.append('dosya', await sikistir(inp.files[0]));
            try {
                const { ok, d } = await postFd('/evrak-yukle', fd);
                if (!ok || !d.success) throw new Error(d.message || 'Yüklenemedi.');
                kutu.classList.add('ok');
                kutu.querySelector('.ep-file-icon').textContent = '✓';
                ad.textContent = d.dosya;
                inp.parentElement.firstChild.textContent = 'Değiştir';
                alan.classList.remove('has-err');
            } catch (e) {
                ad.textContent = eski;
                alan.classList.add('has-err');
                alan.querySelector('.ep-err').textContent = e.message;
            } finally {
                inp.value = '';
            }
        }));

        document.querySelectorAll('input[inputmode="numeric"]').forEach((i) => i.addEventListener('input', () => { i.value = i.value.replace(/\D+/g, ''); }));

        goster(0);
    })();
    </script>
<?php endif; ?>
</div>
</body>
</html>

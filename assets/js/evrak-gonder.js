(function () {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const toast = (category, title, description) => window.showToast ? showToast({ category, title, description }) : alert(description);
    const tipAd = { bilgi: 'Bilgi Formu', kvkk: 'Onay Metni', beyan: 'Beyan', belge: 'Belge Yükleme' };

    function basePath() {
        return window.EVRAK_BASE || '';
    }

    function kanalDegisti() {
        const kanal = document.querySelector('#evg-form input[name="kanal"]:checked').value;
        $('evg-email-wrap').classList.toggle('hidden', kanal !== 'mail');
    }

    document.addEventListener('change', (e) => {
        if (e.target && e.target.name === 'kanal' && e.target.closest('#evg-form')) kanalDegisti();
    });

    window.evrakGonderAc = async function (personelId, onSuccess) {
        const dlg = $('dialog-evrak-gonder');
        window.__evrakOnSuccess = onSuccess || null;
        $('evg-form').classList.remove('hidden');
        $('evg-sonuc').classList.add('hidden');
        $('evg-gonder-btn').classList.remove('hidden');
        $('evg-personel-id').value = personelId;
        $('evg-formlar').innerHTML = '<p class="ev-muted">Yükleniyor...</p>';
        $('evg-personel').textContent = '';
        dlg.showModal();
        try {
            const r = await fetch(`${basePath()}/evrak-paket-secenekleri?personel_id=${encodeURIComponent(personelId)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const d = await r.json();
            if (!d.success) throw new Error(d.message);
            $('evg-personel').textContent = d.personel.ad_soyad;
            $('evg-tel').textContent = d.personel.telefon ? d.personel.telefon : 'Telefon kayıtlı değil';
            if (!d.formlar.length) {
                $('evg-formlar').innerHTML = `<div class="ev-note">Aktif evrak formu yok. <a href="${basePath()}/evrak-formlari" style="text-decoration:underline">Evrak Formları</a> sayfasından hazır şablonları ekleyebilirsiniz.</div>`;
                $('evg-gonder-btn').classList.add('hidden');
                return;
            }
            $('evg-formlar').innerHTML = d.formlar.map((f) => `
                <label class="ev-form-choice">
                    <input type="checkbox" class="input" name="form_ids[]" value="${Number(f.id)}" checked>
                    <span><strong style="font-size:.875rem">${esc(f.ad)}</strong><br><span class="ev-muted">${esc(tipAd[f.tip] || f.tip)} · ${Number(f.alan_sayisi)} alan${f.aciklama ? ' · ' + esc(f.aciklama) : ''}</span></span>
                </label>`).join('');
            kanalDegisti();
        } catch (e) {
            $('evg-formlar').innerHTML = `<div class="ev-note">${esc(e.message || 'Formlar yüklenemedi.')}</div>`;
        }
    };

    window.evrakGonder = async function () {
        const btn = $('evg-gonder-btn');
        const fd = new FormData($('evg-form'));
        if (!fd.getAll('form_ids[]').length) {
            toast('warning', 'Form seçilmedi', 'En az bir form seçmelisiniz.');
            return;
        }
        btn.disabled = true;
        try {
            const r = await fetch(`${basePath()}/evrak-paket-gonder`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const d = await r.json().catch(() => ({ success: false, message: 'Sunucu yanıtı okunamadı.' }));
            if (!d.success) throw new Error(d.message);
            window.evrakSonucGoster(d.link, d.message, d.gonderim && d.gonderim.success);
            toast(d.gonderim && d.gonderim.success ? 'success' : 'warning', 'Evrak paketi oluşturuldu', d.message);
            if (typeof window.__evrakOnSuccess === 'function') window.__evrakOnSuccess(d);
        } catch (e) {
            toast('error', 'Gönderilemedi', e.message || 'İşlem başarısız.');
        } finally {
            btn.disabled = false;
        }
    };

    window.evrakSonucGoster = function (link, mesaj, basarili) {
        const dlg = $('dialog-evrak-gonder');
        if (!dlg.open) dlg.showModal();
        $('evg-form').classList.add('hidden');
        $('evg-gonder-btn').classList.add('hidden');
        $('evg-sonuc').classList.remove('hidden');
        $('evg-sonuc-mesaj').textContent = mesaj;
        $('evg-sonuc-mesaj').classList.toggle('ok', !!basarili);
        $('evg-link').value = link;
        const metin = 'İşe giriş evraklarınızı doldurmak için bağlantı: ' + link;
        $('evg-wa').href = 'https://wa.me/?text=' + encodeURIComponent(metin);
    };

    window.evrakLinkKopyala = async function () {
        const v = $('evg-link').value;
        try {
            await navigator.clipboard.writeText(v);
        } catch (e) {
            $('evg-link').select();
            document.execCommand('copy');
        }
        toast('success', 'Kopyalandı', 'Bağlantı panoya kopyalandı.');
    };
})();

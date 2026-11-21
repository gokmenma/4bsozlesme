<link rel="stylesheet" href="<?php echo routeUrl('assets/css/evrak.css'); ?>">
<dialog id="dialog-evrak-gonder" class="dialog" style="max-width: 600px; width: 92vw;" onclick="if (event.target === this) this.close()">
  <div class="dialog-content ev-scope bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" style="width: 600px; max-width: 100%;" onclick="event.stopPropagation()">
    <header class="mb-4">
      <h2 class="text-lg font-semibold">İşe Giriş Evrakı Gönder</h2>
      <p class="ev-muted" id="evg-personel"></p>
    </header>

    <form id="evg-form" class="ev-col">
      <input type="hidden" name="personel_id" id="evg-personel-id">
      <div class="ev-stack">
        <label>Doldurulacak Formlar</label>
        <div id="evg-formlar" style="display:flex;flex-direction:column;gap:.5rem;max-height:260px;overflow:auto;"></div>
      </div>

      <div class="ev-stack">
        <label>Gönderim Şekli</label>
        <div class="ev-channel">
          <label><input type="radio" name="kanal" value="sms" checked> SMS <small id="evg-tel">Kayıtlı telefona</small></label>
          <label><input type="radio" name="kanal" value="mail"> E-posta <small>Adres girilecek</small></label>
          <label><input type="radio" name="kanal" value="link"> Bağlantı <small>Kopyala / WhatsApp</small></label>
        </div>
      </div>

      <div class="ev-stack hidden" id="evg-email-wrap">
        <label for="evg-email">E-posta Adresi</label>
        <input type="email" name="email" id="evg-email" placeholder="ornek@eposta.com">
      </div>

      <div class="ev-stack">
        <label>Bağlantı Geçerlilik Süresi</label>
        <?php echo renderCustomSelect('evg-gun', 'gun', [['value' => '3', 'label' => '3 gün'], ['value' => '7', 'label' => '7 gün'], ['value' => '14', 'label' => '14 gün'], ['value' => '30', 'label' => '30 gün']], '7', 'w-full'); ?>
      </div>

      <p class="ev-muted">Personel bağlantıyı açtığında T.C. Kimlik numarasının son 4 hanesiyle doğrulama yapar. Bu personele ait açık bir paket varsa iptal edilip yenisi oluşturulur.</p>
    </form>

    <div id="evg-sonuc" class="ev-col hidden">
      <div class="ev-note" id="evg-sonuc-mesaj"></div>
      <div class="ev-linkbox">
        <input type="text" id="evg-link" readonly onclick="this.select()">
        <button type="button" class="btn-sm-outline" onclick="evrakLinkKopyala()">Kopyala</button>
        <a class="btn-sm-outline" id="evg-wa" target="_blank" rel="noopener noreferrer">WhatsApp</a>
      </div>
    </div>

    <footer class="flex justify-end gap-2 mt-5">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">Kapat</button>
      <button type="button" class="btn" id="evg-gonder-btn" onclick="evrakGonder()">Gönder</button>
    </footer>
  </div>
</dialog>
<script>window.EVRAK_BASE = <?php echo json_encode(appBasePath(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;</script>
<script src="<?php echo routeUrl('assets/js/evrak-gonder.js'); ?>"></script>

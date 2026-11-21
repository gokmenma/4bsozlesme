<?php

class EvrakPublicController extends Controller {

    private const SESSION_KEY = 'evrak_dogrulama';

    private function json(array $data, int $code = 200): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function log(array $paket, string $action, string $desc): void {
        (new ActivityLog())->log($action, $desc, null, (int)$paket['tenant_id']);
    }

    private function sessionPaket(): ?array {
        $oturum = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($oturum) || empty($oturum['paket_id']) || empty($oturum['token_hash'])) {
            return null;
        }
        if (time() - (int)($oturum['zaman'] ?? 0) > 7200) {
            unset($_SESSION[self::SESSION_KEY]);
            return null;
        }
        $model = new EvrakPaket();
        $paket = $model->findById((int)$oturum['paket_id']);
        if (!$paket || !hash_equals($paket['token_hash'], $oturum['token_hash'])) {
            unset($_SESSION[self::SESSION_KEY]);
            return null;
        }
        $_SESSION[self::SESSION_KEY]['zaman'] = time();
        return $paket;
    }

    private function requireEditable(): array {
        $paket = $this->sessionPaket();
        if (!$paket) {
            $this->json(['success' => false, 'message' => 'Oturumunuz sona erdi. Lütfen bağlantıyı yeniden açınız.', 'reload' => true], 401);
        }
        $model = new EvrakPaket();
        if (!in_array($paket['durum'], EvrakPaket::DUZENLENEBILIR, true) || $model->isExpired($paket)) {
            $this->json(['success' => false, 'message' => 'Bu evrak paketi artık düzenlenemez.', 'reload' => true], 409);
        }
        return $paket;
    }

    private function findForm(array $paket, int $formId): ?array {
        foreach ((new EvrakPaket())->getForms((int)$paket['id']) as $f) {
            if ((int)$f['id'] === $formId) {
                return $f;
            }
        }
        return null;
    }

    public function show() {
        header('Cache-Control: no-store');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');

        $model = new EvrakPaket();
        $token = (string)($_GET['t'] ?? '');
        $paket = $token !== '' ? $model->findByToken($token) : null;

        $durumMesaji = null;
        if (!$paket) {
            $durumMesaji = 'Bağlantı geçersiz. Lütfen size gönderilen bağlantıyı eksiksiz açtığınızdan emin olun.';
        } elseif ($paket['durum'] === 'iptal') {
            $durumMesaji = 'Bu bağlantı kurum tarafından iptal edilmiştir.';
        } elseif (in_array($paket['durum'], ['dolduruldu', 'onaylandi', 'tamamlandi'], true)) {
            $durumMesaji = 'Evraklarınız kuruma iletilmiştir. Teşekkür ederiz. Islak imza için kurumunuz sizinle iletişime geçecektir.';
        } elseif ($model->isExpired($paket)) {
            $durumMesaji = 'Bu bağlantının süresi dolmuştur. Yeni bağlantı için kurumunuzla iletişime geçiniz.';
        }

        $dogrulandi = false;
        $oturum = $_SESSION[self::SESSION_KEY] ?? null;
        if ($paket && !$durumMesaji && is_array($oturum) && (int)($oturum['paket_id'] ?? 0) === (int)$paket['id']) {
            $dogrulandi = $this->sessionPaket() !== null;
        }

        $settings = $paket ? (new Definition())->getSettings((int)$paket['tenant_id']) : [];
        $kurumAdi = $settings['kurum_adi'] ?? '';
        $personel = $paket ? $model->getPersonnel((int)$paket['personel_id'], (int)$paket['tenant_id']) : null;
        $formlar = [];
        $yanitlar = [];
        $dosyalar = [];
        $vars = [];
        if ($dogrulandi) {
            $formlar = $model->getForms((int)$paket['id']);
            $yanitlar = $model->getAnswers((int)$paket['id']);
            $dosyalar = (new EvrakDosya())->uploadedKeys((int)$paket['id']);
            $vars = EvrakForm::templateVars($settings, $personel ?: []);
        }
        $kilitli = $paket ? $model->isLocked($paket) : false;

        include __DIR__ . '/../pages/evrak/public.php';
        exit;
    }

    public function dogrula() {
        $model = new EvrakPaket();
        $paket = $model->findByToken((string)($_POST['t'] ?? ''));
        if (!$paket || !in_array($paket['durum'], EvrakPaket::DUZENLENEBILIR, true) || $model->isExpired($paket)) {
            $this->json(['success' => false, 'message' => 'Bağlantı geçersiz veya süresi dolmuş.'], 404);
        }
        if ($model->isLocked($paket)) {
            $this->json(['success' => false, 'message' => 'Çok fazla hatalı deneme yapıldı. Lütfen 30 dakika sonra tekrar deneyiniz.'], 429);
        }
        $tcSon4 = preg_replace('/\D+/', '', (string)($_POST['tc_son4'] ?? ''));
        if (!$model->verifyIdentity($paket, $tcSon4)) {
            $this->log($paket, 'Evrak Doğrulama Hatası', 'Paket #' . $paket['id'] . ' için hatalı T.C. doğrulama denemesi');
            $kalan = EvrakPaket::MAX_DOGRULAMA_DENEME - ((int)$paket['dogrulama_deneme'] + 1);
            $mesaj = $kalan > 0
                ? "Girdiğiniz bilgi eşleşmedi. Kalan deneme hakkınız: {$kalan}"
                : 'Çok fazla hatalı deneme yapıldı. Lütfen 30 dakika sonra tekrar deneyiniz.';
            $this->json(['success' => false, 'message' => $mesaj], 422);
        }
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'paket_id'   => (int)$paket['id'],
            'token_hash' => $paket['token_hash'],
            'zaman'      => time(),
        ];
        $this->log($paket, 'Evrak Bağlantısı Açıldı', 'Paket #' . $paket['id'] . ' personel tarafından doğrulandı');
        $this->json(['success' => true]);
    }

    public function kaydet() {
        $paket = $this->requireEditable();
        $model = new EvrakPaket();
        $form = $this->findForm($paket, (int)($_POST['form_id'] ?? 0));
        if (!$form) {
            $this->json(['success' => false, 'message' => 'Form bulunamadı.'], 404);
        }
        try {
            $girdi = is_array($_POST['alan'] ?? null) ? $_POST['alan'] : [];
            $model->saveAnswers((int)$paket['id'], $form, $girdi);
            if ($paket['durum'] === 'gonderildi') {
                $model->setStatus((int)$paket['id'], 'acildi');
            }
            $yanitlar = $model->getAnswers((int)$paket['id'])[$form['id']] ?? [];
            $dosyalar = (new EvrakDosya())->uploadedKeys((int)$paket['id'])[$form['id']] ?? [];
            $hatalar = $model->validateForm($form, $yanitlar, $dosyalar);
            $this->json(['success' => true, 'hatalar' => (object)$hatalar]);
        } catch (Throwable $e) {
            error_log('EvrakPublic kaydet: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Bilgiler kaydedilemedi. Lütfen tekrar deneyiniz.'], 500);
        }
    }

    public function yukle() {
        $paket = $this->requireEditable();
        $form = $this->findForm($paket, (int)($_POST['form_id'] ?? 0));
        $anahtar = (string)($_POST['alan'] ?? '');
        $alan = null;
        foreach ($form['alanlar'] ?? [] as $a) {
            if ($a['anahtar'] === $anahtar && $a['tip'] === 'file') {
                $alan = $a;
            }
        }
        if (!$form || !$alan || empty($_FILES['dosya'])) {
            $this->json(['success' => false, 'message' => 'Geçersiz yükleme isteği.'], 422);
        }
        try {
            $d = (new EvrakDosya())->store($paket, $_FILES['dosya'], 'yukleme', (int)$form['id'], $anahtar, 'personel', null);
            $this->log($paket, 'Evrak Belge Yükleme', 'Paket #' . $paket['id'] . ' - ' . $alan['etiket']);
            $this->json(['success' => true, 'dosya' => $d['orijinal_ad']]);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            error_log('EvrakPublic yukle: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Dosya yüklenemedi. Lütfen tekrar deneyiniz.'], 500);
        }
    }

    public function tamamla() {
        $paket = $this->requireEditable();
        $model = new EvrakPaket();
        $yanitlar = $model->getAnswers((int)$paket['id']);
        $dosyalar = (new EvrakDosya())->uploadedKeys((int)$paket['id']);
        $tumHatalar = [];
        foreach ($model->getForms((int)$paket['id']) as $form) {
            $h = $model->validateForm($form, $yanitlar[$form['id']] ?? [], $dosyalar[$form['id']] ?? []);
            if (!empty($h)) {
                $tumHatalar[$form['id']] = $h;
            }
        }
        if (!empty($tumHatalar)) {
            $this->json(['success' => false, 'message' => 'Eksik veya hatalı alanlar var. Lütfen işaretli alanları kontrol ediniz.', 'hatalar' => $tumHatalar], 422);
        }
        $model->setStatus((int)$paket['id'], 'dolduruldu', ['gonderim_tarihi' => date('Y-m-d H:i:s'), 'iade_notu' => null]);

        $personel = $model->getPersonnel((int)$paket['personel_id'], (int)$paket['tenant_id']);
        $ad = $personel['ad_soyad'] ?? 'Personel';
        if (!empty($paket['gonderen_user_id'])) {
            try {
                (new Notification())->notify((int)$paket['gonderen_user_id'], 'İşe giriş evrakları dolduruldu', $ad . ' evraklarını doldurdu, incelemenizi bekliyor.', 'evrak', routeUrl('/evrak-takip?durum=dolduruldu'), (int)$paket['tenant_id']);
            } catch (Throwable $e) {
                error_log('Evrak bildirim hatası: ' . $e->getMessage());
            }
        }
        $this->log($paket, 'Evrak Paketi Tamamlandı', 'Paket #' . $paket['id'] . ' - ' . $ad . ' evrakları gönderdi');
        unset($_SESSION[self::SESSION_KEY]);
        $this->json(['success' => true]);
    }
}

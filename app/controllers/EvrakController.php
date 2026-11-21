<?php

class EvrakController extends Controller {

    private const ROUTE_FORMLAR = '/evrak-formlari';
    private const ROUTE_TAKIP = '/evrak-takip';

    private function tenantId(): int {
        return (int)($_SESSION['tenant_id'] ?? 0);
    }

    private function userId(): int {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    private function canAccess(string $route): bool {
        if (empty($_SESSION['user_id']) || $this->tenantId() <= 0) {
            return false;
        }
        $rp = new RolePermission();
        return $rp->hasAccess($_SESSION['role_id'] ?? null, $route, $_SESSION['role'] ?? '');
    }

    private function requireJsonAccess(string $route): void {
        if (!$this->canAccess($route)) {
            $this->json(['success' => false, 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.'], 403);
        }
    }

    private function requirePageAccess(string $route): void {
        if (!$this->canAccess($route)) {
            http_response_code(403);
            echo 'Bu sayfaya erişim yetkiniz bulunmamaktadır.';
            exit;
        }
    }

    private function json(array $data, int $code = 200): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function fail(Throwable $e, string $genel = 'İşlem sırasında bir hata oluştu.'): void {
        if ($e instanceof InvalidArgumentException) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        error_log('EvrakController: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        $this->json(['success' => false, 'message' => $genel], 500);
    }

    private function log(string $action, string $desc): void {
        (new ActivityLog())->log($action, $desc);
    }

    private function jsonBody(): array {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        return is_array($data) ? $data : $_POST;
    }

    public static function publicLink(string $token): string {
        $base = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '');
        if ($base === '') {
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $host = preg_replace('/[^a-zA-Z0-9.\-:]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
            $base = ($https ? 'https://' : 'http://') . $host . appBasePath();
        }
        return rtrim($base, '/') . '/evrak?t=' . $token;
    }

    public function formlar() {
        $this->requirePageAccess(self::ROUTE_FORMLAR);
        $model = new EvrakForm();
        return [
            'evrakFormlari'   => $model->listByTenant($this->tenantId()),
            'formTipleri'     => EvrakForm::TIPLER,
            'alanTipleri'     => EvrakForm::ALAN_TIPLERI,
            'personelAlanlari'=> EvrakForm::PERSONEL_ALANLARI,
        ];
    }

    public function formGet() {
        $this->requireJsonAccess(self::ROUTE_FORMLAR);
        $form = (new EvrakForm())->findForTenant((int)($_GET['id'] ?? 0), $this->tenantId());
        if (!$form) {
            $this->json(['success' => false, 'message' => 'Form bulunamadı.'], 404);
        }
        $this->json(['success' => true, 'form' => $form]);
    }

    public function formKaydet() {
        $this->requireJsonAccess(self::ROUTE_FORMLAR);
        try {
            $data = $this->jsonBody();
            $yeni = empty($data['id']);
            $id = (new EvrakForm())->saveForm($this->tenantId(), $data, $this->userId());
            $this->log($yeni ? 'Evrak Formu Oluşturma' : 'Evrak Formu Güncelleme', 'Form #' . $id . ' - ' . trim((string)($data['ad'] ?? '')));
            $this->json(['success' => true, 'id' => $id, 'message' => 'Form kaydedildi.']);
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    public function formSil() {
        $this->requireJsonAccess(self::ROUTE_FORMLAR);
        $id = (int)($_POST['id'] ?? 0);
        if (!(new EvrakForm())->softDelete($id, $this->tenantId())) {
            $this->json(['success' => false, 'message' => 'Form bulunamadı.'], 404);
        }
        $this->log('Evrak Formu Silme', 'Form #' . $id);
        $this->json(['success' => true, 'message' => 'Form silindi.']);
    }

    public function sablonYukle() {
        $this->requireJsonAccess(self::ROUTE_FORMLAR);
        try {
            $adet = (new EvrakForm())->copySystemTemplates($this->tenantId(), $this->userId());
            $this->log('Evrak Şablonları Yükleme', $adet . ' hazır form kuruma kopyalandı');
            $this->json(['success' => true, 'message' => $adet . ' hazır form eklendi.']);
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    public function takip() {
        $this->requirePageAccess(self::ROUTE_TAKIP);
        $paketModel = new EvrakPaket();
        $durum = (string)($_GET['durum'] ?? '');
        $arama = trim((string)($_GET['q'] ?? ''));
        return [
            'paketler'      => $paketModel->listByTenant($this->tenantId(), $durum, mb_substr($arama, 0, 100)),
            'durumSayilari' => $paketModel->statusCounts($this->tenantId()),
            'durumlar'      => EvrakPaket::DURUMLAR,
            'seciliDurum'   => $durum,
            'arama'         => $arama,
            'personeller'   => $paketModel->personnelOptions($this->tenantId()),
        ];
    }

    public function paketSecenekleri() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        $personelId = (int)($_GET['personel_id'] ?? 0);
        $paketModel = new EvrakPaket();
        $personel = $paketModel->getPersonnel($personelId, $this->tenantId());
        if (!$personel) {
            $this->json(['success' => false, 'message' => 'Personel bulunamadı.'], 404);
        }
        $formlar = array_map(fn($f) => [
            'id' => (int)$f['id'], 'ad' => $f['ad'], 'tip' => $f['tip'], 'aciklama' => $f['aciklama'], 'alan_sayisi' => (int)$f['alan_sayisi'],
        ], (new EvrakForm())->listByTenant($this->tenantId(), true));
        $this->json([
            'success'  => true,
            'personel' => ['id' => (int)$personel['id'], 'ad_soyad' => $personel['ad_soyad'], 'telefon' => $personel['telefon']],
            'formlar'  => $formlar,
        ]);
    }

    public function paketGonder() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        try {
            $kanal = (string)($_POST['kanal'] ?? 'link');
            if (!in_array($kanal, ['link', 'sms', 'mail'], true)) {
                throw new InvalidArgumentException('Geçersiz gönderim kanalı.');
            }
            $email = trim((string)($_POST['email'] ?? ''));
            if ($kanal === 'mail' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Geçerli bir e-posta adresi giriniz.');
            }
            $formIds = $_POST['form_ids'] ?? [];
            $sonuc = (new EvrakPaket())->createPackage(
                $this->tenantId(),
                (int)($_POST['personel_id'] ?? 0),
                is_array($formIds) ? $formIds : [],
                $kanal,
                (int)($_POST['gun'] ?? 7),
                $email ?: null,
                $this->userId()
            );
            $paket = (new EvrakPaket())->findForTenant($sonuc['id'], $this->tenantId());
            $link = self::publicLink($sonuc['token']);
            $gonderim = $this->deliver($paket, $link, $kanal, $email);

            $this->log('Evrak Paketi Gönderme', 'Paket #' . $sonuc['id'] . ' - ' . $sonuc['personel']['ad_soyad'] . ' (' . $kanal . ')' . ($gonderim['success'] ? '' : ' - gönderim başarısız'));
            $this->json([
                'success'  => true,
                'paket_id' => $sonuc['id'],
                'link'     => $link,
                'gonderim' => $gonderim,
                'message'  => $gonderim['message'],
            ]);
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    public function paketYenile() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        try {
            $id = (int)($_POST['id'] ?? 0);
            $model = new EvrakPaket();
            $token = $model->renewToken($id, $this->tenantId(), (int)($_POST['gun'] ?? 7));
            $paket = $model->findForTenant($id, $this->tenantId());
            $kanal = (string)($_POST['kanal'] ?? $paket['gonderim_kanali']);
            if (!in_array($kanal, ['link', 'sms', 'mail'], true)) {
                $kanal = 'link';
            }
            $email = trim((string)($_POST['email'] ?? ($paket['alici_email'] ?? '')));
            $link = self::publicLink($token);
            $gonderim = $this->deliver($paket, $link, $kanal, $email);
            $this->log('Evrak Paketi Hatırlatma', 'Paket #' . $id . ' için yeni bağlantı oluşturuldu (' . $kanal . ')');
            $this->json(['success' => true, 'link' => $link, 'gonderim' => $gonderim, 'message' => $gonderim['message']]);
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    private function deliver(array $paket, string $link, string $kanal, string $email): array {
        $settings = (new Definition())->getSettings($this->tenantId());
        $kurum = $settings['kurum_adi'] ?? 'Kurum';
        $sonGun = date('d.m.Y', strtotime($paket['son_gecerlilik']));

        if ($kanal === 'sms') {
            if (empty($paket['telefon'])) {
                return ['success' => false, 'message' => 'Personelin telefon numarası kayıtlı değil. Bağlantıyı kopyalayıp iletebilirsiniz.'];
            }
            $mesaj = "{$kurum}: Sayın {$paket['ad_soyad']}, işe giriş evraklarınızı doldurmak için bağlantı: {$link} Son gün: {$sonGun}";
            $r = SmsGonderService::gonder($mesaj, $paket['telefon'], true, $this->userId());
            return $r['success']
                ? ['success' => true, 'message' => 'Bağlantı SMS ile gönderildi.']
                : ['success' => false, 'message' => 'SMS gönderilemedi: ' . $r['message'] . ' Bağlantıyı kopyalayıp iletebilirsiniz.'];
        }

        if ($kanal === 'mail') {
            $ad = htmlspecialchars($paket['ad_soyad'], ENT_QUOTES, 'UTF-8');
            $kurumHtml = htmlspecialchars($kurum, ENT_QUOTES, 'UTF-8');
            $linkHtml = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $body = "<p>Sayın {$ad},</p><p>{$kurumHtml} işe giriş evraklarınızı aşağıdaki bağlantıdan doldurabilirsiniz. Bağlantıyı açtığınızda doğrulama için T.C. Kimlik numaranızın son 4 hanesi istenecektir.</p>"
                . "<p><a href=\"{$linkHtml}\" style=\"display:inline-block;padding:10px 18px;background:#18181b;color:#fff;border-radius:8px;text-decoration:none\">Evrakları Doldur</a></p>"
                . "<p style=\"color:#71717a;font-size:13px\">Bağlantı {$sonGun} tarihine kadar geçerlidir. Bu e-postayı siz talep etmediyseniz dikkate almayınız.</p>";
            try {
                $ok = MailGonderService::gonder($email, $kurum . ' - İşe Giriş Evrakları', $body, $paket['ad_soyad'], $this->tenantId());
            } catch (Throwable $e) {
                error_log('Evrak mail hatası: ' . $e->getMessage());
                $ok = false;
            }
            return $ok
                ? ['success' => true, 'message' => 'Bağlantı e-posta ile gönderildi.']
                : ['success' => false, 'message' => 'E-posta gönderilemedi. Bağlantıyı kopyalayıp iletebilirsiniz.'];
        }

        return ['success' => true, 'message' => 'Bağlantı oluşturuldu. Kopyalayıp personele iletebilirsiniz.'];
    }

    public function paketDetay() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        $model = new EvrakPaket();
        $paket = $model->findForTenant((int)($_GET['id'] ?? 0), $this->tenantId());
        if (!$paket) {
            $this->json(['success' => false, 'message' => 'Paket bulunamadı.'], 404);
        }
        $formlar = $model->getForms((int)$paket['id']);
        $yanitlar = $model->getAnswers((int)$paket['id']);
        $dosyalar = (new EvrakDosya())->listForPackage((int)$paket['id']);

        $this->log('Evrak Paketi Görüntüleme', 'Paket #' . $paket['id'] . ' - ' . $paket['ad_soyad']);
        unset($paket['token_hash']);
        $this->json([
            'success'  => true,
            'paket'    => $paket,
            'durumAd'  => EvrakPaket::DURUMLAR[$paket['durum']] ?? $paket['durum'],
            'formlar'  => $formlar,
            'yanitlar' => (object)$yanitlar,
            'dosyalar' => $dosyalar,
        ]);
    }

    public function paketOnayla() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        try {
            $model = new EvrakPaket();
            $paket = $model->findForTenant((int)($_POST['id'] ?? 0), $this->tenantId());
            if (!$paket || $paket['durum'] !== 'dolduruldu') {
                throw new InvalidArgumentException('Yalnızca inceleme bekleyen paketler onaylanabilir.');
            }
            $db = $model->getDb();
            $db->beginTransaction();
            try {
                $aktarilan = !empty($_POST['aktar']) ? $model->transferToPersonnel($paket) : [];
                $model->setStatus((int)$paket['id'], 'onaylandi', ['onay_tarihi' => date('Y-m-d H:i:s'), 'onaylayan_user_id' => $this->userId()]);
                $db->commit();
            } catch (Throwable $e) {
                $db->rollBack();
                throw $e;
            }
            $this->log('Evrak Paketi Onaylama', 'Paket #' . $paket['id'] . ' - ' . $paket['ad_soyad'] . (empty($aktarilan) ? '' : ' | Personel kartına aktarılan: ' . implode(', ', $aktarilan)));
            $this->json(['success' => true, 'message' => 'Evraklar onaylandı. Çıktı alıp ıslak imzaya sunabilirsiniz.', 'aktarilan' => $aktarilan]);
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    public function paketIade() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        try {
            $model = new EvrakPaket();
            $paket = $model->findForTenant((int)($_POST['id'] ?? 0), $this->tenantId());
            $not = mb_substr(trim((string)($_POST['not'] ?? '')), 0, 1000);
            if (!$paket || !in_array($paket['durum'], ['dolduruldu', 'onaylandi'], true)) {
                throw new InvalidArgumentException('Bu paket düzeltmeye gönderilemez.');
            }
            if ($not === '') {
                throw new InvalidArgumentException('Personele iletilecek düzeltme notunu yazınız.');
            }
            $ek = ['iade_notu' => $not];
            if ($model->isExpired($paket)) {
                $ek['son_gecerlilik'] = date('Y-m-d H:i:s', strtotime('+3 days'));
            }
            $model->setStatus((int)$paket['id'], 'iade', $ek);
            $this->log('Evrak Paketi İade', 'Paket #' . $paket['id'] . ' - ' . $paket['ad_soyad'] . ' | Not: ' . $not);
            $this->json(['success' => true, 'message' => 'Paket düzeltme için personele açıldı. Aynı bağlantıyla devam edebilir; gerekirse "Yeniden Gönder" kullanın.']);
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    public function paketIptal() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        $model = new EvrakPaket();
        $paket = $model->findForTenant((int)($_POST['id'] ?? 0), $this->tenantId());
        if (!$paket || in_array($paket['durum'], ['tamamlandi', 'iptal'], true)) {
            $this->json(['success' => false, 'message' => 'Bu paket iptal edilemez.'], 422);
        }
        $model->setStatus((int)$paket['id'], 'iptal');
        $this->log('Evrak Paketi İptal', 'Paket #' . $paket['id'] . ' - ' . $paket['ad_soyad']);
        $this->json(['success' => true, 'message' => 'Paket iptal edildi; bağlantı artık çalışmaz.']);
    }

    public function paketSil() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        try {
            $model = new EvrakPaket();
            $paket = $model->findForTenant((int)($_POST['id'] ?? 0), $this->tenantId());
            if (!$paket) {
                throw new InvalidArgumentException('Paket bulunamadı.');
            }
            $model->deletePackage((int)$paket['id'], $this->tenantId());
            $this->log('Evrak Paketi Silme', 'Paket #' . $paket['id'] . ' - ' . $paket['ad_soyad'] . ' (' . (EvrakPaket::DURUMLAR[$paket['durum']] ?? $paket['durum']) . ') yanıtları ve dosyalarıyla birlikte silindi');
            $this->json(['success' => true, 'message' => 'Evrak paketi kalıcı olarak silindi.']);
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    public function dosyaIndir() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        $paket = (new EvrakPaket())->findForTenant((int)($_GET['paket'] ?? 0), $this->tenantId());
        $dosyaModel = new EvrakDosya();
        $dosya = $paket ? $dosyaModel->findInPackage((int)($_GET['id'] ?? 0), (int)$paket['id']) : null;
        $icerik = $dosya ? $dosyaModel->read($dosya) : null;
        if (!$icerik) {
            http_response_code(404);
            echo 'Dosya bulunamadı.';
            exit;
        }
        $this->log('Evrak Dosyası İndirme', 'Paket #' . $paket['id'] . ' - ' . $dosya['orijinal_ad']);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $inline = !empty($_GET['inline']);
        $ad = str_replace(['"', "\r", "\n"], '', $dosya['orijinal_ad']);
        header('Content-Type: ' . $dosya['mime']);
        header('Content-Length: ' . strlen($icerik['content']));
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $ad . '"; filename*=UTF-8\'\'' . rawurlencode($dosya['orijinal_ad']));
        echo $icerik['content'];
        exit;
    }

    public function imzaliYukle() {
        $this->requireJsonAccess(self::ROUTE_TAKIP);
        try {
            $model = new EvrakPaket();
            $paket = $model->findForTenant((int)($_POST['id'] ?? 0), $this->tenantId());
            if (!$paket || !in_array($paket['durum'], ['onaylandi', 'tamamlandi'], true)) {
                throw new InvalidArgumentException('İmzalı nüsha yalnızca onaylanmış paketlere yüklenebilir.');
            }
            if (empty($_FILES['dosya'])) {
                throw new InvalidArgumentException('Dosya seçiniz.');
            }
            $d = (new EvrakDosya())->store($paket, $_FILES['dosya'], 'imzali_nusha', null, null, 'kullanici', $this->userId());
            $model->setStatus((int)$paket['id'], 'tamamlandi');
            $this->log('Evrak İmzalı Nüsha Yükleme', 'Paket #' . $paket['id'] . ' - ' . $paket['ad_soyad'] . ' - ' . $d['orijinal_ad']);
            $this->json(['success' => true, 'message' => 'İmzalı nüsha yüklendi, paket tamamlandı.']);
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    public function yazdir() {
        if (!$this->canAccess(self::ROUTE_TAKIP)) {
            http_response_code(403);
            echo 'Bu sayfaya erişim yetkiniz bulunmamaktadır.';
            exit;
        }
        $model = new EvrakPaket();
        $paket = $model->findForTenant((int)($_GET['id'] ?? 0), $this->tenantId());
        if (!$paket || in_array($paket['durum'], ['iptal'], true)) {
            http_response_code(404);
            echo 'Paket bulunamadı.';
            exit;
        }
        $personel = $model->getPersonnel((int)$paket['personel_id'], $this->tenantId());
        $settings = (new Definition())->getSettings($this->tenantId());
        $formlar = $model->getForms((int)$paket['id']);
        $yanitlar = $model->getAnswers((int)$paket['id']);
        $dosyalar = (new EvrakDosya())->uploadedKeys((int)$paket['id']);
        $vars = EvrakForm::templateVars($settings, $personel ?: []);

        $this->log('Evrak Çıktısı Alma', 'Paket #' . $paket['id'] . ' - ' . $paket['ad_soyad']);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        include __DIR__ . '/../pages/evrak/yazdir.php';
        exit;
    }
}

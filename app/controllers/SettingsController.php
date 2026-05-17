<?php

class SettingsController extends Controller {

    public function __construct() {
        // Admin veya Superadmin kontrolü
        if (($_SESSION['role'] ?? '') !== 'superadmin' && ($_SESSION['role'] ?? '') !== 'admin') {
            if ($this->isAjax()) {
                echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
                exit;
            }
            header('Location: ' . routeUrl('/'));
            exit;
        }
    }

    /**
     * Ayarlar sayfasını görüntüler
     */
    public function index() {
        global $db;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        // Tenant için mevcut ayarları getir
        $stmt = $db->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
        $stmt->execute([$tenant_id]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        // Eğer henüz ayar yoksa varsayılanlar ile bir dizi oluşturalım
        if (!$settings) {
            $settings = [
                'kadro_bildirim_aktif' => 1,
                'sms_api_url' => '',
                'sms_api_key' => '',
                'sms_sender' => '',
                'sms_active' => 0,
                'sms_entegrator' => 'NETGSM'
            ];
        } else if (empty($settings['sms_entegrator'])) {
            $settings['sms_entegrator'] = 'NETGSM';
        }

        return [
            'pageTitle' => 'Kurum Ayarları',
            'pageSubtitle' => 'Kurumunuz için bildirim ve SMS API ayarlarını düzenleyin',
            'settings' => $settings
        ];
    }

    /**
     * Ayarları kaydeder
     */
    public function save() {
        global $db;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            exit;
        }

        $kadro_bildirim_aktif = isset($_POST['kadro_bildirim_aktif']) ? 1 : 0;
        $sms_active = isset($_POST['sms_active']) ? 1 : 0;
        $sms_api_url = trim($_POST['sms_api_url'] ?? '');
        $sms_api_key = trim($_POST['sms_api_key'] ?? '');
        $sms_sender = trim($_POST['sms_sender'] ?? '');
        $sms_entegrator = trim($_POST['sms_entegrator'] ?? 'NETGSM');

        try {
            // Önce bu tenant için kayıt var mı kontrol et
            $stmt = $db->prepare("SELECT id FROM tenant_settings WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);
            $exists = $stmt->fetch();

            if ($exists) {
                // Güncelle
                $updateStmt = $db->prepare("
                    UPDATE tenant_settings 
                    SET kadro_bildirim_aktif = ?, 
                        sms_api_url = ?, 
                        sms_api_key = ?, 
                        sms_sender = ?, 
                        sms_active = ?, 
                        sms_entegrator = ?, 
                        updated_at = NOW() 
                    WHERE tenant_id = ?
                ");
                $updateStmt->execute([
                    $kadro_bildirim_aktif,
                    $sms_api_url,
                    $sms_api_key,
                    $sms_sender,
                    $sms_active,
                    $sms_entegrator,
                    $tenant_id
                ]);
            } else {
                // Yeni Ekle
                $insertStmt = $db->prepare("
                    INSERT INTO tenant_settings (tenant_id, kadro_bildirim_aktif, sms_api_url, sms_api_key, sms_sender, sms_active, sms_entegrator) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([
                    $tenant_id,
                    $kadro_bildirim_aktif,
                    $sms_api_url,
                    $sms_api_key,
                    $sms_sender,
                    $sms_active,
                    $sms_entegrator
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Ayarlar başarıyla kaydedildi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        exit;
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

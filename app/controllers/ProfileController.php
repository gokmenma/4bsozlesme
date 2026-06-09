<?php

class ProfileController extends Controller {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
        
        if (!isset($_SESSION['user_id'])) {
            if ($this->isAjax()) {
                echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
                exit;
            }
            header('Location: ' . routeUrl('/login'));
            exit;
        }
    }

    public function index() {
        global $db;
        $user = $this->userModel->find($_SESSION['user_id']);
        
        // Active or last subscription
        $stmt = $db->prepare("SELECT s.*, sp.name as plan_name, sp.price as plan_price, sp.features as plan_features 
                              FROM subscriptions s 
                              JOIN subscription_plans sp ON s.plan_id = sp.id
                              WHERE s.user_id = ? OR s.tenant_id = ? 
                              ORDER BY s.id DESC LIMIT 1");
        $stmt->execute([$user['id'], $user['tenant_id'] ?? null]);
        $subscription = $stmt->fetch();

        // Get all active plans that can be purchased
        $plansStmt = $db->query("SELECT * FROM subscription_plans WHERE is_active = 1");
        $plans = $plansStmt->fetchAll();

        // Get purchase history for this tenant (or all if superadmin)
        $historyQuery = "SELECT s.*, sp.name as plan_name, u.name as user_name 
                         FROM subscriptions s 
                         JOIN subscription_plans sp ON s.plan_id = sp.id 
                         LEFT JOIN users u ON s.user_id = u.id";
        
        if ($user['role'] !== 'superadmin') {
            $historyQuery .= " WHERE s.tenant_id = " . (int)$user['tenant_id'];
        }
        
        $historyQuery .= " ORDER BY s.created_at DESC";
        $history = $db->query($historyQuery)->fetchAll();

        // Get tenant settings for notification settings page
        $settingsStmt = $db->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
        $settingsStmt->execute([$user['tenant_id'] ?? 0]);
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
        if (!$settings) {
            $settings = [
                'kadro_bildirim_aktif' => 1
            ];
        }

        return [
            'pageTitle' => 'Profil',
            'pageSubtitle' => 'Hesap bilgilerinizi yönetin',
            'user' => $user,
            'subscription' => $subscription,
            'plans' => $plans,
            'history' => $history,
            'settings' => $settings
        ];
    }

    public function update() {
        $id = $_SESSION['user_id'];
        $data = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->userModel->update($id, $data);

        if ($result) {
            $_SESSION['user_name'] = $_POST['name'];
            $_SESSION['user_email'] = $_POST['email'];
            echo json_encode(['success' => true, 'message' => 'Profil başarıyla güncellendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Profil güncellenirken bir hata oluştu.']);
        }
    }

    public function changePassword() {
        $id = $_SESSION['user_id'];
        $user = $this->userModel->find($id);

        if (!password_verify($_POST['current_password'], $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Mevcut şifreniz hatalı.']);
            exit;
        }

        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            echo json_encode(['success' => false, 'message' => 'Yeni şifreler eşleşmiyor.']);
            exit;
        }

        $data = [
            'password' => password_hash($_POST['new_password'], PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->userModel->update($id, $data);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Şifreniz başarıyla değiştirildi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Şifre değiştirilirken bir hata oluştu.']);
        }
    }

    public function deleteAccount() {
        $id = $_SESSION['user_id'];
        $user = $this->userModel->find($id);

        if (!password_verify($_POST['password'], $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Şifreniz hatalı.']);
            exit;
        }

        $result = $this->userModel->delete($id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Hesabınız silindi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Hesap silinirken bir hata oluştu.']);
        }
    }

    public function updateSmsSettings() {
        $id = $_SESSION['user_id'];
        
        $sms_active = isset($_POST['sms_active']) ? 1 : 0;
        $sms_entegrator = trim($_POST['sms_entegrator'] ?? 'NETGSM');
        $sms_sender = trim($_POST['sms_sender'] ?? '');
        $sms_api_url = trim($_POST['sms_api_url'] ?? '');
        $sms_username = trim($_POST['sms_username'] ?? '');
        $sms_password = trim($_POST['sms_password'] ?? '');
        $sms_api_key = trim($_POST['sms_api_key'] ?? '');

        $data = [
            'sms_active' => $sms_active,
            'sms_entegrator' => $sms_entegrator,
            'sms_sender' => $sms_sender,
            'sms_api_url' => $sms_api_url,
            'sms_username' => $sms_username,
            'sms_password' => $sms_password,
            'sms_api_key' => $sms_api_key,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $this->userModel->update($id, $data);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'SMS API ayarları başarıyla güncellendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'SMS API ayarları güncellenirken bir hata oluştu.']);
        }
    }

    /**
     * Formda girili (henüz kaydedilmemiş) ayarlarla test SMS'i gönderir.
     */
    public function sendTestSms() {
        $test_number = trim($_POST['test_number'] ?? '');
        if ($test_number === '') {
            echo json_encode(['success' => false, 'message' => 'Test için bir telefon numarası girin.']);
            exit;
        }

        $username   = trim($_POST['sms_username'] ?? '');
        $password   = trim($_POST['sms_password'] ?? '');
        $originator = trim($_POST['sms_sender'] ?? '');
        $api_url    = trim($_POST['sms_api_url'] ?? '');
        $token      = trim($_POST['sms_api_key'] ?? '');

        if ($username === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'SMS kullanıcı adı ve şifresi alanları boş olamaz.']);
            exit;
        }

        $service = new SmsGonderService($username, $password, $originator, $api_url ?: null, $token);
        $result = $service->send(
            'Bu bir test mesajidir. SMS API ayarlariniz dogru calisiyor.',
            $test_number,
            true
        );

        $message = $result['message'];

        if (!$result['success']) {
            // Başlık tanımsızsa: onaylı başlıkları sorgulayıp kullanıcıya öner
            if (($result['raw'] ?? '') === '06') {
                $orig = $service->queryOriginators();
                if ($orig['success'] && !empty($orig['originators'])) {
                    $message .= ' Hesabınızdaki onaylı başlık(lar): ' . implode(', ', $orig['originators'])
                              . '. Lütfen "SMS Gönderici Başlığı" alanına bunlardan birini birebir (boşluklar dahil) yazın.';
                }
            }
            // Kredi yetersizse: kalan kontörü göster
            if (($result['raw'] ?? '') === '02') {
                $credit = $service->queryCredit();
                if ($credit['success']) {
                    $message .= ' Hesabınızdaki kalan kontör: ' . $credit['credit'] . '.';
                }
            }
        }

        echo json_encode([
            'success' => $result['success'],
            'message' => $message
        ]);
        exit;
    }

    public function updateNotificationSettings() {
        global $db;
        $user = $this->userModel->find($_SESSION['user_id']);
        $tenant_id = $user['tenant_id'] ?? 0;
        
        $kadro_bildirim_aktif = isset($_POST['kadro_bildirim_aktif']) ? 1 : 0;
        
        try {
            $stmt = $db->prepare("SELECT id FROM tenant_settings WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                $updateStmt = $db->prepare("
                    UPDATE tenant_settings 
                    SET kadro_bildirim_aktif = ?, 
                        updated_at = NOW() 
                    WHERE tenant_id = ?
                ");
                $updateStmt->execute([$kadro_bildirim_aktif, $tenant_id]);
            } else {
                $insertStmt = $db->prepare("
                    INSERT INTO tenant_settings (tenant_id, kadro_bildirim_aktif) 
                    VALUES (?, ?)
                ");
                $insertStmt->execute([$tenant_id, $kadro_bildirim_aktif]);
            }
            echo json_encode(['success' => true, 'message' => 'Bildirim ayarları başarıyla güncellendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        exit;
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

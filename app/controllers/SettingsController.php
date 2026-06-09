<?php

class SettingsController extends Controller {

    public function __construct() {
        // Superadmin kontrolü
        if (($_SESSION['role'] ?? '') !== 'superadmin') {
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
                'sms_entegrator' => 'NETGSM',
                'smtp_host' => '',
                'smtp_port' => '',
                'smtp_user' => '',
                'smtp_pass' => '',
                'smtp_from_email' => '',
                'smtp_from_name' => ''
            ];
        } else {
            if (empty($settings['sms_entegrator'])) {
                $settings['sms_entegrator'] = 'NETGSM';
            }
            if (!isset($settings['smtp_host'])) $settings['smtp_host'] = '';
            if (!isset($settings['smtp_port'])) $settings['smtp_port'] = '';
            if (!isset($settings['smtp_user'])) $settings['smtp_user'] = '';
            if (!isset($settings['smtp_pass'])) $settings['smtp_pass'] = '';
            if (!isset($settings['smtp_from_email'])) $settings['smtp_from_email'] = '';
            if (!isset($settings['smtp_from_name'])) $settings['smtp_from_name'] = '';
        }

        return [
            'pageTitle' => 'Kurum Ayarları',
            'pageSubtitle' => 'Kurumunuz için bildirim, e-posta ve SMS API ayarlarını düzenleyin',
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

        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = !empty($_POST['smtp_port']) ? (int)$_POST['smtp_port'] : null;
        $smtp_user = trim($_POST['smtp_user'] ?? '');
        $smtp_pass = trim($_POST['smtp_pass'] ?? '');
        $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');

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
                        smtp_host = ?,
                        smtp_port = ?,
                        smtp_user = ?,
                        smtp_pass = ?,
                        smtp_from_email = ?,
                        smtp_from_name = ?,
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
                    $smtp_host,
                    $smtp_port,
                    $smtp_user,
                    $smtp_pass,
                    $smtp_from_email,
                    $smtp_from_name,
                    $tenant_id
                ]);
            } else {
                // Yeni Ekle
                $insertStmt = $db->prepare("
                    INSERT INTO tenant_settings (
                        tenant_id, kadro_bildirim_aktif, sms_api_url, sms_api_key, 
                        sms_sender, sms_active, sms_entegrator, smtp_host, smtp_port, 
                        smtp_user, smtp_pass, smtp_from_email, smtp_from_name
                    ) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([
                    $tenant_id,
                    $kadro_bildirim_aktif,
                    $sms_api_url,
                    $sms_api_key,
                    $sms_sender,
                    $sms_active,
                    $sms_entegrator,
                    $smtp_host,
                    $smtp_port,
                    $smtp_user,
                    $smtp_pass,
                    $smtp_from_email,
                    $smtp_from_name
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Ayarlar başarıyla kaydedildi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        exit;
    }

    public function sendTestMail() {
        $to_email = trim($_POST['test_email'] ?? '');
        if (empty($to_email)) {
            echo json_encode(['success' => false, 'message' => 'Alıcı e-posta adresi gerekli.']);
            exit;
        }

        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = !empty($_POST['smtp_port']) ? (int)$_POST['smtp_port'] : 587;
        $smtp_user = trim($_POST['smtp_user'] ?? '');
        $smtp_pass = trim($_POST['smtp_pass'] ?? '');
        $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');

        if (empty($smtp_host) || empty($smtp_user) || empty($smtp_pass)) {
            echo json_encode(['success' => false, 'message' => 'SMTP Host, Kullanıcı adı ve Şifre alanları boş olamaz.']);
            exit;
        }

        // Send mail using custom SMTP settings
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_pass;
            $mail->Timeout    = 10; // Prevent hanging on connection issues
            
            if ($smtp_port === 465) {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port       = $smtp_port;
            $mail->CharSet    = 'UTF-8';

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom($smtp_from_email ?: $smtp_user, $smtp_from_name ?: 'Kadro Bildirim Sistemi');
            $mail->addAddress($to_email);

            $mail->isHTML(true);
            $mail->Subject = 'SMTP Test E-postası';
            $mail->Body    = '<h3>SMTP Bağlantısı Başarılı!</h3><p>Bu e-posta, Kadro Bildirim Sistemi ayarları üzerinden gönderilen bir test mesajıdır.</p>';

            $mail->send();
            echo json_encode(['success' => true, 'message' => 'Test e-postası başarıyla gönderildi. Lütfen gelen kutunuzu kontrol edin.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'E-posta gönderilemedi. Hata: ' . $mail->ErrorInfo]);
        }
        exit;
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

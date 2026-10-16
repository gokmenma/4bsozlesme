<?php

class TanimlamalarController extends Controller {
    
    public function index() {
        $definitionModel = new Definition();
        $activityLogModel = new ActivityLog();
        $successMessage = null;
        $tenant_id = (int)($_SESSION['tenant_id'] ?? 0);
        $user_id = (int)($_SESSION['user_id'] ?? 0);

        // Form gönderildiyse (Update/Create işlemi)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            try {
                // CSRF doğrulaması
                csrf_verify();

                // 1. Logo Silme İsteği Kontrolü
                if (isset($_POST['kurum_logo_sil']) && $_POST['kurum_logo_sil'] === '1') {
                    $currentLogo = $definitionModel->getLogo($tenant_id);
                    if (!empty($currentLogo['path']) && file_exists($currentLogo['path'])) {
                        @unlink($currentLogo['path']);
                    }
                    $definitionModel->removeLogo($tenant_id);
                    $activityLogModel->log('kurum_logo_sil', 'Kurum logosu kaldırıldı.', $user_id, $tenant_id);
                }

                // 2. Yeni Logo Yükleme Kontrolü
                if (isset($_FILES['kurum_logo']) && $_FILES['kurum_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['kurum_logo'];

                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        echo json_encode(['success' => false, 'message' => 'Logo yüklenirken bir hata oluştu.']);
                        exit;
                    }

                    // Maksimum boyut: 2MB
                    $maxSize = 2 * 1024 * 1024;
                    if ($file['size'] > $maxSize) {
                        echo json_encode(['success' => false, 'message' => 'Logo dosyası 2 MB\'tan büyük olamaz.']);
                        exit;
                    }

                    // MIME türü doğrulama
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($file['tmp_name']);
                    $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml'];

                    // SVG mime türü bazen text/plain veya text/xml olarak gelebilir, ek uzantı kontrolü
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if ($ext === 'svg' && in_array($mimeType, ['text/plain', 'text/xml', 'image/svg+xml', 'application/xml'])) {
                        $mimeType = 'image/svg+xml';
                    }

                    if (!in_array($mimeType, $allowedMimes, true)) {
                        echo json_encode(['success' => false, 'message' => 'Geçersiz dosya türü. Lütfen PNG, JPG, WEBP veya SVG formatında bir görsel yükleyin.']);
                        exit;
                    }

                    // SVG Güvenlik Kontrolü (XSS script engelleme)
                    if ($mimeType === 'image/svg+xml') {
                        $svgContent = file_get_contents($file['tmp_name']);
                        if (stripos($svgContent, '<script') !== false || stripos($svgContent, 'javascript:') !== false || stripos($svgContent, 'onload=') !== false) {
                            echo json_encode(['success' => false, 'message' => 'SVG dosyası güvenlik kurallarına uygun değil.']);
                            exit;
                        }
                    }

                    // Eski logoyu diskten temizle
                    $currentLogo = $definitionModel->getLogo($tenant_id);
                    if (!empty($currentLogo['path']) && file_exists($currentLogo['path'])) {
                        @unlink($currentLogo['path']);
                    }

                    // ISO 27001 Uyarınca AES-256-GCM ile diskte şifreli sakla
                    $storageDir = __DIR__ . '/../../storage/uploads/logos';
                    if (!is_dir($storageDir)) {
                        mkdir($storageDir, 0770, true);
                    }

                    $randomSuffix = bin2hex(random_bytes(8));
                    $destPath = $storageDir . '/logo_tenant_' . $tenant_id . '_' . $randomSuffix . '.enc';

                    $encrypted = Security::encryptFile($file['tmp_name'], $destPath, $mimeType);
                    if (!$encrypted) {
                        echo json_encode(['success' => false, 'message' => 'Logo şifrelenip kaydedilirken bir hata oluştu.']);
                        exit;
                    }

                    // Model üzerinden veritabanına kaydet
                    $definitionModel->saveLogo($tenant_id, $destPath, $mimeType);
                    $activityLogModel->log('kurum_logo_yukle', 'Yeni kurum logosu şifrelenerek yüklendi.', $user_id, $tenant_id);
                }

                // 3. Standart Form Alanlarını Kaydet
                $excludeKeys = ['kurum_logo_sil', 'kurum_logo', '_csrf', 'csrf_token'];
                foreach ($_POST as $key => $value) {
                    if (in_array($key, $excludeKeys, true)) {
                        continue;
                    }
                    $definitionModel->setSetting($key, $value, $tenant_id);
                }

                $activityLogModel->log('tanimlamalar_guncelle', 'Kurum ve yetkili tanımlamaları güncellendi.', $user_id, $tenant_id);

                // Güncel logo durumunu döner
                $updatedLogo = $definitionModel->getLogo($tenant_id);
                $hasLogo = !empty($updatedLogo['path']) && file_exists($updatedLogo['path']);

                echo json_encode([
                    'success' => true, 
                    'message' => 'Değişiklikler başarıyla kaydedildi.',
                    'has_logo' => $hasLogo,
                    'logo_url' => $hasLogo ? routeUrl('/kurum-logo?v=' . time()) : null
                ]);
                exit;
            } catch (Throwable $e) {
                error_log('TanimlamalarController POST Exception: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Kaydetme sırasında bir hata oluştu.']);
                exit;
            }
        }

        // Mevcut verileri getir (Read işlemi)
        $settings = $definitionModel->getSettings($tenant_id);
        $logoInfo = $definitionModel->getLogo($tenant_id);
        $hasLogo = !empty($logoInfo['path']) && file_exists($logoInfo['path']);

        // View'a verileri aktar
        return [
            'settings' => $settings,
            'hasLogo' => $hasLogo,
            'logoUrl' => $hasLogo ? routeUrl('/kurum-logo?v=' . time()) : null,
            'successMessage' => $successMessage
        ];
    }

    /**
     * ISO 27001 Güvenli Dosya Sunumu
     * Kurum logosunu diskteki AES-256-GCM şifreli dosyadan anlık olarak
     * hafızada deşifre edip kullanıcıya sunar.
     */
    public function getLogo() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            exit;
        }

        $tenant_id = (int)($_SESSION['tenant_id'] ?? 0);
        $userRole = $_SESSION['role'] ?? 'user';

        // Superadmin başka bir kurum logosunu görüntülüyorsa tenant_id parametresi alabilir
        if ($userRole === 'superadmin' && isset($_GET['tenant_id'])) {
            $tenant_id = (int)$_GET['tenant_id'];
        }

        if ($tenant_id <= 0) {
            http_response_code(404);
            exit;
        }

        $definitionModel = new Definition();
        $logoInfo = $definitionModel->getLogo($tenant_id);

        if (empty($logoInfo['path']) || !file_exists($logoInfo['path'])) {
            http_response_code(404);
            exit;
        }

        $decrypted = Security::decryptFile($logoInfo['path']);
        if (!$decrypted || empty($decrypted['content'])) {
            error_log('TanimlamalarController::getLogo - Dosya deşifre edilemedi: ' . $logoInfo['path']);
            http_response_code(404);
            exit;
        }

        // MIME türü ve önbellek başlıkları ile sunulur
        $mime = !empty($decrypted['mime']) ? $decrypted['mime'] : 'image/png';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($decrypted['content']));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        
        echo $decrypted['content'];
        exit;
    }
}

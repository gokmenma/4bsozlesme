<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$projectRoot = null;
foreach ([dirname(__DIR__, 3), dirname(__DIR__, 2)] as $candidate) {
    if (is_file($candidate . '/bootstrap.php')) {
        $projectRoot = $candidate;
        break;
    }
}

if ($projectRoot === null) {
    error_log('Mobile doner API: bootstrap.php bulunamadı.');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem yapılandırma hatası oluştu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $projectRoot . '/bootstrap.php';

function donerResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'POST'], true)) {
        donerResponse(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
    if ($userId <= 0 || $tenantId <= 0) {
        donerResponse(['success' => false, 'message' => 'Oturum bulunamadı.'], 401);
    }

    $rolePermModel = new RolePermission();
    if (!$rolePermModel->hasAccess($_SESSION['role_id'] ?? null, '/doner-matrahi-olustur', (string)($_SESSION['role'] ?? 'user'))) {
        donerResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
    }

    $body = [];
    if ($method === 'POST') {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['mobile_csrf_token'] ?? '';
        if ($expected === '' || !is_string($provided) || !hash_equals((string)$expected, $provided)) {
            donerResponse(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.'], 419);
        }
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        $body = is_array($decoded) ? $decoded : [];
    }

    $model = new MobileDonerModel();
    $activityLog = new ActivityLog();
    $action = (string)($_GET['action'] ?? 'defaults');

    if ($action === 'defaults' && $method === 'GET') {
        $values = (new MobileDefinitionModel())->getValues($tenantId);
        donerResponse(['success' => true, 'maas_katsayisi' => $values['maas_katsayisi'], 'yan_odeme_katsayisi' => $values['yan_odeme_katsayisi']]);
    }

    if ($action === 'build' && $method === 'POST') {
        $month = (int)($body['donem_ay'] ?? 0);
        $year = (int)($body['donem_yil'] ?? 0);
        $maas = (float)str_replace(',', '.', (string)($body['maas_katsayisi'] ?? '0'));
        $yan = (float)str_replace(',', '.', (string)($body['yan_odeme_katsayisi'] ?? '0'));
        $op = (string)($body['ayrilma_op'] ?? 'tum');
        $dateRaw = trim((string)($body['ayrilma_tarihi'] ?? ''));
        $statuses = is_array($body['personel_durum'] ?? null) ? array_values(array_unique(array_map('strval', $body['personel_durum']))) : [];
        $errors = [];

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            $errors[] = 'Geçerli bir dönem seçin.';
        }
        if ($maas <= 0 || $yan <= 0) {
            $errors[] = 'Maaş ve yan ödeme katsayıları sıfırdan büyük olmalıdır.';
        }
        if (array_diff($statuses, MobilePersonnelModel::STATUSES)) {
            $errors[] = 'Geçersiz personel durumu.';
        }
        if (!in_array($op, MobileDonerModel::OPS, true)) {
            $errors[] = 'Geçersiz ayrılış koşulu.';
        }
        $date = null;
        if (in_array($op, ['gt', 'lt', 'gte', 'lte', 'equals'], true)) {
            $ok = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateRaw, $mt) && checkdate((int)$mt[2], (int)$mt[3], (int)$mt[1]);
            if (!$ok) {
                $errors[] = 'Ayrılış tarihi geçersiz.';
            } else {
                $date = $dateRaw;
            }
        }
        if ($errors) {
            donerResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
        }

        $result = $model->build($tenantId, $month, $year, $statuses, $op, $date, $maas, $yan);
        $activityLog->log('mobile_doner_build', sprintf('Mobil SPA döner matrahı oluşturuldu (%02d/%d, %d kayıt).', $month, $year, count($result['rows'])), $userId, $tenantId);
        donerResponse(['success' => true, 'data' => $result['rows'], 'unmatched' => $result['unmatched']]);
    }

    donerResponse(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    error_log('Mobile Doner API Error: ' . $e->getMessage());
    donerResponse(['success' => false, 'message' => 'Döner matrahı oluşturulamadı.'], 500);
}

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
    error_log('Mobile Definitions API: bootstrap.php bulunamadı.');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem yapılandırma hatası oluştu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $projectRoot . '/bootstrap.php';

function definitionResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'POST'], true)) {
        definitionResponse(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
    if ($userId <= 0 || $tenantId <= 0) {
        definitionResponse(['success' => false, 'message' => 'Oturum bulunamadı.'], 401);
    }

    $rolePermModel = new RolePermission();
    if (!$rolePermModel->hasAccess($_SESSION['role_id'] ?? null, '/tanimlamalar', (string)($_SESSION['role'] ?? 'user'))) {
        definitionResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
    }

    $body = [];
    if ($method === 'POST') {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['mobile_csrf_token'] ?? '';
        if ($expected === '' || !is_string($provided) || !hash_equals((string)$expected, $provided)) {
            definitionResponse(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.'], 419);
        }
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        $body = is_array($decoded) ? $decoded : [];
    }

    $model = new MobileDefinitionModel();
    $activityLog = new ActivityLog();
    $action = (string)($_GET['action'] ?? 'get');

    if ($action === 'get' && $method === 'GET') {
        $activityLog->log('mobile_definitions_view', 'Mobil SPA tanımlamalar görüntülendi.', $userId, $tenantId);
        $values = $model->getValues($tenantId);
        $periods = $model->getPeriods($tenantId);
        if ($values['default_wage_period'] !== '' && !in_array($values['default_wage_period'], $periods, true)) {
            $periods[] = $values['default_wage_period'];
        }
        sort($periods);
        definitionResponse(['success' => true, 'values' => $values, 'periods' => $periods]);
    }

    if ($action === 'save' && $method === 'POST') {
        $errors = [];
        $clean = [];
        $text = static fn($v, int $max): string => mb_substr(trim((string)$v), 0, $max);

        $clean['kurum_adi'] = $text($body['kurum_adi'] ?? '', 255);
        $clean['birim_adi'] = $text($body['birim_adi'] ?? '', 255);
        $clean['yetkili_ad_soyad'] = $text($body['yetkili_ad_soyad'] ?? '', 255);
        $clean['yetkili_unvan'] = $text($body['yetkili_unvan'] ?? '', 255);

        $period = $text($body['default_wage_period'] ?? '', 20);
        if (!preg_match('/^\d{4}-\d{1,2}$/', $period)) {
            $errors[] = 'Geçerli bir ücret dönemi seçin.';
        }
        $clean['default_wage_period'] = $period;

        foreach (['maas_katsayisi' => 'Maaş katsayısı', 'yan_odeme_katsayisi' => 'Yan ödeme katsayısı'] as $key => $label) {
            $raw = str_replace(',', '.', $text($body[$key] ?? '', 20));
            if ($raw !== '' && (!is_numeric($raw) || (float)$raw < 0 || (float)$raw > 1000000)) {
                $errors[] = $label . ' geçersiz.';
            }
            $clean[$key] = $raw;
        }

        if ($errors) {
            definitionResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
        }

        $model->saveValues($tenantId, $clean);
        $activityLog->log('mobile_definitions_update', 'Mobil SPA ile kurum tanımlamaları güncellendi.', $userId, $tenantId);
        definitionResponse(['success' => true, 'values' => $model->getValues($tenantId)]);
    }

    definitionResponse(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    error_log('Mobile Definitions API Error: ' . $e->getMessage());
    definitionResponse(['success' => false, 'message' => 'Tanımlamalar alınamadı.'], 500);
}

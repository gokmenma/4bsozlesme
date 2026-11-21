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
    error_log('Mobile Wages API: bootstrap.php bulunamadı.');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem yapılandırma hatası oluştu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $projectRoot . '/bootstrap.php';

function wageResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function wageDecryptId($token): int
{
    if (!is_string($token) || trim($token) === '') {
        return 0;
    }
    $plain = Security::decrypt(trim($token));
    if ($plain === null || !ctype_digit($plain)) {
        return 0;
    }
    $id = (int)$plain;
    return $id > 0 ? $id : 0;
}

function wageSerialize(array $row): array
{
    return [
        'id' => Security::encrypt((string)$row['id']),
        'donem' => (string)($row['donem'] ?? '2026-1'),
        'unvan' => (string)($row['unvan'] ?? ''),
        'ogrenim' => (string)($row['ogrenim'] ?? ''),
        'kidem_yili' => (string)($row['kidem_yili'] ?? ''),
        'ucret' => (float)($row['ucret'] ?? 0),
        'is_active' => (int)($row['is_active'] ?? 1),
        'personel_count' => (int)($row['personel_count'] ?? 0),
        'kullanim_count' => (int)($row['kullanim_count'] ?? $row['personel_count'] ?? 0),
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

function wageParseNumber($raw, bool $allowZero = false): ?float
{
    if (is_int($raw) || is_float($raw)) {
        $val = round((float)$raw, 2);
        if (($allowZero ? $val >= 0 : $val > 0) && $val <= 999999999.99) {
            return $val;
        }
        return null;
    }

    if (!is_string($raw)) {
        return null;
    }

    $clean = str_ireplace(['₺', 'TL'], '', trim($raw));
    $clean = preg_replace('/\s+/u', '', $clean);
    if ($clean === '' || preg_match('/[^0-9.,]/', $clean)) {
        return null;
    }

    $hasDot = strpos($clean, '.') !== false;
    $hasComma = strpos($clean, ',') !== false;

    if ($hasDot && $hasComma) {
        $lastDot = strrpos($clean, '.');
        $lastComma = strrpos($clean, ',');
        if ($lastComma > $lastDot) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }
    } elseif ($hasComma) {
        if (substr_count($clean, ',') > 1) {
            $clean = str_replace(',', '', $clean);
        } else {
            $clean = str_replace(',', '.', $clean);
        }
    } elseif ($hasDot) {
        if (substr_count($clean, '.') > 1 || preg_match('/^\d{1,3}\.\d{3}$/', $clean)) {
            $clean = str_replace('.', '', $clean);
        }
    }

    if (!is_numeric($clean)) {
        return null;
    }

    $val = round((float)$clean, 2);
    if (($allowZero ? $val >= 0 : $val > 0) && $val <= 999999999.99) {
        return $val;
    }

    return null;
}

function wageParseAmount($raw): ?float
{
    return wageParseNumber($raw, false);
}

function wageParsePercent($raw): ?float
{
    if (is_int($raw) || is_float($raw)) {
        $val = round((float)$raw, 2);
        return ($val >= -90.0 && $val <= 500.0) ? $val : null;
    }

    if (!is_string($raw)) {
        return null;
    }

    $clean = str_replace(['%', ' '], '', trim($raw));
    $clean = str_replace(',', '.', $clean);
    if ($clean === '' || !preg_match('/^-?\d+(\.\d+)?$/', $clean)) {
        return null;
    }

    $val = round((float)$clean, 2);
    return ($val >= -90.0 && $val <= 500.0) ? $val : null;
}

function wageValidateInput(array $input): array
{
    $errors = [];
    $unvan = trim((string)preg_replace('/\s+/u', ' ', (string)($input['unvan'] ?? '')));
    $ogrenim = trim((string)preg_replace('/\s+/u', ' ', (string)($input['ogrenim'] ?? '')));
    $kidem = trim((string)preg_replace('/\s+/u', ' ', (string)($input['kidem_yili'] ?? '')));
    $donem = trim((string)preg_replace('/\s+/u', ' ', (string)($input['donem'] ?? '')));
    $ucret = wageParseAmount($input['ucret'] ?? null);

    if ($unvan === '' || mb_strlen($unvan) > 255) {
        $errors[] = 'Unvan zorunludur.';
    }
    if ($ogrenim === '' || mb_strlen($ogrenim) > 255) {
        $errors[] = 'Öğrenim durumu zorunludur.';
    }
    if ($kidem === '' || mb_strlen($kidem) > 255) {
        $errors[] = 'Kıdem yılı zorunludur.';
    }
    if ($donem === '' || mb_strlen($donem) > 50) {
        $errors[] = 'Dönem bilgisi zorunludur.';
    }
    if ($ucret === null) {
        $errors[] = 'Geçerli bir aylık brüt ücret girin.';
    }

    return [$errors, [
        'unvan' => $unvan,
        'ogrenim' => $ogrenim,
        'kidem_yili' => $kidem,
        'donem' => $donem,
        'ucret' => $ucret ?? 0.0,
    ]];
}

try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'POST'], true)) {
        wageResponse(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
    if ($userId <= 0 || $tenantId <= 0) {
        wageResponse(['success' => false, 'message' => 'Oturum bulunamadı.'], 401);
    }

    $roleName = (string)($_SESSION['role'] ?? 'user');
    $rolePermModel = new RolePermission();
    if (!$rolePermModel->hasAccess($_SESSION['role_id'] ?? null, '/ucret-tanimlari', $roleName)) {
        wageResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
    }

    $body = [];
    if ($method === 'POST') {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['mobile_csrf_token'] ?? '';
        if ($expected === '' || !is_string($provided) || !hash_equals((string)$expected, $provided)) {
            wageResponse(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.'], 419);
        }
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        $body = is_array($decoded) ? $decoded : [];
    }

    $model = new MobileWageModel();
    $activityLog = new ActivityLog();
    $action = (string)($_GET['action'] ?? 'list');

    if ($action === 'list' && $method === 'GET') {
        $periods = $model->getPeriods($tenantId);
        $requestedPeriod = mb_substr(trim((string)($_GET['donem'] ?? '')), 0, 50);
        $period = $model->resolvePeriod($tenantId, $requestedPeriod, $periods);
        $defaultPeriod = $model->getDefaultPeriod($tenantId);

        $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);
        $ogrenim = mb_substr(trim((string)($_GET['ogrenim'] ?? '')), 0, 255);
        $kidem = mb_substr(trim((string)($_GET['kidem'] ?? '')), 0, 255);
        $minUcret = isset($_GET['min_ucret']) && trim((string)$_GET['min_ucret']) !== ''
            ? wageParseNumber((string)$_GET['min_ucret'], true)
            : null;
        $maxUcret = isset($_GET['max_ucret']) && trim((string)$_GET['max_ucret']) !== ''
            ? wageParseNumber((string)$_GET['max_ucret'], true)
            : null;
        $sortRaw = (string)($_GET['sort'] ?? 'unvan_asc');
        $sort = in_array($sortRaw, MobileWageModel::SORTS, true) ? $sortRaw : 'unvan_asc';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $list = $model->getList($tenantId, $period, $search, $ogrenim, $kidem, $minUcret, $maxUcret, $sort, $page, 30);
        $summary = $model->getPeriodSummary($tenantId, $period);
        $formOptions = $model->getFormOptions($tenantId);
        $items = array_map('wageSerialize', $list['items']);

        $activityLog->log('mobile_wage_list', 'Mobil SPA ücret tanımları görüntülendi (Dönem: ' . $period . ').', $userId, $tenantId);

        wageResponse([
            'success' => true,
            'period' => $period,
            'default_period' => $defaultPeriod,
            'periods' => $periods,
            'summary' => $summary,
            'form_options' => $formOptions,
            'items' => $items,
            'total' => $list['total'],
            'page' => $list['page'],
            'has_more' => $list['has_more'],
        ]);
    }

    if ($action === 'detail' && $method === 'GET') {
        $id = wageDecryptId($_GET['id'] ?? '');
        $row = $id > 0 ? $model->getDetail($tenantId, $id) : null;
        if ($row === null) {
            error_log('Mobile Wages detail 404: ' . ($id > 0 ? 'kayıt bulunamadı (id=' . $id . ', tenant=' . $tenantId . ')' : 'id çözülemedi (token uzunluğu=' . strlen((string)($_GET['id'] ?? '')) . ')'));
            wageResponse(['success' => false, 'message' => 'Ücret tanımı bulunamadı.'], 404);
        }

        $activityLog->log('mobile_wage_detail', 'Mobil SPA ücret tanımı detayı görüntülendi (ID: ' . $id . ').', $userId, $tenantId);
        wageResponse(['success' => true, 'wage' => wageSerialize($row)]);
    }

    if ($method === 'POST' && in_array($action, ['create', 'update'], true)) {
        $id = wageDecryptId($body['id'] ?? '');
        if ($action === 'update' && ($id <= 0 || $model->getDetail($tenantId, $id) === null)) {
            wageResponse(['success' => false, 'message' => 'Ücret tanımı bulunamadı.'], 404);
        }

        [$errors, $data] = wageValidateInput($body);
        if ($errors) {
            wageResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
        }

        if ($action === 'create') {
            $newId = $model->createWage($tenantId, $data);
            $activityLog->log('mobile_wage_create', 'Mobil SPA ile ücret tanımı eklendi (ID: ' . $newId . ', Dönem: ' . $data['donem'] . ').', $userId, $tenantId);
            wageResponse([
                'success' => true,
                'id' => Security::encrypt((string)$newId),
                'period' => $data['donem'],
            ], 201);
        }

        $model->updateWage($tenantId, $id, $data);
        $activityLog->log('mobile_wage_update', 'Mobil SPA ile ücret tanımı güncellendi (ID: ' . $id . ', Dönem: ' . $data['donem'] . ').', $userId, $tenantId);
        wageResponse([
            'success' => true,
            'id' => Security::encrypt((string)$id),
            'period' => $data['donem'],
        ]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = wageDecryptId($body['id'] ?? '');
        if ($id <= 0 || $model->getDetail($tenantId, $id) === null) {
            wageResponse(['success' => false, 'message' => 'Ücret tanımı bulunamadı.'], 404);
        }

        $usedCount = $model->countPersonnelUsingWage($tenantId, $id);
        if ($usedCount > 0) {
            wageResponse([
                'success' => false,
                'message' => "Bu ücret tanımı şu anda {$usedCount} personel tarafından kullanılmaktadır. Silmeden önce ilgili personellerin unvanlarını değiştirmeniz gerekmektedir.",
            ], 409);
        }

        if (!$model->softDelete($tenantId, $id)) {
            wageResponse(['success' => false, 'message' => 'Ücret tanımı silinemedi.'], 404);
        }

        $activityLog->log('mobile_wage_delete', 'Mobil SPA ile ücret tanımı silindi (ID: ' . $id . ').', $userId, $tenantId);
        wageResponse(['success' => true]);
    }

    if ($method === 'POST' && in_array($action, ['copy_period', 'delete_period', 'set_default_period'], true)) {
        if (!in_array($roleName, ['admin', 'superadmin'], true)) {
            wageResponse(['success' => false, 'message' => 'Dönem yönetimi için yönetici yetkisi gereklidir.'], 403);
        }

        if ($action === 'copy_period') {
            $fromDonem = mb_substr(trim((string)($body['from_donem'] ?? '')), 0, 50);
            $toDonem = mb_substr(trim((string)($body['to_donem'] ?? '')), 0, 50);
            $raisePercent = wageParsePercent($body['raise_percent'] ?? null);

            if ($fromDonem === '' || $toDonem === '') {
                wageResponse(['success' => false, 'message' => 'Kaynak ve hedef dönem bilgileri zorunludur.'], 422);
            }
            if ($fromDonem === $toDonem) {
                wageResponse(['success' => false, 'message' => 'Hedef dönem adı kaynak dönemden farklı olmalıdır.'], 422);
            }
            if ($raisePercent === null) {
                wageResponse(['success' => false, 'message' => 'Geçerli bir zam oranı (%) girin.'], 422);
            }

            $res = $model->copyPeriod($tenantId, $fromDonem, $toDonem, $raisePercent);
            if (!$res['ok']) {
                wageResponse(['success' => false, 'message' => $res['error']], $res['status']);
            }

            $activityLog->log(
                'mobile_wage_period_copy',
                "Mobil SPA ile '{$fromDonem}' dönemi %{$raisePercent} zamla '{$toDonem}' dönemine kopyalandı ({$res['count']} kayıt).",
                $userId,
                $tenantId
            );

            wageResponse([
                'success' => true,
                'count' => $res['count'],
                'period' => $toDonem,
                'message' => "{$res['count']} ücret tanımı %{$raisePercent} oranıyla '{$toDonem}' dönemine aktarıldı.",
            ], 201);
        }

        if ($action === 'delete_period') {
            $donem = mb_substr(trim((string)($body['donem'] ?? '')), 0, 50);
            if ($donem === '') {
                wageResponse(['success' => false, 'message' => 'Silinecek dönem bilgisi gereklidir.'], 422);
            }

            $res = $model->deletePeriod($tenantId, $donem);
            if (!$res['ok']) {
                wageResponse(['success' => false, 'message' => $res['error']], $res['status']);
            }

            if (isset($_SESSION['active_wage_period']) && $_SESSION['active_wage_period'] === $donem) {
                unset($_SESSION['active_wage_period']);
            }

            $activityLog->log(
                'mobile_wage_period_delete',
                "Mobil SPA ile '{$donem}' ücret dönemi silindi ({$res['deleted']} kayıt).",
                $userId,
                $tenantId
            );

            wageResponse([
                'success' => true,
                'deleted' => $res['deleted'],
                'next_period' => $res['next_period'],
                'message' => "'{$donem}' dönemine ait ücret tanımları silindi.",
            ]);
        }

        $donem = mb_substr(trim((string)($body['donem'] ?? '')), 0, 50);
        if ($donem === '') {
            wageResponse(['success' => false, 'message' => 'Dönem bilgisi gereklidir.'], 422);
        }

        if (!$model->setDefaultPeriod($tenantId, $donem)) {
            wageResponse(['success' => false, 'message' => 'Seçilen dönem bulunamadı.'], 404);
        }

        $activityLog->log(
            'mobile_wage_period_default',
            "Mobil SPA ile varsayılan ücret dönemi '{$donem}' olarak ayarlandı.",
            $userId,
            $tenantId
        );

        wageResponse([
            'success' => true,
            'default_period' => $donem,
            'message' => "'{$donem}' varsayılan ücret dönemi olarak ayarlandı.",
        ]);
    }

    wageResponse(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    error_log('Mobile Wages API Error: ' . $e->getMessage());
    wageResponse(['success' => false, 'message' => 'Ücret verileri alınamadı.'], 500);
}

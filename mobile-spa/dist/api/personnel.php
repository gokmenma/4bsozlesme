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
    error_log('Mobile Personnel API: bootstrap.php bulunamadı.');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem yapılandırma hatası oluştu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $projectRoot . '/bootstrap.php';

function personnelResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'POST'], true)) {
        personnelResponse(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
    if ($userId <= 0 || $tenantId <= 0) {
        personnelResponse(['success' => false, 'message' => 'Oturum bulunamadı.'], 401);
    }

    $rolePermModel = new RolePermission();
    if (!$rolePermModel->hasAccess($_SESSION['role_id'] ?? null, '/personel-listesi', (string)($_SESSION['role'] ?? 'user'))) {
        personnelResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
    }

    $body = [];
    if ($method === 'POST') {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['mobile_csrf_token'] ?? '';
        if ($expected === '' || !is_string($provided) || !hash_equals((string)$expected, $provided)) {
            personnelResponse(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.'], 419);
        }
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        $body = is_array($decoded) ? $decoded : [];
    }

    new UcretsizIzin();
    $model = new MobilePersonnelModel();
    $activityLog = new ActivityLog();
    $action = (string)($_GET['action'] ?? 'list');

    $parseDate = static function ($value): ?string {
        $value = trim((string)$value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y) ? $value : null;
    };

    $validatePerson = static function (array $input) use ($parseDate, $model, $tenantId): array {
        $errors = [];
        $name = trim(preg_replace('/\s+/u', ' ', (string)($input['ad_soyad'] ?? '')));
        $tc = trim((string)($input['tc_kimlik'] ?? ''));
        $wageId = (int)($input['ucret_id'] ?? 0);
        $status = (string)($input['durum'] ?? 'aktif');
        $gender = (string)($input['cinsiyet'] ?? 'erkek');
        $start = $parseDate($input['goreve_baslama_tarihi'] ?? '');
        $endRaw = trim((string)($input['ayrilma_tarihi'] ?? ''));
        $end = $endRaw === '' ? null : $parseDate($endRaw);

        if ($name === '' || mb_strlen($name) > 255) {
            $errors[] = 'Ad soyad zorunludur.';
        }
        if (!preg_match('/^\d{11}$/', $tc)) {
            $errors[] = 'TC Kimlik No 11 haneli olmalıdır.';
        }
        if ($wageId <= 0 || !$model->wageExists($tenantId, $wageId)) {
            $errors[] = 'Geçerli bir unvan / ücret seçin.';
        }
        if (!in_array($status, MobilePersonnelModel::STATUSES, true)) {
            $errors[] = 'Geçersiz durum.';
        }
        if (!in_array($gender, ['erkek', 'kadin', 'diger'], true)) {
            $errors[] = 'Geçersiz cinsiyet.';
        }
        if ($start === null) {
            $errors[] = 'Göreve başlama tarihi geçersiz.';
        }
        if ($endRaw !== '' && $end === null) {
            $errors[] = 'Ayrılma tarihi geçersiz.';
        }

        return [$errors, [
            'ad_soyad' => $name,
            'tc_kimlik' => $tc,
            'ucret_id' => $wageId,
            'durum' => $status,
            'cinsiyet' => $gender,
            'goreve_baslama_tarihi' => $start,
            'ayrilma_tarihi' => $end,
            'telefon' => mb_substr(trim((string)($input['telefon'] ?? '')), 0, 30),
            'meslek_kodu' => mb_substr(trim((string)($input['meslek_kodu'] ?? '')), 0, 50),
        ]];
    };

    if ($action === 'list' && $method === 'GET') {
        $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);
        $status = (string)($_GET['status'] ?? '');
        $page = (int)($_GET['page'] ?? 1);
        $result = $model->getList($tenantId, $search, $status, $page, 20);
        $result['counts'] = $model->getStatusCounts($tenantId);
        $result['success'] = true;
        $activityLog->log('mobile_personnel_list', 'Mobil SPA personel listesi görüntülendi.', $userId, $tenantId);
        personnelResponse($result);
    }

    if ($action === 'detail' && $method === 'GET') {
        $id = (int)($_GET['id'] ?? 0);
        $person = $id > 0 ? $model->getDetail($tenantId, $id) : null;
        if ($person === null) {
            personnelResponse(['success' => false, 'message' => 'Personel bulunamadı.'], 404);
        }
        $activityLog->log('mobile_personnel_detail', 'Mobil SPA personel detayı görüntülendi (ID: ' . $id . ').', $userId, $tenantId);
        personnelResponse(['success' => true, 'person' => $person, 'leaves' => $model->listLeaves($tenantId, $id)]);
    }

    if ($action === 'options' && $method === 'GET') {
        personnelResponse(['success' => true, 'wages' => $model->getWageOptions($tenantId)]);
    }

    if ($method === 'POST' && in_array($action, ['create', 'update'], true)) {
        $id = (int)($body['id'] ?? 0);
        if ($action === 'update' && ($id <= 0 || $model->getDetail($tenantId, $id) === null)) {
            personnelResponse(['success' => false, 'message' => 'Personel bulunamadı.'], 404);
        }
        [$errors, $data] = $validatePerson($body);
        if (!$errors && $model->tcExists($tenantId, $data['tc_kimlik'], $action === 'update' ? $id : 0)) {
            $errors[] = 'Bu TC Kimlik numarasına sahip bir personel zaten kayıtlı.';
        }
        if ($errors) {
            personnelResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
        }

        if ($action === 'create') {
            $newId = $model->createPerson($tenantId, $data);
            $activityLog->log('mobile_personnel_create', 'Mobil SPA ile personel eklendi (ID: ' . $newId . ').', $userId, $tenantId);
            personnelResponse(['success' => true, 'id' => $newId], 201);
        }

        $model->updatePerson($tenantId, $id, $data);
        $activityLog->log('mobile_personnel_update', 'Mobil SPA ile personel güncellendi (ID: ' . $id . ').', $userId, $tenantId);
        personnelResponse(['success' => true, 'id' => $id]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0 || !$model->softDelete($tenantId, $id)) {
            personnelResponse(['success' => false, 'message' => 'Personel bulunamadı.'], 404);
        }
        $activityLog->log('mobile_personnel_delete', 'Mobil SPA ile personel silindi (ID: ' . $id . ').', $userId, $tenantId);
        personnelResponse(['success' => true]);
    }

    if ($method === 'POST' && in_array($action, ['leave_add', 'leave_delete'], true)) {
        $personId = (int)($body['personel_id'] ?? 0);
        if ($personId <= 0 || $model->getDetail($tenantId, $personId) === null) {
            personnelResponse(['success' => false, 'message' => 'Personel bulunamadı.'], 404);
        }

        if ($action === 'leave_add') {
            $start = $parseDate($body['baslangic_tarihi'] ?? '');
            $end = $parseDate($body['bitis_tarihi'] ?? '');
            if ($start === null || $end === null) {
                personnelResponse(['success' => false, 'message' => 'Başlangıç ve bitiş tarihi zorunludur.'], 422);
            }
            if ($end < $start) {
                personnelResponse(['success' => false, 'message' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.'], 422);
            }
            $leaveId = $model->addLeave($tenantId, $personId, $start, $end, mb_substr(trim((string)($body['aciklama'] ?? '')), 0, 255));
            $activityLog->log('mobile_personnel_leave_add', 'Mobil SPA ile ücretsiz izin eklendi (Personel ID: ' . $personId . ').', $userId, $tenantId);
            personnelResponse(['success' => true, 'id' => $leaveId, 'leaves' => $model->listLeaves($tenantId, $personId)], 201);
        }

        $leaveId = (int)($body['id'] ?? 0);
        if ($leaveId <= 0 || !$model->deleteLeave($tenantId, $personId, $leaveId)) {
            personnelResponse(['success' => false, 'message' => 'İzin kaydı bulunamadı.'], 404);
        }
        $activityLog->log('mobile_personnel_leave_delete', 'Mobil SPA ile ücretsiz izin silindi (Personel ID: ' . $personId . ').', $userId, $tenantId);
        personnelResponse(['success' => true, 'leaves' => $model->listLeaves($tenantId, $personId)]);
    }

    personnelResponse(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    error_log('Mobile Personnel API Error: ' . $e->getMessage());
    personnelResponse(['success' => false, 'message' => 'Personel verileri alınamadı.'], 500);
}

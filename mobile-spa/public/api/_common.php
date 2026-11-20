<?php
declare(strict_types=1);

$GLOBALS['__mobileProjectRoot'] = null;
foreach ([dirname(__DIR__, 3), dirname(__DIR__, 2)] as $candidate) {
    if (is_file($candidate . '/bootstrap.php')) {
        $GLOBALS['__mobileProjectRoot'] = $candidate;
        break;
    }
}
if ($GLOBALS['__mobileProjectRoot'] !== null) {
    require_once $GLOBALS['__mobileProjectRoot'] . '/bootstrap.php';
}

function mobileApiBoot(string $label, ?string $permission, bool $superadminOnly = false, bool $requireTenant = true): array
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    if ($GLOBALS['__mobileProjectRoot'] === null) {
        error_log('Mobile ' . $label . ' API: bootstrap.php bulunamadı.');
        mobileJson(['success' => false, 'message' => 'Sistem yapılandırma hatası oluştu.'], 500);
    }

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'POST'], true)) {
        mobileJson(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
    $role = (string)($_SESSION['role'] ?? 'user');
    if ($userId <= 0 || ($requireTenant && $tenantId <= 0)) {
        mobileJson(['success' => false, 'message' => 'Oturum bulunamadı.'], 401);
    }

    $isSuper = $role === 'superadmin';
    if ($superadminOnly) {
        if (!$isSuper) {
            mobileJson(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
        }
    } elseif ($permission !== null && $role !== 'admin' && !$isSuper) {
        $allowed = (new RolePermission())->hasAccess($_SESSION['role_id'] ?? null, $permission, $role);
        if (!$allowed) {
            mobileJson(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
        }
    }

    $body = [];
    if ($method === 'POST') {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['mobile_csrf_token'] ?? '';
        if ($expected === '' || !is_string($provided) || !hash_equals((string)$expected, $provided)) {
            mobileJson(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.'], 419);
        }
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        $body = is_array($decoded) ? $decoded : [];
    }

    return [
        'method' => $method,
        'userId' => $userId,
        'tenantId' => $tenantId,
        'role' => $role,
        'roleId' => (int)($_SESSION['role_id'] ?? 0),
        'isSuper' => $isSuper,
        'body' => $body,
        'action' => (string)($_GET['action'] ?? 'list'),
        'log' => new ActivityLog(),
    ];
}

function mobileJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function mobileFail(string $label, Throwable $e): void
{
    error_log('Mobile ' . $label . ' API Error: ' . $e->getMessage());
    mobileJson(['success' => false, 'message' => 'İşlem tamamlanamadı.'], 500);
}

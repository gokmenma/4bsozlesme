<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Proje kökündeki bootstrap.php dosyasını güvenli şekilde bul ve yükle
$projectRoot = null;
foreach ([dirname(__DIR__, 3), dirname(__DIR__, 2)] as $candidate) {
    if (is_file($candidate . '/bootstrap.php')) {
        $projectRoot = $candidate;
        break;
    }
}

if ($projectRoot === null) {
    error_log('Mobile Auth API: bootstrap.php bulunamadı.');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem yapılandırma hatası oluştu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $projectRoot . '/bootstrap.php';

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function getCsrfToken(): string
{
    if (empty($_SESSION['mobile_csrf_token']) || !is_string($_SESSION['mobile_csrf_token'])) {
        $_SESSION['mobile_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['mobile_csrf_token'];
}

function verifyCsrfToken(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['mobile_csrf_token'] ?? '';
    if ($expected === '' || !is_string($provided) || !hash_equals($expected, $provided)) {
        jsonResponse([
            'success' => false,
            'message' => 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.',
            'csrf_token' => getCsrfToken(),
        ], 419);
    }
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function resolveUserTenant(User $userModel, Tenant $tenantModel, array $user): ?array
{
    $activeTenantId = !empty($user['tenant_id']) ? (int)$user['tenant_id'] : 0;
    if ($activeTenantId > 0) {
        $found = $tenantModel->find($activeTenantId);
        if ($found) {
            return $found;
        }
    }

    $userTenants = $userModel->getTenants((int)$user['id']);
    if (!empty($userTenants)) {
        $tenant = $userTenants[0];
        $userModel->update((int)$user['id'], ['tenant_id' => $tenant['id']]);
        return $tenant;
    }

    $allTenants = $tenantModel->all();
    if (!empty($allTenants)) {
        $tenant = $allTenants[0];
        $userModel->update((int)$user['id'], ['tenant_id' => $tenant['id']]);
        return $tenant;
    }

    return null;
}

function buildSessionPayload(User $userModel, Tenant $tenantModel): array
{
    $csrf = getCsrfToken();

    if (empty($_SESSION['user_id'])) {
        return [
            'success' => true,
            'authenticated' => false,
            'csrf_token' => $csrf,
            'user' => null,
            'tenant' => null,
            'tenants' => [],
        ];
    }

    $user = $userModel->find((int)$_SESSION['user_id']);
    if (!$user) {
        unset($_SESSION['user_id']);
        return [
            'success' => true,
            'authenticated' => false,
            'csrf_token' => $csrf,
            'user' => null,
            'tenant' => null,
            'tenants' => [],
        ];
    }

    $activeTenant = null;
    if (!empty($_SESSION['tenant_id'])) {
        $activeTenant = $tenantModel->find((int)$_SESSION['tenant_id']) ?: null;
    }
    if ($activeTenant === null) {
        $activeTenant = resolveUserTenant($userModel, $tenantModel, $user);
        $_SESSION['tenant_id'] = $activeTenant['id'] ?? null;
    }

    $rawTenants = $userModel->getTenants((int)$user['id']);
    $tenants = array_map(static function (array $t): array {
        return [
            'id' => Security::encrypt((string)$t['id']),
            'name' => (string)($t['name'] ?? $t['title'] ?? 'Kurum'),
        ];
    }, $rawTenants);

    $rolePermModel = new RolePermission();
    $roleId = $user['role_id'] ?? ($_SESSION['role_id'] ?? null);
    $roleName = (string)($user['role'] ?? 'user');
    $permissions = [];
    foreach (array_keys(RolePermission::getAllDefinedPages()) as $route) {
        $permissions[$route] = (bool)$rolePermModel->hasAccess($roleId, $route, $roleName);
    }

    return [
        'success' => true,
        'authenticated' => true,
        'csrf_token' => $csrf,
        'permissions' => $permissions,
        'user' => [
            'id' => Security::encrypt((string)$user['id']),
            'name' => (string)($user['name'] ?? ''),
            'username' => !empty($user['username']) ? (string)$user['username'] : explode('@', (string)($user['email'] ?? ''))[0],
            'email' => (string)($user['email'] ?? ''),
            'role' => (string)($user['role'] ?? 'user'),
        ],
        'tenant' => $activeTenant ? [
            'id' => Security::encrypt((string)$activeTenant['id']),
            'name' => (string)($activeTenant['name'] ?? $activeTenant['title'] ?? 'Kurum'),
        ] : null,
        'tenants' => $tenants,
    ];
}

try {
    $action = (string)($_GET['action'] ?? 'session');
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    $userModel = new User();
    $tenantModel = new Tenant();
    $activityLog = new ActivityLog();

    if ($action === 'session' && $method === 'GET') {
        jsonResponse(buildSessionPayload($userModel, $tenantModel));
    }

    if ($action === 'login' && $method === 'POST') {
        verifyCsrfToken();
        $body = readJsonBody();

        $usernameInput = trim((string)($body['username'] ?? ''));
        $password = (string)($body['password'] ?? '');
        $remember = !empty($body['remember']);

        if ($usernameInput === '' || $password === '') {
            jsonResponse([
                'success' => false,
                'message' => 'Lütfen kullanıcı adı / e-posta ve şifre girin.',
            ], 422);
        }

        $user = $userModel->findByUsername($usernameInput);
        if (!$user || !password_verify($password, (string)$user['password'])) {
            $activityLog->log(
                'mobile_login_failed',
                'Mobil SPA başarısız giriş denemesi: ' . mb_substr($usernameInput, 0, 80),
                $user ? (int)$user['id'] : null,
                $user ? ($user['tenant_id'] ?? null) : null
            );
            jsonResponse([
                'success' => false,
                'message' => 'Hatalı kullanıcı adı / e-posta veya şifre.',
            ], 401);
        }

        // Session fixation önlemi
        session_regenerate_id(true);
        $_SESSION['mobile_csrf_token'] = bin2hex(random_bytes(32));

        $activeTenant = resolveUserTenant($userModel, $tenantModel, $user);
        $activeTenantId = $activeTenant['id'] ?? null;

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = (string)$user['name'];
        $_SESSION['user_username'] = !empty($user['username']) ? (string)$user['username'] : explode('@', (string)$user['email'])[0];
        $_SESSION['user_email'] = (string)$user['email'];
        $_SESSION['role'] = (string)$user['role'];
        $_SESSION['role_id'] = $user['role_id'] ?? null;
        $_SESSION['tenant_id'] = $activeTenantId;

        $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        if ($remember) {
            $rememberToken = bin2hex(random_bytes(32));
            $userModel->updateRememberToken((int)$user['id'], $rememberToken);
            setcookie('remember_token', $user['id'] . ':' . $rememberToken, [
                'expires' => time() + (30 * 86400),
                'path' => '/',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            setcookie('remember_user', $usernameInput, [
                'expires' => time() + (30 * 86400),
                'path' => '/',
                'secure' => $isSecure,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        } else {
            $userModel->updateRememberToken((int)$user['id'], null);
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
            ]);
            setcookie('remember_user', '', [
                'expires' => time() - 3600,
                'path' => '/',
            ]);
        }

        $activityLog->log(
            'mobile_login',
            'Mobil SPA girişi yapıldı (' . $_SESSION['user_username'] . ')',
            (int)$user['id'],
            $activeTenantId
        );

        jsonResponse(buildSessionPayload($userModel, $tenantModel));
    }

    if ($action === 'logout' && $method === 'POST') {
        verifyCsrfToken();

        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $tenantId = $_SESSION['tenant_id'] ?? null;

        if ($userId) {
            $userModel->updateRememberToken($userId, null);
            $activityLog->log('mobile_logout', 'Mobil SPA oturumu kapatıldı.', $userId, $tenantId);
        }

        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
        ]);

        $_SESSION = [];
        session_regenerate_id(true);

        jsonResponse([
            'success' => true,
            'authenticated' => false,
            'csrf_token' => getCsrfToken(),
            'user' => null,
            'tenant' => null,
            'tenants' => [],
        ]);
    }

    jsonResponse(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    error_log('Mobile Auth API Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'İşlem sırasında beklenmeyen bir hata oluştu.'], 500);
}

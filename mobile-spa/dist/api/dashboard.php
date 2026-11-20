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
    error_log('Mobile Dashboard API: bootstrap.php bulunamadı.');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem yapılandırma hatası oluştu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $projectRoot . '/bootstrap.php';

function dashboardResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
        dashboardResponse(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
    if ($userId <= 0 || $tenantId <= 0) {
        dashboardResponse(['success' => false, 'message' => 'Oturum bulunamadı.'], 401);
    }

    $rolePermModel = new RolePermission();
    $roleId = $_SESSION['role_id'] ?? null;
    $roleName = (string)($_SESSION['role'] ?? 'user');

    $canPersonnel = $rolePermModel->hasAccess($roleId, '/personel-listesi', $roleName);
    $canWages = $rolePermModel->hasAccess($roleId, '/ucret-tanimlari', $roleName);
    $canTasks = $rolePermModel->hasAccess($roleId, '/yapilacaklar', $roleName);

    new Task();
    $notificationModel = new Notification();
    $dashboardModel = new MobileDashboardModel();

    $payload = [
        'success' => true,
        'generated_at' => date('c'),
        'unread_notifications' => $notificationModel->countUnread($userId),
        'personnel' => null,
        'recent_personnel' => [],
        'wage_count' => null,
        'tasks' => null,
        'upcoming_tasks' => [],
    ];

    if ($canPersonnel) {
        $payload['personnel'] = $dashboardModel->getPersonnelSummary($tenantId);
        $payload['recent_personnel'] = $dashboardModel->getRecentPersonnel($tenantId, 5);
    }
    if ($canWages) {
        $payload['wage_count'] = $dashboardModel->getWageCount($tenantId);
    }
    if ($canTasks) {
        $payload['tasks'] = $dashboardModel->getTaskSummary($tenantId, $userId);
        $payload['upcoming_tasks'] = $dashboardModel->getUpcomingTasks($tenantId, $userId, 4);
    }

    (new ActivityLog())->log('mobile_dashboard_view', 'Mobil SPA ana sayfa özeti görüntülendi.', $userId, $tenantId);

    dashboardResponse($payload);
} catch (Throwable $e) {
    error_log('Mobile Dashboard API Error: ' . $e->getMessage());
    dashboardResponse(['success' => false, 'message' => 'Özet verileri alınamadı.'], 500);
}

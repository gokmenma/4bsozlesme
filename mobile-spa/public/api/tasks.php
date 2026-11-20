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
    error_log('Mobile Tasks API: bootstrap.php bulunamadı.');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem yapılandırma hatası oluştu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $projectRoot . '/bootstrap.php';

function taskResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'POST'], true)) {
        taskResponse(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
    if ($userId <= 0 || $tenantId <= 0) {
        taskResponse(['success' => false, 'message' => 'Oturum bulunamadı.'], 401);
    }

    $rolePermModel = new RolePermission();
    if (!$rolePermModel->hasAccess($_SESSION['role_id'] ?? null, '/yapilacaklar', (string)($_SESSION['role'] ?? 'user'))) {
        taskResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
    }

    $body = [];
    if ($method === 'POST') {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['mobile_csrf_token'] ?? '';
        if ($expected === '' || !is_string($provided) || !hash_equals((string)$expected, $provided)) {
            taskResponse(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.'], 419);
        }
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        $body = is_array($decoded) ? $decoded : [];
    }

    (new Task())->getBoardsForTenant($tenantId);
    $model = new MobileTaskModel();
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

    $validateTask = static function (array $input) use ($parseDate, $model, $tenantId): array {
        $errors = [];
        $title = trim((string)($input['title'] ?? ''));
        $priority = (string)($input['priority'] ?? 'medium');
        $boardId = (int)($input['board_id'] ?? 0);
        $dueRaw = trim((string)($input['due_date'] ?? ''));
        $due = $dueRaw === '' ? null : $parseDate($dueRaw);
        $assignees = is_array($input['assignees'] ?? null) ? $input['assignees'] : [];

        if ($title === '' || mb_strlen($title) > 255) {
            $errors[] = 'Görev başlığı zorunludur (en fazla 255 karakter).';
        }
        if (!in_array($priority, MobileTaskModel::PRIORITIES, true)) {
            $errors[] = 'Geçersiz öncelik.';
        }
        if ($boardId <= 0 || !$model->boardExists($tenantId, $boardId)) {
            $errors[] = 'Geçerli bir sütun seçin.';
        }
        if ($dueRaw !== '' && $due === null) {
            $errors[] = 'Bitiş tarihi geçersiz.';
        }

        return [$errors, [
            'title' => $title,
            'description' => mb_substr(trim((string)($input['description'] ?? '')), 0, 2000),
            'priority' => $priority,
            'board_id' => $boardId,
            'due_date' => $due,
            'assignees' => $assignees,
        ]];
    };

    if ($action === 'list' && $method === 'GET') {
        $scope = (string)($_GET['scope'] ?? 'all');
        if (!in_array($scope, ['all', 'mine', 'assigned'], true)) {
            $scope = 'all';
        }
        $activityLog->log('mobile_tasks_list', 'Mobil SPA görev listesi görüntülendi.', $userId, $tenantId);
        taskResponse([
            'success' => true,
            'boards' => $model->getBoards($tenantId),
            'tasks' => $model->getTasks($tenantId, $userId, $scope),
            'users' => $model->getUsers($tenantId),
            'me' => $userId,
        ]);
    }

    if ($method === 'POST' && in_array($action, ['create', 'update'], true)) {
        $id = (int)($body['id'] ?? 0);
        if ($action === 'update' && ($id <= 0 || $model->getTask($tenantId, $id) === null)) {
            taskResponse(['success' => false, 'message' => 'Görev bulunamadı.'], 404);
        }
        [$errors, $data] = $validateTask($body);
        if ($errors) {
            taskResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
        }
        if ($action === 'create') {
            $newId = $model->createTask($tenantId, $userId, $data);
            $activityLog->log('mobile_task_create', 'Mobil SPA ile görev eklendi (ID: ' . $newId . ').', $userId, $tenantId);
            taskResponse(['success' => true, 'id' => $newId], 201);
        }
        $model->updateTask($tenantId, $id, $data);
        $activityLog->log('mobile_task_update', 'Mobil SPA ile görev güncellendi (ID: ' . $id . ').', $userId, $tenantId);
        taskResponse(['success' => true, 'id' => $id]);
    }

    if ($method === 'POST' && $action === 'move') {
        $id = (int)($body['id'] ?? 0);
        $boardId = (int)($body['board_id'] ?? 0);
        if ($id <= 0 || $model->getTask($tenantId, $id) === null) {
            taskResponse(['success' => false, 'message' => 'Görev bulunamadı.'], 404);
        }
        if ($boardId <= 0 || !$model->boardExists($tenantId, $boardId)) {
            taskResponse(['success' => false, 'message' => 'Geçerli bir sütun seçin.'], 422);
        }
        $model->moveTask($tenantId, $id, $boardId);
        $activityLog->log('mobile_task_move', 'Mobil SPA ile görev taşındı (ID: ' . $id . ').', $userId, $tenantId);
        taskResponse(['success' => true]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0 || !$model->deleteTask($tenantId, $id)) {
            taskResponse(['success' => false, 'message' => 'Görev bulunamadı.'], 404);
        }
        $activityLog->log('mobile_task_delete', 'Mobil SPA ile görev silindi (ID: ' . $id . ').', $userId, $tenantId);
        taskResponse(['success' => true]);
    }

    taskResponse(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    error_log('Mobile Tasks API Error: ' . $e->getMessage());
    taskResponse(['success' => false, 'message' => 'Görev verileri alınamadı.'], 500);
}

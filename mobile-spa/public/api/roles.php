<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

try {
    $ctx = mobileApiBoot('Roles', '/yetki-gruplari');
    ['method' => $method, 'userId' => $userId, 'tenantId' => $tenantId, 'isSuper' => $isSuper, 'body' => $body, 'action' => $action, 'log' => $log] = $ctx;
    $model = new MobileRoleModel();

    $loadManageable = static function (int $id) use ($model, $tenantId, $isSuper): array {
        $role = $id > 0 ? $model->getRole($id) : null;
        if ($role === null) {
            mobileJson(['success' => false, 'message' => 'Yetki grubu bulunamadı.'], 404);
        }
        if (!$model->canManage($role, $tenantId, $isSuper)) {
            mobileJson(['success' => false, 'message' => 'Bu yetki grubunu düzenleme yetkiniz yok.'], 403);
        }
        return $role;
    };

    if ($action === 'list' && $method === 'GET') {
        $roles = array_map(static fn($r) => [
            'id' => (int)$r['id'], 'name' => $r['name'], 'description' => $r['description'],
            'is_system' => (int)$r['is_system'], 'user_count' => (int)$r['user_count'],
            'editable' => $model->canManage($r, $tenantId, $isSuper),
        ], $model->list($tenantId, $isSuper));
        $log->log('mobile_roles_list', 'Mobil SPA yetki grupları görüntülendi.', $userId, $tenantId);
        mobileJson(['success' => true, 'roles' => $roles, 'pages' => $model->pages()]);
    }

    if ($action === 'permissions' && $method === 'GET') {
        $roleId = (int)($_GET['role_id'] ?? 0);
        $loadManageable($roleId);
        mobileJson(['success' => true, 'permissions' => $model->permissions($roleId)]);
    }

    if ($method === 'POST' && in_array($action, ['create', 'update'], true)) {
        $name = mb_substr(trim((string)($body['name'] ?? '')), 0, 100);
        $description = mb_substr(trim((string)($body['description'] ?? '')), 0, 255);
        if ($name === '') {
            mobileJson(['success' => false, 'message' => 'Yetki grubu adı zorunludur.'], 422);
        }
        if ($action === 'create') {
            $id = $model->createRole($name, $description, $tenantId);
            $log->log('mobile_role_create', 'Mobil SPA ile yetki grubu eklendi (ID: ' . $id . ').', $userId, $tenantId);
            mobileJson(['success' => true, 'id' => $id], 201);
        }
        $id = (int)($body['id'] ?? 0);
        $loadManageable($id);
        $model->updateRole($id, $name, $description);
        $log->log('mobile_role_update', 'Mobil SPA ile yetki grubu güncellendi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true, 'id' => $id]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $role = $loadManageable($id);
        if (!empty($role['is_system']) || $id === 1) {
            mobileJson(['success' => false, 'message' => 'Sistem rolleri silinemez.'], 403);
        }
        $count = $model->userCount($id);
        if ($count > 0) {
            mobileJson(['success' => false, 'message' => "Bu gruba tanımlı {$count} kullanıcı var. Önce kullanıcıların grubunu değiştirin."], 422);
        }
        $model->deleteRole($id);
        $log->log('mobile_role_delete', 'Mobil SPA ile yetki grubu silindi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true]);
    }

    if ($method === 'POST' && $action === 'save_permissions') {
        $id = (int)($body['role_id'] ?? 0);
        $loadManageable($id);
        if ($id === 1) {
            mobileJson(['success' => false, 'message' => 'Süper yönetici izinleri kısıtlanamaz.'], 403);
        }
        $input = is_array($body['permissions'] ?? null) ? $body['permissions'] : [];
        if (!$model->savePermissions($id, $input)) {
            mobileJson(['success' => false, 'message' => 'İzinler kaydedilemedi.'], 500);
        }
        $log->log('mobile_role_permissions', 'Mobil SPA ile yetki grubu izinleri güncellendi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true, 'permissions' => $model->permissions($id)]);
    }

    mobileJson(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    mobileFail('Roles', $e);
}

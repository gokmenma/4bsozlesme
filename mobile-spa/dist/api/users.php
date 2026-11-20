<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

try {
    $ctx = mobileApiBoot('Users', '/kullanicilar');
    ['method' => $method, 'userId' => $userId, 'tenantId' => $tenantId, 'isSuper' => $isSuper, 'body' => $body, 'action' => $action, 'log' => $log] = $ctx;
    $model = new MobileUserModel();
    $roleModel = new MobileRoleModel();

    $manageable = static function (?array $target) use ($isSuper, $tenantId): bool {
        return $target !== null && ($isSuper || (int)($target['tenant_id'] ?? 0) === $tenantId);
    };

    $validate = static function (array $in, ?array $existing) use ($model, $roleModel, $tenantId, $userId, $isSuper): array {
        $errors = [];
        $name = trim(preg_replace('/\s+/u', ' ', (string)($in['name'] ?? '')));
        $email = mb_strtolower(trim((string)($in['email'] ?? '')));
        $username = trim((string)($in['username'] ?? ''));
        $password = (string)($in['password'] ?? '');
        $roleId = (int)($in['role_id'] ?? 0);
        $targetTenant = $existing ? ($existing['tenant_id'] !== null ? (int)$existing['tenant_id'] : null) : $tenantId;

        if ($name === '' || mb_strlen($name) > 255) {
            $errors[] = 'Ad soyad zorunludur.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors[] = 'Geçerli bir e-posta girin.';
        }
        if ($username === '') {
            $username = explode('@', $email)[0];
        }
        if (mb_strlen($username) > 100) {
            $errors[] = 'Kullanıcı adı çok uzun.';
        }
        if ($existing === null && mb_strlen($password) < 8) {
            $errors[] = 'Şifre en az 8 karakter olmalıdır.';
        } elseif ($password !== '' && mb_strlen($password) < 8) {
            $errors[] = 'Şifre en az 8 karakter olmalıdır.';
        }

        $role = $existing['role'] ?? 'user';
        if ($roleId > 0) {
            $roleRow = $roleModel->getRole($roleId);
            if ($roleRow === null || !($isSuper || !empty($roleRow['is_system']) || (int)($roleRow['tenant_id'] ?? 0) === $tenantId)) {
                $errors[] = 'Geçersiz yetki grubu.';
            }
            if ($roleId === 1 && $userId !== 1 && (!$existing || $existing['role'] !== 'superadmin')) {
                $errors[] = 'Süper yönetici yetkisi atayamazsınız.';
            }
            $role = $roleId === 1 ? 'superadmin' : ($roleId === 2 ? 'admin' : 'user');
        } else {
            $errors[] = 'Yetki grubu seçin.';
        }

        if (!$errors && $model->emailTaken($email, $targetTenant, $existing ? (int)$existing['id'] : 0)) {
            $errors[] = 'Bu e-posta adresi bu kurumda zaten kayıtlı.';
        }

        return [$errors, [
            'name' => $name, 'email' => $email, 'username' => $username, 'password' => $password,
            'role' => $role, 'role_id' => $roleId, 'tenant_id' => $targetTenant, 'created_by' => $userId,
        ]];
    };

    if ($action === 'list' && $method === 'GET') {
        $scope = $isSuper && ($_GET['scope'] ?? '') === 'all' ? null : $tenantId;
        $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);
        $log->log('mobile_users_list', 'Mobil SPA kullanıcı listesi görüntülendi.', $userId, $tenantId);
        mobileJson([
            'success' => true,
            'users' => $model->list($scope, $search),
            'roles' => $roleModel->list($tenantId, $isSuper),
            'can_scope_all' => $isSuper,
            'me' => $userId,
        ]);
    }

    if ($method === 'POST' && in_array($action, ['create', 'update'], true)) {
        $id = (int)($body['id'] ?? 0);
        $existing = null;
        if ($action === 'update') {
            $existing = $id > 0 ? $model->get($id) : null;
            if (!$manageable($existing)) {
                mobileJson(['success' => false, 'message' => 'Kullanıcı bulunamadı.'], 404);
            }
            if ($id === 1 && $userId !== 1) {
                mobileJson(['success' => false, 'message' => 'Ana kurucu kullanıcıyı değiştirme yetkiniz yok.'], 403);
            }
        }
        [$errors, $data] = $validate($body, $existing);
        if ($errors) {
            mobileJson(['success' => false, 'message' => implode(' ', $errors)], 422);
        }
        if ($action === 'create') {
            $newId = $model->createUser($data);
            $log->log('mobile_user_create', 'Mobil SPA ile kullanıcı eklendi (ID: ' . $newId . ').', $userId, $tenantId);
            mobileJson(['success' => true, 'id' => $newId], 201);
        }
        $model->updateUser($id, $data);
        $log->log('mobile_user_update', 'Mobil SPA ile kullanıcı güncellendi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true, 'id' => $id]);
    }

    if ($method === 'POST' && $action === 'set_active') {
        $id = (int)($body['id'] ?? 0);
        $target = $id > 0 ? $model->get($id) : null;
        if (!$manageable($target)) {
            mobileJson(['success' => false, 'message' => 'Kullanıcı bulunamadı.'], 404);
        }
        if ($id === 1) {
            mobileJson(['success' => false, 'message' => 'Ana kurucu kullanıcı pasif yapılamaz.'], 403);
        }
        if ($id === $userId) {
            mobileJson(['success' => false, 'message' => 'Kendi hesabınızı pasif yapamazsınız.'], 422);
        }
        $active = !empty($body['active']);
        $model->setActive($id, $active);
        $log->log('mobile_user_set_active', 'Mobil SPA ile kullanıcı ' . ($active ? 'aktif' : 'pasif') . ' yapıldı (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true, 'active' => $active]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $target = $id > 0 ? $model->get($id) : null;
        if (!$manageable($target)) {
            mobileJson(['success' => false, 'message' => 'Kullanıcı bulunamadı.'], 404);
        }
        if ($id === 1) {
            mobileJson(['success' => false, 'message' => 'Ana kurucu kullanıcı silinemez.'], 403);
        }
        if ($id === $userId) {
            mobileJson(['success' => false, 'message' => 'Kendi hesabınızı silemezsiniz.'], 422);
        }
        $model->deleteUser($id);
        $log->log('mobile_user_delete', 'Mobil SPA ile kullanıcı silindi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true]);
    }

    mobileJson(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    mobileFail('Users', $e);
}

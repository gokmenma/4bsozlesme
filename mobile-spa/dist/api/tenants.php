<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

try {
    $ctx = mobileApiBoot('Tenants', null, true, false);
    ['method' => $method, 'userId' => $userId, 'tenantId' => $tenantId, 'body' => $body, 'action' => $action, 'log' => $log] = $ctx;
    $model = new MobileTenantModel();

    if ($action === 'list' && $method === 'GET') {
        $log->log('mobile_tenants_list', 'Mobil SPA kurum listesi görüntülendi.', $userId, $tenantId);
        mobileJson(['success' => true, 'tenants' => $model->list(), 'current' => $tenantId]);
    }

    if ($method === 'POST' && $action === 'update') {
        $id = (int)($body['id'] ?? 0);
        $name = mb_substr(trim((string)($body['name'] ?? '')), 0, 255);
        if ($id <= 0 || !$model->exists($id)) {
            mobileJson(['success' => false, 'message' => 'Kurum bulunamadı.'], 404);
        }
        if ($name === '') {
            mobileJson(['success' => false, 'message' => 'Kurum adı zorunludur.'], 422);
        }
        $model->updateTenant($id, $name, !empty($body['is_active']));
        $log->log('mobile_tenant_update', 'Mobil SPA ile kurum güncellendi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0 || !$model->exists($id)) {
            mobileJson(['success' => false, 'message' => 'Kurum bulunamadı.'], 404);
        }
        if ($id === $tenantId) {
            mobileJson(['success' => false, 'message' => 'Aktif kurumunuzu silemezsiniz.'], 422);
        }
        $users = $model->userCount($id);
        if ($users > 0) {
            mobileJson(['success' => false, 'message' => "Bu kuruma bağlı {$users} kullanıcı var. Önce kullanıcıları kaldırın."], 422);
        }
        $model->deleteTenant($id);
        $log->log('mobile_tenant_delete', 'Mobil SPA ile kurum silindi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true]);
    }

    mobileJson(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    mobileFail('Tenants', $e);
}

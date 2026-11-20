<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

try {
    $ctx = mobileApiBoot('Subscription', null, true, false);
    ['method' => $method, 'userId' => $userId, 'tenantId' => $tenantId, 'body' => $body, 'action' => $action, 'log' => $log] = $ctx;
    $model = new MobileSubscriptionModel();

    $validatePlan = static function (array $in): array {
        $errors = [];
        $name = mb_substr(trim((string)($in['name'] ?? '')), 0, 100);
        $price = str_replace(',', '.', trim((string)($in['price'] ?? '')));
        $days = (int)($in['duration_days'] ?? 0);
        if ($name === '') {
            $errors[] = 'Paket adı zorunludur.';
        }
        if (!is_numeric($price) || (float)$price < 0 || (float)$price > 10000000) {
            $errors[] = 'Fiyat geçersiz.';
        }
        if ($days < 1 || $days > 3650) {
            $errors[] = 'Süre 1-3650 gün arasında olmalıdır.';
        }
        return [$errors, [
            'name' => $name, 'price' => round((float)$price, 2), 'duration_days' => $days,
            'features' => mb_substr(trim((string)($in['features'] ?? '')), 0, 1000),
        ]];
    };

    if ($action === 'list' && $method === 'GET') {
        $history = $model->history();
        $stats = ['total_revenue' => 0.0, 'active_count' => 0, 'pending_count' => 0, 'total_count' => count($history)];
        foreach ($history as $h) {
            if ($h['payment_status'] === 'paid') {
                $stats['total_revenue'] += (float)$h['amount'];
            }
            if ($h['status'] === 'active') {
                $stats['active_count']++;
            }
            if ($h['payment_status'] === 'pending') {
                $stats['pending_count']++;
            }
        }
        $log->log('mobile_subscription_list', 'Mobil SPA abonelik ekranı görüntülendi.', $userId, $tenantId);
        mobileJson(['success' => true, 'plans' => $model->plans(), 'history' => $history, 'stats' => $stats]);
    }

    if ($method === 'POST' && in_array($action, ['approve', 'reject', 'delete'], true)) {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0 || !$model->exists($id)) {
            mobileJson(['success' => false, 'message' => 'Abonelik kaydı bulunamadı.'], 404);
        }
        if ($action === 'approve') {
            $model->setState($id, 'active', 'paid');
        } elseif ($action === 'reject') {
            $model->setState($id, 'cancelled', 'failed');
        } else {
            $model->deleteSubscription($id);
        }
        $log->log('mobile_subscription_' . $action, 'Mobil SPA abonelik işlemi: ' . $action . ' (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true]);
    }

    if ($method === 'POST' && in_array($action, ['plan_create', 'plan_update'], true)) {
        [$errors, $data] = $validatePlan($body);
        if ($errors) {
            mobileJson(['success' => false, 'message' => implode(' ', $errors)], 422);
        }
        if ($action === 'plan_create') {
            $id = $model->createPlan($data);
            $log->log('mobile_plan_create', 'Mobil SPA ile abonelik paketi eklendi (ID: ' . $id . ').', $userId, $tenantId);
            mobileJson(['success' => true, 'id' => $id], 201);
        }
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0 || !$model->planExists($id)) {
            mobileJson(['success' => false, 'message' => 'Paket bulunamadı.'], 404);
        }
        $model->updatePlan($id, $data);
        $log->log('mobile_plan_update', 'Mobil SPA ile abonelik paketi güncellendi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true, 'id' => $id]);
    }

    if ($method === 'POST' && $action === 'plan_delete') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0 || !$model->planExists($id)) {
            mobileJson(['success' => false, 'message' => 'Paket bulunamadı.'], 404);
        }
        $model->deactivatePlan($id);
        $log->log('mobile_plan_delete', 'Mobil SPA ile abonelik paketi kaldırıldı (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true]);
    }

    mobileJson(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    mobileFail('Subscription', $e);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

try {
    $ctx = mobileApiBoot('Feedbacks', null, true, false);
    ['method' => $method, 'userId' => $userId, 'tenantId' => $tenantId, 'body' => $body, 'action' => $action, 'log' => $log] = $ctx;
    $model = new MobileFeedbackModel();

    if ($action === 'list' && $method === 'GET') {
        $status = (string)($_GET['status'] ?? 'all');
        if ($status !== 'all' && !in_array($status, Feedback::STATUSES, true)) {
            $status = 'all';
        }
        $log->log('mobile_feedbacks_list', 'Mobil SPA geri bildirimler görüntülendi.', $userId, $tenantId);
        mobileJson(['success' => true, 'items' => $model->list($status), 'counts' => $model->counts()]);
    }

    if ($method === 'POST' && $action === 'update') {
        $id = (int)($body['id'] ?? 0);
        $status = (string)($body['status'] ?? '');
        $note = mb_substr(trim((string)($body['note'] ?? '')), 0, 2000);
        if (!in_array($status, Feedback::STATUSES, true)) {
            mobileJson(['success' => false, 'message' => 'Geçersiz durum.'], 422);
        }
        $feedback = $id > 0 ? $model->getOne($id) : null;
        if ($feedback === null) {
            mobileJson(['success' => false, 'message' => 'Geri bildirim bulunamadı.'], 404);
        }
        $model->respond($feedback, $status, $note);
        $log->log('mobile_feedback_update', 'Mobil SPA ile geri bildirim güncellendi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0 || $model->getOne($id) === null) {
            mobileJson(['success' => false, 'message' => 'Geri bildirim bulunamadı.'], 404);
        }
        $model->deleteOne($id);
        $log->log('mobile_feedback_delete', 'Mobil SPA ile geri bildirim silindi (ID: ' . $id . ').', $userId, $tenantId);
        mobileJson(['success' => true]);
    }

    mobileJson(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    mobileFail('Feedbacks', $e);
}

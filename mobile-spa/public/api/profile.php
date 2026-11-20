<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

try {
    $ctx = mobileApiBoot('Profile', null);
    ['method' => $method, 'userId' => $userId, 'tenantId' => $tenantId, 'body' => $body, 'action' => $action, 'log' => $log] = $ctx;
    $model = new MobileUserModel();

    if ($method === 'POST' && $action === 'password') {
        $current = (string)($body['current_password'] ?? '');
        $new = (string)($body['new_password'] ?? '');
        $confirm = (string)($body['confirm_password'] ?? '');

        $attempts = (int)($_SESSION['mobile_pw_attempts'] ?? 0);
        $lockedUntil = (int)($_SESSION['mobile_pw_locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            mobileJson(['success' => false, 'message' => 'Çok fazla hatalı deneme yaptınız. Lütfen daha sonra tekrar deneyin.'], 429);
        }

        if ($current === '' || $new === '' || $confirm === '') {
            mobileJson(['success' => false, 'message' => 'Tüm alanları doldurun.'], 422);
        }
        if (mb_strlen($new) < 8 || mb_strlen($new) > 200) {
            mobileJson(['success' => false, 'message' => 'Yeni şifre en az 8 karakter olmalıdır.'], 422);
        }
        if ($new !== $confirm) {
            mobileJson(['success' => false, 'message' => 'Yeni şifreler eşleşmiyor.'], 422);
        }
        if ($new === $current) {
            mobileJson(['success' => false, 'message' => 'Yeni şifre mevcut şifreden farklı olmalıdır.'], 422);
        }

        $hash = $model->getPasswordHash($userId);
        if ($hash === null || !password_verify($current, $hash)) {
            $attempts++;
            $_SESSION['mobile_pw_attempts'] = $attempts;
            if ($attempts >= 5) {
                $_SESSION['mobile_pw_locked_until'] = time() + 900;
                $_SESSION['mobile_pw_attempts'] = 0;
            }
            $log->log('mobile_password_change_failed', 'Mobil SPA şifre değiştirme: mevcut şifre hatalı.', $userId, $tenantId);
            mobileJson(['success' => false, 'message' => 'Mevcut şifre hatalı.'], 422);
        }

        $model->updatePassword($userId, $new);
        unset($_SESSION['mobile_pw_attempts'], $_SESSION['mobile_pw_locked_until']);
        session_regenerate_id(true);
        $log->log('mobile_password_change', 'Mobil SPA ile kullanıcı şifresini değiştirdi.', $userId, $tenantId);
        mobileJson(['success' => true]);
    }

    mobileJson(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    mobileFail('Profile', $e);
}

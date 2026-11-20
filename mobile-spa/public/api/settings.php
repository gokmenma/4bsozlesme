<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

try {
    $ctx = mobileApiBoot('Settings', null, true);
    ['method' => $method, 'userId' => $userId, 'tenantId' => $tenantId, 'body' => $body, 'action' => $action, 'log' => $log] = $ctx;
    $model = new MobileSettingsModel();

    if ($action === 'get' && $method === 'GET') {
        $log->log('mobile_settings_view', 'Mobil SPA sistem ayarları görüntülendi.', $userId, $tenantId);
        mobileJson(['success' => true, 'settings' => $model->getPublic($tenantId)]);
    }

    if ($method === 'POST' && $action === 'save') {
        $text = static fn($v, int $max): string => mb_substr(trim((string)$v), 0, $max);
        $errors = [];
        $d = [
            'kadro_bildirim_aktif' => !empty($body['kadro_bildirim_aktif']) ? 1 : 0,
            'sms_active' => !empty($body['sms_active']) ? 1 : 0,
            'sms_api_url' => $text($body['sms_api_url'] ?? '', 500),
            'sms_api_key' => $text($body['sms_api_key'] ?? '', 500),
            'sms_sender' => $text($body['sms_sender'] ?? '', 50),
            'sms_entegrator' => $text($body['sms_entegrator'] ?? 'NETGSM', 50) ?: 'NETGSM',
            'smtp_host' => $text($body['smtp_host'] ?? '', 255),
            'smtp_port' => null,
            'smtp_user' => $text($body['smtp_user'] ?? '', 255),
            'smtp_pass' => $text($body['smtp_pass'] ?? '', 500),
            'smtp_from_email' => $text($body['smtp_from_email'] ?? '', 255),
            'smtp_from_name' => $text($body['smtp_from_name'] ?? '', 255),
        ];
        $port = trim((string)($body['smtp_port'] ?? ''));
        if ($port !== '') {
            if (!ctype_digit($port) || (int)$port < 1 || (int)$port > 65535) {
                $errors[] = 'SMTP port geçersiz.';
            } else {
                $d['smtp_port'] = (int)$port;
            }
        }
        if ($d['sms_api_url'] !== '' && !preg_match('#^https://#i', $d['sms_api_url'])) {
            $errors[] = 'SMS API adresi https:// ile başlamalıdır.';
        }
        if ($d['smtp_from_email'] !== '' && !filter_var($d['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Gönderen e-posta geçersiz.';
        }
        if ($errors) {
            mobileJson(['success' => false, 'message' => implode(' ', $errors)], 422);
        }
        $model->save($tenantId, $d);
        $log->log('mobile_settings_update', 'Mobil SPA ile sistem ayarları güncellendi.', $userId, $tenantId);
        mobileJson(['success' => true, 'settings' => $model->getPublic($tenantId)]);
    }

    if ($method === 'POST' && $action === 'test_mail') {
        $to = trim((string)($body['test_email'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            mobileJson(['success' => false, 'message' => 'Geçerli bir alıcı e-posta girin.'], 422);
        }
        $raw = $model->getRaw($tenantId);
        if ($raw['smtp_host'] === '' || $raw['smtp_user'] === '' || $raw['smtp_pass'] === '') {
            mobileJson(['success' => false, 'message' => 'Önce SMTP ayarlarını kaydedin.'], 422);
        }
        $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        }
        require_once $autoload;

        $port = (int)($raw['smtp_port'] ?: 587);
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $raw['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $raw['smtp_user'];
            $mail->Password = $raw['smtp_pass'];
            $mail->Timeout = 10;
            $mail->SMTPSecure = $port === 465 ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $port;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($raw['smtp_from_email'] ?: $raw['smtp_user'], $raw['smtp_from_name'] ?: 'Kadro Bildirim Sistemi');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = 'SMTP Test E-postası';
            $mail->Body = '<h3>SMTP bağlantısı başarılı!</h3><p>Bu e-posta mobil uygulamadan gönderilen bir test mesajıdır.</p>';
            $mail->send();
        } catch (\Throwable $e) {
            error_log('Mobile Settings test mail error: ' . $mail->ErrorInfo);
            mobileJson(['success' => false, 'message' => 'E-posta gönderilemedi. SMTP ayarlarını kontrol edin.'], 502);
        }
        $log->log('mobile_settings_test_mail', 'Mobil SPA ile SMTP test e-postası gönderildi.', $userId, $tenantId);
        mobileJson(['success' => true, 'message' => 'Test e-postası gönderildi.']);
    }

    mobileJson(['success' => false, 'message' => 'Geçersiz istek.'], 404);
} catch (Throwable $e) {
    mobileFail('Settings', $e);
}

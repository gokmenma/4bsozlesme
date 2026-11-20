<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

$ctx = mobileApiBoot('Kadro Mesaj', '/personel-listesi');

try {
    if ($ctx['method'] !== 'POST') {
        mobileJson(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $personId = (int)($ctx['body']['personel_id'] ?? 0);
    if ($personId <= 0) {
        mobileJson(['success' => false, 'message' => 'Personel bulunamadı.'], 422);
    }

    $model = new MobileDashboardModel();
    $target = $model->getKadroTarget($ctx['tenantId'], $personId);
    if ($target === null) {
        mobileJson(['success' => false, 'message' => 'Personel bulunamadı.'], 404);
    }

    $ctx['log']->log(
        'mobile_kadro_whatsapp',
        'Kadro geçiş bilgilendirmesi WhatsApp ile hazırlandı: ' . $target['ad_soyad'] . ' (ID: ' . $target['id'] . ', kadro tarihi: ' . $target['kadro_tarihi'] . ')',
        $ctx['userId'],
        $ctx['tenantId']
    );

    mobileJson(['success' => true]);
} catch (Throwable $e) {
    mobileFail('Kadro Mesaj', $e);
}

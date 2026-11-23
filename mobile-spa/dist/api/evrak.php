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

function evrakResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($projectRoot === null) {
    error_log('Mobile Evrak API: bootstrap.php bulunamadı.');
    evrakResponse(['success' => false, 'message' => 'Sistem yapılandırma hatası oluştu.'], 500);
}

require_once $projectRoot . '/bootstrap.php';

function evrakPublicLink(string $token): string
{
    $base = (string)(getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? ''));
    if ($base === '') {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = preg_replace('/[^a-zA-Z0-9.\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $host = preg_replace('/^mobile\./i', '', $host);
        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $path = '';
        if (($pos = strpos($script, '/mobile-spa/')) !== false) {
            $path = substr($script, 0, $pos);
        }
        $base = ($https ? 'https://' : 'http://') . $host . $path;
    }
    return rtrim($base, '/') . '/evrak?t=' . $token;
}

try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'POST'], true)) {
        evrakResponse(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
    if ($userId <= 0 || $tenantId <= 0) {
        evrakResponse(['success' => false, 'message' => 'Oturum bulunamadı.'], 401);
    }

    $rolePermModel = new RolePermission();
    if (!$rolePermModel->hasAccess($_SESSION['role_id'] ?? null, '/evrak-takip', (string)($_SESSION['role'] ?? 'user'))) {
        evrakResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
    }

    $body = [];
    if ($method === 'POST') {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['mobile_csrf_token'] ?? '';
        if ($expected === '' || !is_string($provided) || !hash_equals((string)$expected, $provided)) {
            evrakResponse(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.'], 419);
        }
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        $body = is_array($decoded) ? $decoded : [];
    }

    $activityLog = new ActivityLog();
    $paketModel = new EvrakPaket();
    $action = (string)($_GET['action'] ?? 'options');

    if ($action === 'options' && $method === 'GET') {
        $personel = $paketModel->getPersonnel((int)($_GET['personel_id'] ?? 0), $tenantId);
        if (!$personel) {
            evrakResponse(['success' => false, 'message' => 'Personel bulunamadı.'], 404);
        }
        $formlar = array_map(static fn(array $f): array => [
            'id' => (int)$f['id'],
            'ad' => (string)$f['ad'],
            'tip' => (string)$f['tip'],
            'aciklama' => (string)($f['aciklama'] ?? ''),
            'alan_sayisi' => (int)$f['alan_sayisi'],
        ], array_values(array_filter(
            (new EvrakForm())->listByTenant($tenantId, true),
            static fn(array $f): bool => $f['tip'] !== 'cikti'
        )));
        evrakResponse([
            'success' => true,
            'personel' => ['id' => (int)$personel['id'], 'ad_soyad' => $personel['ad_soyad'], 'telefon' => $personel['telefon']],
            'formlar' => $formlar,
        ]);
    }

    if ($action === 'send' && $method === 'POST') {
        $kanal = (string)($body['kanal'] ?? 'link');
        if (!in_array($kanal, ['link', 'sms', 'mail'], true)) {
            evrakResponse(['success' => false, 'message' => 'Geçersiz gönderim kanalı.'], 422);
        }
        $email = trim((string)($body['email'] ?? ''));
        if ($kanal === 'mail' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            evrakResponse(['success' => false, 'message' => 'Geçerli bir e-posta adresi giriniz.'], 422);
        }
        $formIds = is_array($body['form_ids'] ?? null) ? $body['form_ids'] : [];

        $sonuc = $paketModel->createPackage($tenantId, (int)($body['personel_id'] ?? 0), $formIds, $kanal, (int)($body['gun'] ?? 7), $email ?: null, $userId);
        $paket = $paketModel->findForTenant($sonuc['id'], $tenantId);
        $link = evrakPublicLink($sonuc['token']);
        $gonderim = EvrakGonderService::teslimEt($paket, $link, $kanal, $email, $tenantId, $userId);

        $activityLog->log('Evrak Paketi Gönderme', 'Mobil - Paket #' . $sonuc['id'] . ' - ' . $sonuc['personel']['ad_soyad'] . ' (' . $kanal . ')' . ($gonderim['success'] ? '' : ' - gönderim başarısız'), $userId, $tenantId);
        evrakResponse([
            'success' => true,
            'paket_id' => $sonuc['id'],
            'link' => $link,
            'gonderim' => $gonderim,
            'message' => $gonderim['message'],
        ]);
    }

    evrakResponse(['success' => false, 'message' => 'Geçersiz işlem.'], 404);
} catch (InvalidArgumentException $e) {
    evrakResponse(['success' => false, 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('Mobile Evrak API: ' . $e->getMessage());
    evrakResponse(['success' => false, 'message' => 'İşlem sırasında bir hata oluştu.'], 500);
}

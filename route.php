<?php

function currentRoute(): string
{
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $basePath = appBasePath();

    if ($basePath !== '' && $basePath !== '/' && strpos($requestPath, $basePath) === 0) {
        $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
    }

    $requestPath = '/' . trim($requestPath, '/');

    return $requestPath === '/' ? '/' : $requestPath;
}

function appBasePath(): string
{
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    return ($basePath === '/' || $basePath === '.') ? '' : $basePath;
}

function routeUrl(string $path): string
{
    $url = appBasePath() . '/' . ltrim($path, '/');
    $parsed = parse_url($path);
    $purePath = $parsed['path'] ?? $path;
    
    if (preg_match('/\.(css|js)$/i', $purePath)) {
        $fullPath = __DIR__ . '/' . ltrim($purePath, '/');
        if (file_exists($fullPath)) {
            $version = filemtime($fullPath);
            $separator = (isset($parsed['query']) && $parsed['query'] !== '') ? '&' : '?';
            $url .= $separator . 'v=' . $version;
        }
    }

    return $url;
}

function isStandaloneRoute(string $page): bool
{
    return in_array($page, ['/login', '/logout', '/register'], true) || strpos($page, '/mobile') === 0;
}

function renderPage(string $page): void
{
    global $pageTitle, $pageSubtitle;
    $pageFile = 'app/pages/' . $page . '.php';
    $fallbackFile = 'app/pages/index.php';

    if (file_exists($pageFile)) {
        include $pageFile;
        return;
    }

    if (file_exists($fallbackFile)) {
        include $fallbackFile;
        return;
    }

    echo '<p>Sayfa bulunamadi.</p>';
}

function logoutUser(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            $params['secure'] ?? false,
            $params['httponly'] ?? true
        );
    }

    session_destroy();
}

function renderRoute(string $page): void
{
    if ($page === '/mobile/manifest.json') {
        header('Content-Type: application/json; charset=utf-8');
        readfile(__DIR__ . '/mobile/manifest.json');
        exit;
    }

    if ($page === '/mobile/sw.js') {
        header('Content-Type: application/javascript; charset=utf-8');
        readfile(__DIR__ . '/mobile/sw.js');
        exit;
    }

    if ($page === '/mobile') {
        include 'mobile/index.php';
        exit;
    }

    if ($page === '/mobile/pages/home/index.php') {
        include 'mobile/pages/home/index.php';
        exit;
    }

    if ($page === '/mobile/pages/personel/index.php') {
        include 'mobile/pages/personel/index.php';
        exit;
    }

    if ($page === '/mobile/pages/ucretler/index.php') {
        include 'mobile/pages/ucretler/index.php';
        exit;
    }

    if ($page === '/mobile/pages/profil/index.php') {
        include 'mobile/pages/profil/index.php';
        exit;
    }

    if ($page === '/mobile/pages/other/index.php') {
        include 'mobile/pages/other/index.php';
        exit;
    }

    if ($page === '/mobile/pages/other/tenants.php') {
        include 'mobile/pages/other/tenants.php';
        exit;
    }

    if ($page === '/mobile/pages/other/users.php') {
        include 'mobile/pages/other/users.php';
        exit;
    }

    if ($page === '/mobile/pages/other/subscription.php') {
        include 'mobile/pages/other/subscription.php';
        exit;
    }

    if ($page === '/mobile/pages/other/template.php') {
        include 'mobile/pages/other/template.php';
        exit;
    }

    if ($page === '/mobile/pages/other/settings.php') {
        include 'mobile/pages/other/settings.php';
        exit;
    }

    if ($page === '/login') {
        include 'login.php';
        exit;
    }

    if ($page === '/register') {
        include 'register.php';
        exit;
    }

    if ($page === '/logout') {
        logoutUser();
        header('Location: ' . routeUrl('login'));
        exit;
    }

    // Controller bazlı yönlendirme
    if ($page === '/tanimlamalar') {
        $controller = new TanimlamalarController();
        $data = $controller->index();
        extract($data);
        include 'app/pages/tanimlamalar.php';
        return;
    }

    if ($page === '/doner-matrahi-olustur') {
        $controller = new DonerMatrahiController();
        $data = $controller->index();
        extract($data);
        include 'app/pages/doner-matrahi-olustur.php';
        return;
    }

    if ($page === '/doner-matrahi-indir') {
        $controller = new DonerMatrahiController();
        $controller->downloadBasis();
        return;
    }

    if ($page === '/matrah-yonetimi') {
        $controller = new MatrahController();
        $controller->index();
        return;
    }

    if ($page === '/personel-listesi') {
        $controller = new PersonnelController();
        $data = $controller->list();
        extract($data);
        include 'app/pages/personel/list.php';
        return;
    }

    if ($page === '/personel-datatable' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new PersonnelController();
        $controller->fetchDataTable();
        exit;
    }

    if ($page === '/personel-ekle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new PersonnelController();
        $controller->store();
        exit;
    }

    if ($page === '/personel-get') {
        $controller = new PersonnelController();
        $controller->get();
        exit;
    }

    if ($page === '/personel-guncelle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new PersonnelController();
        $controller->update();
        exit;
    }

    if ($page === '/personel-ai-scan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new PersonnelController();
        $controller->aiScan();
        exit;
    }

    if ($page === '/personel-sil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new PersonnelController();
        $controller->delete();
        exit;
    }

    if ($page === '/personel-get-preview') {
        $controller = new PersonnelController();
        $controller->previewContract();
        exit;
    }

    if ($page === '/personel-download-word') {
        $controller = new PersonnelController();
        $controller->downloadWord();
        exit;
    }

    if ($page === '/personel-import-excel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new PersonnelController();
        $controller->importExcel();
        exit;
    }

    if ($page === '/personel-sample-template') {
        $controller = new PersonnelController();
        $controller->downloadSample();
        exit;
    }

    if ($page === '/') {
        $controller = new DashboardController();
        $data = $controller->index();
        extract($data);
        include 'app/pages/home.php';
        return;
    }

    if ($page === '/ucret-tanimlari') {
        $controller = new WageController();
        $data = $controller->list();
        extract($data);
        include 'app/pages/ucret-tanimlari/list.php';
        return;
    }

    if ($page === '/ucret-ekle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new WageController();
        $controller->store();
        exit;
    }

    if ($page === '/ucret-get') {
        $controller = new WageController();
        $controller->get();
        exit;
    }

    if ($page === '/ucret-guncelle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new WageController();
        $controller->update();
        exit;
    }

    if ($page === '/ucret-sil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new WageController();
        $controller->delete();
        exit;
    }

    if ($page === '/ucret-import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new WageController();
        $controller->import();
        exit;
    }

    if ($page === '/sozlesme-taslagi') {
        $controller = new TemplateController();
        $data = $controller->index();
        extract($data);
        include 'app/pages/sozlesme-taslagi/list.php';
        return;
    }

    if ($page === '/sozlesme-taslagi-kaydet') {
        $controller = new TemplateController();
        $controller->save();
        exit;
    }

    if ($page === '/kullanicilar') {
        $controller = new UserController();
        $data = $controller->list();
        extract($data);
        include 'app/pages/users/list.php';
        return;
    }

    if ($page === '/kullanici-ekle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new UserController();
        $controller->store();
        exit;
    }

    if ($page === '/kullanici-get') {
        $controller = new UserController();
        $controller->get();
        exit;
    }

    if ($page === '/kullanici-guncelle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new UserController();
        $controller->update();
        exit;
    }

    if ($page === '/kullanici-sil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new UserController();
        $controller->delete();
        exit;
    }

    if ($page === '/abonelik') {
        $controller = new SubscriptionController();
        $data = $controller->index();
        extract($data);
        include 'app/pages/subscription/index.php';
        return;
    }

    if ($page === '/abonelik-paket-ekle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SubscriptionController();
        $controller->storePlan();
        exit;
    }

    if ($page === '/abonelik-paket-get') {
        $controller = new SubscriptionController();
        $controller->getPlan();
        exit;
    }

    if ($page === '/abonelik-paket-guncelle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SubscriptionController();
        $controller->updatePlan();
        exit;
    }

    if ($page === '/abonelik-paket-sil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SubscriptionController();
        $controller->deletePlan();
        exit;
    }

    if ($page === '/abonelik-satinal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SubscriptionController();
        $controller->purchase();
        exit;
    }

    if ($page === '/abonelik-onayla' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SubscriptionController();
        $controller->approve();
        exit;
    }

    if ($page === '/abonelik-sil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SubscriptionController();
        $controller->deleteSubscription();
        exit;
    }

    if ($page === '/abonelik-reddet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SubscriptionController();
        $controller->reject();
        exit;
    }

    if ($page === '/kurum-yonetimi') {
        $controller = new SuperadminController();
        $data = $controller->tenants();
        extract($data);
        include 'app/pages/admin/tenants.php';
        return;
    }

    if ($page === '/admin-kurumlar-list') {
        $controller = new SuperadminController();
        $controller->listTenantsJSON();
        exit;
    }

    if ($page === '/admin-kurum-get') {
        $controller = new SuperadminController();
        $controller->getTenant();
        exit;
    }

    if ($page === '/admin-kurum-guncelle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SuperadminController();
        $controller->updateTenant();
        exit;
    }

    if ($page === '/admin-kurum-sil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SuperadminController();
        $controller->deleteTenant();
        exit;
    }

    if ($page === '/profil') {
        $controller = new ProfileController();
        $data = $controller->index();
        extract($data);
        include 'app/pages/profile.php';
        return;
    }

    if ($page === '/profil-guncelle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new ProfileController();
        $controller->update();
        exit;
    }

    if ($page === '/sifre-degistir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new ProfileController();
        $controller->changePassword();
        exit;
    }

    if ($page === '/switch-tenant') {
        $controller = new TenantController();
        $controller->switch();
        exit;
    }

    if ($page === '/kurum-ekle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new TenantController();
        $controller->store();
        exit;
    }

    if ($page === '/hesap-sil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new ProfileController();
        $controller->deleteAccount();
        exit;
    }

    if ($page === '/ayarlar') {
        $controller = new SettingsController();
        $data = $controller->index();
        extract($data);
        include 'app/pages/ayarlar.php';
        return;
    }

    if ($page === '/ayarlar-kaydet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new SettingsController();
        $controller->save();
        exit;
    }

    if ($page === '/onboarding-complete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        global $db;
        $user_id = $_SESSION['user_id'] ?? 0;
        if ($user_id > 0) {
            $stmt = $db->prepare("UPDATE users SET is_onboarded = 1 WHERE id = ?");
            $stmt->execute([$user_id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    renderPage('404');
}

$page = currentRoute();

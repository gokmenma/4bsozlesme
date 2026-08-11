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
    if (substr($basePath, -7) === '/mobile') {
        $basePath = substr($basePath, 0, -7);
    }
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

/**
 * =========================================================================
 *  Merkezi Rota Tablosu (Controller bazlı yönlendirme)
 * =========================================================================
 *  Her giriş için anahtarlar:
 *    'c'    => Controller sınıf adı
 *    'a'    => Çağrılacak metot
 *    'm'    => (ops.) Zorunlu HTTP metodu (örn. 'POST'); eşleşmezse 404
 *    'view' => (ops.) Aksiyon sonrası include edilecek görünüm dosyası (sayfa rotaları)
 *    'api'  => (ops.) AJAX/JSON/indirme uç noktası -> mobil cihazda yönlendirilmez
 *    'exit' => (ops.) Aksiyondan sonra çıkış yapılır (kendi çıktısını üreten uçlar)
 *
 *  Not: Bu tablo hem renderRoute() hem de isStandaloneRoute() tarafından
 *  kullanılır; böylece API uç nokta listesi tek bir yerde tutulur.
 */
function appRoutes(): array
{
    static $routes = null;
    if ($routes !== null) {
        return $routes;
    }

    $routes = [
        // --- Sayfa rotaları (layout ile sarmalanır) ---
        '/'                      => ['c' => 'DashboardController',   'a' => 'index', 'view' => 'app/pages/home.php'],
        '/tanimlamalar'          => ['c' => 'TanimlamalarController', 'a' => 'index', 'view' => 'app/pages/tanimlamalar.php'],
        '/doner-matrahi-olustur' => ['c' => 'DonerMatrahiController', 'a' => 'index', 'view' => 'app/pages/doner-matrahi-olustur.php'],
        '/personel-listesi'      => ['c' => 'PersonnelController',    'a' => 'list',  'view' => 'app/pages/personel/list.php'],
        '/ucret-tanimlari'       => ['c' => 'WageController',         'a' => 'list',  'view' => 'app/pages/ucret-tanimlari/list.php'],
        '/sozlesme-taslagi'      => ['c' => 'TemplateController',     'a' => 'index', 'view' => 'app/pages/sozlesme-taslagi/list.php'],
        '/kullanicilar'          => ['c' => 'UserController',         'a' => 'list',  'view' => 'app/pages/users/list.php'],
        '/abonelik'              => ['c' => 'SubscriptionController',  'a' => 'index', 'view' => 'app/pages/subscription/index.php'],
        '/kurum-yonetimi'        => ['c' => 'SuperadminController',    'a' => 'tenants', 'view' => 'app/pages/admin/tenants.php'],
        '/profil'                => ['c' => 'ProfileController',       'a' => 'index', 'view' => 'app/pages/profile.php'],
        '/ayarlar'               => ['c' => 'SettingsController',      'a' => 'index', 'view' => 'app/pages/ayarlar.php'],
        '/yapilacaklar'          => ['c' => 'KanbanController',        'a' => 'index', 'view' => 'app/pages/kanban.php'],
        '/geri-bildirimler'      => ['c' => 'FeedbackController',      'a' => 'adminIndex', 'view' => 'app/pages/admin/feedbacks.php'],
        '/yetki-gruplari'        => ['c' => 'RoleController',         'a' => 'index', 'view' => 'app/pages/admin/roles.php'],

        // --- Kendi çıktısını üreten sayfa (layout ile sarmalanır, view yok) ---
        '/matrah-yonetimi'       => ['c' => 'MatrahController', 'a' => 'index'],

        // --- İndirme (standalone) ---
        '/doner-matrahi-indir'   => ['c' => 'DonerMatrahiController', 'a' => 'downloadBasis', 'api' => true],

        // --- Personel API ---
        '/personel-datatable'              => ['c' => 'PersonnelController', 'a' => 'fetchDataTable',     'm' => 'POST', 'api' => true, 'exit' => true],
        '/personel-ekle'                   => ['c' => 'PersonnelController', 'a' => 'store',              'm' => 'POST', 'api' => true, 'exit' => true],
        '/personel-get'                    => ['c' => 'PersonnelController', 'a' => 'get',                'api' => true, 'exit' => true],
        '/personel-guncelle'               => ['c' => 'PersonnelController', 'a' => 'update',             'm' => 'POST', 'api' => true, 'exit' => true],
        '/personel-ai-scan'                => ['c' => 'PersonnelController', 'a' => 'aiScan',             'm' => 'POST', 'api' => true, 'exit' => true],
        '/personel-sil'                    => ['c' => 'PersonnelController', 'a' => 'delete',             'm' => 'POST', 'api' => true, 'exit' => true],
        '/personel-ucretsiz-izin-list'     => ['c' => 'PersonnelController', 'a' => 'ucretsizIzinList',  'api' => true, 'exit' => true],
        '/personel-ucretsiz-izin-ekle'     => ['c' => 'PersonnelController', 'a' => 'ucretsizIzinEkle',  'm' => 'POST', 'api' => true, 'exit' => true],
        '/personel-ucretsiz-izin-sil'      => ['c' => 'PersonnelController', 'a' => 'ucretsizIzinSil',   'm' => 'POST', 'api' => true, 'exit' => true],
        '/personel-ucretsiz-izin-guncelle' => ['c' => 'PersonnelController', 'a' => 'ucretsizIzinGuncelle', 'm' => 'POST', 'api' => true, 'exit' => true],
        '/personel-get-preview'            => ['c' => 'PersonnelController', 'a' => 'previewContract',    'api' => true, 'exit' => true],
        '/personel-download-word'          => ['c' => 'PersonnelController', 'a' => 'downloadWord',       'api' => true, 'exit' => true],
        '/personel-import-excel'           => ['c' => 'PersonnelController', 'a' => 'importExcel',        'm' => 'POST', 'api' => true, 'exit' => true],
        '/personel-sample-template'        => ['c' => 'PersonnelController', 'a' => 'downloadSample',     'api' => true, 'exit' => true],

        // --- Ücret API ---
        '/ucret-ekle'           => ['c' => 'WageController', 'a' => 'store',       'm' => 'POST', 'api' => true, 'exit' => true],
        '/ucret-get'            => ['c' => 'WageController', 'a' => 'get',          'exit' => true],
        '/ucret-guncelle'       => ['c' => 'WageController', 'a' => 'update',       'm' => 'POST', 'api' => true, 'exit' => true],
        '/ucret-sil'            => ['c' => 'WageController', 'a' => 'delete',       'm' => 'POST', 'api' => true, 'exit' => true],
        '/ucret-import'         => ['c' => 'WageController', 'a' => 'import',       'm' => 'POST', 'api' => true, 'exit' => true],
        '/ucret-donem-kopyala'  => ['c' => 'WageController', 'a' => 'copyPeriod',   'm' => 'POST', 'api' => true, 'exit' => true],
        '/ucret-donem-sil'      => ['c' => 'WageController', 'a' => 'deletePeriod', 'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Sözleşme taslağı ---
        '/sozlesme-taslagi-kaydet' => ['c' => 'TemplateController', 'a' => 'save', 'exit' => true],

        // --- Kullanıcı API ---
        '/kullanici-ekle'              => ['c' => 'UserController', 'a' => 'store',           'm' => 'POST', 'api' => true, 'exit' => true],
        '/kullanici-get'               => ['c' => 'UserController', 'a' => 'get',             'exit' => true],
        '/kullanici-guncelle'          => ['c' => 'UserController', 'a' => 'update',          'm' => 'POST', 'api' => true, 'exit' => true],
        '/kullanici-sil'               => ['c' => 'UserController', 'a' => 'delete',          'm' => 'POST', 'api' => true, 'exit' => true],
        '/kullanici-kurumlari-getir'   => ['c' => 'UserController', 'a' => 'getUserTenants',  'api' => true, 'exit' => true],
        '/kullanici-kurumlari-kaydet'  => ['c' => 'UserController', 'a' => 'saveUserTenants', 'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Abonelik API ---
        '/abonelik-paket-ekle'     => ['c' => 'SubscriptionController', 'a' => 'storePlan',          'm' => 'POST', 'api' => true, 'exit' => true],
        '/abonelik-paket-get'      => ['c' => 'SubscriptionController', 'a' => 'getPlan',            'exit' => true],
        '/abonelik-paket-guncelle' => ['c' => 'SubscriptionController', 'a' => 'updatePlan',         'm' => 'POST', 'api' => true, 'exit' => true],
        '/abonelik-paket-sil'      => ['c' => 'SubscriptionController', 'a' => 'deletePlan',         'm' => 'POST', 'api' => true, 'exit' => true],
        '/abonelik-satinal'        => ['c' => 'SubscriptionController', 'a' => 'purchase',           'm' => 'POST', 'api' => true, 'exit' => true],
        '/abonelik-onayla'         => ['c' => 'SubscriptionController', 'a' => 'approve',            'm' => 'POST', 'api' => true, 'exit' => true],
        '/abonelik-sil'            => ['c' => 'SubscriptionController', 'a' => 'deleteSubscription', 'm' => 'POST', 'api' => true, 'exit' => true],
        '/abonelik-reddet'         => ['c' => 'SubscriptionController', 'a' => 'reject',             'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Süperadmin / Kurum API ---
        '/kurum-listesi-json'         => ['c' => 'SuperadminController', 'a' => 'listTenantsJSON',  'api' => true, 'exit' => true],
        '/kurum-getir-json'           => ['c' => 'SuperadminController', 'a' => 'getTenant',        'api' => true, 'exit' => true],
        '/kurum-guncelle-json'        => ['c' => 'SuperadminController', 'a' => 'updateTenant',     'm' => 'POST', 'api' => true, 'exit' => true],
        '/kurum-sil-json'             => ['c' => 'SuperadminController', 'a' => 'deleteTenant',     'm' => 'POST', 'api' => true, 'exit' => true],
        '/kurum-kullanicilari-getir'  => ['c' => 'SuperadminController', 'a' => 'getTenantUsers',   'api' => true, 'exit' => true],
        '/kurum-kullanicilari-kaydet' => ['c' => 'SuperadminController', 'a' => 'saveTenantUsers',  'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Profil API ---
        '/profil-guncelle'     => ['c' => 'ProfileController', 'a' => 'update',             'm' => 'POST', 'api' => true, 'exit' => true],
        '/profil-sms-guncelle' => ['c' => 'ProfileController', 'a' => 'updateSmsSettings',  'm' => 'POST', 'api' => true, 'exit' => true],
        '/profil-sms-test'     => ['c' => 'ProfileController', 'a' => 'sendTestSms',        'm' => 'POST', 'api' => true, 'exit' => true],
        '/profil-bildirim-guncelle' => ['c' => 'ProfileController', 'a' => 'updateNotificationSettings',  'm' => 'POST', 'api' => true, 'exit' => true],
        '/sifre-degistir'      => ['c' => 'ProfileController', 'a' => 'changePassword',     'm' => 'POST', 'api' => true, 'exit' => true],
        '/hesap-sil'           => ['c' => 'ProfileController', 'a' => 'deleteAccount',      'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Kurum (tenant) ---
        '/switch-tenant' => ['c' => 'TenantController', 'a' => 'switch', 'exit' => true],
        '/kurum-ekle'    => ['c' => 'TenantController', 'a' => 'store',  'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Ayarlar API ---
        '/ayarlar-kaydet'    => ['c' => 'SettingsController', 'a' => 'save',          'm' => 'POST', 'api' => true, 'exit' => true],
        '/ayarlar-test-mail' => ['c' => 'SettingsController', 'a' => 'sendTestMail',  'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Kanban API ---
        '/kanban-gorev-ekle'           => ['c' => 'KanbanController', 'a' => 'store',            'm' => 'POST', 'api' => true, 'exit' => true],
        '/kanban-gorev-guncelle'       => ['c' => 'KanbanController', 'a' => 'update',           'm' => 'POST', 'api' => true, 'exit' => true],
        '/kanban-gorev-sil'            => ['c' => 'KanbanController', 'a' => 'delete',           'm' => 'POST', 'api' => true, 'exit' => true],
        '/kanban-gorev-durum-guncelle' => ['c' => 'KanbanController', 'a' => 'updateStatus',     'm' => 'POST', 'api' => true, 'exit' => true],
        '/kanban-board-ekle'           => ['c' => 'KanbanController', 'a' => 'addBoard',         'm' => 'POST', 'api' => true, 'exit' => true],
        '/kanban-board-sil'            => ['c' => 'KanbanController', 'a' => 'deleteBoard',      'm' => 'POST', 'api' => true, 'exit' => true],
        '/kanban-board-sira-guncelle'  => ['c' => 'KanbanController', 'a' => 'updateBoardOrder', 'm' => 'POST', 'api' => true, 'exit' => true],
        '/kanban-board-baslik-guncelle' => ['c' => 'KanbanController', 'a' => 'updateBoardTitle', 'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Geri Bildirim API ---
        '/geri-bildirim-gonder'        => ['c' => 'FeedbackController', 'a' => 'store',    'm' => 'POST', 'api' => true, 'exit' => true],
        '/geri-bildirim-guncelle'      => ['c' => 'FeedbackController', 'a' => 'update',   'm' => 'POST', 'api' => true, 'exit' => true],
        '/geri-bildirim-sil'           => ['c' => 'FeedbackController', 'a' => 'delete',   'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Bildirim API ---
        '/bildirim-okundu'             => ['c' => 'NotificationController', 'a' => 'markRead',    'm' => 'POST', 'api' => true, 'exit' => true],
        '/bildirim-tumu-okundu'        => ['c' => 'NotificationController', 'a' => 'markAllRead', 'm' => 'POST', 'api' => true, 'exit' => true],

        // --- Yetki Grupları API ---
        '/yetki-grubu-ekle'            => ['c' => 'RoleController', 'a' => 'store',            'm' => 'POST', 'api' => true, 'exit' => true],
        '/yetki-grubu-guncelle'        => ['c' => 'RoleController', 'a' => 'update',           'm' => 'POST', 'api' => true, 'exit' => true],
        '/yetki-grubu-sil'             => ['c' => 'RoleController', 'a' => 'delete',           'm' => 'POST', 'api' => true, 'exit' => true],
        '/yetki-izinleri-getir'        => ['c' => 'RoleController', 'a' => 'getPermissions',   'api' => true, 'exit' => true],
        '/yetki-izinleri-kaydet'       => ['c' => 'RoleController', 'a' => 'savePermissions',  'm' => 'POST', 'api' => true, 'exit' => true],
    ];

    return $routes;
}

function isStandaloneRoute(string $page): bool
{
    if (in_array($page, ['/login', '/logout', '/register'], true)) {
        return true;
    }
    if (strpos($page, '/mobile') === 0) {
        return true;
    }

    // Tablo dışında özel ele alınan standalone uçlar (indirme / inline API)
    if (in_array($page, ['/belge-yazdir', '/onboarding-complete'], true)) {
        return true;
    }

    // Rota tablosunda 'api' işaretli AJAX/JSON/indirme uçları
    $routes = appRoutes();
    return isset($routes[$page]) && !empty($routes[$page]['api']);
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

    if (!empty($_SESSION['user_id'])) {
        try {
            $userModel = new User();
            $userModel->updateRememberToken($_SESSION['user_id'], null);
        } catch (Exception $e) {
            error_log("Logout remember_token error: " . $e->getMessage());
        }
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

    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true
    ]);

    session_destroy();
}

/**
 * Mobil PWA varlıkları ve sayfaları için özel include'lar.
 * Eşleşme olursa çıktıyı üretip exit eder, yoksa false döner.
 */
function renderMobileRoute(string $page): bool
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

    if ($page === '/mobile' || $page === '/mobile/' || $page === '/mobile/index.php') {
        include 'mobile/index.php';
        exit;
    }

    // mobile/pages/** altındaki bilinen sayfalar
    $mobilePages = [
        '/mobile/pages/home/index.php',
        '/mobile/pages/personel/index.php',
        '/mobile/pages/ucretler/index.php',
        '/mobile/pages/profil/index.php',
        '/mobile/pages/kanban/index.php',
        '/mobile/pages/other/index.php',
        '/mobile/pages/other/tenants.php',
        '/mobile/pages/other/users.php',
        '/mobile/pages/other/subscription.php',
        '/mobile/pages/other/template.php',
        '/mobile/pages/other/settings.php',
        '/mobile/pages/other/tanimlamalar.php',
    ];
    if (in_array($page, $mobilePages, true)) {
        include ltrim($page, '/');
        exit;
    }

    return false;
}

function renderRoute(string $page): void
{
    // --- Merkezi CSRF koruması: tüm değiştirici (POST) istekleri doğrula ---
    // login/register kendi standalone akışlarına sahiptir ve hariç tutulur.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
        && !in_array($page, ['/login', '/register'], true)) {
        csrf_verify();
    }

    // --- Mobil PWA rotaları ---
    if (renderMobileRoute($page)) {
        return;
    }

    // --- Belge yazdırma (standalone indirme) ---
    if ($page === '/belge-yazdir') {
        $controller = new PersonnelController();
        $controller->printDocument();
        exit;
    }

    // --- Kimlik / oturum sayfaları ---
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
        $logoutRedirect = ($_GET['context'] ?? '') === 'mobile'
            ? routeUrl('/mobile')
            : routeUrl('/login');
        header('Location: ' . $logoutRedirect);
        exit;
    }

    // --- Onboarding tamamlama (inline) ---
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

    // --- Tablo bazlı controller yönlendirmesi ---
    $routes = appRoutes();
    if (isset($routes[$page])) {
        $route = $routes[$page];

        // HTTP metot kısıtı
        if (isset($route['m']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $route['m']) {
            renderPage('404');
            return;
        }

        $controller = new $route['c']();
        $result = $controller->{$route['a']}();

        // Görünüm dosyası belirtilmişse veriyi aktarıp include et
        if (!empty($route['view'])) {
            if (is_array($result)) {
                extract($result);
            }
            include $route['view'];
            return;
        }

        // Kendi çıktısını üreten uçlar
        if (!empty($route['exit'])) {
            exit;
        }
        return;
    }

    renderPage('404');
}

$page = currentRoute();

<?php 

// .env dosyası yükleme (Çevre değişkenleri)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantısını dahil et
require_once __DIR__ . '/app/core/db.php';
require_once __DIR__ . '/app/core/Helpers.php';

// Autoloader: app/core ve app/models altındaki sınıfları otomatik yükler
spl_autoload_register(function ($class_name) {
    $dirs = [
        'app/core/',
        'app/models/',
        'app/controllers/',
        'app/Service/'
    ];
    
    foreach ($dirs as $dir) {
        $file = __DIR__ . '/' . $dir . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// PHP-based mobile/tablet redirection for real devices
$isPostRequest = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$isAjaxRequest = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if (isset($page) && $page !== '/mobile' && !isStandaloneRoute($page) && !$isPostRequest && !$isAjaxRequest) {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
        header("Location: " . routeUrl('/mobile'));
        exit;
    }
}

// Beni Hatırla (Remember Me) çerezi kontrolü
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $parts = explode(':', $_COOKIE['remember_token'], 2);
    if (count($parts) === 2) {
        $rUserId = (int)$parts[0];
        $rToken = $parts[1];
        if ($rUserId > 0 && !empty($rToken)) {
            $userModel = new User();
            $user = $userModel->findByRememberToken($rUserId, $rToken);
            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_username'] = !empty($user['username']) ? $user['username'] : explode('@', $user['email'])[0];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['role_id'] = $user['role_id'] ?? null;
                $_SESSION['tenant_id'] = $user['tenant_id'];
            }
        }
    }
}

// Eğer kullanıcı giriş yapmamışsa
if (!isset($_SESSION['user_id'])) {
    if (isset($page) && PHP_SAPI !== 'cli') {
        if (!isStandaloneRoute($page)) {
            // Standart sayfalar için çıkışa yönlendir
            header("Location: " . routeUrl('/logout'));
            exit;
        } else {
            // Mobil ve API sayfaları için izin verilenler dışındakilere 401 döndür
            $allowedStandalone = [
                '/login',
                '/logout',
                '/register',
                '/mobile',
                '/mobile/',
                '/mobile/index.php',
                '/mobile/manifest.json',
                '/mobile/sw.js',
                '/belge-yazdir'
            ];
            if (!in_array($page, $allowedStandalone, true)) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'unauthorized',
                    'message' => 'Oturumunuz sonlanmıştır. Lütfen tekrar giriş yapın.'
                ]);
                exit;
            }
        }
    }
} else {
    // Kullanıcı zaten giriş yapmışsa ve login/register sayfasına gitmeye çalışıyorsa ana sayfaya yönlendir.
    if (isset($page) && in_array($page, ['/login', '/register'])) {
        header("Location: " . routeUrl('/'));
        exit;
    }
}

// Active Subscription & Trial Enforcement and Role Page Permissions
if (isset($_SESSION['user_id']) && isset($page)) {
    $uStmt = $db->prepare("SELECT role, role_id, tenant_id, trial_ends_at FROM users WHERE id = ?");
    $uStmt->execute([$_SESSION['user_id']]);
    $uData = $uStmt->fetch();

    if ($uData) {
        $userRole = $uData['role'] ?? 'user';
        $userRoleId = $uData['role_id'] ?? null;
        $_SESSION['role_id'] = $userRoleId;
        
        // Superadmin and allowed routes are NEVER blocked
        $allowedPages = ['/profil', '/logout', '/abonelik-satinal', '/abonelik-sil', '/abonelik-reddet', '/profil-guncelle', '/sifre-degistir'];
        if ($userRole !== 'superadmin' && !in_array($page, $allowedPages) && !isStandaloneRoute($page)) {
            // Check if trial is active
            $trialValid = false;
            if (!empty($uData['trial_ends_at'])) {
                $trialValid = (strtotime($uData['trial_ends_at']) >= strtotime(date('Y-m-d')));
            }
            
            // Check if there is an active subscription
            $subValid = false;
            if (!empty($uData['tenant_id'])) {
                $subStmt = $db->prepare("SELECT id FROM subscriptions WHERE tenant_id = ? AND status = 'active' AND end_date >= ? LIMIT 1");
                $subStmt->execute([$uData['tenant_id'], date('Y-m-d')]);
                $subValid = (bool)$subStmt->fetch();
            }
            
            // Block if both are invalid
            if (!$trialValid && !$subValid) {
                // If it is an AJAX request, return a JSON error
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Sistemi kullanmaya devam edebilmek için aktif bir aboneliğinizin veya deneme sürenizin olması gerekmektedir. Lütfen profil sayfanızdan abonelik paketi satın alın.'
                    ]);
                    exit;
                }
                
                // Set flash warning message
                $_SESSION['subscription_error'] = 'Sistemi kullanabilmek için aktif bir aboneliğinizin veya deneme sürenizin olması gerekmektedir. Lütfen aşağıdaki paketlerden birini seçerek aboneliğinizi başlatın.';
                
                header("Location: " . routeUrl('/profil'));
                exit;
            }

            // --- Sayfa Bazlı Yetki Kontrolü (Role Permission Enforcement) ---
            $rolePermissionModel = new RolePermission();
            if (!$rolePermissionModel->hasAccess($userRoleId, $page, $userRole)) {
                if ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') || ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'error' => 'forbidden',
                        'message' => 'Bu sayfaya veya işleme erişim yetkiniz bulunmamaktadır.'
                    ]);
                    exit;
                }

                $_SESSION['permission_error'] = 'Bu sayfaya erişim yetkiniz bulunmamaktadır.';
                header("Location: " . routeUrl('/'));
                exit;
            }
        }
    }
}
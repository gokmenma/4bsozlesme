<?php 

session_start();

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

// Eğer kullanıcı giriş yapmamışsa ve login/logout sayfalarında değilse, logout sayfasına yönlendir.
// logout sayfası oturumu temizleyip login sayfasına atacaktır.
if(!isset($_SESSION['user_id'])){
    if (isset($page) && !isStandaloneRoute($page)) {
        header("Location: " . routeUrl('/logout'));
        exit;
    }
} else {
    // Kullanıcı zaten giriş yapmışsa ve login/register sayfasına gitmeye çalışıyorsa ana sayfaya yönlendir.
    if (isset($page) && in_array($page, ['/login', '/register'])) {
        header("Location: " . routeUrl('/'));
        exit;
    }
}

// Active Subscription & Trial Enforcement
if (isset($_SESSION['user_id']) && isset($page)) {
    $uStmt = $db->prepare("SELECT role, tenant_id, trial_ends_at FROM users WHERE id = ?");
    $uStmt->execute([$_SESSION['user_id']]);
    $uData = $uStmt->fetch();

    if ($uData) {
        $userRole = $uData['role'] ?? 'user';
        
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
        }
    }
}
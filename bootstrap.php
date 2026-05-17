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
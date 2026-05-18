<?php

/**
 * Veritabanı Bağlantı Yapılandırması
 * PDO (PHP Data Objects) kullanılarak güvenli bağlantı sağlanır.
 */

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'sozlesme';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';


// $dbname = 'mbeyazil_sozlesme';
// $username = 'mbeyazil_4bsozlesme';
// $password = 'h?]C=qC9qLJ.Cv{C'; // XAMPP varsayılanı boştur





$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Global olarak erişilebilecek bağlantı değişkeni
    $db = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Hata durumunda mesaj göster ve durdur
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

<?php

$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

if ($host === false || $host === '' || $dbname === false || $dbname === '' || $username === false || $username === '' || $password === false) {
    error_log('Veritabanı yapılandırması eksik: .env içinde DB_HOST, DB_NAME, DB_USER, DB_PASS tanımlı olmalı.');
    http_response_code(500);
    die('Sistem yapılandırma hatası oluştu.');
}

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $db = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    error_log('Veritabanı bağlantı hatası: ' . $e->getMessage());
    http_response_code(500);
    die('Veritabanı bağlantısı kurulamadı.');
}

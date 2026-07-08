<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_SESSION['tenant_id'] = 1;
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'superadmin';
$_POST = [
    'default_wage_period' => '2026-1',
    'kurum_adi' => 'Düzce Üniversitesi',
    'birim_adi' => 'Sağlık Uygulama ve Araştırma Merkezi',
    'yetkili_ad_soyad' => 'Prof.Dr.Nedim Sözbir',
    'yetkili_unvan' => 'Rektör',
    'maas_katsayisi' => '1.387871',
    'yan_odeme_katsayisi' => '0.440141'
];

require_once __DIR__ . '/bootstrap.php';

$controller = new TanimlamalarController();
$controller->index();



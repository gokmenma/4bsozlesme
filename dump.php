<?php
require_once 'bootstrap.php';
require_once 'app/models/Template.php';

session_start();
$_SESSION['tenant_id'] = 1; // Assuming 1 for test

$t = new Template();
$res = $t->getLatest();
if ($res) {
    file_put_contents('scratch/content_dump.html', $res['content']);
    echo "Dumped " . strlen($res['content']) . " bytes.";
} else {
    echo "No template found.";
}

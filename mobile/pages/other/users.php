<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../bootstrap.php';
global $db;

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

// Fetch users
$tenant_id = $_SESSION['tenant_id'] ?? 0;
$uStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$uStmt->execute([$_SESSION['user_id']]);
$userRole = $uStmt->fetchColumn() ?: 'user';

if ($userRole === 'superadmin') {
    // Superadmin sees all users
    $stmt = $db->query("SELECT u.*, t.name as tenant_name FROM users u LEFT JOIN tenants t ON u.tenant_id = t.id ORDER BY u.name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Normal admin sees users of their tenant
    $stmt = $db->prepare("SELECT u.*, t.name as tenant_name FROM users u LEFT JOIN tenants t ON u.tenant_id = t.id WHERE u.tenant_id = ? ORDER BY u.name ASC");
    $stmt->execute([$tenant_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="space-y-4 animate-fade-in">
    <div class="flex items-center gap-2">
        <button onclick="goBackToOtherMenu()" class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div>
            <h2 class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50">Kullanıcı Yönetimi</h2>
            <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Toplam <?= count($users) ?> Kullanıcı</p>
        </div>
    </div>

    <!-- Users Cards -->
    <div class="space-y-3">
        <?php foreach ($users as $u): ?>
            <div class="glass-card p-4 rounded-xl flex items-center justify-between">
                <div class="space-y-1">
                    <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100"><?= htmlspecialchars($u['name']) ?></h4>
                    <p class="text-[9px] text-zinc-400 font-semibold"><?= htmlspecialchars($u['email']) ?></p>
                    <p class="text-[8px] text-zinc-500 dark:text-zinc-400 uppercase tracking-wide font-bold">Kurum: <?= htmlspecialchars($u['tenant_name'] ?? 'Genel Yönetim') ?></p>
                </div>
                <div class="text-right shrink-0">
                    <span class="bg-indigo-50 dark:bg-zinc-900 border border-indigo-100 dark:border-zinc-800 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider"><?= htmlspecialchars($u['role']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

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

// Fetch all tenants
$stmt = $db->query("SELECT * FROM tenants ORDER BY name ASC");
$tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="space-y-4 animate-fade-in">
    <div class="flex items-center gap-2">
        <button onclick="goBackToOtherMenu()" class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div>
            <h2 class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50">Kurum Yönetimi</h2>
            <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Toplam <?= count($tenants) ?> Kurum</p>
        </div>
    </div>

    <!-- Tenants Cards -->
    <div class="space-y-3">
        <?php foreach ($tenants as $t): ?>
            <div class="glass-card p-4 rounded-xl flex items-center justify-between border-l-2 border-zinc-800 dark:border-zinc-200">
                <div class="space-y-1">
                    <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100"><?= htmlspecialchars($t['name']) ?></h4>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold">Domain: <?= htmlspecialchars($t['domain'] ?? '-') ?></p>
                </div>
                <div class="text-right shrink-0">
                    <span class="badge-aktif px-2 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider">Aktif</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

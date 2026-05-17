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

$tenant_id = $_SESSION['tenant_id'] ?? 0;

$stmt_ucret = $db->prepare("SELECT id, unvan, ucret, ogrenim, kidem_yili FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? ORDER BY unvan ASC");
$stmt_ucret->execute([$tenant_id]);
$ucretler = $stmt_ucret->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="space-y-4 animate-fade-in">
    <div>
        <h2 class="text-lg font-extrabold text-zinc-950 dark:text-zinc-50">Ücret Tanımları</h2>
        <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider">Unvan, Öğrenim & Kıdem Matrahları</p>
    </div>

    <!-- Search Definition -->
    <div class="relative flex items-center">
        <div class="absolute left-4 text-zinc-500">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </div>
        <input id="definitionSearch" type="text" class="mobile-input pl-11" placeholder="Unvan veya öğrenim ara..." onkeyup="filterDefinitionsList()">
    </div>

    <!-- Definitions List -->
    <div id="definitions-list-wrapper" class="space-y-3">
        <?php if (empty($ucretler)): ?>
            <div class="glass-card p-10 rounded-lg text-center space-y-2">
                <p class="text-xs font-bold text-zinc-400">Kurumunuzda henüz ücret tanımı bulunmamaktadır.</p>
            </div>
        <?php else: ?>
            <?php foreach ($ucretler as $u): ?>
                <div class="glass-card p-4 rounded-lg flex items-center justify-between definition-item-card"
                     data-unvan="<?= htmlspecialchars($u['unvan']) ?>"
                     data-ogrenim="<?= htmlspecialchars($u['ogrenim']) ?>">
                    <div class="space-y-1">
                        <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200 leading-tight"><?= htmlspecialchars($u['unvan']) ?></h4>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[9px] bg-zinc-100 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-800 px-2 py-0.5 rounded font-bold uppercase"><?= htmlspecialchars($u['ogrenim']) ?></span>
                            <span class="text-[9px] bg-indigo-50 dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-zinc-800 px-2 py-0.5 rounded font-bold uppercase leading-none"><?= htmlspecialchars($u['kidem_yili']) ?></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-extrabold text-zinc-900 dark:text-zinc-100 block"><?= number_format($u['ucret'] ?? 0, 2, ',', '.') ?> TL</span>
                        <span class="text-[9px] text-zinc-500 font-semibold block mt-0.5">Brüt Matrah</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

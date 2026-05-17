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

$stmt_tenants = $db->prepare("SELECT t.id, t.name FROM tenants t INNER JOIN user_tenants ut ON t.id = ut.tenant_id WHERE ut.user_id = ?");
$stmt_tenants->execute([$_SESSION['user_id']]);
$tenants = $stmt_tenants->fetchAll(PDO::FETCH_ASSOC);

$uStmt = $db->prepare("SELECT role, trial_ends_at FROM users WHERE id = ?");
$uStmt->execute([$_SESSION['user_id']]);
$uData = $uStmt->fetch();
$userRole = $uData['role'] ?? 'user';

$trialDaysLeft = 0;
if (!empty($uData['trial_ends_at'])) {
    $trialDaysLeft = ceil((strtotime($uData['trial_ends_at']) - strtotime(date('Y-m-d'))) / 86400);
    if ($trialDaysLeft < 0) $trialDaysLeft = 0;
}
?>
<div class="space-y-6 animate-fade-in">
    <!-- Profile Header card -->
    <div class="glass-card p-6 rounded-lg text-center relative overflow-hidden">
        <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center font-bold text-2xl text-zinc-900 dark:text-zinc-100 uppercase mx-auto mb-3 shadow-sm">
            <?= mb_substr($_SESSION['user_name'], 0, 2) ?>
        </div>
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 leading-tight"><?= htmlspecialchars($_SESSION['user_name']) ?></h3>
        <p class="text-xs text-zinc-400 font-medium mt-0.5"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
        <span class="badge-kadro px-3 py-1 rounded text-[9px] font-bold uppercase tracking-wider inline-block mt-3"><?= htmlspecialchars($userRole) ?></span>
    </div>

    <!-- Tenant Selector -->
    <div class="glass-card p-5 rounded-lg space-y-3">
        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Aktif Kurum Değiştir</h4>
        <div class="space-y-2">
            <?php foreach ($tenants as $t): 
                $isActiveTenant = ($t['id'] == $tenant_id);
                $activeBorder = $isActiveTenant ? 'border-zinc-400 bg-zinc-100 dark:bg-zinc-900/50' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/20';
            ?>
                <a href="<?= routeUrl('/switch-tenant?id=' . $t['id']) ?>" 
                   class="w-full p-3 rounded border <?= $activeBorder ?> flex items-center justify-between active:scale-98 transition-all hover:bg-zinc-100 dark:hover:bg-zinc-900 block text-left">
                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($t['name']) ?></span>
                    <?php if ($isActiveTenant): ?>
                        <span class="w-2 h-2 bg-zinc-950 dark:bg-zinc-100 rounded-full"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Subscription Card -->
    <?php if ($userRole !== 'superadmin'): ?>
        <div class="glass-card p-5 rounded-lg space-y-3">
            <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Abonelik Detayları</h4>
            <div class="p-4 bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-md space-y-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="font-bold text-zinc-400">Durum:</span>
                    <span class="text-emerald-400 font-extrabold uppercase">Aktif Lisans</span>
                </div>
                <?php if ($trialDaysLeft > 0): ?>
                    <div class="flex justify-between items-center text-xs pt-1.5 border-t border-zinc-800">
                        <span class="font-bold text-zinc-400">Kalan Deneme Süresi:</span>
                        <span class="text-zinc-200 font-extrabold"><?= $trialDaysLeft ?> Gün</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="space-y-3">
        <a href="<?= routeUrl('/logout') ?>" class="w-full py-4 bg-red-50 dark:bg-zinc-900 border border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-zinc-800 active:scale-98 transition-all rounded-md text-center font-bold text-xs flex items-center justify-center gap-2 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m11 9H9m7-4 4 4-4 4"/></svg>
            Güvenli Çıkış Yap
        </a>
    </div>

    <div class="text-center text-[10px] text-zinc-500 font-semibold uppercase tracking-widest pt-2">
        Sözleşme 4B Mobil v1.0.0
    </div>
</div>

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

// Fetch tenant details
$tStmt = $db->prepare("SELECT * FROM tenants WHERE id = ?");
$tStmt->execute([$tenant_id]);
$tenant = $tStmt->fetch();

// Fetch trial details
$uStmt = $db->prepare("SELECT trial_ends_at FROM users WHERE id = ?");
$uStmt->execute([$_SESSION['user_id']]);
$trial_ends_at = $uStmt->fetchColumn();

$trialDaysLeft = 0;
if (!empty($trial_ends_at)) {
    $trialDaysLeft = ceil((strtotime($trial_ends_at) - strtotime(date('Y-m-d'))) / 86400);
    if ($trialDaysLeft < 0) $trialDaysLeft = 0;
}
?>
<div class="space-y-4 animate-fade-in">
    <div class="flex items-center gap-2">
        <button onclick="goBackToOtherMenu()" class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div>
            <h2 class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50">Abonelik & Paketler</h2>
            <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Lisans & Limit Durumu</p>
        </div>
    </div>

    <!-- Active Subscription Details -->
    <div class="glass-card p-6 rounded-xl space-y-4">
        <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
            <div>
                <span class="text-[9px] text-zinc-400 font-bold uppercase tracking-wider block">Mevcut Plan</span>
                <span class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50 block mt-0.5">Ücretsiz Deneme Sürümü</span>
            </div>
            <span class="badge-aktif px-2.5 py-1 rounded text-[8px] font-extrabold uppercase tracking-wider shrink-0">Aktif</span>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-lg border border-zinc-200/50 dark:border-zinc-800/80">
                <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Kalan Süre</span>
                <span class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 block mt-1"><?= $trialDaysLeft ?> Gün</span>
            </div>
            
            <div class="bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-lg border border-zinc-200/50 dark:border-zinc-800/80">
                <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Kullanıcı Limiti</span>
                <span class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 block mt-1">Sınırsız</span>
            </div>
        </div>

        <div class="bg-indigo-50/50 dark:bg-zinc-900/30 border border-indigo-100/50 dark:border-zinc-800/50 p-4 rounded-lg">
            <p class="text-[10px] text-zinc-600 dark:text-zinc-400 font-semibold leading-relaxed">
                Tüm kurumsal özellikleriniz ve matrah hesaplama modülleriniz deneme süreniz boyunca tam yetki ile aktiftir. Sorularınız için destek merkezimize ulaşabilirsiniz.
            </p>
        </div>
    </div>
</div>

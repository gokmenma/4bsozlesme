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

$dashboardController = new DashboardController();
$dashboardData = $dashboardController->index();
$stats = $dashboardData['stats'];
$recentPersonnel = $dashboardData['recent_personnel'];
$eligiblePersonnel = $dashboardData['eligible_personnel'];

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
    <!-- Greeting Card -->
    <div class="p-6 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 relative overflow-hidden">
        <div class="absolute w-36 h-36 bg-zinc-500/5 rounded-full blur-3xl -top-12 -right-12"></div>
        <h2 class="text-xl font-extrabold text-zinc-950 dark:text-white mb-1">Merhaba, <?= explode(' ', $_SESSION['user_name'])[0] ?> 👋</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Sözleşme 4B Mobil Portala hoş geldiniz. İşte bugün kurumunuzdaki son durum:</p>
        
        <?php if ($trialDaysLeft > 0 && $userRole !== 'superadmin'): ?>
            <div class="mt-4 flex items-center justify-between bg-zinc-200/50 dark:bg-zinc-950 border border-zinc-300/50 dark:border-zinc-800 rounded-lg p-3">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-500 dark:text-zinc-400" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Deneme Süreniz</span>
                </div>
                <span class="text-xs font-bold text-zinc-600 dark:text-zinc-400"><?= $trialDaysLeft ?> Gün Kaldı</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- KPI Stats 2x2 Grid -->
    <div class="grid grid-cols-2 gap-4">
        <div class="glass-card p-4 rounded-xl flex flex-col justify-between h-28 relative">
            <div class="w-8 h-8 rounded-md bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-400">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8m13 10v-2a4 4 0 0 0-3-3.87m-4-12a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <span class="text-[0.72rem] font-bold text-zinc-400 uppercase tracking-wider block">Toplam Personel</span>
                <span class="text-2xl font-extrabold text-zinc-950 dark:text-zinc-50 leading-none mt-1 block"><?= $stats['total_personnel'] ?></span>
            </div>
        </div>
        
        <div class="glass-card p-4 rounded-xl flex flex-col justify-between h-28 relative">
            <div class="w-8 h-8 rounded-md bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-emerald-500 dark:text-emerald-400">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div>
                <span class="text-[0.72rem] font-bold text-zinc-400 uppercase tracking-wider block">Aktif Çalışan</span>
                <span class="text-2xl font-extrabold text-zinc-950 dark:text-zinc-50 leading-none mt-1 block"><?= $stats['active_personnel'] ?></span>
             </div>
             <div class="absolute top-4 right-4 w-2 h-2 bg-emerald-500 rounded-full animate-ping"></div>
        </div>

        <div class="glass-card p-4 rounded-xl flex flex-col justify-between h-28">
            <div class="w-8 h-8 rounded-md bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-sky-500 dark:text-sky-400">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8"/></svg>
            </div>
            <div>
                <span class="text-[0.72rem] font-bold text-zinc-400 uppercase tracking-wider block">Bu Ay Eklenen</span>
                <span class="text-2xl font-extrabold text-zinc-950 dark:text-zinc-50 leading-none mt-1 block"><?= $stats['new_personnel_this_month'] ?></span>
            </div>
        </div>

        <div class="glass-card p-4 rounded-xl flex flex-col justify-between h-28">
            <div class="w-8 h-8 rounded-md bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-amber-500 dark:text-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            </div>
            <div>
                <span class="text-[0.72rem] font-bold text-zinc-400 uppercase tracking-wider block">Üret Tanımları</span>
                <span class="text-2xl font-extrabold text-zinc-950 dark:text-zinc-50 leading-none mt-1 block"><?= $stats['total_wages'] ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Action Panel -->
    <div class="space-y-3">
        <h3 class="text-sm font-bold text-zinc-400 uppercase tracking-wider">Hızlı İşlemler</h3>
        <div class="grid grid-cols-2 gap-3">
            <button onclick="switchTab('personnel', () => openEkleSheet())" class="glass-card p-4 rounded-lg flex items-center gap-3 cursor-pointer text-left w-full">
                <div class="w-9 h-9 rounded-md bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-indigo-500 dark:text-indigo-400 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14m-7-7v14"/></svg>
                </div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Personel Ekle</span>
                    <span class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold block leading-tight mt-0.5 truncate">Hızlı ve kolay form</span>
                </div>
            </button>

            <button onclick="openImportSheet()" class="glass-card p-4 rounded-lg flex items-center gap-3 cursor-pointer text-left w-full">
                <div class="w-9 h-9 rounded-md bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-emerald-500 dark:text-emerald-400 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5-5 5 5m-5-5v12"/></svg>
                </div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Excel Yükle</span>
                    <span class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold block leading-tight mt-0.5 truncate">Toplu yükleme</span>
                </div>
            </button>
        </div>
    </div>

    <!-- Tenure approaching warning panel -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-zinc-400 uppercase tracking-wider">Kadro Hakkı Gelenler (3 Yıl)</h3>
            <?php if (!empty($eligiblePersonnel)): ?>
                <button onclick="viewAllEligible()" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline active:scale-95 transition-all cursor-pointer">Tümünü Gör</button>
            <?php endif; ?>
        </div>
        <div class="space-y-3">
            <?php if (empty($eligiblePersonnel)): ?>
                <div class="glass-card p-5 rounded-lg text-center space-y-2">
                    <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center mx-auto text-zinc-500 dark:text-zinc-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    </div>
                    <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400">Yaklaşan kadro geçişi bulunmamaktadır.</p>
                </div>
            <?php else: ?>
                <?php foreach ($eligiblePersonnel as $p): ?>
                    <div class="glass-card p-4 rounded-lg flex items-center justify-between border-l-2 border-emerald-500 bg-zinc-50 dark:bg-zinc-900/50">
                        <div class="space-y-1">
                             <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($p['ad_soyad']) ?></h4>
                             <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($p['unvan'] ?? 'Unvansız') ?></p>
                        </div>
                        <div class="text-right">
                             <span class="badge-aktif px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider block">Kadro: <?= date('d.m.Y', strtotime($p['goreve_baslama_tarihi'] . ' +3 years')) ?></span>
                             <span class="text-[9px] font-semibold text-zinc-500 dark:text-zinc-400 block mt-1">Başlama: <?= date('d.m.Y', strtotime($p['goreve_baslama_tarihi'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

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

// Fetch user role & trial status
$uStmt = $db->prepare("SELECT role, trial_ends_at FROM users WHERE id = ?");
$uStmt->execute([$_SESSION['user_id']]);
$uData = $uStmt->fetch();
$userRole = $uData['role'] ?? 'user';
$isSuperAdmin = ($userRole === 'superadmin');

$trialDaysLeft = 0;
if (!empty($uData['trial_ends_at'])) {
    $trialDaysLeft = ceil((strtotime($uData['trial_ends_at']) - strtotime(date('Y-m-d'))) / 86400);
    if ($trialDaysLeft < 0) $trialDaysLeft = 0;
}

// Fetch active/latest subscription for this tenant
$subStmt = $db->prepare("SELECT s.*, sp.name as plan_name 
                        FROM subscriptions s 
                        JOIN subscription_plans sp ON s.plan_id = sp.id 
                        WHERE s.tenant_id = ? 
                        ORDER BY s.end_date DESC LIMIT 1");
$subStmt->execute([$tenant_id]);
$currentSub = $subStmt->fetch(PDO::FETCH_ASSOC);

// Fetch subscription plans
$stmt = $db->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch transaction/purchase history (tenant specific unless superadmin)
$historyQuery = "SELECT s.*, sp.name as plan_name, t.name as tenant_name, u.name as user_name 
                FROM subscriptions s 
                JOIN subscription_plans sp ON s.plan_id = sp.id 
                LEFT JOIN tenants t ON s.tenant_id = t.id
                LEFT JOIN users u ON s.user_id = u.id";

if (!$isSuperAdmin) {
    $historyQuery .= " WHERE s.tenant_id = " . (int)$tenant_id;
}

$historyQuery .= " ORDER BY s.created_at DESC";
$history = $db->query($historyQuery)->fetchAll(PDO::FETCH_ASSOC);

// Stats Summary
$stats = [
    'total_revenue' => 0,
    'active_count' => 0,
    'total_count' => count($history)
];

foreach ($history as $item) {
    $stats['total_revenue'] += $item['amount'];
    if ($item['status'] === 'active') {
        $stats['active_count']++;
    }
}
?>

<div class="space-y-4 animate-fade-in text-zinc-950 dark:text-zinc-50">
    
    <!-- Top Header Navigation -->
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <button onclick="goBackToOtherMenu()" class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <div>
                <h2 class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50">Abonelik & Limitler</h2>
                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Lisans & Paket Durumu</p>
            </div>
        </div>
        
        <?php if ($isSuperAdmin): ?>
            <button onclick="openAddPlanSheet()" class="px-2.5 py-1.5 bg-zinc-900 dark:bg-zinc-50 text-white dark:text-zinc-950 rounded-lg text-[9px] font-bold uppercase tracking-wider flex items-center gap-1 active:scale-95 transition-all cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Yeni Paket
            </button>
        <?php endif; ?>
    </div>

    <!-- Pill Tabs Selection -->
    <div class="flex bg-zinc-100 dark:bg-zinc-900/60 p-1 rounded-xl">
        <button id="btn-tab-plans" onclick="switchSubTab('plans')" class="flex-1 py-2 text-center text-[10px] font-extrabold uppercase tracking-wider rounded-lg transition-all cursor-pointer bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm">
            Paketler
        </button>
        <button id="btn-tab-history" onclick="switchSubTab('history')" class="flex-1 py-2 text-center text-[10px] font-extrabold uppercase tracking-wider rounded-lg transition-all cursor-pointer text-zinc-500 dark:text-zinc-400">
            Satın Alma Geçmişi (<?= count($history) ?>)
        </button>
    </div>

    <!-- TAB 1: PLANS & ACTIVE SUBSCRIPTION -->
    <div id="sub-tab-plans" class="space-y-4">
        
        <!-- Current Active / Trial / Pending Status Card -->
        <div class="glass-card p-4 rounded-xl space-y-3 relative overflow-hidden">
            <div class="absolute -right-6 -top-6 w-16 h-16 bg-indigo-500/5 dark:bg-indigo-400/5 rounded-full blur-xl"></div>
            
            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                <div>
                    <span class="text-[8px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider block">Mevcut Durum</span>
                    <span class="text-xs font-black text-zinc-900 dark:text-zinc-100 block mt-0.5">
                        <?php if ($currentSub && $currentSub['status'] === 'active'): ?>
                            <?= htmlspecialchars($currentSub['plan_name']) ?>
                        <?php elseif ($currentSub && $currentSub['payment_status'] === 'pending'): ?>
                            <?= htmlspecialchars($currentSub['plan_name']) ?> <span class="text-amber-500 font-bold">(Onay Bekliyor)</span>
                        <?php else: ?>
                            Ücretsiz Deneme Sürümü
                        <?php endif; ?>
                    </span>
                </div>
                <div>
                    <?php if ($currentSub && $currentSub['status'] === 'active'): ?>
                        <span class="badge-aktif px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider">Aktif</span>
                    <?php elseif ($currentSub && $currentSub['payment_status'] === 'pending'): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider bg-amber-500/10 text-amber-500 border border-amber-500/20 animate-pulse">Talep Alındı</span>
                    <?php else: ?>
                        <span class="badge-aktif px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider">Deneme</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-zinc-50/50 dark:bg-zinc-900/40 p-2.5 rounded-lg border border-zinc-150 dark:border-zinc-800">
                    <span class="text-[8px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider block">Lisans Süresi</span>
                    <span class="text-xs font-black text-zinc-900 dark:text-zinc-100 block mt-0.5">
                        <?php if ($currentSub && $currentSub['status'] === 'active'): ?>
                            <?= ceil((strtotime($currentSub['end_date']) - time()) / 86400) ?> Gün Kaldı
                        <?php else: ?>
                            <?= $trialDaysLeft ?> Gün Kaldı
                        <?php endif; ?>
                    </span>
                </div>
                <div class="bg-zinc-50/50 dark:bg-zinc-900/40 p-2.5 rounded-lg border border-zinc-150 dark:border-zinc-800">
                    <span class="text-[8px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider block">Kullanıcı Limiti</span>
                    <span class="text-xs font-black text-zinc-900 dark:text-zinc-100 block mt-0.5">Sınırsız</span>
                </div>
            </div>
        </div>

        <!-- Plans Comparison List -->
        <div class="space-y-4">
            <h3 class="text-[10px] text-zinc-400 dark:text-zinc-500 font-extrabold uppercase tracking-wider block mb-1">Paket Seçenekleri</h3>
            
            <?php if (count($plans) === 0): ?>
                <div class="glass-card p-6 text-center text-zinc-450 dark:text-zinc-505 text-xs font-bold rounded-xl">
                    Aktif abonelik paketi bulunmamaktadır.
                </div>
            <?php endif; ?>

            <?php foreach ($plans as $plan): ?>
                <div class="glass-card p-5 rounded-2xl flex flex-col relative overflow-hidden transition-all duration-200">
                    
                    <!-- Admin Edit/Delete Controls -->
                    <?php if ($isSuperAdmin): ?>
                        <div class="absolute top-4 right-4 flex gap-1.5">
                            <button onclick="openEditPlanSheet(<?= $plan['id'] ?>)" class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 active:scale-95 transition-all cursor-pointer" title="Düzenle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                            </button>
                            <button onclick="confirmDeletePlan(<?= $plan['id'] ?>)" class="w-7 h-7 rounded-lg bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-500 hover:bg-rose-500/20 active:scale-95 transition-all cursor-pointer" title="Sil">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <span class="text-[8px] bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">Plan</span>
                        <h3 class="text-xs font-black text-zinc-950 dark:text-zinc-50 mt-1"><?= htmlspecialchars($plan['name']) ?></h3>
                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-xl font-black tracking-tight text-zinc-950 dark:text-zinc-50"><?= number_format($plan['price'], 0, ',', '.') ?> ₺</span>
                            <span class="text-[9px] font-bold text-zinc-400">/<?= $plan['duration_days'] ?> Gün</span>
                        </div>
                    </div>

                    <!-- Features -->
                    <ul class="mb-4 space-y-2.5 border-t border-zinc-100 dark:border-zinc-800 pt-3 flex-1">
                        <?php 
                        $features = explode(',', $plan['features']);
                        foreach ($features as $feature): 
                        ?>
                            <li class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 shrink-0">
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-[11px] text-zinc-650 dark:text-zinc-400 font-semibold"><?= htmlspecialchars(trim($feature)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php
                    // CTA Button Config
                    $btnClass = "w-full py-3 bg-zinc-900 dark:bg-zinc-50 text-white dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer active:scale-95 transition-all shadow-sm";
                    $btnText = "Hemen Başla";
                    $btnDisabled = false;

                    if (!$isSuperAdmin) {
                        if ($currentSub && $currentSub['plan_id'] == $plan['id'] && $currentSub['status'] === 'active') {
                            $btnClass = "w-full py-3 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-400 dark:text-zinc-500 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 cursor-not-allowed";
                            $btnText = "Aktif Paketiniz";
                            $btnDisabled = true;
                        } elseif ($currentSub && $currentSub['payment_status'] === 'pending') {
                            $btnClass = "w-full py-3 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-400 dark:text-zinc-500 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 cursor-not-allowed";
                            $btnText = "Onay Bekleyen Talep Var";
                            $btnDisabled = true;
                        }
                    }
                    ?>
                    
                    <button onclick="openPurchasePlanSheet(<?= $plan['id'] ?>, '<?= htmlspecialchars($plan['name'], ENT_QUOTES) ?>', '<?= number_format($plan['price'], 0, ',', '.') ?> ₺')" 
                            class="<?= $btnClass ?>" 
                            <?= $btnDisabled ? 'disabled' : '' ?>>
                        <?= $btnText ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAB 2: PURCHASE HISTORY -->
    <div id="sub-tab-history" class="space-y-4 hidden">
        
        <!-- Summary Stats (Superadmin only) -->
        <?php if ($isSuperAdmin): ?>
            <div class="grid grid-cols-3 gap-2">
                <div class="glass-card p-3 rounded-xl">
                    <span class="text-[7px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider block">Toplam Gelir</span>
                    <span class="text-xs font-black text-zinc-950 dark:text-zinc-50 block mt-1"><?= number_format($stats['total_revenue'], 0, ',', '.') ?> ₺</span>
                </div>
                <div class="glass-card p-3 rounded-xl">
                    <span class="text-[7px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider block">Aktif Lisans</span>
                    <span class="text-xs font-black text-zinc-950 dark:text-zinc-50 block mt-1"><?= $stats['active_count'] ?></span>
                </div>
                <div class="glass-card p-3 rounded-xl">
                    <span class="text-[7px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider block">Toplam İşlem</span>
                    <span class="text-xs font-black text-zinc-950 dark:text-zinc-50 block mt-1"><?= $stats['total_count'] ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- History Cards List -->
        <div class="space-y-3">
            <?php if (count($history) === 0): ?>
                <div class="glass-card p-6 text-center text-zinc-450 dark:text-zinc-505 text-xs font-bold rounded-xl">
                    Herhangi bir satın alma kaydı bulunamadı.
                </div>
            <?php endif; ?>

            <?php foreach ($history as $item): ?>
                <div class="glass-card p-4 rounded-xl space-y-3 relative overflow-hidden">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[8px] bg-indigo-500/10 text-indigo-500 px-2 py-0.5 rounded font-black uppercase tracking-wider"><?= htmlspecialchars($item['plan_name']) ?></span>
                            <span class="text-[11px] font-black text-zinc-950 dark:text-zinc-50 block mt-1.5"><?= number_format($item['amount'], 2, ',', '.') ?> ₺</span>
                        </div>
                        
                        <!-- Status Badge -->
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider <?php 
                            if ($item['payment_status'] === 'pending') {
                                echo 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400 animate-pulse';
                            } elseif ($item['payment_status'] === 'failed') {
                                echo 'bg-rose-100 text-rose-600 dark:bg-rose-950/30 dark:text-rose-450';
                            } elseif ($item['status'] === 'active') {
                                echo 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400';
                            } elseif ($item['status'] === 'expired') {
                                echo 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400';
                            } else {
                                echo 'bg-rose-100 text-rose-600 dark:bg-rose-955/30 dark:text-rose-400';
                            }
                        ?>">
                            <?php 
                            if ($item['payment_status'] === 'pending') {
                                echo 'Onay Bekliyor';
                            } elseif ($item['payment_status'] === 'failed') {
                                echo 'Reddedildi';
                            } elseif ($item['status'] === 'active') {
                                echo 'Aktif / Onaylı';
                            } elseif ($item['status'] === 'expired') {
                                echo 'Süresi Doldu';
                            } else {
                                echo 'İptal Edildi';
                            }
                            ?>
                        </span>
                    </div>

                    <!-- Client Detail (Superadmin only) -->
                    <div class="grid grid-cols-2 gap-2 text-[9px] border-t border-zinc-100 dark:border-zinc-900 pt-2 text-zinc-500 dark:text-zinc-400 font-semibold leading-relaxed">
                        <?php if ($isSuperAdmin): ?>
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-505 block text-[7px] uppercase tracking-wider">Kurum</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($item['tenant_name'] ?: 'Bireysel') ?></span>
                            </div>
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-505 block text-[7px] uppercase tracking-wider">Satın Alan</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($item['user_name'] ?: '-') ?></span>
                            </div>
                        <?php else: ?>
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-505 block text-[7px] uppercase tracking-wider">Başlangıç</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200"><?= date('d.m.Y', strtotime($item['start_date'])) ?></span>
                            </div>
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-505 block text-[7px] uppercase tracking-wider">Bitiş</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200"><?= date('d.m.Y', strtotime($item['end_date'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isSuperAdmin): ?>
                        <div class="grid grid-cols-2 gap-2 text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold leading-relaxed">
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-505 block text-[7px] uppercase tracking-wider">Başlangıç</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200"><?= date('d.m.Y', strtotime($item['start_date'])) ?></span>
                            </div>
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-505 block text-[7px] uppercase tracking-wider">Bitiş</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200"><?= date('d.m.Y', strtotime($item['end_date'])) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action buttons based on permissions -->
                    <?php if ($isSuperAdmin || (!$isSuperAdmin && $item['payment_status'] === 'pending')): ?>
                        <div class="flex justify-end gap-2 pt-1 border-t border-zinc-100 dark:border-zinc-900">
                            <?php if ($isSuperAdmin): ?>
                                <?php if ($item['payment_status'] === 'pending'): ?>
                                    <button onclick="openApproveSubSheet(<?= $item['id'] ?>, '<?= htmlspecialchars($item['tenant_name'] ?: 'Bireysel', ENT_QUOTES) ?>', '<?= htmlspecialchars($item['plan_name'], ENT_QUOTES) ?>', '<?= number_format($item['amount'], 2, ',', '.') ?> ₺', '<?= htmlspecialchars($item['user_name'] ?: '-', ENT_QUOTES) ?>')" 
                                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[9px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        Onayla
                                    </button>
                                    <button onclick="openRejectSubSheet(<?= $item['id'] ?>, '<?= htmlspecialchars($item['tenant_name'] ?: 'Bireysel', ENT_QUOTES) ?>', '<?= htmlspecialchars($item['plan_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['user_name'] ?: '-', ENT_QUOTES) ?>')" 
                                            class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[9px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        Reddet
                                    </button>
                                <?php endif; ?>
                                <button onclick="openDeleteSubSheet(<?= $item['id'] ?>, '<?= htmlspecialchars($item['tenant_name'] ?: 'Bireysel', ENT_QUOTES) ?>', '<?= htmlspecialchars($item['plan_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['user_name'] ?: '-', ENT_QUOTES) ?>')" 
                                        class="px-2.5 py-1.5 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-950/30 text-rose-600 dark:text-rose-450 rounded-lg text-[9px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                    Sil
                                </button>
                            <?php else: ?>
                                <!-- Normal user can delete pending purchase -->
                                <button onclick="openDeleteSubSheet(<?= $item['id'] ?>, '<?= htmlspecialchars($item['tenant_name'] ?: 'Bireysel', ENT_QUOTES) ?>', '<?= htmlspecialchars($item['plan_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['user_name'] ?: '-', ENT_QUOTES) ?>')" 
                                        class="px-2.5 py-1.5 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-950/30 text-rose-600 dark:text-rose-450 rounded-lg text-[9px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                    İptal Et
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ==============================================
     MOBILE CUSTOM BOTTOM SHEETS
     ============================================== -->

<!-- 1. ADD PLAN SHEET -->
<div id="sheet-add-plan" class="mobile-custom-sheet bottom-sheet flex flex-col max-h-[85%] text-zinc-950 dark:text-zinc-50">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Yeni Paket Ekle</h3>
        <p class="text-xs text-zinc-550 dark:text-zinc-400 font-medium">Müşterilerinize sunmak için yeni bir abonelik paketi oluşturun.</p>
        
        <form id="form-mobile-add-plan" class="space-y-4" onsubmit="handleAddPlan(event)">
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 dark:text-zinc-505 uppercase tracking-wider block" for="add-plan-name">Paket Adı</label>
                <input class="mobile-input font-semibold" type="text" id="add-plan-name" name="name" required placeholder="Örn: Başlangıç, Profesyonel" />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-zinc-400 dark:text-zinc-505 uppercase tracking-wider block" for="add-plan-price">Fiyat (TL)</label>
                    <input class="mobile-input font-bold" type="number" id="add-plan-price" name="price" required placeholder="0" />
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-zinc-400 dark:text-zinc-505 uppercase tracking-wider block" for="add-plan-duration">Süre (Gün)</label>
                    <input class="mobile-input font-bold" type="number" id="add-plan-duration" name="duration_days" required placeholder="30" />
                </div>
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 dark:text-zinc-505 uppercase tracking-wider block" for="add-plan-features">Özellikler (Virgülle ayırın)</label>
                <textarea class="mobile-input font-semibold text-xs leading-relaxed" id="add-plan-features" name="features" rows="3" required placeholder="Örn: 5 Personel, AI Desteği, Raporlama"></textarea>
            </div>
            
            <button type="submit" class="w-full py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
                Paketi Oluştur
            </button>
        </form>
    </div>
</div>

<!-- 2. EDIT PLAN SHEET -->
<div id="sheet-edit-plan" class="mobile-custom-sheet bottom-sheet flex flex-col max-h-[85%] text-zinc-950 dark:text-zinc-50">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Paket Düzenle</h3>
        <p class="text-xs text-zinc-550 dark:text-zinc-400 font-medium">Abonelik paketi bilgilerini güncelleyin.</p>
        
        <form id="form-mobile-edit-plan" class="space-y-4" onsubmit="handleEditPlan(event)">
            <input type="hidden" id="edit-plan-id" name="id" />
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 dark:text-zinc-505 uppercase tracking-wider block" for="edit-plan-name">Paket Adı</label>
                <input class="mobile-input font-semibold" type="text" id="edit-plan-name" name="name" required />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-zinc-400 dark:text-zinc-505 uppercase tracking-wider block" for="edit-plan-price">Fiyat (TL)</label>
                    <input class="mobile-input font-bold" type="number" id="edit-plan-price" name="price" required />
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-zinc-400 dark:text-zinc-505 uppercase tracking-wider block" for="edit-plan-duration">Süre (Gün)</label>
                    <input class="mobile-input font-bold" type="number" id="edit-plan-duration" name="duration_days" required />
                </div>
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 dark:text-zinc-505 uppercase tracking-wider block" for="edit-plan-features">Özellikler (Virgülle ayırın)</label>
                <textarea class="mobile-input font-semibold text-xs leading-relaxed" id="edit-plan-features" name="features" rows="3" required></textarea>
            </div>
            
            <button type="submit" class="w-full py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
                Değişiklikleri Kaydet
            </button>
        </form>
    </div>
</div>

<!-- 3. CONFIRM PURCHASE SHEET -->
<div id="sheet-confirm-purchase" class="mobile-custom-sheet bottom-sheet flex flex-col max-h-[75%] text-zinc-950 dark:text-zinc-50">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Aboneliği Başlat</h3>
        <p class="text-xs text-zinc-550 dark:text-zinc-400 font-medium">Seçtiğiniz paketi satın alarak aboneliğinizi başlatmak istediğinize emin misiniz?</p>
        
        <div class="bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-150 dark:border-zinc-800 p-4 rounded-xl space-y-2 select-none">
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Seçilen Paket:</span>
                <span id="confirm-purchase-name" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Toplam Tutar:</span>
                <span id="confirm-purchase-price" class="font-extrabold text-zinc-900 dark:text-zinc-100 text-indigo-500 dark:text-indigo-400">-</span>
            </div>
        </div>
        
        <button id="btn-confirm-purchase-submit" class="w-full py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
            Satın Alımı Onayla
        </button>
    </div>
</div>

<!-- 4. APPROVE SUBSCRIPTION SHEET -->
<div id="sheet-approve-sub" class="mobile-custom-sheet bottom-sheet flex flex-col max-h-[75%] text-zinc-950 dark:text-zinc-50">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Aboneliği Onayla</h3>
        <p class="text-xs text-zinc-550 dark:text-zinc-400 font-medium">Bu satın alma işlemini onaylamak istediğinize emin misiniz?</p>
        
        <div class="bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-150 dark:border-zinc-800 p-4 rounded-xl space-y-2 select-none">
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Müşteri:</span>
                <span id="approve-sub-tenant" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Satın Alan:</span>
                <span id="approve-sub-user" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Paket:</span>
                <span id="approve-sub-plan" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Tutar:</span>
                <span id="approve-sub-price" class="font-extrabold text-emerald-500 dark:text-emerald-400">-</span>
            </div>
        </div>
        
        <button id="btn-approve-sub-submit" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
            Aboneliği Onayla
        </button>
    </div>
</div>

<!-- 5. REJECT SUBSCRIPTION SHEET -->
<div id="sheet-reject-sub" class="mobile-custom-sheet bottom-sheet flex flex-col max-h-[75%] text-zinc-950 dark:text-zinc-50">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Satın Almayı Reddet</h3>
        <p class="text-xs text-zinc-550 dark:text-zinc-400 font-medium">Bu satın alma talebini reddetmek istediğinize emin misiniz? Kullanıcının aboneliği başlatılmayacaktır.</p>
        
        <div class="bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-150 dark:border-zinc-800 p-4 rounded-xl space-y-2 select-none">
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Müşteri:</span>
                <span id="reject-sub-tenant" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Satın Alan:</span>
                <span id="reject-sub-user" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Paket:</span>
                <span id="reject-sub-plan" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
        </div>
        
        <button id="btn-reject-sub-submit" class="w-full py-3.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
            Onayla ve Reddet
        </button>
    </div>
</div>

<!-- 6. DELETE/CANCEL SUBSCRIPTION SHEET -->
<div id="sheet-delete-sub" class="mobile-custom-sheet bottom-sheet flex flex-col max-h-[75%] text-zinc-950 dark:text-zinc-50">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Satın Alımı İptal Et / Sil</h3>
        <p class="text-xs text-zinc-550 dark:text-zinc-400 font-medium">Bu satın alma kaydını tamamen silmek/iptal etmek istediğinize emin misiniz? Bu işlem geri alınamaz.</p>
        
        <div class="bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-150 dark:border-zinc-800 p-4 rounded-xl space-y-2 select-none">
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Müşteri:</span>
                <span id="delete-sub-tenant" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Satın Alan:</span>
                <span id="delete-sub-user" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Paket:</span>
                <span id="delete-sub-plan" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
        </div>
        
        <button id="btn-delete-sub-submit" class="w-full py-3.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
            Onayla ve Sil
        </button>
    </div>
</div>

<!-- ==============================================
     INTERACTIVE JAVASCRIPT HANDLERS
     ============================================== -->
<script>
$(document).ready(function() {
    // 1. Move all bottom sheets to screen-content to prevent nested overflow clipping
    $('.mobile-custom-sheet').appendTo('.screen-content');
});

// Tab switching logic
function switchSubTab(tab) {
    if (tab === 'plans') {
        $('#sub-tab-plans').removeClass('hidden');
        $('#sub-tab-history').addClass('hidden');
        
        $('#btn-tab-plans')
            .addClass('bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm')
            .removeClass('text-zinc-500 dark:text-zinc-400');
            
        $('#btn-tab-history')
            .removeClass('bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm')
            .addClass('text-zinc-500 dark:text-zinc-400');
    } else {
        $('#sub-tab-plans').addClass('hidden');
        $('#sub-tab-history').removeClass('hidden');
        
        $('#btn-tab-history')
            .addClass('bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm')
            .removeClass('text-zinc-500 dark:text-zinc-400');
            
        $('#btn-tab-plans')
            .removeClass('bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm')
            .addClass('text-zinc-500 dark:text-zinc-400');
    }
}

// ----------------------------------------------
// SUPERADMIN: ADD PLAN ACTIONS
// ----------------------------------------------
function openAddPlanSheet() {
    $('#form-mobile-add-plan')[0].reset();
    openSheet('sheet-add-plan');
}

function handleAddPlan(e) {
    e.preventDefault();
    const btn = $('#form-mobile-add-plan button[type="submit"]');
    const originalText = btn.text();
    btn.prop('disabled', true).text('İşleniyor...');
    
    const basePath = '<?php echo appBasePath(); ?>';
    
    $.post(basePath + '/abonelik-paket-ekle', $('#form-mobile-add-plan').serialize(), function(response) {
        try {
            response = JSON.parse(response);
        } catch(e) {}
        
        closeAllSheets();
        
        if (response.success) {
            showToast('Abonelik paketi başarıyla oluşturuldu.', 'success');
            setTimeout(() => {
                loadOtherSubpage('subscription');
            }, 1000);
        } else {
            showToast(response.message || 'Paket oluşturulurken bir hata oluştu.', 'error');
            btn.prop('disabled', false).text(originalText);
        }
    }).fail(function() {
        closeAllSheets();
        showToast('Sunucu ile iletişim kurulamadı.', 'error');
        btn.prop('disabled', false).text(originalText);
    });
}

// ----------------------------------------------
// SUPERADMIN: EDIT PLAN ACTIONS
// ----------------------------------------------
function openEditPlanSheet(id) {
    const basePath = '<?php echo appBasePath(); ?>';
    
    $.get(basePath + '/abonelik-paket-get', { id: id }, function(data) {
        try {
            data = JSON.parse(data);
        } catch(e) {}
        
        $('#edit-plan-id').val(data.id);
        $('#edit-plan-name').val(data.name);
        $('#edit-plan-price').val(data.price);
        $('#edit-plan-duration').val(data.duration_days);
        $('#edit-plan-features').val(data.features);
        
        openSheet('sheet-edit-plan');
    }).fail(function() {
        showToast('Paket bilgileri alınamadı.', 'error');
    });
}

function handleEditPlan(e) {
    e.preventDefault();
    const btn = $('#form-mobile-edit-plan button[type="submit"]');
    const originalText = btn.text();
    btn.prop('disabled', true).text('İşleniyor...');
    
    const basePath = '<?php echo appBasePath(); ?>';
    
    $.post(basePath + '/abonelik-paket-guncelle', $('#form-mobile-edit-plan').serialize(), function(response) {
        try {
            response = JSON.parse(response);
        } catch(e) {}
        
        closeAllSheets();
        
        if (response.success) {
            showToast('Değişiklikler başarıyla kaydedildi.', 'success');
            setTimeout(() => {
                loadOtherSubpage('subscription');
            }, 1000);
        } else {
            showToast(response.message || 'Paket güncellenirken bir hata oluştu.', 'error');
            btn.prop('disabled', false).text(originalText);
        }
    }).fail(function() {
        closeAllSheets();
        showToast('Sunucu ile iletişim kurulamadı.', 'error');
        btn.prop('disabled', false).text(originalText);
    });
}

// ----------------------------------------------
// SUPERADMIN: DELETE PLAN ACTIONS
// ----------------------------------------------
function confirmDeletePlan(id) {
    if (!confirm('Bu paketi silmek (pasif yapmak) istediğinize emin misiniz?')) return;
    
    const basePath = '<?php echo appBasePath(); ?>';
    
    $.post(basePath + '/abonelik-paket-sil', { id: id }, function(response) {
        try {
            response = JSON.parse(response);
        } catch(e) {}
        
        if (response.success) {
            showToast('Paket başarıyla pasif yapıldı.', 'success');
            setTimeout(() => {
                loadOtherSubpage('subscription');
            }, 1000);
        } else {
            showToast(response.message || 'Paket silinemedi.', 'error');
        }
    }).fail(function() {
        showToast('Sunucu ile iletişim kurulamadı.', 'error');
    });
}

// ----------------------------------------------
// NORMAL USER: PURCHASE PLAN ACTIONS
// ----------------------------------------------
function openPurchasePlanSheet(id, name, price) {
    $('#confirm-purchase-name').text(name);
    $('#confirm-purchase-price').text(price);
    
    $('#btn-confirm-purchase-submit').off('click').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('Talebiniz Gönderiliyor...');
        
        const basePath = '<?php echo appBasePath(); ?>';
        
        $.post(basePath + '/abonelik-satinal', { plan_id: id }, function(response) {
            try {
                response = JSON.parse(response);
            } catch(e) {}
            
            closeAllSheets();
            
            if (response.success) {
                showToast(response.message || 'Satın alma talebiniz alındı!', 'success');
                setTimeout(() => {
                    loadOtherSubpage('subscription');
                }, 1500);
            } else {
                showToast(response.message || 'Talebiniz iletilemedi.', 'error');
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            closeAllSheets();
            showToast('Sunucu bağlantı hatası.', 'error');
            btn.prop('disabled', false).text(originalText);
        });
    });
    
    openSheet('sheet-confirm-purchase');
}

// ----------------------------------------------
// SUPERADMIN: APPROVE PURCHASE ACTIONS
// ----------------------------------------------
function openApproveSubSheet(id, tenantName, planName, amount, userName) {
    $('#approve-sub-tenant').text(tenantName || '-');
    $('#approve-sub-user').text(userName || '-');
    $('#approve-sub-plan').text(planName || '-');
    $('#approve-sub-price').text(amount || '-');
    
    $('#btn-approve-sub-submit').off('click').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('İşleniyor...');
        
        const basePath = '<?php echo appBasePath(); ?>';
        
        $.post(basePath + '/abonelik-onayla', { id: id }, function(response) {
            try {
                response = JSON.parse(response);
            } catch(e) {}
            
            closeAllSheets();
            
            if (response.success) {
                showToast('Abonelik başarıyla onaylandı.', 'success');
                setTimeout(() => {
                    loadOtherSubpage('subscription');
                }, 1000);
            } else {
                showToast(response.message || 'Abonelik onaylanamadı.', 'error');
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            closeAllSheets();
            showToast('Sunucu bağlantı hatası.', 'error');
            btn.prop('disabled', false).text(originalText);
        });
    });
    
    openSheet('sheet-approve-sub');
}

// ----------------------------------------------
// SUPERADMIN: REJECT PURCHASE ACTIONS
// ----------------------------------------------
function openRejectSubSheet(id, tenantName, planName, userName) {
    $('#reject-sub-tenant').text(tenantName || '-');
    $('#reject-sub-user').text(userName || '-');
    $('#reject-sub-plan').text(planName || '-');
    
    $('#btn-reject-sub-submit').off('click').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('İşleniyor...');
        
        const basePath = '<?php echo appBasePath(); ?>';
        
        $.post(basePath + '/abonelik-reddet', { id: id }, function(response) {
            try {
                response = JSON.parse(response);
            } catch(e) {}
            
            closeAllSheets();
            
            if (response.success) {
                showToast('Satın alma talebi reddedildi.', 'success');
                setTimeout(() => {
                    loadOtherSubpage('subscription');
                }, 1000);
            } else {
                showToast(response.message || 'Talebi reddederken hata oluştu.', 'error');
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            closeAllSheets();
            showToast('Sunucu bağlantı hatası.', 'error');
            btn.prop('disabled', false).text(originalText);
        });
    });
    
    openSheet('sheet-reject-sub');
}

// ----------------------------------------------
// NORMAL/SUPERADMIN: DELETE/CANCEL PURCHASE ACTIONS
// ----------------------------------------------
function openDeleteSubSheet(id, tenantName, planName, userName) {
    $('#delete-sub-tenant').text(tenantName || '-');
    $('#delete-sub-user').text(userName || '-');
    $('#delete-sub-plan').text(planName || '-');
    
    $('#btn-delete-sub-submit').off('click').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('İşleniyor...');
        
        const basePath = '<?php echo appBasePath(); ?>';
        
        $.post(basePath + '/abonelik-sil', { id: id }, function(response) {
            try {
                response = JSON.parse(response);
            } catch(e) {}
            
            closeAllSheets();
            
            if (response.success) {
                showToast(response.message || 'Satın alma kaydı iptal edildi/silindi.', 'success');
                setTimeout(() => {
                    loadOtherSubpage('subscription');
                }, 1000);
            } else {
                showToast(response.message || 'İşlem başarısız.', 'error');
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            closeAllSheets();
            showToast('Sunucu bağlantı hatası.', 'error');
            btn.prop('disabled', false).text(originalText);
        });
    });
    
    openSheet('sheet-delete-sub');
}
</script>

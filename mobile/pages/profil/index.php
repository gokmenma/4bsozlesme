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

// Fetch full user row
$uStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$uStmt->execute([$_SESSION['user_id']]);
$user = $uStmt->fetch();
$userRole = $user['role'] ?? 'user';

$trialDaysLeft = 0;
if (!empty($user['trial_ends_at'])) {
    $trialDaysLeft = ceil((strtotime($user['trial_ends_at']) - strtotime(date('Y-m-d'))) / 86400);
    if ($trialDaysLeft < 0) $trialDaysLeft = 0;
}

// Active or last subscription
$stmt = $db->prepare("SELECT s.*, sp.name as plan_name, sp.price as plan_price, sp.features as plan_features 
                      FROM subscriptions s 
                      JOIN subscription_plans sp ON s.plan_id = sp.id
                      WHERE s.user_id = ? OR s.tenant_id = ? 
                      ORDER BY s.id DESC LIMIT 1");
$stmt->execute([$user['id'], $user['tenant_id'] ?? null]);
$subscription = $stmt->fetch();
$has_subscription = !empty($subscription);
$sub_start_date = $has_subscription ? date('d.m.Y', strtotime($subscription['start_date'])) : null;
$sub_end_date = $has_subscription ? date('d.m.Y', strtotime($subscription['end_date'])) : null;

// Get all active plans that can be purchased
$plansStmt = $db->query("SELECT * FROM subscription_plans WHERE is_active = 1");
$plans = $plansStmt->fetchAll();

// Get purchase history for this tenant (or all if superadmin)
$historyQuery = "SELECT s.*, sp.name as plan_name, u.name as user_name 
                 FROM subscriptions s 
                 JOIN subscription_plans sp ON s.plan_id = sp.id 
                 LEFT JOIN users u ON s.user_id = u.id";

if ($userRole !== 'superadmin') {
    $historyQuery .= " WHERE s.tenant_id = " . (int)$user['tenant_id'];
}

$historyQuery .= " ORDER BY s.created_at DESC";
$history = $db->query($historyQuery)->fetchAll();

// Fetch tenants for switching
$stmt_tenants = $db->prepare("SELECT t.id, t.name FROM tenants t INNER JOIN user_tenants ut ON t.id = ut.tenant_id WHERE ut.user_id = ?");
$stmt_tenants->execute([$_SESSION['user_id']]);
$tenants = $stmt_tenants->fetchAll(PDO::FETCH_ASSOC);

// Helper function to safely output values
if (!function_exists('getVal')) {
    function getVal($key, $user) {
        return htmlspecialchars($user[$key] ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
<div class="space-y-6 animate-fade-in pb-10">
    <!-- Profile Header Card -->
    <div class="glass-card p-6 rounded-2xl text-center relative overflow-hidden">
        <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center font-black text-2xl text-zinc-900 dark:text-zinc-100 uppercase mx-auto mb-3 shadow-sm select-none">
            <?= mb_substr($_SESSION['user_name'], 0, 2) ?>
        </div>
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 leading-tight"><?= htmlspecialchars($_SESSION['user_name']) ?></h3>
        <p class="text-xs text-zinc-400 font-semibold mt-0.5"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
        <span class="badge-kadro px-3 py-1 rounded text-[9px] font-black uppercase tracking-wider inline-block mt-3"><?= htmlspecialchars($userRole) ?></span>
    </div>

    <!-- Segmented Tab Navigation -->
    <div class="flex p-1 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/50 dark:border-zinc-800/40 rounded-xl select-none">
        <button onclick="switchMobileProfileTab('personal')" id="m-tab-btn-personal" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all text-center cursor-pointer bg-white dark:bg-zinc-800 text-zinc-950 dark:text-zinc-50 shadow-sm" data-tab="personal">
            Kişisel
        </button>
        <button onclick="switchMobileProfileTab('security')" id="m-tab-btn-security" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all text-center cursor-pointer text-zinc-500 dark:text-zinc-400" data-tab="security">
            Güvenlik
        </button>
        <?php if ($userRole === 'admin' || $userRole === 'superadmin'): ?>
        <button onclick="switchMobileProfileTab('subscription')" id="m-tab-btn-subscription" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all text-center cursor-pointer text-zinc-500 dark:text-zinc-400" data-tab="subscription">
            Abonelik
        </button>
        <?php endif; ?>
    </div>

    <!-- TAB PANEL 1: PERSONAL (Kişisel Bilgiler & Kurum Değiştirici) -->
    <div id="m-profile-tab-personal" class="space-y-6">
        <!-- Personal Info Form -->
        <div class="glass-card p-5 rounded-2xl space-y-4">
            <h4 class="text-[10px] font-bold text-zinc-450 dark:text-zinc-500 uppercase tracking-wider flex items-center gap-1.5 select-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Hesap ve İletişim Bilgileri
            </h4>
            
            <form id="form-profile" class="space-y-4">
                <div class="space-y-1.5">
                    <label for="name" class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Ad Soyad</label>
                    <input type="text" id="name" name="name" value="<?php echo getVal('name', $user); ?>" placeholder="Ad Soyad" required class="mobile-input text-xs font-semibold">
                </div>
                <div class="space-y-1.5 opacity-55 select-none pointer-events-none">
                    <label for="username_display" class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Kullanıcı Adı</label>
                    <input type="text" id="username_display" value="<?php echo explode('@', getVal('email', $user))[0]; ?>" disabled class="mobile-input text-xs font-semibold bg-zinc-100 dark:bg-zinc-950 cursor-not-allowed text-zinc-400 dark:text-zinc-500 outline-none select-none" tabindex="-1">
                </div>

                <div class="space-y-1.5 opacity-55 select-none pointer-events-none">
                    <label for="email_display" class="text-[10px] font-black text-zinc-400 dark:text-zinc-550 uppercase tracking-wider block">E-posta Adresi</label>
                    <input type="email" id="email_display" value="<?php echo getVal('email', $user); ?>" disabled class="mobile-input text-xs font-semibold bg-zinc-100 dark:bg-zinc-950 cursor-not-allowed text-zinc-400 dark:text-zinc-500 outline-none select-none" tabindex="-1">
                </div>
                <input type="hidden" name="email" id="email" value="<?php echo getVal('email', $user); ?>">
                
                <span class="text-[9px] text-zinc-400 dark:text-zinc-500 font-semibold block leading-tight select-none mt-1">Kullanıcı adı ve e-posta adresi benzersiz giriş kimliğiniz olup güvenlik amacıyla değiştirilemez.</span>
                
                <button type="submit" class="w-full py-3.5 bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 active:scale-95 transition-all shadow-sm cursor-pointer select-none">
                    Bilgileri Güncelle
                </button>
            </form>
        </div>

        <!-- Tenant Selector -->
        <div class="glass-card p-5 rounded-2xl space-y-4">
            <h4 class="text-[10px] font-bold text-zinc-450 dark:text-zinc-500 uppercase tracking-wider flex items-center gap-1.5 select-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Aktif Kurum Değiştir
            </h4>
            <div class="space-y-2.5">
                <?php foreach ($tenants as $t): 
                    $isActiveTenant = ($t['id'] == $tenant_id);
                    $activeBorder = $isActiveTenant ? 'border-zinc-450 dark:border-zinc-600 bg-zinc-100 dark:bg-zinc-900/50' : 'border-zinc-200 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-950/20';
                ?>
                    <a href="<?= routeUrl('/switch-tenant?id=' . $t['id']) ?>" 
                       class="w-full p-3.5 rounded-xl border <?= $activeBorder ?> flex items-center justify-between active:scale-[0.97] transition-all hover:bg-zinc-100 dark:hover:bg-zinc-900 block text-left">
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 leading-none"><?= htmlspecialchars($t['name']) ?></span>
                        <?php if ($isActiveTenant): ?>
                            <span class="w-2 h-2 bg-zinc-950 dark:bg-zinc-100 rounded-full shadow-sm"></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Secure Logout Button -->
        <div class="space-y-3">
            <a href="<?= routeUrl('/logout') ?>" class="w-full py-4 bg-rose-500/10 dark:bg-rose-550/5 border border-rose-500/20 dark:border-rose-900/20 text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 active:scale-[0.97] transition-all rounded-xl text-center font-bold text-xs flex items-center justify-center gap-2 cursor-pointer select-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m11 9H9m7-4 4 4-4 4"/></svg>
                Güvenli Çıkış Yap
            </a>
        </div>
    </div>

    <!-- TAB PANEL 2: SECURITY (Şifre Değiştirme & Güvenlik) -->
    <div id="m-profile-tab-security" class="space-y-6 hidden">
        <!-- Change Password Form -->
        <div class="glass-card p-5 rounded-2xl space-y-4">
            <h4 class="text-[10px] font-bold text-zinc-450 dark:text-zinc-500 uppercase tracking-wider flex items-center gap-1.5 select-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Şifre Değiştir
            </h4>
            
            <form id="form-password" class="space-y-4">
                <div class="space-y-1.5">
                    <label for="current_password" class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Mevcut Şifre</label>
                    <input type="password" id="current_password" name="current_password" required placeholder="••••••••" class="mobile-input text-xs font-semibold">
                </div>

                <div class="space-y-1.5">
                    <label for="new_password" class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Yeni Şifre</label>
                    <input type="password" id="new_password" name="new_password" required placeholder="••••••••" class="mobile-input text-xs font-semibold">
                </div>

                <div class="space-y-1.5">
                    <label for="confirm_password" class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Şifre Tekrarı</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" class="mobile-input text-xs font-semibold">
                </div>
                
                <button type="submit" class="w-full py-3.5 bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 active:scale-95 transition-all shadow-sm cursor-pointer select-none">
                    Şifreyi Güncelle
                </button>
            </form>
        </div>

        <!-- Danger Zone / Delete Account -->
        <div class="bg-rose-500/5 dark:bg-rose-500/5 border border-rose-500/20 dark:border-rose-500/20 rounded-2xl p-5 space-y-4 select-none">
            <div class="space-y-1">
                <h4 class="text-xs font-bold text-rose-500 flex items-center gap-1.5 uppercase tracking-wider">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                    Hesabı Kalıcı Olarak Sil
                </h4>
                <p class="text-[10px] text-zinc-550 dark:text-zinc-400 font-semibold leading-relaxed">Hesabınızı silmek kalıcı bir işlemdir. Tüm sözleşme şablonlarınız, personel verileriniz ve ödeme kayıtlarınız tamamen silinir ve geri alınamaz.</p>
            </div>
            <button type="button" onclick="openDeleteModal()" class="w-full py-3.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm active:scale-95 cursor-pointer select-none">
                Hesabımı Sil
            </button>
        </div>
    </div>

    <!-- TAB PANEL 3: SUBSCRIPTION (Abonelik, Satın Alma & Paketler) -->
    <?php if ($userRole === 'admin' || $userRole === 'superadmin'): ?>
    <div id="m-profile-tab-subscription" class="space-y-6 hidden">
        <!-- Active Subscription Status -->
        <div class="space-y-3">
            <h4 class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 flex items-center gap-1.5 select-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>
                Mevcut Paket Durumu
            </h4>

            <?php if ($has_subscription && $subscription['status'] === 'active'): ?>
                <!-- User is subscribed and active -->
                <div class="bg-gradient-to-br from-indigo-500/10 to-purple-500/5 border border-indigo-500/20 rounded-2xl p-5 flex flex-col gap-4 shadow-sm select-none">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-indigo-500/10 text-indigo-500 rounded-2xl shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 2 18 2 18 6 6 6 6 2"/><rect width="14" height="14" x="5" y="6" rx="2"/><path d="M9 16h6"/></svg>
                        </div>
                        <div class="space-y-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-indigo-500/10 border border-indigo-500/20 text-indigo-500 shadow-sm">
                                Aktif Abonelik
                            </span>
                            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 leading-tight">
                                <?php echo htmlspecialchars($subscription['plan_name']); ?>
                            </h3>
                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5">
                                Dönem: <span class="font-bold text-zinc-750 dark:text-zinc-300"><?php echo $sub_start_date; ?> - <?php echo $sub_end_date; ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-zinc-200/50 dark:border-zinc-800/80">
                        <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-550 uppercase tracking-wider">Abonelik Tutarı</span>
                        <span class="text-lg font-black text-indigo-555 dark:text-indigo-400"><?php echo number_format($subscription['amount'], 0, ',', '.'); ?> ₺</span>
                    </div>
                </div>
            <?php elseif ($has_subscription && $subscription['payment_status'] === 'pending'): ?>
                <!-- User subscription is pending approval -->
                <div class="bg-gradient-to-br from-amber-500/10 to-yellow-500/5 border border-amber-500/20 rounded-2xl p-5 flex flex-col gap-4 shadow-sm select-none">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-amber-500/10 text-amber-550 rounded-2xl animate-pulse shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        </div>
                        <div class="space-y-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-amber-500/10 border border-amber-500/20 text-amber-550 shadow-sm animate-pulse">
                                Onay Bekliyor
                            </span>
                            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 leading-tight">
                                <?php echo htmlspecialchars($subscription['plan_name']); ?>
                            </h3>
                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5 leading-relaxed">
                                Talebiniz sisteme iletildi. Yönetici onayının ardından paketiniz aktif hale getirilecektir.
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-zinc-200/50 dark:border-zinc-800/80">
                        <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-550 uppercase tracking-wider">Abonelik Tutarı</span>
                        <span class="text-lg font-black text-amber-550 dark:text-amber-400"><?php echo number_format($subscription['amount'], 0, ',', '.'); ?> ₺</span>
                    </div>
                </div>
            <?php else: ?>
                <!-- User is in trial -->
                <div class="bg-gradient-to-br from-amber-500/10 to-orange-500/5 border border-amber-500/20 rounded-2xl p-5 flex flex-col gap-4 shadow-sm select-none">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-amber-500/10 text-amber-500 rounded-2xl shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        </div>
                        <div class="space-y-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-amber-500/10 border border-amber-500/20 text-amber-550 shadow-sm animate-pulse">
                                Deneme Sürümü
                            </span>
                            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 leading-tight">
                                Ücretsiz 30 Günlük Deneme
                            </h3>
                            <p class="text-[10px] text-zinc-555 dark:text-zinc-455 font-semibold mt-0.5">
                                Sistem bitiş tarihi: <strong class="font-bold text-zinc-700 dark:text-zinc-300"><?php echo date('d.m.Y', strtotime($user['trial_ends_at'])); ?></strong>
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-zinc-200/50 dark:border-zinc-800/80">
                        <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-550 uppercase tracking-wider">Kalan Süre</span>
                        <?php 
                        $remainingDays = ceil((strtotime($user['trial_ends_at']) - time()) / 86400); 
                        $remainingDays = $remainingDays > 0 ? $remainingDays : 0;
                        ?>
                        <span class="text-lg font-black text-amber-555 dark:text-amber-400"><?php echo $remainingDays; ?> Gün</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Subscription Plans (Pricing cards list) -->
        <div class="space-y-3">
            <h4 class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mt-6 flex items-center gap-1.5 select-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Satın Alabileceğiniz Paketler
            </h4>
            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold select-none leading-relaxed">Kurumunuza en uygun planı seçerek hemen aboneliğinizi başlatın veya yükseltin.</p>

            <div class="space-y-4.5">
                <?php foreach ($plans as $plan): ?>
                    <div class="glass-card rounded-2xl p-5 flex flex-col space-y-4 shadow-sm relative">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xs font-black text-zinc-900 dark:text-zinc-100">
                                    <?php echo htmlspecialchars($plan['name']); ?>
                                </h3>
                                <p class="text-[9px] text-zinc-400 font-bold uppercase tracking-wider mt-0.5">Süre: <?php echo $plan['duration_days']; ?> Gün</p>
                            </div>
                            <div class="text-right">
                                <span class="text-base font-black text-zinc-900 dark:text-zinc-100">
                                    <?php echo number_format($plan['price'], 0, ',', '.'); ?> ₺
                                </span>
                            </div>
                        </div>
                        
                        <!-- Features list -->
                        <ul class="space-y-2 pt-3 border-t border-zinc-200/50 dark:border-zinc-800/80">
                            <?php 
                            $features = explode(',', $plan['features']);
                            foreach ($features as $feature): 
                            ?>
                                <li class="flex items-start gap-2">
                                    <svg class="h-3.5 w-3.5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold leading-tight">
                                        <?php echo htmlspecialchars(trim($feature)); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- Buy Button -->
                        <button onclick="purchasePlan(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['name'], ENT_QUOTES); ?>', '<?php echo number_format($plan['price'], 0, ',', '.'); ?> ₺')" class="w-full py-2.5 bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 rounded-xl text-xs font-bold transition-all cursor-pointer active:scale-95 text-center shadow-sm hover:opacity-90 select-none">
                            Hemen Satın Al
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Purchase History (Sleek List) -->
        <div class="space-y-3">
            <h4 class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mt-6 flex items-center gap-1.5 select-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Satın Alma ve Ödeme Geçmişi
            </h4>

            <?php if (empty($history)): ?>
                <div class="glass-card rounded-2xl p-6 text-center text-zinc-500 dark:text-zinc-400 text-[10px] font-semibold select-none">
                    Henüz bir satın alma kaydınız bulunmamaktadır.
                </div>
            <?php else: ?>
                <div class="space-y-3 select-none">
                    <?php foreach ($history as $item): ?>
                        <div class="glass-card rounded-xl p-4 flex flex-col gap-3 shadow-xs">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h5 class="text-[11px] font-extrabold text-zinc-900 dark:text-zinc-100 leading-tight">
                                        <?php echo htmlspecialchars($item['plan_name']); ?>
                                    </h5>
                                    <p class="text-[9px] text-zinc-400 font-semibold mt-0.5">Satın Alan: <?= htmlspecialchars($item['user_name'] ?: '-') ?></p>
                                </div>
                                <span class="text-xs font-extrabold text-zinc-900 dark:text-zinc-100">
                                    <?php echo number_format($item['amount'], 2, ',', '.'); ?> ₺
                                </span>
                            </div>
                            
                            <div class="flex justify-between items-center text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold">
                                <span>Dönem: <?= date('d.m.Y', strtotime($item['start_date'])); ?> - <?= date('d.m.Y', strtotime($item['end_date'])); ?></span>
                                
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider <?php 
                                    if ($item['payment_status'] === 'pending') {
                                        echo 'bg-amber-500/10 border border-amber-500/20 text-amber-500 animate-pulse';
                                    } elseif ($item['payment_status'] === 'failed') {
                                        echo 'bg-rose-500/10 border border-rose-500/20 text-rose-500';
                                    } elseif ($item['status'] === 'active') {
                                        echo 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-500';
                                    } elseif ($item['status'] === 'expired') {
                                        echo 'bg-zinc-500/10 border border-zinc-500/20 text-zinc-400';
                                    } else {
                                        echo 'bg-red-500/10 border border-red-500/20 text-red-500';
                                    }
                                ?>">
                                    <?php 
                                    if ($item['payment_status'] === 'pending') {
                                        echo 'Onay Bekliyor';
                                    } elseif ($item['payment_status'] === 'failed') {
                                        echo 'Reddedildi';
                                    } elseif ($item['status'] === 'active') {
                                        echo 'Onaylı';
                                    } elseif ($item['status'] === 'expired') {
                                        echo 'Süresi Doldu';
                                    } else {
                                        echo 'İptal Edildi';
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($item['payment_status'] === 'pending'): ?>
                                <div class="pt-2 border-t border-zinc-200/50 dark:border-zinc-800/80 flex justify-end">
                                    <button onclick="cancelSubscriptionPurchase(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['plan_name'], ENT_QUOTES); ?>')" class="px-3 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-500 rounded-lg text-[9px] font-black transition-all cursor-pointer active:scale-95">
                                        Talebi İptal Et
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center text-[10px] text-zinc-500 font-semibold uppercase tracking-widest pt-2 select-none">
        Sözleşme 4B Mobil v1.0.0
    </div>
</div>

<!-- ==================== MODALS & DIALOGS (PORTABILITY & NATIVE LOOK) ==================== -->

<!-- Purchase Confirmation Modal -->
<dialog id="dialog-confirm-purchase" class="backdrop:bg-zinc-950/60 backdrop:backdrop-blur-xs bg-transparent border-none outline-none max-w-sm w-full p-4 mx-auto my-auto self-center select-none">
  <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-2xl space-y-4">
    <div class="flex items-center gap-3 text-zinc-900 dark:text-zinc-100">
      <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-500">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </div>
      <h3 class="text-sm font-extrabold">Aboneliği Başlat</h3>
    </div>
    
    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-semibold leading-relaxed">Seçtiğiniz abonelik paketini satın alarak hesabınızı aktif hale getirmek istediğinizden emin misiniz?</p>
    
    <div class="bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 p-4 rounded-xl space-y-2">
      <div class="flex justify-between items-center text-xs">
        <span class="text-zinc-400 dark:text-zinc-500 font-semibold">Seçilen Paket:</span>
        <span id="confirm-plan-name" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
      </div>
      <div class="flex justify-between items-center text-xs">
        <span class="text-zinc-400 dark:text-zinc-500 font-semibold">Toplam Tutar:</span>
        <span id="confirm-plan-price" class="font-black text-indigo-550 dark:text-indigo-400">-</span>
      </div>
    </div>
    
    <div class="flex justify-end gap-3 pt-2">
      <button type="button" onclick="document.getElementById('dialog-confirm-purchase').close()" class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-850 text-zinc-500 dark:text-zinc-400 cursor-pointer transition-colors">Vazgeç</button>
      <button type="button" id="btn-confirm-purchase" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 hover:opacity-95 cursor-pointer transition-all active:scale-95 shadow-sm">Satın Alımı Onayla</button>
    </div>
  </div>
</dialog>

<!-- Cancel Pending Purchase Dialog -->
<dialog id="dialog-cancel-purchase" class="backdrop:bg-zinc-950/60 backdrop:backdrop-blur-xs bg-transparent border-none outline-none max-w-sm w-full p-4 mx-auto my-auto self-center select-none">
  <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-2xl space-y-4">
    <div class="flex items-center gap-3 text-rose-500">
      <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-500 shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 9-6 6"/><path d="m9 9 6 6"/><circle cx="12" cy="12" r="10"/></svg>
      </div>
      <h3 class="text-sm font-extrabold">Talebi İptal Et</h3>
    </div>
    
    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-semibold leading-relaxed">Bu satın alma talebini iptal etmek istediğinize emin misiniz? Onay bekleyen ödeme kaydınız silinecektir.</p>
    
    <div class="bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 p-4 rounded-xl">
      <div class="flex justify-between items-center text-xs">
        <span class="text-zinc-400 dark:text-zinc-500 font-semibold">İptal Edilecek Paket:</span>
        <span id="cancel-plan-name" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
      </div>
    </div>
    
    <div class="flex justify-end gap-3 pt-2">
      <button type="button" onclick="document.getElementById('dialog-cancel-purchase').close()" class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-850 text-zinc-500 dark:text-zinc-400 cursor-pointer transition-colors">Vazgeç</button>
      <button type="button" id="btn-confirm-cancel" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white cursor-pointer transition-all active:scale-95 shadow-sm">Talebi İptal Et ve Sil</button>
    </div>
  </div>
</dialog>

<!-- Delete Account Modal -->
<dialog id="delete-modal" class="backdrop:bg-zinc-950/60 backdrop:backdrop-blur-xs bg-transparent border-none outline-none max-w-sm w-full p-4 mx-auto my-auto self-center select-none">
  <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-2xl space-y-4">
    <div class="flex items-center gap-3 text-rose-500">
      <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-500">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 9-6 6"/><path d="m9 9 6 6"/><circle cx="12" cy="12" r="10"/></svg>
      </div>
      <h3 class="text-sm font-extrabold">Hesabınızı Silin</h3>
    </div>
    
    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-semibold leading-relaxed">Bu işlem kalıcıdır ve geri alınamaz. Lütfen işlemi onaylamak için mevcut şifrenizi girin.</p>
    
    <form id="form-delete-account" class="space-y-4">
      <div class="space-y-1.5">
        <label for="delete_password" class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Mevcut Şifreniz</label>
        <input type="password" id="delete_password" name="password" required placeholder="Şifreniz" class="mobile-input text-xs font-semibold">
      </div>
      
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-850 text-zinc-500 dark:text-zinc-400 cursor-pointer transition-colors">Vazgeç</button>
        <button type="submit" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white cursor-pointer transition-all active:scale-95 shadow-sm">Onayla ve Sil</button>
      </div>
    </form>
  </div>
</dialog>

<!-- JavaScript Section (Self-Contained Ajax Engine) -->
<script>
function switchMobileProfileTab(tabName) {
    // Hide all tab panels
    document.getElementById('m-profile-tab-personal').classList.add('hidden');
    document.getElementById('m-profile-tab-security').classList.add('hidden');
    const subTabPanel = document.getElementById('m-profile-tab-subscription');
    if (subTabPanel) subTabPanel.classList.add('hidden');
    
    // Show active panel
    document.getElementById('m-profile-tab-' + tabName).classList.remove('hidden');
    
    // Reset all tab button styles
    const tabButtons = document.querySelectorAll('[id^="m-tab-btn-"]');
    tabButtons.forEach(btn => {
        btn.classList.remove('bg-white', 'dark:bg-zinc-800', 'text-zinc-950', 'dark:text-zinc-50', 'shadow-sm');
        btn.classList.add('text-zinc-500', 'dark:text-zinc-400');
    });
    
    // Set active tab button style
    const activeBtn = document.getElementById('m-tab-btn-' + tabName);
    if (activeBtn) {
        activeBtn.classList.remove('text-zinc-500', 'dark:text-zinc-400');
        activeBtn.classList.add('bg-white', 'dark:bg-zinc-800', 'text-zinc-950', 'dark:text-zinc-50', 'shadow-sm');
    }
}

// Dialog Actions
function openDeleteModal() {
    document.getElementById('delete-modal').showModal();
}
function closeDeleteModal() {
    document.getElementById('delete-modal').close();
}

// Plan Purchase Logic
function purchasePlan(id, planName, planPrice) {
    document.getElementById('confirm-plan-name').innerText = planName;
    document.getElementById('confirm-plan-price').innerText = planPrice;
    
    const dialog = document.getElementById('dialog-confirm-purchase');
    dialog.showModal();
    
    const confirmBtn = document.getElementById('btn-confirm-purchase');
    confirmBtn.onclick = async function() {
        confirmBtn.disabled = true;
        confirmBtn.innerText = 'İşleniyor...';
        
        try {
            const formData = new FormData();
            formData.append('plan_id', id);
            
            const response = await fetch('<?= routeUrl("/abonelik-satinal") ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            dialog.close();
            
            if (data.success) {
                showToast(data.message || 'Abonelik talebi oluşturuldu.', 'success');
                setTimeout(() => switchTab('profile'), 1500);
            } else {
                showToast(data.message || 'Satın alma işlemi başarısız.', 'error');
            }
        } catch (err) {
            dialog.close();
            showToast('Sunucu ile iletişim kurulamadı.', 'error');
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerText = 'Satın Alımı Onayla';
        }
    };
}

// Cancel Pending Purchase Logic
function cancelSubscriptionPurchase(id, planName) {
    document.getElementById('cancel-plan-name').innerText = planName;
    
    const dialog = document.getElementById('dialog-cancel-purchase');
    dialog.showModal();
    
    const confirmBtn = document.getElementById('btn-confirm-cancel');
    confirmBtn.onclick = async function() {
        confirmBtn.disabled = true;
        confirmBtn.innerText = 'İşleniyor...';
        
        try {
            const formData = new FormData();
            formData.append('id', id);
            
            const response = await fetch('<?= routeUrl("/abonelik-sil") ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            dialog.close();
            
            if (data.success) {
                showToast(data.message || 'Satın alma işlemi iptal edildi.', 'success');
                setTimeout(() => switchTab('profile'), 1500);
            } else {
                showToast(data.message || 'Bir hata oluştu.', 'error');
            }
        } catch (err) {
            dialog.close();
            showToast('Sunucuyla iletişim kurulurken hata oluştu.', 'error');
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerText = 'Talebi İptal Et ve Sil';
        }
    };
}

// DOM Setup & Form listeners
(function() {
    // 1. Personal Profile Form Update Ajax
    const formProfile = document.getElementById('form-profile');
    if (formProfile) {
        formProfile.onsubmit = async function(e) {
            e.preventDefault();
            const submitBtn = formProfile.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Güncelleniyor...';
            
            try {
                const formData = new FormData(formProfile);
                const response = await fetch('<?= routeUrl("/profil-guncelle") ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message || 'Profil başarıyla güncellendi.', 'success');
                    
                    // Live UI Updates in DOM immediately
                    const newName = formProfile.querySelector('#name').value;
                    const newEmail = formProfile.querySelector('#email').value;
                    
                    document.querySelectorAll('.user-name-display').forEach(el => el.textContent = newName);
                    
                    const nameParts = newName.trim().split(' ');
                    let initials = '';
                    if (nameParts.length >= 2) {
                        initials = nameParts[0].substring(0, 1) + nameParts[nameParts.length - 1].substring(0, 1);
                    } else if (nameParts.length === 1 && nameParts[0] !== '') {
                        initials = nameParts[0].substring(0, 2);
                    }
                    initials = initials.toUpperCase();
                    
                    // Main shell topbar avatar
                    const shellAvatar = document.querySelector('.w-8.h-8.rounded-full');
                    if (shellAvatar) shellAvatar.textContent = initials;
                    
                    // Local page avatar
                    const pageAvatar = document.querySelector('.glass-card .w-16.h-16.rounded-full');
                    if (pageAvatar) pageAvatar.textContent = initials;
                    
                    // Local page name & email displays
                    const pageName = document.querySelector('.glass-card h3');
                    if (pageName) pageName.textContent = newName;
                    
                    const pageEmail = document.querySelector('.glass-card p');
                    if (pageEmail) pageEmail.textContent = newEmail;
                    
                    // Local page username field
                    const usernameDisp = document.getElementById('username_display');
                    if (usernameDisp) {
                        usernameDisp.value = newEmail.split('@')[0];
                    }
                } else {
                    showToast(data.message || 'Güncelleme başarısız.', 'error');
                }
            } catch (err) {
                showToast('Sunucuyla iletişim kurulurken hata oluştu.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        };
    }

    // 2. Security Form Change Password Ajax
    const formPassword = document.getElementById('form-password');
    if (formPassword) {
        formPassword.onsubmit = async function(e) {
            e.preventDefault();
            const submitBtn = formPassword.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Güncelleniyor...';
            
            try {
                const formData = new FormData(formPassword);
                const response = await fetch('<?= routeUrl("/sifre-degistir") ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message || 'Şifreniz başarıyla değiştirildi.', 'success');
                    formPassword.reset();
                } else {
                    showToast(data.message || 'Şifre değiştirilemedi.', 'error');
                }
            } catch (err) {
                showToast('Sunucuyla iletişim kurulurken hata oluştu.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        };
    }

    // 3. Danger Zone Delete Account Ajax
    const formDelete = document.getElementById('form-delete-account');
    if (formDelete) {
        formDelete.onsubmit = async function(e) {
            e.preventDefault();
            const submitBtn = formDelete.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Siliniyor...';
            
            try {
                const formData = new FormData(formDelete);
                const response = await fetch('<?= routeUrl("/hesap-sil") ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                closeDeleteModal();
                
                if (data.success) {
                    showToast('Hesabınız başarıyla silindi. Yönlendiriliyorsunuz...', 'success');
                    setTimeout(() => {
                        window.location.href = '<?= routeUrl("/logout") ?>';
                    }, 1500);
                } else {
                    showToast(data.message || 'Hesap silinemedi.', 'error');
                }
            } catch (err) {
                closeDeleteModal();
                showToast('Sunucuyla iletişim kurulurken hata oluştu.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        };
    }
})();
</script>

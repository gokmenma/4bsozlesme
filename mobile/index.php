<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../bootstrap.php';
global $db;

$isLoggedIn = isset($_SESSION['user_id']);

// Eğer kullanıcı giriş yapmışsa verileri çekelim
if ($isLoggedIn) {
    $tenant_id = $_SESSION['tenant_id'] ?? 0;
    $selectedPeriod = $_SESSION['active_wage_period'] ?? '2026-1';
    
    // 1. Dashboard istatistikleri
    $dashboardController = new DashboardController();
    $dashboardData = $dashboardController->index();
    $stats = $dashboardData['stats'];
    $recentPersonnel = $dashboardData['recent_personnel'];
    $eligiblePersonnel = $dashboardData['eligible_personnel'];
    
    // 2. Personel Listesi
    $stmt = $db->prepare("
        SELECT p.*, u.unvan, u.ucret, u.ogrenim, u.kidem_yili
        FROM personeller p 
        LEFT JOIN ucretler u ON p.ucret_id = u.id 
        WHERE p.deleted_at IS NULL AND p.tenant_id = ? 
        ORDER BY p.ad_soyad ASC
    ");
    $stmt->execute([$tenant_id]);
    $personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Ücret Tanımları Listesi (personel formları için)
    $stmt_ucret = $db->prepare("SELECT id, unvan, ucret, ogrenim, kidem_yili FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? AND donem = ? ORDER BY unvan ASC");
    $stmt_ucret->execute([$tenant_id, $selectedPeriod]);
    $ucretler = $stmt_ucret->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Kurum Listesi (Kurum Değiştirici için)
    $stmt_tenants = $db->prepare("SELECT t.id, t.name FROM tenants t INNER JOIN user_tenants ut ON t.id = ut.tenant_id WHERE ut.user_id = ?");
    $stmt_tenants->execute([$_SESSION['user_id']]);
    $tenants = $stmt_tenants->fetchAll(PDO::FETCH_ASSOC);
    
    // Aktif Kurum Adı
    $activeTenantName = 'Tanımsız Kurum';
    foreach ($tenants as $t) {
        if ($t['id'] == $tenant_id) {
            $activeTenantName = $t['name'];
            break;
        }
    }

    // 5. Özel Dilekçe Şablonu
    $defModel = new Definition();
    $tenant_settings = $defModel->getSettings($tenant_id);
    $custom_petition = $tenant_settings['custom_petition_template'] ?? '';

    // Abonelik & Deneme Süresi Kontrolü
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
}
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Sözleşme 4B Mobil Portal</title>
    
    <!-- PWA Capabilities & Fullscreen Meta Tags -->
    <link rel="manifest" href="<?php echo routeUrl('/mobile/manifest.json'); ?>" crossorigin="use-credentials">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Sözleşme 4B">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#09090b" media="(prefers-color-scheme: dark)">
    <link rel="apple-touch-icon" href="<?php echo routeUrl('/mobile/icon-192.png'); ?>">
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo routeUrl('/mobile/sw.js'); ?>')
                    .then(reg => console.log('PWA Service Worker registered.'))
                    .catch(err => console.log('Service Worker registration failed: ', err));
            });
        }
    </script>
    
    <!-- Theme Init -->
    <script src="<?php echo routeUrl('/assets/js/theme.js'); ?>"></script>
    
    <!-- Premium Google Fonts: Inter, Geist & Fira Code -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Geist:wght@100..900&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Quill snow theme CSS for document rendering -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <!-- Flatpickr Date Picker (Shadcn customized theme) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="<?php echo routeUrl('/assets/css/flatpickr.custom.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <!-- Tailwind CSS 4 -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    
    <!-- Tailwind CSS 4 Class-Based Dark Mode Configuration -->
    <style type="text/tailwindcss">
        @import "tailwindcss";
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
    
    <!-- Basecoat UI -->
    <link rel="stylesheet" href="https://unpkg.com/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <script src="https://unpkg.com/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>
    
    <link rel="stylesheet" href="<?php echo routeUrl('/mobile/style.css'); ?>?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body class="h-full flex items-center justify-center select-none">

    <!-- Toast Notification Banner -->
    <div id="toast" class="mobile-toast">
        <div id="toast-icon" class="text-emerald-500">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <div id="toast-message" class="text-[0.92rem] font-medium">İşlem başarıyla tamamlandı.</div>
    </div>

    <!-- Background shell frame container -->
    <div class="desktop-bg w-full h-full">
        
        <!-- Phone Frame Wrapper -->
        <div class="phone-frame">
            
            <!-- Phone Notch Speaker and Camera -->
            <div class="notch">
                <div class="notch-camera">
                    <div class="notch-camera-inner"></div>
                </div>
                <div class="notch-speaker"></div>
            </div>
            
            <!-- Screen content area -->
            <div class="screen-content">
                
                <!-- Bottom Sheet Backdrop -->
                <div id="sheet-backdrop" class="bottom-sheet-backdrop" onclick="closeAllSheets()"></div>

                <?php if (!$isLoggedIn): ?>
                
                <!-- MOBILE LOGIN SCREEN -->
                <div class="flex-1 flex flex-col justify-between p-7 pt-12 overflow-y-auto app-scroll bg-zinc-950">
                    <div class="my-auto space-y-8">
                        
                        <!-- Header & Logo -->
                        <div class="text-center flex flex-col items-center">
                            <div class="w-16 h-16 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-50 mb-5 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m7 17 10-10" />
                                    <path d="m13 17 4-4" opacity="0.5" />
                                </svg>
                            </div>
                            <h1 class="text-3xl font-extrabold tracking-tight text-white mb-1">
                                Sözleşme <span class="text-indigo-400">4B</span>
                            </h1>
                            <p class="text-[10px] text-zinc-400 font-bold uppercase tracking-widest">
                                Mobil Yönetim Portalı
                            </p>
                        </div>
                        
                        <!-- Login Form -->
                        <form id="mobileLoginForm" method="POST" action="<?= routeUrl('/login') ?>" class="space-y-5">
                            <div class="space-y-2">
                                <label for="username">E-Posta / Kullanıcı Adı</label>
                                <input class="mobile-input" type="text" id="username" name="username" placeholder="ornek@kurum.com" required>
                            </div>
                            <div class="space-y-2">
                                <label for="password">Şifre</label>
                                <input class="mobile-input" type="password" id="password" name="password" placeholder="••••••••" required>
                            </div>
                            <button type="submit" class="btn w-full justify-center gap-2 mt-4">
                                Giriş Yap
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                            </button>
                        </form>
                    </div>
                    
                    <div class="text-center text-xs text-zinc-500 font-medium">
                        Kurumsal Sözleşme & Yönetim Sistemi © <?= date('Y') ?>
                    </div>
                </div>

                <?php else: ?>

                <!-- MOBILE APP CONTAINER -->
                <div class="flex-1 flex flex-col h-full overflow-hidden">
                    
                    <!-- Refined App Status Topbar with dynamic Light/Dark mode and glassmorphism -->
                    <div class="pt-8 pb-3 px-6 flex items-center justify-between bg-white/80 dark:bg-zinc-950/80 border-b border-zinc-200/60 dark:border-zinc-800/60 backdrop-blur-md shadow-sm z-30 transition-all">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800/80 flex items-center justify-center font-bold text-zinc-900 dark:text-zinc-100 text-xs uppercase shadow-sm">
                                <?= mb_substr($_SESSION['user_name'], 0, 2) ?>
                            </div>
                            <div>
                                <!-- Active Page Title (Dynamic) -->
                                <h4 id="topbar-page-title" class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50 tracking-tight leading-none uppercase">Ana Sayfa</h4>
                                <p class="text-[8px] font-extrabold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mt-0.5"><?= htmlspecialchars($activeTenantName) ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <!-- Theme Switcher Button -->
                            <button id="theme-toggle-btn" onclick="toggleTheme()" class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800/80 flex items-center justify-center text-zinc-500 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer shadow-sm">
                                <!-- Light mode icon (visible in dark mode) -->
                                <svg class="hidden dark:block" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41m12.72-12.72l-1.41 1.41"/></svg>
                                <!-- Dark mode icon (visible in light mode) -->
                                <svg class="block dark:hidden" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                            </button>

                            <!-- Premium glow notification icon -->
                            <div class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800/80 flex items-center justify-center text-zinc-500 dark:text-zinc-400 relative active:scale-95 transition-all cursor-pointer shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9m10.3 13a3 3 0 0 1-5.6 0"/></svg>
                                <?php if (count($eligiblePersonnel) > 0): ?>
                                    <div class="w-2 h-2 bg-emerald-500 border border-white dark:border-zinc-950 rounded-full absolute -top-0.5 -right-0.5 shadow-sm animate-pulse"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Tab Content loaded via JavaScript -->
                    <div id="dynamic-content-wrapper" class="flex-1 overflow-y-auto app-scroll p-5 pb-28">
                        <!-- Loaded dynamically via Fetch -->
                    </div>

                    <!-- Pinned Immersive Bottom Navigation Bar with top-left & top-right border-radius -->
                    <div class="absolute bottom-0 left-0 right-0 h-20 bg-white/95 dark:bg-zinc-950/95 border-t border-zinc-200/60 dark:border-zinc-800/60 rounded-t-[24px] shadow-[0_-8px_30px_rgb(0,0,0,0.04)] backdrop-blur-xl flex items-center justify-around px-4 z-40 pb-2">
                        <button onclick="switchTab('home')" class="nav-btn flex flex-col items-center gap-1 text-zinc-950 dark:text-zinc-50 transition-all cursor-pointer relative py-2 px-3 rounded-xl active:scale-95" id="nav-home">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="nav-icon" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <span class="text-[9px] font-bold tracking-wider leading-none">Ana Sayfa</span>
                            <!-- Active indicator bulb -->
                            <div class="absolute bottom-1 w-4 h-0.5 bg-zinc-950 dark:bg-zinc-50 rounded-full" id="nav-indicator-home"></div>
                        </button>
                        
                        <button onclick="switchTab('personnel')" class="nav-btn flex flex-col items-center gap-1 text-zinc-400 dark:text-zinc-500 transition-all cursor-pointer relative py-2 px-3 rounded-xl active:scale-95" id="nav-personnel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="nav-icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span class="text-[9px] font-bold tracking-wider leading-none">Personel</span>
                            <div class="absolute bottom-1 w-4 h-0.5 bg-zinc-950 dark:bg-zinc-50 rounded-full hidden" id="nav-indicator-personnel"></div>
                        </button>
                        
                        <button onclick="switchTab('definitions')" class="nav-btn flex flex-col items-center gap-1 text-zinc-400 dark:text-zinc-500 transition-all cursor-pointer relative py-2 px-3 rounded-xl active:scale-95" id="nav-definitions">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="nav-icon" viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 6v6l4 2"/></svg>
                            <span class="text-[9px] font-bold tracking-wider leading-none">Ücretler</span>
                            <div class="absolute bottom-1 w-4 h-0.5 bg-zinc-950 dark:bg-zinc-50 rounded-full hidden" id="nav-indicator-definitions"></div>
                        </button>

                        <button onclick="switchTab('kanban')" class="nav-btn flex flex-col items-center gap-1 text-zinc-400 dark:text-zinc-500 transition-all cursor-pointer relative py-2 px-3 rounded-xl active:scale-95" id="nav-kanban">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="nav-icon" viewBox="0 0 24 24"><rect x="3" y="5" width="6" height="6" rx="1"/><path d="m5 12 2 2 4-4"/><path d="M13 5h8"/><path d="M13 9h8"/><path d="M13 13h8"/><rect x="3" y="15" width="6" height="6" rx="1"/></svg>
                            <span class="text-[9px] font-bold tracking-wider leading-none">Yapılacaklar</span>
                            <div class="absolute bottom-1 w-4 h-0.5 bg-zinc-950 dark:bg-zinc-50 rounded-full hidden" id="nav-indicator-kanban"></div>
                        </button>

                        <button onclick="openSheet('other-menu-sheet')" class="nav-btn flex flex-col items-center gap-1 text-zinc-400 dark:text-zinc-500 transition-all cursor-pointer relative py-2 px-3 rounded-xl active:scale-95" id="nav-other">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                            <span class="text-[9px] font-bold tracking-wider leading-none">Diğer</span>
                            <div class="absolute bottom-1 w-4 h-0.5 bg-zinc-950 dark:bg-zinc-50 rounded-full hidden" id="nav-indicator-other"></div>
                        </button>
                    </div>

                    <!-- MORE MENU (DIĞER) BOTTOM SHEET -->
                    <div id="other-menu-sheet" class="bottom-sheet flex flex-col max-h-[85%] text-zinc-950 dark:text-zinc-50">
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
                        
                        <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-6">
                            <!-- Premium User Profil Header -->
                            <div class="flex items-center gap-4 border-b border-zinc-200/60 dark:border-zinc-800/60 pb-5">
                                <div class="w-12 h-12 rounded-full bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800/80 flex items-center justify-center font-bold text-zinc-900 dark:text-zinc-100 text-lg uppercase shadow-sm">
                                    <?= mb_substr($_SESSION['user_name'], 0, 2) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50 tracking-tight leading-tight"><?= htmlspecialchars($_SESSION['user_name']) ?></h4>
                                    <p class="text-[10px] text-zinc-400 dark:text-zinc-505 font-extrabold uppercase tracking-wider mt-0.5"><?= htmlspecialchars($activeTenantName) ?></p>
                                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 tracking-tight mt-0.5"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
                                </div>
                                <a href="<?= routeUrl('/cikis') ?>" class="w-9 h-9 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 flex items-center justify-center text-rose-500 active:scale-95 transition-all cursor-pointer shadow-sm" title="Çıkış Yap">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/></svg>
                                </a>
                            </div>

                            <!-- List of Action Options -->
                            <div class="space-y-3">
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-extrabold uppercase tracking-wider block">YÖNETİM & AYARLAR</span>
                                
                                <div class="grid grid-cols-1 gap-2.5">
                                    <!-- Profil İşlemleri -->
                                    <button onclick="closeAllSheets(); switchTab('profile');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-900 dark:text-zinc-100 border border-zinc-200/40 dark:border-zinc-800/60 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-xs font-bold leading-tight">Profil & Hesap Ayarları</h5>
                                            <p class="text-[9px] text-zinc-400 dark:text-zinc-500 tracking-tight mt-0.5">Şifre işlemleri ve kurum değiştirici</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>

                                    <!-- Kurum Yönetimi (Superadmin only) -->
                                    <?php if ($isSuperAdmin): ?>
                                    <button onclick="closeAllSheets(); loadOtherSubpage('tenants');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500 border border-emerald-500/20 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-xs font-bold leading-tight text-emerald-500">Kurum Yönetimi (Superadmin)</h5>
                                            <p class="text-[9px] text-zinc-400 dark:text-zinc-500 tracking-tight mt-0.5">Sistemdeki kurumsal kiracıların listesi</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    <?php endif; ?>

                                    <!-- Kullanıcı Yönetimi -->
                                    <button onclick="closeAllSheets(); loadOtherSubpage('users');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-900 dark:text-zinc-100 border border-zinc-200/40 dark:border-zinc-800/60 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-xs font-bold leading-tight">Kullanıcı Yetkilendirme</h5>
                                            <p class="text-[9px] text-zinc-400 dark:text-zinc-500 tracking-tight mt-0.5">Sistem kullanıcıları, rolleri ve durumları</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>

                                    <!-- Abonelik & Paketler -->
                                    <button onclick="closeAllSheets(); loadOtherSubpage('subscription');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-900 dark:text-zinc-100 border border-zinc-200/40 dark:border-zinc-800/60 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-xs font-bold leading-tight">Abonelik & Limitler</h5>
                                            <p class="text-[9px] text-zinc-400 dark:text-zinc-500 tracking-tight mt-0.5">Kalan gün sayısı, deneme durumu ve limitler</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>


                                    <!-- Kurum Tanımlamaları -->
                                    <button onclick="closeAllSheets(); loadOtherSubpage('tanimlamalar');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-900 dark:text-zinc-100 border border-zinc-200/40 dark:border-zinc-800/60 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 8v4l3 3"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-xs font-bold leading-tight">Kurum Tanımlamaları</h5>
                                            <p class="text-[9px] text-zinc-400 dark:text-zinc-500 tracking-tight mt-0.5">Kurum & yetkili bilgileri, katsayılar ve bütçe dönemi</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>

                                    <!-- Sistem Ayarları -->
                                    <button onclick="closeAllSheets(); loadOtherSubpage('settings');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-900 dark:text-zinc-100 border border-zinc-200/40 dark:border-zinc-800/60 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-xs font-bold leading-tight">Sistem & SMS Ayarları</h5>
                                            <p class="text-[9px] text-zinc-400 dark:text-zinc-505 tracking-tight mt-0.5">Bildirim süreleri, SMS API bilgileri ve entegrasyonlar</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 1. PERSONNEL DETAIL BOTTOM SHEET -->
                    <div id="detail-sheet" class="bottom-sheet flex flex-col max-h-[82%]">
                        <!-- Handle for native drag look -->
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
                        
                        <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-6">
                            <!-- Detail Header -->
                            <div class="text-center relative">
                                <div id="detail-gender-icon" class="w-14 h-14 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mx-auto mb-2 border border-indigo-500/20">
                                    <!-- Dynamic Gender Avatar -->
                                </div>
                                <h3 id="detail-name" class="text-base font-extrabold text-white leading-tight">Mehmet Yılmaz</h3>
                                <p id="detail-unvan" class="text-xs text-zinc-400 font-bold mt-1 uppercase tracking-wider">-</p>
                            </div>

                            <!-- Info Grid -->
                            <div class="glass-card p-4 rounded-lg space-y-3.5">
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-800/60">
                                    <span class="font-bold text-zinc-400">T.C. Kimlik No:</span>
                                    <div class="flex items-center gap-1.5">
                                        <span id="detail-tc" class="font-extrabold text-zinc-800 dark:text-zinc-200">12345678901</span>
                                        <button onclick="toggleTcMask()" class="text-zinc-400 active:scale-90 transition-all cursor-pointer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-800/60">
                                    <span class="font-bold text-zinc-400">Telefon:</span>
                                    <span id="detail-telefon" class="font-extrabold text-zinc-800 dark:text-zinc-200">-</span>
                                </div>
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-800/60">
                                    <span class="font-bold text-zinc-400">Brüt Maaş Matrahı:</span>
                                    <span id="detail-ucret" class="font-extrabold text-emerald-400">56.000,00 TL</span>
                                </div>
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-800/60">
                                    <span class="font-bold text-zinc-400">Öğrenim Durumu:</span>
                                    <span id="detail-ogrenim" class="font-extrabold text-zinc-800 dark:text-zinc-200">Lisans</span>
                                </div>
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-800/60">
                                    <span class="font-bold text-zinc-400">Kıdem Grubu:</span>
                                    <span id="detail-kidem" class="font-extrabold text-zinc-800 dark:text-zinc-200">3-5 Yıl</span>
                                </div>
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-800/60">
                                    <span class="font-bold text-zinc-400">Meslek Kodu:</span>
                                    <span id="detail-meslek" class="font-extrabold text-zinc-800 dark:text-zinc-200">-</span>
                                </div>
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-800/60">
                                    <span class="font-bold text-zinc-400">Göreve Başlama:</span>
                                    <span id="detail-baslama" class="font-extrabold text-zinc-800 dark:text-zinc-200">01.01.2025</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-zinc-400">Durum:</span>
                                    <span id="detail-durum-badge" class="badge-aktif px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Aktif</span>
                                </div>
                            </div>

                            <!-- Premium Action Layout Section -->
                            <div class="space-y-4 pt-2">
                                <!-- Section 1: Documents -->
                                <div class="space-y-2">
                                    <span class="text-[9px] text-zinc-400 dark:text-zinc-500 font-extrabold uppercase tracking-wider block">YASAL BELGELER & İNDİRME</span>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button id="btn-preview-contract" class="py-3 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 active:scale-95 transition-all shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            Sözleşme Önizle
                                        </button>
                                        
                                        <button id="btn-preview-petition" class="py-3 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 active:scale-95 transition-all shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                            Dilekçe Önizle
                                        </button>
                                    </div>
                                    <button id="btn-download-word" class="w-full py-3 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800/80 hover:bg-zinc-200 dark:hover:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-xl font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 active:scale-95 transition-all shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5-5 5 5m-5-5v12"/></svg>
                                        Resmi Word Dosyası İndir (.docx)
                                    </button>
                                </div>
                                
                                <!-- Section 2: Management -->
                                <div class="space-y-2">
                                    <span class="text-[9px] text-zinc-400 dark:text-zinc-500 font-extrabold uppercase tracking-wider block">KART YÖNETİMİ</span>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button id="btn-edit-personnel" class="py-3 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800/80 hover:bg-zinc-200 dark:hover:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-xl font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 active:scale-95 transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                            Kartı Düzenle
                                        </button>
                                        
                                        <button id="btn-delete-personnel" class="py-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-900/30 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-950/30 rounded-xl font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 active:scale-95 transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2M10 11v6m4-16v6"/></svg>
                                            Çalışanı Sil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. ADD/EDIT FORM BOTTOM SHEET -->
                    <div id="form-sheet" class="bottom-sheet flex flex-col max-h-[88%]">
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
                        
                        <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
                            <h3 id="form-title" class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">Yeni Personel Ekle</h3>
                            
                            <form id="personnelForm" class="space-y-4">
                                <input type="hidden" id="form-p-id" name="id" value="">
                                
                                <div class="space-y-1.5">
                                    <label for="form-ad">Ad Soyad*</label>
                                    <input class="mobile-input" type="text" id="form-ad" name="ad_soyad" required placeholder="Ad ve soyad girin">
                                </div>

                                <div class="space-y-1.5">
                                    <label for="form-tc">TC Kimlik No*</label>
                                    <input class="mobile-input" type="text" id="form-tc" name="tc_kimlik" required minlength="11" maxlength="11" placeholder="11 haneli TC no">
                                </div>

                                <div class="space-y-1.5">
                                    <label for="form-ucret-select">Unvan & Ücret Seçimi*</label>
                                    <select class="mobile-input" id="form-ucret-select" name="ucret_id" required>
                                        <option value="" disabled selected>Ücret Matrahı Seçin</option>
                                        <?php foreach ($ucretler as $u): ?>
                                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['unvan']) ?> - <?= htmlspecialchars($u['ogrenim']) ?> (<?= htmlspecialchars($u['kidem_yili']) ?>) - <?= number_format($u['ucret'] ?? 0, 2, ',', '.') ?> TL</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label for="form-cinsiyet">Cinsiyet</label>
                                        <select class="mobile-input" id="form-cinsiyet" name="cinsiyet">
                                            <option value="erkek">Erkek</option>
                                            <option value="kadin">Kadın</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label for="form-durum">Durum</label>
                                        <select class="mobile-input" id="form-durum" name="durum">
                                            <option value="aktif">Aktif</option>
                                            <option value="pasif">Pasif</option>
                                            <option value="dilekce_alindi">Dilekçe Alındı</option>
                                            <option value="kadroya_gecti">Kadroya Geçti</option>
                                            <option value="kadroya_gecmeyecek">Kadroya Geçmeyecek</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label for="form-baslama">Giriş Tarihi*</label>
                                        <input class="mobile-input" type="text" id="form-baslama" name="goreve_baslama_tarihi" placeholder="Seçiniz..." required>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label for="form-meslek">Meslek Kodu</label>
                                        <input class="mobile-input" type="text" id="form-meslek" name="meslek_kodu" placeholder="Örn: 2512.02">
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label for="form-telefon">Telefon Numarası</label>
                                    <input class="mobile-input" type="tel" id="form-telefon" name="telefon" placeholder="Örn: 05301234567">
                                </div>

                                <div class="flex gap-3 mt-4">
                                    <button type="button" class="btn-outline flex-1 justify-center" onclick="closeAllSheets()">İptal</button>
                                    <button type="submit" class="btn flex-1 justify-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                        Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 3. CONTRACT PREVIEW FULL MODAL OVERLAY -->
                    <div id="preview-modal" class="absolute inset-0 bg-zinc-100 dark:bg-zinc-950 z-[999] flex flex-col hidden">
                        <!-- Top Navigation of Preview -->
                        <div class="pt-10 pb-4 px-6 flex items-center justify-between bg-white dark:bg-[#18181b] border-b border-zinc-200 dark:border-zinc-800">
                            <button onclick="closePreviewModal()" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 cursor-pointer active:scale-90 transition-all flex items-center gap-1 font-bold text-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
                                Geri
                            </button>
                            <h3 id="preview-title" class="text-[11px] font-extrabold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">Belge Önizleme</h3>
                            <div class="flex items-center gap-2">
                                <button id="btn-preview-print" class="bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-800 text-zinc-900 dark:text-zinc-100 px-3 py-1.5 rounded-lg font-bold text-[10px] uppercase tracking-wider flex items-center gap-1 shadow-sm cursor-pointer active:scale-95 transition-all">
                                    Yazdır
                                </button>
                                <button id="btn-preview-download" class="bg-zinc-900 dark:bg-zinc-100 text-zinc-50 dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 px-3 py-1.5 rounded-lg font-bold text-[10px] uppercase tracking-wider flex items-center gap-1 shadow-sm cursor-pointer active:scale-95 transition-all">
                                    İndir
                                </button>
                            </div>
                        </div>
                        
                        <!-- Content of Document filled dynamically inside simulated paper -->
                        <div class="flex-1 overflow-y-auto app-scroll document-preview-wrapper">
                            <div id="preview-content-area" class="document-preview-page">
                                <!-- Dynamic preview HTML -->
                            </div>
                        </div>
                    </div>

                    <!-- 4. EXCEL IMPORT BOTTOM SHEET -->
                    <div id="import-sheet" class="bottom-sheet flex flex-col max-h-[75%]">
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
                        
                        <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
                            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">Toplu Excel Yükleme</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">Toplu personel eklemek için hazırladığınız <strong>CSV</strong> şablon dosyasını aşağıdaki alandan yükleyebilirsiniz.</p>
                            
                            <div class="p-4 bg-zinc-100 dark:bg-zinc-955 border border-zinc-200 dark:border-zinc-800 rounded-md space-y-2">
                                <label>1. Adım: Şablonu İndirin</label>
                                <a href="<?= routeUrl('/personel-sample-template') ?>" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5-5 5 5m-5-5v12"/></svg>
                                    Örnek CSV Şablonu İndir
                                </a>
                            </div>

                            <div class="space-y-2">
                                <label for="import-file-input">2. Adım: CSV Dosyası Seçin</label>
                                <input type="file" id="import-file-input" accept=".csv" class="mobile-input p-3 block text-xs">
                            </div>

                            <div class="flex gap-3 mt-4">
                                <button type="button" class="btn-outline flex-1 justify-center" onclick="closeAllSheets()">İptal</button>
                                <button onclick="handleExcelUpload()" class="btn flex-1 justify-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5-5 5 5m-5-5v12"/></svg>
                                    Yüklemeyi Tamamla
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 5. DEFINITION ADD/EDIT FORM BOTTOM SHEET -->
                    <div id="def-form-sheet" class="bottom-sheet flex flex-col max-h-[88%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
                        
                        <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
                            <h3 id="def-form-title" class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">Yeni Ücret Tanımı Ekle</h3>
                            
                            <form id="definitionForm" class="space-y-4">
                                <input type="hidden" id="form-def-id" name="id" value="">
                                <input type="hidden" id="form-def-donem" name="donem" value="">
                                
                                <div class="space-y-1.5">
                                    <label for="form-def-unvan">Unvan*</label>
                                    <input class="mobile-input" type="text" id="form-def-unvan" name="unvan" required placeholder="Örn: Büro Personeli, Mühendis">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label for="form-def-ogrenim">Öğrenim Durumu*</label>
                                        <select class="mobile-input" id="form-def-ogrenim" name="ogrenim" required>
                                            <option value="Lise" selected>Lise</option>
                                            <option value="Önlisans">Önlisans</option>
                                            <option value="Lisans">Lisans</option>
                                            <option value="Yüksek Lisans">Yüksek Lisans</option>
                                            <option value="Doktora">Doktora</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label for="form-def-kidem">Kıdem Grubu*</label>
                                        <select class="mobile-input" id="form-def-kidem" name="kidem_yili" required>
                                            <option value="0-5 Yıl (Dahil)" selected>0-5 Yıl (Dahil)</option>
                                            <option value="5-10 Yıl (Dahil)">5-10 Yıl (Dahil)</option>
                                            <option value="10-15 Yıl (Dahil)">10-15 Yıl (Dahil)</option>
                                            <option value="15-20 Yıl (Dahil)">15-20 Yıl (Dahil)</option>
                                            <option value="20 üzeri">20 üzeri</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label for="form-def-ucret">Aylık Brüt Ücret (₺)*</label>
                                    <input class="mobile-input" type="text" id="form-def-ucret" name="ucret" required placeholder="0,00">
                                </div>

                                <div class="flex gap-3 mt-4">
                                    <button type="button" class="btn-outline flex-1 justify-center" onclick="closeAllSheets()">İptal</button>
                                    <button type="submit" class="btn flex-1 justify-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                        Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 6. ADVANCED FILTER BOTTOM SHEET FOR DEFINITIONS -->
                    <div id="def-filter-sheet" class="bottom-sheet flex flex-col max-h-[82%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
                        
                        <div class="overflow-y-auto app-scroll px-6 pb-36 flex-1 space-y-5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Gelişmiş Filtreleme</h3>
                                <button onclick="clearAllDefFilters()" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Temizle</button>
                            </div>
                            
                            <div class="space-y-4">
                                <!-- Öğrenim Durumu Filtresi (Multiple) -->
                                <div class="space-y-1.5">
                                    <label for="filter-def-ogrenim">Öğrenim Durumu (Çoklu Seçim)</label>
                                    <div class="relative">
                                        <select id="filter-def-ogrenim" class="mobile-input" multiple>
                                            <option value="">Tüm Öğrenim Durumları</option>
                                            <option value="Lise">Lise</option>
                                            <option value="Önlisans">Önlisans</option>
                                            <option value="Lisans">Lisans</option>
                                            <option value="Yüksek Lisans">Yüksek Lisans</option>
                                            <option value="Doktora">Doktora</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Kıdem Grubu Filtresi (Multiple) -->
                                <div class="space-y-1.5">
                                    <label for="filter-def-kidem">Kıdem Grubu (Çoklu Seçim)</label>
                                    <div class="relative">
                                        <select id="filter-def-kidem" class="mobile-input" multiple>
                                            <option value="">Tümü</option>
                                            <option value="0-5 Yıl (Dahil)">0-5 Yıl (Dahil)</option>
                                            <option value="5-10 Yıl (Dahil)">5-10 Yıl (Dahil)</option>
                                            <option value="10-15 Yıl (Dahil)">10-15 Yıl (Dahil)</option>
                                            <option value="15-20 Yıl (Dahil)">15-20 Yıl (Dahil)</option>
                                            <option value="20 üzeri">20 üzeri</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Aylık Ücret Filtresi -->
                                <div class="space-y-1.5">
                                    <label for="filter-def-ucret-val">Aylık Brüt Ücret</label>
                                    <div class="grid grid-cols-5 gap-2">
                                        <div class="col-span-2">
                                            <select id="filter-def-ucret-op" class="mobile-input">
                                                <option value="equals" selected>Eşittir (=)</option>
                                                <option value="gt">Büyüktür (>)</option>
                                                <option value="lt">Küçüktür (&lt;)</option>
                                                <option value="gte">Büyük Eşit (>=)</option>
                                                <option value="lte">Küçük Eşit (&lt;=)</option>
                                            </select>
                                        </div>
                                        <div class="col-span-3">
                                            <input type="number" id="filter-def-ucret-val" class="mobile-input text-xs font-semibold" placeholder="Ücret girin (₺)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex gap-3 mt-4">
                                <button type="button" class="btn-outline flex-1 justify-center" onclick="closeAllSheets()">İptal</button>
                                <button onclick="applyDefinitionFilters(); closeAllSheets();" class="btn flex-1 justify-center">
                                    Filtreyi Uygula
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 7. ADVANCED SORT BOTTOM SHEET FOR DEFINITIONS -->
                    <div id="def-sort-sheet" class="bottom-sheet flex flex-col max-h-[82%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
                        
                        <div class="overflow-y-auto app-scroll px-6 pb-24 flex-1 space-y-5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Sıralama Seçenekleri</h3>
                            </div>
                            
                            <div class="space-y-1">
                                <button onclick="applyDefSorting('title_asc')" class="def-sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="title_asc">
                                    <span>Unvana göre (A'dan Z'ye)</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </button>
                                <button onclick="applyDefSorting('title_desc')" class="def-sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="title_desc">
                                    <span>Unvana göre (Z'den A'ya)</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </button>
                                
                                <div class="h-px bg-zinc-100 dark:bg-zinc-800 my-2"></div>
                                
                                <button onclick="applyDefSorting('wage_asc')" class="def-sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="wage_asc">
                                    <span>Aylık Brüt Ücrete göre (Artan)</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </button>
                                <button onclick="applyDefSorting('wage_desc')" class="def-sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="wage_desc">
                                    <span>Aylık Brüt Ücrete göre (Azalan)</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </button>
                                
                                <div class="h-px bg-zinc-100 dark:bg-zinc-800 my-2"></div>
                                
                                <button onclick="applyDefSorting('edu_asc')" class="def-sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="edu_asc">
                                    <span>Öğrenim Durumuna göre (A'dan Z'ye)</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </button>
                                <button onclick="applyDefSorting('edu_desc')" class="def-sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="edu_desc">
                                    <span>Öğrenim Durumuna göre (Z'den A'ya)</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 8. PERIOD COPY BOTTOM SHEET FOR DEFINITIONS -->
                    <div id="def-copy-sheet" class="bottom-sheet flex flex-col max-h-[85%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
                        
                        <div class="overflow-y-auto app-scroll px-6 pb-24 flex-1 space-y-5">
                            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">Dönem Kopyala & Toplu Zam</h3>
                            
                            <!-- Premium warning callout -->
                            <div class="p-4 bg-amber-500/10 border border-amber-500/25 rounded-xl space-y-2">
                                <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400 font-extrabold text-xs uppercase tracking-wider">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                                    Önemli Uyarı ve Risk Bilgisi
                                </div>
                                <p class="text-[11px] text-amber-700 dark:text-amber-300/90 leading-relaxed font-medium">
                                    Bu işlem, aktif dönemdeki tüm ücret tanımlarını girdiğiniz zam oranıyla (% artış) çarparak <strong>belirleyeceğiniz yeni bir döneme kopyalar</strong>.
                                    Eski döneme ait ücret tanımları ve o dönemdeki personellerin geçmiş sözleşmeleri <strong>kesinlikle korunur ve zarar görmez</strong>.
                                </p>
                            </div>

                            <form id="defCopyForm" class="space-y-4" onsubmit="handlePeriodCopy(event)">
                                <div class="space-y-1.5">
                                    <label>Kaynak Dönem (Aktif)</label>
                                    <input class="mobile-input bg-zinc-50 dark:bg-zinc-950 font-bold" type="text" id="copy-from-donem" readonly value="<?= htmlspecialchars($selectedPeriod ?? '2026-1') ?>">
                                </div>

                                <div class="space-y-1.5">
                                    <label for="copy-to-donem">Hedef Dönem (Yeni)*</label>
                                    <input class="mobile-input font-bold" type="text" id="copy-to-donem" required placeholder="Örn: 2026-2">
                                    <p class="text-[9px] text-zinc-500 font-semibold mt-0.5">Yeni dönemin adı benzersiz olmalıdır (Örn: 2026-2, 2027-1).</p>
                                </div>

                                <div class="space-y-1.5">
                                    <label for="copy-raise-percent">Zam Oranı (%)*</label>
                                    <input class="mobile-input font-bold" type="number" step="0.01" id="copy-raise-percent" required placeholder="Örn: 25.5">
                                    <p class="text-[9px] text-zinc-500 font-semibold mt-0.5">Tüm ücretler bu yüzde oranında artırılarak yeni döneme aktarılır.</p>
                                </div>

                                <div class="flex gap-3 mt-6">
                                    <button type="button" class="btn-outline flex-1 justify-center" onclick="closeAllSheets()">İptal</button>
                                    <button type="submit" class="btn flex-1 justify-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                                        Aktar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 9. PETITION STATUS CONFIRM BOTTOM SHEET -->
                    <div id="petition-confirm-sheet" class="bottom-sheet flex flex-col max-h-[50%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 text-zinc-950 dark:text-zinc-50" style="z-index: 1001 !important;">
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
                        <div class="px-6 pb-8 pt-2 flex-1 flex flex-col justify-between space-y-5">
                            <div class="text-center space-y-2">
                                <div class="w-12 h-12 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-500 mx-auto border border-indigo-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                </div>
                                <h3 class="text-sm font-extrabold tracking-tight">Durum Güncelleme Onayı</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed font-semibold">
                                    <span id="petition-confirm-name" class="font-extrabold text-zinc-900 dark:text-zinc-100">Personel</span> isimli çalışanın durumunu <strong>"Dilekçe Alındı"</strong> olarak güncellemek ister misiniz?
                                </p>
                            </div>
                            
                            <div class="flex gap-3">
                                <button type="button" id="btn-petition-confirm-no" onclick="closePetitionConfirmSheet(); printMobileDocument();" class="btn-outline flex-1 justify-center py-3 text-xs font-bold">Hayır, Sadece Yazdır</button>
                                <button type="button" id="btn-petition-confirm-yes" onclick="updatePetitionStatusAndPrint()" class="btn flex-1 justify-center py-3 text-xs font-bold gap-1">Evet, Güncelle ve Yazdır</button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Premium Native JavaScript Mechanics -->
    <script>
        // Global Fetch Interceptor to catch Session Expiration (401 Unauthorized) and redirect to login
        (function() {
            const originalFetch = window.fetch;
            window.fetch = async function(...args) {
                try {
                    const response = await originalFetch(...args);
                    if (response.status === 401) {
                        // Clear any local tab storage to prevent trying to load dynamic tabs after logging in
                        localStorage.removeItem('last_active_tab');
                        localStorage.removeItem('last_active_subpage');
                        
                        // Session expired, reload the page to display the login screen
                        window.location.reload();
                    }
                    return response;
                } catch (error) {
                    console.error('Fetch error:', error);
                    throw error;
                }
            };
        })();

        // Global variables holding current page state
        let currentTab = 'home';
        let selectedPersonnelCard = null;
        let isTcMasked = true;
        let currentPetitionPersonnel = null;

        function openPetitionConfirmSheet(personnelId, name) {
            document.getElementById('petition-confirm-name').innerText = name;
            
            // Promote sheet-backdrop z-index so it stands above preview-modal (z-index 999)
            const backdrop = document.getElementById('sheet-backdrop');
            if (backdrop) {
                backdrop.style.zIndex = '1000';
            }
            
            openSheet('petition-confirm-sheet');
        }

        function closePetitionConfirmSheet() {
            closeAllSheets();
            setTimeout(() => {
                const backdrop = document.getElementById('sheet-backdrop');
                if (backdrop) {
                    backdrop.style.zIndex = '';
                }
            }, 350);
        }

        function updatePetitionStatusAndPrint() {
            if (!currentPetitionPersonnel) return;
            
            const btn = document.getElementById('btn-petition-confirm-yes');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Güncelleniyor...';
            
            const basePath = '<?php echo appBasePath(); ?>';
            const url = basePath + '/personel-guncelle';
            
            const formData = new FormData();
            formData.append('id', currentPetitionPersonnel.id);
            formData.append('tc_kimlik', currentPetitionPersonnel.tc);
            formData.append('ad_soyad', currentPetitionPersonnel.name);
            formData.append('ucret_id', currentPetitionPersonnel.ucret_id);
            formData.append('durum', 'dilekce_alindi');
            formData.append('goreve_baslama_tarihi', currentPetitionPersonnel.baslama);
            formData.append('telefon', currentPetitionPersonnel.telefon);
            formData.append('meslek_kodu', currentPetitionPersonnel.meslek);
            formData.append('cinsiyet', currentPetitionPersonnel.cinsiyet);
            formData.append('ajax', '1');

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                if (data.success) {
                    showToast('Personel durumu "Dilekçe Alındı" olarak güncellendi.');
                    
                    // Instantly update the DOM card's attribute
                    const card = document.querySelector(`.personnel-item-card[data-id="${currentPetitionPersonnel.id}"]`);
                    if (card) {
                        card.setAttribute('data-durum', 'dilekce_alindi');
                        const detailBadge = document.getElementById('detail-durum-badge');
                        if (detailBadge) {
                            detailBadge.className = 'px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-blue-500/10 text-blue-500 border border-blue-500/20';
                            detailBadge.innerText = 'Dilekçe Alındı';
                        }
                    }
                    
                    if (currentTab === 'personnel') {
                        switchTab('personnel');
                    } else if (currentTab === 'home') {
                        switchTab('home');
                    }
                    
                    closePetitionConfirmSheet();
                    printMobileDocument();
                } else {
                    showToast(data.error || 'Durum güncellenirken bir hata oluştu.', 'error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                showToast('Bağlantı hatası oluştu.', 'error');
            });
        }

        // Iframe-based high fidelity print logic specifically for mobile viewports
        function printMobileDocument() {
            const contentArea = document.getElementById('preview-content-area');
            if (!contentArea) return;
            
            // Create a temporary hidden iframe
            const iframe = document.createElement('iframe');
            iframe.name = 'print_iframe';
            iframe.style.position = 'fixed';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.style.left = '-9999px';
            document.body.appendChild(iframe);
            
            const docType = document.body.getAttribute('data-doc-type') || 'dilekce';
            const doc = iframe.contentWindow.document;
            
            doc.write('<!DOCTYPE html><html><head><title>Belge Yazdır</title>');
            doc.write('<style>');
            doc.write('@page { size: A4 portrait; margin: 0 !important; }');
            doc.write('body { margin: 0 !important; padding: 0 !important; background: white !important; color: black !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }');
            
            if (docType === 'dilekce') {
                doc.write('body { font-family: "Times New Roman", Times, serif !important; padding: 3.5cm 2.5cm 2.5cm 2.5cm !important; }');
                doc.write('* { font-family: "Times New Roman", Times, serif !important; font-size: 11pt !important; line-height: 1.6 !important; color: black !important; }');
                doc.write('.ql-editor p { padding-left: 2.3cm !important; padding-right: 2.3cm !important; margin-bottom: 8px !important; text-align: justify !important; }');
                doc.write('.ql-editor p:nth-child(1), .ql-editor p:nth-child(2), .ql-editor p:nth-child(3) { padding-left: 0 !important; padding-right: 0 !important; }');
            } else {
                // Contract (sözleşme)
                doc.write('body { font-family: "Times New Roman", Times, serif !important; padding: 2.5cm 2cm 2.5cm 2cm !important; }');
                doc.write('* { font-family: "Times New Roman", Times, serif !important; font-size: 10.5pt !important; line-height: 1.6 !important; color: black !important; }');
                
                const hasBorder = contentArea.classList.contains('has-border');
                if (hasBorder) {
                    doc.write('.document-preview-page { border: 3px double #000000 !important; padding: 10px !important; }');
                }
            }
            doc.write('</style></head><body>');
            doc.write('<div class="document-preview-page">');
            doc.write(contentArea.innerHTML);
            doc.write('</div>');
            doc.write('</body></html>');
            doc.close();
            
            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 1000);
            }, 300);
        }

        // Dynamic simulated A4 zoom scaling to perfectly fit mobile viewports like a real PDF viewer
        function adjustPreviewZoom() {
            const wrapper = document.querySelector('.document-preview-wrapper');
            const page = document.getElementById('preview-content-area');
            if (!wrapper || !page) return;
            
            const wrapperWidth = wrapper.clientWidth;
            const targetWidth = 794; // Fixed standard A4 width
            
            if (wrapperWidth < targetWidth) {
                const zoomFactor = (wrapperWidth - 16) / targetWidth;
                
                // Use scale transform for full cross-browser mobile & iOS Safari support instead of zoom
                page.style.transform = `scale(${zoomFactor})`;
                page.style.transformOrigin = 'top center';
                
                // Adjust layout footprint height to match scaled height and prevent bottom empty spaces
                const originalHeight = page.offsetHeight || 1123;
                const scaledHeight = originalHeight * zoomFactor;
                page.style.marginBottom = `-${originalHeight - scaledHeight}px`;
            } else {
                page.style.transform = 'none';
                page.style.transformOrigin = 'top center';
                page.style.marginBottom = '0';
            }
        }
        window.addEventListener('resize', adjustPreviewZoom);

        // Swiping gesture handler for personnel list items (pure standard PointerEvents)
        function initSwipeActions() {
            const containers = document.querySelectorAll('.swipe-container');
            containers.forEach(container => {
                const front = container.querySelector('.swipe-front');
                if (!front) return;
                
                let startX = 0;
                let currentX = 0;
                let isDragging = false;
                let hasDragged = false;
                let originalX = 0;

                const getTransformX = () => {
                    const style = front.style.transform;
                    if (!style) return 0;
                    const match = style.match(/translateX\((.*?)(px|%)\)/);
                    return match ? parseFloat(match[1]) : 0;
                };

                front.addEventListener('pointerdown', (e) => {
                    isDragging = true;
                    hasDragged = false;
                    startX = e.clientX;
                    originalX = getTransformX();
                    front.style.transition = 'none';
                    front.setPointerCapture(e.pointerId);

                    // Close any other open swipe containers
                    document.querySelectorAll('.swipe-front').forEach(otherFront => {
                        if (otherFront !== front) {
                            const otherStyle = otherFront.style.transform;
                            if (otherStyle && otherStyle !== 'translateX(0px)') {
                                otherFront.style.transition = 'transform 0.25s cubic-bezier(0.16, 1, 0.3, 1)';
                                otherFront.style.transform = 'translateX(0px)';
                                otherFront.parentElement.classList.remove('swipe-open-right', 'swipe-open-left');
                            }
                        }
                    });
                });

                front.addEventListener('pointermove', (e) => {
                    if (!isDragging) return;
                    const deltaX = e.clientX - startX;
                    if (Math.abs(deltaX) > 8) {
                        hasDragged = true;
                    }
                    currentX = originalX + deltaX;

                    // Disable swiping right if this is a definition item card
                    if (container.classList.contains('definition-item-card')) {
                        if (currentX > 0) currentX = 0;
                    }

                    // Elastic bound limits matching the full height w-12 columns with pl-4 and gap-4 (total 130px right, -56px left)
                    if (currentX > 140) currentX = 140 + (currentX - 140) * 0.15;
                    if (currentX < -70) currentX = -70 + (currentX + 70) * 0.15;

                    front.style.transform = `translateX(${currentX}px)`;
                });

                const handleEnd = (e) => {
                    if (!isDragging) return;
                    isDragging = false;
                    front.style.transition = 'transform 0.25s cubic-bezier(0.16, 1, 0.3, 1)';
                    
                    const finalX = getTransformX();
                    
                    if (finalX > 45) {
                        if (container.classList.contains('definition-item-card')) {
                            front.style.transform = 'translateX(0px)';
                            container.classList.remove('swipe-open-right', 'swipe-open-left');
                        } else {
                            // Snap right (reveal Left buttons: Sözleşme & Dilekçe - total 130px wide)
                            front.style.transform = 'translateX(130px)';
                            container.classList.add('swipe-open-right');
                            container.classList.remove('swipe-open-left');
                        }
                    } else if (finalX < -30) {
                        // Snap left (reveal Right button: Sil - 56px wide)
                        front.style.transform = 'translateX(-56px)';
                        container.classList.add('swipe-open-left');
                        container.classList.remove('swipe-open-right');
                    } else {
                        // Reset
                        front.style.transform = 'translateX(0px)';
                        container.classList.remove('swipe-open-right', 'swipe-open-left');
                    }
                };

                front.addEventListener('pointerup', handleEnd);
                front.addEventListener('pointercancel', handleEnd);

                // Safe click shield: prevent opening Edit Modal if dragged
                front.addEventListener('click', (e) => {
                    if (hasDragged || originalX !== 0) {
                        e.stopPropagation();
                        e.preventDefault();
                        
                        // If it was already open and clicked, snap it back to closed
                        if (originalX !== 0 && !hasDragged) {
                            front.style.transition = 'transform 0.25s cubic-bezier(0.16, 1, 0.3, 1)';
                            front.style.transform = 'translateX(0px)';
                            container.classList.remove('swipe-open-right', 'swipe-open-left');
                        }
                    }
                }, true); // Use capture phase to intercept click handlers immediately
            });
        }

        // Switch bottom navigation tabs smoothly and fetch content dynamically
        async function switchTab(tabName, callback = null, params = {}) {
            // Reset bottom navigation active indicators
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('text-zinc-950', 'dark:text-zinc-50');
                btn.classList.add('text-zinc-400', 'dark:text-zinc-500');
            });
            document.querySelectorAll('[id^="nav-indicator-"]').forEach(ind => ind.classList.add('hidden'));

            // Set active indicators
            const activeBtn = document.getElementById('nav-' + tabName);
            if (activeBtn) {
                activeBtn.classList.add('text-zinc-950', 'dark:text-zinc-50');
                activeBtn.classList.remove('text-zinc-400', 'dark:text-zinc-500');
            }
            const activeInd = document.getElementById('nav-indicator-' + tabName);
            if (activeInd) {
                activeInd.classList.remove('hidden');
            }

            currentTab = tabName;
            
            // Persist active state in localStorage
            localStorage.setItem('last_active_tab', tabName);
            if (tabName !== 'other') {
                localStorage.removeItem('last_active_subpage');
            }

            // Update topbar title dynamically
            const topbarTitle = document.getElementById('topbar-page-title');
            if (topbarTitle) {
                let displayTitle = 'Ana Sayfa';
                if (tabName === 'personnel') displayTitle = 'Personel Listesi';
                else if (tabName === 'definitions') displayTitle = 'Ücret Tanımları';
                else if (tabName === 'kanban') displayTitle = 'Yapılacaklar';
                else if (tabName === 'other') displayTitle = 'Diğer İşlemler';
                else if (tabName === 'profile') displayTitle = 'Kullanıcı Profili';
                topbarTitle.innerText = displayTitle;
            }

            // Fetch dynamic tab content
            const wrapper = document.getElementById('dynamic-content-wrapper');
            if (wrapper) {
                // Show a premium glassmorphic loader
                wrapper.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-20 space-y-3 animate-fade-in">
                        <div class="w-8 h-8 rounded-full border-4 border-zinc-200 dark:border-zinc-800 border-t-zinc-950 dark:border-t-zinc-50 animate-spin"></div>
                        <span class="text-[10px] text-zinc-400 font-bold uppercase tracking-wider">Yükleniyor...</span>
                    </div>
                `;

                try {
                    // Map Tab name to page folder
                    let pagePath = tabName;
                    if (tabName === 'home') pagePath = 'home';
                    else if (tabName === 'personnel') pagePath = 'personel';
                    else if (tabName === 'definitions') pagePath = 'ucretler';
                    else if (tabName === 'kanban') pagePath = 'kanban';
                    else if (tabName === 'other') pagePath = 'other';
                    else if (tabName === 'profile') pagePath = 'profil';

                    const basePath = '<?php echo appBasePath(); ?>';
                    const query = new URLSearchParams(params).toString();
                    const cacheBuster = 't=' + Date.now();
                    const finalQuery = query ? query + '&' + cacheBuster : cacheBuster;
                    const response = await fetch(basePath + `/mobile/pages/${pagePath}/index.php?` + finalQuery);
                    if (!response.ok) throw new Error("Yüklenemedi");
                    
                    const html = await response.text();
                    
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    wrapper.innerHTML = html;
                    
                    // Extract and run scripts inside subpages
                    const scripts = tempDiv.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        newScript.textContent = script.textContent;
                        document.body.appendChild(newScript);
                        newScript.remove();
                    });
                    
                    // Trigger swiping engine if loading personnel or definitions or kanban
                    if (tabName === 'personnel' || tabName === 'definitions' || tabName === 'kanban') {
                        initSwipeActions();
                        if (typeof initMobileCustomSelects === 'function') {
                            initMobileCustomSelects();
                        }
                        if ((tabName === 'personnel' || tabName === 'kanban') && typeof initMobileFlatpickr === 'function') {
                            initMobileFlatpickr();
                        }
                    }

                    // Re-initialize tab actions if any
                    if (callback) {
                        callback();
                    }
                } catch (err) {
                    wrapper.innerHTML = `
                        <div class="glass-card p-6 text-center space-y-3 my-10 animate-fade-in">
                            <span class="text-xs font-bold text-rose-500 block">İçerik yüklenirken bir hata oluştu.</span>
                            <button onclick="switchTab('${tabName}')" class="px-4 py-2 bg-zinc-900 dark:bg-zinc-50 text-zinc-50 dark:text-zinc-950 text-xs font-bold rounded-md">Tekrar Dene</button>
                        </div>
                    `;
                }
            }
        }

        async function loadOtherSubpage(subpageName) {
            // Update active tab style to 'other'
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('text-zinc-950', 'dark:text-zinc-50');
                btn.classList.add('text-zinc-400', 'dark:text-zinc-500');
            });
            document.querySelectorAll('[id^="nav-indicator-"]').forEach(ind => ind.classList.add('hidden'));

            const activeBtn = document.getElementById('nav-other');
            if (activeBtn) {
                activeBtn.classList.add('text-zinc-950', 'dark:text-zinc-50');
                activeBtn.classList.remove('text-zinc-400', 'dark:text-zinc-500');
            }
            const activeInd = document.getElementById('nav-indicator-other');
            if (activeInd) {
                activeInd.classList.remove('hidden');
            }

            currentTab = 'other';

            const wrapper = document.getElementById('dynamic-content-wrapper');
            if (wrapper) {
                // Show loader
                wrapper.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-20 space-y-3 animate-fade-in">
                        <div class="w-8 h-8 rounded-full border-4 border-zinc-200 dark:border-zinc-800 border-t-zinc-950 dark:border-t-zinc-50 animate-spin"></div>
                        <span class="text-[10px] text-zinc-400 font-bold uppercase tracking-wider">Yükleniyor...</span>
                    </div>
                `;

                try {
                    // Persist state in localStorage
                    localStorage.setItem('last_active_tab', 'other');
                    localStorage.setItem('last_active_subpage', subpageName);

                    // Update topbar title dynamically based on active Diğer action
                    const topbarTitle = document.getElementById('topbar-page-title');
                    if (topbarTitle) {
                        let displayTitle = 'Diğer İşlemler';
                        if (subpageName === 'tenants') displayTitle = 'Kurum Yönetimi';
                        else if (subpageName === 'users') displayTitle = 'Kullanıcı Yönetimi';
                        else if (subpageName === 'subscription') displayTitle = 'Abonelik & Limitler';
                        else if (subpageName === 'template') displayTitle = 'Sözleşme Taslakları';
                        else if (subpageName === 'settings') displayTitle = 'Sistem & SMS Ayarları';
                        topbarTitle.innerText = displayTitle;
                    }

                    const basePath = '<?php echo appBasePath(); ?>';
                    const response = await fetch(basePath + `/mobile/pages/other/${subpageName}.php?t=` + Date.now());
                    if (!response.ok) throw new Error("Yüklenemedi");
                    
                    const html = await response.text();
                    
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    wrapper.innerHTML = html;
                    
                    // Extract and run scripts inside subpages
                    const scripts = tempDiv.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        newScript.textContent = script.textContent;
                        document.body.appendChild(newScript);
                        newScript.remove();
                    });
                } catch (err) {
                    wrapper.innerHTML = `
                        <div class="glass-card p-6 text-center space-y-3 my-10 animate-fade-in">
                            <span class="text-xs font-bold text-rose-500 block">İçerik yüklenirken bir hata oluştu.</span>
                            <button onclick="loadOtherSubpage('${subpageName}')" class="px-4 py-2 bg-zinc-900 dark:bg-zinc-50 text-zinc-50 dark:text-zinc-950 text-xs font-bold rounded-md">Tekrar Dene</button>
                        </div>
                    `;
                }
            }
        }

        function goBackToOtherMenu() {
            switchTab('home');
            setTimeout(() => {
                openSheet('other-menu-sheet');
            }, 150);
        }

        // Inline Theme Toggle Helper
        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }

        // Show Toast banner programmatically
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');

            toastMsg.innerText = message;
            
            if (type === 'success') {
                toastIcon.className = "text-emerald-500";
                toastIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>`;
            } else {
                toastIcon.className = "text-rose-500";
                toastIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>`;
            }

            toast.classList.add('open');
            setTimeout(() => {
                toast.classList.remove('open');
            }, 3000);
        }

        // Bottom Sheet toggles
        function openSheet(sheetId) {
            closeAllSheets();
            document.getElementById(sheetId).classList.add('open');
            document.getElementById('sheet-backdrop').classList.add('open');
        }

        function closeAllSheets() {
            document.querySelectorAll('.bottom-sheet').forEach(el => el.classList.remove('open'));
            document.getElementById('sheet-backdrop').classList.remove('open');
            if (typeof closeAllCustomSelectPopovers === 'function') {
                closeAllCustomSelectPopovers();
            }
        }

        // Personnel Detail sheet
        function openDetailSheet(cardElement) {
            selectedPersonnelCard = cardElement;
            isTcMasked = true;

            // Gather attributes from DOM card
            const id = cardElement.getAttribute('data-id');
            const name = cardElement.getAttribute('data-name');
            const tc = cardElement.getAttribute('data-tc');
            const maskedTc = cardElement.getAttribute('data-masked-tc');
            const telefon = cardElement.getAttribute('data-telefon');
            const meslek = cardElement.getAttribute('data-meslek');
            const cinsiyet = cardElement.getAttribute('data-cinsiyet');
            const baslama = cardElement.getAttribute('data-baslama');
            const unvan = cardElement.getAttribute('data-unvan');
            const ucret = cardElement.getAttribute('data-ucret');
            const ogrenim = cardElement.getAttribute('data-ogrenim');
            const kidem = cardElement.getAttribute('data-kidem');
            const durum = cardElement.getAttribute('data-durum');

            // Populate Sheet fields
            document.getElementById('detail-name').innerText = name;
            document.getElementById('detail-unvan').innerText = unvan.toUpperCase();
            document.getElementById('detail-tc').innerText = maskedTc;
            document.getElementById('detail-telefon').innerText = telefon || '-';
            document.getElementById('detail-ucret').innerText = ucret;
            document.getElementById('detail-ogrenim').innerText = ogrenim;
            document.getElementById('detail-kidem').innerText = kidem;
            document.getElementById('detail-meslek').innerText = meslek || '-';
            document.getElementById('detail-baslama').innerText = baslama;
            
            // Set dynamic gender icon in sheet header
            const genderIconWrapper = document.getElementById('detail-gender-icon');
            if (cinsiyet === 'kadin') {
                genderIconWrapper.className = "w-14 h-14 rounded-full bg-rose-500/10 flex items-center justify-center text-rose-400 mx-auto mb-2 border border-rose-500/20";
                genderIconWrapper.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="9" r="6"/><path d="M12 15v7m-3-3h6"/></svg>`;
            } else {
                genderIconWrapper.className = "w-14 h-14 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mx-auto mb-2 border border-indigo-500/20";
                genderIconWrapper.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14.5 9.5 21 3m0 0h-6m6 0v6"/><circle cx="10" cy="14" r="6"/></svg>`;
            }

            // Set durum badge
            const durumBadge = document.getElementById('detail-durum-badge');
            durumBadge.innerText = durum.toUpperCase();
            if (durum === 'aktif') {
                durumBadge.className = "badge-aktif px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider";
            } else {
                durumBadge.className = "badge-pasif px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider";
            }

            // Attach dynamic event listeners to detail buttons
            document.getElementById('btn-preview-contract').onclick = () => previewContract(id);
            document.getElementById('btn-preview-petition').onclick = () => previewPetition(id);
            document.getElementById('btn-download-word').onclick = () => downloadWord(id);
            document.getElementById('btn-edit-personnel').onclick = () => openEditFormSheet(cardElement);
            document.getElementById('btn-delete-personnel').onclick = () => confirmDeletePersonnel(id, name);

            openSheet('detail-sheet');
        }

        // Toggle Mask on TC
        function toggleTcMask() {
            if (!selectedPersonnelCard) return;
            const detailTc = document.getElementById('detail-tc');
            
            if (isTcMasked) {
                detailTc.innerText = selectedPersonnelCard.getAttribute('data-tc');
                isTcMasked = false;
            } else {
                detailTc.innerText = selectedPersonnelCard.getAttribute('data-masked-tc');
                isTcMasked = true;
            }
        }

        // Open Personnel Add Sheet
        function openEkleSheet() {
            document.getElementById('form-title').innerText = "Yeni Personel Ekle";
            document.getElementById('personnelForm').reset();
            document.getElementById('form-p-id').value = "";
            
            // Set default date to today
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const todayStr = `${yyyy}-${mm}-${dd}`;
            
            const baslamaInput = document.getElementById('form-baslama');
            if (baslamaInput) {
                baslamaInput.value = todayStr;
                if (baslamaInput._flatpickr) {
                    baslamaInput._flatpickr.setDate(todayStr);
                }
            }

            if (typeof syncMobileCustomSelects === 'function') {
                syncMobileCustomSelects();
            }
            openSheet('form-sheet');
        }

        // Open Edit Form Sheet
        function openEditFormSheet(cardElement) {
            document.getElementById('form-title').innerText = "Çalışan Kartını Düzenle";
            
            // Fill form with values
            document.getElementById('form-p-id').value = cardElement.getAttribute('data-id');
            document.getElementById('form-ad').value = cardElement.getAttribute('data-name');
            document.getElementById('form-tc').value = cardElement.getAttribute('data-tc');
            document.getElementById('form-ucret-select').value = cardElement.getAttribute('data-ucret-id');
            document.getElementById('form-cinsiyet').value = cardElement.getAttribute('data-cinsiyet');
            document.getElementById('form-durum').value = cardElement.getAttribute('data-durum');
            document.getElementById('form-meslek').value = cardElement.getAttribute('data-meslek');
            document.getElementById('form-telefon').value = cardElement.getAttribute('data-telefon');

            // Format date correctly for standard HTML date input (Y-m-d)
            const dateParts = cardElement.getAttribute('data-baslama').split('.');
            if (dateParts.length === 3) {
                const formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                const baslamaInput = document.getElementById('form-baslama');
                if (baslamaInput) {
                    baslamaInput.value = formattedDate;
                    if (baslamaInput._flatpickr) {
                        baslamaInput._flatpickr.setDate(formattedDate);
                    }
                }
            }

            if (typeof syncMobileCustomSelects === 'function') {
                syncMobileCustomSelects();
            }

            openSheet('form-sheet');
        }

        // Open Toplu Excel Sheet
        function openImportSheet() {
            openSheet('import-sheet');
        }

        // Real-time client side search filters
        function filterPersonnelList() {
            const query = document.getElementById('personnelSearch').value.toLowerCase();
            document.querySelectorAll('.personnel-item-card').forEach(card => {
                const name = card.getAttribute('data-name').toLowerCase();
                const tc = card.getAttribute('data-tc').toLowerCase();
                const unvan = card.getAttribute('data-unvan').toLowerCase();
                
                if (name.includes(query) || tc.includes(query) || unvan.includes(query)) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        function filterDefinitionsList() {
            const query = document.getElementById('definitionSearch').value.toLowerCase();
            document.querySelectorAll('.definition-item-card').forEach(card => {
                const unvan = card.getAttribute('data-unvan').toLowerCase();
                const ogrenim = card.getAttribute('data-ogrenim').toLowerCase();
                
                if (unvan.includes(query) || ogrenim.includes(query)) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        // Gender-based template preprocessor for petitions
        function processGenderTemplate(templateHtml, gender) {
            if (!templateHtml) return '';
            const isFemale = gender && gender.toLowerCase() === 'kadin';
            if (!isFemale) return templateHtml; // If male, keep exactly as is

            // Use a temp DOM parser to safely edit elements
            const parser = new DOMParser();
            const doc = parser.parseFromString(templateHtml, 'text/html');
            
            // 1. Identify and remove any node containing "askerlik" or "terhis"
            const elements = doc.body.querySelectorAll('p, div, li, span');
            elements.forEach(el => {
                const text = el.textContent.toLowerCase();
                if (text.includes('askerlik') || text.includes('terhis')) {
                    el.remove();
                }
            });

            // 2. Renumber remaining numbering (e.g. 1-, 2-, 3-, 4-) sequentially starting from 1
            const remainingElements = doc.body.querySelectorAll('p, div, li, span');
            let index = 1;
            remainingElements.forEach(el => {
                const txt = el.textContent.trim();
                // Match numbers like "3-", "3.", "3 -", "3. "
                const textMatch = txt.match(/^(\d+)\s*([-.]+)\s*(.*)/);
                if (textMatch) {
                    // Retrieve exact punctuation (- or .)
                    const punct = textMatch[2];
                    // Replace starting number in the actual HTML of the element
                    el.innerHTML = el.innerHTML.replace(/^\s*\d+\s*([-.]+)\s*/, index + punct + ' ');
                    index++;
                }
            });

            return doc.body.innerHTML;
        }

        // Global custom petition template passed from PHP
        const customPetitionTemplate = `<?php echo addslashes(str_replace(["\r", "\n"], '', $custom_petition)); ?>`;

        // Preview Petition Logic
        function previewPetition(id) {
            const card = document.querySelector(`.personnel-item-card[data-id="${id}"]`);
            if (!card) return;

            const name = card.getAttribute('data-name') || '';
            const tc = card.getAttribute('data-tc') || '';
            const telefon = card.getAttribute('data-telefon') || '';
            const unvan = card.getAttribute('data-unvan') || '';
            const baslama = card.getAttribute('data-baslama') || '';
            const cinsiyet = (card.getAttribute('data-cinsiyet') || 'erkek').toLowerCase();

            const today = new Date();
            const dd = String(today.getDate()).padStart(2, '0');
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const yyyy = today.getFullYear();
            const todayStr = dd + '.' + mm + '.' + yyyy;

            const defaultContent = 
                '<p style="text-align: center; font-size: 11pt; margin-bottom: 2pt;"><strong>DÜZCE ÜNİVERSİTESİ REKTÖRLÜĞÜNE</strong></p>' +
                '<p style="text-align: center; font-size: 11pt; margin-bottom: 12pt;">(...................................................................)</p>' +
                '<p><br></p>' +
                '<p style="text-indent: 1.5cm; text-align: justify; margin-bottom: 12pt;">Üniversiteniz ................................................................... biriminde, 657 sayılı Devlet Memurları Kanunu\'nun 4/B maddesi uyarınca <strong>' + unvan + '</strong> pozisyonunda sözleşmeli personel olarak <strong>' + baslama + '</strong> tarihinden itibaren görev yapmaktayım.</p>' +
                '<p style="text-indent: 1.5cm; text-align: justify; margin-bottom: 12pt;">26 Ocak 2023 tarih ve 32085 sayılı Resmi Gazete\'de yayınlanan 7433 sayılı "<em>Devlet Memurları Kanunu ve Bazı Kanunlar ile 663 Sayılı Kanun Hükmünde Kararnamelerde Değişiklik Yapılmasına Dair Kanun</em>" ile 657 sayılı Devlet Memurları Kanununa eklenen "<em>...Bu kapsamda istihdam edilen sözleşmeli personelden aynı kurumda üç yıllık çalışma süresini tamamlayanlar bu sürenin bitiminden itibaren otuz gün içinde talepte bulunmaları hâlinde bulundukları yerde aynı unvanlı memur kadrolarına atanır.</em>" hükmü gereğince çalışmakta olduğum pozisyona uygun bir kadroya atanmak istiyorum. Atamaya esas kullanılmak üzere gereken belgeler dilekçemin ekinde mevcuttur.</p>' +
                '<p style="text-indent: 1.5cm; text-align: justify; margin-bottom: 24pt;">Gereğinin yapılmasını müsaadelerinizi arz ederim. ' + todayStr + '</p>' +
                '<p style="text-align: right; margin-bottom: 24pt;"><strong>' + name + ' / ' + tc + ' / İMZA</strong></p>' +
                '<p style="margin-bottom: 4pt;"><strong><u>EK:</u></strong></p>' +
                '<p style="margin-bottom: 4pt;">1- Nüfus Cüzdanı Fotokopisi</p>' +
                '<p style="margin-bottom: 4pt;">2- Son öğrenim durumunu gösterir diploma aslı ve fotokopisi veya Mezun Belgesi (güncel e-devlet çıktısı)</p>' +
                '<p style="margin-bottom: 4pt;">3- Askerlik Durum Belgesi (güncel e-devlet çıktısı) / Askerliğini yapanlar için Terhis Belgesi aslı ve fotokopisi,</p>' +
                '<p style="margin-bottom: 12pt;">4- Tam teşekküllü devlet hastanesi ya da Üniversite hastanesinden alınacak sağlık kurulu (heyet) raporu (aslı ve fotokopisi ya da e-devlet çıktısı)</p>' +
                '<p style="margin-bottom: 8pt;"><strong><u>ADRES:</u></strong> ...................................................................</p>' +
                '<p style="margin-bottom: 8pt;"><strong><u>TEL:</u></strong> ' + (telefon || '...................................................') + '</p>';

            let processedTemplate = customPetitionTemplate ? customPetitionTemplate : defaultContent;
            
            // Apply gender preprocessing
            processedTemplate = processGenderTemplate(processedTemplate, cinsiyet);

            // Replace tokens
            processedTemplate = processedTemplate
                .replace(/\{\{UNVAN\}\}/g, unvan)
                .replace(/\{\{AD_SOYAD\}\}/g, name)
                .replace(/\{\{TC_NO\}\}/g, tc)
                .replace(/\{\{GOREVE_BASLAMA\}\}/g, baslama)
                .replace(/\{\{TELEFON\}\}/g, telefon || '...................................................')
                .replace(/\{\{TODAY\}\}/g, todayStr);

            const contentArea = document.getElementById('preview-content-area');
            contentArea.innerHTML = `
                <div class="ql-container ql-snow" style="border:none">
                    <div class="ql-editor">${processedTemplate}</div>
                </div>
            `;
            contentArea.classList.remove('has-border');

            document.body.setAttribute('data-doc-type', 'dilekce');

            // Set UI details
            document.getElementById('preview-title').innerText = "Dilekçe Önizleme";
            
            // Set current petition personnel details for status update
            currentPetitionPersonnel = {
                id: id,
                name: name,
                tc: tc,
                telefon: telefon,
                ucret_id: card.getAttribute('data-ucret-id') || '',
                cinsiyet: cinsiyet,
                durum: card.getAttribute('data-durum') || 'aktif',
                meslek: card.getAttribute('data-meslek') || '',
                baslama: baslama
            };

            // Hide word download button, bind print
            document.getElementById('btn-preview-download').classList.add('hidden');
            document.getElementById('btn-preview-print').onclick = () => {
                openPetitionConfirmSheet(id, name);
            };

            document.getElementById('preview-modal').classList.remove('hidden');
            setTimeout(adjustPreviewZoom, 50);
        }

        // Preview Contract Logic via AJAX
        function previewContract(id) {
            const basePath = '<?php echo appBasePath(); ?>';
            
            const contentArea = document.getElementById('preview-content-area');
            contentArea.innerHTML = `
                <div class="flex flex-col items-center justify-center py-20 space-y-3">
                    <div class="w-8 h-8 rounded-full border-4 border-zinc-200 dark:border-zinc-800 border-t-indigo-600 animate-spin"></div>
                    <span class="text-xs text-zinc-400 font-bold uppercase tracking-wider">Sözleşme yükleniyor...</span>
                </div>
            `;
            contentArea.classList.remove('has-border');

            document.getElementById('preview-title').innerText = "Sözleşme Taslağı";
            document.getElementById('btn-preview-download').classList.remove('hidden');

            document.getElementById('preview-modal').classList.remove('hidden');

            fetch(basePath + '/personel-get-preview?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.body.setAttribute('data-doc-type', 'sozlesme');
                        
                        contentArea.innerHTML = `
                            <div class="ql-container ql-snow" style="border:none">
                                <div class="ql-editor">${data.content}</div>
                            </div>
                        `;
                        
                        if (data.has_border) {
                            contentArea.classList.add('has-border');
                        } else {
                            contentArea.classList.remove('has-border');
                        }
                        
                        // Bind download and print buttons
                        document.getElementById('btn-preview-download').onclick = () => downloadWord(id);
                        document.getElementById('btn-preview-print').onclick = () => {
                            printMobileDocument();
                        };
                        setTimeout(adjustPreviewZoom, 50);
                    } else {
                        showToast(data.error || 'Önizleme yüklenirken bir hata oluştu.', 'error');
                        closePreviewModal();
                    }
                })
                .catch(err => {
                    showToast('Sunucu bağlantı hatası.', 'error');
                    closePreviewModal();
                });
        }

        function closePreviewModal() {
            document.getElementById('preview-modal').classList.add('hidden');
            document.body.removeAttribute('data-doc-type');
        }

        // Filter Sheet opening
        function openFilterSheet() {
            closeAllSheets();
            if (typeof syncMobileCustomSelects === 'function') {
                syncMobileCustomSelects();
            }
            const sheet = document.getElementById('filter-sheet');
            const backdrop = document.getElementById('sheet-backdrop');
            if (sheet && backdrop) {
                sheet.classList.add('open');
                backdrop.classList.add('open');
            }
        }

        // Switch to personnel tab and automatically filter by tenure-eligible personnel
        function viewAllEligible() {
            switchTab('personnel', () => {
                window.filterKadroShortcut = 'gelenler';
                applyPersonnelFilters();
            });
        }

        // Clear a single active filter
        function clearSingleFilter(type) {
            if (type === 'unvan') {
                const unvanSelect = document.getElementById('filter-unvan');
                if (unvanSelect) {
                    Array.from(unvanSelect.options).forEach(o => o.selected = false);
                    const placeholder = unvanSelect.querySelector('option[value=""]');
                    if (placeholder) placeholder.selected = true;
                }
            } else if (type === 'durum') {
                const durumSelect = document.getElementById('filter-durum');
                if (durumSelect) {
                    Array.from(durumSelect.options).forEach(o => o.selected = false);
                    const placeholder = durumSelect.querySelector('option[value=""]');
                    if (placeholder) placeholder.selected = true;
                }
            } else if (type === 'ogrenim') {
                const ogrenimSelect = document.getElementById('filter-ogrenim');
                if (ogrenimSelect) ogrenimSelect.value = '';
            } else if (type === 'ucret') {
                const ucretInput = document.getElementById('filter-ucret-val');
                if (ucretInput) ucretInput.value = '';
            } else if (type === 'baslama') {
                const baslamaDateInput = document.getElementById('filter-baslama-tarih');
                if (baslamaDateInput) {
                    baslamaDateInput.value = '';
                    if (baslamaDateInput._flatpickr) {
                        baslamaDateInput._flatpickr.clear();
                    }
                }
            } else if (type === 'kadro') {
                window.filterKadroShortcut = null;
            }
            
            if (typeof syncMobileCustomSelects === 'function') {
                syncMobileCustomSelects();
            }
            applyPersonnelFilters();
        }

        // Clear all filters
        function clearAllFilters() {
            const unvanSelect = document.getElementById('filter-unvan');
            const durumSelect = document.getElementById('filter-durum');
            const ogrenimSelect = document.getElementById('filter-ogrenim');
            const ogrenimOpSelect = document.getElementById('filter-ogrenim-op');
            const ucretInput = document.getElementById('filter-ucret-val');
            const ucretOpSelect = document.getElementById('filter-ucret-op');
            const baslamaDateInput = document.getElementById('filter-baslama-tarih');
            const baslamaOpSelect = document.getElementById('filter-baslama-op');
            const searchInput = document.getElementById('personnelSearch');
            
            if (unvanSelect) {
                Array.from(unvanSelect.options).forEach(o => o.selected = false);
                const placeholder = unvanSelect.querySelector('option[value=""]');
                if (placeholder) placeholder.selected = true;
            }
            if (durumSelect) {
                Array.from(durumSelect.options).forEach(o => o.selected = false);
                const placeholder = durumSelect.querySelector('option[value=""]');
                if (placeholder) placeholder.selected = true;
            }
            if (ogrenimSelect) ogrenimSelect.value = '';
            if (ogrenimOpSelect) ogrenimOpSelect.value = 'equals';
            if (ucretInput) ucretInput.value = '';
            if (ucretOpSelect) ucretOpSelect.value = 'equals';
            if (baslamaDateInput) {
                baslamaDateInput.value = '';
                if (baslamaDateInput._flatpickr) {
                    baslamaDateInput._flatpickr.clear();
                }
            }
            if (baslamaOpSelect) baslamaOpSelect.value = 'equals';
            if (searchInput) searchInput.value = '';

            window.filterKadroShortcut = null;
            
            if (typeof syncMobileCustomSelects === 'function') {
                syncMobileCustomSelects();
            }
            
            applyPersonnelFilters();
            closeAllSheets();
        }

        // Sort Sheet opening
        function openSortSheet() {
            openSheet('sort-sheet');
            const currentSort = window.currentMobileSort || 'name_asc';
            document.querySelectorAll('.sort-option-btn').forEach(btn => {
                const checkIcon = btn.querySelector('.check-icon');
                if (btn.getAttribute('data-sort') === currentSort) {
                    if (checkIcon) checkIcon.classList.remove('hidden');
                    btn.classList.add('bg-zinc-50', 'dark:bg-zinc-800/60', 'text-indigo-600', 'dark:text-indigo-400');
                } else {
                    if (checkIcon) checkIcon.classList.add('hidden');
                    btn.classList.remove('bg-zinc-50', 'dark:bg-zinc-800/60', 'text-indigo-600', 'dark:text-indigo-400');
                }
            });
        }

        // Apply dynamic sorting to the list of personnel
        function applySorting(type) {
            window.currentMobileSort = type;
            closeAllSheets();
            
            const wrapper = document.getElementById('personnel-list-wrapper');
            if (!wrapper) return;
            
            const cards = Array.from(wrapper.querySelectorAll('.personnel-item-card'));
            
            cards.sort((a, b) => {
                let valA, valB;
                
                if (type === 'name_asc' || type === 'name_desc') {
                    valA = (a.getAttribute('data-name') || '').toLocaleLowerCase('tr-TR');
                    valB = (b.getAttribute('data-name') || '').toLocaleLowerCase('tr-TR');
                    return type === 'name_asc' ? valA.localeCompare(valB, 'tr') : valB.localeCompare(valA, 'tr');
                }
                
                if (type === 'start_asc' || type === 'start_desc') {
                    const partsA = (a.getAttribute('data-baslama') || '').split('.');
                    const partsB = (b.getAttribute('data-baslama') || '').split('.');
                    valA = partsA.length === 3 ? `${partsA[2]}-${partsA[1]}-${partsA[0]}` : '';
                    valB = partsB.length === 3 ? `${partsB[2]}-${partsB[1]}-${partsB[0]}` : '';
                    return type === 'start_asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
                }

                if (type === 'tenure_asc' || type === 'tenure_desc') {
                    const partsA = (a.getAttribute('data-baslama') || '').split('.');
                    const partsB = (b.getAttribute('data-baslama') || '').split('.');
                    valA = partsA.length === 3 ? `${partsA[2]}-${partsA[1]}-${partsA[0]}` : '';
                    valB = partsB.length === 3 ? `${partsB[2]}-${partsB[1]}-${partsB[0]}` : '';
                    return type === 'tenure_asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
                }

                if (type === 'wage_asc' || type === 'wage_desc') {
                    valA = parseFloat(a.getAttribute('data-ucret-raw') || '0');
                    valB = parseFloat(b.getAttribute('data-ucret-raw') || '0');
                    return type === 'wage_asc' ? valA - valB : valB - valA;
                }

                if (type === 'edu_asc' || type === 'edu_desc') {
                    const eduLevels = {
                        'ilkokul': 1, 'ortaokul': 2, 'lise': 3, 'önlisans': 4, 'ön lisans': 4,
                        'lisans': 5, 'yüksek lisans': 6, 'doktora': 7
                    };
                    const getEduWeight = (val) => {
                        const v = (val || '').toLowerCase();
                        for (const key in eduLevels) {
                            if (v.includes(key)) return eduLevels[key];
                        }
                        return 0;
                    };
                    valA = getEduWeight(a.getAttribute('data-ogrenim'));
                    valB = getEduWeight(b.getAttribute('data-ogrenim'));
                    return type === 'edu_asc' ? valA - valB : valB - valA;
                }

                if (type === 'title_asc' || type === 'title_desc') {
                    valA = (a.getAttribute('data-unvan') || '').toLocaleLowerCase('tr-TR');
                    valB = (b.getAttribute('data-unvan') || '').toLocaleLowerCase('tr-TR');
                    return type === 'title_asc' ? valA.localeCompare(valB, 'tr') : valB.localeCompare(valA, 'tr');
                }
                
                return 0;
            });
            
            // Append sorted cards back to wrapper
            cards.forEach(card => wrapper.appendChild(card));
            
            showToast('Sıralama başarıyla uygulandı.', 'success');
        }

        // Apply Personnel Filters dynamically in JS (extremely fast SPA feel with premium operators)
        function applyPersonnelFilters() {
            const unvanSelect = document.getElementById('filter-unvan');
            const durumSelect = document.getElementById('filter-durum');
            const ogrenimSelect = document.getElementById('filter-ogrenim');
            const ogrenimOpSelect = document.getElementById('filter-ogrenim-op');
            const ucretInput = document.getElementById('filter-ucret-val');
            const ucretOpSelect = document.getElementById('filter-ucret-op');
            const baslamaDateInput = document.getElementById('filter-baslama-tarih');
            const baslamaOpSelect = document.getElementById('filter-baslama-op');
            const searchInput = document.getElementById('personnelSearch');

            const searchVal = searchInput ? searchInput.value.toLowerCase() : '';
            
            // Multiple select values
            const selectedUnvans = unvanSelect ? Array.from(unvanSelect.selectedOptions)
                                                      .map(o => o.value.toLowerCase())
                                                      .filter(v => v !== '') : [];
            const selectedDurums = durumSelect ? Array.from(durumSelect.selectedOptions)
                                                        .map(o => o.value.toLowerCase())
                                                        .filter(v => v !== '') : [];
            
            const ogrenim = ogrenimSelect ? ogrenimSelect.value.toLowerCase() : '';
            const ogrenimOp = ogrenimOpSelect ? ogrenimOpSelect.value : 'equals';
            
            const ucretVal = ucretInput && ucretInput.value !== '' ? parseFloat(ucretInput.value) : null;
            const ucretOp = ucretOpSelect ? ucretOpSelect.value : 'equals';

            const baslamaTarih = baslamaDateInput ? baslamaDateInput.value : ''; // yyyy-mm-dd
            const baslamaOp = baslamaOpSelect ? baslamaOpSelect.value : 'equals';

            const cards = document.querySelectorAll('.personnel-item-card');
            let countVisible = 0;

            cards.forEach(card => {
                const cardName = (card.getAttribute('data-name') || '').toLowerCase();
                const cardTc = (card.getAttribute('data-tc') || '').toLowerCase();
                const cardUnvan = (card.getAttribute('data-unvan') || '').toLowerCase();
                const cardDurum = (card.getAttribute('data-durum') || '').toLowerCase();
                const cardOgrenim = (card.getAttribute('data-ogrenim') || '').toLowerCase();
                const cardUcretRaw = parseFloat(card.getAttribute('data-ucret-raw') || '0');
                const cardBaslama = card.getAttribute('data-baslama') || ''; // dd.mm.yyyy
                const cardEligible = card.getAttribute('data-eligible') === '1';

                // Search Match
                const matchesSearch = cardName.includes(searchVal) || cardTc.includes(searchVal);

                // Unvan Operator Match (Multi-Select)
                let matchesUnvan = true;
                if (selectedUnvans.length > 0) {
                    matchesUnvan = selectedUnvans.includes(cardUnvan);
                }

                // Durum Operator Match (Multi-Select)
                let matchesDurum = true;
                if (selectedDurums.length > 0) {
                    matchesDurum = selectedDurums.includes(cardDurum);
                }

                // Öğrenim Operator Match
                let matchesOgrenim = true;
                if (ogrenim) {
                    if (ogrenimOp === 'equals') {
                        matchesOgrenim = (cardOgrenim === ogrenim);
                    } else if (ogrenimOp === 'not_equals') {
                        matchesOgrenim = (cardOgrenim !== ogrenim);
                    }
                }

                // Sözleşme Ücret Operator Match
                let matchesUcret = true;
                if (ucretVal !== null) {
                    if (ucretOp === 'equals') {
                        matchesUcret = (cardUcretRaw === ucretVal);
                    } else if (ucretOp === 'gt') {
                        matchesUcret = (cardUcretRaw > ucretVal);
                    } else if (ucretOp === 'lt') {
                        matchesUcret = (cardUcretRaw < ucretVal);
                    } else if (ucretOp === 'gte') {
                        matchesUcret = (cardUcretRaw >= ucretVal);
                    } else if (ucretOp === 'lte') {
                        matchesUcret = (cardUcretRaw <= ucretVal);
                    }
                }

                // Kadro Shortcut Match
                let matchesKadro = true;
                if (window.filterKadroShortcut === 'gelenler') {
                    matchesKadro = cardEligible;
                }

                // Göreve Başlama Tarihi Operator Match
                let matchesBaslama = true;
                if (baslamaTarih) {
                    const parts = cardBaslama.split('.');
                    if (parts.length === 3) {
                        const cardDateStr = `${parts[2]}-${parts[1]}-${parts[0]}`; // yyyy-mm-dd
                        const cardTime = new Date(cardDateStr).getTime();
                        const filterTime = new Date(baslamaTarih).getTime();
                        
                        if (baslamaOp === 'equals') {
                            matchesBaslama = (cardDateStr === baslamaTarih);
                        } else if (baslamaOp === 'gt') {
                            matchesBaslama = (cardTime > filterTime);
                        } else if (baslamaOp === 'lt') {
                            matchesBaslama = (cardTime < filterTime);
                        } else if (baslamaOp === 'gte') {
                            matchesBaslama = (cardTime >= filterTime);
                        } else if (baslamaOp === 'lte') {
                            matchesBaslama = (cardTime <= filterTime);
                        }
                    } else {
                        matchesBaslama = false;
                    }
                }

                if (matchesSearch && matchesUnvan && matchesDurum && matchesOgrenim && matchesUcret && matchesKadro && matchesBaslama) {
                    card.classList.remove('hidden');
                    countVisible++;
                } else {
                    card.classList.add('hidden');
                }
            });

            // Update personnel count text if it exists
            const countText = document.querySelector('.personnel-count-text');
            if (countText) {
                countText.innerText = `Toplam ${countVisible} Çalışan`;
            }

            // Check if any filters are active (excluding search)
            const hasActiveFilters = selectedUnvans.length > 0 || 
                                     selectedDurums.length > 0 || 
                                     ogrenim !== '' || 
                                     ucretVal !== null || 
                                     baslamaTarih !== '' || 
                                     window.filterKadroShortcut === 'gelenler';

            // Toggle active state for filter toggle button
            const filterBtn = document.getElementById('mobileFilterToggleBtn');
            if (filterBtn) {
                if (hasActiveFilters) {
                    filterBtn.className = "p-3 bg-zinc-900 dark:bg-zinc-50 text-white dark:text-zinc-950 border border-zinc-900 dark:border-zinc-50 rounded-lg active:scale-95 transition-all cursor-pointer flex items-center justify-center shadow-md shadow-zinc-500/10";
                } else {
                    filterBtn.className = "p-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg text-zinc-600 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer flex items-center justify-center";
                }
            }

            // Render Active Filter Badges dynamically
            const badgeContainer = document.getElementById('active-filters-badges');
            if (badgeContainer) {
                if (hasActiveFilters) {
                    let badgesHtml = '';

                    if (selectedUnvans.length > 0) {
                        const labels = Array.from(unvanSelect.selectedOptions).map(o => o.text);
                        badgesHtml += `
                            <div class="inline-flex items-center gap-1.5 bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700/50 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                                <span>Unvan: ${labels.join(', ')}</span>
                                <button onclick="clearSingleFilter('unvan')" class="hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors ml-0.5 font-bold cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                        `;
                    }

                    if (selectedDurums.length > 0) {
                        const labels = Array.from(durumSelect.selectedOptions).map(o => o.text);
                        badgesHtml += `
                            <div class="inline-flex items-center gap-1.5 bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700/50 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                                <span>Durum: ${labels.join(', ')}</span>
                                <button onclick="clearSingleFilter('durum')" class="hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors ml-0.5 font-bold cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                        `;
                    }

                    if (ogrenim) {
                        const opText = ogrenimOp === 'equals' ? '=' : '≠';
                        badgesHtml += `
                            <div class="inline-flex items-center gap-1.5 bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700/50 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                                <span>Öğrenim ${opText} ${ogrenim.toUpperCase()}</span>
                                <button onclick="clearSingleFilter('ogrenim')" class="hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors ml-0.5 font-bold cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                        `;
                    }

                    if (ucretVal !== null) {
                        let opSign = '=';
                        if (ucretOp === 'gt') opSign = '>';
                        if (ucretOp === 'lt') opSign = '<';
                        if (ucretOp === 'gte') opSign = '≥';
                        if (ucretOp === 'lte') opSign = '≤';
                        
                        badgesHtml += `
                            <div class="inline-flex items-center gap-1.5 bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700/50 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                                <span>Ücret ${opSign} ₺${ucretVal.toLocaleString('tr-TR')}</span>
                                <button onclick="clearSingleFilter('ucret')" class="hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors ml-0.5 font-bold cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                        `;
                    }

                    if (baslamaTarih) {
                        let opSign = '=';
                        if (baslamaOp === 'gt') opSign = '>';
                        if (baslamaOp === 'lt') opSign = '<';
                        if (baslamaOp === 'gte') opSign = '≥';
                        if (baslamaOp === 'lte') opSign = '≤';

                        // Format date for badge
                        const dateParts = baslamaTarih.split('-');
                        const formattedDate = dateParts.length === 3 ? `${dateParts[2]}.${dateParts[1]}.${dateParts[0]}` : baslamaTarih;

                        badgesHtml += `
                            <div class="inline-flex items-center gap-1.5 bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700/50 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                                <span>Giriş ${opSign} ${formattedDate}</span>
                                <button onclick="clearSingleFilter('baslama')" class="hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors ml-0.5 font-bold cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                        `;
                    }

                    if (window.filterKadroShortcut === 'gelenler') {
                        badgesHtml += `
                            <div class="inline-flex items-center gap-1.5 bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700/50 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                                <span>Kadroya Geçiş Süresi Dolanlar</span>
                                <button onclick="clearSingleFilter('kadro')" class="hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors ml-0.5 font-bold cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                        `;
                    }

                    badgeContainer.innerHTML = badgesHtml;
                    badgeContainer.classList.remove('hidden');
                } else {
                    badgeContainer.innerHTML = '';
                    badgeContainer.classList.add('hidden');
                }
            }
        }

        // Download Word Document
        function downloadWord(id) {
            const basePath = '<?php echo appBasePath(); ?>';
            window.location.href = basePath + '/personel-download-word?id=' + id;
            showToast('Sözleşme indirme işlemi başlatıldı.');
        }

        // Show a premium confirm dialog modal (Shadcn styled)
        function showConfirmDialog(title, message, onConfirm, confirmText = "Evet, Sil", confirmColorClass = "bg-rose-600 hover:bg-rose-700 text-white", iconColorClass = "bg-rose-500/10 text-rose-500") {
            let confirmModal = document.getElementById('confirm-dialog-modal');
            if (!confirmModal) {
                confirmModal = document.createElement('div');
                confirmModal.id = 'confirm-dialog-modal';
                confirmModal.className = 'fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-6 hidden opacity-0 transition-opacity duration-200';
                confirmModal.innerHTML = `
                    <div class="w-full max-w-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl p-5 space-y-4 scale-95 transition-transform duration-200 transform">
                        <div class="flex items-center gap-3">
                            <div id="confirm-icon-wrapper" class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                            </div>
                            <h4 id="confirm-title" class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50 leading-tight">İşlem Onayı</h4>
                        </div>
                        <p id="confirm-message" class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed font-medium">Bu işlemi gerçekleştirmek istediğinize emin misiniz?</p>
                        <div class="flex items-center justify-end gap-2.5 pt-2">
                            <button id="confirm-btn-cancel" class="px-3.5 py-2 rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-zinc-100 text-xs font-bold transition-all cursor-pointer">Vazgeç</button>
                            <button id="confirm-btn-ok" class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer shadow-sm">Evet, Sil</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(confirmModal);
            }

            const titleEl = confirmModal.querySelector('#confirm-title');
            const messageEl = confirmModal.querySelector('#confirm-message');
            const iconWrapper = confirmModal.querySelector('#confirm-icon-wrapper');
            const btnCancel = confirmModal.querySelector('#confirm-btn-cancel');
            const btnOk = confirmModal.querySelector('#confirm-btn-ok');

            titleEl.innerText = title;
            messageEl.innerText = message;
            btnOk.innerText = confirmText;
            
            // Set dynamic button and icon colors
            btnOk.className = `px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer shadow-sm ${confirmColorClass}`;
            iconWrapper.className = `w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ${iconColorClass}`;

            const closeModal = () => {
                confirmModal.classList.add('opacity-0');
                confirmModal.querySelector('.transform').classList.add('scale-95');
                setTimeout(() => {
                    confirmModal.classList.add('hidden');
                }, 200);
            };

            btnCancel.onclick = closeModal;
            btnOk.onclick = () => {
                closeModal();
                onConfirm();
            };

            confirmModal.classList.remove('hidden');
            confirmModal.offsetHeight; // Force reflow
            confirmModal.classList.remove('opacity-0');
            confirmModal.querySelector('.transform').classList.remove('scale-95');
        }

        // Confirm Delete Personnel
        function confirmDeletePersonnel(id, name) {
            showConfirmDialog(
                'Personel Kartını Sil',
                `"${name}" isimli personeli silmek istediğinize emin misiniz? Bu işlem geri alınamaz.`,
                () => {
                    const basePath = '<?php echo appBasePath(); ?>';
                    const formData = new FormData();
                    formData.append('id', id);

                    fetch(basePath + '/personel-sil', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Çalışan kartı silindi.');
                            closeAllSheets();
                            setTimeout(() => {
                                switchTab(currentTab);
                            }, 1200);
                        } else {
                            showToast(data.error || 'Silme işlemi gerçekleştirilemedi.', 'error');
                        }
                    })
                    .catch(err => {
                        showToast('Bağlantı hatası oluştu.', 'error');
                    });
                }
            );
        }

        // Handle Add/Edit form submission via AJAX
        document.getElementById('personnelForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('form-p-id').value;
            const basePath = '<?php echo appBasePath(); ?>';
            const url = id ? (basePath + '/personel-guncelle') : (basePath + '/personel-ekle');
            
            const formData = new FormData(this);
            formData.append('ajax', '1');

            // Send XMLHttpRequest header so controller outputs JSON
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(id ? 'Personel güncellendi.' : 'Personel eklendi.');
                    closeAllSheets();
                    setTimeout(() => {
                        switchTab(currentTab);
                    }, 1200);
                } else {
                    showToast(data.error || 'Kaydetme sırasında bir hata oluştu.', 'error');
                }
            })
            .catch(err => {
                showToast('Sunucuya erişilemedi.', 'error');
            });
        });

        // Parse and Upload Excel CSV file via AJAX
        function handleExcelUpload() {
            const fileInput = document.getElementById('import-file-input');
            const file = fileInput.files[0];
            
            if (!file) {
                showToast('Lütfen yüklenecek bir CSV dosyası seçin.', 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                const rows = csvToArray(text);
                
                if (rows.length === 0) {
                    showToast('Dosya boş veya geçersiz format.', 'error');
                    return;
                }

                const basePath = '<?php echo appBasePath(); ?>';
                fetch(basePath + '/personel-import-excel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        data: rows,
                        update_wages: true
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(`${data.count} personel başarıyla yüklendi.`);
                        closeAllSheets();
                        setTimeout(() => {
                            switchTab(currentTab);
                        }, 1500);
                    } else {
                        showToast(data.error || 'Toplu yükleme başarısız.', 'error');
                    }
                })
                .catch(err => {
                    showToast('Sunucu bağlantısı koptu.', 'error');
                });
            };
            reader.readAsText(file);
        }

        // Helper to parse standard CSV data into JSON array
        function csvToArray(str, delimiter = ",") {
            // Check delimiter (comma or semicolon)
            if (str.includes(';')) delimiter = ';';
            
            const headers = str.slice(0, str.indexOf("\n")).split(delimiter).map(h => h.trim().replace(/^"|"$/g, ''));
            const rows = str.slice(str.indexOf("\n") + 1).split("\n");
            
            return rows.map(function (row) {
                const values = [];
                let inQuotes = false;
                let currentVal = '';
                
                for (let i = 0; i < row.length; i++) {
                    const char = row[i];
                    if (char === '"') {
                        inQuotes = !inQuotes;
                    } else if (char === delimiter && !inQuotes) {
                        values.push(currentVal.trim());
                        currentVal = '';
                    } else {
                        currentVal += char;
                    }
                }
                values.push(currentVal.trim());
                
                if (values.length < headers.length) return null;
                
                return headers.reduce(function (object, header, index) {
                    object[header] = values[index] ? values[index].replace(/^"|"$/g, '') : '';
                    return object;
                }, {});
            }).filter(row => row !== null);
        }

        // Mobile Custom Select Dropdown Mechanics (Matches Premium Desktop Styling & UX with Multi-Select support)
        function initMobileCustomSelects() {
            const selects = document.querySelectorAll('#personnelForm select, #filter-sheet select, #definitionForm select, #def-filter-sheet select, #mobileTanimlamalarForm select, #mobileSettingsForm select, #wage-period-select, #mobileTaskForm select');
            selects.forEach(select => {
                if (!select.id) {
                    select.id = 'mobile-select-rand-' + Math.random().toString(36).substr(2, 9);
                }
                if (select.getAttribute('data-custom-select-initialized')) return;
                select.setAttribute('data-custom-select-initialized', 'true');
                
                const id = select.id;
                const parent = select.parentElement;
                
                // Hide native select visually but keep it focusable for browser validation
                select.style.position = 'absolute';
                select.style.width = '1px';
                select.style.height = '1px';
                select.style.padding = '0';
                select.style.margin = '0';
                select.style.border = '0';
                select.style.opacity = '0.01';
                select.style.pointerEvents = 'none';
                select.style.overflow = 'hidden';
                select.style.clip = 'rect(0, 0, 0, 0)';
                
                // Create custom select wrapper
                const wrapper = document.createElement('div');
                wrapper.className = id === 'wage-period-select' ? 'relative mobile-custom-select-wrapper' : 'relative mobile-custom-select-wrapper w-full';
                wrapper.id = 'custom-select-wrapper-' + id;
                
                // Trigger button
                const trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.id = 'custom-select-trigger-' + id;
                
                if (id === 'wage-period-select') {
                    trigger.className = 'flex items-center gap-1 cursor-pointer text-xs font-extrabold text-zinc-950 dark:text-white bg-transparent border-0 p-0 pr-4 focus:ring-0 focus:outline-none uppercase transition-all duration-200';
                    trigger.innerHTML = `
                        <span class="selected-label uppercase truncate text-zinc-950 dark:text-white">${select.options[select.selectedIndex]?.text || 'SEÇİN'}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" class="opacity-80 shrink-0 text-zinc-950 dark:text-white transition-transform duration-200" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    `;
                } else {
                    trigger.className = 'mobile-input flex items-center justify-between cursor-pointer w-full text-left font-semibold text-sm transition-all duration-200';
                    trigger.innerHTML = `
                        <span class="selected-label truncate text-zinc-400 dark:text-zinc-500">Seçim Yapın</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" class="opacity-50 shrink-0 text-zinc-400 transition-transform duration-200" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    `;
                }
                
                // Popover container
                const popover = document.createElement('div');
                popover.className = id === 'wage-period-select'
                    ? 'mobile-custom-select-popover absolute top-full left-0 mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-50 hidden max-h-60 overflow-y-auto app-scroll py-1 animate-fade-in w-48'
                    : 'mobile-custom-select-popover absolute top-full left-0 right-0 mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-50 hidden max-h-60 overflow-y-auto app-scroll py-1 animate-fade-in';
                popover.id = 'custom-select-popover-' + id;
                
                // Populate options
                const options = select.querySelectorAll('option');

                // Prepend sticky search container if options > 3 or is Unvan or Wage select
                if (options.length > 3 || id === 'filter-unvan' || id === 'form-ucret-select') {
                    const searchContainer = document.createElement('div');
                    searchContainer.className = 'p-2 border-b border-zinc-100 dark:border-zinc-800/40 sticky top-0 bg-white dark:bg-zinc-900 z-10';
                    searchContainer.innerHTML = `
                        <div class="relative flex items-center bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-2 py-1.5 gap-1.5 w-full">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="opacity-40 text-zinc-400 shrink-0" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" class="popover-search-input bg-transparent border-0 outline-none text-xs w-full font-semibold text-zinc-800 dark:text-zinc-200" placeholder="Ara..." onclick="event.stopPropagation()">
                        </div>
                    `;
                    popover.appendChild(searchContainer);
                    
                    const searchInput = searchContainer.querySelector('.popover-search-input');
                    if (searchInput) {
                        searchInput.addEventListener('input', (e) => {
                            const term = e.target.value.toLowerCase();
                            popover.querySelectorAll('[data-value]').forEach(div => {
                                const text = div.querySelector('.option-label').innerText.toLowerCase();
                                if (text.includes(term)) {
                                    div.style.setProperty('display', 'flex', 'important');
                                } else {
                                    div.style.setProperty('display', 'none', 'important');
                                }
                            });
                        });
                    }
                }

                options.forEach(opt => {
                    const val = opt.value;
                    const text = opt.text;
                    const isDisabled = opt.disabled;
                    
                    if (isDisabled && !val) return; // skip placeholder in dropdown options
                    
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer flex items-center justify-between transition-colors border-b border-zinc-100 dark:border-zinc-800/40 last:border-0 font-medium';
                    optionDiv.setAttribute('data-value', val);
                    optionDiv.innerHTML = `
                        <span class="option-label truncate pr-4 text-zinc-800 dark:text-zinc-200 font-semibold">${text}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    `;
                    
                    optionDiv.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (select.multiple) {
                            const isPlaceholder = (val === "");
                            if (isPlaceholder) {
                                // Clear all selections
                                Array.from(options).forEach(o => o.selected = false);
                                opt.selected = true;
                            } else {
                                // Deselect placeholder option
                                const placeholderOpt = select.querySelector('option[value=""]');
                                if (placeholderOpt) placeholderOpt.selected = false;
                                
                                opt.selected = !opt.selected;
                                
                                // If nothing selected, revert to placeholder
                                const anySelected = Array.from(options).some(o => o.value !== "" && o.selected);
                                if (!anySelected && placeholderOpt) {
                                    placeholderOpt.selected = true;
                                }
                            }
                            select.dispatchEvent(new Event('change'));
                            syncMobileCustomSelects();
                            
                            if (select.closest('#filter-sheet')) {
                                applyPersonnelFilters();
                            }
                            if (select.closest('#def-filter-sheet')) {
                                applyDefinitionFilters();
                            }
                        } else {
                            select.value = val;
                            select.dispatchEvent(new Event('change'));
                            syncMobileCustomSelects();
                            closeAllCustomSelectPopovers();
                            
                            if (id === 'wage-period-select') {
                                if (typeof switchWagePeriod === 'function') {
                                    switchWagePeriod(val);
                                }
                            }
                            if (select.closest('#filter-sheet')) {
                                applyPersonnelFilters();
                            }
                            if (select.closest('#def-filter-sheet')) {
                                applyDefinitionFilters();
                            }
                        }
                    });
                    
                    popover.appendChild(optionDiv);
                });
                
                wrapper.appendChild(trigger);
                wrapper.appendChild(popover);
                parent.appendChild(wrapper);
                
                // Trigger click handler
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isHidden = popover.classList.contains('hidden');
                    closeAllCustomSelectPopovers();
                    if (isHidden) {
                        popover.classList.remove('hidden');
                        trigger.querySelector('svg').classList.add('rotate-180');
                    }
                });
            });
            
            // Sync initial state
            syncMobileCustomSelects();
        }

        function closeAllCustomSelectPopovers() {
            document.querySelectorAll('.mobile-custom-select-popover').forEach(popover => {
                popover.classList.add('hidden');
            });
            document.querySelectorAll('.mobile-custom-select-wrapper button svg').forEach(svg => {
                svg.classList.remove('rotate-180');
            });
        }

        function syncMobileCustomSelects() {
            const selects = document.querySelectorAll('#personnelForm select, #filter-sheet select, #definitionForm select, #def-filter-sheet select, #mobileTanimlamalarForm select, #mobileSettingsForm select, #wage-period-select, #mobileTaskForm select');
            selects.forEach(select => {
                const id = select.id;
                const wrapper = document.getElementById('custom-select-wrapper-' + id);
                if (!wrapper) return;
                
                const trigger = wrapper.querySelector('button');
                const popover = wrapper.querySelector('.mobile-custom-select-popover');
                const selectedLabelSpan = trigger.querySelector('.selected-label');
                
                if (select.multiple) {
                    const selectedOptions = Array.from(select.selectedOptions).filter(o => o.value !== "");
                    if (selectedOptions.length > 0) {
                        selectedLabelSpan.innerText = selectedOptions.map(o => o.text).join(', ');
                        selectedLabelSpan.classList.remove('text-zinc-400', 'dark:text-zinc-500');
                        selectedLabelSpan.classList.add('text-zinc-900', 'dark:text-zinc-100');
                    } else {
                        const placeholderOpt = select.querySelector('option[value=""]') || select.querySelector('option');
                        selectedLabelSpan.innerText = placeholderOpt ? placeholderOpt.text : 'Tümü';
                        selectedLabelSpan.classList.add('text-zinc-400', 'dark:text-zinc-500');
                        selectedLabelSpan.classList.remove('text-zinc-900', 'dark:text-zinc-100');
                    }
                    
                    // Mark active options
                    popover.querySelectorAll('[data-value]').forEach(item => {
                        const val = item.getAttribute('data-value');
                        const check = item.querySelector('.check-icon');
                        const opt = select.querySelector(`option[value="${val}"]`);
                        
                        if (opt && opt.selected && val !== "") {
                            item.classList.add('bg-zinc-50', 'dark:bg-zinc-800', 'text-zinc-900', 'dark:text-white', 'font-bold');
                            check.classList.remove('hidden');
                        } else if (val === "" && (!opt || opt.selected)) {
                            item.classList.add('bg-zinc-50', 'dark:bg-zinc-800', 'text-zinc-900', 'dark:text-white', 'font-bold');
                            check.classList.remove('hidden');
                        } else {
                            item.classList.remove('bg-zinc-50', 'dark:bg-zinc-800', 'text-zinc-900', 'dark:text-white', 'font-bold');
                            check.classList.add('hidden');
                        }
                    });
                } else {
                    const selectedValue = select.value;
                    const selectedOption = select.querySelector(`option[value="${selectedValue}"]`) || select.querySelector('option:checked');
                    
                    if (selectedOption) {
                        selectedLabelSpan.innerText = selectedOption.text;
                        if (id === 'wage-period-select') {
                            selectedLabelSpan.classList.remove('text-zinc-400', 'dark:text-zinc-500');
                            selectedLabelSpan.classList.add('text-zinc-950', 'dark:text-white');
                        } else if (selectedOption.disabled && !selectedOption.value) {
                            // Placeholder option selected (no value yet)
                            selectedLabelSpan.classList.add('text-zinc-400', 'dark:text-zinc-500');
                            selectedLabelSpan.classList.remove('text-zinc-900', 'dark:text-zinc-100');
                        } else {
                            selectedLabelSpan.classList.remove('text-zinc-400', 'dark:text-zinc-500');
                            selectedLabelSpan.classList.add('text-zinc-900', 'dark:text-zinc-100');
                        }
                    }
                    
                    // Mark active option
                    popover.querySelectorAll('[data-value]').forEach(item => {
                        const val = item.getAttribute('data-value');
                        const check = item.querySelector('.check-icon');
                        if (val === selectedValue) {
                            item.classList.add('bg-zinc-50', 'dark:bg-zinc-800', 'text-zinc-900', 'dark:text-white', 'font-bold');
                            check.classList.remove('hidden');
                        } else {
                            item.classList.remove('bg-zinc-50', 'dark:bg-zinc-800', 'text-zinc-900', 'dark:text-white', 'font-bold');
                            check.classList.add('hidden');
                        }
                    });
                }
            });
        }

        // Initialize custom date picker via Flatpickr
        function initMobileFlatpickr() {
            const dateInput = document.getElementById('filter-baslama-tarih');
            if (dateInput && !dateInput._flatpickr) {
                flatpickr(dateInput, {
                    locale: 'tr',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd.m.Y',
                    disableMobile: true, // Force standard desktop style UI calendar drop
                    placeholder: 'Tarih Seçin',
                    onChange: function() {
                        applyPersonnelFilters();
                    }
                });
            }

            const formDateInput = document.getElementById('form-baslama');
            if (formDateInput && !formDateInput._flatpickr) {
                flatpickr(formDateInput, {
                    locale: 'tr',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd.m.Y',
                    disableMobile: true, // Force standard desktop style UI calendar drop
                    placeholder: 'Giriş Tarihi Seçin'
                });
            }
        }

        async function handlePeriodCopy(e) {
            e.preventDefault();
            
            const from_donem = document.getElementById('copy-from-donem').value;
            const to_donem = document.getElementById('copy-to-donem').value.trim();
            const raise_percent = document.getElementById('copy-raise-percent').value;
            
            if (!to_donem || !raise_percent) {
                showToast("Lütfen tüm alanları doldurun.", "error");
                return;
            }
            
            const confirmMsg = `${from_donem} dönemindeki tüm ücret tanımları %${raise_percent} zam oranıyla ${to_donem} dönemine kopyalanacak. Emin misiniz?`;
            
            showConfirmDialog(
                'Dönem Kopyala & Zam Yap',
                confirmMsg,
                async () => {
                    try {
                        const basePath = '<?php echo appBasePath(); ?>';
                        const formData = new FormData();
                        formData.append('from_donem', from_donem);
                        formData.append('to_donem', to_donem);
                        formData.append('raise_percent', raise_percent);
                        
                        const response = await fetch(basePath + '/ucret-donem-kopyala', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            showToast(result.message, "success");
                            closeAllSheets();
                            
                            // Reset copy form inputs
                            document.getElementById('copy-to-donem').value = "";
                            document.getElementById('copy-raise-percent').value = "";
                            
                            // Switch current active period to the new target period and reload wages tab!
                            switchTab('definitions', null, { donem: to_donem });
                        } else {
                            showToast(result.error || "Bir hata oluştu.", "error");
                        }
                    } catch (err) {
                        showToast("İstek gönderilirken bağlantı hatası oluştu.", "error");
                    }
                },
                'Evet, Kopyala',
                'bg-indigo-600 hover:bg-indigo-700 text-white',
                'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400'
            );
        }

        // Turkish Currency Formatter Helper (Formats float number to XX.XXX,XX)
        function formatTurkishCurrency(value) {
            if (value === null || value === undefined || value === '') return '';
            let val = parseFloat(value);
            if (isNaN(val)) return value;
            
            // Format with dots as thousands separator and comma as decimal separator
            let parts = val.toFixed(2).split('.');
            let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return integerPart + ',' + parts[1];
        }

        // Dynamic Input Mask for Turkish Currency Format
        function initWageInputMask() {
            const input = document.getElementById('form-def-ucret');
            if (!input) return;
            
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                
                // Remove everything except numbers and comma
                value = value.replace(/[^0-9,]/g, '');
                
                // Ensure there is only one comma
                const parts = value.split(',');
                if (parts.length > 2) {
                    value = parts[0] + ',' + parts.slice(1).join('');
                }
                
                // Format the integer part with thousands separators (dots)
                let integerPart = parts[0];
                let decimalPart = parts[1];
                
                // Remove leading zeros from integer part
                if (integerPart.length > 1 && integerPart.startsWith('0')) {
                    integerPart = integerPart.replace(/^0+/, '');
                    if (integerPart === '') integerPart = '0';
                }
                
                // Add dots every three digits
                integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                
                // Reassemble the value
                if (decimalPart !== undefined) {
                    // Limit decimal part to 2 digits
                    decimalPart = decimalPart.substring(0, 2);
                    e.target.value = integerPart + ',' + decimalPart;
                } else {
                    e.target.value = integerPart;
                }
            });
        }

        // Click outside event listener to automatically dismiss custom select popovers
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.mobile-custom-select-wrapper')) {
                closeAllCustomSelectPopovers();
            }
        });

        // Initial tab loading on DOM ready if user is logged in
        window.addEventListener('DOMContentLoaded', () => {
            <?php if ($isLoggedIn): ?>
            // Initialize mobile custom selects on form fields
            initMobileCustomSelects();
            initMobileFlatpickr();
            initWageInputMask();

            const lastActiveTab = localStorage.getItem('last_active_tab') || 'home';
            const lastActiveSubpage = localStorage.getItem('last_active_subpage');
            
            let targetTab = lastActiveTab;
            if (targetTab === 'other') {
                if (lastActiveSubpage) {
                    loadOtherSubpage(lastActiveSubpage);
                } else {
                    switchTab('home');
                }
            } else {
                switchTab(targetTab);
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>



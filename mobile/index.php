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
    $stmt_ucret = $db->prepare("SELECT id, unvan, ucret, ogrenim, kidem_yili FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? ORDER BY unvan ASC");
    $stmt_ucret->execute([$tenant_id]);
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
    
    <!-- Theme Init -->
    <script src="../assets/js/theme.js"></script>
    
    <!-- Premium Google Fonts: Inter, Geist & Fira Code -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Geist:wght@100..900&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Quill snow theme CSS for document rendering -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <!-- Tailwind CSS 4 -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    
    <style>
        :root {
            --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
            font-family: var(--font-sans);
            --background: 0 0% 100%;
            --foreground: 240 10% 3.9%;
            --card: 0 0% 100%;
            --card-foreground: 240 10% 3.9%;
            --popover: 0 0% 100%;
            --popover-foreground: 240 10% 3.9%;
            --primary: 240 5.9% 10%;
            --primary-foreground: 0 0% 98%;
            --secondary: 240 4.8% 95.9%;
            --secondary-foreground: 240 5.9% 10%;
            --muted: 240 4.8% 95.9%;
            --muted-foreground: 240 3.8% 46.1%;
            --accent: 240 4.8% 95.9%;
            --accent-foreground: 240 5.9% 10%;
            --border: 240 5.9% 90%;
            --input: 240 5.9% 96%;
            --ring: 240 5.9% 10%;
        }

        .dark {
            --background: 240 10% 3.9%;
            --foreground: 0 0% 98%;
            --card: 240 10% 9%;
            --card-foreground: 0 0% 98%;
            --popover: 240 10% 9%;
            --popover-foreground: 0 0% 98%;
            --primary: 240 5.9% 90%;
            --primary-foreground: 240 5.9% 10%;
            --secondary: 240 3.7% 15.9%;
            --secondary-foreground: 0 0% 98%;
            --muted: 240 3.7% 15.9%;
            --muted-foreground: 240 5% 64.9%;
            --accent: 240 3.7% 15.9%;
            --accent-foreground: 0 0% 98%;
            --border: 240 3.7% 15.9%;
            --input: 240 3.7% 12%;
            --ring: 240 4.9% 83.9%;
        }

        /* Prevent elastic scroll on iOS */
        html, body {
            overflow: hidden;
            height: 100%;
            font-size: 0.875rem;
            background-color: hsl(var(--background));
            color: hsl(var(--foreground));
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* Desktop Mockup Framework */
        .desktop-bg {
            background: radial-gradient(circle at 50% 50%, #f4f4f5 0%, #e4e4e7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            transition: background 0.2s ease;
        }
        .dark .desktop-bg {
            background: radial-gradient(circle at 50% 50%, #18181b 0%, #09090b 100%);
        }

        .phone-frame {
            position: relative;
            width: 410px;
            height: 840px;
            background: hsl(var(--background));
            border-radius: 40px;
            padding: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1),
                        inset 0 0 2px 1px rgba(0, 0, 0, 0.05),
                        0 0 0 4px #e4e4e7;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .dark .phone-frame {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5),
                        inset 0 0 2px 1px rgba(255, 255, 255, 0.05),
                        0 0 0 4px #27272a;
        }

        /* Real Notch look */
        .notch {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 30px;
            background: hsl(var(--background));
            border-radius: 0 0 20px 20px;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-bottom: 1px solid hsl(var(--border));
            border-left: 1px solid hsl(var(--border));
            border-right: 1px solid hsl(var(--border));
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .notch-camera {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: hsl(var(--background));
            border: 2px solid hsl(var(--border));
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notch-camera-inner {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: hsl(var(--border));
        }

        .notch-speaker {
            width: 50px;
            height: 4px;
            border-radius: 2px;
            background: hsl(var(--border));
        }

        .screen-content {
            flex: 1;
            background: hsl(var(--background));
            border-radius: 28px;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: background-color 0.2s ease;
        }

        /* Responsive Breakpoint to make phone go fullscreen on mobile/tablet */
        @media (max-width: 1023px) {
            .desktop-bg {
                background: none !important;
                padding: 0 !important;
                min-height: 100% !important;
            }
            .phone-frame {
                width: 100% !important;
                height: 100% !important;
                max-height: 100% !important;
                border-radius: 0 !important;
                border: none !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
            .notch {
                display: none !important;
            }
            .screen-content {
                border-radius: 0 !important;
            }
        }

        /* Shadcn Cards */
        .glass-card {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            transition: all 0.2s ease-in-out;
        }

        .glass-card:active {
            transform: scale(0.98);
            border-color: hsl(var(--border));
            opacity: 0.9;
        }

        /* Custom Scrollbar for inner app */
        .app-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .app-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .app-scroll::-webkit-scrollbar-thumb {
            background: rgba(120, 120, 120, 0.2);
            border-radius: 2px;
        }

        /* Bottom Sheet Styling */
        .bottom-sheet {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: hsl(var(--card));
            border-top: 1px solid hsl(var(--border));
            border-radius: 20px 20px 0 0;
            transform: translateY(105%);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 50;
            box-shadow: 0 -10px 40px -10px rgba(0, 0, 0, 0.15);
        }
        .dark .bottom-sheet {
            box-shadow: 0 -10px 40px -10px rgba(0, 0, 0, 0.7);
        }

        .bottom-sheet.open {
            transform: translateY(0);
        }

        .bottom-sheet-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 40;
        }
        .dark .bottom-sheet-backdrop {
            background: rgba(9, 9, 11, 0.8);
        }

        .bottom-sheet-backdrop.open {
            opacity: 1;
            pointer-events: auto;
        }

        /* Custom Input */
        .mobile-input {
            background: hsl(var(--input));
            border: 1px solid hsl(var(--border));
            border-radius: 8px;
            color: hsl(var(--foreground));
            padding: 10px 14px;
            font-size: 0.9rem;
            width: 100%;
            transition: all 0.2s ease;
            outline: none;
        }
        .mobile-input:focus {
            border-color: hsl(var(--foreground));
            box-shadow: 0 0 0 2px rgba(120, 120, 120, 0.1);
        }

        /* Status Badges */
        .badge-aktif {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 6px;
        }
        .badge-pasif {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 6px;
        }
        .badge-kadro {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 6px;
        }

        /* Toast Popup */
        .mobile-toast {
            position: absolute;
            top: 24px;
            left: 16px;
            right: 16px;
            padding: 14px 20px;
            border-radius: 12px;
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(-150%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .dark .mobile-toast {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .mobile-toast.open {
            transform: translateY(0);
        }

        /* Swipe Actions Layout Styles */
        .swipe-container {
            position: relative;
            overflow: hidden;
        }
        .swipe-front {
            position: relative;
            z-index: 10;
            user-select: none;
            touch-action: pan-y; /* Flawless vertical scrolling, clean horizontal swipe */
        }

        /* Document Preview Page Simulated Look */
        .document-preview-wrapper {
            background-color: #f4f4f5;
            padding: 1rem 0.5rem;
            display: flex;
            justify-content: center;
            min-height: 100%;
            overflow-y: auto;
        }
        .dark .document-preview-wrapper {
            background-color: #09090b;
        }
        .document-preview-page {
            width: 100%;
            max-width: 794px; /* A4 Ratio */
            background-color: #ffffff;
            color: #000000;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 2.5rem 1.5rem;
            font-family: 'Times New Roman', Times, serif !important;
            line-height: 1.6;
            font-size: 10.5pt;
            text-align: justify;
            box-sizing: border-box;
            border-radius: 4px;
        }
        .document-preview-page * {
            font-family: 'Times New Roman', Times, serif !important;
            color: #000000 !important;
        }
        .document-preview-page.has-border {
            border: 3px double #000000;
        }
        /* Custom styles for Quill dynamic HTML to look identical to premium print sheets */
        .document-preview-page .ql-editor {
            padding: 0 !important;
        }
        .document-preview-page .ql-editor p {
            margin-bottom: 8px;
            text-indent: 1.5cm;
        }
        .document-preview-page .ql-editor p[style*="text-align: center"],
        .document-preview-page .ql-editor p[style*="text-align:center"] {
            text-indent: 0 !important;
        }

        /* Dark Mode Custom CSS Overrides */
        .dark body {
            background-color: #09090b !important;
            color: #f4f4f5 !important;
        }
        .dark .bg-white {
            background-color: #18181b !important;
        }
        .dark .glass-card {
            background: rgba(24, 24, 27, 0.8) !important;
            border-color: rgba(39, 39, 42, 0.5) !important;
        }
        .dark .text-zinc-900 {
            color: #f4f4f5 !important;
        }
        .dark .text-zinc-800 {
            color: #e4e4e7 !important;
        }
        .dark .text-zinc-700 {
            color: #d4d4d8 !important;
        }
        .dark .text-zinc-600 {
            color: #a1a1aa !important;
        }
        .dark .border-zinc-200 {
            border-color: #27272a !important;
        }
        .dark input, .dark select, .dark textarea {
            background-color: #09090b !important;
            color: #f4f4f5 !important;
            border-color: #27272a !important;
        }

        /* Unified List styles matching 3rd image */
        #personnel-list-wrapper {
            background-color: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .dark #personnel-list-wrapper {
            background-color: #18181b !important;
            border-color: #27272a !important;
            box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.4);
        }

        /* Swipe container overrides for single list integration */
        .swipe-container {
            background-color: #f4f4f5 !important;
            border: none !important;
            border-radius: 0 !important;
        }
        .dark .swipe-container {
            background-color: #09090b !important;
        }
        
        .swipe-front {
            border: none !important;
            border-radius: 0 !important;
            background-color: #ffffff !important;
        }
        .dark .swipe-front {
            background-color: #18181b !important;
        }

        /* Soft gray buttons in unified list in Dark Mode */
        .dark .bg-zinc-100 {
            background-color: #27272a !important;
            border-color: rgba(63, 63, 70, 0.3) !important;
        }
        .dark .hover\:bg-zinc-200:hover {
            background-color: #3f3f46 !important;
        }
        
        /* Select click pointer and selection disable override for mobile browsers */
        select, input, textarea {
            user-select: auto !important;
            -webkit-user-select: auto !important;
            pointer-events: auto !important;
        }
    </style>
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
                                <label class="text-sm font-semibold text-zinc-400" for="username">E-Posta / Kullanıcı Adı</label>
                                <input class="mobile-input" type="text" id="username" name="username" placeholder="ornek@kurum.com" required>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-zinc-400" for="password">Şifre</label>
                                <input class="mobile-input" type="password" id="password" name="password" placeholder="••••••••" required>
                            </div>
                            <button type="submit" class="w-full py-3.5 bg-zinc-50 hover:bg-zinc-200 text-zinc-950 active:scale-98 transition-all rounded-lg font-bold shadow-sm flex items-center justify-center gap-2 cursor-pointer mt-4">
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
                                    <button onclick="closeAllSheets(); switchTab('other'); loadOtherSubpage('tenants');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
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
                                    <button onclick="closeAllSheets(); switchTab('other'); loadOtherSubpage('users');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
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
                                    <button onclick="closeAllSheets(); switchTab('other'); loadOtherSubpage('subscription');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-900 dark:text-zinc-100 border border-zinc-200/40 dark:border-zinc-800/60 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-xs font-bold leading-tight">Abonelik & Limitler</h5>
                                            <p class="text-[9px] text-zinc-400 dark:text-zinc-500 tracking-tight mt-0.5">Kalan gün sayısı, deneme durumu ve limitler</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>

                                    <!-- Sözleşme Taslakları -->
                                    <button onclick="closeAllSheets(); switchTab('other'); loadOtherSubpage('template');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-900 dark:text-zinc-100 border border-zinc-200/40 dark:border-zinc-800/60 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-xs font-bold leading-tight">Sözleşme Taslakları</h5>
                                            <p class="text-[9px] text-zinc-400 dark:text-zinc-500 tracking-tight mt-0.5">Sözleşme şablonu ve yasal maddeler</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>

                                    <!-- Sistem Ayarları -->
                                    <button onclick="closeAllSheets(); switchTab('other'); loadOtherSubpage('settings');" class="w-full p-4 rounded-xl glass-card flex items-center gap-3.5 text-left transition-all active:scale-[0.98] border border-zinc-200/60 dark:border-zinc-800/80 cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-900 dark:text-zinc-100 border border-zinc-200/40 dark:border-zinc-800/60 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-xs font-bold leading-tight">Sistem & SMS Ayarları</h5>
                                            <p class="text-[9px] text-zinc-400 dark:text-zinc-500 tracking-tight mt-0.5">Bildirim süreleri, SMS API bilgileri ve entegrasyonlar</p>
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

                            <!-- Action Grid Buttons -->
                            <div class="grid grid-cols-2 gap-3.5">
                                <button id="btn-preview-contract" class="py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-md font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 active:scale-95 transition-all shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    Sözleşme Önizle
                                </button>
                                
                                <button id="btn-download-word" class="py-3.5 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-md font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 active:scale-95 transition-all shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5-5 5 5m-5-5v12"/></svg>
                                    Word İndir
                                </button>
                                
                                <button id="btn-edit-personnel" class="py-3.5 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-md font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 active:scale-95 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                    Kartı Düzenle
                                </button>
                                
                                <button id="btn-delete-personnel" class="py-3.5 bg-red-50 dark:bg-zinc-900 border border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-zinc-800 rounded-md font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 active:scale-95 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2M10 11v6m4-16v6"/></svg>
                                    Çalışanı Sil
                                </button>
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
                                    <label class="text-[0.78rem] font-bold text-zinc-400 uppercase tracking-wider block" for="form-ad">Ad Soyad*</label>
                                    <input class="mobile-input" type="text" id="form-ad" name="ad_soyad" required placeholder="Ad ve soyad girin">
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[0.78rem] font-bold text-zinc-400 uppercase tracking-wider block" for="form-tc">TC Kimlik No*</label>
                                    <input class="mobile-input" type="text" id="form-tc" name="tc_kimlik" required minlength="11" maxlength="11" placeholder="11 haneli TC no">
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[0.78rem] font-bold text-zinc-400 uppercase tracking-wider block" for="form-ucret-select">Unvan & Ücret Seçimi*</label>
                                    <select class="mobile-input" id="form-ucret-select" name="ucret_id" required>
                                        <option value="" disabled selected>Ücret Matrahı Seçin</option>
                                        <?php foreach ($ucretler as $u): ?>
                                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['unvan']) ?> - <?= htmlspecialchars($u['ogrenim']) ?> (<?= htmlspecialchars($u['kidem_yili']) ?>) - <?= number_format($u['ucret'] ?? 0, 2, ',', '.') ?> TL</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-[0.78rem] font-bold text-zinc-400 uppercase tracking-wider block" for="form-cinsiyet">Cinsiyet</label>
                                        <select class="mobile-input" id="form-cinsiyet" name="cinsiyet">
                                            <option value="erkek">Erkek</option>
                                            <option value="kadin">Kadın</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-[0.78rem] font-bold text-zinc-400 uppercase tracking-wider block" for="form-durum">Durum</label>
                                        <select class="mobile-input" id="form-durum" name="durum">
                                            <option value="aktif">Aktif</option>
                                            <option value="pasif">Pasif</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-[0.78rem] font-bold text-zinc-400 uppercase tracking-wider block" for="form-baslama">Giriş Tarihi*</label>
                                        <input class="mobile-input" type="date" id="form-baslama" name="goreve_baslama_tarihi" required>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-[0.78rem] font-bold text-zinc-400 uppercase tracking-wider block" for="form-meslek">Meslek Kodu</label>
                                        <input class="mobile-input" type="text" id="form-meslek" name="meslek_kodu" placeholder="Örn: 2512.02">
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[0.78rem] font-bold text-zinc-400 uppercase tracking-wider block" for="form-telefon">Telefon Numarası</label>
                                    <input class="mobile-input" type="tel" id="form-telefon" name="telefon" placeholder="Örn: 05301234567">
                                </div>

                                <button type="submit" class="w-full py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-md font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    Kaydet & Gönder
                                </button>
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
                            <h3 class="text-base font-extrabold text-white">Toplu Excel Yükleme</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">Toplu personel eklemek için hazırladığınız <strong>CSV</strong> şablon dosyasını aşağıdaki alandan yükleyebilirsiniz.</p>
                            
                            <div class="p-4 bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-md space-y-2">
                                <span class="text-[0.7rem] text-zinc-400 font-bold block">1. ADIM: ŞABLONU İNDİRİN</span>
                                <a href="<?= routeUrl('/personel-sample-template') ?>" class="inline-flex items-center gap-1.5 text-xs text-zinc-100 font-bold hover:underline">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5-5 5 5m-5-5v12"/></svg>
                                    Örnek CSV Şablonu İndir
                                </a>
                            </div>

                            <div class="space-y-2">
                                <span class="text-[0.7rem] text-zinc-400 font-bold block">2. ADIM: CSV DOSYASI SEÇİN</span>
                                <input type="file" id="import-file-input" accept=".csv" class="mobile-input p-3 block text-xs">
                            </div>

                            <button onclick="handleExcelUpload()" class="w-full py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-md font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5-5 5 5m-5-5v12"/></svg>
                                Yüklemeyi Tamamla
                            </button>
                        </div>
                    </div>

                </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Premium Native JavaScript Mechanics -->
    <script>
        // Global variables holding current page state
        let currentTab = 'home';
        let selectedPersonnelCard = null;
        let isTcMasked = true;

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

                    // Elastic bound limits matching the full height w-14 columns (total 112px right, -56px left)
                    if (currentX > 125) currentX = 125 + (currentX - 125) * 0.15;
                    if (currentX < -70) currentX = -70 + (currentX + 70) * 0.15;

                    front.style.transform = `translateX(${currentX}px)`;
                });

                const handleEnd = (e) => {
                    if (!isDragging) return;
                    isDragging = false;
                    front.style.transition = 'transform 0.25s cubic-bezier(0.16, 1, 0.3, 1)';
                    
                    const finalX = getTransformX();
                    
                    if (finalX > 45) {
                        // Snap right (reveal Left buttons: Önizle & Dilekçe - total 112px wide)
                        front.style.transform = 'translateX(112px)';
                    } else if (finalX < -30) {
                        // Snap left (reveal Right button: Sil - 56px wide)
                        front.style.transform = 'translateX(-56px)';
                    } else {
                        // Reset
                        front.style.transform = 'translateX(0px)';
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
                        }
                    }
                }, true); // Use capture phase to intercept click handlers immediately
            });
        }

        // Switch bottom navigation tabs smoothly and fetch content dynamically
        async function switchTab(tabName, callback = null) {
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
                    else if (tabName === 'other') pagePath = 'other';
                    else if (tabName === 'profile') pagePath = 'profil';

                    const basePath = '<?php echo appBasePath(); ?>';
                    const response = await fetch(basePath + `/mobile/pages/${pagePath}/index.php`);
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
                    
                    // Trigger swiping engine if loading personnel
                    if (tabName === 'personnel') {
                        initSwipeActions();
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

        // Load other actions subpages dynamically
        async function loadOtherSubpage(subpageName) {
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
                    const response = await fetch(basePath + `/mobile/pages/other/${subpageName}.php`);
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
                document.getElementById('form-baslama').value = formattedDate;
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

            let eklerContent = '';
            if (cinsiyet === 'kadin') {
                eklerContent = '<p style="margin-bottom: 12pt;">3- Tam teşekküllü devlet hastanesi ya da Üniversite hastanesinden alınacak sağlık kurulu (heyet) raporu (aslı ve fotokopisi ya da e-devlet çıktısı)</p>';
            } else {
                eklerContent = '<p style="margin-bottom: 4pt;">3- Askerlik Durum Belgesi (güncel e-devlet çıktısı) / Askerliğini yapanlar için Terhis Belgesi aslı ve fotokopisi,</p>' +
                               '<p style="margin-bottom: 12pt;">4- Tam teşekküllü devlet hastanesi ya da Üniversite hastanesinden alınacak sağlık kurulu (heyet) raporu (aslı ve fotokopisi ya da e-devlet çıktısı)</p>';
            }

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
                eklerContent +
                '<p style="margin-bottom: 8pt;"><strong><u>ADRES:</u></strong> ...................................................................</p>' +
                '<p style="margin-bottom: 8pt;"><strong><u>TEL:</u></strong> ' + telefon + '</p>';

            let contentHtml = defaultContent;
            if (customPetitionTemplate) {
                let temp = customPetitionTemplate;
                if (cinsiyet === 'kadin') {
                    const paragraphs = temp.match(/<p[^>]*>.*?<\/p>/gi);
                    if (paragraphs) {
                        temp = paragraphs.filter(p => !p.toLowerCase().includes('askerlik')).join('');
                        temp = temp.replace(/4-\s*Tam teşekküllü/gi, '3- Tam teşekküllü');
                    }
                }
                contentHtml = temp
                    .replace(/\{\{UNVAN\}\}/g, unvan)
                    .replace(/\{\{AD_SOYAD\}\}/g, name)
                    .replace(/\{\{TC_NO\}\}/g, tc)
                    .replace(/\{\{GOREVE_BASLAMA\}\}/g, baslama)
                    .replace(/\{\{TELEFON\}\}/g, telefon || '...................................................')
                    .replace(/\{\{TODAY\}\}/g, todayStr);
            }

            const contentArea = document.getElementById('preview-content-area');
            contentArea.innerHTML = `
                <div class="ql-container ql-snow" style="border:none">
                    <div class="ql-editor">${contentHtml}</div>
                </div>
            `;
            contentArea.classList.remove('has-border');

            // Set UI details
            document.getElementById('preview-title').innerText = "Dilekçe Önizleme";
            
            // Hide word download button, bind print
            document.getElementById('btn-preview-download').classList.add('hidden');
            document.getElementById('btn-preview-print').onclick = () => printDocumentMarkup(name, "Dilekçe", contentHtml, false);

            document.getElementById('preview-modal').classList.remove('hidden');
        }

        // Clean Print helper
        function printDocumentMarkup(name, docType, contentHtml, hasBorder = false) {
            const printWindow = window.open('', '_blank');
            
            let printStyles = '';
            if (docType === 'Dilekçe') {
                printStyles = `
                    @page { size: A4 portrait; margin: 2cm !important; }
                    * { box-sizing: border-box !important; }
                    body { 
                        font-family: "Times New Roman", Times, serif !important; 
                        margin: 0 !important; 
                        padding: 0 !important; 
                        background: white !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    p, div, span, strong, em, li {
                        font-family: "Times New Roman", Times, serif !important;
                        font-size: 11pt !important;
                        line-height: 1.6 !important;
                    }
                    .ql-editor {
                        padding: 0 !important;
                        font-family: "Times New Roman", Times, serif !important;
                        font-size: 11pt !important;
                        line-height: 1.6 !important;
                        text-align: justify;
                    }
                    .ql-editor p {
                        margin-bottom: 8px;
                        text-indent: 1.5cm;
                    }
                    .ql-editor p[style*="text-align: center"],
                    .ql-editor p[style*="text-align:center"] {
                        text-indent: 0 !important;
                    }
                `;
            } else {
                // Contract print styles
                printStyles = `
                    @page { size: A4 portrait; margin: 0 !important; }
                    * { box-sizing: border-box !important; }
                    html, body { 
                        margin: 0 !important; 
                        padding: 0 !important; 
                        background: white !important;
                        font-family: 'Times New Roman', Times, serif;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    .page-container { 
                        width: 210mm !important; 
                        height: auto !important; 
                        min-height: 297mm !important;
                        padding: 2.5cm 2cm 2.5cm !important;
                        margin: 0 auto !important; 
                        background: white !important; 
                        position: relative !important;
                        box-sizing: border-box !important;
                        overflow: visible !important;
                    }
                    .page-container.has-border {
                        border: 3px double #000;
                    }
                    .ql-editor {
                        padding: 0 !important;
                        font-family: "Times New Roman", Times, serif !important;
                        line-height: 1.6 !important;
                        text-align: justify;
                    }
                `;
            }

            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="tr">
                    <head>
                        <meta charset="UTF-8">
                        <title>${docType} - ${name}</title>
                        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                        <style>${printStyles}</style>
                    </head>
                    <body>
                        <div class="page-container ${hasBorder ? 'has-border' : ''}">
                            <div class="ql-container ql-snow" style="border:none">
                                <div class="ql-editor">
                                    ${contentHtml}
                                </div>
                            </div>
                        </div>
                        <script>
                            window.onload = function() {
                                setTimeout(() => {
                                    window.print();
                                    window.close();
                                }, 800);
                            };
                        <\/script>
                    </body>
                </html>
            `);
            printWindow.document.close();
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
                        document.getElementById('btn-preview-print').onclick = () => printDocumentMarkup(data.personnel_name, "Sözleşme", data.content, data.has_border);
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
        }

        // Filter Sheet opening
        function openFilterSheet() {
            closeAllSheets();
            const sheet = document.getElementById('filter-sheet');
            const backdrop = document.getElementById('sheet-backdrop');
            if (sheet && backdrop) {
                sheet.classList.add('open');
                backdrop.classList.add('open');
            }
        }

        // Clear all filters
        function clearAllFilters() {
            const unvanSelect = document.getElementById('filter-unvan');
            const durumSelect = document.getElementById('filter-durum');
            const baslamaSelect = document.getElementById('filter-baslama-yili');
            
            if (unvanSelect) unvanSelect.value = '';
            if (durumSelect) durumSelect.value = '';
            if (baslamaSelect) baslamaSelect.value = '';
            
            applyPersonnelFilters();
            closeAllSheets();
        }

        // Apply Personnel Filters dynamically in JS (extremely fast SPA feel)
        function applyPersonnelFilters() {
            const unvanSelect = document.getElementById('filter-unvan');
            const durumSelect = document.getElementById('filter-durum');
            const baslamaSelect = document.getElementById('filter-baslama-yili');
            const searchInput = document.getElementById('personnelSearch');

            const unvan = unvanSelect ? unvanSelect.value.toLowerCase() : '';
            const durum = durumSelect ? durumSelect.value.toLowerCase() : '';
            const yil = baslamaSelect ? baslamaSelect.value : '';
            const searchVal = searchInput ? searchInput.value.toLowerCase() : '';

            const cards = document.querySelectorAll('.personnel-item-card');
            let countVisible = 0;

            cards.forEach(card => {
                const cardName = (card.getAttribute('data-name') || '').toLowerCase();
                const cardTc = (card.getAttribute('data-tc') || '').toLowerCase();
                const cardUnvan = (card.getAttribute('data-unvan') || '').toLowerCase();
                const cardDurum = (card.getAttribute('data-durum') || '').toLowerCase();
                const cardBaslama = card.getAttribute('data-baslama') || ''; // dd.mm.yyyy

                const matchesSearch = cardName.includes(searchVal) || cardTc.includes(searchVal);
                const matchesUnvan = !unvan || cardUnvan === unvan;
                const matchesDurum = !durum || cardDurum === durum;
                
                let matchesYil = true;
                if (yil) {
                    const parts = cardBaslama.split('.');
                    if (parts.length === 3) {
                        matchesYil = parts[2] === yil;
                    } else {
                        matchesYil = false;
                    }
                }

                if (matchesSearch && matchesUnvan && matchesDurum && matchesYil) {
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

            closeAllSheets();
        }

        // Download Word Document
        function downloadWord(id) {
            const basePath = '<?php echo appBasePath(); ?>';
            window.location.href = basePath + '/personel-download-word?id=' + id;
            showToast('Sözleşme indirme işlemi başlatıldı.');
        }

        // Show a premium confirm dialog modal (Shadcn styled)
        function showConfirmDialog(title, message, onConfirm) {
            let confirmModal = document.getElementById('confirm-dialog-modal');
            if (!confirmModal) {
                confirmModal = document.createElement('div');
                confirmModal.id = 'confirm-dialog-modal';
                confirmModal.className = 'fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-6 hidden opacity-0 transition-opacity duration-200';
                confirmModal.innerHTML = `
                    <div class="w-full max-w-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl p-5 space-y-4 scale-95 transition-transform duration-200 transform">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-rose-500/10 flex items-center justify-center text-rose-500 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                            </div>
                            <h4 id="confirm-title" class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50 leading-tight">İşlem Onayı</h4>
                        </div>
                        <p id="confirm-message" class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed font-medium">Bu işlemi gerçekleştirmek istediğinize emin misiniz?</p>
                        <div class="flex items-center justify-end gap-2.5 pt-2">
                            <button id="confirm-btn-cancel" class="px-3.5 py-2 rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-zinc-100 text-xs font-bold transition-all cursor-pointer">Vazgeç</button>
                            <button id="confirm-btn-ok" class="px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all cursor-pointer shadow-sm">Evet, Sil</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(confirmModal);
            }

            const titleEl = confirmModal.querySelector('#confirm-title');
            const messageEl = confirmModal.querySelector('#confirm-message');
            const btnCancel = confirmModal.querySelector('#confirm-btn-cancel');
            const btnOk = confirmModal.querySelector('#confirm-btn-ok');

            titleEl.innerText = title;
            messageEl.innerText = message;

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

        // Initial tab loading on DOM ready if user is logged in
        window.addEventListener('DOMContentLoaded', () => {
            <?php if ($isLoggedIn): ?>
            const lastActiveTab = localStorage.getItem('last_active_tab') || 'home';
            const lastActiveSubpage = localStorage.getItem('last_active_subpage');
            
            if (lastActiveTab === 'other' && lastActiveSubpage) {
                switchTab('other', () => {
                    loadOtherSubpage(lastActiveSubpage);
                });
            } else {
                switchTab(lastActiveTab);
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>



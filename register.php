<?php
$tenantName = $_POST['tenant_name'] ?? '';
$userName = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($tenantName) && !empty($userName) && !empty($email) && !empty($password)) {
        try {
            global $db;

            $tenantModel = new Tenant();
            $userModel = new User();

            // Basic slug generation
            $slug = mb_strtolower($tenantName, 'UTF-8');
            $slug = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $slug);
            $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
            $slug = preg_replace('/[\s-]+/', ' ', $slug);
            $slug = preg_replace('/\s/', '-', $slug);
            $slug = trim($slug, '-');

            if (empty($slug)) {
                throw new Exception("Geçerli bir kurum adı giriniz.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Lütfen geçerli bir e-posta adresi giriniz.");
            }

            if (strlen($password) < 6) {
                throw new Exception("Parola en az 6 karakter uzunluğunda olmalıdır.");
            }

            // Pre-validation to provide clear errors
            if (!empty($tenantModel->where('slug', $slug))) {
                throw new Exception("Bu kurum adı veya benzeri daha önce alınmış. Lütfen farklı bir kurum adı giriniz.");
            }

            if (!empty($userModel->where('email', $email))) {
                throw new Exception("Bu e-posta adresi zaten kullanımda. Lütfen başka bir e-posta adresi giriniz.");
            }

            $db->beginTransaction();

            // Create Tenant
            $tenantId = $tenantModel->create([
                'name' => $tenantName,
                'slug' => $slug,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Create User
            $userId = $userModel->create([
                'name' => $userName,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin', // İlk kayıt olan admin olur
                'tenant_id' => $tenantId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'trial_ends_at' => date('Y-m-d', strtotime('+1 month'))
            ]);

            // Associate user with tenant
            $tenantModel->associateWithUser($tenantId, $userId, 'admin');

            $db->commit();

            // Auto login after register
            $user = $userModel->findByUsername($email);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['tenant_id'] = $user['tenant_id'];
                
                header("Location: " . routeUrl('/'));
                exit;
            }

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $msg = $e->getMessage();
            if (strpos(strtolower($msg), 'select') === false && strpos(strtolower($msg), 'insert') === false && strpos(strtolower($msg), 'duplicate') === false && strpos(strtolower($msg), 'sqlstate') === false) {
                $error = $msg;
            } elseif (strpos(strtolower($msg), 'duplicate') !== false || strpos(strtolower($msg), '1062') !== false) {
                if (strpos(strtolower($msg), 'email') !== false) {
                    $error = "Bu e-posta adresi zaten kullanımda. Lütfen başka bir e-posta adresi giriniz.";
                } elseif (strpos(strtolower($msg), 'slug') !== false || strpos(strtolower($msg), 'tenants') !== false) {
                    $error = "Bu kurum adı veya benzeri zaten kullanımda. Lütfen başka bir kurum adı deneyin.";
                } else {
                    $error = "Girilen bilgilerden bazıları zaten sistemde kayıtlı. Lütfen bilgilerinizi kontrol edin.";
                }
            } else {
                $error = "Kayıt işlemi sırasında bir hata oluştu. Lütfen bilgilerinizi kontrol edip tekrar deneyiniz.";
            }
        }
    } else {
        $error = "Lütfen tüm alanları doldurun.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Sözleşme 4B</title>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo routeUrl('/assets/images/favicon.svg'); ?>" type="image/svg+xml">
    <!-- Premium Google Fonts: Geist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <link rel="stylesheet" href="<?php echo routeUrl('/assets/css/app.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>
    <style>
        * {
            font-family: 'Geist', sans-serif !important;
        }
        /* Custom UI Overrides to protect against global CSS pollution */
        .bg-white-forced {
            background-color: #ffffff !important;
        }
        .text-zinc-950-forced {
            color: #09090b !important;
        }
        .border-zinc-800-forced {
            border-color: rgba(39, 39, 42, 0.8) !important;
        }
        .bg-zinc-900-forced {
            background-color: rgba(24, 24, 27, 0.75) !important;
        }
        .text-white-forced {
            color: #ffffff !important;
        }
        .text-zinc-400-forced {
            color: #a1a1aa !important;
        }
        .text-zinc-500-forced {
            color: #71717a !important;
        }
        .text-zinc-300-forced {
            color: #d4d4d8 !important;
        }
        .login-input {
            padding-left: 2.5rem !important;
            padding-right: 1rem !important;
            padding-top: 0.625rem !important;
            padding-bottom: 0.625rem !important;
            border-radius: 0.75rem !important;
            font-size: 0.875rem !important;
            transition: all 0.2s ease !important;
        }
        .login-input:focus {
            border-color: #09090b !important;
            box-shadow: 0 0 0 3px rgba(9, 9, 11, 0.08) !important;
            outline: none !important;
        }
        .dark .login-input:focus,
        html.dark .login-input:focus {
            border-color: #f4f4f5 !important;
            box-shadow: 0 0 0 3px rgba(244, 244, 245, 0.1) !important;
        }
        .pr-10-forced {
            padding-right: 2.5rem !important;
        }
    </style>
</head>
<body class="h-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-50 antialiased selection:bg-indigo-500/30">
    <!-- Centered Register Card Layout -->
    <div class="min-h-screen flex items-center justify-center p-4 bg-zinc-50 dark:bg-zinc-950 relative overflow-hidden">
        <!-- Subtle Background Glow -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(99,102,241,0.02),transparent_70%)]"></div>
        
        <div class="w-full max-w-[400px] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 sm:p-8 shadow-sm relative z-10 space-y-6">
            
            <!-- Brand Header -->
            <div class="text-center flex flex-col items-center">
                <div class="flex size-11 items-center justify-center rounded-xl bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-950 mb-3.5 shadow-sm transition-transform hover:scale-105 duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m7 17 10-10" />
                        <path d="m13 17 4-4" opacity="0.5" />
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-50 mb-0.5">
                    Sözleşme <span class="bg-gradient-to-r from-indigo-500 to-indigo-600 dark:from-indigo-400 dark:to-indigo-500 bg-clip-text text-transparent">4B</span>
                </h1>
                <p class="text-[9px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-widest">
                    Kurumsal Sözleşme & Yönetim Sistemi
                </p>
            </div>

            <!-- Section Header -->
            <div class="text-center">
                <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Kayıt Ol</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Yeni bir kurum hesabı oluşturun</p>
            </div>

            <!-- Errors -->
            <?php if (isset($error)): ?>
              <div class="alert-destructive text-left w-full border border-red-200/50 dark:border-red-900/30 bg-red-50/50 dark:bg-red-950/20 p-3.5 rounded-xl flex gap-2.5 text-red-900 dark:text-red-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-red-600 dark:text-red-400 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" x2="12" y1="8" y2="12" />
                    <line x1="12" x2="12.01" y1="16" y2="16" />
                </svg>
                <div>
                    <h4 class="font-semibold text-xs">Kayıt Hatası</h4>
                    <p class="text-[11px] text-red-700 dark:text-red-300 mt-0.5"><?php echo $error; ?></p>
                </div>
              </div>
            <?php endif; ?>

            <form id="registerForm" class="space-y-4" action="<?php echo htmlspecialchars(routeUrl('/register'), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                <!-- Tenant Name (Kurum Adı) -->
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider" for="tenant_name">Kurum Adı</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-450 dark:text-zinc-500 group-focus-within:text-zinc-900 dark:group-focus-within:text-zinc-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2"/><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                        </span>
                        <input class="w-full login-input border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-sm font-medium text-zinc-900 dark:text-zinc-50 placeholder:text-zinc-450 focus:outline-none transition-all duration-200 shadow-sm" type="text" id="tenant_name" name="tenant_name" value="<?php echo htmlspecialchars($tenantName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Kurumunuzun adını girin" required>
                    </div>
                </div>

                <!-- Name (Ad Soyad) -->
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider" for="name">Ad Soyad</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-450 dark:text-zinc-500 group-focus-within:text-zinc-900 dark:group-focus-within:text-zinc-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input class="w-full login-input border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-sm font-medium text-zinc-900 dark:text-zinc-50 placeholder:text-zinc-450 focus:outline-none transition-all duration-200 shadow-sm" type="text" id="name" name="name" value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Adınızı ve soyadınızı girin" required>
                    </div>
                </div>

                <!-- Email (E-posta) -->
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider" for="email">E-posta</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-450 dark:text-zinc-500 group-focus-within:text-zinc-900 dark:group-focus-within:text-zinc-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        </span>
                        <input class="w-full login-input border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-sm font-medium text-zinc-900 dark:text-zinc-50 placeholder:text-zinc-450 focus:outline-none transition-all duration-200 shadow-sm" type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="E-posta adresinizi girin" required>
                    </div>
                </div>

                <!-- Password (Parola) -->
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300" for="password">Parola</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-455 dark:text-zinc-500 group-focus-within:text-zinc-900 dark:group-focus-within:text-zinc-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input class="w-full login-input pr-10-forced border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-sm font-medium text-zinc-900 dark:text-zinc-50 placeholder:text-zinc-455 focus:outline-none transition-all duration-200 shadow-sm" type="password" id="password" name="password" placeholder="Parolanızı belirleyin" required>
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 dark:text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon hidden"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Button -->
                <button class="w-full py-2.5 px-4 rounded-xl bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 font-semibold text-xs tracking-wide uppercase transition-all duration-200 shadow-sm active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2 group mt-2" type="submit">
                    <span>Kayıt Ol</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>

                <!-- Footer Link -->
                <p class="text-center text-xs text-zinc-500 dark:text-zinc-400 pt-1">
                    Zaten bir hesabınız var mı? 
                    <a href="<?php echo routeUrl('/login'); ?>" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 transition-colors hover:underline underline-offset-4 ml-1">Giriş Yap</a>
                </p>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        if (togglePassword) {
            const passwordInput = document.getElementById('password');
            const eyeIcon = togglePassword.querySelector('.eye-icon');
            const eyeOffIcon = togglePassword.querySelector('.eye-off-icon');

            togglePassword.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.classList.add('hidden');
                    eyeOffIcon.classList.remove('hidden');
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.classList.remove('hidden');
                    eyeOffIcon.classList.add('hidden');
                }
            });
        }
    });
    </script>
</body>
</html>

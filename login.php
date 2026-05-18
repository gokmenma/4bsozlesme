<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['username'] ?? ''; // Kullanıcı adı olarak email kullanıyoruz
    $password = $_POST['password'] ?? '';

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (!empty($email) && !empty($password)) {
        $userModel = new User();
        // findByUsername aslında email ile arama yapacak şekilde güncellenmeli veya modelde yeni method eklenmeli
        // Mevcut User modeline baktığımızda findByUsername var. Onu kullanalım.
        $user = $userModel->findByUsername($email);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['tenant_id'] = $user['tenant_id'];
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => routeUrl('/')]);
                exit;
            }
            
            header("Location: " . routeUrl('/'));
            exit;
        } else {
            $error = "Hatalı e-posta veya parola.";
        }
    } else {
        $error = "Lütfen e-posta ve parola girin.";
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Sözleşme 4B</title>
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
    <!-- Centered Login Card Layout -->
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
                <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Giriş Yap</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Devam etmek için hesabınıza giriş yapın</p>
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
                    <h4 class="font-semibold text-xs">Giriş Hatası</h4>
                    <p class="text-[11px] text-red-700 dark:text-red-300 mt-0.5"><?php echo $error; ?></p>
                </div>
              </div>
            <?php endif; ?>

            <form id="loginForm" class="space-y-4" action="<?php echo htmlspecialchars(function_exists('routeUrl') ? routeUrl('/login') : 'login', ENT_QUOTES, 'UTF-8'); ?>" method="post">
                <!-- Username / Email -->
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider" for="username">Kullanıcı Adı</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-450 dark:text-zinc-500 group-focus-within:text-zinc-900 dark:group-focus-within:text-zinc-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input class="w-full login-input border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-sm font-medium text-zinc-900 dark:text-zinc-50 placeholder:text-zinc-450 focus:outline-none transition-all duration-200 shadow-sm" type="text" id="username" name="username" placeholder="Kullanıcı adınızı girin" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider" for="password">Parola</label>
                    </div>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-450 dark:text-zinc-500 group-focus-within:text-zinc-900 dark:group-focus-within:text-zinc-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input class="w-full login-input pr-10-forced border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-sm font-medium text-zinc-900 dark:text-zinc-50 placeholder:text-zinc-455 focus:outline-none transition-all duration-200 shadow-sm" type="password" id="password" name="password" placeholder="Parolanızı girin" required>
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 dark:text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon hidden"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Button -->
                <button class="w-full py-2.5 px-4 rounded-xl bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 font-semibold text-xs tracking-wide uppercase transition-all duration-200 shadow-sm active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2 group mt-2" type="submit">
                    <span>Giriş Yap</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>

                <!-- Footer Link -->
                <p class="text-center text-xs text-zinc-500 dark:text-zinc-400 pt-1">
                    Henüz hesabınız yok mu? 
                    <a href="<?php echo routeUrl('/register'); ?>" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 transition-colors hover:underline underline-offset-4 ml-1">Kayıt Ol</a>
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

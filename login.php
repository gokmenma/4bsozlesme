<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (!empty($usernameInput) && !empty($password)) {
        $userModel = new User();
        $user = $userModel->findByUsername($usernameInput);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_username'] = !empty($user['username']) ? $user['username'] : explode('@', $user['email'])[0];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['role_id'] = $user['role_id'] ?? null;
            $activeTenantId = $user['tenant_id'];
            if (!empty($activeTenantId)) {
                $tCheck = $userModel->getDb()->prepare("SELECT id FROM tenants WHERE id = ?");
                $tCheck->execute([$activeTenantId]);
                if (!$tCheck->fetch()) {
                    $activeTenantId = null;
                }
            }
            if (empty($activeTenantId)) {
                $userTenants = $userModel->getTenants($user['id']);
                if (!empty($userTenants)) {
                    $activeTenantId = $userTenants[0]['id'];
                } else {
                    $firstTenant = $userModel->getDb()->query("SELECT id FROM tenants ORDER BY id ASC LIMIT 1")->fetch();
                    $activeTenantId = $firstTenant['id'] ?? null;
                }
                if ($activeTenantId) {
                    $userModel->update($user['id'], ['tenant_id' => $activeTenantId]);
                }
            }
            $_SESSION['tenant_id'] = $activeTenantId;

            // Beni Hatırla (Remember Me) İşlemleri
            $remember = !empty($_POST['remember']);
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            if ($remember) {
                $rememberToken = bin2hex(random_bytes(32));
                $userModel->updateRememberToken($user['id'], $rememberToken);
                setcookie('remember_token', $user['id'] . ':' . $rememberToken, [
                    'expires' => time() + (30 * 86400),
                    'path' => '/',
                    'secure' => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                setcookie('remember_user', $usernameInput, [
                    'expires' => time() + (30 * 86400),
                    'path' => '/',
                    'secure' => $isSecure,
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
            } else {
                $userModel->updateRememberToken($user['id'], null);
                setcookie('remember_token', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'httponly' => true
                ]);
                setcookie('remember_user', '', [
                    'expires' => time() - 3600,
                    'path' => '/'
                ]);
            }
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => routeUrl('/')]);
                exit;
            }
            
            header("Location: " . routeUrl('/'));
            exit;
        } else {
            $error = "Hatalı e-posta / kullanıcı adı veya parola.";
        }
    } else {
        $error = "Lütfen e-posta / kullanıcı adı ve parola girin.";
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
    <!-- Favicon -->
    <link rel="icon" href="<?php echo routeUrl('/assets/images/favicon.svg'); ?>" type="image/svg+xml">
    <!-- Premium Google Fonts: Geist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <!-- Basecoat UI -->
    <link rel="stylesheet" href="https://unpkg.com/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <script src="https://unpkg.com/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="<?php echo routeUrl('/assets/css/app.css'); ?>">
    <style>
        * {
            font-family: 'Geist', sans-serif !important;
        }
    </style>
</head>
<body class="h-full bg-zinc-50/50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-50 antialiased selection:bg-zinc-950/10">
    <!-- Centered Login Layout -->
    <div class="min-h-screen flex flex-col items-center justify-center p-6 relative">
        
        <!-- Brand Header (Horizontal Layout matching Image) -->
        <div class="flex items-center justify-center gap-3 mb-6 select-none">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-950 shadow-sm">
                <!-- Balance/Gavel Icon matching the image style -->
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11.5 15H7a4 4 0 0 0-4 4v2" />
                    <path d="M11.5 11.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
                    <path d="M16 21.16a1 1 0 0 1-1.2-1.2l.3-1a1.76 1.76 0 0 1 .4-.6l4.9-4.9a1.2 1.2 0 0 1 1.7 1.7l-4.9 4.9a1.76 1.76 0 0 1-.6.4z" />
                </svg>
            </div>
            <span class="text-xl font-bold text-zinc-900 dark:text-zinc-50 tracking-tight">Sözleşme 4B</span>
        </div>

        <!-- Login Card -->
        <div class="w-full max-w-[420px] bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] relative z-10 space-y-6">
            
            <!-- Header Texts -->
            <div class="text-center space-y-1">
                <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Hesabınıza Giriş Yapın</h2>
                <p class="text-xs text-zinc-450 dark:text-zinc-400">E-posta veya kullanıcı adınızı kullanarak giriş yapın</p>
            </div>

            <!-- Errors -->
            <?php if (isset($error)): ?>
              <div class="alert-destructive text-left w-full border border-red-200/50 dark:border-red-900/30 bg-red-50/50 dark:bg-red-950/20 p-3.5 rounded-xl flex gap-2.5 text-red-900 dark:text-red-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-red-650 dark:text-red-400 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300" for="username">E-posta veya Kullanıcı Adı</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400 dark:text-zinc-500 group-focus-within:text-zinc-900 dark:group-focus-within:text-zinc-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input class="w-full pl-10 pr-4 py-2.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-sm placeholder:text-zinc-400 focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-100 focus:ring-1 focus:ring-zinc-950/20 rounded-xl transition-all duration-200" type="text" id="username" name="username" value="<?php echo htmlspecialchars($_COOKIE['remember_user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="kullanici@firma.com" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300" for="password">Şifre</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400 dark:text-zinc-500 group-focus-within:text-zinc-900 dark:group-focus-within:text-zinc-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input class="w-full pl-10 pr-10 py-2.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-sm placeholder:text-zinc-400 focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-100 focus:ring-1 focus:ring-zinc-950/20 rounded-xl transition-all duration-200" type="password" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-zinc-400 hover:text-zinc-650 transition-colors cursor-pointer focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon hidden"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between pt-1">
                    <div role="group" class="fieldset">
                      <div role="group" class="field cursor-pointer" data-orientation="horizontal">
                        <input type="checkbox" id="remember" name="remember" class="input cursor-pointer" <?php echo (!empty($_COOKIE['remember_user']) || !empty($_COOKIE['remember_token'])) ? 'checked' : ''; ?> />
                        <label for="remember" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">Beni Hatırla</label>
                      </div>
                    </div>
                    <a href="#" class="text-xs font-medium text-zinc-900 dark:text-zinc-100 hover:underline">Şifremi Unuttum</a>
                </div>

                <!-- Submit Button -->
                <button class="w-full py-3 px-4 rounded-xl bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-850 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 font-bold text-sm tracking-wide transition-all duration-200 shadow-sm active:scale-[0.99] cursor-pointer mt-3" type="submit">
                    Giriş Yap
                </button>
            </form>
        </div>

        <!-- Footer Link (matches Image placement) -->
        <p class="text-center text-xs text-zinc-450 dark:text-zinc-500 mt-6">
            Henüz hesabınız yok mu? 
            <a href="<?php echo routeUrl('/register'); ?>" class="font-bold text-zinc-900 dark:text-zinc-50 hover:underline ml-1">Kayıt Ol</a>
        </p>
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

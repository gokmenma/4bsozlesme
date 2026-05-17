<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['username'] ?? ''; // Kullanıcı adı olarak email kullanıyoruz
    $password = $_POST['password'] ?? '';

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
            
            header("Location: " . routeUrl('/'));
            exit;
        } else {
            $error = "Hatalı e-posta veya parola.";
        }
    } else {
        $error = "Lütfen e-posta ve parola girin.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <!-- Premium Google Fonts: Geist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Geist', sans-serif !important;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <link rel="stylesheet" href="<?php echo routeUrl('/assets/css/app.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>
</head>
<body>
    <section class="min-h-svh flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-6">
      <div class="text-center flex flex-col items-center">
        <!-- Logo -->
        <div class="flex size-16 items-center justify-center rounded-2xl bg-zinc-900 dark:bg-zinc-100 text-white dark:text-black mb-4 shadow-xl shadow-zinc-900/10 dark:shadow-zinc-100/5 transition-transform hover:scale-105 duration-300 cursor-pointer">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="animate-pulse">
            <path d="m7 17 10-10" />
            <path d="m13 17 4-4" opacity="0.5" />
          </svg>
        </div>
        <!-- Program Name -->
        <h1 class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-50 mb-1">
          Sözleşme <span class="bg-gradient-to-r from-indigo-500 to-indigo-600 dark:from-indigo-400 dark:to-indigo-500 bg-clip-text text-transparent">4B</span>
        </h1>
        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 font-semibold uppercase tracking-widest mb-6">
          Kurumsal Sözleşme & Yönetim Sistemi
        </p>

        <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mb-1">Giriş Yap</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">Devam etmek için hesabınıza giriş yapın</p>
        <?php if (isset($error)): ?>
          <div class="alert-destructive text-left w-full">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" x2="12" y1="8" y2="12" /><line x1="12" x2="12.01" y1="16" y2="16" /></svg>
            <h2>Bir şeyler ters gitti!</h2>
            <section><?php echo $error; ?></section>
          </div>
        <?php endif; ?>
      </div>
      <form id="loginForm" class="space-y-4" action="<?php echo htmlspecialchars(function_exists('routeUrl') ? routeUrl('/login') : 'login', ENT_QUOTES, 'UTF-8'); ?>" method="post">
        <div class="space-y-2">
          <label class="text-sm font-medium" for="username">Kullanıcı Adı</label>
          <input class="input w-full" type="text" id="username" name="username" placeholder="Kullanıcı adınızı girin" required>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium" for="password">Parola</label>
          <input class="input w-full" type="password" id="password" name="password" placeholder="Parolanızı girin" required>
        </div>
        <button class="btn btn-primary w-full" type="submit">Giriş Yap</button>
        <div class="text-center text-sm text-muted-foreground mt-4">
            Henüz hesabınız yok mu? <a href="<?php echo routeUrl('/register'); ?>" class="text-primary hover:underline">Kayıt Ol</a>
        </div>
      </form>
    </div>
  </section>
</body>
</html>

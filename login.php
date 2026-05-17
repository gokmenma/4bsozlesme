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
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <link rel="stylesheet" href="<?php echo routeUrl('/assets/css/app.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>
</head>
<body>
    <section class="min-h-svh flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-6">
      <div class="text-center">
        <h1 class="text-2xl font-bold mb-2">Giriş Yap</h1>
        <p class="text-muted-foreground mb-6">Hesabınıza giriş yapın</p>
        <?php if (isset($error)): ?>
          <div class="alert-destructive text-left">
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

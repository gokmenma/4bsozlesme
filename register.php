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
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <link rel="stylesheet" href="<?php echo routeUrl('/assets/css/app.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>
</head>
<body>
    <section class="min-h-svh flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-6">
      <div class="text-center">
        <h1 class="text-2xl font-bold mb-2">Kayıt Ol</h1>
        <p class="text-muted-foreground mb-6">Yeni bir kurum hesabı oluşturun</p>
        <?php if (isset($error)): ?>
          <div class="alert-destructive text-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" x2="12" y1="8" y2="12" /><line x1="12" x2="12.01" y1="16" y2="16" /></svg>
            <h2>Bir şeyler ters gitti!</h2>
            <section><?php echo $error; ?></section>
          </div>
        <?php endif; ?>
      </div>
       <form id="registerForm" class="space-y-4" action="<?php echo htmlspecialchars(routeUrl('/register'), ENT_QUOTES, 'UTF-8'); ?>" method="post">
        <div class="space-y-2">
          <label class="text-sm font-medium" for="tenant_name">Kurum Adı</label>
          <input class="input w-full" type="text" id="tenant_name" name="tenant_name" value="<?php echo htmlspecialchars($tenantName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Kurumunuzun adını girin" required>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium" for="name">Ad Soyad</label>
          <input class="input w-full" type="text" id="name" name="name" value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Adınızı ve soyadınızı girin" required>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium" for="email">E-posta</label>
          <input class="input w-full" type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="E-posta adresinizi girin" required>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium" for="password">Parola</label>
          <input class="input w-full" type="password" id="password" name="password" placeholder="Parolanızı belirleyin" required>
        </div>
        <button class="btn btn-primary w-full" type="submit">Kayıt Ol</button>
        <div class="text-center text-sm text-muted-foreground mt-4">
            Zaten bir hesabınız var mı? <a href="<?php echo routeUrl('/login'); ?>" class="text-primary hover:underline">Giriş Yap</a>
        </div>
      </form>
    </div>
  </section>
</body>
</html>

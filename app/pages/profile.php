<?php 
$pageTitle = "Profil"; 
$pageSubtitle = "Hesap bilgilerinizi ve güvenliğinizi bu sayfadan yönetebilirsiniz.";

// Helper function to get values safely
if (!function_exists('getVal')) {
    function getVal($key, $user) {
        return htmlspecialchars($user[$key] ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$trial_ends_at = !empty($user['trial_ends_at']) ? date('d.m.Y', strtotime($user['trial_ends_at'])) : 'Belirtilmemiş';
$has_subscription = !empty($subscription);
$sub_start_date = $has_subscription ? date('d.m.Y', strtotime($subscription['start_date'])) : null;
$sub_end_date = $has_subscription ? date('d.m.Y', strtotime($subscription['end_date'])) : null;

// Build alert message for standard JavaScript alert too
$alert_msg = "Deneme süresi bitiş tarihi: {$trial_ends_at}";
if ($has_subscription) {
    $alert_msg .= "\nAbonelik başlangıç tarihi: {$sub_start_date}\nAbonelik bitiş tarihi: {$sub_end_date}";
}
?>

<div class="flex flex-col gap-8 max-w-4xl mx-auto">
  <!-- Header -->
  <div class="flex flex-col gap-1">
    <h1 class="text-2xl font-bold tracking-tight text-foreground"><?php echo $pageTitle; ?></h1>
    <p class="text-muted-foreground"><?php echo $pageSubtitle; ?></p>
  </div>

  <!-- Abonelik & Deneme Bilgileri Alert -->
  <div class="alert p-5 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-950 dark:bg-amber-950 dark:text-amber-100 flex items-start justify-between gap-4 shadow-sm relative overflow-hidden">
    <div class="flex gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5 text-amber-600 dark:text-amber-400"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
      <div>
        <h2 class="text-sm font-bold">Hesap Durumu ve Abonelik Bilgileri</h2>
        <section class="text-xs mt-1 space-y-1 opacity-80">
          <?php if ($has_subscription): ?>
            <p>Aboneliğiniz aktif. Bitiş Tarihi: <strong class="font-bold"><?php echo $sub_end_date; ?></strong></p>
          <?php else: ?>
            <p>Deneme Süresi Bitiş Tarihi: <strong class="font-bold"><?php echo $trial_ends_at; ?></strong></p>
          <?php endif; ?>
        </section>
      </div>
    </div>

    <?php if ($has_subscription): ?>
      <div class="flex flex-col items-end">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-200/50 dark:bg-amber-900/50 border border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-100 uppercase tracking-wider">
          <?php echo htmlspecialchars($subscription['plan_name']); ?>
        </span>
      </div>
    <?php endif; ?>
  </div>

  <!-- Profil Bilgileri Section -->
  <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
    <div class="p-6 border-b border-border bg-muted/30">
      <div class="flex items-center gap-2">
        <div class="p-2 rounded-lg bg-primary/10 text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <h2 class="text-lg font-semibold leading-none tracking-tight">Kişisel Bilgiler</h2>
          <p class="text-sm text-muted-foreground mt-1">Sistemdeki temel hesap bilgileriniz.</p>
        </div>
      </div>
    </div>
    <div class="p-6">
      <form id="form-profile" class="grid gap-6 md:grid-cols-2">
        <div class="space-y-2">
          <label for="name" class="text-sm font-medium leading-none">Ad Soyad</label>
          <input type="text" id="name" name="name" value="<?php echo getVal('name', $user); ?>" placeholder="Ad Soyad" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        </div>
        
        <div class="space-y-2">
          <label for="email" class="text-sm font-medium leading-none">E-posta</label>
          <input type="email" id="email" name="email" value="<?php echo getVal('email', $user); ?>" placeholder="E-posta" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        </div>

        <div class="md:col-span-2 flex justify-end">
          <button type="submit" class="btn-save inline-flex h-10 items-center justify-center rounded-md bg-zinc-900 dark:bg-white dark:text-zinc-900 px-8 py-2 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
            Profilimi Güncelle
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Şifre Değiştir Section -->
  <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
    <div class="p-6 border-b border-border bg-muted/30">
      <div class="flex items-center gap-2">
        <div class="p-2 rounded-lg bg-primary/10 text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div>
          <h2 class="text-lg font-semibold leading-none tracking-tight">Güvenlik</h2>
          <p class="text-sm text-muted-foreground mt-1">Hesabınızın güvenliğini sağlamak için şifrenizi güncelleyin.</p>
        </div>
      </div>
    </div>
    <div class="p-6">
      <form id="form-password" class="grid gap-6 md:grid-cols-3">
        <div class="space-y-2">
          <label for="current_password" class="text-sm font-medium leading-none">Mevcut Şifre</label>
          <input type="password" id="current_password" name="current_password" required class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        </div>
        
        <div class="space-y-2">
          <label for="new_password" class="text-sm font-medium leading-none">Yeni Şifre</label>
          <input type="password" id="new_password" name="new_password" required class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        </div>

        <div class="space-y-2">
          <label for="confirm_password" class="text-sm font-medium leading-none">Yeni Şifre (Tekrar)</label>
          <input type="password" id="confirm_password" name="confirm_password" required class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        </div>

        <div class="md:col-span-3 flex justify-end">
          <button type="submit" class="btn-save inline-flex h-10 items-center justify-center rounded-md bg-zinc-900 dark:bg-white dark:text-zinc-900 px-8 py-2 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
            Şifremi Değiştir
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Hesabı Sil Section -->
  <div class="rounded-xl border border-destructive/20 bg-destructive/5 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-destructive/10 bg-destructive/10">
      <div class="flex items-center gap-2">
        <div class="p-2 rounded-lg bg-destructive text-destructive-foreground">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
        </div>
        <div>
          <h2 class="text-lg font-semibold leading-none tracking-tight text-destructive">Tehlikeli Alan</h2>
          <p class="text-sm text-destructive/80 mt-1">Hesabınızı silmek kalıcı bir işlemdir ve geri alınamaz.</p>
        </div>
      </div>
    </div>
    <div class="p-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
          <p class="text-sm font-medium">Hesabı Sil</p>
          <p class="text-xs text-muted-foreground">Tüm verileriniz kalıcı olarak silinecektir.</p>
        </div>
        <button type="button" onclick="openDeleteModal()" class="inline-flex h-10 items-center justify-center rounded-md bg-destructive px-6 py-2 text-sm font-medium text-destructive-foreground shadow-sm hover:bg-destructive/90 transition-colors">
          Hesabımı Sil
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Account Modal -->
<dialog id="delete-modal" class="bg-transparent p-0 backdrop:bg-black/50 backdrop:backdrop-blur-sm">
  <div class="w-full max-w-md rounded-xl border border-border bg-background p-6 shadow-2xl">
    <div class="flex flex-col gap-4">
      <div class="flex items-center gap-3 text-destructive">
        <div class="p-2 rounded-full bg-destructive/10">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 9-6 6"/><path d="m9 9 6 6"/><circle cx="12" cy="12" r="10"/></svg>
        </div>
        <h3 class="text-lg font-bold">Hesabınızı silmek istediğinizden emin misiniz?</h3>
      </div>
      
      <p class="text-sm text-muted-foreground">Bu işlem geri alınamaz. Lütfen onaylamak için şifrenizi girin.</p>
      
      <form id="form-delete-account" class="space-y-4">
        <div class="space-y-2">
          <label for="delete_password" class="text-sm font-medium">Şifre</label>
          <input type="password" id="delete_password" name="password" required class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-medium rounded-md border border-border hover:bg-muted">Vazgeç</button>
          <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md bg-destructive text-destructive-foreground hover:bg-destructive/90">Onayla ve Sil</button>
        </div>
      </form>
    </div>
  </div>
</dialog>

<script>
function openDeleteModal() {
    document.getElementById('delete-modal').showModal();
}

function closeDeleteModal() {
    document.getElementById('delete-modal').close();
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle Profile Update
    const formProfile = document.getElementById('form-profile');
    formProfile.addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm(formProfile, 'profil-guncelle', 'Profil güncellendi.');
    });

    // Handle Password Change
    const formPassword = document.getElementById('form-password');
    formPassword.addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm(formPassword, 'sifre-degistir', 'Şifre değiştirildi.');
    });

    // Handle Account Deletion
    const formDelete = document.getElementById('form-delete-account');
    formDelete.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = formDelete.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Siliniyor...';

        const formData = new FormData(formDelete);
        fetch('hesap-sil', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'logout';
            } else {
                window.showToast({
                    category: 'error',
                    title: 'Hata',
                    description: data.message
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    });

    function submitForm(form, url, successMsg) {
        const btn = form.querySelector('.btn-save');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin mr-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> İşlem yapılıyor...`;

        const formData = new FormData(form);
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showToast({
                    category: 'success',
                    title: 'Başarılı',
                    description: data.message || successMsg
                });
                if (url === 'sifre-degistir') form.reset();
            } else {
                window.showToast({
                    category: 'error',
                    title: 'Hata',
                    description: data.message
                });
            }
        })
        .catch(error => {
            window.showToast({
                category: 'error',
                title: 'Hata',
                description: 'Bir hata oluştu.'
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
});
</script>

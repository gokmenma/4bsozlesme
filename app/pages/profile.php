<?php 
$pageTitle = "Profil Ayarları"; 
$pageSubtitle = "Kişisel bilgilerinizi, güvenlik tercihlerinizi ve hesap detaylarınızı buradan yönetin.";

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

// Initials generation
$nameParts = explode(' ', trim($user['name'] ?? ''));
$initials = '';
if (count($nameParts) >= 2) {
    $initials = mb_substr($nameParts[0], 0, 1, 'UTF-8') . mb_substr(end($nameParts), 0, 1, 'UTF-8');
} else if (count($nameParts) === 1 && !empty($nameParts[0])) {
    $initials = mb_substr($nameParts[0], 0, 2, 'UTF-8');
} else {
    $initials = 'U';
}
$initials = mb_strtoupper($initials, 'UTF-8');
?>

<div class="flex flex-col gap-6 max-w-6xl mx-auto px-2 md:px-4">
  <!-- Breadcrumb -->
  <nav class="flex text-xs text-zinc-400 dark:text-zinc-500 font-semibold select-none" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1.5 md:space-x-2">
      <li>
        <a href="<?php echo routeUrl('/'); ?>" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">Ana Sayfa</a>
      </li>
      <li class="flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><path d="m9 18 6-6-6-6"/></svg>
        <span class="text-zinc-900 dark:text-zinc-100 font-bold">Profil</span>
      </li>
    </ol>
  </nav>

  <!-- Header Section -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50"><?php echo $pageTitle; ?></h1>
      <p class="text-xs text-zinc-500 dark:text-zinc-400 font-normal"><?php echo $pageSubtitle; ?></p>
    </div>
    <div class="shrink-0 flex">
      <button type="button" onclick="submitActiveForm()" id="btn-global-save" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-zinc-950 hover:bg-zinc-900 text-white rounded-lg text-xs font-bold transition-all shadow-sm active:scale-[0.98] cursor-pointer select-none">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        Değişiklikleri Kaydet
      </button>
    </div>
  </div>

  <?php if (!empty($_SESSION['subscription_error'])): ?>
    <div class="bg-rose-50 dark:bg-rose-955/20 border border-rose-100 dark:border-rose-900/30 rounded-2xl p-4 flex items-start gap-3 select-none">
      <div class="p-1.5 bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-455 rounded-full shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
      </div>
      <div class="space-y-0.5">
        <h4 class="text-xs font-bold text-rose-900 dark:text-rose-200">Abonelik Uyarısı</h4>
        <p class="text-[11px] text-rose-750 dark:text-rose-350 font-semibold leading-relaxed">
          <?php 
          echo $_SESSION['subscription_error']; 
          unset($_SESSION['subscription_error']);
          ?>
        </p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Main Profile Layout Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mt-2">
    
    <!-- Left Column: User Card & Menu Sidebar -->
    <div class="lg:col-span-4 flex flex-col gap-5">
      
      <!-- Profile Card -->
      <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm flex flex-col items-center">
        <!-- Initials Avatar Circle -->
        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-zinc-950 dark:bg-zinc-100 text-white dark:text-zinc-900 font-black text-lg select-none">
          <?php echo $initials; ?>
        </div>
        
        <!-- Name -->
        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-50 mt-4 text-center">
          <?php echo getVal('name', $user); ?>
        </h3>
        
        <!-- Email -->
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 text-center font-medium">
          <?php echo getVal('email', $user); ?>
        </p>

        <!-- Sidebar Navigation Menu -->
        <div class="w-full border-t border-zinc-100 dark:border-zinc-800/80 mt-6 pt-5 flex flex-col gap-1">
          <!-- Personal Tab Button -->
          <button onclick="switchProfileTab('personal')" class="tab-btn w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer" id="tab-btn-personal" data-tab="personal">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Kişisel Bilgiler
          </button>

          <!-- Security Tab Button -->
          <button onclick="switchProfileTab('security')" class="tab-btn w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer" id="tab-btn-security" data-tab="security">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Şifre Değiştir
          </button>

          <!-- Subscription Tab Button (Only for Admin or Superadmin) -->
          <?php if ($user['role'] === 'admin' || $user['role'] === 'superadmin'): ?>
          <button onclick="switchProfileTab('subscription')" class="tab-btn w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer" id="tab-btn-subscription" data-tab="subscription">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            Hesap Detayları
          </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Reminder Card -->
      <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm flex items-start gap-3 select-none">
        <div class="p-1 bg-blue-50 dark:bg-blue-950/40 rounded-full text-blue-500 shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        </div>
        <div class="space-y-1">
          <h4 class="text-xs font-bold text-zinc-950 dark:text-zinc-50">Hatırlatma</h4>
          <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
            Profil değişikliklerinizin kaydedilmesi için sağ üst köşedeki <strong class="font-bold text-zinc-700 dark:text-zinc-300">"Değişiklikleri Kaydet"</strong> butonuna tıklayınız.
          </p>
        </div>
      </div>

    </div>

    <!-- Right Column: Active Tab Content Panels -->
    <div class="lg:col-span-8">
      <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 md:p-8 shadow-sm min-h-[460px]">
        
        <!-- Tab 1: Personal Info -->
        <div id="profile-tab-personal" class="profile-tab-content">
          <div class="flex items-start gap-3">
            <div class="p-1 text-zinc-900 dark:text-zinc-100 shrink-0 mt-0.5">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-50">Kişisel Bilgiler</h3>
              <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 font-normal">Yönetici kimliğinizi ve sistemde görüntülenecek iletişim tercihlerinizi yapılandırın.</p>
            </div>
          </div>
          
          <hr class="border-zinc-100 dark:border-zinc-800/80 my-5">

          <form id="form-profile" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="flex flex-col gap-1.5">
                <label for="name" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Ad Soyad</label>
                <input type="text" id="name" name="name" value="<?php echo getVal('name', $user); ?>" placeholder="Ad Soyad" required class="w-full h-10 px-3 rounded-lg border border-zinc-200 dark:border-zinc-850 bg-white dark:bg-zinc-950 focus:border-zinc-950 focus:ring-2 focus:ring-zinc-950/5 transition-all text-xs font-semibold outline-none">
              </div>
              
              <div class="flex flex-col gap-1.5">
                <label for="username_display" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Kullanıcı Adı</label>
                <input type="text" id="username_display" value="<?php echo explode('@', getVal('email', $user))[0]; ?>" readonly class="w-full h-10 px-3 rounded-lg border border-zinc-200 dark:border-zinc-850 bg-zinc-50 dark:bg-zinc-900 cursor-not-allowed text-zinc-400 text-xs font-semibold outline-none select-none" tabindex="-1">
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label for="email" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">E-posta Adresi</label>
              <input type="email" id="email" name="email" value="<?php echo getVal('email', $user); ?>" placeholder="E-posta" required class="w-full h-10 px-3 rounded-lg border border-zinc-200 dark:border-zinc-850 bg-white dark:bg-zinc-950 focus:border-zinc-950 focus:ring-2 focus:ring-zinc-955/5 transition-all text-xs font-semibold outline-none">
              <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-medium">Giriş yapmak ve sistem kritik bildirimlerini almak için kullanılır.</span>
            </div>
          </form>
        </div>

        <!-- Tab 2: Security & Password Change -->
        <div id="profile-tab-security" class="profile-tab-content hidden">
          <div class="flex items-start gap-3">
            <div class="p-1 text-zinc-900 dark:text-zinc-100 shrink-0 mt-0.5">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-50">Şifre Değiştir</h3>
              <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 font-normal">Hesap güvenliğinizi korumak için şifrenizi düzenli aralıklarla güncelleyin.</p>
            </div>
          </div>
          
          <hr class="border-zinc-100 dark:border-zinc-800/80 my-5">

          <form id="form-password" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
              <div class="flex flex-col gap-1.5">
                <label for="current_password" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Mevcut Şifre</label>
                <input type="password" id="current_password" name="current_password" required class="w-full h-10 px-3 rounded-lg border border-zinc-200 dark:border-zinc-850 bg-white dark:bg-zinc-950 focus:border-zinc-950 focus:ring-2 focus:ring-zinc-955/5 transition-all text-xs font-semibold outline-none">
              </div>

              <div class="flex flex-col gap-1.5">
                <label for="new_password" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Yeni Şifre</label>
                <input type="password" id="new_password" name="new_password" required class="w-full h-10 px-3 rounded-lg border border-zinc-200 dark:border-zinc-850 bg-white dark:bg-zinc-950 focus:border-zinc-950 focus:ring-2 focus:ring-zinc-955/5 transition-all text-xs font-semibold outline-none">
              </div>

              <div class="flex flex-col gap-1.5">
                <label for="confirm_password" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Şifre Tekrarı</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full h-10 px-3 rounded-lg border border-zinc-200 dark:border-zinc-850 bg-white dark:bg-zinc-950 focus:border-zinc-950 focus:ring-2 focus:ring-zinc-955/5 transition-all text-xs font-semibold outline-none">
              </div>
            </div>
          </form>

          <!-- Danger Zone / Delete Account -->
          <div class="mt-10 bg-rose-50/20 dark:bg-rose-950/5 border border-rose-100 dark:border-rose-950/20 rounded-xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-5 select-none">
            <div class="space-y-1">
              <h4 class="text-xs font-bold text-rose-700 dark:text-rose-400 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                Hesabı Kalıcı Olarak Sil
              </h4>
              <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">Hesabınızı silmek kalıcı bir işlemdir. Tüm sözleşme şablonlarınız, personel verileriniz ve ödeme kayıtlarınız tamamen silinir ve geri alınamaz.</p>
            </div>
            <button type="button" onclick="openDeleteModal()" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm shrink-0 active:scale-[0.98] cursor-pointer select-none">
              Hesabımı Sil
            </button>
          </div>
        </div>

        <!-- Tab 3: Subscription & Packages -->
        <div id="profile-tab-subscription" class="profile-tab-content hidden">
          <div class="flex items-start gap-3">
            <div class="p-1 text-zinc-900 dark:text-zinc-100 shrink-0 mt-0.5">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-50">Hesap Detayları</h3>
              <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 font-normal">Abonelik paketinizi, satın alabileceğiniz paketleri ve ödeme geçmişinizi yönetin.</p>
            </div>
          </div>

          <hr class="border-zinc-100 dark:border-zinc-800/80 my-5">

          <!-- Part 1: Current Active Subscription Package -->
          <h4 class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-3 flex items-center gap-1.5 select-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>
            Mevcut Paket Durumu
          </h4>

          <?php if ($has_subscription && $subscription['status'] === 'active'): ?>
            <!-- User is subscribed and active -->
            <div class="bg-gradient-to-br from-indigo-50/20 to-purple-50/20 dark:from-indigo-950/10 dark:to-purple-950/5 border border-indigo-100 dark:border-indigo-900/30 rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-sm select-none">
              <div class="flex items-start gap-4">
                <div class="p-3 bg-indigo-500/10 dark:bg-indigo-400/10 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 2 18 2 18 6 6 6 6 2"/><rect width="14" height="14" x="5" y="6" rx="2"/><path d="M9 16h6"/></svg>
                </div>
                <div class="space-y-1">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-indigo-100 dark:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 shadow-sm">
                    Aktif Abonelik
                  </span>
                  <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">
                    <?php echo htmlspecialchars($subscription['plan_name']); ?>
                  </h3>
                  <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5">
                    Abonelik Dönemi: <span class="font-bold text-zinc-700 dark:text-zinc-300"><?php echo $sub_start_date; ?></span> - <span class="font-bold text-zinc-700 dark:text-zinc-300"><?php echo $sub_end_date; ?></span>
                  </p>
                </div>
              </div>
              <div class="flex flex-col md:items-end justify-center shrink-0">
                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Abonelik Tutarı</span>
                <span class="text-xl font-black text-indigo-650 dark:text-indigo-400 mt-1"><?php echo number_format($subscription['amount'], 0, ',', '.'); ?> ₺</span>
              </div>
            </div>
          <?php elseif ($has_subscription && $subscription['payment_status'] === 'pending'): ?>
            <!-- User subscription is pending approval -->
            <div class="bg-gradient-to-br from-amber-50/20 to-yellow-50/20 dark:from-amber-950/10 dark:to-yellow-950/5 border border-amber-100 dark:border-yellow-900/30 rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-sm select-none">
              <div class="flex items-start gap-4">
                <div class="p-3 bg-amber-500/10 dark:bg-amber-400/10 text-amber-600 dark:text-amber-400 rounded-2xl animate-pulse">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                </div>
                <div class="space-y-1">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-amber-100 dark:bg-amber-900/40 border border-amber-200 dark:border-amber-805 text-amber-700 dark:text-amber-300 shadow-sm animate-pulse">
                    Onay Bekliyor
                  </span>
                  <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">
                    <?php echo htmlspecialchars($subscription['plan_name']); ?>
                  </h3>
                  <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5">
                    Abonelik talebiniz alınmıştır. Yönetici onayının ardından paketiniz aktif hale getirilecektir.
                  </p>
                </div>
              </div>
              <div class="flex flex-col md:items-end justify-center shrink-0">
                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Abonelik Tutarı</span>
                <span class="text-xl font-black text-amber-600 dark:text-amber-400 mt-1"><?php echo number_format($subscription['amount'], 0, ',', '.'); ?> ₺</span>
              </div>
            </div>
          <?php else: ?>
            <!-- User is in trial -->
            <div class="bg-gradient-to-br from-amber-50/20 to-orange-50/20 dark:from-amber-950/10 dark:to-orange-950/5 border border-amber-100 dark:border-amber-900/30 rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-sm select-none">
              <div class="flex items-start gap-4">
                <div class="p-3 bg-amber-500/10 dark:bg-amber-400/10 text-amber-600 dark:text-amber-400 rounded-2xl">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                </div>
                <div class="space-y-1">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-amber-100 dark:bg-amber-900/40 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 shadow-sm animate-pulse">
                    Deneme Sürümü
                  </span>
                  <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">
                    Ücretsiz 30 Günlük Deneme
                  </h3>
                  <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5">
                    Sistemdeki deneme süreniz. Bitiş Tarihi: <strong class="font-bold text-zinc-700 dark:text-zinc-300"><?php echo $trial_ends_at; ?></strong>
                  </p>
                </div>
              </div>
              <div class="flex flex-col md:items-end justify-center shrink-0">
                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Kalan Süre</span>
                <?php 
                $remainingDays = ceil((strtotime($user['trial_ends_at']) - time()) / 86400); 
                $remainingDays = $remainingDays > 0 ? $remainingDays : 0;
                ?>
                <span class="text-xl font-black text-amber-600 dark:text-amber-400 mt-1"><?php echo $remainingDays; ?> Gün</span>
              </div>
            </div>
          <?php endif; ?>

          <!-- Part 2: Pricing Cards (Subscription Plans to Purchase) -->
          <h4 class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mt-10 mb-3 flex items-center gap-1.5 select-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Satın Alabileceğiniz Paketler
          </h4>
          <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mb-5 font-semibold select-none">Kurumunuza en uygun planı seçerek hemen aboneliğinizi başlatın veya paketinizi yükseltin.</p>

          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($plans as $plan): ?>
              <div class="bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 flex flex-col shadow-sm hover:shadow-md hover:border-zinc-300 dark:hover:border-zinc-800 transition-all relative group">
                
                <div class="mb-5">
                  <h3 class="text-sm font-extrabold text-zinc-900 dark:text-zinc-100 group-hover:text-primary transition-colors">
                    <?php echo htmlspecialchars($plan['name']); ?>
                  </h3>
                  <div class="mt-3 flex items-baseline gap-1">
                    <span class="text-xl font-black tracking-tight text-zinc-900 dark:text-zinc-100">
                      <?php echo number_format($plan['price'], 0, ',', '.'); ?> ₺
                    </span>
                    <span class="text-[10px] font-bold text-zinc-400">/<?php echo $plan['duration_days']; ?> gün</span>
                  </div>
                </div>

                <!-- Features list -->
                <ul class="mb-6 space-y-2.5 flex-1 border-t border-zinc-100 dark:border-zinc-900 pt-4">
                  <?php 
                  $features = explode(',', $plan['features']);
                  foreach ($features as $feature): 
                  ?>
                    <li class="flex items-start gap-2">
                      <svg class="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                      <span class="text-[10px] text-zinc-550 dark:text-zinc-400 font-semibold leading-tight">
                        <?php echo htmlspecialchars(trim($feature)); ?>
                      </span>
                    </li>
                  <?php endforeach; ?>
                </ul>

                <!-- Buy Button -->
                <button onclick="purchasePlan(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['name'], ENT_QUOTES); ?>', '<?php echo number_format($plan['price'], 0, ',', '.'); ?> ₺')" class="w-full py-2.5 bg-zinc-950 dark:bg-zinc-100 hover:bg-zinc-900 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 rounded-xl text-xs font-bold transition-all cursor-pointer">
                  Hemen Satın Al
                </button>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Part 3: Purchase History Table -->
          <h4 class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mt-12 mb-3 flex items-center gap-1.5 select-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Satın Alma ve Ödeme Geçmişi
          </h4>

          <?php if (empty($history)): ?>
            <div class="bg-zinc-50/50 dark:bg-zinc-950/20 border border-zinc-150 dark:border-zinc-800 rounded-2xl p-8 text-center text-zinc-500 dark:text-zinc-450 text-[11px] font-medium select-none">
              Henüz bir satın alma kaydınız bulunmamaktadır.
            </div>
          <?php else: ?>
            <div class="border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden bg-white dark:bg-zinc-950">
              <table class="w-full text-left text-[11px] font-semibold border-collapse">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800 select-none">
                  <tr>
                    <th class="px-5 py-3.5 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Paket</th>
                    <th class="px-5 py-3.5 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Satın Alan</th>
                    <th class="px-5 py-3.5 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Tutar</th>
                    <th class="px-5 py-3.5 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Başlangıç</th>
                    <th class="px-5 py-3.5 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Bitiş</th>
                    <th class="px-5 py-3.5 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Durum</th>
                    <th class="px-5 py-3.5 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">İşlem</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                  <?php foreach ($history as $item): ?>
                    <tr class="hover:bg-zinc-50/40 dark:hover:bg-zinc-900/20 transition-colors">
                      <td class="px-5 py-4 font-bold text-zinc-900 dark:text-zinc-100">
                        <?php echo htmlspecialchars($item['plan_name']); ?>
                      </td>
                      <td class="px-5 py-4 text-zinc-500 dark:text-zinc-400 font-medium">
                        <?php echo htmlspecialchars($item['user_name'] ?: '-'); ?>
                      </td>
                      <td class="px-5 py-4 text-zinc-900 dark:text-zinc-100 font-extrabold">
                        <?php echo number_format($item['amount'], 2, ',', '.'); ?> ₺
                      </td>
                      <td class="px-5 py-4 text-zinc-500 dark:text-zinc-500 font-semibold select-none">
                        <?php echo date('d.m.Y', strtotime($item['start_date'])); ?>
                      </td>
                      <td class="px-5 py-4 text-zinc-500 dark:text-zinc-500 font-semibold select-none">
                        <?php echo date('d.m.Y', strtotime($item['end_date'])); ?>
                      </td>
                      <td class="px-5 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider <?php 
                          if ($item['payment_status'] === 'pending') {
                              echo 'bg-amber-50 dark:bg-amber-955/30 border border-amber-100 dark:border-amber-900/30 text-amber-750 dark:text-amber-400 animate-pulse';
                          } elseif ($item['payment_status'] === 'failed') {
                              echo 'bg-rose-50 dark:bg-rose-955/30 border border-rose-100 dark:border-rose-900/30 text-rose-750 dark:text-rose-455';
                          } elseif ($item['status'] === 'active') {
                              echo 'bg-emerald-50 dark:bg-emerald-955/30 border border-emerald-100 dark:border-emerald-900/30 text-emerald-700 dark:text-emerald-400';
                          } elseif ($item['status'] === 'expired') {
                              echo 'bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-500 dark:text-zinc-500';
                          } else {
                              echo 'bg-red-50 dark:bg-red-955/30 border border-red-100 dark:border-red-900/30 text-red-700 dark:text-red-400';
                          }
                        ?>">
                          <?php 
                          if ($item['payment_status'] === 'pending') {
                              echo 'Onay Bekliyor';
                          } elseif ($item['payment_status'] === 'failed') {
                              echo 'Reddedildi';
                          } elseif ($item['status'] === 'active') {
                              echo 'Aktif / Onaylı';
                          } elseif ($item['status'] === 'expired') {
                              echo 'Süresi Doldu';
                          } else {
                              echo 'İptal Edildi';
                          }
                          ?>
                        </span>
                      </td>
                      <td class="px-5 py-4">
                        <?php if ($item['payment_status'] === 'pending'): ?>
                          <button onclick="cancelSubscriptionPurchase(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['plan_name'], ENT_QUOTES); ?>')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 text-red-650 dark:text-red-450 rounded-lg text-[10px] font-extrabold transition-all cursor-pointer border border-red-100 dark:border-red-955/30 select-none">
                            İptal Et
                          </button>
                        <?php else: ?>
                          <span class="text-zinc-400 text-xs font-semibold select-none">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Purchase Confirmation Modal -->
<dialog id="dialog-confirm-purchase" class="dialog bg-transparent p-0">
  <div class="w-full max-w-sm rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-2xl">
    <div class="flex flex-col gap-4">
      <div class="flex items-center gap-3 text-zinc-900 dark:text-zinc-100">
        <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <h3 class="text-sm font-extrabold">Aboneliği Başlat</h3>
      </div>
      
      <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">Seçtiğiniz abonelik paketini satın alarak hesabınızı aktif hale getirmek istediğinizden emin misiniz?</p>
      
      <div class="bg-zinc-50/50 dark:bg-zinc-950/20 border border-zinc-150 dark:border-zinc-800 p-4 rounded-xl space-y-2 select-none">
        <div class="flex justify-between items-center text-xs">
          <span class="text-zinc-400 font-medium">Seçilen Paket:</span>
          <span id="confirm-plan-name" class="font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
        <div class="flex justify-between items-center text-xs">
          <span class="text-zinc-400 font-medium">Toplam Tutar:</span>
          <span id="confirm-plan-price" class="font-black text-indigo-600 dark:text-indigo-400">-</span>
        </div>
      </div>
      
      <div class="flex justify-end gap-3 mt-4">
        <button type="button" onclick="document.getElementById('dialog-confirm-purchase').close()" class="px-4 py-2 text-xs font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-850 text-zinc-500 dark:text-zinc-400 cursor-pointer transition-colors">Vazgeç</button>
        <button type="button" id="btn-confirm-purchase" class="px-4 py-2 text-xs font-bold rounded-xl bg-zinc-950 dark:bg-zinc-100 text-white dark:text-zinc-950 hover:opacity-95 cursor-pointer transition-all active:scale-[0.98]">Satın Alımı Onayla</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Satın Alma İptal Dialog -->
<dialog id="dialog-cancel-purchase" class="dialog bg-transparent p-0" onclick="if (event.target === this) this.close()">
  <div class="w-full max-w-sm rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-2xl" onclick="event.stopPropagation()">
    <div class="flex flex-col gap-4">
      <div class="flex items-center gap-3 text-red-750">
        <div class="p-2.5 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-650 dark:text-red-400 shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 9-6 6"/><path d="m9 9 6 6"/><circle cx="12" cy="12" r="10"/></svg>
        </div>
        <h3 class="text-sm font-extrabold text-red-700 dark:text-red-400">Satın Alımı İptal Et</h3>
      </div>
      
      <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">Bu satın alma işlemini iptal etmek istediğinize emin misiniz? Onay bekleyen ödeme kaydınız silinecektir.</p>
      
      <div class="bg-zinc-50/50 dark:bg-zinc-950/20 border border-zinc-150 dark:border-zinc-800 p-4 rounded-xl select-none">
        <div class="flex justify-between items-center text-xs">
          <span class="text-zinc-500 font-medium">İptal Edilecek Paket:</span>
          <span id="cancel-plan-name" class="font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
      </div>
      
      <div class="flex justify-end gap-3 mt-4">
        <button type="button" onclick="document.getElementById('dialog-cancel-purchase').close()" class="px-4 py-2 text-xs font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-850 text-zinc-500 dark:text-zinc-400 cursor-pointer transition-colors">Vazgeç</button>
        <button type="button" id="btn-confirm-cancel" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-red-650 hover:bg-red-700 text-white cursor-pointer transition-all active:scale-[0.98] shadow-sm">İptal Et ve Sil</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Delete Account Modal -->
<dialog id="delete-modal" class="dialog bg-transparent p-0">
  <div class="w-full max-w-sm rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-2xl">
    <div class="flex flex-col gap-4">
      <div class="flex items-center gap-3 text-red-700">
        <div class="p-2.5 rounded-xl bg-red-50/50 dark:bg-red-950/20 text-red-650 dark:text-red-400">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 9-6 6"/><path d="m9 9 6 6"/><circle cx="12" cy="12" r="10"/></svg>
        </div>
        <h3 class="text-sm font-extrabold text-red-700 dark:text-red-455">Hesabınızı Silin</h3>
      </div>
      
      <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">Bu işlem kalıcıdır ve geri alınamaz. Lütfen işlemi onaylamak için mevcut şifrenizi girin.</p>
      
      <form id="form-delete-account" class="space-y-4">
        <div class="flex flex-col gap-1.5">
          <label for="delete_password" class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider ml-1">Mevcut Şifreniz</label>
          <input type="password" id="delete_password" name="password" required placeholder="Şifreniz" class="w-full h-11 px-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/30 focus:bg-white dark:focus:bg-zinc-950 focus:border-zinc-955 dark:focus:border-white focus:ring-4 focus:ring-zinc-900/5 dark:focus:ring-white/5 transition-all text-xs font-bold outline-none">
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-xs font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-850 text-zinc-500 dark:text-zinc-400 cursor-pointer transition-colors">Vazgeç</button>
          <button type="submit" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-red-600 hover:bg-red-700 text-white cursor-pointer transition-all active:scale-[0.98] shadow-sm">Onayla ve Sil</button>
        </div>
      </form>
    </div>
  </div>
</dialog>

<script>
function switchProfileTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.profile-tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    // Show active tab content
    const activeContent = document.getElementById('profile-tab-' + tabName);
    if (activeContent) {
        activeContent.classList.remove('hidden');
    }

    // Update buttons styling
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.dataset.active = "false";
        btn.className = "tab-btn w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200 text-zinc-500 hover:text-zinc-900 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:text-white dark:hover:bg-zinc-800 bg-transparent cursor-pointer";
    });

    const activeBtn = document.getElementById('tab-btn-' + tabName);
    if (activeBtn) {
        activeBtn.dataset.active = "true";
        activeBtn.className = "tab-btn w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold transition-all duration-200 bg-zinc-950 dark:bg-zinc-100 text-white dark:text-zinc-950 shadow-sm cursor-pointer";
    }

    // Show/hide global save button based on active tab
    const globalSaveBtn = document.getElementById('btn-global-save');
    if (globalSaveBtn) {
        if (tabName === 'subscription') {
            globalSaveBtn.style.opacity = '0';
            globalSaveBtn.style.pointerEvents = 'none';
        } else {
            globalSaveBtn.style.opacity = '1';
            globalSaveBtn.style.pointerEvents = 'auto';
        }
    }
}

function submitActiveForm() {
    const activeTabEl = document.querySelector('.tab-btn[data-active="true"]');
    if (!activeTabEl) return;
    
    const activeTab = activeTabEl.dataset.tab;
    if (activeTab === 'personal') {
        const form = document.getElementById('form-profile');
        if (form && form.reportValidity()) {
            form.requestSubmit();
        }
    } else if (activeTab === 'security') {
        const form = document.getElementById('form-password');
        if (form && form.reportValidity()) {
            form.requestSubmit();
        }
    }
}

function openDeleteModal() {
    document.getElementById('delete-modal').showModal();
}

function closeDeleteModal() {
    document.getElementById('delete-modal').close();
}

function purchasePlan(id, planName, planPrice) {
    $('#confirm-plan-name').text(planName);
    $('#confirm-plan-price').text(planPrice);
    
    const dialog = document.getElementById('dialog-confirm-purchase');
    dialog.showModal();

    $('#btn-confirm-purchase').off('click').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('İşleniyor...');
        
        $.post('<?php echo routeUrl("abonelik-satinal"); ?>', { plan_id: id }, function(response) {
            try {
                response = JSON.parse(response);
            } catch(e) {}
            
            dialog.close();
            
            if (response.success) {
                window.showToast({ 
                    category: 'success', 
                    title: 'Başarılı', 
                    description: response.message || 'Abonelik talebi oluşturuldu.' 
                });
                setTimeout(() => location.reload(), 1500);
            } else {
                window.showToast({ 
                    category: 'error', 
                    title: 'Hata', 
                    description: response.message || 'Satın alma işlemi başarısız.' 
                });
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            dialog.close();
            window.showToast({ 
                category: 'error', 
                title: 'Hata', 
                description: 'Sunucu ile iletişim kurulamadı.' 
            });
            btn.prop('disabled', false).text(originalText);
        });
    });
}

function cancelSubscriptionPurchase(id, planName) {
    $('#cancel-plan-name').text(planName);
    
    const dialog = document.getElementById('dialog-cancel-purchase');
    dialog.showModal();

    $('#btn-confirm-cancel').off('click').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).text('İşleniyor...');
        
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('<?php echo routeUrl("abonelik-sil"); ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            dialog.close();
            if (data.success) {
                window.showToast({ 
                    category: 'success', 
                    title: 'Başarılı', 
                    description: data.message || 'Satın alma işlemi iptal edildi.' 
                });
                setTimeout(() => location.reload(), 1500);
            } else {
                window.showToast({ 
                    category: 'error', 
                    title: 'Hata', 
                    description: data.message || 'Bir hata oluştu.' 
                });
                btn.prop('disabled', false).text('İptal Et ve Sil');
            }
        })
        .catch(() => {
            dialog.close();
            window.showToast({ 
                category: 'error', 
                title: 'Hata', 
                description: 'İşlem sırasında bir hata oluştu.' 
            });
            btn.prop('disabled', false).text('İptal Et ve Sil');
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Set default active tab
    switchProfileTab('personal');

    // Handle Profile Update
    const formProfile = document.getElementById('form-profile');
    if (formProfile) {
        formProfile.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(formProfile, 'profil-guncelle', 'Profil başarıyla güncellendi.');
        });
    }

    // Handle Password Change
    const formPassword = document.getElementById('form-password');
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(formPassword, 'sifre-degistir', 'Şifreniz başarıyla değiştirildi.');
        });
    }

    // Handle Account Deletion
    const formDelete = document.getElementById('form-delete-account');
    if (formDelete) {
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
            })
            .catch(() => {
                window.showToast({
                    category: 'error',
                    title: 'Hata',
                    description: 'İşlem sırasında bir hata oluştu.'
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }

    function submitForm(form, url, successMsg) {
        const globalBtn = document.getElementById('btn-global-save');
        if (!globalBtn) return;
        
        const originalText = globalBtn.innerHTML;
        globalBtn.disabled = true;
        globalBtn.innerHTML = `<svg class="animate-spin mr-2 h-4 w-4 shrink-0 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Kaydediliyor...`;

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
                
                if (url === 'sifre-degistir') {
                    form.reset();
                }
                
                // Live UI update if personal profile details changed
                if (url === 'profil-guncelle') {
                    const newName = form.querySelector('#name').value;
                    const newEmail = form.querySelector('#email').value;
                    
                    // Update main layout header user names immediately
                    document.querySelectorAll('.user-name-display').forEach(el => el.textContent = newName);
                    
                    // Update local page left sidebar name and email
                    const sidebarName = document.querySelector('.lg\\:col-span-4 h3');
                    if (sidebarName) sidebarName.textContent = newName;
                    
                    const sidebarEmail = document.querySelector('.lg\\:col-span-4 p');
                    if (sidebarEmail) sidebarEmail.textContent = newEmail;
                    
                    // Live update initials in avatar
                    const nameParts = newName.trim().split(' ');
                    let initials = '';
                    if (nameParts.length >= 2) {
                        initials = nameParts[0].substring(0, 1) + nameParts[nameParts.length - 1].substring(0, 1);
                    } else if (nameParts.length === 1 && nameParts[0] !== '') {
                        initials = nameParts[0].substring(0, 2);
                    }
                    initials = initials.toUpperCase();
                    const avatarCircle = document.querySelector('.lg\\:col-span-4 .w-16');
                    if (avatarCircle) avatarCircle.textContent = initials;

                    // Live update the username display field
                    const usernameDisp = document.getElementById('username_display');
                    if (usernameDisp) {
                        usernameDisp.value = newEmail.split('@')[0];
                    }
                }
            } else {
                window.showToast({
                    category: 'error',
                    title: 'Hata',
                    description: data.message || 'Güncelleme başarısız.'
                });
            }
        })
        .catch(error => {
            window.showToast({
                category: 'error',
                title: 'Hata',
                description: 'Sunucuyla iletişim kurulurken hata oluştu.'
            });
        })
        .finally(() => {
            globalBtn.disabled = false;
            globalBtn.innerHTML = originalText;
        });
    }
});
</script>

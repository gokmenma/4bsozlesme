<?php
$currentUserName = $_SESSION['user_name'] ?? 'Kullanıcı';
$currentUserEmail = $_SESSION['user_email'] ?? '';
$currentUserRole = $_SESSION['role'] ?? 'user';
$nameParts = preg_split('/\s+/', trim($currentUserName));
$userInitials = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . substr($nameParts[1] ?? 'S', 0, 1));

// Aktif sayfa kontrolü için yardımcı fonksiyon
$currentPage = $page ?? '/';
function isMenuActive($href, $currentPage)
{
  $href = '/' . ltrim($href, '/');
  return $href === $currentPage;
}
?>

<aside class="sidebar" data-side="left" aria-hidden="false">
  <nav class="flex min-h-0 flex-1 flex-col" aria-label="Sidebar navigation">
    <?php
    $userModel = new User();
    $tenants = $userModel->getTenants($_SESSION['user_id']);
    $currentTenantId = $_SESSION['tenant_id'];
    $currentTenant = null;
    foreach ($tenants as $t) {
      if ($t['id'] == $currentTenantId) {
        $currentTenant = $t;
        break;
      }
    }

    // Abonelik limit kontrolü
    global $db;
    $stmt = $db->prepare("SELECT s.*, sp.features FROM subscriptions s 
                          JOIN subscription_plans sp ON s.plan_id = sp.id
                          WHERE s.tenant_id = ? AND s.status = 'active' 
                          ORDER BY s.id DESC LIMIT 1");
    $stmt->execute([$currentTenantId]);
    $activeSub = $stmt->fetch();

    $tenantLimit = 1; // Varsayılan limit
    if ($_SESSION['role'] === 'superadmin') {
      $tenantLimit = 999;
    } elseif ($activeSub) {
      $features = $activeSub['features'];
      if (stripos($features, 'Sınırsız') !== false) {
        $tenantLimit = 999;
      } else {
        // Virgülle ayrılmış özellikleri kontrol et
        $featureList = explode(',', $features);
        foreach ($featureList as $feature) {
          if (preg_match('/(\d+)\s*Kurum/i', trim($feature), $matches)) {
            $tenantLimit = (int)$matches[1];
            break;
          }
        }
      }
    }
    $canAddTenant = count($tenants) < $tenantLimit;
    ?>
    <div class="px-4 border-b border-border mb-2 relative z-[60] bg-background w-full max-w-[230px] min-w-0">
      <details class="w-full relative group min-w-0" id="tenant-switcher" data-tenant-menu>
        <summary
          class="no-marker flex items-center justify-between w-full cursor-pointer p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors border border-border shadow-sm min-w-0">
          <div class="flex items-center gap-3 overflow-hidden min-w-0 flex-1">
            <div
              class="flex size-8 shrink-0 items-center justify-center rounded-md bg-zinc-900 text-white dark:bg-white dark:text-black">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="m7 17 10-10" />
                <path d="m13 17 4-4" opacity="0.5" />
              </svg>
            </div>
            <div class="flex flex-col text-left overflow-hidden min-w-0">
              <span
                class="text-sm font-bold truncate leading-tight"><?php echo htmlspecialchars($currentTenant['name'] ?? 'Kurum Seçin', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="text-muted-foreground group-open:rotate-180 transition-transform">
            <path d="m7 15 5-5 5 5" />
          </svg>
        </summary>

        <div
          class="absolute left-0 right-0 top-full mt-2 z-[100] bg-white dark:bg-zinc-900 border border-border rounded-xl shadow-2xl p-1.5 overflow-hidden animate-in fade-in zoom-in duration-200">
          <div class="max-h-64 overflow-y-auto custom-scrollbar">
            <?php foreach ($tenants as $tenant): ?>
              <a href="<?php echo routeUrl('/switch-tenant?id=' . $tenant['id']); ?>"
                class="flex items-center justify-between p-2.5 rounded-lg hover:bg-muted transition-all group/item <?php echo $tenant['id'] == $currentTenantId ? 'bg-primary/10 text-primary' : ''; ?>">
                <span
                  class="text-sm font-medium truncate"><?php echo htmlspecialchars($tenant['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if ($tenant['id'] == $currentTenantId): ?>
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                    class="text-primary">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>

          <div class="border-t border-border mt-1.5 pt-1.5 px-1">
            <?php if ($canAddTenant): ?>
              <button
                onclick="document.getElementById('add-tenant-modal').showModal(); document.getElementById('tenant-switcher').removeAttribute('open');"
                class="flex items-center gap-2.5 w-full p-2.5 rounded-lg hover:bg-primary/10 transition-all text-primary font-semibold group/add">
                <div
                  class="size-6 flex items-center justify-center rounded-md bg-primary/10 group-hover/add:bg-primary/20 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                  </svg>
                </div>
                <span class="text-sm">Yeni Kurum Ekle</span>
              </button>
            <?php else: ?>
              <button
                onclick="showToast({category: 'warning', title: 'Limit Aşıldı', description: 'Kurum ekleme limitine ulaştınız. Lütfen paketinizi yükseltin.'});"
                class="flex items-center gap-2.5 w-full p-2.5 rounded-lg opacity-60 cursor-not-allowed hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all text-zinc-500 font-semibold group/add">
                <div
                  class="size-6 flex items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="opacity-50">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                  </svg>
                </div>
                <span class="text-sm">Yeni Kurum Ekle</span>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </details>
    </div>

    <section class="scrollbar min-h-0 flex-1">
      <div role="group" aria-labelledby="group-label-content-1">
        <h3 id="group-label-content-1">Yönetim</h3>

        <ul>
          <li>
            <a href="/"
              class="<?php echo isMenuActive('/', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
              <span>Ana Sayfa</span>
            </a>
          </li>
          
          <li>
            <a href="yapilacaklar"
              class="<?php echo isMenuActive('yapilacaklar', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list-todo"><rect x="3" y="5" width="6" height="6" rx="1"/><path d="m5 12 2 2 4-4"/><path d="M13 5h8"/><path d="M13 9h8"/><path d="M13 13h8"/><rect x="3" y="15" width="6" height="6" rx="1"/></svg>
              <span>Yapılacaklar</span>
            </a>
          </li>

          <li>
            <a href="personel-listesi"
              class="<?php echo isMenuActive('personel-listesi', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 8V4H8" />
                <rect width="16" height="12" x="4" y="8" rx="2" />
                <path d="M2 14h2" />
                <path d="M20 14h2" />
                <path d="M15 13v2" />
                <path d="M9 13v2" />
              </svg>
              <span>Personeller</span>
            </a>
          </li>
          <li>
            <a href="ucret-tanimlari"
              class="<?php echo isMenuActive('ucret-tanimlari', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-coins-icon lucide-coins">
                <path d="M13.744 17.736a6 6 0 1 1-7.48-7.48" />
                <path d="M15 6h1v4" />
                <path d="m6.134 14.768.866-.5 2 3.464" />
                <circle cx="16" cy="8" r="6" />
              </svg>
              <span>Ücret Tanımları</span>
            </a>
          </li>
        </ul>
      </div>

      <div role="group" aria-labelledby="group-label-content-1">
        <h3 id="group-label-content-1">Diğer İşlemler</h3>
        <ul>
          <li>
            <a href="sozlesme-taslagi"
              class="<?php echo isMenuActive('sozlesme-taslagi', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-file-text-icon lucide-file-text">
                <path
                  d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" />
                <path d="M14 2v5a1 1 0 0 0 1 1h5" />
                <path d="M10 9H8" />
                <path d="M16 13H8" />
                <path d="M16 17H8" />
              </svg>
              <span>Sözleşme Taslağı</span>
            </a>
          </li>
          <li>
            <a href="tanimlamalar"
              class="<?php echo isMenuActive('tanimlamalar', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-cog-icon lucide-file-cog"><path d="M15 8a1 1 0 0 1-1-1V2a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8z"/><path d="M20 8v12a2 2 0 0 1-2 2h-4.182"/><path d="m3.305 19.53.923-.382"/><path d="M4 10.592V4a2 2 0 0 1 2-2h8"/><path d="m4.228 16.852-.924-.383"/><path d="m5.852 15.228-.383-.923"/><path d="m5.852 20.772-.383.924"/><path d="m8.148 15.228.383-.923"/><path d="m8.53 21.696-.382-.924"/><path d="m9.773 16.852.922-.383"/><path d="m9.773 19.148.922.383"/><circle cx="7" cy="18" r="3"/></svg>
              <span>Tanımlamalar</span>
            </a>
          </li>
      <?php if ($currentUserRole === 'superadmin'): ?>


          <li>
            <a href="doner-matrahi-olustur"
              class="<?php echo isMenuActive('doner-matrahi-olustur', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-banknote-icon lucide-banknote"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
              <span>Döner Matrahı Oluştur</span>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <?php if ($currentUserRole === 'superadmin' || $currentUserRole === 'admin'): ?>
        <div role="group" aria-labelledby="group-label-saas">
          <h3 id="group-label-saas">Sistem Yönetimi</h3>
          <ul>
            <li>
              <a href="kullanicilar"
                class="<?php echo isMenuActive('kullanicilar', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-users-round-icon lucide-users-round">
                  <path d="M18 21a8 8 0 0 0-16 0" />
                  <circle cx="10" cy="8" r="5" />
                  <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3" />
                </svg>
                <span>Kullanıcılar</span>
              </a>
            </li>

            <?php if ($currentUserRole === 'superadmin'): ?>
              <li>
                <a href="kurum-yonetimi"
                  class="<?php echo isMenuActive('kurum-yonetimi', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-building-2-icon lucide-building-2">
                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z" />
                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2" />
                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2" />
                    <path d="M10 6h4" />
                    <path d="M10 10h4" />
                    <path d="M10 14h4" />
                    <path d="M10 18h4" />
                  </svg>
                  <span>Kurum Yönetimi</span>
                </a>
              </li>
              <li>
                <a href="abonelik"
                  class="<?php echo isMenuActive('abonelik', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-credit-card-icon lucide-credit-card">
                    <rect width="20" height="14" x="2" y="5" rx="2" />
                    <line x1="2" x2="22" y1="10" y2="10" />
                  </svg>
                  <span>Abonelik</span>
                </a>
              </li>
            <?php endif; ?>

              <?php if ($currentUserRole === 'superadmin' || $currentUserRole === 'admin'): ?>

                <li>
                  <a href="ayarlar"
                    class="<?php echo isMenuActive('ayarlar', $currentPage) ? 'bg-muted font-medium text-foreground' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="3" />
                      <path
                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1Z" />
                    </svg>
                    <span>Ayarlar</span>
                  </a>
                </li>
              <?php endif; ?>


          </ul>
        </div>
      <?php endif; ?>
    </section>

    <footer class="border-t border-border p-2">
      <details class="relative" data-user-menu>
        <summary class="no-marker flex w-full cursor-pointer items-center gap-3 rounded-md px-2 py-2 hover:bg-muted">
          <span
            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm font-semibold border border-indigo-100 dark:border-indigo-800">
            <?php echo htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8'); ?>
          </span>

          <span class="min-w-0 flex-1">
            <span
              class="block truncate text-sm font-medium"><?php echo htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8'); ?></span>
            <span
              class="block truncate text-xs text-muted-foreground"><?php echo htmlspecialchars($currentUserEmail, ENT_QUOTES, 'UTF-8'); ?></span>
          </span>

          <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-muted-foreground" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            aria-hidden="true">
            <path d="m7 15 5-5 5 5" />
          </svg>
        </summary>

        <div
          class="absolute inset-x-2 bottom-full z-50 mb-2 rounded-md border border-border bg-white dark:bg-zinc-900 p-1 shadow-lg">
          <div class="border-b border-border px-2 py-2">
            <p class="truncate text-sm font-medium">
              <?php echo htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p class="truncate text-xs text-muted-foreground">
              <?php echo htmlspecialchars($currentUserRole, ENT_QUOTES, 'UTF-8'); ?>
            </p>
          </div>

          <a href="profil" class="flex items-center gap-2 rounded px-2 py-2 text-sm hover:bg-muted">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M18 20a6 6 0 0 0-12 0" />
              <circle cx="12" cy="10" r="4" />
              <circle cx="12" cy="12" r="10" />
            </svg>
            Profil
          </a>

          <a href="logout"
            class="flex items-center gap-2 rounded px-2 py-2 text-sm text-red-600 hover:bg-muted dark:text-red-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="m16 17 5-5-5-5" />
              <path d="M21 12H9" />
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            </svg>
            Çıkış yap
          </a>
        </div>
      </details>
    </footer>
  </nav>
</aside>

<!-- Yeni Kurum Ekleme Modalı -->
<dialog id="add-tenant-modal" class="modal p-0 bg-transparent border-none backdrop:bg-black/50 backdrop:backdrop-blur-sm">
  <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
    <div class="modal-box w-full max-w-sm p-6 bg-white dark:bg-zinc-900 border border-border shadow-2xl rounded-2xl pointer-events-auto animate-in zoom-in-95 duration-200">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Yeni Kurum Ekle</h3>
        <button onclick="document.getElementById('add-tenant-modal').close()" class="size-8 flex items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-zinc-500">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>
      </div>

      <form action="<?php echo routeUrl('/kurum-ekle'); ?>" method="POST" class="space-y-6">
        <div class="space-y-2">
          <label class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 ml-1" for="tenant_name_new">Kurum Adı</label>
          <input type="text" id="tenant_name_new" name="name"
            class="w-full px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50 border border-border focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all rounded-xl text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400"
            placeholder="Örn: ABC Teknolojileri" required autofocus>
          <p class="text-[11px] text-zinc-500 dark:text-zinc-400 ml-1">Eklediğiniz kurum profilinize otomatik olarak atanacaktır.</p>
        </div>

        <div class="flex items-center gap-3 pt-2">
          <button type="button" onclick="document.getElementById('add-tenant-modal').close()" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium border border-border hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">Vazgeç</button>
          <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 shadow-lg shadow-zinc-900/20 dark:shadow-zinc-100/10 hover:opacity-90 transition-opacity">
            Kurumu Oluştur
          </button>
        </div>
      </form>
    </div>
  </div>
</dialog>

<script>
  document.addEventListener('click', (event) => {
    // Kurum seçici menüsünü dışarı tıklayınca kapat
    document.querySelectorAll('details[data-tenant-menu]').forEach((details) => {
      if (!details.contains(event.target)) {
        details.removeAttribute('open');
      }
    });
  });
</script>
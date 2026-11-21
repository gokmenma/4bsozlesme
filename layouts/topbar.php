<?php
global $pageTitle, $pageSubtitle;

// --- Kullanıcının kurumları (topbar kurum seçici) ---
$tenants = [];
$currentTenantId = $_SESSION['tenant_id'] ?? null;
$currentTenant = null;
$canAddTenant = false;

if (!empty($_SESSION['user_id'])) {
    try {
        $userModel = new User();
        $tenants = $userModel->getTenants($_SESSION['user_id']);
        if (!empty($tenants)) {
            foreach ($tenants as $t) {
                if ($t['id'] == $currentTenantId) {
                    $currentTenant = $t;
                    break;
                }
            }
            if (!$currentTenant && !empty($tenants[0])) {
                $currentTenant = $tenants[0];
                $_SESSION['tenant_id'] = $currentTenant['id'];
                $currentTenantId = $currentTenant['id'];
            }
        }
        if (!$currentTenant && !empty($currentTenantId)) {
            global $db;
            $stmtT = $db->prepare("SELECT * FROM tenants WHERE id = ?");
            $stmtT->execute([$currentTenantId]);
            $currentTenant = $stmtT->fetch();
        }
        if (!$currentTenant) {
            global $db;
            $stmtFirstT = $db->query("SELECT * FROM tenants ORDER BY id ASC LIMIT 1");
            $currentTenant = $stmtFirstT->fetch();
            if ($currentTenant) {
                $_SESSION['tenant_id'] = $currentTenant['id'];
                $currentTenantId = $currentTenant['id'];
            }
        }

        // Abonelik limit kontrolü
        global $db;
        $stmtSub = $db->prepare("SELECT s.*, sp.features FROM subscriptions s
                              JOIN subscription_plans sp ON s.plan_id = sp.id
                              WHERE s.tenant_id = ? AND s.status = 'active'
                              ORDER BY s.id DESC LIMIT 1");
        $stmtSub->execute([$currentTenantId]);
        $activeSub = $stmtSub->fetch();

        $tenantLimit = 1;
        if (($_SESSION['role'] ?? '') === 'superadmin') {
            $tenantLimit = 999;
        } elseif ($activeSub) {
            $features = $activeSub['features'];
            if (stripos($features, 'Sınırsız') !== false) {
                $tenantLimit = 999;
            } else {
                foreach (explode(',', $features) as $feature) {
                    if (preg_match('/(\d+)\s*Kurum/i', trim($feature), $matches)) {
                        $tenantLimit = (int) $matches[1];
                        break;
                    }
                }
            }
        }
        $canAddTenant = count($tenants) < $tenantLimit;
    } catch (Exception $e) {
        error_log('Topbar tenant load error: ' . $e->getMessage());
    }
}

// --- Kullanıcının bildirimleri (topbar bildirim rozeti) ---
$notifItems = [];
$notifUnread = 0;
if (!empty($_SESSION['user_id'])) {
    try {
        $notificationModel = new Notification();
        $notifItems = $notificationModel->getForUser((int)$_SESSION['user_id'], 15);
        $notifUnread = $notificationModel->countUnread((int)$_SESSION['user_id']);
    } catch (Exception $e) {
        $notifItems = [];
        $notifUnread = 0;
    }
}

if (!function_exists('fbTimeAgo')) {
    function fbTimeAgo($datetime): string {
        if (empty($datetime)) return '';
        $ts = strtotime($datetime);
        if (!$ts) return '';
        $diff = time() - $ts;
        if ($diff < 60) return 'az önce';
        if ($diff < 3600) return floor($diff / 60) . ' dk önce';
        if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
        if ($diff < 604800) return floor($diff / 86400) . ' gün önce';
        return date('d.m.Y', $ts);
    }
}
?>
<header class="app-topbar">
  <div class="topbar-left" style="min-width: 0;">
    <button
      type="button"
      class="topbar-btn"
      aria-label="Menüyü Daralt/Genişlet"
      title="Menüyü Daralt/Genişlet"
      onclick="document.dispatchEvent(new CustomEvent('basecoat:sidebar'))"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect width="18" height="18" x="3" y="3" rx="2" />
        <path d="M9 3v18" />
      </svg>
    </button>

    <details class="relative" id="tenant-switcher" data-tenant-menu style="min-width: 0;">
      <summary class="no-marker inline-flex items-center gap-1.5 cursor-pointer rounded-md px-2 py-1.5 text-[13px] font-semibold text-zinc-900 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors max-w-[260px]">
        <span class="truncate"><?php echo htmlspecialchars($currentTenant['name'] ?? 'Varsayılan Kurum', ENT_QUOTES, 'UTF-8'); ?></span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 shrink-0">
          <path d="m6 9 6 6 6-6"/>
        </svg>
      </summary>

      <div class="absolute left-0 top-full mt-1.5 z-50 min-w-[230px] rounded-xl border border-border bg-white dark:bg-zinc-900 p-1.5 shadow-lg">
        <div class="max-h-64 overflow-y-auto custom-scrollbar">
          <?php foreach ($tenants as $tenant): ?>
            <a href="<?php echo routeUrl('/switch-tenant?id=' . (int)$tenant['id']); ?>"
               class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-lg text-[13px] font-medium transition-colors <?php echo $tenant['id'] == $currentTenantId ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60'; ?>">
              <span class="truncate"><?php echo htmlspecialchars($tenant['name'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php if ($tenant['id'] == $currentTenantId): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                  <polyline points="20 6 9 17 4 12" />
                </svg>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="border-t border-border mt-1 pt-1">
          <?php if ($canAddTenant): ?>
            <button type="button"
              onclick="document.getElementById('add-tenant-modal').showModal(); document.getElementById('tenant-switcher').removeAttribute('open');"
              class="flex items-center gap-2 w-full px-2.5 py-2 rounded-lg text-[13px] font-medium text-zinc-900 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/><path d="M12 5v14"/>
              </svg>
              <span>Yeni Kurum Ekle</span>
            </button>
          <?php else: ?>
            <button type="button"
              onclick="if(typeof showToast==='function') showToast({category: 'warning', title: 'Limit Aşıldı', description: 'Kurum ekleme limitine ulaştınız. Lütfen paketinizi yükseltin.'});"
              class="flex items-center gap-2 w-full px-2.5 py-2 rounded-lg text-[13px] font-medium text-zinc-400 opacity-60 cursor-not-allowed">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/><path d="M12 5v14"/>
              </svg>
              <span>Yeni Kurum Ekle</span>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </details>
  </div>

  <div class="topbar-center hidden sm:flex">
    <div class="global-search-container" id="global-search-container">
      <div class="global-search-input-box" id="global-search-input-box">
        <svg xmlns="http://www.w3.org/2000/svg" class="global-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
        </svg>
        <input type="text" class="global-search-input" id="global-search-input"
               placeholder="Sayfa veya modül ara..."
               autocomplete="off" spellcheck="false">
        <div class="global-search-kbd-badge" title="Kısayol: Ctrl + K"><kbd>ctrl</kbd><kbd>k</kbd></div>
      </div>
      <div class="global-search-dropdown" id="global-search-dropdown" style="display: none;">
        <div class="global-search-results" id="global-search-results"></div>
      </div>
    </div>
  </div>

  <div class="topbar-right">
    <details class="relative" data-feedback-menu>
      <summary
        class="no-marker topbar-btn relative"
        aria-label="Bildirimler"
        title="Bildirimler"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M10.27 21a2 2 0 0 0 3.46 0" />
          <path d="M14.5 4.1a5 5 0 0 0-7 4.6c0 1.8-.6 3.4-1.7 4.8L4 16h16l-1.8-2.5a7.8 7.8 0 0 1-1.7-4.8 5 5 0 0 0-2-4.6Z" />
        </svg>
        <span id="notif-badge"
          class="absolute top-1 right-1 inline-flex min-w-[16px] h-[16px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white leading-none <?php echo $notifUnread > 0 ? '' : 'hidden'; ?>"
          <?php if ($notifUnread <= 0): ?>style="display:none"<?php endif; ?>>
          <?php echo $notifUnread > 9 ? '9+' : (int)$notifUnread; ?>
        </span>
      </summary>

      <div class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-xl border border-border bg-white dark:bg-zinc-900 shadow-lg overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-border">
          <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Bildirimler</span>
          <button type="button" id="notif-mark-all" onclick="markAllNotificationsRead()"
            class="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 <?php echo $notifUnread > 0 ? '' : 'hidden'; ?>">
            Tümünü okundu yap
          </button>
        </div>

        <div class="max-h-96 overflow-y-auto custom-scrollbar" id="notif-list">
          <?php if (empty($notifItems)): ?>
            <div class="px-4 py-10 text-center">
              <p class="text-sm text-zinc-500 dark:text-zinc-400">Henüz bildiriminiz yok.</p>
            </div>
          <?php else: ?>
            <?php foreach ($notifItems as $item):
              $isUnread = (int)($item['is_read'] ?? 0) === 0;
            ?>
              <div class="notif-item flex gap-3 px-4 py-3 border-b border-border last:border-0 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors <?php echo $isUnread ? 'bg-blue-50/50 dark:bg-blue-500/5' : ''; ?>"
                data-notif-id="<?php echo (int)$item['id']; ?>" data-unread="<?php echo $isUnread ? '1' : '0'; ?>"
                <?php if (!empty($item['link'])): ?>data-link="<?php echo htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
                onclick="onNotificationClick(this)">
                <div class="mt-0.5 size-8 shrink-0 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100 <?php echo $isUnread ? '' : 'opacity-80'; ?>"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if ($isUnread): ?><span class="mt-1.5 size-2 shrink-0 rounded-full bg-blue-500"></span><?php endif; ?>
                  </div>
                  <?php if (!empty($item['body'])): ?>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 whitespace-pre-line break-words line-clamp-3"><?php echo htmlspecialchars($item['body'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <?php endif; ?>
                  <span class="text-[11px] text-zinc-400 mt-1 block"><?php echo fbTimeAgo($item['created_at']); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="border-t border-border">
          <a href="<?php echo routeUrl('/profil?tab=inbox'); ?>" class="block px-4 py-3 text-center text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
            Tüm bildirimleri gör
          </a>
        </div>
      </div>
    </details>

    <button type="button" id="theme-toggle" class="topbar-btn" title="Tema Değiştir" onclick="if(typeof toggleAppTheme==='function') toggleAppTheme();">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dark:hidden" aria-hidden="true">
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2" /><path d="M12 20v2" />
        <path d="m4.93 4.93 1.41 1.41" /><path d="m17.66 17.66 1.41 1.41" />
        <path d="M2 12h2" /><path d="M20 12h2" />
        <path d="m6.34 17.66-1.41 1.41" /><path d="m19.07 4.93-1.41 1.41" />
      </svg>
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden dark:block" aria-hidden="true">
        <path d="M20.99 11.65A9 9 0 1 1 12.35 3a7 7 0 0 0 8.64 8.65Z" />
      </svg>
    </button>
  </div>
</header>

<!-- Yeni Kurum Ekleme Modalı -->
<dialog id="add-tenant-modal" class="modal p-0 bg-transparent border-none backdrop:bg-black/50 backdrop:backdrop-blur-sm">
  <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
    <div class="modal-box w-full max-w-sm p-6 bg-white dark:bg-zinc-900 border border-border shadow-2xl rounded-2xl pointer-events-auto animate-in zoom-in-95 duration-200">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Yeni Kurum Ekle</h3>
        <button type="button" onclick="document.getElementById('add-tenant-modal').close()" class="size-8 flex items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-zinc-500">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6 6 18" /><path d="m6 6 12 12" />
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
  (() => {
    const menu = document.querySelector('details[data-feedback-menu]');
    const badge = document.getElementById('notif-badge');
    const markAllBtn = document.getElementById('notif-mark-all');
    const NOTIF_READ_URL = '<?php echo routeUrl('/bildirim-okundu'); ?>';
    const NOTIF_READ_ALL_URL = '<?php echo routeUrl('/bildirim-tumu-okundu'); ?>';

    function refreshBadge(count) {
      if (!badge) return;
      if (count > 0) {
        badge.textContent = count > 9 ? '9+' : count;
        badge.classList.remove('hidden');
        badge.style.display = '';
      } else {
        badge.classList.add('hidden');
        badge.style.display = 'none';
        if (markAllBtn) markAllBtn.classList.add('hidden');
      }
    }

    window.onNotificationClick = function (el) {
      const id = el.getAttribute('data-notif-id');
      const link = el.getAttribute('data-link');
      if (el.getAttribute('data-unread') === '1') {
        el.setAttribute('data-unread', '0');
        el.classList.remove('bg-blue-50/50', 'dark:bg-blue-500/5');
        el.querySelector('.bg-blue-500')?.remove();
        fetch(NOTIF_READ_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body: 'id=' + encodeURIComponent(id)
        }).then(r => r.json()).then(d => { if (d && typeof d.unread !== 'undefined') refreshBadge(d.unread); }).catch(() => {});
      }
      if (link) window.location.href = link;
    };

    window.markAllNotificationsRead = function () {
      fetch(NOTIF_READ_ALL_URL, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).catch(() => {});
      document.querySelectorAll('#notif-list .notif-item').forEach((el) => {
        el.setAttribute('data-unread', '0');
        el.classList.remove('bg-blue-50/50', 'dark:bg-blue-500/5');
        el.querySelector('.bg-blue-500')?.remove();
      });
      refreshBadge(0);
    };

    document.addEventListener('click', (event) => {
      if (menu && !menu.contains(event.target)) menu.removeAttribute('open');
      document.querySelectorAll('details[data-tenant-menu]').forEach((details) => {
        if (!details.contains(event.target)) details.removeAttribute('open');
      });
    });

    // Global Search (Ctrl+K) - Sidebar modülleri arasında hızlı geçiş
    const gsInput = document.getElementById('global-search-input');
    const gsDropdown = document.getElementById('global-search-dropdown');
    const gsResults = document.getElementById('global-search-results');

    function getNavItems() {
      const items = [];
      document.querySelectorAll('#sidebar-scroll-container .sidebar-group').forEach(group => {
        const groupName = group.querySelector('.sidebar-group-title')?.textContent?.trim() || '';
        group.querySelectorAll('.nav-link').forEach(link => {
          items.push({
            title: link.dataset.title || link.textContent.trim(),
            href: link.getAttribute('href'),
            group: groupName
          });
        });
      });
      return items;
    }

    function renderGsResults(q) {
      if (!gsResults || !gsDropdown) return;
      const term = q.trim().toLowerCase();
      if (!term) {
        gsDropdown.style.display = 'none';
        return;
      }
      const matches = getNavItems().filter(item =>
        item.title.toLowerCase().includes(term) || item.group.toLowerCase().includes(term)
      );
      if (matches.length === 0) {
        gsResults.innerHTML = '<div class="px-3 py-4 text-center text-xs text-zinc-500">Sonuç bulunamadı</div>';
      } else {
        gsResults.innerHTML = matches.map((m, idx) =>
          `<a href="${m.href}" class="gs-result-item${idx === 0 ? ' active' : ''}">
            <span>${m.title}</span>
            <span class="gs-result-group">${m.group}</span>
          </a>`
        ).join('');
      }
      gsDropdown.style.display = 'block';
    }

    if (gsInput) {
      gsInput.addEventListener('input', () => renderGsResults(gsInput.value));
      gsInput.addEventListener('focus', () => { if (gsInput.value.trim()) renderGsResults(gsInput.value); });
      gsInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          gsDropdown.style.display = 'none';
          gsInput.blur();
        } else if (e.key === 'Enter') {
          const first = gsResults.querySelector('.gs-result-item.active, .gs-result-item');
          if (first) window.location.href = first.getAttribute('href');
        }
      });
      document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
          e.preventDefault();
          gsInput.focus();
          gsInput.select();
        }
      });
      document.addEventListener('click', (e) => {
        const container = document.getElementById('global-search-container');
        if (container && !container.contains(e.target)) {
          gsDropdown.style.display = 'none';
        }
      });
    }
  })();
</script>

<?php
global $pageTitle, $pageSubtitle;

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

// "x dakika önce" biçimi
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
<header class="app-topbar flex items-center justify-between border-b border-border bg-background/95 px-3 backdrop-blur md:px-5">
  <div class="flex min-w-0 items-center gap-2">
    <button
      type="button"
      class="btn-outline inline-flex size-9 shrink-0 items-center justify-center p-0"
      aria-label="Sidebar aç/kapat"
      title="Sidebar aç/kapat"
      onclick="document.dispatchEvent(new CustomEvent('basecoat:sidebar'))"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect width="18" height="18" x="3" y="3" rx="2" />
        <path d="M9 3v18" />
      </svg>
    </button>

    <div class="hidden min-w-0 flex-col leading-tight sm:flex">
      <span class="truncate text-sm font-medium pageTitle"><?php echo $pageTitle ?? "Sözleşme Takip" ?></span>
      <span class="truncate text-xs text-muted-foreground pageSubtitle"><?php echo $pageSubtitle ?? "Hoş geldiniz" ?></span>
    </div>
  </div>

  <div class="flex items-center gap-2">
    <button
      type="button"
      class="btn-outline hidden size-9 shrink-0 items-center justify-center p-0 sm:inline-flex"
      aria-label="Arama"
      title="Arama"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m21 21-4.34-4.34" />
        <circle cx="11" cy="11" r="8" />
      </svg>
    </button>

    <details class="relative" data-theme-menu>
      <summary class="no-marker btn-outline inline-flex size-9 cursor-pointer items-center justify-center p-0" aria-label="Tema seçimi" title="Tema seçimi">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="4" />
          <path d="M12 2v2" />
          <path d="M12 20v2" />
          <path d="m4.93 4.93 1.41 1.41" />
          <path d="m17.66 17.66 1.41 1.41" />
          <path d="M2 12h2" />
          <path d="M20 12h2" />
          <path d="m6.34 17.66-1.41 1.41" />
          <path d="m19.07 4.93-1.41 1.41" />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M20.99 11.65A9 9 0 1 1 12.35 3a7 7 0 0 0 8.64 8.65Z" />
        </svg>
      </summary>

      <div class="absolute right-0 z-50 mt-2 w-40 rounded-md border border-border bg-white dark:bg-zinc-900 p-1 shadow-lg">
        <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-2 text-left text-sm hover:bg-muted" data-theme-value="light">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2" />
            <path d="M12 20v2" />
            <path d="M4 12H2" />
            <path d="M22 12h-2" />
          </svg>
          Açık
        </button>
        <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-2 text-left text-sm hover:bg-muted" data-theme-value="dark">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20.99 11.65A9 9 0 1 1 12.35 3a7 7 0 0 0 8.64 8.65Z" />
          </svg>
          Koyu
        </button>
        <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-2 text-left text-sm hover:bg-muted" data-theme-value="system">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="18" height="12" x="3" y="4" rx="2" />
            <path d="M8 20h8" />
            <path d="M12 16v4" />
          </svg>
          Sistem
        </button>
      </div>
    </details>

    <details class="relative" data-feedback-menu>
      <summary
        class="no-marker btn-outline relative inline-flex size-9 shrink-0 cursor-pointer items-center justify-center p-0"
        aria-label="Bildirimler"
        title="Geri bildirimlerim"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M10.27 21a2 2 0 0 0 3.46 0" />
          <path d="M14.5 4.1a5 5 0 0 0-7 4.6c0 1.8-.6 3.4-1.7 4.8L4 16h16l-1.8-2.5a7.8 7.8 0 0 1-1.7-4.8 5 5 0 0 0-2-4.6Z" />
        </svg>
        <span id="notif-badge"
          class="absolute -right-1 -top-1 inline-flex min-w-[18px] h-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white <?php echo $notifUnread > 0 ? '' : 'hidden'; ?>"
          <?php if ($notifUnread <= 0): ?>style="display:none"<?php endif; ?>>
          <?php echo $notifUnread > 9 ? '9+' : (int)$notifUnread; ?>
        </span>
      </summary>

      <div class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-xl border border-border bg-white dark:bg-zinc-900 shadow-lg overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-border">
          <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Bildirimler</span>
          <button type="button" id="notif-mark-all" onclick="markAllNotificationsRead()"
            class="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 <?php echo $notifUnread > 0 ? '' : 'hidden'; ?>">
            Tümünü okundu işaretle
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
  </div>
</header>

<script>
  (() => {
    const menu = document.querySelector('details[data-feedback-menu]');
    if (!menu) return;
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

    // Dışarı tıklayınca kapat
    document.addEventListener('click', (event) => {
      if (!menu.contains(event.target)) menu.removeAttribute('open');
    });
  })();
</script>

<script>
  (() => {
    const media = window.matchMedia('(prefers-color-scheme: dark)');

    const applyTheme = (theme) => {
      const selectedTheme = theme || localStorage.getItem('theme') || 'system';
      const useDark = selectedTheme === 'dark' || (selectedTheme === 'system' && media.matches);

      document.documentElement.classList.toggle('dark', useDark);
      document.documentElement.dataset.theme = selectedTheme;
      localStorage.setItem('theme', selectedTheme);
    };

    document.querySelectorAll('[data-theme-value]').forEach((button) => {
      button.addEventListener('click', () => {
        applyTheme(button.dataset.themeValue);
        button.closest('details')?.removeAttribute('open');
      });
    });

    media.addEventListener('change', () => {
      if ((localStorage.getItem('theme') || 'system') === 'system') {
        applyTheme('system');
      }
    });

    document.addEventListener('click', (event) => {
      document.querySelectorAll('details[data-theme-menu], details[data-user-menu]').forEach((details) => {
        if (!details.contains(event.target)) {
          details.removeAttribute('open');
        }
      });
    });
  })();
</script>

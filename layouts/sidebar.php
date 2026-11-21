<?php
$currentUserName = $_SESSION['user_name'] ?? 'Kullanıcı';
$currentUserEmail = $_SESSION['user_email'] ?? '';
$currentUserRole = $_SESSION['role'] ?? 'user';
$nameParts = preg_split('/\s+/', trim($currentUserName));
$userInitials = strtoupper(mb_substr($nameParts[0] ?? 'U', 0, 1) . mb_substr($nameParts[1] ?? '', 0, 1));
if ($userInitials === '') {
    $userInitials = 'KU';
}

$currentPage = $page ?? '/';
function isMenuActive($href, $currentPage)
{
    $href = '/' . ltrim($href, '/');
    return $href === $currentPage;
}

$rolePermModel = new RolePermission();
$currentUserRoleId = $_SESSION['role_id'] ?? null;
function canSeePage($route)
{
    global $rolePermModel, $currentUserRoleId, $currentUserRole;
    return $rolePermModel->hasAccess($currentUserRoleId, $route, $currentUserRole);
}
?>

<aside class="sidebar" data-side="left" aria-hidden="false">
  <nav aria-label="Ana menü">
    <header class="sidebar-header">
      <a href="<?php echo routeUrl('/'); ?>" class="sidebar-logo" style="text-decoration:none;">
        <div class="logo-box">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/>
            <path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/>
            <path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>
          </svg>
        </div>
        <div class="logo-text">
          <span class="logo-title">Sözleşme Takip</span>
          <span class="logo-subtitle">v1.0</span>
        </div>
      </a>
    </header>

    <div class="sidebar-search-wrapper">
      <div class="sidebar-search-field">
        <svg xmlns="http://www.w3.org/2000/svg" class="sidebar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="8"/>
          <path d="m21 21-4.3-4.3"/>
        </svg>
        <input type="text" id="sidebar-search-input" placeholder="Menüde ara..." autocomplete="off">
        <button type="button" id="sidebar-search-clear" title="Temizle">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <section class="scrollbar" id="sidebar-scroll-container">
      <?php if (canSeePage('/')): ?>
      <div role="group" class="sidebar-group" aria-labelledby="group-label-genel">
        <h3 id="group-label-genel" class="sidebar-group-title">Genel</h3>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/'); ?>"
               class="nav-link<?php echo isMenuActive('/', $currentPage) ? ' active' : ''; ?>"
               data-title="Ana Sayfa">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
              <span>Ana Sayfa</span>
            </a>
          </li>
        </ul>
      </div>
      <?php endif; ?>

      <?php if (canSeePage('/personel-listesi') || canSeePage('/ucret-tanimlari')): ?>
      <div role="group" class="sidebar-group" aria-labelledby="group-label-yonetim">
        <h3 id="group-label-yonetim" class="sidebar-group-title">Personel & Ücret</h3>
        <ul class="sidebar-menu-list">
          <?php if (canSeePage('/personel-listesi')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/personel-listesi'); ?>"
               class="nav-link<?php echo isMenuActive('personel-listesi', $currentPage) ? ' active' : ''; ?>"
               data-title="Personeller">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <span>Personeller</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if (canSeePage('/ucret-tanimlari')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/ucret-tanimlari'); ?>"
               class="nav-link<?php echo isMenuActive('ucret-tanimlari', $currentPage) ? ' active' : ''; ?>"
               data-title="Ücret Tanımları">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.744 17.736a6 6 0 1 1-7.48-7.48"/><path d="M15 6h1v4"/><path d="m6.134 14.768.866-.5 2 3.464"/><circle cx="16" cy="8" r="6"/></svg>
              <span>Ücret Tanımları</span>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if (canSeePage('/sozlesme-taslagi') || canSeePage('/tanimlamalar') || canSeePage('/yapilacaklar') || canSeePage('/doner-matrahi-olustur')): ?>
      <div role="group" class="sidebar-group" aria-labelledby="group-label-islemler">
        <h3 id="group-label-islemler" class="sidebar-group-title">Diğer İşlemler</h3>
        <ul class="sidebar-menu-list">
          <?php if (canSeePage('/sozlesme-taslagi')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/sozlesme-taslagi'); ?>"
               class="nav-link<?php echo isMenuActive('sozlesme-taslagi', $currentPage) ? ' active' : ''; ?>"
               data-title="Sözleşme Taslağı">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
              <span>Sözleşme Taslağı</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if (canSeePage('/tanimlamalar')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/tanimlamalar'); ?>"
               class="nav-link<?php echo isMenuActive('tanimlamalar', $currentPage) ? ' active' : ''; ?>"
               data-title="Tanımlamalar">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><circle cx="10" cy="14" r="2"/><path d="m10 10 .5 1"/><path d="m10 18 .5-1"/></svg>
              <span>Tanımlamalar</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if (canSeePage('/yapilacaklar')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/yapilacaklar'); ?>"
               class="nav-link<?php echo isMenuActive('yapilacaklar', $currentPage) ? ' active' : ''; ?>"
               data-title="Yapılacaklar">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="6" height="6" rx="1"/><path d="m3 17 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>
              <span>Yapılacaklar</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if (canSeePage('/doner-matrahi-olustur')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/doner-matrahi-olustur'); ?>"
               class="nav-link<?php echo isMenuActive('doner-matrahi-olustur', $currentPage) ? ' active' : ''; ?>"
               data-title="Döner Matrahı Oluştur">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
              <span>Döner Matrahı Oluştur</span>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($currentUserRole === 'superadmin' || $currentUserRole === 'admin' || canSeePage('/kullanicilar') || canSeePage('/yetki-gruplari')): ?>
      <div role="group" class="sidebar-group" aria-labelledby="group-label-sistem">
        <h3 id="group-label-sistem" class="sidebar-group-title">Sistem</h3>
        <ul class="sidebar-menu-list">
          <?php if (canSeePage('/kullanicilar')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/kullanicilar'); ?>"
               class="nav-link<?php echo isMenuActive('kullanicilar', $currentPage) ? ' active' : ''; ?>"
               data-title="Kullanıcılar">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 21a8 8 0 0 0-16 0"/><circle cx="10" cy="8" r="5"/><path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/></svg>
              <span>Kullanıcılar</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if (canSeePage('/yetki-gruplari')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/yetki-gruplari'); ?>"
               class="nav-link<?php echo isMenuActive('yetki-gruplari', $currentPage) ? ' active' : ''; ?>"
               data-title="Yetki Grupları">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
              <span>Yetki Grupları</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($currentUserRole === 'superadmin' || canSeePage('/kurum-yonetimi')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/kurum-yonetimi'); ?>"
               class="nav-link<?php echo isMenuActive('kurum-yonetimi', $currentPage) ? ' active' : ''; ?>"
               data-title="Kurum Yönetimi">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
              <span>Kurum Yönetimi</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($currentUserRole === 'superadmin' || canSeePage('/abonelik')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/abonelik'); ?>"
               class="nav-link<?php echo isMenuActive('abonelik', $currentPage) ? ' active' : ''; ?>"
               data-title="Abonelik">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
              <span>Abonelik</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($currentUserRole === 'superadmin' || canSeePage('/ayarlar')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/ayarlar'); ?>"
               class="nav-link<?php echo isMenuActive('ayarlar', $currentPage) ? ' active' : ''; ?>"
               data-title="Ayarlar">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
              <span>Ayarlar</span>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($currentUserRole === 'superadmin' || canSeePage('/geri-bildirimler')): ?>
          <li class="sidebar-menu-item">
            <a href="<?php echo routeUrl('/geri-bildirimler'); ?>"
               class="nav-link<?php echo isMenuActive('geri-bildirimler', $currentPage) ? ' active' : ''; ?>"
               data-title="Geri Bildirimler">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M7 9h10"/><path d="M7 13h6"/></svg>
              <span>Geri Bildirimler</span>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>
    </section>

    <footer class="sidebar-footer">
      <details class="user-dropdown" data-user-menu>
        <summary>
          <div class="user-avatar"><?php echo htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="user-email"><?php echo htmlspecialchars($currentUserEmail !== '' ? $currentUserEmail : $currentUserRole, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/>
          </svg>
        </summary>
        <div class="dropdown-content">
          <div class="dropdown-header">
            <p class="dropdown-label">Hesabım</p>
            <p class="dropdown-email"><?php echo htmlspecialchars($currentUserEmail !== '' ? $currentUserEmail : $currentUserName, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
          <a href="<?php echo routeUrl('/profil'); ?>" class="dropdown-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20a6 6 0 0 0-12 0"/><circle cx="12" cy="10" r="4"/><circle cx="12" cy="12" r="10"/></svg>
            <span>Profil</span>
          </a>
          <button type="button" onclick="if(typeof openFeedbackDialog==='function') openFeedbackDialog();" class="dropdown-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M7 9h10"/><path d="M7 13h6"/></svg>
            <span>Geri Bildirim</span>
          </button>
          <hr>
          <a href="<?php echo routeUrl('/logout'); ?>" class="dropdown-item logout">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>
            <span>Çıkış yap</span>
          </a>
        </div>
      </details>
    </footer>
  </nav>
</aside>

<script>
(function () {
    const input = document.getElementById('sidebar-search-input');
    const clearBtn = document.getElementById('sidebar-search-clear');
    const container = document.getElementById('sidebar-scroll-container');
    if (!input || !container) return;

    function filterSidebar() {
        const q = input.value.trim().toLowerCase();
        if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

        container.querySelectorAll('.sidebar-group').forEach(group => {
            let visibleCount = 0;
            group.querySelectorAll('.sidebar-menu-item').forEach(item => {
                const title = (item.querySelector('.nav-link')?.dataset.title || item.textContent || '').toLowerCase();
                const match = !q || title.includes(q);
                item.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            group.style.display = visibleCount > 0 ? '' : 'none';
        });
    }

    input.addEventListener('input', filterSidebar);
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            input.value = '';
            filterSidebar();
            input.focus();
        });
    }

    document.addEventListener('click', (event) => {
        document.querySelectorAll('details[data-user-menu]').forEach((details) => {
            if (!details.contains(event.target)) {
                details.removeAttribute('open');
            }
        });
    });
})();
</script>

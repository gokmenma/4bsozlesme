<?php
$pageTitle = 'Yetki Grupları & Sayfa İzinleri';
$pageSubtitle = 'Sistemdeki rollerin hangi sayfalara erişebileceğini belirleyin';
$roles = $roles ?? [];
$definedPages = $definedPages ?? [];

// Gruplara ayrılmış sayfaları hazırla
$categorizedPages = [];
foreach ($definedPages as $route => $info) {
    $cat = $info['category'] ?? 'Genel';
    $categorizedPages[$cat][$route] = $info;
}
?>

<div class="space-y-6">
  <!-- Üst Aksiyon Çubuğu (Sayfa başlığı topbar'da gösterildiği için burada sadece aksiyon butonu yer alır) -->
  <div class="flex items-center justify-between pb-2 border-b border-zinc-200/80 dark:border-zinc-800/80">
    <div class="flex items-center gap-2">
      <div class="size-8 rounded-lg bg-zinc-900 text-zinc-50 dark:bg-zinc-100 dark:text-zinc-900 flex items-center justify-center shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <path d="m9 12 2 2 4-4"/>
        </svg>
      </div>
      <div>
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 tracking-tight">Yetki Grupları Matrisi</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Rol seçin ve erişim verilecek sayfaları açıp kapatın</p>
      </div>
    </div>

    <button onclick="document.getElementById('modal-add-role').showModal()" class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-3.5 py-2 text-xs font-medium text-zinc-50 hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-50/90 shadow transition-colors cursor-pointer gap-1.5">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 12h14"/>
        <path d="M12 5v14"/>
      </svg>
      Yeni Yetki Grubu
    </button>
  </div>

  <!-- Ana İçerik Grid (12 Sütun) -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    <!-- Sol Sütun: Yetki Grupları Kart Listesi (Shadcn Style 4 Sütun) -->
    <div class="lg:col-span-4 space-y-3">
      <div class="flex items-center justify-between px-2">
        <span class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Yetki Grupları</span>
        <span class="inline-flex items-center rounded-md border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-[11px] font-mono text-zinc-600 dark:text-zinc-400">
          <?php echo count($roles); ?> Grup
        </span>
      </div>

      <div class="space-y-3 max-h-[calc(100vh-230px)] overflow-y-auto p-1.5 px-2.5 custom-scrollbar" id="roles-list-container">
        <?php foreach ($roles as $index => $role): ?>
          <?php
            $isSystem = !empty($role['is_system']);
            $userCount = (int)($role['user_count'] ?? 0);
            $isSelected = ($index === 0);
          ?>
          <div id="role-card-<?php echo $role['id']; ?>" 
               onclick="selectRoleForMatrix(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8'); ?>', <?php echo $isSystem ? 'true' : 'false'; ?>)"
               class="role-card group relative p-4 rounded-xl transition-all cursor-pointer select-none <?php echo $isSelected ? 'bg-zinc-100/90 dark:bg-zinc-900 border-2 border-zinc-900 dark:border-zinc-100 shadow-md' : 'bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50 shadow-sm'; ?>">
            
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="size-8 rounded-md flex items-center justify-center font-bold text-xs shrink-0 <?php echo $isSelected ? 'bg-zinc-900 text-zinc-50 dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300'; ?>">
                  <?php echo mb_strtoupper(mb_substr($role['name'], 0, 2, 'UTF-8'), 'UTF-8'); ?>
                </div>

                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <h3 class="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                      <?php echo htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </h3>
                  </div>

                  <div class="flex items-center gap-2 mt-0.5">
                    <?php if ($isSystem): ?>
                      <span class="inline-flex items-center rounded-sm px-1.5 py-0.2 text-[10px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                        Sistem
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center rounded-sm px-1.5 py-0.2 text-[10px] font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900/60">
                        Özel
                      </span>
                    <?php endif; ?>

                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                      <?php echo $userCount; ?> kişi
                    </span>
                  </div>
                </div>
              </div>

              <!-- Rol Aksiyon Butonları (Superadmin haricindeki tüm roller düzenlenebilir ve silinebilir) -->
              <?php if ($role['id'] != 1): ?>
                <div class="flex items-center gap-1.5 shrink-0" onclick="event.stopPropagation()">
                  <button type="button" 
                          onclick="openEditRoleModal(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars(addslashes($role['name']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($role['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>')"
                          class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-[11px] font-medium transition-colors" title="Grup Adını ve Açıklamasını Düzenle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    Düzenle
                  </button>
                  <?php if ($userCount === 0): ?>
                    <button type="button" 
                            onclick="confirmDeleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars(addslashes($role['name']), ENT_QUOTES, 'UTF-8'); ?>')"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-red-50 dark:bg-red-950/40 hover:bg-red-100 dark:hover:bg-red-900/60 text-red-600 dark:text-red-400 text-[11px] font-medium transition-colors" title="Grubu Sil">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                      Sil
                    </button>
                  <?php else: ?>
                    <button type="button" 
                            onclick="showToast({category: 'warning', title: 'Silinemez', description: 'Bu gruba tanımlı <?php echo $userCount; ?> kullanıcı bulunmaktadır. Önce kullanıcıların grubunu değiştirin.'})"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-zinc-100 dark:bg-zinc-900 opacity-50 cursor-not-allowed text-zinc-400 text-[11px] font-medium" title="Kullanıcısı olan grup silinemez">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                      Sil
                    </button>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="flex items-center gap-1 shrink-0" title="Superadmin rolü korumalıdır">
                  <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 text-[10px] font-medium border border-amber-200/50 dark:border-amber-900/50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Korumalı
                  </span>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($role['description'])): ?>
              <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-1 mt-1.5 pl-11">
                <?php echo htmlspecialchars($role['description'], ENT_QUOTES, 'UTF-8'); ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Sağ Sütun: Sayfa İzin Matrisi (Shadcn Style 8 Sütun) -->
    <div class="lg:col-span-8">
      <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 shadow-sm overflow-hidden flex flex-col h-full min-h-[520px]">
        
        <!-- Matris Header -->
        <div class="p-4 px-6 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/40 flex items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">İzin Matrisi</span>
              <span id="selected-role-badge" class="inline-flex items-center rounded-md border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-900 dark:text-zinc-100">
                <?php echo htmlspecialchars($roles[0]['name'] ?? 'Rol Seçin', ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>
            <h3 id="selected-role-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5 tracking-tight">
              <?php echo htmlspecialchars($roles[0]['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> Grubunun İzinleri
            </h3>
          </div>

          <div id="superadmin-lock-notice" class="hidden items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Superadmin yetkileri kilitlidir
          </div>
        </div>

        <!-- Matris Formu & Sayfa Kartları -->
        <form id="form-role-permissions" class="flex-1 flex flex-col overflow-hidden" onsubmit="event.preventDefault(); saveRolePermissions();">
          <input type="hidden" id="active_role_id" name="role_id" value="<?php echo $roles[0]['id'] ?? 1; ?>">

          <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
            <?php foreach ($categorizedPages as $categoryName => $pages): ?>
              <div class="space-y-3">
                <div class="flex items-center justify-between pb-1.5 border-b border-zinc-200/80 dark:border-zinc-800/80">
                  <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 flex items-center gap-2">
                    <span class="size-1.5 rounded-full bg-zinc-900 dark:bg-zinc-100"></span>
                    <?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?> Sayfaları
                  </h4>

                  <div class="flex items-center gap-3">
                    <button type="button" onclick="toggleCategoryPermissions('<?php echo md5($categoryName); ?>', true)" class="text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                      Tümünü Seç
                    </button>
                    <span class="text-zinc-300 dark:text-zinc-800">|</span>
                    <button type="button" onclick="toggleCategoryPermissions('<?php echo md5($categoryName); ?>', false)" class="text-xs font-medium text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
                      Tümünü Kaldır
                    </button>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="category-group-<?php echo md5($categoryName); ?>">
                  <?php foreach ($pages as $route => $pageInfo): ?>
                    <label class="page-permission-card flex items-center justify-between p-3 rounded-lg border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-950 hover:border-zinc-400 dark:hover:border-zinc-700 transition-all cursor-pointer select-none">
                      <div class="flex items-center gap-2.5 min-w-0 pr-2">
                        <div class="size-7 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/50 dark:border-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 shrink-0">
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                            <path d="M9 3v18"/>
                          </svg>
                        </div>
                        <div class="min-w-0">
                          <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 block truncate">
                            <?php echo htmlspecialchars($pageInfo['title'], ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                          <span class="text-[10px] font-mono text-zinc-400 block truncate">
                            <?php echo htmlspecialchars($route, ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        </div>
                      </div>

                      <!-- Shadcn Switch Toggle -->
                      <div class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" 
                               id="perm_<?php echo md5($route); ?>" 
                               name="permissions[<?php echo htmlspecialchars($route, ENT_QUOTES, 'UTF-8'); ?>]" 
                               value="1" 
                               class="perm-checkbox sr-only peer">
                        <div class="w-9 h-5 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:after:border-zinc-600 peer-checked:bg-zinc-900 dark:peer-checked:bg-zinc-100"></div>
                      </div>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Footer Action Bar -->
          <div class="p-4 px-6 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/40 flex items-center justify-between gap-4">
            <span class="text-xs text-zinc-500 dark:text-zinc-400">
              Değişiklikleri uygulamak için kaydet butonuna tıklayın.
            </span>

            <button type="submit" id="btn-save-permissions" class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-4 py-2 text-xs font-medium text-zinc-50 hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-50/90 shadow transition-colors cursor-pointer gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
              </svg>
              İzinleri Kaydet
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Yeni Yetki Grubu Ekle (Shadcn Dialog) -->
<dialog id="modal-add-role" class="modal bg-transparent p-0 backdrop:bg-zinc-950/60 backdrop:backdrop-blur-sm">
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200">
      
      <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
          Yeni Yetki Grubu
        </h3>
        <button type="button" onclick="document.getElementById('modal-add-role').close()" class="p-1 rounded-md text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>

      <form onsubmit="event.preventDefault(); submitAddRole();" class="space-y-4 mt-4">
        <div>
          <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
            Grup Adı <span class="text-red-500">*</span>
          </label>
          <input type="text" id="add_role_name" required placeholder="Örn: İnsan Kaynakları Uzmanı" class="w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-950 dark:focus:ring-zinc-300">
        </div>

        <div>
          <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
            Açıklama (Opsiyonel)
          </label>
          <textarea id="add_role_description" rows="3" placeholder="Sorumluluklar ve yetki alanı açıklaması..." class="w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-950 dark:focus:ring-zinc-300 resize-none"></textarea>
        </div>

        <div class="pt-3 flex items-center justify-end gap-2 border-t border-zinc-200 dark:border-zinc-800">
          <button type="button" onclick="document.getElementById('modal-add-role').close()" class="px-3.5 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition-colors">
            İptal
          </button>
          <button type="submit" class="px-4 py-1.5 bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:hover:bg-zinc-200 dark:text-zinc-900 text-white text-xs font-medium rounded-md shadow transition-colors">
            Oluştur
          </button>
        </div>
      </form>
    </div>
  </div>
</dialog>

<!-- Modal: Yetki Grubunu Düzenle (Shadcn Dialog) -->
<dialog id="modal-edit-role" class="modal bg-transparent p-0 backdrop:bg-zinc-950/60 backdrop:backdrop-blur-sm">
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200">
      
      <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
          Yetki Grubunu Düzenle
        </h3>
        <button type="button" onclick="document.getElementById('modal-edit-role').close()" class="p-1 rounded-md text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>

      <form onsubmit="event.preventDefault(); submitUpdateRole();" class="space-y-4 mt-4">
        <input type="hidden" id="edit_role_id">

        <div>
          <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
            Grup Adı <span class="text-red-500">*</span>
          </label>
          <input type="text" id="edit_role_name" required class="w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-transparent px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-950 dark:focus:ring-zinc-300">
        </div>

        <div>
          <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
            Açıklama
          </label>
          <textarea id="edit_role_description" rows="3" class="w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-transparent px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-950 dark:focus:ring-zinc-300 resize-none"></textarea>
        </div>

        <div class="pt-3 flex items-center justify-end gap-2 border-t border-zinc-200 dark:border-zinc-800">
          <button type="button" onclick="document.getElementById('modal-edit-role').close()" class="px-3.5 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition-colors">
            İptal
          </button>
          <button type="submit" class="px-4 py-1.5 bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:hover:bg-zinc-200 dark:text-zinc-900 text-white text-xs font-medium rounded-md shadow transition-colors">
            Güncelle
          </button>
        </div>
      </form>
    </div>
  </div>
</dialog>

<script>
let currentRoleId = <?php echo (int)($roles[0]['id'] ?? 1); ?>;

document.addEventListener('DOMContentLoaded', function() {
  if (currentRoleId > 0) {
    fetchRolePermissions(currentRoleId);
  }
});

function selectRoleForMatrix(roleId, roleName, isSystem) {
  currentRoleId = roleId;
  document.getElementById('active_role_id').value = roleId;
  document.getElementById('selected-role-title').textContent = roleName + ' Grubunun İzinleri';
  document.getElementById('selected-role-badge').textContent = roleName;

  // Aktif kart Shadcn vurgusu
  document.querySelectorAll('.role-card').forEach(card => {
    card.className = 'role-card group relative p-4 rounded-xl border transition-all cursor-pointer select-none bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50 shadow-sm';
    const avatar = card.querySelector('.size-8');
    if (avatar) {
      avatar.className = 'size-8 rounded-md flex items-center justify-center font-bold text-xs shrink-0 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300';
    }
  });
  
  const activeCard = document.getElementById('role-card-' + roleId);
  if (activeCard) {
    activeCard.className = 'role-card group relative p-4 rounded-xl transition-all cursor-pointer select-none bg-zinc-100/90 dark:bg-zinc-900 border-2 border-zinc-900 dark:border-zinc-100 shadow-md';
    const avatar = activeCard.querySelector('.size-8');
    if (avatar) {
      avatar.className = 'size-8 rounded-md flex items-center justify-center font-bold text-xs shrink-0 bg-zinc-900 text-zinc-50 dark:bg-zinc-100 dark:text-zinc-900';
    }
  }

  // Superadmin kilit uyarısı
  const superadminNotice = document.getElementById('superadmin-lock-notice');
  const saveBtn = document.getElementById('btn-save-permissions');
  
  if (roleId === 1) {
    superadminNotice.classList.remove('hidden');
    superadminNotice.classList.add('flex');
    saveBtn.disabled = true;
    saveBtn.classList.add('opacity-50', 'cursor-not-allowed');
  } else {
    superadminNotice.classList.add('hidden');
    superadminNotice.classList.remove('flex');
    saveBtn.disabled = false;
    saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
  }

  fetchRolePermissions(roleId);
}

function fetchRolePermissions(roleId) {
  fetch('<?php echo routeUrl('/yetki-izinleri-getir'); ?>?role_id=' + roleId)
    .then(res => res.json())
    .then(data => {
      if (data.success && data.permissions) {
        const isSuperadmin = (roleId === 1);
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
          const route = cb.name.replace('permissions[', '').replace(']', '');
          cb.checked = isSuperadmin || (data.permissions[route] == 1);
          cb.disabled = isSuperadmin;
        });
      } else {
        showToast({category: 'danger', title: 'Hata', description: data.message || 'İzinler alınamadı.'});
      }
    })
    .catch(err => {
      console.error(err);
      showToast({category: 'danger', title: 'Hata', description: 'Sunucu ile iletişim kurulamadı.'});
    });
}

function toggleCategoryPermissions(catId, status) {
  if (currentRoleId === 1) return;
  const container = document.getElementById('category-group-' + catId);
  if (container) {
    container.querySelectorAll('.perm-checkbox').forEach(cb => {
      cb.checked = status;
    });
  }
}

function saveRolePermissions() {
  if (currentRoleId === 1) {
    showToast({category: 'warning', title: 'Kısıtlama', description: 'Superadmin yetkileri değiştirilemez.'});
    return;
  }

  const form = document.getElementById('form-role-permissions');
  const formData = new FormData(form);

  const saveBtn = document.getElementById('btn-save-permissions');
  saveBtn.disabled = true;
  saveBtn.innerText = 'Kaydediliyor...';

  fetch('<?php echo routeUrl('/yetki-izinleri-kaydet'); ?>', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    saveBtn.disabled = false;
    saveBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> İzinleri Kaydet`;

    if (data.success) {
      showToast({category: 'success', title: 'Başarılı', description: data.message});
    } else {
      showToast({category: 'danger', title: 'Hata', description: data.message});
    }
  })
  .catch(err => {
    saveBtn.disabled = false;
    saveBtn.innerHTML = `İzinleri Kaydet`;
    showToast({category: 'danger', title: 'Hata', description: 'Kaydetme sırasında bir hata oluştu.'});
  });
}

function submitAddRole() {
  const name = document.getElementById('add_role_name').value;
  const description = document.getElementById('add_role_description').value;

  const formData = new FormData();
  formData.append('name', name);
  formData.append('description', description);

  fetch('<?php echo routeUrl('/yetki-grubu-ekle'); ?>', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById('modal-add-role').close();
      showToast({category: 'success', title: 'Başarılı', description: data.message});
      setTimeout(() => location.reload(), 500);
    } else {
      showToast({category: 'danger', title: 'Hata', description: data.message});
    }
  });
}

function openEditRoleModal(id, name, description) {
  document.getElementById('edit_role_id').value = id;
  document.getElementById('edit_role_name').value = name;
  document.getElementById('edit_role_description').value = description;
  document.getElementById('modal-edit-role').showModal();
}

function submitUpdateRole() {
  const id = document.getElementById('edit_role_id').value;
  const name = document.getElementById('edit_role_name').value;
  const description = document.getElementById('edit_role_description').value;

  const formData = new FormData();
  formData.append('id', id);
  formData.append('name', name);
  formData.append('description', description);

  fetch('<?php echo routeUrl('/yetki-grubu-guncelle'); ?>', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById('modal-edit-role').close();
      showToast({category: 'success', title: 'Başarılı', description: data.message});
      setTimeout(() => location.reload(), 500);
    } else {
      showToast({category: 'danger', title: 'Hata', description: data.message});
    }
  });
}

function confirmDeleteRole(id, name) {
  if (confirm(`"${name}" yetki grubunu silmek istediğinizden emin misiniz?`)) {
    const formData = new FormData();
    formData.append('id', id);

    fetch('<?php echo routeUrl('/yetki-grubu-sil'); ?>', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast({category: 'success', title: 'Başarılı', description: data.message});
        setTimeout(() => location.reload(), 500);
      } else {
        showToast({category: 'danger', title: 'Hata', description: data.message});
      }
    });
  }
}
</script>

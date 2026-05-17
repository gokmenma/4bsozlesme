<?php 
$pageTitle = 'Kullanıcı Yönetimi'; 
$pageSubtitle = 'Sistemdeki tüm kullanıcıların listesi ve yönetimi';
$isAdmin = $_SESSION['role'] === 'admin';
$isSuperAdmin = $_SESSION['role'] === 'superadmin';
$isDeveloper = isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1;
$viewType = $viewType ?? $_GET['view_type'] ?? 'mine';
?>

<div class="p-6">
    <!-- Actions Bar -->
    <div class="flex items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-6">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Kullanıcılar</h1>
            
            <?php if ($isSuperAdmin): ?>
            <fieldset class="flex items-center gap-4 ml-2 bg-zinc-50 dark:bg-zinc-800/40 p-1.5 px-3 rounded-lg border border-zinc-200/60 dark:border-zinc-700/60">
                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-zinc-700 dark:text-zinc-300 select-none">
                    <input type="radio" name="view_type" value="mine" onchange="switchViewType(this.value)" class="w-3.5 h-3.5 text-indigo-600 focus:ring-indigo-500 border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800" <?php echo $viewType === 'mine' ? 'checked' : ''; ?>>
                    Benim Kullanıcılarım
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-zinc-700 dark:text-zinc-300 select-none">
                    <input type="radio" name="view_type" value="all" onchange="switchViewType(this.value)" class="w-3.5 h-3.5 text-indigo-600 focus:ring-indigo-500 border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800" <?php echo $viewType === 'all' ? 'checked' : ''; ?>>
                    Tüm Kullanıcılar
                </label>
            </fieldset>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative w-full max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="userSearch" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="Kullanıcı ara...">
            </div>
            <button onclick="document.getElementById('dialog-add-user').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                Yeni Kullanıcı
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden relative flex flex-col h-[calc(100vh-230px)]">
        <?php echo renderTablePreloader(); ?>

        <div id="table-container" class="flex-1 flex flex-col overflow-hidden" style="display: none;">
            <table id="userTable" class="w-full text-left">
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Kurum</th>
                        <?php if ($isSuperAdmin): ?>
                        <th>Deneme Bitiş</th>
                        <?php endif; ?>
                        <th>Kurum Aboneliği</th>
                        <th>Oluşturulma</th>
                        <th>Ekleyen</th>
                        <th class="text-right no-sort">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="font-medium text-zinc-900 dark:text-zinc-100">
                                <button onclick="editUser(<?php echo $u['id']; ?>)" class="hover:text-primary transition-all text-left font-medium flex items-center gap-3 group/name">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-semibold border border-indigo-100 dark:border-indigo-800 group-hover/name:bg-indigo-100 dark:group-hover/name:bg-indigo-900/50 transition-all">
                                        <?php 
                                        $uParts = preg_split('/\s+/', trim($u['name']));
                                        echo htmlspecialchars(strtoupper(mb_substr($uParts[0] ?? 'U', 0, 1) . mb_substr(end($uParts) ?: '', 0, 1)), ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </span>
                                    <span class="group-hover/name:underline underline-offset-4 decoration-primary/50"><?php echo htmlspecialchars($u['name']); ?></span>
                                </button>
                            </td>
                            <td class="text-zinc-600 dark:text-zinc-400"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php 
                                    echo $u['role'] === 'superadmin' ? 'border-purple-100 dark:border-purple-900/30 text-purple-700 dark:text-purple-400' : 
                                        ($u['role'] === 'admin' ? 'border-blue-100 dark:border-blue-900/30 text-blue-700 dark:text-blue-400' : 
                                        'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400'); 
                                ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td class="text-zinc-600 dark:text-zinc-400">
                                <?php echo htmlspecialchars($u['tenant_name'] ?? '-'); ?>
                            </td>
                            <?php if ($isSuperAdmin): ?>
                            <td class="text-zinc-600 dark:text-zinc-400">
                                <?php if (empty($u['subscription'])): ?>
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm"><?php echo !empty($u['trial_ends_at']) ? date('d.m.Y', strtotime($u['trial_ends_at'])) : '-'; ?></span>
                                    <?php 
                                    $trialEnds = !empty($u['trial_ends_at']) ? strtotime($u['trial_ends_at']) : null;
                                    $now = strtotime('today');
                                    if ($trialEnds):
                                        $diffDays = round(($trialEnds - $now) / 86400);
                                        if ($diffDays > 0):
                                            ?>
                                            <span class="inline-flex items-center justify-center w-fit px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800">
                                                <?php echo $diffDays; ?> Gün kaldı
                                            </span>
                                            <?php
                                        else:
                                            ?>
                                            <span class="inline-flex items-center justify-center w-fit px-2 py-0.5 rounded text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-100 dark:border-red-800">
                                                Süre Doldu
                                            </span>
                                            <?php
                                        endif;
                                    endif;
                                    ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="text-zinc-600 dark:text-zinc-400">
                                <?php if (!empty($u['subscription'])): ?>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                            <?php echo htmlspecialchars($u['subscription']['plan_name']); ?>
                                        </span>
                                        <span class="text-xs text-zinc-500">
                                            <?php echo date('d.m.Y', strtotime($u['subscription']['end_date'])); ?>'e kadar
                                        </span>
                                        <?php 
                                        $subEnds = strtotime($u['subscription']['end_date']);
                                        if ($subEnds < strtotime('today')):
                                        ?>
                                            <span class="inline-flex items-center justify-center w-fit px-2 py-0.5 rounded text-[10px] font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-100 dark:border-red-800">
                                                Süresi Dolmuş
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center justify-center w-fit px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800">
                                                Aktif
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-zinc-400">Abonelik Yok</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-zinc-600 dark:text-zinc-400"><?php echo date('d.m.Y H:i', strtotime($u['created_at'])); ?></td>
                            <td class="text-zinc-600 dark:text-zinc-400"><?php echo htmlspecialchars($u['creator_name'] ?? '-'); ?></td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="editUser(<?php echo $u['id']; ?>)" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-zinc-400 transition-colors" title="Düzenle">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                    <button onclick="deleteUser(<?php echo $u['id']; ?>)" class="p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded text-red-400 transition-colors" title="Sil">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yeni Kullanıcı Dialog -->
<dialog id="dialog-add-user" class="dialog w-full sm:max-w-[450px] !overflow-visible" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl !overflow-visible" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Yeni Kullanıcı Ekle</h2>
      <p class="text-sm text-zinc-500">Sisteme erişebilecek yeni bir kullanıcı oluşturun.</p>
    </header>

    <form action="<?php echo routeUrl('kullanici-ekle'); ?>" method="POST" id="form-add-user" class="form grid gap-4">
        <div class="grid gap-2">
            <label for="name">Ad Soyad</label>
            <input type="text" name="name" id="name" required placeholder="Kullanıcının tam adı" />
        </div>
        <div class="grid gap-2">
            <label for="email">E-posta</label>
            <input type="email" name="email" id="email" required placeholder="E-posta adresi" />
        </div>
        <div class="grid gap-2">
            <label for="password">Şifre</label>
            <input type="password" name="password" id="password" required placeholder="En az 6 karakter" />
        </div>
        <div class="grid gap-2">
            <label for="role">Rol</label>
            <div id="select-role-add" class="app-select w-full">
              <button type="button" class="btn-outline w-full justify-between" id="select-role-add-trigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="select-role-add-listbox" onclick="toggleRoleSelect(this, event)">
                <span class="truncate">User</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevrons-up-down-icon lucide-chevrons-up-down text-muted-foreground opacity-50 shrink-0">
                  <path d="m7 15 5 5 5-5" /><path d="m7 9 5-5 5 5" />
                </svg>
              </button>
              <div id="select-role-add-popover" data-popover data-custom-popover aria-hidden="true" class="w-full">
                <div role="listbox" id="select-role-add-listbox" class="p-1">
                    <div role="option" data-value="user" onclick="selectRoleOption(this, 'select-role-add')" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded cursor-pointer text-sm flex items-center justify-between">
                        <span>User</span>
                    </div>
                    <div role="option" data-value="admin" onclick="selectRoleOption(this, 'select-role-add')" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded cursor-pointer text-sm flex items-center justify-between">
                        <span>Admin</span>
                    </div>
                    <?php if ($isDeveloper): ?>
                    <div role="option" data-value="superadmin" onclick="selectRoleOption(this, 'select-role-add')" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded cursor-pointer text-sm flex items-center justify-between">
                        <span>Superadmin</span>
                    </div>
                    <?php endif; ?>
                </div>
              </div>
              <input type="hidden" name="role" value="user" />
            </div>
        </div>
        <?php if ($isSuperAdmin): ?>
        <div class="grid gap-2">
            <label for="trial_ends_at">Deneme Bitiş Tarihi</label>
            <input type="text" name="trial_ends_at" id="trial_ends_at" class="datepicker" value="<?php echo date('d.m.Y', strtotime('+1 month')); ?>" />
        </div>
        <?php endif; ?>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="submit" form="form-add-user" class="btn">Kullanıcıyı Oluştur</button>
    </footer>

    <button type="button" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
  </div>
</dialog>

<!-- Kullanıcı Düzenle Dialog -->
<dialog id="dialog-edit-user" class="dialog w-full sm:max-w-[450px] !overflow-visible" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl !overflow-visible" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Kullanıcı Düzenle</h2>
      <p class="text-sm text-zinc-500">Kullanıcı bilgilerini güncelleyin.</p>
    </header>

    <form id="form-edit-user" class="form grid gap-4">
        <input type="hidden" name="id" id="edit_user_id" />
        <div class="grid gap-2">
            <label for="edit_name">Ad Soyad</label>
            <input type="text" name="name" id="edit_name" required />
        </div>
        <div class="grid gap-2">
            <label for="edit_email">E-posta</label>
            <input type="email" name="email" id="edit_email" required />
        </div>
        <div class="grid gap-2">
            <label for="edit_password">Şifre (Boş bırakılırsa değişmez)</label>
            <input type="password" name="password" id="edit_password" placeholder="Yeni şifre (opsiyonel)" />
        </div>
        <div class="grid gap-2">
            <label for="edit_role">Rol</label>
            <div id="select-role-edit" class="app-select w-full">
              <button type="button" class="btn-outline w-full justify-between" id="select-role-edit-trigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="select-role-edit-listbox" onclick="toggleRoleSelect(this, event)">
                <span class="truncate">User</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevrons-up-down-icon lucide-chevrons-up-down text-muted-foreground opacity-50 shrink-0">
                  <path d="m7 15 5 5 5-5" /><path d="m7 9 5-5 5 5" />
                </svg>
              </button>
              <div id="select-role-edit-popover" data-popover data-custom-popover aria-hidden="true" class="w-full">
                <div role="listbox" id="select-role-edit-listbox" class="p-1">
                    <div role="option" data-value="user" onclick="selectRoleOption(this, 'select-role-edit')" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded cursor-pointer text-sm flex items-center justify-between">
                        <span>User</span>
                    </div>
                    <div role="option" data-value="admin" onclick="selectRoleOption(this, 'select-role-edit')" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded cursor-pointer text-sm flex items-center justify-between">
                        <span>Admin</span>
                    </div>
                    <?php if ($isDeveloper): ?>
                    <div role="option" data-value="superadmin" onclick="selectRoleOption(this, 'select-role-edit')" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded cursor-pointer text-sm flex items-center justify-between">
                        <span>Superadmin</span>
                    </div>
                    <?php endif; ?>
                </div>
              </div>
              <input type="hidden" name="role" value="user" />
            </div>
        </div>
        <?php if ($isSuperAdmin): ?>
        <div class="grid gap-2">
            <label for="edit_trial_ends_at">Deneme Bitiş Tarihi</label>
            <input type="text" name="trial_ends_at" id="edit_trial_ends_at" class="datepicker" />
        </div>
        <?php endif; ?>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" onclick="saveUser()" class="btn">Değişiklikleri Kaydet</button>
    </footer>

    <button type="button" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
  </div>
</dialog>

<!-- Silme Onay Dialog -->
<dialog id="alert-dialog-delete" class="dialog" aria-labelledby="alert-dialog-title" aria-describedby="alert-dialog-description" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl max-w-[400px] w-full" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 id="alert-dialog-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Emin misiniz?</h2>
      <p id="alert-dialog-description" class="text-sm text-zinc-500 mt-2">Bu işlem geri alınamaz. Bu kullanıcıyı sistemden kalıcı olarak sileceksiniz.</p>
    </header>

    <footer class="flex justify-end gap-3">
      <button class="btn-outline" onclick="document.getElementById('alert-dialog-delete').close()">İptal</button>
      <button class="btn bg-red-600 hover:bg-red-700 text-white border-none" onclick="confirmDelete()">Silmeyi Tamamla</button>
    </footer>
  </div>
</dialog>

<script>
function switchViewType(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('view_type', val);
    window.location.href = url.toString();
}

let userToDelete = null;

$(document).ready(function() {
    flatpickr('#trial_ends_at', {
        dateFormat: 'd.m.Y',
        static: true,
        appendTo: document.getElementById('dialog-add-user')
    });
    flatpickr('#edit_trial_ends_at', {
        dateFormat: 'd.m.Y',
        static: true,
        appendTo: document.getElementById('dialog-edit-user')
    });

    const table = initDataTable('#userTable', {
        order: [[0, 'asc']],
        dom: '<"flex-1 overflow-auto"rt><"mt-auto border-t border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row justify-between items-center p-4 gap-4 bg-zinc-50/50 dark:bg-zinc-800/30"lip>',
        preloader: '#table-preloader'
    });

    $('#userSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    $('#form-add-user').on('submit', function(e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize(), function(response) {
            try {
                response = JSON.parse(response);
                if (response.success) {
                    showToast({ category: 'success', title: 'Başarılı', description: response.message });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast({ category: 'error', title: 'Hata', description: response.message });
                }
            } catch (err) {
                console.error("JSON Parse Error:", response);
                showToast({ category: 'error', title: 'Hata', description: 'Sunucudan geçersiz bir yanıt geldi.' });
            }
        });
    });

    // Custom Select Logic
    window.toggleRoleSelect = function(btn, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        const popover = $(btn).siblings('[data-popover], [data-custom-popover]');
        const isHidden = popover.attr('aria-hidden') === 'true';
        
        // Close other popovers first
        $('[data-popover], [data-custom-popover]').attr('aria-hidden', 'true');
        $('.app-select button, .select button').attr('aria-expanded', 'false');
        
        if (isHidden) {
            popover.attr('aria-hidden', 'false');
            $(btn).attr('aria-expanded', 'true');
        }
    };

    window.selectRoleOption = function(el, selectId) {
        if (window.event) window.event.stopPropagation();
        const value = $(el).attr('data-value');
        const text = $(el).find('span').text().trim();
        const $select = $('#' + selectId);
        
        $select.find('input[type="hidden"]').val(value);
        $select.find('button span.truncate').text(text);
        
        // Close popover
        $select.find('[data-popover], [data-custom-popover]').attr('aria-hidden', 'true');
        $select.find('button').attr('aria-expanded', 'false');
    };

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.app-select, .select').length) {
            $('[data-popover], [data-custom-popover]').attr('aria-hidden', 'true');
            $('.app-select button, .select button').attr('aria-expanded', 'false');
        }
    });
});

function editUser(id) {
    $.get('<?php echo routeUrl("kullanici-get"); ?>', { id: id }, function(data) {
        try {
            data = JSON.parse(data);
            if (data.success === false) {
                showToast({ category: 'error', title: 'Hata', description: data.message });
                return;
            }
            $('#edit_user_id').val(data.id);
            $('#edit_name').val(data.name);
            $('#edit_email').val(data.email);
            
            if (data.trial_ends_at) {
                const dateParts = data.trial_ends_at.split('-');
                if (dateParts.length === 3) {
                    const formattedDate = `${dateParts[2]}.${dateParts[1]}.${dateParts[0]}`;
                    $('#edit_trial_ends_at').val(formattedDate);
                    if (document.getElementById('edit_trial_ends_at')._flatpickr) {
                        document.getElementById('edit_trial_ends_at')._flatpickr.setDate(formattedDate);
                    }
                }
            } else {
                $('#edit_trial_ends_at').val('');
                if (document.getElementById('edit_trial_ends_at')._flatpickr) {
                    document.getElementById('edit_trial_ends_at')._flatpickr.clear();
                }
            }
            
            // Custom select update for role
            const roleName = data.role.charAt(0).toUpperCase() + data.role.slice(1);
            $('#select-role-edit input[type="hidden"]').val(data.role);
            $('#select-role-edit-trigger span.truncate').text(roleName);
            
            $('#edit_password').val('');
            document.getElementById('dialog-edit-user').showModal();
        } catch (err) {
            console.error("JSON Parse Error:", data);
            showToast({ category: 'error', title: 'Hata', description: 'Veri alınırken sunucu hatası oluştu.' });
        }
    });
}

function saveUser() {
    $.post('<?php echo routeUrl("kullanici-guncelle"); ?>', $('#form-edit-user').serialize(), function(response) {
        try {
            response = JSON.parse(response);
            if (response.success) {
                showToast({ category: 'success', title: 'Başarılı', description: response.message });
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.message });
            }
        } catch (err) {
            showToast({ category: 'error', title: 'Hata', description: 'Sunucudan geçersiz bir yanıt geldi.' });
        }
    });
}

function deleteUser(id) {
    userToDelete = id;
    document.getElementById('alert-dialog-delete').showModal();
}

function confirmDelete() {
    if (!userToDelete) return;
    
    $.post('<?php echo routeUrl("kullanici-sil"); ?>', { id: userToDelete }, function(response) {
        try {
            response = JSON.parse(response);
            if (response.success) {
                showToast({ category: 'success', title: 'Başarılı', description: response.message });
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.message });
            }
        } catch (err) {
            showToast({ category: 'error', title: 'Hata', description: 'Sunucudan geçersiz bir yanıt geldi.' });
        } finally {
            document.getElementById('alert-dialog-delete').close();
            userToDelete = null;
        }
    });
}
</script>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../bootstrap.php';
global $db;

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

// Fetch user role and check authorization
$tenant_id = $_SESSION['tenant_id'] ?? 0;
$uStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$uStmt->execute([$_SESSION['user_id']]);
$userRole = $uStmt->fetchColumn() ?: 'user';

// Admin or Superadmin role is required
if ($userRole !== 'superadmin' && $userRole !== 'admin') {
    echo '<div class="glass-card p-6 text-center text-rose-500 font-bold text-xs rounded-xl">Bu sayfaya erişim yetkiniz bulunmamaktadır.</div>';
    exit;
}

$isSuperAdmin = ($userRole === 'superadmin');
$isDeveloper = isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1;

// Fetch users
if ($isSuperAdmin) {
    // Superadmin sees all users
    $stmt = $db->prepare("
        SELECT u.*, t.name as tenant_name, uc.name as creator_name 
        FROM users u 
        LEFT JOIN tenants t ON u.tenant_id = t.id 
        LEFT JOIN users uc ON u.created_by = uc.id 
        ORDER BY u.name ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Normal admin sees users of their tenant
    $stmt = $db->prepare("
        SELECT u.*, t.name as tenant_name, uc.name as creator_name 
        FROM users u 
        LEFT JOIN tenants t ON u.tenant_id = t.id 
        LEFT JOIN users uc ON u.created_by = uc.id 
        WHERE u.tenant_id = ? 
        ORDER BY u.name ASC
    ");
    $stmt->execute([$tenant_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Map subscriptions and package details for each user (aligning with desktop logic)
$users = array_map(function($u) use ($db) {
    $stmt = $db->prepare("
        SELECT s.*, sp.name as plan_name 
        FROM subscriptions s 
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.tenant_id = ? 
        ORDER BY s.id DESC 
        LIMIT 1
    ");
    $stmt->execute([$u['tenant_id'] ?? null]);
    $u['subscription'] = $stmt->fetch(PDO::FETCH_ASSOC);
    return $u;
}, $users);
?>
<div class="space-y-4 animate-fade-in text-zinc-950 dark:text-zinc-50">
    <!-- Header -->
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <button onclick="goBackToOtherMenu()" class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <div>
                <h2 class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50">Kullanıcı Yönetimi</h2>
                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Toplam <span id="users-count"><?= count($users) ?></span> Kullanıcı</p>
            </div>
        </div>
        
        <button onclick="openAddUserSheet()" class="px-2.5 py-1.5 bg-zinc-900 dark:bg-zinc-50 text-white dark:text-zinc-950 rounded-lg text-[9px] font-bold uppercase tracking-wider flex items-center gap-1 active:scale-95 transition-all cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Yeni Kullanıcı
        </button>
    </div>

    <!-- Superadmin Pill Selector Tabs (All vs Mine) -->
    <?php if ($isSuperAdmin): ?>
        <div class="flex bg-zinc-100 dark:bg-zinc-900/60 p-1 rounded-xl">
            <button id="btn-users-mine" onclick="switchUsersView('mine')" class="flex-1 py-2 text-center text-[10px] font-extrabold uppercase tracking-wider rounded-lg transition-all cursor-pointer bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm">
                Benim Kullanıcılarım
            </button>
            <button id="btn-users-all" onclick="switchUsersView('all')" class="flex-1 py-2 text-center text-[10px] font-extrabold uppercase tracking-wider rounded-lg transition-all cursor-pointer text-zinc-500 dark:text-zinc-400">
                Tüm Kullanıcılar
            </button>
        </div>
    <?php endif; ?>

    <!-- Search Input Bar -->
    <div class="relative w-full">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
        <input type="text" id="userSearchMobile" onkeyup="filterUsers()" class="block w-full pl-9 pr-3 py-2.5 mobile-input bg-white/50 dark:bg-zinc-900/60 text-xs placeholder-zinc-500 transition-all font-semibold" placeholder="Kullanıcı adı veya e-posta ara...">
    </div>

    <!-- Users Cards Container -->
    <div id="users-cards-list" class="space-y-3">
        <?php foreach ($users as $u): 
            $isMine = ($u['tenant_id'] == $tenant_id) ? 'true' : 'false';
            ?>
            <div class="user-card glass-card p-4 rounded-xl flex flex-col gap-3 transition-all duration-200" 
                 data-id="<?= $u['id'] ?>" 
                 data-name="<?= htmlspecialchars($u['name']) ?>" 
                 data-email="<?= htmlspecialchars($u['email']) ?>" 
                 data-role="<?= htmlspecialchars($u['role']) ?>" 
                 data-trial="<?= !empty($u['trial_ends_at']) ? date('d.m.Y', strtotime($u['trial_ends_at'])) : '' ?>" 
                 data-mine="<?= $isMine ?>">
                
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <!-- Initials Circle Avatar -->
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-black border border-indigo-100 dark:border-indigo-800/80">
                            <?php 
                            $uParts = preg_split('/\s+/', trim($u['name']));
                            echo htmlspecialchars(strtoupper(mb_substr($uParts[0] ?? 'U', 0, 1) . mb_substr(end($uParts) ?: '', 0, 1)), ENT_QUOTES, 'UTF-8');
                            ?>
                        </span>
                        <div>
                            <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 leading-tight"><?= htmlspecialchars($u['name']) ?></h4>
                            <p class="text-[9px] text-zinc-400 font-semibold mt-0.5"><?= htmlspecialchars($u['email']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Role Badge -->
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[8px] font-black border uppercase tracking-wider <?php 
                        echo $u['role'] === 'superadmin' ? 'border-purple-200 bg-purple-500/5 text-purple-700 dark:border-purple-900/30 dark:text-purple-400' : 
                            ($u['role'] === 'admin' ? 'border-blue-200 bg-blue-500/5 text-blue-700 dark:border-blue-900/30 dark:text-blue-400' : 
                            'border-zinc-200 bg-zinc-500/5 text-zinc-650 dark:border-zinc-800 dark:text-zinc-400'); 
                    ?>">
                        <?= htmlspecialchars($u['role']) ?>
                    </span>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-2 text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold border-t border-zinc-100/70 dark:border-zinc-800/40 pt-2.5 mt-0.5 leading-relaxed">
                    <div>
                        <span class="text-zinc-450 dark:text-zinc-500 block text-[7px] uppercase tracking-wider">Kurum</span>
                        <span class="font-bold text-zinc-850 dark:text-zinc-200 truncate block"><?= htmlspecialchars($u['tenant_name'] ?? 'Genel Yönetim') ?></span>
                    </div>
                    <div>
                        <span class="text-zinc-450 dark:text-zinc-500 block text-[7px] uppercase tracking-wider">Ekleyen</span>
                        <span class="font-bold text-zinc-850 dark:text-zinc-200 truncate block"><?= htmlspecialchars($u['creator_name'] ?? '-') ?></span>
                    </div>
                    <div>
                        <span class="text-zinc-450 dark:text-zinc-500 block text-[7px] uppercase tracking-wider">Oluşturulma</span>
                        <span class="font-bold text-zinc-850 dark:text-zinc-200"><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></span>
                    </div>
                    <div>
                        <span class="text-zinc-450 dark:text-zinc-500 block text-[7px] uppercase tracking-wider">Abonelik / Deneme</span>
                        <span class="font-bold block mt-0.5">
                            <?php if (!empty($u['subscription'])): ?>
                                <span class="text-indigo-500 dark:text-indigo-400 font-bold"><?= htmlspecialchars($u['subscription']['plan_name']) ?></span>
                                <?php if (strtotime($u['subscription']['end_date']) < strtotime('today')): ?>
                                    <span class="text-rose-500 text-[8px] font-black block">(Süresi Doldu)</span>
                                <?php else: ?>
                                    <span class="text-emerald-500 text-[8px] font-black block">(Aktif)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php 
                                $trialEnds = !empty($u['trial_ends_at']) ? strtotime($u['trial_ends_at']) : null;
                                $now = strtotime('today');
                                if ($trialEnds):
                                    $diffDays = round(($trialEnds - $now) / 86400);
                                    if ($diffDays > 0):
                                        ?>
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold"><?= $diffDays ?> Gün kaldı (Deneme)</span>
                                        <?php
                                    else:
                                        ?>
                                        <span class="text-rose-500 font-bold">Deneme Süresi Doldu</span>
                                        <?php
                                    endif;
                                else:
                                    ?>
                                    <span class="text-zinc-400">Deneme Belirsiz</span>
                                    <?php
                                endif;
                             endif;
                             ?>
                        </span>
                    </div>
                </div>

                <!-- Action Button Toolbar -->
                <?php 
                $canEdit = ($u['id'] != 1 || $_SESSION['user_id'] == 1);
                $canDelete = ($u['id'] != 1 && $u['id'] != $_SESSION['user_id']);
                ?>
                <?php if ($canEdit || $canDelete): ?>
                    <div class="flex justify-end gap-2 border-t border-zinc-100/70 dark:border-zinc-800/40 pt-2.5 mt-0.5">
                        <?php if ($canEdit): ?>
                            <button onclick="openEditUserSheet(<?= $u['id'] ?>)" class="px-2.5 py-1.5 bg-zinc-150 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-800 rounded-lg text-[9px] font-extrabold uppercase tracking-wider flex items-center gap-1 cursor-pointer active:scale-95 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                Düzenle
                            </button>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <button onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')" class="px-2.5 py-1.5 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-950/30 text-rose-600 dark:text-rose-450 hover:bg-rose-100 dark:hover:bg-rose-950/30 rounded-lg text-[9px] font-extrabold uppercase tracking-wider flex items-center gap-1 cursor-pointer active:scale-95 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                Sil
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ==============================================
     MOBILE USER BOTTOM SHEETS
     ============================================== -->

<!-- 1. ADD USER BOTTOM SHEET -->
<div id="sheet-add-user" class="mobile-users-sheet bottom-sheet flex flex-col max-h-[85%] text-zinc-950 dark:text-zinc-50">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">Yeni Kullanıcı Ekle</h3>
        <p class="text-xs text-zinc-500">Sisteme erişebilecek yeni bir kullanıcı oluşturun.</p>
        
        <form id="form-mobile-add-user" class="space-y-4" onsubmit="handleAddUser(event)">
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="add-user-name">Ad Soyad*</label>
                <input class="mobile-input" type="text" id="add-user-name" name="name" required placeholder="Kullanıcının tam adı" />
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="add-user-email">E-posta*</label>
                <input class="mobile-input" type="email" id="add-user-email" name="email" required placeholder="E-posta adresi" />
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="add-user-password">Şifre*</label>
                <input class="mobile-input" type="password" id="add-user-password" name="password" required placeholder="En az 6 karakter" />
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="add-user-role">Rol</label>
                <select class="mobile-input" id="add-user-role" name="role">
                    <option value="user" selected>User</option>
                    <option value="admin">Admin</option>
                    <?php if ($isDeveloper): ?>
                    <option value="superadmin">Superadmin</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <?php if ($isSuperAdmin): ?>
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="add-user-trial">Deneme Bitiş Tarihi</label>
                <input type="text" name="trial_ends_at" id="add-user-trial" class="mobile-input datepicker-input" value="<?php echo date('d.m.Y', strtotime('+1 month')); ?>" />
            </div>
            <?php endif; ?>
            
            <button type="submit" class="w-full py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
                Kullanıcıyı Oluştur
            </button>
        </form>
    </div>
</div>

<!-- 2. EDIT USER BOTTOM SHEET -->
<div id="sheet-edit-user" class="mobile-users-sheet bottom-sheet flex flex-col max-h-[85%] text-zinc-950 dark:text-zinc-50">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">Kullanıcı Düzenle</h3>
        <p class="text-xs text-zinc-500">Kullanıcı bilgilerini güncelleyin.</p>
        
        <form id="form-mobile-edit-user" class="space-y-4" onsubmit="handleEditUser(event)">
            <input type="hidden" id="edit-user-id" name="id" />
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="edit-user-name">Ad Soyad*</label>
                <input class="mobile-input font-semibold" type="text" id="edit-user-name" name="name" required />
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="edit-user-email">E-posta*</label>
                <input class="mobile-input font-semibold" type="email" id="edit-user-email" name="email" required />
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="edit-user-password">Şifre (Boş bırakılırsa değişmez)</label>
                <input class="mobile-input font-semibold" type="password" id="edit-user-password" name="password" placeholder="Yeni şifre (opsiyonel)" />
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="edit-user-role">Rol</label>
                <select class="mobile-input" id="edit-user-role" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <?php if ($isDeveloper): ?>
                    <option value="superadmin">Superadmin</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <?php if ($isSuperAdmin): ?>
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block" for="edit-user-trial">Deneme Bitiş Tarihi</label>
                <input type="text" name="trial_ends_at" id="edit-user-trial" class="mobile-input datepicker-input" />
            </div>
            <?php endif; ?>
            
            <button type="submit" class="w-full py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
                Değişiklikleri Kaydet
            </button>
        </form>
    </div>
</div>

<!-- 3. CONFIRM DELETE BOTTOM SHEET -->
<div id="sheet-delete-user" class="mobile-users-sheet bottom-sheet flex flex-col max-h-[75%] text-zinc-950 dark:text-zinc-50">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <h3 class="text-base font-extrabold text-rose-500 dark:text-rose-400">Emin misiniz?</h3>
        <p class="text-xs text-zinc-500">Bu işlem geri alınamaz. Bu kullanıcıyı sistemden kalıcı olarak sileceksiniz.</p>
        
        <div class="bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-150 dark:border-zinc-800 p-4 rounded-xl space-y-2 select-none">
            <div class="flex justify-between items-center text-xs">
                <span class="text-zinc-450 dark:text-zinc-500 font-medium">Kullanıcı:</span>
                <span id="delete-user-name-display" class="font-extrabold text-zinc-900 dark:text-zinc-100">-</span>
            </div>
        </div>
        
        <button id="btn-delete-user-submit" class="w-full py-3.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-2 cursor-pointer active:scale-95 transition-all shadow-sm">
            Silmeyi Tamamla
        </button>
    </div>
</div>

<!-- ==============================================
     INTERACTIVE JAVASCRIPT HANDLERS
     ============================================== -->
<script>
var activeUsersViewType = 'mine';
var userToDeleteId = null;

$(document).ready(function() {
    // Move bottom sheets to screen-content wrapper to avoid clipping and styling bugs
    $('.mobile-users-sheet').appendTo('.screen-content');
    
    // Initialize datepickers if flatpickr exists
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.datepicker-input', {
            dateFormat: 'd.m.Y',
            static: true
        });
    }

    // Initial load/filter state
    applyUsersFilters();
});

// Superadmin view type toggling
function switchUsersView(type) {
    activeUsersViewType = type;
    
    if (type === 'mine') {
        $('#btn-users-mine')
            .addClass('bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm')
            .removeClass('text-zinc-500 dark:text-zinc-400');
            
        $('#btn-users-all')
            .removeClass('bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm')
            .addClass('text-zinc-500 dark:text-zinc-400');
    } else {
        $('#btn-users-all')
            .addClass('bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm')
            .removeClass('text-zinc-500 dark:text-zinc-400');
            
        $('#btn-users-mine')
            .removeClass('bg-white dark:bg-zinc-850 text-zinc-950 dark:text-zinc-50 shadow-sm')
            .addClass('text-zinc-500 dark:text-zinc-400');
    }
    
    applyUsersFilters();
}

// Client-side search and view filters
function applyUsersFilters() {
    const searchVal = $('#userSearchMobile').val().toLowerCase().trim();
    let visibleCount = 0;
    
    $('.user-card').each(function() {
        const card = $(this);
        const name = card.attr('data-name').toLowerCase();
        const email = card.attr('data-email').toLowerCase();
        const isMine = card.attr('data-mine') === 'true';
        
        // 1. Filter by superadmin view type (all vs mine)
        let matchesView = true;
        <?php if ($isSuperAdmin): ?>
        if (activeUsersViewType === 'mine' && !isMine) {
            matchesView = false;
        }
        <?php endif; ?>
        
        // 2. Filter by search input query
        let matchesSearch = true;
        if (searchVal.length > 0) {
            if (!name.includes(searchVal) && !email.includes(searchVal)) {
                matchesSearch = false;
            }
        }
        
        if (matchesView && matchesSearch) {
            card.removeClass('hidden');
            visibleCount++;
        } else {
            card.addClass('hidden');
        }
    });
    
    // Update count display
    $('#users-count').text(visibleCount);
}

// keyup search trigger
function filterUsers() {
    applyUsersFilters();
}

// ----------------------------------------------
// CREATE: ADD USER ACTIONS
// ----------------------------------------------
function openAddUserSheet() {
    $('#form-mobile-add-user')[0].reset();
    openSheet('sheet-add-user');
}

function handleAddUser(e) {
    e.preventDefault();
    const btn = $('#form-mobile-add-user button[type="submit"]');
    const originalText = btn.text();
    btn.prop('disabled', true).text('Oluşturuluyor...');
    
    const basePath = '<?php echo appBasePath(); ?>';
    
    $.post(basePath + '/kullanici-ekle', $('#form-mobile-add-user').serialize(), function(response) {
        try {
            response = JSON.parse(response);
        } catch(err) {}
        
        closeAllSheets();
        
        if (response.success) {
            showToast('Kullanıcı başarıyla oluşturuldu.', 'success');
            setTimeout(() => {
                loadOtherSubpage('users');
            }, 1000);
        } else {
            showToast(response.message || 'Kullanıcı eklenirken bir hata oluştu.', 'error');
            btn.prop('disabled', false).text(originalText);
        }
    }).fail(function() {
        closeAllSheets();
        showToast('Sunucu ile iletişim kurulamadı.', 'error');
        btn.prop('disabled', false).text(originalText);
    });
}

// ----------------------------------------------
// UPDATE: EDIT USER ACTIONS
// ----------------------------------------------
function openEditUserSheet(id) {
    const basePath = '<?php echo appBasePath(); ?>';
    
    $.get(basePath + '/kullanici-get', { id: id }, function(data) {
        try {
            data = JSON.parse(data);
        } catch(err) {}
        
        if (data.success === false) {
            showToast(data.message || 'Kullanıcı bilgileri alınamadı.', 'error');
            return;
        }
        
        $('#edit-user-id').val(data.id);
        $('#edit-user-name').val(data.name);
        $('#edit-user-email').val(data.email);
        $('#edit-user-password').val('');
        $('#edit-user-role').val(data.role);
        
        if (data.trial_ends_at) {
            const dateParts = data.trial_ends_at.split('-');
            if (dateParts.length === 3) {
                const formattedDate = `${dateParts[2]}.${dateParts[1]}.${dateParts[0]}`;
                $('#edit-user-trial').val(formattedDate);
                if (document.getElementById('edit-user-trial')._flatpickr) {
                    document.getElementById('edit-user-trial')._flatpickr.setDate(formattedDate);
                }
            }
        } else {
            $('#edit-user-trial').val('');
            if (document.getElementById('edit-user-trial')._flatpickr) {
                document.getElementById('edit-user-trial')._flatpickr.clear();
            }
        }
        
        openSheet('sheet-edit-user');
    }).fail(function() {
        showToast('Kullanıcı bilgileri alınamadı.', 'error');
    });
}

function handleEditUser(e) {
    e.preventDefault();
    const btn = $('#form-mobile-edit-user button[type="submit"]');
    const originalText = btn.text();
    btn.prop('disabled', true).text('Kaydediliyor...');
    
    const basePath = '<?php echo appBasePath(); ?>';
    
    $.post(basePath + '/kullanici-guncelle', $('#form-mobile-edit-user').serialize(), function(response) {
        try {
            response = JSON.parse(response);
        } catch(err) {}
        
        closeAllSheets();
        
        if (response.success) {
            showToast('Kullanıcı bilgileri başarıyla güncellendi.', 'success');
            setTimeout(() => {
                loadOtherSubpage('users');
            }, 1000);
        } else {
            showToast(response.message || 'Kullanıcı güncellenirken bir hata oluştu.', 'error');
            btn.prop('disabled', false).text(originalText);
        }
    }).fail(function() {
        closeAllSheets();
        showToast('Sunucu ile iletişim kurulamadı.', 'error');
        btn.prop('disabled', false).text(originalText);
    });
}

// ----------------------------------------------
// DELETE: DELETE USER ACTIONS
// ----------------------------------------------
function confirmDeleteUser(id, name) {
    userToDeleteId = id;
    $('#delete-user-name-display').text(name);
    
    $('#btn-delete-user-submit').off('click').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('Siliniyor...');
        
        const basePath = '<?php echo appBasePath(); ?>';
        
        $.post(basePath + '/kullanici-sil', { id: userToDeleteId }, function(response) {
            try {
                response = JSON.parse(response);
            } catch(err) {}
            
            closeAllSheets();
            
            if (response.success) {
                showToast('Kullanıcı başarıyla silindi.', 'success');
                setTimeout(() => {
                    loadOtherSubpage('users');
                }, 1000);
            } else {
                showToast(response.message || 'Kullanıcı silinemedi.', 'error');
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            closeAllSheets();
            showToast('Sunucu bağlantı hatası.', 'error');
            btn.prop('disabled', false).text(originalText);
        });
    });
    
    openSheet('sheet-delete-user');
}
</script>

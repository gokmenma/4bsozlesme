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

// Fetch user role
$uStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$uStmt->execute([$_SESSION['user_id']]);
$userRole = $uStmt->fetchColumn() ?: 'user';
?>
<div class="space-y-6 animate-fade-in">
    <div>
        <h2 class="text-lg font-extrabold text-zinc-950 dark:text-zinc-50">Diğer İşlemler</h2>
        <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider">Yönetici Paneli & Sistem Araçları</p>
    </div>

    <!-- Actions Grid -->
    <div class="grid grid-cols-1 gap-4">
        
        <?php if ($userRole === 'superadmin'): ?>
            <!-- Kurum Yönetimi (Superadmin Only) -->
            <button onclick="loadOtherSubpage('tenants')" class="glass-card p-4 rounded-xl flex items-center justify-between cursor-pointer hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50 transition-all text-left">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-900 dark:text-zinc-100 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M8 10h.01M16 10h.01M12 6h.01M12 10h.01M8 14h.01M16 14h.01M12 14h.01"/></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Kurum Yönetimi</h4>
                        <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5">Tüm kurum tanımları ve limitleri yönetimi</p>
                    </div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        <?php endif; ?>

        <!-- Kullanıcı Listesi -->
        <button onclick="loadOtherSubpage('users')" class="glass-card p-4 rounded-xl flex items-center justify-between cursor-pointer hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50 transition-all text-left">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-900 dark:text-zinc-100 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Kullanıcı Yönetimi</h4>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5">Sistem kullanıcıları ve rol yetkilendirmesi</p>
                </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        </button>

        <!-- Abonelik & Paketler -->
        <button onclick="loadOtherSubpage('subscription')" class="glass-card p-4 rounded-xl flex items-center justify-between cursor-pointer hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50 transition-all text-left">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-900 dark:text-zinc-100 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Abonelik & Paketler</h4>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5">Abonelik planları ve lisans durumu</p>
                </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        </button>

        <!-- Sözleşme Taslakları -->
        <button onclick="loadOtherSubpage('template')" class="glass-card p-4 rounded-xl flex items-center justify-between cursor-pointer hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50 transition-all text-left">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-900 dark:text-zinc-100 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Sözleşme Taslakları</h4>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5">Kurumsal 4B sözleşme şablonu düzenleyicisi</p>
                </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        </button>

        <!-- Sistem Ayarları -->
        <button onclick="loadOtherSubpage('settings')" class="glass-card p-4 rounded-xl flex items-center justify-between cursor-pointer hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50 transition-all text-left">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-900 dark:text-zinc-100 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Sistem Ayarları</h4>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold mt-0.5">Kurum başlıkları, imzacı yetkililer ve genel ayarlar</p>
                </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        </button>

    </div>
</div>

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

$tenant_id = $_SESSION['tenant_id'] ?? 0;

// Fetch tenant settings
$stmt = $db->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
    $settings = [
        'kadro_bildirim_aktif' => 1,
        'sms_active' => 0,
        'sms_api_url' => '',
        'sms_api_key' => '',
        'sms_sender' => '',
        'sms_entegrator' => 'NETGSM'
    ];
}
?>
<div class="space-y-4 animate-fade-in">
    <div class="flex items-center gap-2">
        <button onclick="goBackToOtherMenu()" class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div>
            <h2 class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50">Sistem Ayarları</h2>
            <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Kurum & Bildirim Ayarları</p>
        </div>
    </div>

    <!-- Settings Form -->
    <form id="mobileSettingsForm" class="space-y-4">
        <div class="glass-card p-4 rounded-xl space-y-4">
            
            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div>
                    <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Kadro Geçiş Bildirimi</h4>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold">3 yılını dolduranlar için uyarı verilsin</p>
                </div>
                <input type="checkbox" name="kadro_bildirim_aktif" value="1" class="w-4 h-4 rounded text-zinc-950 accent-zinc-950 cursor-pointer" <?= $settings['kadro_bildirim_aktif'] ? 'checked' : '' ?>>
            </div>

            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div>
                    <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">SMS Bildirimleri</h4>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold">Otomatik SMS gönderimini etkinleştir</p>
                </div>
                <input type="checkbox" name="sms_active" value="1" class="w-4 h-4 rounded text-zinc-950 accent-zinc-950 cursor-pointer" <?= $settings['sms_active'] ? 'checked' : '' ?>>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">SMS Entegratör</label>
                <select name="sms_entegrator" class="mobile-input">
                    <option value="NETGSM" <?= ($settings['sms_entegrator'] === 'NETGSM') ? 'selected' : '' ?>>NETGSM</option>
                    <option value="VATANSMS" <?= ($settings['sms_entegrator'] === 'VATANSMS') ? 'selected' : '' ?>>VATANSMS</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">SMS API URL</label>
                <input type="text" name="sms_api_url" class="mobile-input" value="<?= htmlspecialchars($settings['sms_api_url']) ?>" placeholder="https://api.netgsm.com.tr/...">
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">SMS API Anahtarı / Şifre</label>
                <input type="password" name="sms_api_key" class="mobile-input" value="<?= htmlspecialchars($settings['sms_api_key']) ?>" placeholder="******">
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">SMS Gönderici Başlığı (Header)</label>
                <input type="text" name="sms_sender" class="mobile-input" value="<?= htmlspecialchars($settings['sms_sender']) ?>" placeholder="NETGSM">
            </div>
        </div>

        <button type="submit" class="w-full py-3.5 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-900 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 active:scale-98 transition-all rounded-lg font-bold shadow-md flex items-center justify-center gap-2 cursor-pointer">
            Ayarları Kaydet
        </button>
    </form>
</div>

<script>
if (typeof initMobileCustomSelects === 'function') {
    setTimeout(initMobileCustomSelects, 50);
}

document.getElementById('mobileSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const basePath = '<?php echo appBasePath(); ?>';
    
    fetch(basePath + '/ayarlar-kaydet', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Sistem ayarları başarıyla güncellendi.');
            setTimeout(() => {
                loadOtherSubpage('settings');
            }, 1000);
        } else {
            showToast(data.message || 'Kaydetme sırasında bir hata oluştu.', 'error');
        }
    })
    .catch(err => {
        showToast('Sunucu bağlantı hatası.', 'error');
    });
});
</script>

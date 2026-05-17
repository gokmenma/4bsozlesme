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
$defModel = new Definition();
$settings = $defModel->getSettings($tenant_id);

if (!function_exists('getVal')) {
    function getVal($key, $settings) {
        return htmlspecialchars($settings[$key] ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
<div class="space-y-4 animate-fade-in text-zinc-950 dark:text-zinc-50">
    <!-- Header -->
    <div class="flex items-center gap-2">
        <button onclick="goBackToOtherMenu()" class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div>
            <h2 class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50">Kurum Tanımlamaları</h2>
            <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Kurum, Yetkili & Katsayı Ayarları</p>
        </div>
    </div>

    <form id="mobileTanimlamalarForm" class="space-y-4">
        <!-- 1. Ücret Dönemi Ayarları -->
        <div class="glass-card p-4 rounded-xl space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Ücret Dönemi Ayarları</h3>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold">Aktif hesaplama ve sözleşme dönemi</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Varsayılan Ücret Dönemi</label>
                <?php
                $stmt_periods = $db->prepare("SELECT DISTINCT donem FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? ORDER BY donem DESC");
                $stmt_periods->execute([$tenant_id]);
                $db_periods = $stmt_periods->fetchAll(PDO::FETCH_COLUMN);

                $periods = array_unique(array_merge($db_periods, ['2026-1']));
                sort($periods);

                $default_wage_period = $settings['default_wage_period'] ?? '2026-1';
                ?>
                <select id="mobile-default-wage-period" name="default_wage_period" class="mobile-input">
                    <?php foreach ($periods as $p): ?>
                        <option value="<?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $p === $default_wage_period ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?> Dönemi
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[8px] text-zinc-500 font-bold leading-normal mt-1">
                    * Bu seçim, tüm personellerin listelerinde ve döner matrah hesaplamalarında temel alınacak ücret tablosunu belirler.
                </p>
            </div>
        </div>

        <!-- 2. Kurum Bilgileri -->
        <div class="glass-card p-4 rounded-xl space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/></svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Kurum Bilgileri</h3>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold">Sözleşmede yer alacak resmi kurum bilgileri</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Kurum Adı</label>
                <input type="text" name="kurum_adi" class="mobile-input" value="<?php echo getVal('kurum_adi', $settings); ?>" placeholder="Örn: ABC Teknolojileri A.Ş.">
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Birim Adı</label>
                <input type="text" name="birim_adi" class="mobile-input" value="<?php echo getVal('birim_adi', $settings); ?>" placeholder="Örn: İdari İşler Dairesi">
            </div>
        </div>

        <!-- 3. Yetkili Bilgileri -->
        <div class="glass-card p-4 rounded-xl space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><circle cx="19" cy="11" r="3"/></svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Yetkili Bilgileri</h3>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold">Sözleşmeyi onaylayan idareci bilgileri</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Ad Soyad</label>
                <input type="text" name="yetkili_ad_soyad" class="mobile-input" value="<?php echo getVal('yetkili_ad_soyad', $settings); ?>" placeholder="Ahmet Yılmaz">
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Ünvan</label>
                <input type="text" name="yetkili_unvan" class="mobile-input" value="<?php echo getVal('yetkili_unvan', $settings); ?>" placeholder="Rektör V.">
            </div>
        </div>

        <!-- 4. Katsayı Bilgileri -->
        <div class="glass-card p-4 rounded-xl space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Katsayı Bilgileri</h3>
                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-semibold">Matrah & döner sermaye katsayıları</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Maaş Katsayısı</label>
                <input type="number" step="0.000001" name="maas_katsayisi" class="mobile-input" value="<?php echo getVal('maas_katsayisi', $settings); ?>" placeholder="0.907796">
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Yan Ödeme Katsayısı</label>
                <input type="number" step="0.000001" name="yan_odeme_katsayisi" class="mobile-input" value="<?php echo getVal('yan_odeme_katsayisi', $settings); ?>" placeholder="0.287912">
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" id="btn-mobile-save-definitions" class="w-full py-3.5 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-900 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 active:scale-98 transition-all rounded-lg font-bold shadow-md flex items-center justify-center gap-2 cursor-pointer">
            Tanımlamaları Kaydet
        </button>
    </form>
</div>

<script>
if (typeof initMobileCustomSelects === 'function') {
    setTimeout(initMobileCustomSelects, 50);
}

document.getElementById('mobileTanimlamalarForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-mobile-save-definitions');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin mr-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Kaydediliyor...`;

    const formData = new FormData(this);
    const basePath = '<?php echo appBasePath(); ?>';
    
    fetch(basePath + '/tanimlamalar', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Tanımlamalar başarıyla güncellendi.');
            setTimeout(() => {
                loadOtherSubpage('tanimlamalar');
            }, 1000);
        } else {
            showToast(data.message || 'Kaydetme sırasında bir hata oluştu.', 'error');
        }
    })
    .catch(err => {
        showToast('Sunucu bağlantı hatası.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});
</script>

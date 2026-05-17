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

// Fetch template
$stmt = $db->prepare("SELECT * FROM contract_templates WHERE tenant_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$tenant_id]);
$template = $stmt->fetch() ?: [
    'name' => 'Varsayılan 4B Sözleşme Şablonu',
    'content' => '',
    'has_border' => 0
];
?>
<div class="space-y-4 animate-fade-in">
    <div class="flex items-center gap-2">
        <button onclick="goBackToOtherMenu()" class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div>
            <h2 class="text-sm font-extrabold text-zinc-950 dark:text-zinc-50">Sözleşme Taslağı</h2>
            <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Şablon Düzenleyici</p>
        </div>
    </div>

    <!-- Template Form -->
    <form id="mobileTemplateForm" class="space-y-4">
        <div class="glass-card p-4 rounded-xl space-y-4">
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Şablon Adı</label>
                <input type="text" name="name" class="mobile-input" value="<?= htmlspecialchars($template['name']) ?>" required>
            </div>
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Sözleşme İçeriği (HTML Destekli)</label>
                <textarea name="content" class="mobile-input min-h-[250px] font-mono text-xs leading-relaxed" placeholder="Sözleşme metnini buraya yazın..."><?= htmlspecialchars($template['content']) ?></textarea>
            </div>

            <div class="flex items-center justify-between bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-lg border border-zinc-200/50 dark:border-zinc-800/80">
                <span class="text-[10px] font-bold text-zinc-700 dark:text-zinc-300">Kenarlık Çerçevesi Olsun</span>
                <input type="checkbox" name="has_border" value="1" class="w-4 h-4 rounded text-zinc-950 accent-zinc-950 cursor-pointer" <?= $template['has_border'] ? 'checked' : '' ?>>
            </div>
        </div>

        <button type="submit" class="w-full py-3.5 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-900 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 active:scale-98 transition-all rounded-lg font-bold shadow-md flex items-center justify-center gap-2 cursor-pointer">
            Taslağı Kaydet
        </button>
    </form>
</div>

<script>
document.getElementById('mobileTemplateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const basePath = '<?php echo appBasePath(); ?>';
    
    fetch(basePath + '/sozlesme-taslagi-kaydet', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Sözleşme şablonu başarıyla güncellendi.');
            setTimeout(() => {
                loadOtherSubpage('template');
            }, 1000);
        } else {
            showToast(data.error || 'Kaydetme sırasında bir hata oluştu.', 'error');
        }
    })
    .catch(err => {
        showToast('Sunucu bağlantı hatası.', 'error');
    });
});
</script>

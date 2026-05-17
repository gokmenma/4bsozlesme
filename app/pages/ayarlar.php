<?php 
$pageTitle = "Kurum Ayarları"; 
$pageSubtitle = "Kurumunuz için bildirim ve SMS API ayarlarını bu sayfadan yönetebilirsiniz.";

// Helper function to safely read settings array
if (!function_exists('getVal')) {
    function getVal($key, $settings) {
        return htmlspecialchars($settings[$key] ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>

<style>
#sms_entegrator.custom-select-component { display: block !important; width: 100% !important; }
#sms_entegrator-trigger { display: flex !important; width: 100% !important; height: 40px !important; }
#sms_entegrator-popover { width: 100% !important; }
</style>

<div class="flex flex-col gap-8 max-w-4xl mx-auto">
  <!-- Header -->
  <div class="flex flex-col gap-1">
    <h1 class="text-2xl font-bold tracking-tight text-foreground"><?php echo $pageTitle; ?></h1>
    <p class="text-muted-foreground"><?php echo $pageSubtitle; ?></p>
  </div>

  <form id="form-settings" class="flex flex-col gap-8">
    <!-- Kadro Bildirim Ayarları Section -->
    <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
      <div class="p-6 border-b border-border bg-muted/30">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold leading-none tracking-tight">Kadro Bildirim Ayarları</h2>
            <p class="text-sm text-muted-foreground mt-1">Personellerin kadroya geçiş sürecinde otomatik e-posta gönderimi.</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="space-y-1">
            <p class="text-sm font-medium">E-posta Bildirimlerini Aç/Kapat</p>
            <p class="text-xs text-muted-foreground">Her gün saat 08:00'da kadroya geçecekleri (göreve başlama tarihinden tam 3 yıl geçmiş personelleri) ilgili tenant kullanıcılarına mail yoluyla gönderir.</p>
          </div>
          <input type="checkbox" name="kadro_bildirim_aktif" id="kadro_bildirim_aktif" value="1" role="switch" class="input" <?php echo ((int)($settings['kadro_bildirim_aktif'] ?? 1) === 1) ? 'checked' : ''; ?>>
        </div>
      </div>
    </div>

    <!-- SMS API Ayarları Section -->
    <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
      <div class="p-6 border-b border-border bg-muted/30">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold leading-none tracking-tight">SMS API Ayarları</h2>
            <p class="text-sm text-muted-foreground mt-1">Personellere SMS göndermek için API entegrasyonu bilgilerini girin.</p>
          </div>
        </div>
      </div>
      <div class="p-6 flex flex-col gap-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-border">
          <div class="space-y-1">
            <p class="text-sm font-medium">SMS Hizmetini Aktifleştir</p>
            <p class="text-xs text-muted-foreground">Eğer SMS API entegrasyonunu aktifleştirirseniz sistem üzerinden personellere otomatik veya manuel SMS gönderebilirsiniz.</p>
          </div>
          <input type="checkbox" name="sms_active" id="sms_active" value="1" role="switch" class="input" <?php echo ((int)($settings['sms_active'] ?? 0) === 1) ? 'checked' : ''; ?>>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          <div class="space-y-2">
            <label for="sms_entegrator" class="text-sm font-medium leading-none">SMS Entegratörü</label>
            <?php 
            $integratorOptions = [
              ['value' => 'NETGSM', 'label' => 'NETGSM'],
              ['value' => 'MUTLUCELL', 'label' => 'MUTLUCELL']
            ];
            echo renderCustomSelect(
              'sms_entegrator', 
              'sms_entegrator', 
              $integratorOptions, 
              getVal('sms_entegrator', $settings), 
              'w-full'
            );
            ?>
          </div>

          <div class="space-y-2">
            <label for="sms_sender" class="text-sm font-medium leading-none">SMS Gönderici Başlığı (Originator / Header)</label>
            <input type="text" id="sms_sender" name="sms_sender" value="<?php echo getVal('sms_sender', $settings); ?>" placeholder="Örn: KURUM-ADI" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>

          <div class="space-y-2">
            <label for="sms_api_url" class="text-sm font-medium leading-none">SMS API URL</label>
            <input type="url" id="sms_api_url" name="sms_api_url" value="<?php echo getVal('sms_api_url', $settings); ?>" placeholder="https://api.sms-servisi.com/send" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>
          
          <div class="space-y-2">
            <label for="sms_api_key" class="text-sm font-medium leading-none">SMS API Key / Token</label>
            <input type="text" id="sms_api_key" name="sms_api_key" value="<?php echo getVal('sms_api_key', $settings); ?>" placeholder="API Anahtarınız veya Token" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Section -->
    <div class="flex justify-end">
      <button type="submit" class="btn-save inline-flex h-11 items-center justify-center rounded-xl bg-zinc-900 dark:bg-white dark:text-zinc-900 px-8 text-sm font-semibold text-white shadow-lg hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all">
        Ayarları Kaydet
      </button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-settings');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = form.querySelector('.btn-save');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin mr-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Kaydediliyor...`;

        const formData = new FormData(form);
        
        fetch('ayarlar-kaydet', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showToast({
                    category: 'success',
                    title: 'Başarılı',
                    description: data.message || 'Ayarlar başarıyla kaydedildi.'
                });
            } else {
                window.showToast({
                    category: 'error',
                    title: 'Hata',
                    description: data.message || 'Ayarlar kaydedilirken bir hata oluştu.'
                });
            }
        })
        .catch(error => {
            window.showToast({
                category: 'error',
                title: 'Hata',
                description: 'Sunucuyla bağlantı kurulamadı.'
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
});
</script>

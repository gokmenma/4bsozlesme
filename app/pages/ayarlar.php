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
    <!-- SMTP E-posta Ayarları Section -->
    <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
      <div class="p-6 border-b border-border bg-muted/30">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold leading-none tracking-tight">SMTP E-posta Ayarları</h2>
            <p class="text-sm text-muted-foreground mt-1">Sistemden gönderilecek e-postalar için kendi SMTP mail sunucusu bilgilerinizi girin.</p>
          </div>
        </div>
      </div>
      <div class="p-6 flex flex-col gap-6">
        <div class="grid gap-6 md:grid-cols-2">
          <div class="space-y-2">
            <label for="smtp_host" class="text-sm font-medium leading-none">SMTP Sunucusu (Host)</label>
            <input type="text" id="smtp_host" name="smtp_host" value="<?php echo getVal('smtp_host', $settings); ?>" placeholder="Örn: smtp.yandex.com" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>

          <div class="space-y-2">
            <label for="smtp_port" class="text-sm font-medium leading-none">SMTP Portu</label>
            <input type="number" id="smtp_port" name="smtp_port" value="<?php echo getVal('smtp_port', $settings); ?>" placeholder="Örn: 587 veya 465" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>

          <div class="space-y-2">
            <label for="smtp_user" class="text-sm font-medium leading-none">E-posta Kullanıcı Adı (Username)</label>
            <input type="text" id="smtp_user" name="smtp_user" value="<?php echo getVal('smtp_user', $settings); ?>" placeholder="Kullanıcı adı veya e-posta adresiniz" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>

          <div class="space-y-2">
            <label for="smtp_pass" class="text-sm font-medium leading-none">E-posta Şifresi (Password)</label>
            <input type="password" id="smtp_pass" name="smtp_pass" value="<?php echo getVal('smtp_pass', $settings); ?>" placeholder="Şifreniz" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>

          <div class="space-y-2">
            <label for="smtp_from_email" class="text-sm font-medium leading-none">Gönderici E-posta Adresi (From Email)</label>
            <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo getVal('smtp_from_email', $settings); ?>" placeholder="gonderici@alanadiniz.com" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>

          <div class="space-y-2">
            <label for="smtp_from_name" class="text-sm font-medium leading-none">Gönderici Adı (From Name)</label>
            <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo getVal('smtp_from_name', $settings); ?>" placeholder="Örn: İnsan Kaynakları" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>
        </div>

        <!-- Test E-posta Gönderme Alanı -->
        <div class="flex items-end gap-3 mt-4 border-t border-border pt-6">
          <div class="space-y-2 flex-1 max-w-sm">
            <label for="test_email" class="text-sm font-medium leading-none">Test Alıcı E-postası</label>
            <input type="email" id="test_email" placeholder="ornek@alanadi.com" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
          </div>
          <button type="button" onclick="sendTestMail()" id="btn-send-test-mail" class="inline-flex h-10 items-center justify-center rounded-md border border-zinc-200 dark:border-zinc-800 bg-white hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800 px-4 text-sm font-semibold text-zinc-900 dark:text-zinc-100 shadow-sm transition-all whitespace-nowrap cursor-pointer">
            Test Maili Gönder
          </button>
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

function sendTestMail() {
    const testEmailInput = document.getElementById('test_email');
    const toEmail = testEmailInput.value.trim();
    if (!toEmail) {
        window.showToast({
            category: 'error',
            title: 'Hata',
            description: 'Lütfen test alıcı e-posta adresini girin.'
        });
        return;
    }

    const btn = document.getElementById('btn-send-test-mail');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin mr-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Gönderiliyor...`;

    const form = document.getElementById('form-settings');
    const formData = new FormData(form);
    formData.append('test_email', toEmail);

    fetch('ayarlar-test-mail', {
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
                description: data.message
            });
        } else {
            window.showToast({
                category: 'error',
                title: 'Hata',
                description: data.message
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
}
</script>

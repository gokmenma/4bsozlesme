<?php 
$pageTitle = "Tanımlamalar"; 
$pageSubtitle = "Sözleşme taslaklarında kullanılacak kurum, yetkili ve katsayı bilgilerini bu sayfadan yönetebilirsiniz.";

// Input helper function (View içinde kalabilir veya ortak bir yere taşınabilir)
if (!function_exists('getVal')) {
    function getVal($key, $settings) {
        return htmlspecialchars($settings[$key] ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="flex flex-col gap-8 max-w-4xl mx-auto">
  <!-- Header -->
  <div class="flex flex-col gap-1">
    <h1 class="text-2xl font-bold tracking-tight text-foreground"><?php echo $pageTitle; ?></h1>
    <p class="text-muted-foreground"><?php echo $pageSubtitle; ?></p>
  </div>



  <form id="form-definitions" action="" method="POST" class="flex flex-col gap-8">
    
    <!-- Kurum Bilgileri Section -->
    <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
      <div class="p-6 border-b border-border bg-muted/30">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building-2"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold leading-none tracking-tight">Kurum Bilgileri</h2>
            <p class="text-sm text-muted-foreground mt-1">Sözleşme tarafı olan kurumun resmi bilgileri.</p>
          </div>
        </div>
      </div>
      <div class="p-6 grid gap-6 md:grid-cols-2">
        <div class="space-y-2 md:col-span-2">
          <label for="kurum_adi" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Kurum Adı</label>
          <input type="text" id="kurum_adi" name="kurum_adi" value="<?php echo getVal('kurum_adi', $settings); ?>" placeholder="Örn: ABC Teknolojileri A.Ş." class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
        </div>
        
        <div class="space-y-2 md:col-span-2">
          <label for="birim_adi" class="text-sm font-medium leading-none">Birim Adı</label>
          <input type="text" id="birim_adi" name="birim_adi" value="<?php echo getVal('birim_adi', $settings); ?>" placeholder="Personellerin görev yaptığı birim adı" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
        </div>
      </div>
    </div>

    <!-- Yetkili Bilgileri Section -->
    <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
      <div class="p-6 border-b border-border bg-muted/30">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-cog"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><circle cx="19" cy="11" r="3"/></svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold leading-none tracking-tight">Yetkili Bilgileri</h2>
            <p class="text-sm text-muted-foreground mt-1">Sözleşmeyi imzalayacak olan yetkili kişi bilgileri.</p>
          </div>
        </div>
      </div>
      <div class="p-6 grid gap-6 md:grid-cols-2">
        <div class="space-y-2">
          <label for="yetkili_ad_soyad" class="text-sm font-medium leading-none">Ad Soyad</label>
          <input type="text" id="yetkili_ad_soyad" name="yetkili_ad_soyad" value="<?php echo getVal('yetkili_ad_soyad', $settings); ?>" placeholder="Örn: Ahmet Yılmaz" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        </div>
        
        <div class="space-y-2">
          <label for="yetkili_unvan" class="text-sm font-medium leading-none">Ünvan</label>
          <input type="text" id="yetkili_unvan" name="yetkili_unvan" value="<?php echo getVal('yetkili_unvan', $settings); ?>" placeholder="Örn: Genel Müdür" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        </div>
      </div>
    </div>

    <!-- Katsayı Bilgileri Section -->
    <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
      <div class="p-6 border-b border-border bg-muted/30">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-percent"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold leading-none tracking-tight">Katsayı Bilgileri</h2>
            <p class="text-sm text-muted-foreground mt-1">Sözleşme hesaplamalarında kullanılacak genel katsayılar.</p>
          </div>
        </div>
      </div>
      <div class="p-6 grid gap-6 md:grid-cols-2">
        <div class="space-y-2">
          <label for="maas_katsayisi" class="text-sm font-medium leading-none">Maaş Katsayısı</label>
          <div class="relative">
            <input type="number" step="0.000001" id="maas_katsayisi" name="maas_katsayisi" value="<?php echo getVal('maas_katsayisi', $settings); ?>" placeholder="0.907796" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted-foreground text-xs uppercase font-bold tracking-wider">
              Ratio
            </div>
          </div>
          <p class="text-[10px] text-muted-foreground italic">Döner matrahı hesaplamalarında kullanılacak maaş katsayısı.</p>
        </div>

        <div class="space-y-2">
          <label for="yan_odeme_katsayisi" class="text-sm font-medium leading-none">Yan Ödeme Katsayısı</label>
          <div class="relative">
            <input type="number" step="0.000001" id="yan_odeme_katsayisi" name="yan_odeme_katsayisi" value="<?php echo getVal('yan_odeme_katsayisi', $settings); ?>" placeholder="0.287912" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted-foreground text-xs uppercase font-bold tracking-wider">
              Ratio
            </div>
          </div>
          <p class="text-[10px] text-muted-foreground italic">Döner matrahı hesaplamalarında kullanılacak yan ödeme katsayısı.</p>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="flex items-center justify-end gap-4">
      <button type="button" class="inline-flex h-10 items-center justify-center rounded-md border border-border bg-background px-6 py-2 text-sm font-medium hover:bg-muted transition-colors">
        İptal
      </button>
      <button type="submit" id="btn-save-definitions" class="inline-flex h-10 items-center justify-center rounded-md bg-zinc-900 dark:bg-white dark:text-zinc-900 px-8 py-2 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Değişiklikleri Kaydet
      </button>
    </div>

  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-definitions');
    const btn = document.getElementById('btn-save-definitions');

    if (form && btn) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin mr-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Kaydediliyor...`;

            const formData = new FormData(form);

            fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.showToast({
                        category: 'success',
                        title: 'Başarılı',
                        description: data.message || 'Değişiklikler başarıyla kaydedildi.'
                    });
                } else {
                    window.showToast({
                        category: 'error',
                        title: 'Hata',
                        description: data.message || 'Kaydetme sırasında bir hata oluştu.'
                    });
                }
            })
            .catch(error => {
                window.showToast({
                    category: 'error',
                    title: 'Hata',
                    description: 'Kaydetme sırasında bir hata oluştu.'
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
});
</script>

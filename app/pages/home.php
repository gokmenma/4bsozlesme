<?php 
$pageTitle = "Dashboard"; 
$pageSubtitle = "Sistem genelindeki performans ve istatistiklerin özeti.";

$currentUserTrialEnds = null;
if (isset($_SESSION['user_id'])) {
    global $db;
    $uStmt = $db->prepare("SELECT trial_ends_at FROM users WHERE id = ?");
    $uStmt->execute([$_SESSION['user_id']]);
    $uRow = $uStmt->fetch();
    $currentUserTrialEnds = $uRow['trial_ends_at'] ?? null;
}
$isTrialExpired = false;
if ($currentUserTrialEnds) {
    if (strtotime($currentUserTrialEnds) < strtotime('today')) {
        $isTrialExpired = true;
    }
}
?>

<?php if ($isTrialExpired): ?>
<div class="mb-6 p-4 rounded-xl border border-red-200/60 dark:border-red-900/40 bg-red-50/50 dark:bg-red-900/10 text-red-800 dark:text-red-200 shadow-sm flex items-center justify-between gap-4">
  <div class="flex items-center gap-3">
    <div class="size-9 rounded-lg bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
    </div>
    <div>
      <h4 class="text-sm font-semibold">Deneme Süreniz Sona Erdi</h4>
      <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">Sistemi tam yetkiyle kullanmaya devam etmek için lütfen aboneliğinizi yenileyin veya yöneticiyle iletişime geçin.</p>
    </div>
  </div>
  <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin'): ?>
  <a href="<?= routeUrl('kullanicilar') ?>" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-800/60 text-red-700 dark:text-red-200 border border-red-200 dark:border-red-800 text-xs font-medium transition-colors">
    Deneme Süresini Uzat
  </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Dashboard Header -->
<div class="flex items-center justify-between mb-8">
  <div>
    <h1 class="text-2xl font-bold tracking-tight text-foreground">Dashboard</h1>
    <p class="text-muted-foreground mt-1">Sistem genelindeki performans ve istatistiklerin özeti.</p>
  </div>
  <div class="flex items-center gap-2">
    <button class="inline-flex h-9 items-center justify-center rounded-md border border-border bg-background px-4 py-2 text-sm font-medium hover:bg-muted focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
      Rapor İndir
    </button>
  </div>
</div>

<!-- Stat Cards -->
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
  <!-- Card 1: Toplam Personel -->
  <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
    <div class="flex items-center justify-between space-y-0 pb-2">
      <h3 class="text-sm font-medium text-muted-foreground">Toplam Personel</h3>
      <div class="inline-flex items-center rounded-full border border-blue-500/20 bg-blue-500/10 px-2 py-0.5 text-xs font-semibold text-blue-600 transition-colors dark:text-blue-400">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Sistemde Kayıtlı
      </div>
    </div>
    <div class="flex flex-col gap-1">
      <div class="text-2xl font-bold"><?= $stats['total_personnel'] ?></div>
      <div class="mt-4 space-y-1">
        <p class="text-xs font-medium flex items-center justify-between">
          Tüm zamanların toplamı
        </p>
        <p class="text-xs text-muted-foreground">Aktif ve pasif tüm kayıtlar</p>
      </div>
    </div>
  </div>

  <!-- Card 2: Aktif Personel -->
  <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
    <div class="flex items-center justify-between space-y-0 pb-2">
      <h3 class="text-sm font-medium text-muted-foreground">Aktif Personeller</h3>
      <div class="inline-flex items-center rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-600 transition-colors dark:text-emerald-400">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Çalışan
      </div>
    </div>
    <div class="flex flex-col gap-1">
      <div class="text-2xl font-bold"><?= $stats['active_personnel'] ?></div>
      <div class="mt-4 space-y-1">
        <p class="text-xs font-medium flex items-center justify-between">
          Görevde olan personel
        </p>
        <p class="text-xs text-muted-foreground">Aktif sözleşme durumu</p>
      </div>
    </div>
  </div>

  <!-- Card 3: Bu Ay Eklenen -->
  <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
    <div class="flex items-center justify-between space-y-0 pb-2">
      <h3 class="text-sm font-medium text-muted-foreground">Bu Ay Eklenen</h3>
      <div class="inline-flex items-center rounded-full border border-orange-500/20 bg-orange-500/10 px-2 py-0.5 text-xs font-semibold text-orange-600 transition-colors dark:text-orange-400">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Yeni Kayıt
      </div>
    </div>
    <div class="flex flex-col gap-1">
      <div class="text-2xl font-bold"><?= $stats['new_personnel_this_month'] ?></div>
      <div class="mt-4 space-y-1">
        <p class="text-xs font-medium flex items-center justify-between">
          İçinde bulunduğumuz ay
        </p>
        <p class="text-xs text-muted-foreground">Kayıt tarihi baz alınmıştır</p>
      </div>
    </div>
  </div>

  <!-- Card 4: Ücret Tanımları -->
  <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
    <div class="flex items-center justify-between space-y-0 pb-2">
      <h3 class="text-sm font-medium text-muted-foreground">Ücret Tanımları</h3>
      <div class="inline-flex items-center rounded-full border border-purple-500/20 bg-purple-500/10 px-2 py-0.5 text-xs font-semibold text-purple-600 transition-colors dark:text-purple-400">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
        Ünvan/Kadro
      </div>
    </div>
    <div class="flex flex-col gap-1">
      <div class="text-2xl font-bold"><?= $stats['total_wages'] ?></div>
      <div class="mt-4 space-y-1">
        <p class="text-xs font-medium flex items-center justify-between">
          Farklı pozisyon türü
        </p>
        <p class="text-xs text-muted-foreground">Tanımlı maaş katsayıları</p>
      </div>
    </div>
  </div>
</div>

<!-- Recent Personnel & Chart Section -->
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7 mb-8 items-start">
  <!-- Visitors Chart Section -->
  <div class="lg:col-span-4 flex flex-col gap-4">
    <div class="rounded-xl border border-border bg-card shadow-sm h-full">
    <div class="flex flex-col space-y-1.5 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold leading-none tracking-tight">Kayıt İstatistikleri</h3>
          <p class="text-sm text-muted-foreground mt-1">Son dönem personel ekleme trendi</p>
        </div>
      </div>
    </div>
    <div class="p-6 pt-0">
      <div class="h-[300px] w-full">
        <canvas id="visitorsChart"></canvas>
      </div>
      </div>
    </div>
  </div>

  <div class="lg:col-span-3">
    <!-- Kadroya Geçişi Yaklaşanlar -->
    <div class="rounded-xl border border-border bg-card shadow-sm">
      <div class="flex flex-col space-y-1.5 p-6">
        <h3 class="text-base font-semibold leading-none tracking-tight">Kadroya Geçişi Yaklaşanlar</h3>
        <p class="text-sm text-muted-foreground mt-1">Görev süresi 3 yılı dolan son 5 kayıt.</p>
      </div>
      <div class="p-6 pt-0">
        <div class="space-y-4">
          <?php foreach ($eligible_personnel as $p): ?>
          <div class="flex items-center gap-4">
            <div class="h-9 w-9 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-bold">
              <?= mb_substr($p['ad_soyad'], 0, 1) ?>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium leading-none truncate"><?= htmlspecialchars($p['ad_soyad']) ?></p>
              <p class="text-xs text-muted-foreground truncate"><?= htmlspecialchars($p['unvan'] ?? 'Ünvan Belirtilmemiş') ?></p>
            </div>
            <div class="text-xs text-muted-foreground flex flex-col items-end gap-0.5">
              <?php 
                  $kadroTarihi = date('Y-m-d', strtotime($p['goreve_baslama_tarihi'] . ' + 3 years'));
              ?>
              <span class="text-green-600 dark:text-green-400 font-bold text-[11px] whitespace-nowrap">
                  <?= date('d.m.Y', strtotime($kadroTarihi)) ?>
              </span>
              <span class="text-[10px] opacity-60">Süre Doldu</span>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($eligible_personnel)): ?>
            <p class="text-sm text-muted-foreground text-center py-4">Kadroya geçişi dolan personel bulunmamaktadır.</p>
          <?php endif; ?>
        </div>
        <div class="mt-6">
          <a href="<?= routeUrl('personel-listesi') ?>?filter=kadro_dolmus" class="inline-flex w-full items-center justify-center rounded-md border border-border bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
            Tümünü Gör
          </a>
        </div>
      </div>
    </div>
  </div>
</div>


</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('visitorsChart').getContext('2d');
    
    // Create gradient
    const gradient1 = ctx.createLinearGradient(0, 0, 0, 300);
    gradient1.addColorStop(0, 'rgba(39, 39, 42, 0.2)');
    gradient1.addColorStop(1, 'rgba(39, 39, 42, 0)');

    const gradient2 = ctx.createLinearGradient(0, 0, 0, 300);
    gradient2.addColorStop(0, 'rgba(161, 161, 170, 0.1)');
    gradient2.addColorStop(1, 'rgba(161, 161, 170, 0)');

    const labels = <?= json_encode(array_column($chart_data, 'label')) ?>;
    const data1 = <?= json_encode(array_column($chart_data, 'value')) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Yeni Kayıt',
                    data: data1,
                    borderColor: '#27272a',
                    backgroundColor: gradient1,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: '#fff',
                    titleColor: '#000',
                    bodyColor: '#666',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: true,
                    usePointStyle: true,
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#a1a1aa',
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    display: false,
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
});
</script>

<?php 
$userOnboarded = 0;
if (isset($_SESSION['user_id'])) {
    global $db;
    $userStmt = $db->prepare("SELECT is_onboarded FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userOnboarded = (int)($userRow['is_onboarded'] ?? 0);
}
if ($userOnboarded === 0):
?>
<!-- Modal Onboarding Dialog -->
<dialog id="onboarding-dialog" class="dialog w-full sm:max-w-[500px]" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <!-- Step 1 -->
    <div id="onboarding-step-1" class="onboarding-step">
      <header class="mb-6">
        <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Sözleşme 4B'ye Hoş Geldiniz!</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Sistemi en verimli şekilde kullanabilmeniz için kısa bir tanıtım turu hazırladık.</p>
      </header>
      <div class="flex justify-end gap-3 mt-6">
        <button type="button" class="btn bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900" onclick="nextOnboardingStep(2)">Başlayalım</button>
      </div>
    </div>

    <!-- Step 2 -->
    <div id="onboarding-step-2" class="onboarding-step hidden">
      <header class="mb-6">
        <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Personel Yönetimi</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Personellerinizi hızlıca ekleyebilir, katsayılarını ve sözleşme durumlarını takip edebilirsiniz.</p>
      </header>
      <div class="flex justify-end gap-3 mt-6">
        <button type="button" class="btn-outline" onclick="nextOnboardingStep(1)">Geri</button>
        <button type="button" class="btn bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900" onclick="nextOnboardingStep(3)">İleri</button>
      </div>
    </div>

    <!-- Step 3 -->
    <div id="onboarding-step-3" class="onboarding-step hidden">
      <header class="mb-6">
        <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Sistem & Kurum Ayarları</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Kurum bildirimlerinizi ve SMS API entegrasyonlarınızı yöneterek personellere bilgi ulaştırabilirsiniz.</p>
      </header>
      <div class="flex justify-end gap-3 mt-6">
        <button type="button" class="btn-outline" onclick="nextOnboardingStep(2)">Geri</button>
        <button type="button" class="btn bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900" onclick="completeOnboarding()">Bitir ve Keşfet</button>
      </div>
    </div>
  </div>
</dialog>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dialog = document.getElementById('onboarding-dialog');
    if (dialog) dialog.showModal();
});

function nextOnboardingStep(step) {
    document.querySelectorAll('.onboarding-step').forEach(el => el.classList.add('hidden'));
    document.getElementById('onboarding-step-' + step).classList.remove('hidden');
}

function closeOnboarding() {
    const dialog = document.getElementById('onboarding-dialog');
    if (dialog) dialog.close();
}

function completeOnboarding() {
    fetch('onboarding-complete', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        closeOnboarding();
        if (window.showToast) {
            window.showToast({
                category: 'success',
                title: 'Başarılı',
                description: 'Tanıtım turu tamamlandı. İyi çalışmalar dileriz.'
            });
        }
    })
    .catch(err => {
        closeOnboarding();
    });
}
</script>
<?php endif; ?>

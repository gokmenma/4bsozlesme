<?php 
$pageTitle = 'Abonelik Yönetimi'; 
$pageSubtitle = 'Abonelik paketlerinizi ve ödeme geçmişinizi yönetin';
?>

<div class="p-6">
    <div class="flex items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Abonelik</h1>
        <?php if ($_SESSION['role'] === 'superadmin'): ?>
            <button onclick="document.getElementById('dialog-add-plan').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Yeni Paket Ekle
            </button>
        <?php endif; ?>
    </div>

    <!-- Tabs Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-800 mb-6">
        <nav class="flex gap-8" aria-label="Tabs">
            <button onclick="switchTab('plans')" id="tab-plans-btn" class="border-b-2 border-primary py-4 text-sm font-medium text-primary">
                Abonelik Paketleri
            </button>
            <button onclick="switchTab('history')" id="tab-history-btn" class="border-b-2 border-transparent py-4 text-sm font-medium text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">
                Satın Alma Geçmişi
            </button>
        </nav>
    </div>

    <!-- Tab: Plans -->
    <div id="tab-plans" class="tab-content">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($plans as $plan): ?>
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-8 flex flex-col shadow-sm hover:shadow-md transition-shadow relative">
                    <?php if ($_SESSION['role'] === 'superadmin'): ?>
                        <div class="absolute top-4 right-4 flex gap-2">
                            <button onclick="editPlan(<?php echo $plan['id']; ?>)" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-zinc-400 transition-colors" title="Düzenle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                            </button>
                            <button onclick="deletePlan(<?php echo $plan['id']; ?>)" class="p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded text-red-400 transition-colors" title="Sil">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100"><?php echo htmlspecialchars($plan['name']); ?></h3>
                        <div class="mt-4 flex items-baseline">
                            <span class="text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100"><?php echo number_format($plan['price'], 0, ',', '.'); ?> ₺</span>
                            <span class="ml-1 text-xl font-semibold text-zinc-500">/<?php echo $plan['duration_days']; ?> gün</span>
                        </div>
                    </div>
                    
                    <ul class="mb-8 space-y-4 flex-1">
                        <?php 
                        $features = explode(',', $plan['features']);
                        foreach ($features as $feature): 
                        ?>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-zinc-600 dark:text-zinc-400"><?php echo htmlspecialchars(trim($feature)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <button onclick="purchasePlan(<?php echo $plan['id']; ?>)" class="w-full py-3 px-4 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-xl font-bold hover:opacity-90 transition-opacity">
                        Hemen Başla
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tab: History -->
    <div id="tab-history" class="tab-content hidden">
        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="m17 5-5-3-5 3"/><path d="m17 19-5 3-5-3"/><path d="M2 12h20"/><path d="m5 7-3 5 3 5"/><path d="m19 7 3 5-3 5"/></svg>
                    </div>
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Toplam Gelir</span>
                </div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100"><?php echo number_format($stats['total_revenue'], 2, ',', '.'); ?> ₺</div>
            </div>
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Aktif Abonelik</span>
                </div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100"><?php echo $stats['active_count']; ?></div>
            </div>
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Toplam İşlem</span>
                </div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100"><?php echo $stats['total_count']; ?></div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden relative">
            <table id="history-table" class="w-full text-left">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Paket</th>
                        <?php if($_SESSION['role'] === 'superadmin'): ?>
                            <th class="px-6 py-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Müşteri</th>
                        <?php endif; ?>
                        <th class="px-6 py-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Satın Alan</th>
                        <th class="px-6 py-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Tutar</th>
                        <th class="px-6 py-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Tarih</th>
                        <th class="px-6 py-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Bitiş</th>
                        <th class="px-6 py-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Durum</th>
                        <?php if($_SESSION['role'] === 'superadmin'): ?>
                            <th class="px-6 py-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">İşlem</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <?php foreach ($history as $item): ?>
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100"><?php echo htmlspecialchars($item['plan_name']); ?></td>
                            <?php if($_SESSION['role'] === 'superadmin'): ?>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400"><?php echo htmlspecialchars($item['tenant_name'] ?: 'Bireysel'); ?></td>
                            <?php endif; ?>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400"><?php echo htmlspecialchars($item['user_name'] ?: '-'); ?></td>
                            <td class="px-6 py-4 text-zinc-900 dark:text-zinc-100 font-bold"><?php echo number_format($item['amount'], 2, ',', '.'); ?> ₺</td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400"><?php echo date('d.m.Y', strtotime($item['start_date'])); ?></td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400"><?php echo date('d.m.Y', strtotime($item['end_date'])); ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php 
                                    if ($item['payment_status'] === 'pending') {
                                        echo 'bg-amber-100 text-amber-850 border border-amber-200 dark:bg-amber-955/30 dark:text-amber-405 dark:border-amber-900/30 animate-pulse';
                                    } elseif ($item['payment_status'] === 'failed') {
                                        echo 'bg-rose-100 text-rose-850 border border-rose-200 dark:bg-rose-955/30 dark:text-rose-450 dark:border-rose-900/30';
                                    } elseif ($item['status'] === 'active') {
                                        echo 'bg-emerald-100 text-emerald-850 border border-emerald-200 dark:bg-emerald-955/30 dark:text-emerald-400 dark:border-emerald-900/30';
                                    } elseif ($item['status'] === 'expired') {
                                        echo 'bg-zinc-100 text-zinc-800 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700';
                                    } else {
                                        echo 'bg-red-100 text-red-850 border border-red-200 dark:bg-red-955/30 dark:text-red-400 dark:border-red-900/30';
                                    }
                                ?>">
                                    <?php 
                                    if ($item['payment_status'] === 'pending') {
                                        echo 'Onay Bekliyor';
                                    } elseif ($item['payment_status'] === 'failed') {
                                        echo 'Reddedildi';
                                    } elseif ($item['status'] === 'active') {
                                        echo 'Aktif / Onaylı';
                                    } elseif ($item['status'] === 'expired') {
                                        echo 'Süresi Doldu';
                                    } else {
                                        echo 'İptal Edildi';
                                    }
                                    ?>
                                </span>
                            </td>
                            <?php if($_SESSION['role'] === 'superadmin'): ?>
                                <td class="px-6 py-4">
                                    <?php if ($item['payment_status'] === 'pending'): ?>
                                        <div class="flex items-center gap-2">
                                            <button onclick="approveSubscription(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['tenant_name'] ?: 'Bireysel', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['plan_name'], ENT_QUOTES); ?>', '<?php echo number_format($item['amount'], 2, ',', '.'); ?> ₺', '<?php echo htmlspecialchars($item['user_name'] ?: '-', ENT_QUOTES); ?>')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm cursor-pointer select-none">
                                                Onayla
                                            </button>
                                            <button onclick="rejectSubscription(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['tenant_name'] ?: 'Bireysel', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['plan_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['user_name'] ?: '-', ENT_QUOTES); ?>')" class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm cursor-pointer select-none">
                                                Reddet
                                            </button>
                                            <button onclick="deleteSubscriptionPurchase(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['tenant_name'] ?: 'Bireysel', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['plan_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['user_name'] ?: '-', ENT_QUOTES); ?>')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm cursor-pointer select-none">
                                                Sil
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button onclick="deleteSubscriptionPurchase(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['tenant_name'] ?: 'Bireysel', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['plan_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['user_name'] ?: '-', ENT_QUOTES); ?>')" class="inline-flex items-center gap-1 px-2 py-1 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 text-red-650 dark:text-red-450 rounded-md text-[10px] font-bold transition-all cursor-pointer border border-red-100 dark:border-red-950/30 select-none">
                                            Sil
                                        </button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yeni Paket Dialog -->
<dialog id="dialog-add-plan" class="dialog w-full sm:max-w-[450px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Yeni Paket Ekle</h2>
      <p class="text-sm text-zinc-500">Müşterilerinize sunmak için yeni bir abonelik paketi oluşturun.</p>
    </header>

    <form action="<?php echo routeUrl('abonelik-paket-ekle'); ?>" method="POST" id="form-add-plan" class="form grid gap-4">
        <div class="grid gap-2">
            <label for="plan_name">Paket Adı</label>
            <input type="text" name="name" id="plan_name" required placeholder="Örn: Başlangıç, Profesyonel" />
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <label for="plan_price">Fiyat (TL)</label>
                <input type="number" name="price" id="plan_price" required placeholder="0" />
            </div>
            <div class="grid gap-2">
                <label for="plan_duration">Süre (Gün)</label>
                <input type="number" name="duration_days" id="plan_duration" required placeholder="30" />
            </div>
        </div>
        <div class="grid gap-2">
            <label for="plan_features">Özellikler (Virgülle ayırın)</label>
            <textarea name="features" id="plan_features" rows="4" required placeholder="Örn: 5 Personel, AI Desteği, Raporlama"></textarea>
        </div>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="submit" form="form-add-plan" class="btn">Paketi Oluştur</button>
    </footer>

    <button type="button" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
  </div>
</dialog>

<!-- Paket Düzenle Dialog -->
<dialog id="dialog-edit-plan" class="dialog w-full sm:max-w-[450px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Paket Düzenle</h2>
      <p class="text-sm text-zinc-500">Abonelik paketi bilgilerini güncelleyin.</p>
    </header>

    <form id="form-edit-plan" class="form grid gap-4">
        <input type="hidden" name="id" id="edit_plan_id" />
        <div class="grid gap-2">
            <label for="edit_plan_name">Paket Adı</label>
            <input type="text" name="name" id="edit_plan_name" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <label for="edit_plan_price">Fiyat (TL)</label>
                <input type="number" name="price" id="edit_plan_price" required />
            </div>
            <div class="grid gap-2">
                <label for="edit_plan_duration">Süre (Gün)</label>
                <input type="number" name="duration_days" id="edit_plan_duration" required />
            </div>
        </div>
        <div class="grid gap-2">
            <label for="edit_plan_features">Özellikler (Virgülle ayırın)</label>
            <textarea name="features" id="edit_plan_features" rows="4" required></textarea>
        </div>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" onclick="savePlan()" class="btn">Değişiklikleri Kaydet</button>
    </footer>

    <button type="button" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
  </div>
</dialog>

<!-- Paket Satın Alma Onay Dialog -->
<dialog id="dialog-confirm-purchase" class="dialog w-full sm:max-w-[425px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Aboneliği Başlat</h2>
      <p class="text-sm text-zinc-500">Seçtiğiniz paketi satın alarak aboneliğinizi başlatmak istediğinize emin misiniz?</p>
    </header>

    <div id="purchase-details" class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg mb-6 border border-zinc-100 dark:border-zinc-700">
        <div class="flex justify-between mb-2">
            <span class="text-sm text-zinc-500">Seçilen Paket:</span>
            <span id="confirm-plan-name" class="text-sm font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
        <div class="flex justify-between">
            <span class="text-sm text-zinc-500">Toplam Tutar:</span>
            <span id="confirm-plan-price" class="text-sm font-bold text-primary">-</span>
        </div>
    </div>

    <footer class="flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" id="btn-confirm-purchase" class="btn">Satın Alımı Onayla</button>
    </footer>

    <button type="button" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
  </div>
</dialog>

<!-- Abonelik Onaylama Dialog -->
<dialog id="dialog-approve-subscription" class="dialog w-full sm:max-w-[425px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
        <svg class="h-5 w-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Abonelik Onayla
      </h2>
      <p class="text-sm text-zinc-500">Bu satın alma işlemini onaylamak istediğinize emin misiniz?</p>
    </header>

    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg mb-6 border border-zinc-100 dark:border-zinc-700">
        <div class="flex justify-between mb-2">
            <span class="text-sm text-zinc-500">Müşteri:</span>
            <span id="approve-tenant-name" class="text-sm font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
        <div class="flex justify-between mb-2">
            <span class="text-sm text-zinc-500">Satın Alan:</span>
            <span id="approve-user-name" class="text-sm font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
        <div class="flex justify-between mb-2">
            <span class="text-sm text-zinc-500">Paket:</span>
            <span id="approve-plan-name" class="text-sm font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
        <div class="flex justify-between">
            <span class="text-sm text-zinc-500">Tutar:</span>
            <span id="approve-plan-price" class="text-sm font-bold text-emerald-600 dark:text-emerald-450">-</span>
        </div>
    </div>

    <footer class="flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">Vazgeç</button>
      <button type="button" id="btn-confirm-approve" class="btn bg-emerald-600 hover:bg-emerald-700 border-emerald-600 hover:border-emerald-700 text-white font-bold rounded-xl px-4 py-2.5 transition-all">Aboneliği Onayla</button>
    </footer>

    <button type="button" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
  </div>
</dialog>

<!-- Satın Alma Silme Dialog -->
<dialog id="dialog-delete-subscription" class="dialog bg-transparent p-0" onclick="if (event.target === this) this.close()">
  <div class="w-full max-w-sm rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-2xl" onclick="event.stopPropagation()">
    <div class="flex flex-col gap-4">
      <div class="flex items-center gap-3 text-red-750">
        <div class="p-2.5 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-650 dark:text-red-400 shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
        </div>
        <h3 class="text-sm font-extrabold text-red-700 dark:text-red-400">Satın Alımı Sil</h3>
      </div>
      
      <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">Bu satın alma kaydını tamamen silmek istediğinize emin misiniz? Bu işlem geri alınamaz.</p>
      
      <div class="bg-zinc-50/50 dark:bg-zinc-950/20 border border-zinc-150 dark:border-zinc-800 p-4 rounded-xl space-y-2 select-none">
        <div class="flex justify-between items-center text-xs">
          <span class="text-zinc-450 text-zinc-500 font-medium">Müşteri:</span>
          <span id="delete-sub-tenant" class="font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
        <div class="flex justify-between items-center text-xs">
          <span class="text-zinc-450 text-zinc-500 font-medium">Satın Alan:</span>
          <span id="delete-sub-user" class="font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
        <div class="flex justify-between items-center text-xs">
          <span class="text-zinc-450 text-zinc-500 font-medium">Paket:</span>
          <span id="delete-sub-plan" class="font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
      </div>
      
      <div class="flex justify-end gap-3 mt-4">
        <button type="button" onclick="document.getElementById('dialog-delete-subscription').close()" class="px-4 py-2 text-xs font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-850 text-zinc-500 dark:text-zinc-400 cursor-pointer transition-colors">Vazgeç</button>
        <button type="button" id="btn-confirm-delete-sub" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-red-650 hover:bg-red-700 text-white cursor-pointer transition-all active:scale-[0.98] shadow-sm">Onayla ve Sil</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Abonelik Reddetme Dialog -->
<dialog id="dialog-reject-subscription" class="dialog bg-transparent p-0" onclick="if (event.target === this) this.close()">
  <div class="w-full max-w-sm rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-2xl" onclick="event.stopPropagation()">
    <div class="flex flex-col gap-4">
      <div class="flex items-center gap-3 text-amber-750">
        <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-955/40 text-amber-650 dark:text-amber-450 shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
        </div>
        <h3 class="text-sm font-extrabold text-amber-700 dark:text-amber-450">Satın Almayı Reddet</h3>
      </div>
      
      <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">Bu satın alma talebini reddetmek istediğinize emin misiniz? Kullanıcının aboneliği başlatılmayacaktır.</p>
      
      <div class="bg-zinc-50/50 dark:bg-zinc-950/20 border border-zinc-150 dark:border-zinc-800 p-4 rounded-xl space-y-2 select-none">
        <div class="flex justify-between items-center text-xs">
          <span class="text-zinc-450 text-zinc-500 font-medium">Müşteri:</span>
          <span id="reject-sub-tenant" class="font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
        <div class="flex justify-between items-center text-xs">
          <span class="text-zinc-450 text-zinc-500 font-medium">Satın Alan:</span>
          <span id="reject-sub-user" class="font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
        <div class="flex justify-between items-center text-xs">
          <span class="text-zinc-450 text-zinc-500 font-medium">Paket:</span>
          <span id="reject-sub-plan" class="font-bold text-zinc-900 dark:text-zinc-100">-</span>
        </div>
      </div>
      
      <div class="flex justify-end gap-3 mt-4">
        <button type="button" onclick="document.getElementById('dialog-reject-subscription').close()" class="px-4 py-2 text-xs font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-850 text-zinc-500 dark:text-zinc-400 cursor-pointer transition-colors">Vazgeç</button>
        <button type="button" id="btn-confirm-reject-sub" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white cursor-pointer transition-all active:scale-[0.98] shadow-sm">Onayla ve Reddet</button>
      </div>
    </div>
  </div>
</dialog>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelectorAll('nav button').forEach(btn => {
        btn.classList.remove('border-primary', 'text-primary');
        btn.classList.add('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
    });
    const activeBtn = document.getElementById('tab-' + tab + '-btn');
    activeBtn.classList.remove('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
    activeBtn.classList.add('border-primary', 'text-primary');
}

$(document).ready(function() {
    $('#form-add-plan').on('submit', function(e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize(), function(response) {
            response = JSON.parse(response);
            if (response.success) {
                showToast({ category: 'success', title: 'Başarılı', description: 'Paket başarıyla oluşturuldu.' });
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.message || 'Bir hata oluştu.' });
            }
        });
    });

    // Initialize DataTable for history
    initDataTable('#history-table', {
        order: [[4, 'desc']], // Tarihe göre azalan
        preloader: true
    });
});

function editPlan(id) {
    $.get('<?php echo routeUrl("abonelik-paket-get"); ?>', { id: id }, function(data) {
        data = JSON.parse(data);
        $('#edit_plan_id').val(data.id);
        $('#edit_plan_name').val(data.name);
        $('#edit_plan_price').val(data.price);
        $('#edit_plan_duration').val(data.duration_days);
        $('#edit_plan_features').val(data.features);
        document.getElementById('dialog-edit-plan').showModal();
    });
}

function savePlan() {
    $.post('<?php echo routeUrl("abonelik-paket-guncelle"); ?>', $('#form-edit-plan').serialize(), function(response) {
        response = JSON.parse(response);
        if (response.success) {
            showToast({ category: 'success', title: 'Başarılı', description: 'Paket başarıyla güncellendi.' });
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast({ category: 'error', title: 'Hata', description: response.message || 'Bir hata oluştu.' });
        }
    });
}

function deletePlan(id) {
    if (!confirm('Bu paketi silmek (pasif yapmak) istediğinize emin misiniz?')) return;
    $.post('<?php echo routeUrl("abonelik-paket-sil"); ?>', { id: id }, function(response) {
        response = JSON.parse(response);
        if (response.success) {
            showToast({ category: 'success', title: 'Başarılı', description: 'Paket pasif yapıldı.' });
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast({ category: 'error', title: 'Hata', description: response.message || 'Bir hata oluştu.' });
        }
    });
}

function purchasePlan(id) {
    const $card = $(event.target).closest('.bg-white');
    const planName = $card.find('h3').text();
    const planPrice = $card.find('.text-4xl').text();

    $('#confirm-plan-name').text(planName);
    $('#confirm-plan-price').text(planPrice);
    
    const dialog = document.getElementById('dialog-confirm-purchase');
    dialog.showModal();

    $('#btn-confirm-purchase').off('click').on('click', function() {
        $(this).prop('disabled', true).text('İşleniyor...');
        
        $.post('<?php echo routeUrl("abonelik-satinal"); ?>', { plan_id: id }, function(response) {
            response = JSON.parse(response);
            
            // Dialogu kapat ki toast görünsün
            document.getElementById('dialog-confirm-purchase').close();
            
            if (response.success) {
                showToast({ category: 'success', title: 'Başarılı', description: response.message });
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.message });
                $('#btn-confirm-purchase').prop('disabled', false).text('Satın Alımı Onayla');
            }
        });
    });
}

function approveSubscription(id, tenantName, planName, planPrice, userName) {
    $('#approve-tenant-name').text(tenantName || '-');
    $('#approve-user-name').text(userName || '-');
    $('#approve-plan-name').text(planName || '-');
    $('#approve-plan-price').text(planPrice || '-');
    
    const dialog = document.getElementById('dialog-approve-subscription');
    dialog.showModal();

    $('#btn-confirm-approve').off('click').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('İşleniyor...');
        
        $.post('<?php echo routeUrl("abonelik-onayla"); ?>', { id: id }, function(response) {
            try {
                response = JSON.parse(response);
            } catch(e) {}
            
            dialog.close();
            
            if (response.success) {
                showToast({ category: 'success', title: 'Başarılı', description: response.message || 'Abonelik başarıyla onaylandı.' });
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.message || 'Bir hata oluştu.' });
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            dialog.close();
            showToast({ category: 'error', title: 'Hata', description: 'Sunucu ile iletişim kurulamadı.' });
            btn.prop('disabled', false).text(originalText);
        });
    });
}

function deleteSubscriptionPurchase(id, tenantName, planName, userName) {
    $('#delete-sub-tenant').text(tenantName || '-');
    $('#delete-sub-user').text(userName || '-');
    $('#delete-sub-plan').text(planName || '-');
    
    const dialog = document.getElementById('dialog-delete-subscription');
    dialog.showModal();

    $('#btn-confirm-delete-sub').off('click').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('İşleniyor...');
        
        $.post('<?php echo routeUrl("abonelik-sil"); ?>', { id: id }, function(response) {
            try {
                response = JSON.parse(response);
            } catch(e) {}
            
            dialog.close();
            
            if (response.success) {
                showToast({ category: 'success', title: 'Başarılı', description: response.message || 'Satın alma kaydı silindi.' });
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.message || 'Bir hata oluştu.' });
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            dialog.close();
            showToast({ category: 'error', title: 'Hata', description: 'Sunucu ile iletişim kurulamadı.' });
            btn.prop('disabled', false).text(originalText);
        });
    });
}

function rejectSubscription(id, tenantName, planName, userName) {
    $('#reject-sub-tenant').text(tenantName || '-');
    $('#reject-sub-user').text(userName || '-');
    $('#reject-sub-plan').text(planName || '-');
    
    const dialog = document.getElementById('dialog-reject-subscription');
    dialog.showModal();

    $('#btn-confirm-reject-sub').off('click').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('İşleniyor...');
        
        $.post('<?php echo routeUrl("abonelik-reddet"); ?>', { id: id }, function(response) {
            try {
                response = JSON.parse(response);
            } catch(e) {}
            
            dialog.close();
            
            if (response.success) {
                showToast({ category: 'success', title: 'Başarılı', description: response.message || 'Satın alma talebi reddedildi.' });
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.message || 'Bir hata oluştu.' });
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            dialog.close();
            showToast({ category: 'error', title: 'Hata', description: 'Sunucu ile iletişim kurulamadı.' });
            btn.prop('disabled', false).text(originalText);
        });
    });
}
</script>


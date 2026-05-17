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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                    echo $item['status'] === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-400'; 
                                ?>">
                                    <?php echo $item['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                                </span>
                            </td>
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
</script>

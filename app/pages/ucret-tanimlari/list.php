<?php 
$pageTitle = 'Ücret Tanımları'; 
$pageSubtitle = 'Sistemdeki tüm unvan ve ücret kriterlerinin listesi';
?>

<div class="p-6">
    <!-- Actions Bar -->
    <div class="flex items-center justify-between gap-3 mb-6 !overflow-visible">
        <div class="flex items-center gap-4 !overflow-visible">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Ücret Tanımları</h1>
            <!-- Aktif Dönem Seçici (Custom Select) -->
            <div class="relative flex items-center gap-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm shadow-sm !overflow-visible">
                <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider whitespace-nowrap">Dönem:</span>
                <div class="app-select-rich !border-none !shadow-none !bg-transparent min-w-[120px] !overflow-visible" id="select-desktop-period">
                  <button type="button" class="font-bold text-zinc-800 dark:text-zinc-100 bg-transparent border-none focus:outline-none cursor-pointer flex items-center gap-1.5 px-1 py-0.5" onclick="toggleCustomSelect(this, event)">
                    <span class="truncate"><?php echo htmlspecialchars($selectedPeriod); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-60"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                  </button>
                  <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                    <header class="!bg-white !dark:bg-zinc-900 !px-3 !py-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                      <input type="text" placeholder="Dönem ara..." autocomplete="off" onkeyup="filterCustomOptions(this)" />
                    </header>
                    <div role="listbox" class="max-h-[200px] overflow-y-auto custom-scrollbar p-1">
                        <?php foreach ($allPeriods as $p): ?>
                        <div role="option" data-select-option data-value="<?php echo htmlspecialchars($p); ?>" onclick="selectDesktopPeriodOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md <?php echo $p === $selectedPeriod ? 'selected' : ''; ?>">
                            <span><?php echo htmlspecialchars($p); ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <?php endforeach; ?>
                    </div>
                  </div>
                  <input type="hidden" id="desktop-wage-period-select" value="<?php echo htmlspecialchars($selectedPeriod); ?>" />
                </div>
                <!-- Silme Butonu -->
                <?php if (count($allPeriods) > 1): ?>
                <button onclick="deleteDesktopPeriod()" class="text-zinc-400 hover:text-red-500 transition-colors ml-1 shrink-0" title="Bu Dönemi Sil">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </button>
                <?php else: ?>
                <button disabled class="text-zinc-300 dark:text-zinc-700 opacity-40 cursor-not-allowed ml-1 shrink-0" title="Sistemde en az bir ücret dönemi bulunmalıdır">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex items-center gap-3 !overflow-visible">
            <!-- Arama Çubuğu -->
            <div class="relative w-full max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="wageSearch" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="Tanım ara...">
            </div>
            
            <!-- Diğer İşlemler (Actions Dropdown) -->
            <div class="relative app-select-rich !overflow-visible" id="select-desktop-actions">
                <button type="button" onclick="toggleCustomSelect(this, event)" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-sm whitespace-nowrap">
                    <span>İşlemler</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-60"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl min-w-[200px] right-0 left-auto bg-white dark:bg-zinc-950 rounded-lg border">
                    <div role="listbox" class="p-1">
                        <!-- Excel'den Yükle -->
                        <div role="option" onclick="document.getElementById('dialog-import-wage').showModal()" class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors rounded-md text-zinc-700 dark:text-zinc-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-70"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span>Excel'den Yükle</span>
                        </div>
                        <!-- Toplu Zam / Kopyala -->
                        <div role="option" onclick="document.getElementById('dialog-copy-period').showModal()" class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors rounded-md text-zinc-700 dark:text-zinc-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-70"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>
                            <span>Toplu Zam / Kopyala</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yeni Ücret Tanımı -->
            <button onclick="document.getElementById('dialog-add-wage').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Yeni Ücret Tanımı
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden relative flex flex-col h-[calc(100vh-230px)]">
        <?php echo renderTablePreloader(); ?>

        <div id="table-container" class="flex-1 flex flex-col overflow-hidden" style="display: none;">
            <table id="wageTable" class="w-full text-left">
                <thead>
                    <tr>
                        <th class="no-sort" style="max-width: 3%">
                            <input type="checkbox" class="input">
                        </th>
                        <th>Unvan</th>
                        <th>Öğrenim</th>
                        <th>Kıdem Yılı</th>
                        <th>Ücret</th>
                        <th class="text-right no-sort">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <?php foreach ($ucretler as $u): ?>
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td>
                                <input type="checkbox" class="input">
                            </td>
                            <td class="font-medium text-zinc-900 dark:text-zinc-100">
                                <button onclick="editWage(<?php echo $u['id']; ?>)" class="hover:text-primary transition-colors text-left">
                                    <?php echo htmlspecialchars($u['unvan']); ?>
                                </button>
                            </td>
                            <td class="text-zinc-600 dark:text-zinc-400"><?php echo htmlspecialchars($u['ogrenim']); ?></td>
                            <td>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400">
                                    <?php echo htmlspecialchars($u['kidem_yili']); ?>
                                </span>
                            </td>
                            <td class="text-zinc-600 dark:text-zinc-100 font-semibold">
                                <?php echo number_format($u['ucret'], 2, ',', '.') . ' ₺'; ?>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="editWage(<?php echo $u['id']; ?>)" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-zinc-400 transition-colors" title="Düzenle">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                    <button onclick="copyWage(<?php echo $u['id']; ?>)" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-zinc-400 transition-colors" title="Kopyala">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                    </button>
                                    <button onclick="deleteWage(<?php echo $u['id']; ?>)" class="p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded text-red-400 transition-colors" title="Sil">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yeni Ücret Tanımı Dialog -->
<dialog id="dialog-add-wage" class="dialog w-full sm:max-w-[450px] !overflow-visible" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl !overflow-visible" onclick="event.stopPropagation()">
    <header class="flex items-start justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Yeni Ücret Tanımı Ekle</h2>
        <p class="text-sm text-zinc-500">Unvan ve öğrenim durumuna göre yeni bir ücret kriteri oluşturun.</p>
      </div>
    </header>

    <form action="<?php echo routeUrl('ucret-ekle'); ?>" method="POST" id="form-add-wage" class="form grid gap-4 !overflow-visible">
        <div class="grid gap-2">
            <label for="unvan">Unvan</label>
            <input type="text" name="unvan" id="unvan" required placeholder="Örn: Hemşire, Doktor, Sekreter" />
        </div>
        
        <div class="grid grid-cols-2 gap-4 !overflow-visible">
            <div class="grid gap-2 !overflow-visible">
                <label>Öğrenim Durumu</label>
                <div class="app-select-rich" id="select-add-ogrenim">
                  <button type="button" class="btn-outline w-full justify-between px-3 text-sm" onclick="toggleCustomSelect(this, event)">
                    <span class="truncate">Lise</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                  </button>
                  <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                    <header class="!bg-white !dark:bg-zinc-900 !px-3 !py-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                      <input type="text" placeholder="Öğrenim ara..." autocomplete="off" onkeyup="filterCustomOptions(this)" />
                    </header>
                    <div role="listbox" class="max-h-[240px] overflow-y-auto custom-scrollbar">
                        <div class="px-3 py-1.5 text-[10px] font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-50 dark:border-zinc-800/50 mb-1">Öğrenim Durumları</div>
                        <?php 
                        $ogrenimler = ['Lise', 'Önlisans', 'Lisans', 'Yüksek Lisans', 'Doktora'];
                        foreach ($ogrenimler as $o): ?>
                        <div role="option" data-select-option data-value="<?php echo $o; ?>" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md <?php echo $o === 'Lise' ? 'selected' : ''; ?>">
                            <span><?php echo $o; ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <?php endforeach; ?>
                    </div>
                  </div>
                  <input type="hidden" name="ogrenim" id="add_ogrenim" value="Lise" required />
                </div>
            </div>
            <div class="grid gap-2 !overflow-visible">
                <label>Kıdem Yılı</label>
                <div class="app-select-rich" id="select-add-kidem">
                  <button type="button" class="btn-outline w-full justify-between px-3 text-sm" onclick="toggleCustomSelect(this, event)">
                    <span class="truncate">0-5 Yıl (Dahil)</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                  </button>
                  <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                    <header class="!bg-white !dark:bg-zinc-900 !px-3 !py-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                      <input type="text" placeholder="Kıdem ara..." autocomplete="off" onkeyup="filterCustomOptions(this)" />
                    </header>
                    <div role="listbox" class="max-h-[240px] overflow-y-auto custom-scrollbar">
                        <div class="px-3 py-1.5 text-[10px] font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-50 dark:border-zinc-800/50 mb-1">Kıdem Aralıkları</div>
                        <?php 
                        $kidemler = ['0-5 Yıl (Dahil)', '5-10 Yıl (Dahil)', '10-15 Yıl (Dahil)', '15-20 Yıl (Dahil)', '20 üzeri'];
                        foreach ($kidemler as $k): ?>
                        <div role="option" data-select-option data-value="<?php echo $k; ?>" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md <?php echo $k === '0-5 Yıl (Dahil)' ? 'selected' : ''; ?>">
                            <span><?php echo $k; ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <?php endforeach; ?>
                    </div>
                  </div>
                  <input type="hidden" name="kidem_yili" id="add_kidem_yili" value="0-5 Yıl (Dahil)" required />
                </div>
            </div>
        </div>

        <input type="hidden" name="donem" id="add_donem" />
        <div class="grid gap-2">
            <label for="ucret">Aylık Ücret (₺)</label>
            <input type="text" name="ucret" id="ucret" required placeholder="0,00" />
        </div>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="submit" form="form-add-wage" class="btn">Tanımı Kaydet</button>
    </footer>

    <button type="button" aria-label="Close dialog" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">
        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
      </svg>
    </button>
  </div>
</dialog>

<!-- Ücret Düzenle Dialog -->
<dialog id="dialog-edit-wage" class="dialog w-full sm:max-w-[450px] !overflow-visible" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl !overflow-visible" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Ücret Tanımı Düzenle</h2>
      <p class="text-sm text-zinc-500">Mevcut ücret tanımını güncellemek için formu doldurun.</p>
    </header>

    <form id="form-edit-wage" class="form grid gap-4 !overflow-visible">
        <input type="hidden" name="id" id="edit_id" />
        <div class="grid gap-2">
            <label for="edit_unvan">Unvan</label>
            <input type="text" name="unvan" id="edit_unvan" required />
        </div>
        
        <div class="grid grid-cols-2 gap-4 !overflow-visible">
            <div class="grid gap-2 !overflow-visible">
                <label>Öğrenim Durumu</label>
                <div class="app-select-rich" id="select-edit-ogrenim">
                  <button type="button" class="btn-outline w-full justify-between px-3 text-sm" onclick="toggleCustomSelect(this, event)">
                    <span class="truncate">Seçiniz...</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                  </button>
                  <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                    <header class="!bg-white !dark:bg-zinc-900 !px-3 !py-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                      <input type="text" placeholder="Öğrenim ara..." autocomplete="off" onkeyup="filterCustomOptions(this)" />
                    </header>
                    <div role="listbox" class="max-h-[240px] overflow-y-auto custom-scrollbar">
                        <div class="px-3 py-1.5 text-[10px] font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-50 dark:border-zinc-800/50 mb-1">Öğrenim Durumları</div>
                        <?php foreach ($ogrenimler as $o): ?>
                        <div role="option" data-select-option data-value="<?php echo $o; ?>" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md">
                            <span><?php echo $o; ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <?php endforeach; ?>
                    </div>
                  </div>
                  <input type="hidden" name="ogrenim" id="edit_ogrenim" required />
                </div>
            </div>
            <div class="grid gap-2 !overflow-visible">
                <label>Kıdem Yılı</label>
                <div class="app-select-rich" id="select-edit-kidem">
                  <button type="button" class="btn-outline w-full justify-between px-3 text-sm" onclick="toggleCustomSelect(this, event)">
                    <span class="truncate">Seçiniz...</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                  </button>
                  <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                    <header class="!bg-white !dark:bg-zinc-900 !px-3 !py-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                      <input type="text" placeholder="Kıdem ara..." autocomplete="off" onkeyup="filterCustomOptions(this)" />
                    </header>
                    <div role="listbox" class="max-h-[240px] overflow-y-auto custom-scrollbar">
                        <div class="px-3 py-1.5 text-[10px] font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-50 dark:border-zinc-800/50 mb-1">Kıdem Aralıkları</div>
                        <?php foreach ($kidemler as $k): ?>
                        <div role="option" data-select-option data-value="<?php echo $k; ?>" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md">
                            <span><?php echo $k; ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <?php endforeach; ?>
                    </div>
                  </div>
                  <input type="hidden" name="kidem_yili" id="edit_kidem_yili" required />
                </div>
            </div>
        </div>

        <input type="hidden" name="donem" id="edit_donem" />
        <div class="grid gap-2">
            <label for="edit_ucret">Aylık Ücret (₺)</label>
            <input type="text" name="ucret" id="edit_ucret" required />
        </div>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" onclick="saveWage()" class="btn">Değişiklikleri Kaydet</button>
    </footer>

    <button type="button" aria-label="Close dialog" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">
        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
      </svg>
    </button>
  </div>
</dialog>

<!-- Dönem Kopyala & Toplu Zam Dialog -->
<dialog id="dialog-copy-period" class="dialog w-full sm:max-w-[450px] !overflow-visible" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl !overflow-visible" onclick="event.stopPropagation()">
    <header class="mb-5">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Yeni Döneme Kopyala & Toplu Zam</h2>
      <p class="text-sm text-zinc-500">Mevcut ücret tanımlarını zam oranıyla birlikte yeni bir döneme aktarın.</p>
    </header>

    <!-- Risk Alert Panel -->
    <div class="bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 rounded-xl p-4 mb-5 flex items-start gap-3">
        <div class="p-1.5 bg-amber-100 dark:bg-amber-900/50 rounded-lg text-amber-600 dark:text-amber-400 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="space-y-1">
            <h4 class="text-xs font-bold text-amber-800 dark:text-amber-300">Önemli Güvenlik Bildirimi</h4>
            <p class="text-[11px] text-amber-700/90 dark:text-amber-400/80 leading-relaxed">
                Bu işlem, seçtiğiniz döneme ait tüm ücret kombinasyonlarını yeni döneme kopyalayacak ve girdiğiniz oranda artıracaktır. <strong>Mevcut dönem verileriniz kesinlikle zarar görmez.</strong>
            </p>
        </div>
    </div>

    <form id="form-copy-period" class="form grid gap-4" onsubmit="event.preventDefault(); submitCopyPeriod();">
        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <label>Kaynak Dönem</label>
                <input type="text" id="copy-from-donem" class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 cursor-not-allowed" readonly value="<?php echo htmlspecialchars($selectedPeriod); ?>" />
            </div>
            <div class="grid gap-2">
                <label for="copy-to-donem">Hedef Dönem Adı*</label>
                <input type="text" id="copy-to-donem" required placeholder="Örn: 2026-2" />
            </div>
        </div>
        <div class="grid gap-2">
            <label for="copy-raise-percent">Zam Oranı (%)*</label>
            <div class="relative flex items-center">
                <input type="number" step="0.1" id="copy-raise-percent" required placeholder="Örn: 15.4" class="w-full pr-8" />
                <div class="absolute right-3 text-zinc-400 text-sm font-bold">%</div>
            </div>
        </div>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" onclick="submitCopyPeriod()" class="btn">Kopyalamayı Başlat</button>
    </footer>

    <button type="button" aria-label="Close dialog" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">
        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
      </svg>
    </button>
  </div>
</dialog>

<!-- Excel'den Yükle Dialog -->
<dialog id="dialog-import-wage" class="dialog w-full sm:max-w-[500px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Excel'den Ücret Tanımı Yükle</h2>
      <p class="text-sm text-zinc-500">Ücret tanımlarını toplu olarak yüklemek için Excel (.xlsx, .xls) veya CSV dosyası seçin.</p>
    </header>

    <div class="grid gap-6">
        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700">
            <div class="flex items-center justify-between text-xs mb-3">
                <span class="font-semibold text-zinc-500">Dosya Formatı:</span>
                <button type="button" onclick="downloadTemplate()" class="text-primary hover:underline flex items-center gap-1 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Örnek Şablon İndir
                </button>
            </div>
            <div class="text-[11px] text-zinc-500 mb-3 space-y-1">
                <ul class="list-disc list-inside">
                    <li>Sütunlar: <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Unvan</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Öğrenim</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Kıdem</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Ücret</span></li>
                    <li>Öğrenim Örn: Lise, Önlisans, Lisans, Yüksek Lisans, Doktora</li>
                    <li>Kıdem Örn: 0-5 Yıl (Dahil), 5-10 Yıl (Dahil) vb.</li>
                </ul>
            </div>
            <input type="file" id="importFile" accept=".xlsx, .xls, .csv" class="hidden" onchange="handleFileSelect(this)">
            <button onclick="document.getElementById('importFile').click()" class="w-full py-8 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col items-center justify-center gap-3 hover:bg-white dark:hover:bg-zinc-900 transition-all group">
                <div class="p-3 bg-zinc-100 dark:bg-zinc-800 rounded-full group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-500"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div class="text-center">
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100 block" id="fileNameDisplay">Dosya Seçin</span>
                    <span class="text-xs text-zinc-500">veya buraya sürükleyin</span>
                </div>
            </button>
        </div>

        <div id="importPreview" class="hidden">
            <h3 class="text-sm font-semibold mb-2">Önizleme (İlk 5 Satır)</h3>
            <div class="max-h-[200px] overflow-auto border border-zinc-200 dark:border-zinc-800 rounded-lg">
                <table class="w-full text-[11px] text-left">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 sticky top-0">
                        <tr>
                            <th class="p-2">Unvan</th>
                            <th class="p-2">Öğrenim</th>
                            <th class="p-2">Kıdem</th>
                            <th class="p-2">Ücret</th>
                        </tr>
                    </thead>
                    <tbody id="previewBody" class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    </tbody>
                </table>
            </div>
            <p id="totalRowsCount" class="text-xs text-zinc-500 mt-2"></p>
        </div>
    </div>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" id="btn-do-import" class="btn hidden" onclick="doImport()">Yükle</button>
    </footer>

    <button type="button" aria-label="Close dialog" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">
        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
      </svg>
    </button>
  </div>
</dialog>

<!-- Silme Onay Dialog -->
<dialog id="dialog-confirm-delete" class="dialog w-full sm:max-w-[480px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-950 p-6 rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-800" onclick="event.stopPropagation()">
    <header class="mb-4">
      <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 tracking-tight">Emin misiniz?</h2>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 leading-relaxed">
        Bu işlem geri alınamaz. Bu ücret tanımını kalıcı olarak silecek ve ilişkili verileri sistemden kaldıracaktır.
      </p>
    </header>
    
    <footer class="flex justify-end gap-3">
      <button type="button" class="px-4 py-2 text-sm font-medium border border-zinc-200 dark:border-zinc-800 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors" onclick="document.getElementById('dialog-confirm-delete').close()">İptal</button>
      <button type="button" id="btn-confirm-delete" class="px-4 py-2 text-sm font-medium bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-md hover:opacity-90 transition-opacity">Devam Et</button>
    </footer>
  </div>
</dialog>

<!-- Dönem Silme Onay Dialog -->
<dialog id="dialog-confirm-delete-period" class="dialog w-full sm:max-w-[480px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-950 p-6 rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-800" onclick="event.stopPropagation()">
    <header class="mb-4">
      <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 tracking-tight flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
        Dönemi Sil?
      </h2>
      <p id="delete-period-warning-text" class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 leading-relaxed">
        Seçilen döneme ait kullanılmayan tüm ücret tanımlarını silmek istediğinize emin misiniz? Bu işlem geri alınamaz!
      </p>
    </header>
    
    <footer class="flex justify-end gap-3">
      <button type="button" class="px-4 py-2 text-sm font-medium border border-zinc-200 dark:border-zinc-800 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors" onclick="document.getElementById('dialog-confirm-delete-period').close()">İptal</button>
      <button type="button" id="btn-confirm-delete-period" class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">Dönemi Sil</button>
    </footer>
  </div>
</dialog>

<!-- Kopyalama Onay Dialog -->
<dialog id="dialog-confirm-copy" class="dialog w-full sm:max-w-[460px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-950 p-6 rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-800" onclick="event.stopPropagation()">
    <header class="mb-4">
      <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 tracking-tight flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-500"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        Kopyalamayı Başlat?
      </h2>
      <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-3 space-y-2 leading-relaxed bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-lg border border-zinc-100 dark:border-zinc-800/80">
        <div class="flex justify-between"><span class="font-medium text-zinc-400">Kaynak Dönem:</span> <span id="confirm-copy-from" class="font-bold text-zinc-800 dark:text-zinc-200"></span></div>
        <div class="flex justify-between"><span class="font-medium text-zinc-400">Hedef Dönem:</span> <span id="confirm-copy-to" class="font-bold text-zinc-800 dark:text-zinc-200"></span></div>
        <div class="flex justify-between"><span class="font-medium text-zinc-400">Zam Oranı:</span> <span id="confirm-copy-raise" class="font-bold text-green-600 dark:text-green-400"></span></div>
      </div>
      <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-3 leading-relaxed">
        Bu işlem, kaynak dönemdeki tüm ücret kombinasyonlarını belirtilen zam oranıyla çarparak hedef dönem altına kopyalayacaktır.
      </p>
    </header>
    
    <footer class="flex justify-end gap-3">
      <button type="button" class="px-4 py-2 text-sm font-medium border border-zinc-200 dark:border-zinc-800 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors" onclick="document.getElementById('dialog-confirm-copy').close()">Vazgeç</button>
      <button type="button" id="btn-confirm-copy-submit" class="px-4 py-2 text-sm font-medium bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 rounded-md hover:opacity-90 transition-opacity">Kopyalamayı Başlat</button>
    </footer>
  </div>
</dialog>

<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>

<script>
// Custom Select Fonksiyonları (Premium Görünüm)
function toggleCustomSelect(btn, event) {
    if (event) event.stopPropagation();
    
    const popover = btn.nextElementSibling;
    const isHidden = popover.getAttribute('aria-hidden') === 'true';
    
    // Diğer tüm popover'ları kapat
    $('[data-custom-popover]').attr('aria-hidden', 'true');
    $('.app-select-rich button').attr('aria-expanded', 'false');

    if (isHidden) {
        popover.setAttribute('aria-hidden', 'false');
        btn.setAttribute('aria-expanded', 'true');
        setTimeout(() => popover.querySelector('input').focus(), 100);
    }
}

function selectCustomOption(el) {
    const value = el.getAttribute('data-value');
    const selectDiv = el.closest('.app-select-rich');
    const btnSpan = selectDiv.querySelector('button span');
    const hiddenInput = selectDiv.querySelector('input[type="hidden"]');
    
    btnSpan.textContent = value;
    btnSpan.classList.remove('text-zinc-400');
    hiddenInput.value = value;
    
    // Popover'ı kapat
    const popover = selectDiv.querySelector('[data-custom-popover]');
    popover.setAttribute('aria-hidden', 'true');
    selectDiv.querySelector('button').setAttribute('aria-expanded', 'false');
    
    // Seçili sınıfını ekle
    $(selectDiv).find('[data-select-option]').removeClass('selected');
    $(el).addClass('selected');
}

function filterCustomOptions(input) {
    const filter = input.value.toLowerCase();
    const options = input.closest('[data-custom-popover]').querySelectorAll('[data-select-option]');
    
    options.forEach(opt => {
        const text = opt.textContent.toLowerCase();
        opt.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Dışarı tıklayınca kapatma
$(document).on('click', function(e) {
    if (!$(e.target).closest('.app-select-rich').length) {
        $('[data-custom-popover]').attr('aria-hidden', 'true');
        $('.app-select-rich button').attr('aria-expanded', 'false');
    }
});

$(document).ready(function() {
    if (typeof initDataTable !== 'function') {
        $('#table-preloader').hide();
        $('#table-container').show();
        return;
    }

    const table = initDataTable('#wageTable', {
        order: [[1, 'asc']],
        dom: '<"flex-1 overflow-auto"rt><"mt-auto border-t border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row justify-between items-center p-4 gap-4 bg-zinc-50/50 dark:bg-zinc-800/30"lip>',
        preloader: '#table-preloader'
    });

    $('#wageSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Initialize currency input masks
    const ucretInput = document.getElementById('ucret');
    if (ucretInput) formatTurkishCurrencyInputDesktop(ucretInput);
    const editUcretInput = document.getElementById('edit_ucret');
    if (editUcretInput) formatTurkishCurrencyInputDesktop(editUcretInput);

    $('#form-add-wage').on('submit', function(e) {
        e.preventDefault();
        
        // Clean formatted currency input before serializing
        const ucretEl = $('#ucret');
        const rawVal = ucretEl.val();
        const cleanedVal = rawVal.replace(/\./g, '').replace(',', '.');
        ucretEl.val(cleanedVal);
        
        const formData = $(this).serialize();
        ucretEl.val(rawVal); // Restore immediately
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                // Her durumda modalı kapat ki toastlar görünür olsun
                document.getElementById('dialog-add-wage').close();

                if (response.success) {
                    showToast({ 
                        category: 'success', 
                        title: 'Başarılı', 
                        description: 'Ücret tanımı başarıyla eklendi.',
                        duration: 1500,
                        onClose: () => location.reload()
                    });
                    setTimeout(() => location.reload(), 1700);
                } else {
                    showToast({ 
                        category: 'error', 
                        title: 'Hata', 
                        description: response.error || 'Ekleme sırasında bir hata oluştu.' 
                    });
                }
            },
            error: function(xhr) {
                document.getElementById('dialog-add-wage').close();
                showToast({ category: 'error', title: 'Hata', description: 'Sunucu hatası oluştu.' });
            }
        });
    });

    // Diyalog açıldığında custom selectleri varsayılanlara sıfırla
    $('[onclick*="dialog-add-wage"]').on('click', function() {
        $('#form-add-wage')[0].reset();
        
        // Varsayılanları ayarla
        updateCustomSelect('#select-add-ogrenim', 'Lise');
        updateCustomSelect('#select-add-kidem', '0-5 Yıl (Dahil)');
        
        // Set active period in hidden field
        $('#add_donem').val($('#desktop-wage-period-select').val());
        
        $('#form-add-wage [data-custom-popover]').attr('aria-hidden', 'true');
    });
});

function editWage(id) {
    $.get('<?php echo routeUrl("ucret-get"); ?>', { id: id }, function(data) {
        if (data.error) {
            showToast({ category: 'error', title: 'Hata', description: data.error });
            return;
        }

        $('#edit_id').val(data.id);
        $('#edit_unvan').val(data.unvan);
        
        // Custom Select'leri güncelle
        updateCustomSelect('#select-edit-ogrenim', data.ogrenim);
        updateCustomSelect('#select-edit-kidem', data.kidem_yili);
        
        // Format wage when loading
        $('#edit_ucret').val(typeof formatTurkishCurrencyDesktop === 'function' ? formatTurkishCurrencyDesktop(data.ucret) : data.ucret);
        $('#edit_donem').val(data.donem || '2026-1');

        document.getElementById('dialog-edit-wage').showModal();
    });
}

function copyWage(id) {
    $.get('<?php echo routeUrl("ucret-get"); ?>', { id: id }, function(data) {
        if (data.error) {
            showToast({ category: 'error', title: 'Hata', description: data.error });
            return;
        }

        $('#unvan').val(data.unvan);
        
        // Custom Select'leri güncelle
        updateCustomSelect('#select-add-ogrenim', data.ogrenim);
        updateCustomSelect('#select-add-kidem', data.kidem_yili);
        
        // Format wage when loading
        $('#ucret').val(typeof formatTurkishCurrencyDesktop === 'function' ? formatTurkishCurrencyDesktop(data.ucret) : data.ucret);
        $('#add_donem').val(data.donem || $('#desktop-wage-period-select').val());

        document.getElementById('dialog-add-wage').showModal();
    });
}

function updateCustomSelect(selector, value) {
    const $div = $(selector);
    const btnSpan = $div.find('button span');
    
    btnSpan.text(value || 'Seçiniz...');
    if (!value || value === 'Seçiniz...') {
        btnSpan.addClass('text-zinc-400');
    } else {
        btnSpan.removeClass('text-zinc-400');
    }
    
    $div.find('input[type="hidden"]').val(value);
    $div.find('[data-select-option]').removeClass('selected');
    if (value) {
        $div.find('[data-value="' + value + '"]').addClass('selected');
    }
}

function saveWage() {
    // Clean formatted currency input before serializing
    const editUcretEl = $('#edit_ucret');
    const rawVal = editUcretEl.val();
    const cleanedVal = rawVal.replace(/\./g, '').replace(',', '.');
    editUcretEl.val(cleanedVal);
    
    const formData = $('#form-edit-wage').serialize();
    editUcretEl.val(rawVal); // Restore immediately
    
    $.post('<?php echo routeUrl("ucret-guncelle"); ?>', formData, function(response) {
        // Her durumda modalı kapat
        document.getElementById('dialog-edit-wage').close();

        if (response.success) {
            showToast({ 
                category: 'success', 
                title: 'Başarılı', 
                description: 'Ücret tanımı başarıyla güncellendi.',
                duration: 1500,
                onClose: () => location.reload()
            });
            setTimeout(() => location.reload(), 1700);
        } else {
            showToast({ 
                category: 'error', 
                title: 'Hata', 
                description: response.error || 'Güncelleme sırasında bir hata oluştu.' 
            });
        }
    }).fail(function() {
        document.getElementById('dialog-edit-wage').close();
        showToast({ category: 'error', title: 'Hata', description: 'Sunucu hatası oluştu.' });
    });
}

let deleteId = null;

function deleteWage(id) {
    deleteId = id;
    const dialog = document.getElementById('dialog-confirm-delete');
    
    // Onay butonuna tıklama olayını bağla (eskisini kaldırarak)
    const confirmBtn = document.getElementById('btn-confirm-delete');
    confirmBtn.onclick = function() {
        confirmDelete();
    };
    
    dialog.showModal();
}

function confirmDelete() {
    if (!deleteId) return;
    
    const btn = document.getElementById('btn-confirm-delete');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Siliniyor...';

    $.post('<?php echo routeUrl("ucret-sil"); ?>', { id: deleteId }, function(response) {
        document.getElementById('dialog-confirm-delete').close();
        btn.disabled = false;
        btn.textContent = originalText;

        if (response.success) {
            showToast({ 
                category: 'success', 
                title: 'Başarılı', 
                description: 'Ücret tanımı başarıyla silindi.',
                duration: 1500,
                onClose: () => location.reload()
            });
            setTimeout(() => location.reload(), 1700);
        } else {
            showToast({ 
                category: 'error', 
                title: 'Hata', 
                description: response.error || 'Silme işlemi başarısız oldu.' 
            });
        }
    }).fail(function() {
        document.getElementById('dialog-confirm-delete').close();
        btn.disabled = false;
        btn.textContent = originalText;
        showToast({ category: 'error', title: 'Hata', description: 'Sunucu hatası oluştu.' });
    });
}

let importData = [];

function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;

    document.getElementById('fileNameDisplay').textContent = file.name;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        // JSON'a çevir (header'ları otomatik algıla veya manuel ver)
        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        
        if (jsonData.length < 2) {
            showToast({ category: 'error', title: 'Hata', description: 'Dosya boş veya başlık satırı eksik.' });
            return;
        }

        // Başlıkları geç (ilk satır)
        const rows = jsonData.slice(1);
        importData = rows.map(row => ({
            unvan: row[0],
            ogrenim: row[1],
            kidem_yili: row[2],
            ucret: row[3]
        })).filter(row => row.unvan); // Boş satırları filtrele

        renderPreview();
    };
    reader.readAsArrayBuffer(file);
}

function renderPreview() {
    const previewBody = document.getElementById('previewBody');
    previewBody.innerHTML = '';
    
    const displayRows = importData.slice(0, 5);
    displayRows.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="p-2">${row.unvan || ''}</td>
            <td class="p-2">${row.ogrenim || ''}</td>
            <td class="p-2">${row.kidem_yili || ''}</td>
            <td class="p-2">${row.ucret || ''}</td>
        `;
        previewBody.appendChild(tr);
    });

    document.getElementById('importPreview').classList.remove('hidden');
    document.getElementById('btn-do-import').classList.remove('hidden');
    document.getElementById('totalRowsCount').textContent = `Toplam ${importData.length} kayıt bulundu.`;
}

async function downloadTemplate() {
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('Ücret Tanımları');

    // Başlıklar
    worksheet.columns = [
        { header: 'Unvan', key: 'unvan', width: 25 },
        { header: 'Öğrenim', key: 'ogrenim', width: 20 },
        { header: 'Kıdem', key: 'kidem', width: 25 },
        { header: 'Ücret', key: 'ucret', width: 15 }
    ];

    // Örnek veriler
    worksheet.addRow({ unvan: 'Hemşire', ogrenim: 'Lisans', kidem: '0-5 Yıl (Dahil)', ucret: 45000 });
    worksheet.addRow({ unvan: 'Doktor', ogrenim: 'Doktora', kidem: '5-10 Yıl (Dahil)', ucret: 85000 });

    // Başlık stilini ayarla
    worksheet.getRow(1).font = { bold: true };
    worksheet.getRow(1).fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FFE4E4E7' } // Zinc-200
    };

    // Veri Doğrulama (Açılır Listeler)
    const ogrenimList = ['Lise', 'Önlisans', 'Lisans', 'Yüksek Lisans', 'Doktora'];
    const kidemList = ['0-5 Yıl (Dahil)', '5-10 Yıl (Dahil)', '10-15 Yıl (Dahil)', '15-20 Yıl (Dahil)', '20 üzeri'];

    // İlk 100 satır için doğrulama ekle (Öğrenim - B sütunu)
    for (let i = 2; i <= 100; i++) {
        worksheet.getCell(`B${i}`).dataValidation = {
            type: 'list',
            allowBlank: true,
            formulae: [`"${ogrenimList.join(',')}"`]
        };
        
        // Kıdem (C sütunu)
        worksheet.getCell(`C${i}`).dataValidation = {
            type: 'list',
            allowBlank: true,
            formulae: [`"${kidemList.join(',')}"`]
        };

        // Ücret (D sütunu) - Sayı doğrulaması
        worksheet.getCell(`D${i}`).dataValidation = {
            type: 'decimal',
            operator: 'greaterThanOrEqual',
            formulae: [0],
            showErrorMessage: true,
            errorTitle: 'Hatalı Ücret',
            error: 'Lütfen geçerli bir sayı giriniz.'
        };
    }

    // Dosyayı indir
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'ucret_tanimlari_sablon.xlsx';
    link.click();
}

function doImport() {
    if (importData.length === 0) return;

    const btn = document.getElementById('btn-do-import');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Yükleniyor...';

    $.ajax({
        url: '<?php echo routeUrl("ucret-import"); ?>',
        method: 'POST',
        data: JSON.stringify({ 
            data: importData,
            donem: document.getElementById('desktop-wage-period-select').value
        }),
        contentType: 'application/json',
        success: function(response) {
            document.getElementById('dialog-import-wage').close();
            if (response.success) {
                showToast({ 
                    category: 'success', 
                    title: 'Başarılı', 
                    description: response.count + ' kayıt başarıyla yüklendi.',
                    duration: 2000,
                    onClose: () => location.reload()
                });
                setTimeout(() => location.reload(), 2200);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.error || 'Yükleme başarısız.' });
                btn.disabled = false;
                btn.textContent = originalText;
            }
        },
        error: function() {
            showToast({ category: 'error', title: 'Hata', description: 'Sunucu hatası oluştu.' });
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
}

function selectDesktopPeriodOption(el) {
    const value = el.getAttribute('data-value');
    switchDesktopWagePeriod(value);
}

function switchDesktopWagePeriod(donem) {
    const url = new URL(window.location.href);
    url.searchParams.set('donem', donem);
    window.location.href = url.toString();
}

function deleteDesktopPeriod() {
    const selectedPeriod = document.getElementById('desktop-wage-period-select').value;
    
    // Set text warning
    document.getElementById('delete-period-warning-text').textContent = `"${selectedPeriod}" dönemine ait kullanılmayan tüm ücret tanımlarını silmek istediğinize emin misiniz? Bu işlem geri alınamaz!`;
    
    // Bind click event to confirm button
    const confirmBtn = document.getElementById('btn-confirm-delete-period');
    confirmBtn.onclick = function() {
        confirmBtn.disabled = true;
        const originalText = confirmBtn.textContent;
        confirmBtn.textContent = 'Siliniyor...';
        
        $.post('<?php echo routeUrl("ucret-donem-sil"); ?>', { donem: selectedPeriod }, function(response) {
            document.getElementById('dialog-confirm-delete-period').close();
            confirmBtn.disabled = false;
            confirmBtn.textContent = originalText;
            
            if (response.success) {
                showToast({
                    category: 'success',
                    title: 'Başarılı',
                    description: response.message || 'Dönem başarıyla silindi.',
                    duration: 1500,
                    onClose: () => {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('donem');
                        window.location.href = url.toString();
                    }
                });
                setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('donem');
                    window.location.href = url.toString();
                }, 1700);
            } else {
                showToast({
                    category: 'error',
                    title: 'Hata',
                    description: response.error || 'Dönem silinirken bir hata oluştu.'
                });
            }
        }).fail(function() {
            document.getElementById('dialog-confirm-delete-period').close();
            confirmBtn.disabled = false;
            confirmBtn.textContent = originalText;
            showToast({ category: 'error', title: 'Hata', description: 'Sunucu hatası oluştu.' });
        });
    };
    
    // Open Dialog
    document.getElementById('dialog-confirm-delete-period').showModal();
}

function submitCopyPeriod() {
    const from_donem = $('#copy-from-donem').val();
    const to_donem = $('#copy-to-donem').val();
    const raise_percent = $('#copy-raise-percent').val();
    
    if (!to_donem || !raise_percent) {
        showToast({ category: 'error', title: 'Hata', description: 'Lütfen tüm alanları doldurun.' });
        return;
    }

    // Populate confirmation card details
    document.getElementById('confirm-copy-from').textContent = from_donem;
    document.getElementById('confirm-copy-to').textContent = to_donem;
    document.getElementById('confirm-copy-raise').textContent = `%${parseFloat(raise_percent).toFixed(1)}`;

    const confirmBtn = document.getElementById('btn-confirm-copy-submit');
    confirmBtn.onclick = function() {
        confirmBtn.disabled = true;
        const originalText = confirmBtn.textContent;
        confirmBtn.textContent = 'Kopyalanıyor...';

        $.post('<?php echo routeUrl("ucret-donem-kopyala"); ?>', {
            from_donem: from_donem,
            to_donem: to_donem,
            raise_percent: raise_percent
        }, function(response) {
            document.getElementById('dialog-confirm-copy').close();
            document.getElementById('dialog-copy-period').close();
            confirmBtn.disabled = false;
            confirmBtn.textContent = originalText;
            
            if (response.success) {
                showToast({
                    category: 'success',
                    title: 'Başarılı',
                    description: response.message || 'Dönem başarıyla kopyalandı.',
                    duration: 1500,
                    onClose: () => {
                        const url = new URL(window.location.href);
                        url.searchParams.set('donem', to_donem);
                        window.location.href = url.toString();
                    }
                });
                setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('donem', to_donem);
                    window.location.href = url.toString();
                }, 1700);
            } else {
                showToast({
                    category: 'error',
                    title: 'Hata',
                    description: response.error || 'Kopyalama sırasında bir hata oluştu.'
                });
            }
        }).fail(function() {
            document.getElementById('dialog-confirm-copy').close();
            confirmBtn.disabled = false;
            confirmBtn.textContent = originalText;
            showToast({ category: 'error', title: 'Hata', description: 'Sunucu hatası oluştu.' });
        });
    };

    // Open confirmation dialog
    document.getElementById('dialog-confirm-copy').showModal();
}

function formatTurkishCurrencyDesktop(value) {
    if (value === null || value === undefined || value === '') return '';
    let val = parseFloat(value);
    if (isNaN(val)) return value;
    let parts = val.toFixed(2).split('.');
    let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return integerPart + ',' + parts[1];
}

function formatTurkishCurrencyInputDesktop(inputElement) {
    inputElement.addEventListener('input', function(e) {
        let value = e.target.value;
        value = value.replace(/[^0-9,]/g, '');
        const parts = value.split(',');
        if (parts.length > 2) {
            value = parts[0] + ',' + parts.slice(1).join('');
        }
        let integerPart = parts[0];
        let decimalPart = parts[1];
        if (integerPart.length > 1 && integerPart.startsWith('0')) {
            integerPart = integerPart.replace(/^0+/, '');
            if (integerPart === '') integerPart = '0';
        }
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        if (decimalPart !== undefined) {
            decimalPart = decimalPart.substring(0, 2);
            e.target.value = integerPart + ',' + decimalPart;
        } else {
            e.target.value = integerPart;
        }
    });
}
</script>

<style>
/* Premium Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e4e4e7;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #3f3f46;
}

/* Nowrap Custom Select Options */
.app-select-rich [role="option"] {
    white-space: nowrap !important;
}
.app-select-rich [data-custom-popover] {
    width: max-content !important;
    min-width: 100% !important;
}
</style>

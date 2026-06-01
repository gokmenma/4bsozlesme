<?php 
$pageTitle = 'Personeller Listesi'; 
$pageSubtitle = 'Sistemdeki tüm personellerin detaylı listesi';

$defModel = new Definition();
$tenant_id = $_SESSION['tenant_id'] ?? 0;
$tenant_settings = $defModel->getSettings($tenant_id);
$custom_petition = $tenant_settings['custom_petition_template'] ?? '';
?>
<script>
if (!document.getElementById('toaster')) {
    document.write('<div id="toaster" class="toaster" data-position="bottom-right" popover="manual"></div>');
}
</script>

<div class="p-6">
    <!-- Actions Bar -->
    <div class="flex items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Personeller</h1>
        <div class="flex items-center gap-3">
            <button id="clearAllFilters" style="display: none;" class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M7 6v12"/><path d="M11 6v12"/><path d="M15 6v12"/><path d="M19 6v12"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                Temizle
            </button>
            <div class="relative w-full max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="personnelSearch" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="Personel ara...">
            </div>
            <button onclick="exportToExcel()" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-900 text-green-600 dark:text-green-400 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                Aktar
            </button>
            <button onclick="document.getElementById('dialog-import-excel').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                Yükle
            </button>
            <button onclick="document.getElementById('dialog-add-personnel').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Yeni Personel
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden relative flex flex-col max-h-[calc(100vh-200px)] min-h-0 h-fit">
        <?php echo renderTablePreloader(); ?>

        <div id="table-container" class="flex-1 flex flex-col overflow-hidden" style="display: none;">
            <table id="personnelTable" class="w-full text-left">
                <thead>
                    <tr>
                        <th class="w-[5%] no-sort px-2 text-center">
                             <input type="checkbox" class="input">
                        </th>
                        <th class="w-[5%] no-sort px-0 text-center"></th>
                        <th data-column="2" class="w-[12%] min-w-[120px]">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>Ad Soyad</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="3">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>TC Kimlik</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="4">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>Cinsiyet</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="5">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>Unvan</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="6">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>Öğrenim</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="7">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>Ücret</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="8">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>Durum</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="9">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>G. Başlama</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="10">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>Kadroya Geçiş</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="11">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>Ayrılış / Kadro</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th data-column="12">
                            <div class="flex items-center justify-between gap-2 group/th">
                                <span>Telefon</span>
                                <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>
                        </th>
                        <th class="text-right no-sort w-20">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yeni Personel Dialog -->
<dialog id="dialog-add-personnel" class="dialog" style="max-width: 750px; width: 90vw;" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" style="width: 750px; max-width: 100%;" onclick="event.stopPropagation()">
    <header class="flex items-start justify-between mb-6">
      <div>
        <h2 id="dialog-add-personnel-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Yeni Personel Ekle</h2>
        <p id="dialog-add-personnel-description" class="text-sm text-zinc-500">Sisteme yeni bir personel kaydı eklemek için formu doldurun veya kimlik okutun.</p>
      </div>
      <input type="file" id="ai-scan-input" class="hidden" accept="image/*" onchange="processAiScan(this)">
    </header>

    <section class="relative">
      <!-- AI Loading Overlay -->
      <div id="ai-loading" class="absolute inset-0 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center rounded-lg hidden">
        <div class="relative w-16 h-16 mb-4">
          <div class="absolute inset-0 border-4 border-indigo-100 dark:border-indigo-900/30 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-indigo-600 rounded-full border-t-transparent animate-spin"></div>
        </div>
        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Yapay zeka bilgileri çözümlüyor...</p>
        <p class="text-xs text-zinc-500 mt-1">Bu işlem birkaç saniye sürebilir.</p>
      </div>

      <form action="<?php echo routeUrl('personel-ekle'); ?>" method="POST" id="form-add-personnel" class="form grid gap-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <label for="tc_kimlik">TC Kimlik No</label>
                <input type="text" name="tc_kimlik" id="tc_kimlik" maxlength="11" required placeholder="11 haneli TC no" />
            </div>
            <div class="grid gap-2">
                <label for="ad_soyad">Ad Soyad</label>
                <input type="text" name="ad_soyad" id="ad_soyad" required placeholder="Adı ve Soyadı" />
            </div>
        </div>

        <div class="grid gap-2">
            <label>Unvan / Ücret Tanımı</label>
            <div id="select-add-ucret" class="app-select-rich">
              <button type="button" class="btn-outline w-full justify-between" id="select-add-ucret-trigger" aria-expanded="false" onclick="toggleSelectRich(this)">
                <span class="truncate">Ücret tanımı seçin...</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-50"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
              </button>
              <div id="select-add-ucret-popover" data-custom-popover aria-hidden="true">
                <header>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-50"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                  <input type="text" placeholder="Unvan veya ücret ara..." autocomplete="off" />
                </header>
                <div id="select-add-ucret-listbox" class="max-h-[300px] overflow-y-auto">
                  <?php foreach ($ucretler as $u): ?>
                    <div data-select-option data-value="<?php echo $u['id']; ?>" onclick="selectRichOption(this)" class="p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer border-b border-zinc-100 dark:border-zinc-800 last:border-0 transition-colors relative group">
                      <div class="grid grid-cols-3 gap-2 items-start mb-1 pr-8">
                        <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate" title="<?php echo htmlspecialchars($u['unvan']); ?>">
                          <?php echo htmlspecialchars($u['unvan']); ?>
                        </div>
                        <div class="text-[11px] text-zinc-500 truncate text-center" title="<?php echo htmlspecialchars($u['ogrenim']); ?>">
                          <?php echo htmlspecialchars($u['ogrenim']); ?>
                        </div>
                        <div class="font-bold text-sm text-primary text-right whitespace-nowrap">
                          <?php echo number_format($u['ucret'], 2, ',', '.'); ?> TL
                        </div>
                      </div>
                      <div class="text-[10px] text-zinc-400 uppercase tracking-widest pr-8">
                        <?php echo htmlspecialchars($u['kidem_yili']); ?> KIDEM
                      </div>
                      <div class="check-icon absolute right-4 top-1/2 -translate-y-1/2 opacity-0 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-900 dark:text-zinc-100"><path d="M20 6 9 17l-5-5"/></svg>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <input type="hidden" name="ucret_id" id="add_ucret_id" required />
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
                <label for="goreve_baslama_tarihi">Göreve Başlama</label>
                <input type="text" name="goreve_baslama_tarihi" id="goreve_baslama_tarihi" class="datepicker" required />
            </div>
            <div class="grid gap-2">
                <label>Durum</label>
                <?php echo renderCustomSelect('add-durum', 'durum', [
                    ['value' => 'aktif', 'label' => 'Aktif'],
                    ['value' => 'pasif', 'label' => 'Pasif'],
                    ['value' => 'dilekce_alindi', 'label' => 'Dilekçe Alındı'],
                    ['value' => 'kadroya_gecti', 'label' => 'Kadroya Geçti'],
                    ['value' => 'kadroya_gecmeyecek', 'label' => 'Kadroya Geçmeyecek']
                ], 'aktif', 'w-full'); ?>
            </div>
            <div class="grid gap-2">
                <label>Cinsiyet</label>
                <?php echo renderCustomSelect('add-cinsiyet', 'cinsiyet', [
                    ['value' => 'erkek', 'label' => 'Erkek'],
                    ['value' => 'kadin', 'label' => 'Kadın']
                ], 'erkek', 'w-full'); ?>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
                <label for="telefon">Telefon</label>
                <input type="text" name="telefon" id="telefon" placeholder="05XX XXX XX XX" />
            </div>
            <div class="grid gap-2">
                <label for="meslek_kodu">Meslek Kodu</label>
                <input type="text" name="meslek_kodu" id="meslek_kodu" placeholder="SGK Meslek Kodu" />
            </div>
            <div class="grid gap-2">
                <label for="ayrilma_tarihi">Ayrılış / Kadroya Geçiş Tarihi</label>
                <input type="text" name="ayrilma_tarihi" id="ayrilma_tarihi" class="datepicker" placeholder="Seçiniz..." />
            </div>
        </div>
      </form>
    </section>

    <footer>
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="submit" form="form-add-personnel" class="btn">Kaydet ve Ekle</button>
    </footer>

    <button type="button" onclick="triggerAiScan()" class="absolute top-4 right-12 p-2 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-all group" title="Yapay Zeka ile Tara">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles group-hover:scale-110 transition-transform"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
    </button>

    <button type="button" aria-label="Close dialog" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
      </svg>
    </button>
  </div>
</dialog>
 
<!-- Personel Sil Dialog -->
<dialog id="dialog-delete-personnel" class="dialog w-full sm:max-w-[480px]" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <div>
      <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mb-2">Emin misiniz?</h2>
      <p class="text-sm text-zinc-500 dark:text-zinc-400">Bu personeli silmek istediğinize emin misiniz? Bu işlem geri alınamaz.</p>
    </div>
    <div class="flex justify-end gap-3 mt-6">
      <button type="button" onclick="document.getElementById('dialog-delete-personnel').close()" class="px-5 py-2 text-sm font-medium border border-zinc-300 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-900 dark:text-zinc-100 transition-colors">İptal</button>
      <button type="button" id="btn-confirm-delete-personnel" class="px-5 py-2 text-sm font-medium bg-zinc-950 dark:bg-zinc-100 text-white dark:text-zinc-950 rounded-lg hover:opacity-90 transition-opacity">Devam Et</button>
    </div>
  </div>
</dialog>

<!-- Personel Düzenle Dialog -->
<dialog id="dialog-edit-personnel" class="dialog" style="max-width: 750px; width: 90vw;" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" style="width: 750px; max-width: 100%;" onclick="event.stopPropagation()">
    <header>
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Personel Düzenle</h2>
      <p class="text-sm text-zinc-500">Personel bilgilerini güncellemek için aşağıdaki formu kullanın.</p>
    </header>
 
    <section>
      <form id="form-edit-personnel" class="form grid gap-4">
        <input type="hidden" name="id" id="edit_id" />
        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <label for="edit_tc_kimlik">TC Kimlik No</label>
                <input type="text" name="tc_kimlik" id="edit_tc_kimlik" maxlength="11" required />
            </div>
            <div class="grid gap-2">
                <label for="edit_ad_soyad">Ad Soyad</label>
                <input type="text" name="ad_soyad" id="edit_ad_soyad" required />
            </div>
        </div>
 
        <div class="grid gap-2">
            <label>Unvan / Ücret Tanımı</label>
            <div id="select-edit-ucret" class="app-select-rich">
              <button type="button" class="btn-outline w-full justify-between" id="select-edit-ucret-trigger" aria-expanded="false" onclick="toggleSelectRich(this)">
                <span class="truncate">Ücret tanımı seçin...</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-50"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
              </button>
              <div id="select-edit-ucret-popover" data-custom-popover aria-hidden="true">
                <header>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-50"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                  <input type="text" placeholder="Unvan veya ücret ara..." autocomplete="off" />
                </header>
                <div id="select-edit-ucret-listbox" class="max-h-[300px] overflow-y-auto">
                  <?php foreach ($ucretler as $u): ?>
                    <div data-select-option data-value="<?php echo $u['id']; ?>" onclick="selectRichOption(this)" class="p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer border-b border-zinc-100 dark:border-zinc-800 last:border-0 transition-colors relative group">
                      <div class="grid grid-cols-3 gap-2 items-start mb-1 pr-8">
                        <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate" title="<?php echo htmlspecialchars($u['unvan']); ?>">
                          <?php echo htmlspecialchars($u['unvan']); ?>
                        </div>
                        <div class="text-[11px] text-zinc-500 truncate text-center" title="<?php echo htmlspecialchars($u['ogrenim']); ?>">
                          <?php echo htmlspecialchars($u['ogrenim']); ?>
                        </div>
                        <div class="font-bold text-sm text-primary text-right whitespace-nowrap">
                          <?php echo number_format($u['ucret'], 2, ',', '.'); ?> TL
                        </div>
                      </div>
                      <div class="text-[10px] text-zinc-400 uppercase tracking-widest pr-8">
                        <?php echo htmlspecialchars($u['kidem_yili']); ?> KIDEM
                      </div>
                      <div class="check-icon absolute right-4 top-1/2 -translate-y-1/2 opacity-0 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-900 dark:text-zinc-100"><path d="M20 6 9 17l-5-5"/></svg>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <input type="hidden" name="ucret_id" id="edit_ucret_id" required />
            </div>
        </div>
 
        <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
                <label for="edit_goreve_baslama_tarihi">Göreve Başlama</label>
                <input type="text" name="goreve_baslama_tarihi" id="edit_goreve_baslama_tarihi" class="datepicker" required />
            </div>
            <div class="grid gap-2">
                <label>Durum</label>
                <?php echo renderCustomSelect('edit-durum', 'durum', [
                    ['value' => 'aktif', 'label' => 'Aktif'],
                    ['value' => 'pasif', 'label' => 'Pasif'],
                    ['value' => 'dilekce_alindi', 'label' => 'Dilekçe Alındı'],
                    ['value' => 'kadroya_gecti', 'label' => 'Kadroya Geçti'],
                    ['value' => 'kadroya_gecmeyecek', 'label' => 'Kadroya Geçmeyecek']
                ], 'aktif', 'w-full'); ?>
            </div>
            <div class="grid gap-2">
                <label>Cinsiyet</label>
                <?php echo renderCustomSelect('edit-cinsiyet', 'cinsiyet', [
                    ['value' => 'erkek', 'label' => 'Erkek'],
                    ['value' => 'kadin', 'label' => 'Kadın']
                ], 'erkek', 'w-full'); ?>
            </div>
        </div>
 
        <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
                <label for="edit_telefon">Telefon</label>
                <input type="text" name="telefon" id="edit_telefon" />
            </div>
            <div class="grid gap-2">
                <label for="edit_meslek_kodu">Meslek Kodu</label>
                <input type="text" name="meslek_kodu" id="edit_meslek_kodu" />
            </div>
            <div class="grid gap-2">
                <label for="edit_ayrilma_tarihi">Ayrılış / Kadroya Geçiş Tarihi</label>
                <input type="text" name="ayrilma_tarihi" id="edit_ayrilma_tarihi" class="datepicker" placeholder="Seçiniz..." />
            </div>
        </div>
      </form>
    </section>
 
    <footer>
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" onclick="savePersonnel()" class="btn">Değişiklikleri Kaydet</button>
    </footer>
 
    <button type="button" aria-label="Close dialog" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
      </svg>
    </button>
  </div>
</dialog>

<!-- Sözleşme Önizleme Dialog -->
<dialog id="dialog-preview-contract" class="dialog contract-preview-dialog" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-0 rounded-xl shadow-2xl overflow-hidden flex flex-col" onclick="event.stopPropagation()" style="width:95vw; max-width:1400px; height:95vh; max-height:95vh;">
    <header class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 flex-shrink-0">
      <div class="flex items-center gap-3">
        <div class="p-2.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
        </div>
        <div>
          <h2 class="text-[15px] font-bold text-zinc-900 dark:text-zinc-100 m-0 leading-tight">Sözleşme Önizleme</h2>
          <p class="text-xs font-medium text-zinc-500 m-0" id="preview-personnel-name"></p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button onclick="printPreview()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-semibold hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
            Yazdır
        </button>
        <button onclick="document.getElementById('dialog-preview-contract').close()" class="flex items-center justify-center p-2 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
        </button>
      </div>
    </header>

    <div id="contract-preview-viewport" class="flex-1 overflow-hidden bg-zinc-200 dark:bg-zinc-900/50 flex justify-center items-start p-6">
        <div id="contract-preview-wrapper" class="origin-top">
            <div id="contract-preview-content" class="contract-print-preview contract-document bg-white dark:bg-white w-[210mm] min-h-[297mm] p-[0.5cm_2cm] shadow-2xl border border-zinc-200 dark:border-zinc-800">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
  </div>
</dialog>

<style>
#dialog-petition .ql-container {
    height: calc(100% - 42px) !important;
}
#dialog-petition .ql-editor {
    height: 100% !important;
    overflow-y: auto !important;
}
</style>
<!-- Dilekçe Yazdır Dialog -->
<dialog id="dialog-petition" class="dialog w-full sm:max-w-[1550px] w-[96vw]" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl flex flex-col gap-4 max-h-[96vh] h-[92vh]" onclick="event.stopPropagation()">
    <header class="flex items-center justify-between" style="display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; text-align: left !important; width: 100% !important;">
      <div style="text-align: left !important;">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100" style="text-align: left !important;">Dilekçe Yazdır</h2>
        <p class="text-sm text-zinc-500" style="text-align: left !important;">Dilekçe içeriğini düzenleyip yazdırabilirsiniz.</p>
      </div>
      <div class="flex items-center justify-end gap-3" style="display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: flex-end !important; gap: 12px !important;">
        <button type="button" onclick="savePetitionTemplate()" class="btn btn-secondary flex items-center gap-2 text-sm px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 rounded-xl transition-all shadow-sm font-medium">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Şablonu Varsayılan Yap
        </button>
        <button type="button" class="text-zinc-400 hover:text-zinc-600 ml-1" onclick="this.closest('dialog').close()">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>
      </div>
    </header>

    <div class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-200 px-4 py-3 rounded-xl flex items-center gap-3 border border-indigo-100 dark:border-indigo-900/60 shadow-sm text-xs select-none">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-indigo-600 dark:text-indigo-400"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span><strong>Bilgi:</strong> Şablonu dilediğiniz gibi düzenleyip kaydedebilirsiniz. Kadın personeller için dilekçe yazdırılırken, <em>"Askerlik Durum Belgesi"</em> eki sistem tarafından **otomatik olarak çıkartılarak** listeniz ardışık olarak yeniden numaralandırılacaktır.</span>
    </div>

    <div class="flex-1 min-h-0 overflow-hidden border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900">
      <div id="petition-quill-editor" style="height: 100%;"></div>
    </div>

    <footer class="flex justify-end gap-3 shrink-0">
      <button type="button" class="px-3 py-2 text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 font-medium" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" onclick="showPetitionStatusConfirmation()" class="btn bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 flex items-center gap-2 px-4 py-2 text-sm rounded-xl shadow-md transition-all font-medium">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
        Yazdır
      </button>
    </footer>
  </div>
</dialog>

<!-- Dilekçe Alındı Durum Güncelleme Onay Dialog -->
<dialog id="dialog-confirm-petition-status" class="dialog w-full sm:max-w-[480px]" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl flex flex-col gap-4 text-left" onclick="event.stopPropagation()" style="text-align: left !important;">
    <header class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800" style="display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; width: 100% !important; text-align: left !important;">
      <div style="text-align: left !important; display: flex !important; align-items: center !important; gap: 12px !important;">
        <div class="p-2.5 bg-primary/10 text-primary rounded-xl flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </div>
        <div style="text-align: left !important;">
          <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 m-0 leading-tight">Durum Güncelleme Onayı</h3>
          <p class="text-xs text-zinc-500 m-0 mt-0.5">Dilekçe alındı işlemi</p>
        </div>
      </div>
      <button type="button" class="text-zinc-400 hover:text-zinc-600 rounded-lg p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors" onclick="document.getElementById('dialog-confirm-petition-status').close()">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
      </button>
    </header>
    
    <section class="text-sm text-zinc-600 dark:text-zinc-400 py-1" style="text-align: left !important;">
      <p><strong><span id="confirm-personnel-name">Personel</span></strong> isimli personelin durumu <strong>"Dilekçe Alındı"</strong> olarak güncellensin mi?</p>
    </section>
    
    <footer class="flex justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800" style="display: flex !important; justify-content: flex-end !important; gap: 12px !important; width: 100% !important;">
      <button type="button" class="btn-outline text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 px-4 py-2 text-sm font-medium rounded-xl border border-zinc-200 dark:border-zinc-700 bg-transparent transition-all" onclick="proceedWithPrint()">Hayır, Sadece Yazdır</button>
      <button type="button" id="btn-confirm-petition-status" onclick="updateStatusAndPrint()" class="btn bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 flex items-center gap-2 px-4 py-2 text-sm rounded-xl shadow-md transition-all font-medium">Evet, Güncelle ve Yazdır</button>
    </footer>
  </div>
</dialog>


<!-- Excel'den Yükle Dialog -->
<dialog id="dialog-import-excel" class="dialog w-full sm:max-w-[550px]" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="flex items-start justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Excel'den Personel Yükle</h2>
        <p class="text-sm text-zinc-500">Excel dosyasındaki personelleri toplu olarak sisteme aktarın.</p>
      </div>
      <button onclick="downloadSampleXLSX()" class="text-xs font-medium text-primary hover:underline flex items-center gap-1 bg-transparent border-none p-0 cursor-pointer">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
        Örnek Şablon İndir
      </button>
    </header>

    <section class="grid gap-6">
      <div class="grid gap-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Excel Dosyası Seçin</label>
        <div class="relative group">
          <input type="file" id="excel-file-input" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept=".xlsx, .xls, .csv" onchange="handleExcelFile(this)">
          <div class="border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl p-8 flex flex-col items-center justify-center gap-2 group-hover:border-primary group-hover:bg-primary/5 transition-all">
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-full text-zinc-400 group-hover:text-primary transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
            </div>
            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100" id="excel-filename">Excel dosyasını buraya sürükleyin veya tıklayın</p>
            <p class="text-xs text-zinc-500">Maksimum 5MB, .xlsx veya .csv formatında</p>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-800">
        <div class="flex-1 pr-4">
            <label for="update-wages-toggle" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 cursor-pointer">Dosyadaki ücretleri güncelleyerek kullan</label>
            <p class="text-[11px] text-zinc-500 mt-1 leading-relaxed">Eğer unvan, öğrenim ve kıdem eşleşirse, dosyadaki ücreti sistemdeki tanıma kaydeder.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer shrink-0">
            <input type="checkbox" id="update-wages-toggle" class="sr-only peer">
            <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-zinc-900 dark:peer-checked:bg-zinc-100"></div>
        </label>
      </div>
    </section>

    <footer class="mt-8">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" onclick="startImport()" class="btn" id="btn-start-import" disabled>Yüklemeyi Başlat</button>
    </footer>
  </div>
</dialog>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.3.0/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<link rel="stylesheet" href="<?php echo routeUrl('assets/css/contract-document.css'); ?>">


<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<style>
    #dialog-petition {
        max-width: 1550px !important;
        width: 95vw !important;
        height: 94vh !important;
        max-height: none !important;
    }
    #dialog-petition .dialog-content {
        max-width: 1550px !important;
        width: 100% !important;
        height: 100% !important;
    }
    #petition-quill-editor .ql-editor {
        font-family: "Times New Roman", Times, serif !important;
        font-size: 11pt !important;
        line-height: 1.6 !important;
        padding: 1.5cm 4.5cm !important;
        text-align: justify;
    }

    .contract-preview-dialog {
        max-width: none !important;
        padding: 0 !important;
    }

    /* Fix Flatpickr z-index and modal clipping */
    .flatpickr-calendar {
        z-index: 99999 !important;
    }

    .filter-rule-row .datepicker + .flatpickr-calendar {
        position: absolute !important;
    }

    /* DataTables Sorting Icon on Left - Fixed & Aggressive Hide */
    table.dataTable thead th.sorting,
    table.dataTable thead th.sorting_asc,
    table.dataTable thead th.sorting_desc {
        background-image: none !important;
        position: relative;
        padding-left: 36px !important;
        padding-right: 8px !important; /* Reset default padding-right for icons */
        cursor: pointer;
    }

    /* Aggressively hide DataTables 1.x and 2.x default icons */
    table.dataTable thead th.sorting::before,
    table.dataTable thead th.sorting_asc::before,
    table.dataTable thead th.sorting_desc::before,
    table.dataTable thead th.sorting::after,
    table.dataTable thead th.sorting_asc::after,
    table.dataTable thead th.sorting_desc::after {
        content: none !important;
        display: none !important;
        background-image: none !important;
    }

    /* Inject our Custom Sort Icon using a pseudo-element that we fully control */
    /* We'll use a specific selector to ensure it's not hidden by the rule above */
    table.dataTable thead th.sorting,
    table.dataTable thead th.sorting_asc,
    table.dataTable thead th.sorting_desc {
        &::before {
            display: block !important;
            content: "" !important;
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.25;
            transition: all 0.2s ease;
        }
    }

    /* Default Arrow-Down-Up (Lucide style) */
    table.dataTable thead th.sorting::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m3 16 4 4 4-4'/%3E%3Cpath d='M7 20V4'/%3E%3Cpath d='m21 8-4-4-4 4'/%3E%3Cpath d='M17 4v16'/%3E%3C/svg%3E") !important;
    }

    /* Active States - Using matching Lucide arrows */
    table.dataTable thead th.sorting_asc::before {
        opacity: 1 !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%236366f1' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m5 12 7-7 7 7'/%3E%3Cpath d='M12 19V5'/%3E%3C/svg%3E") !important;
        transform: translateY(-50%) scale(1.1);
    }

    table.dataTable thead th.sorting_desc::before {
        opacity: 1 !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%236366f1' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m19 12-7 7-7-7'/%3E%3Cpath d='M12 5v14'/%3E%3C/svg%3E") !important;
        transform: translateY(-50%) scale(1.1);
    }

    table.dataTable thead th.no-sort::before {  
        display: none !important;
    }

    table.dataTable td.dataTables_empty {
        text-align: center !important;
        padding: 0 !important;
    }
</style>



<script>
$(document).ready(function() {
    if (typeof initDataTable !== 'function') {
        console.error('initDataTable function not found!');
        $('#table-preloader').hide();
        $('#table-container').show();
        return;
    }

    const table = initDataTable('#personnelTable', {
        serverSide: true,
        processing: true,
        ajax: {
            url: '<?php echo routeUrl("personel-datatable"); ?>',
            type: 'POST',
            data: function(d) {
                d.columnFilterState = $('#personnelTable').data('columnFilterState');
            }
        },
        columns: [
            { 
                data: 'id', 
                className: 'px-2 text-center',
                orderable: false,
                render: (data) => '<input type="checkbox" class="input">' 
            },
            { 
                data: null, 
                className: 'px-0 text-center',
                orderable: false,
                render: (data, type, row) => {
                    const rowData = JSON.stringify(row).replace(/'/g, "&#39;");
                    return `
                    <div class="flex items-center justify-center gap-1">
                        <button onclick='printContract(${row.id})' class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer" title="Sözleşme Yazdır">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-printer"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        </button>
                        ${row.durum !== 'kadroya_gecti' ? `
                        <button onclick='openPetitionModal(${rowData})' class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer" title="Dilekçe Yazdır">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </button>
                        ` : ''}
                    </div>`;
                }
            },
            { 
                data: 'ad_soyad',
                className: 'cursor-pointer font-medium text-zinc-900 dark:text-zinc-100 min-w-[120px]',
                render: (data, type, row) => `
                    <button onclick="editPersonnel(${row.id})" class="cursor-pointer hover:text-primary transition-colors text-left whitespace-nowrap">
                        ${data}
                    </button>`
            },
            { data: 'tc_kimlik', className: 'text-zinc-600 dark:text-zinc-400' },
            { 
                data: 'cinsiyet',
                className: 'text-zinc-600 dark:text-zinc-400',
                render: (data) => {
                    const val = data || 'erkek';
                    return val.charAt(0).toUpperCase() + val.slice(1);
                }
            },
            { 
                data: 'unvan',
                render: (data) => `
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400">
                        ${data || 'Belirtilmemiş'}
                    </span>`
            },
            { data: 'ogrenim', className: 'text-zinc-600 dark:text-zinc-400', defaultContent: 'Belirtilmemiş' },
            { 
                data: 'ucret',
                className: 'text-zinc-600 dark:text-zinc-100 font-semibold',
                render: (data) => data ? new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2 }).format(data) + ' ₺' : '-'
            },
            { 
                data: 'durum',
                render: (data) => {
                    const status = data || 'aktif';
                    if (status === 'aktif') return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border border-green-100 dark:border-green-900/30 text-green-700 dark:text-green-400"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Aktif</span>`;
                    if (status === 'pasif') return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>Pasif</span>`;
                    if (status === 'dilekce_alindi') return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border border-blue-100 dark:border-blue-900/30 text-blue-700 dark:text-blue-400"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Dilekçe Alındı</span>`;
                    if (status === 'kadroya_gecti') return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border border-indigo-100 dark:border-indigo-900/30 text-indigo-700 dark:text-indigo-400"><span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>Kadroya Geçti</span>`;
                    if (status === 'kadroya_gecmeyecek') return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border border-orange-100 dark:border-orange-900/30 text-orange-700 dark:text-orange-400"><span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>Kadroya Geçmeyecek</span>`;
                    return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>${status}</span>`;
                }
            },
            { 
                data: 'goreve_baslama_tarihi',
                className: 'text-zinc-600 dark:text-zinc-400',
                render: (data) => data ? new Date(data).toLocaleDateString('tr-TR') : '-'
            },
            { 
                data: 'goreve_baslama_tarihi',
                render: (data) => {
                    if (!data) return '<span class="text-zinc-400">-</span>';
                    const date = new Date(data);
                    date.setFullYear(date.getFullYear() + 3);
                    const today = new Date();
                    const isPast = date < today;
                    const badgeClass = isPast 
                        ? 'bg-green-50 text-green-700 border-green-100 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20' 
                        : 'bg-orange-50 text-orange-700 border-orange-100 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20';
                    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ${badgeClass}">${date.toLocaleDateString('tr-TR')}</span>`;
                }
            },
            { 
                data: 'ayrilma_tarihi',
                className: 'text-zinc-600 dark:text-zinc-400',
                render: (data) => data ? new Date(data).toLocaleDateString('tr-TR') : '-'
            },
            { data: 'telefon', className: 'text-zinc-600 dark:text-zinc-400', defaultContent: '-' },
            {
                data: null,
                className: 'text-right',
                orderable: false,
                render: (data, type, row) => {
                    const rowData = JSON.stringify(row).replace(/'/g, "&#39;");
                    return `
                    <div class="relative inline-block text-left group-dropdown">
                        <button type="button" onclick="toggleDropdown(this)" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-zinc-400 transition-colors cursor-pointer" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                        </button>
                        <div class="app-dropdown-menu absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg z-50 hidden opacity-0 translate-y-[-10px] transition-all">
                            <div class="py-1">
                                <button onclick="editPersonnel(${row.id})" class="flex items-center w-full px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    Düzenle
                                </button>
                                <button onclick="printContract(${row.id})" class="flex items-center w-full px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                    Sözleşme Yazdır
                                </button>
                                <button onclick='openPetitionModal(${rowData})' class="flex items-center w-full px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    Dilekçe Yazdır
                                </button>
                                <hr class="my-1 border-zinc-100 dark:border-zinc-800">
                                <button onclick="deletePersonnel(${row.id})" class="flex items-center w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    Sil
                                </button>
                            </div>
                        </div>
                    </div>`;
                }
            }
        ],
        order: [[2, 'asc']],
        dom: '<"flex-1 overflow-auto"rt><"mt-auto border-t border-zinc-200 dark:border-zinc-800 flex flex-row justify-between items-center py-0 px-4 bg-zinc-50/50 dark:bg-zinc-800/30"lip>',
        preloader: '#table-preloader'
    });

    window.personnelTable = table;

    // URL'den gelen filtreleri işle (Kadro Dolmuş Kısayolu)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('filter') === 'kadro_dolmus') {
        // 1. Durum = Aktif (Kolon 8)
        table.column(8).search('Aktif', false, true); 
        
        // 2. Kadroya Geçiş <= Bugün (Kolon 10)
        const today = new Date();
        const dd = String(today.getDate()).padStart(2, '0');
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const yyyy = today.getFullYear();
        const todayStr = `${dd}.${mm}.${yyyy}`;
        
        const columnFilterState = table.table().node().getAttribute('data-columnFilterState') || '{}';
        const stateObj = JSON.parse(columnFilterState);
        stateObj[10] = { 
            match: 'all', 
            rules: [{ operator: 'lte', value: todayStr, type: 'date' }] 
        };
        $(table.table().node()).data('columnFilterState', stateObj);
        
        table.draw();
    }


    $('[onclick*="dialog-add-personnel"]').on('click', function() {
        <?php if (empty($ucretler)): ?>
        setTimeout(() => {
            showToast({
                category: 'warning',
                title: 'Uyarı',
                description: 'Sistemde herhangi bir ücret tanımı yok. Personel eklemeden önce ücret tanımlamalısınız.'
            });
        }, 50);
        <?php endif; ?>

        $('#form-add-personnel')[0].reset();
        $('#add_ucret_id').val('');
        $('#select-add-ucret-trigger span').text('Ücret tanımı seçin...');
        $('[data-custom-popover]').attr('aria-hidden', 'true');
        
        // Göreve başlama varsayılan bugün
        const today = new Date();
        const dd = String(today.getDate()).padStart(2, '0');
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const yyyy = today.getFullYear();
        $('#goreve_baslama_tarihi').val(`${dd}.${mm}.${yyyy}`);
    });

    // Datepicker initialization
    $('.datepicker').flatpickr({
        dateFormat: 'd.m.Y',
        locale: 'tr',
        allowInput: true,
        disableMobile: true,
        static: true
    });

    $('.app-select-rich header input').on('keyup', function() {
        const searchText = $(this).val().toLowerCase().trim();
        const searchWords = searchText.split(/\s+/);
        const $listbox = $(this).closest('.app-select-rich').find('[data-select-option]').parent();
        
        $listbox.find('[data-select-option]').each(function() {
            const text = $(this).text().toLowerCase();
            const matchesAll = searchWords.every(word => text.includes(word));
            $(this).toggle(matchesAll);
        });
    });

    $('#form-add-personnel').on('submit', function(e) {
        e.preventDefault();
        
        const ucretId = $('#add_ucret_id').val();
        if (!ucretId) {
            showToast({
                category: 'warning',
                title: 'Uyarı',
                description: 'Lütfen bir ücret tanımı seçin.'
            });
            return;
        }

        const formData = $(this).serialize();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                if (response.success) {
                    showToast({ 
                        category: 'success', 
                        title: 'Başarılı', 
                        description: 'Yeni personel başarıyla eklendi.',
                        duration: 2000,
                        onClose: () => location.reload()
                    });
                    document.getElementById('dialog-add-personnel').close();
                    setTimeout(() => location.reload(), 2100);
                } else {
                    showToast({ category: 'error', title: 'Hata', description: 'Ekleme sırasında bir hata oluştu.' });
                }
            }
        });
    });

    // Initial check on page load
    checkActiveFiltersForTable($(table.table().node()));
});

// Gender-based template preprocessor for petitions
function processGenderTemplate(templateHtml, gender) {
    if (!templateHtml) return '';
    const isFemale = gender && gender.toLowerCase() === 'kadin';
    if (!isFemale) return templateHtml; // If male, keep exactly as is

    // Use a temp DOM parser to safely edit elements
    const parser = new DOMParser();
    const doc = parser.parseFromString(templateHtml, 'text/html');
    
    // 1. Identify and remove any node containing "askerlik" or "terhis"
    const elements = doc.body.querySelectorAll('p, div, li, span');
    elements.forEach(el => {
        const text = el.textContent.toLowerCase();
        if (text.includes('askerlik') || text.includes('terhis')) {
            el.remove();
        }
    });

    // 2. Renumber remaining numbering (e.g. 1-, 2-, 3-, 4-) sequentially starting from 1
    const remainingElements = doc.body.querySelectorAll('p, div, li, span');
    let index = 1;
    remainingElements.forEach(el => {
        const txt = el.textContent.trim();
        // Match numbers like "3-", "3.", "3 -", "3. "
        const textMatch = txt.match(/^(\d+)\s*([-.]+)\s*(.*)/);
        if (textMatch) {
            // Retrieve exact punctuation (- or .)
            const punct = textMatch[2];
            // Replace starting number in the actual HTML of the element
            el.innerHTML = el.innerHTML.replace(/^\s*\d+\s*([-.]+)\s*/, index + punct + ' ');
            index++;
        }
    });

    return doc.body.innerHTML;
}

// Dropdown Mantığı
function toggleDropdown(btn) {
    if (window.event) window.event.stopPropagation();
    const dropdown = btn.nextElementSibling;
    const isHidden = dropdown.classList.contains('hidden');
    
    $('.app-dropdown-menu').addClass('hidden').removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-[-10px]').removeAttr('style');
    
    if (isHidden) {
        dropdown.classList.remove('hidden');
        setTimeout(() => {
            dropdown.classList.add('opacity-100', 'translate-y-0');
            dropdown.classList.remove('opacity-0', 'translate-y-[-10px]');
        }, 10);
    }
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('.group-dropdown').length) {
        $('.app-dropdown-menu').addClass('hidden').removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-[-10px]');
    }
    if (!$(e.target).closest('.app-select-rich').length) {
        $('[data-custom-popover]').attr('aria-hidden', 'true');
        $('.app-select-rich button').attr('aria-expanded', 'false');
    }
});

function scalePreview() {
    const viewport = document.getElementById('contract-preview-viewport');
    const wrapper = document.getElementById('contract-preview-wrapper');
    const content = document.getElementById('contract-preview-content');
    if (!viewport || !wrapper || !content) return;

    // Reset scale to measure real size
    wrapper.style.transform = 'scale(1)';
    
    const vpW = viewport.clientWidth - 48; // padding
    const vpH = viewport.clientHeight - 48;
    const pageW = content.offsetWidth;
    const pageH = content.offsetHeight;

    const scaleX = vpW / pageW;
    const scaleY = vpH / pageH;
    const scale = Math.min(scaleX, scaleY, 1); // never zoom in beyond 100%

    wrapper.style.transform = `scale(${scale})`;
    wrapper.style.width = pageW + 'px';
    wrapper.style.height = (pageH * scale) + 'px';
}

function getContractPrintStyles() {
    return `
       @page { size: A4 portrait; margin: 0 !important; }
                        * { box-sizing: border-box !important; }
                        
                        html, body { 
                            margin: 0 !important; 
                            padding: 0 !important; 
                            background: #f3f4f6;
                            font-family: 'Times New Roman', Times, serif;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                        
                        .page-container { 
                            width: 210mm !important; 
                            height: auto !important; 
                            min-height: 297mm !important;
                            padding: 0cm 1.5cm 0cm !important;
                            margin: 0 auto !important; 
                            background: white !important; 
                            position: relative !important;
                            box-sizing: border-box !important;
                            overflow: visible !important;
                        }
                        
                        @media print {
                            @page { size: A4 portrait; margin: 0 !important; }
                            html, body { 
                                margin: 0 !important; 
                                padding: 0 !important; 
                                background: white !important;
                                width: 210mm !important;
                                height: auto !important;
                                overflow: visible !important;
                            }
                            .page-container { 
                                margin: 0 auto !important; 
                                box-shadow: none !important; 
                                width: 210mm !important;
                                height: auto !important;
                                min-height: 297mm !important;
                                max-height: none !important;
                                overflow: visible !important;
                            }
                        }
    `;
}

function downloadContractAsWord(id) {
    window.location.href = '<?php echo routeUrl("personel-download-word"); ?>?id=' + id;
}

let petitionQuill = null;
let currentPetitionPersonnel = null;

function openPetitionModal(p) {
    closeAllDropdowns();
    currentPetitionPersonnel = p;
    const dialog = document.getElementById('dialog-petition');
    
    // Initialize Quill once
    if (!petitionQuill) {
        petitionQuill = new Quill('#petition-quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['clean']
                ]
            }
        });
    }

    const today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const yyyy = today.getFullYear();
    const todayStr = dd + '.' + mm + '.' + yyyy;

    const baslamaStr = p.goreve_baslama_tarihi 
        ? new Date(p.goreve_baslama_tarihi).toLocaleDateString('tr-TR') 
        : '...................................................';

    const unvanStr = p.unvan || '...................................................';
    const telefonStr = p.telefon || '...................................................';
    const adSoyadStr = p.ad_soyad || '...................................................';
    const tcKimlikStr = p.tc_kimlik || '...................................................';

    const cinsiyet = p.cinsiyet ? p.cinsiyet.toLowerCase() : 'erkek';

    const defaultContent = 
        '<p style="text-align: center; font-size: 11pt; margin-bottom: 2pt;"><strong>DÜZCE ÜNİVERSİTESİ REKTÖRLÜĞÜNE</strong></p>' +
        '<p style="text-align: center; font-size: 11pt; margin-bottom: 12pt;">(...................................................................)</p>' +
        '<p><br></p>' +
        '<p style="text-indent: 1.5cm; text-align: justify; margin-bottom: 12pt;">Üniversiteniz ................................................................... biriminde, 657 sayılı Devlet Memurları Kanunu\'nun 4/B maddesi uyarınca <strong>' + unvanStr + '</strong> pozisyonunda sözleşmeli personel olarak <strong>' + baslamaStr + '</strong> tarihinden itibaren görev yapmaktayım.</p>' +
        '<p style="text-indent: 1.5cm; text-align: justify; margin-bottom: 12pt;">26 Ocak 2023 tarih ve 32085 sayılı Resmi Gazete\'de yayınlanan 7433 sayılı "<em>Devlet Memurları Kanunu ve Bazı Kanunlar ile 663 Sayılı Kanun Hükmünde Kararnamelerde Değişiklik Yapılmasına Dair Kanun</em>" ile 657 sayılı Devlet Memurları Kanununa eklenen "<em>...Bu kapsamda istihdam edilen sözleşmeli personelden aynı kurumda üç yıllık çalışma süresini tamamlayanlar bu sürenin bitiminden itibaren otuz gün içinde talepte bulunmaları hâlinde bulundukları yerde aynı unvanlı memur kadrolarına atanır.</em>" hükmü gereğince çalışmakta olduğum pozisyona uygun bir kadroya atanmak istiyorum. Atamaya esas kullanılmak üzere gereken belgeler dilekçemin ekinde mevcuttur.</p>' +
        '<p style="text-indent: 1.5cm; text-align: justify; margin-bottom: 24pt;">Gereğinin yapılmasını müsaadelerinizi arz ederim. ' + todayStr + '</p>' +
        '<p style="text-align: right; margin-bottom: 24pt;"><strong>' + adSoyadStr + ' / ' + tcKimlikStr + ' / İMZA</strong></p>' +
        '<p style="margin-bottom: 4pt;"><strong><u>EK:</u></strong></p>' +
        '<p style="margin-bottom: 4pt;">1- Nüfus Cüzdanı Fotokopisi</p>' +
        '<p style="margin-bottom: 4pt;">2- Son öğrenim durumunu gösterir diploma aslı ve fotokopisi veya Mezun Belgesi (güncel e-devlet çıktısı)</p>' +
        '<p style="margin-bottom: 4pt;">3- Askerlik Durum Belgesi (güncel e-devlet çıktısı) / Askerliğini yapanlar için Terhis Belgesi aslı ve fotokopisi,</p>' +
        '<p style="margin-bottom: 12pt;">4- Tam teşekküllü devlet hastanesi ya da Üniversite hastanesinden alınacak sağlık kurulu (heyet) raporu (aslı ve fotokopisi ya da e-devlet çıktısı)</p>' +
        '<p style="margin-bottom: 8pt;"><strong><u>ADRES:</u></strong> ...................................................................</p>' +
        '<p style="margin-bottom: 8pt;"><strong><u>TEL:</u></strong> ' + telefonStr + '</p>';

    let customTemplate = `<?php echo addslashes(str_replace(["\r", "\n"], '', $custom_petition)); ?>`;
    let processedTemplate = customTemplate ? customTemplate : defaultContent;
    
    // NOTE: We do NOT apply gender preprocessing here so that the Master template
    // containing the Askerlik clause remains fully editable and savable by everyone.

    // Replace tokens
    processedTemplate = processedTemplate
        .replace(/\{\{UNVAN\}\}/g, unvanStr)
        .replace(/\{\{AD_SOYAD\}\}/g, adSoyadStr)
        .replace(/\{\{TC_NO\}\}/g, tcKimlikStr)
        .replace(/\{\{GOREVE_BASLAMA\}\}/g, baslamaStr)
        .replace(/\{\{TELEFON\}\}/g, telefonStr)
        .replace(/\{\{TODAY\}\}/g, todayStr);

    petitionQuill.root.innerHTML = processedTemplate;
    dialog.showModal();
}

function printPetition() {
    if (!petitionQuill || !currentPetitionPersonnel) return;
    let content = petitionQuill.root.innerHTML;

    // Apply gender preprocessing to strip military clauses only during print generation
    const cinsiyet = currentPetitionPersonnel.cinsiyet ? currentPetitionPersonnel.cinsiyet.toLowerCase() : 'erkek';
    content = processGenderTemplate(content, cinsiyet);

    const printWindow = window.open('', '_blank');
    const name = currentPetitionPersonnel.ad_soyad || 'Personel';
    
    let html = '<!DOCTYPE html>' +
        '<html lang="tr">' +
        '<head>' +
        '<meta charset="UTF-8">' +
        '<title>Dilekçe - ' + name + '</title>' +
        '<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">' +
        '<link rel="stylesheet" href="<?php echo routeUrl("assets/css/contract-document.css"); ?>">' +
        '<style>' +
        '@page { size: A4 portrait; margin: 4.5cm 2cm 2.5cm 2cm !important; }' +
        '* { box-sizing: border-box !important; }' +
        'body { ' +
        'font-family: "Times New Roman", Times, serif !important; ' +
        'margin: 0 !important; ' +
        'padding: 0 !important; ' +
        'background: white !important;' +
        '-webkit-print-color-adjust: exact !important;' +
        'print-color-adjust: exact !important;' +
        '}' +
        'p, div, span, strong, em, li {' +
        'font-family: "Times New Roman", Times, serif !important;' +
        'font-size: 11pt !important;' +
        'line-height: 1.6 !important;' +
        '}' +
        '.ql-editor {' +
        'padding: 0 !important;' +
        'font-family: "Times New Roman", Times, serif !important;' +
        'font-size: 11pt !important;' +
        'line-height: 1.6 !important;' +
        'text-align: justify;' +
        '}' +
        '.ql-editor p {' +
        'padding-left: 2.3cm !important;' +
        'padding-right: 2.3cm !important;' +
        '}' +
        '.ql-editor p:nth-child(1), .ql-editor p:nth-child(2), .ql-editor p:nth-child(3) {' +
        'padding-left: 0 !important;' +
        'padding-right: 0 !important;' +
        '}' +
        '</style>' +
        '</head>' +
        '<body>' +
        '<div class="ql-container ql-snow" style="border:none">' +
        '<div class="ql-editor">' +
        content +
        '</div>' +
        '</div>' +
        '<script>' +
        'window.onload = function() {' +
        'setTimeout(function() {' +
        'window.print();' +
        'window.close();' +
        '}, 500);' +
        '};' +
        '<\/script>' +
        '</body>' +
        '</html>';

    printWindow.document.write(html);
    printWindow.document.close();
}

function showPetitionStatusConfirmation() {
    if (!petitionQuill || !currentPetitionPersonnel) return;
    const name = currentPetitionPersonnel.ad_soyad || 'Personel';
    document.getElementById('confirm-personnel-name').innerText = name;
    document.getElementById('dialog-confirm-petition-status').showModal();
}

function proceedWithPrint() {
    const dialog = document.getElementById('dialog-confirm-petition-status');
    if (dialog && dialog.open) {
        dialog.close();
    }
    printPetition();
}

function updateStatusAndPrint() {
    if (!currentPetitionPersonnel) return;
    
    const btn = document.getElementById('btn-confirm-petition-status');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Güncelleniyor...';
    
    const formData = {
        id: currentPetitionPersonnel.id,
        tc_kimlik: currentPetitionPersonnel.tc_kimlik,
        ad_soyad: currentPetitionPersonnel.ad_soyad,
        ucret_id: currentPetitionPersonnel.ucret_id,
        durum: 'dilekce_alindi',
        goreve_baslama_tarihi: currentPetitionPersonnel.goreve_baslama_tarihi,
        telefon: currentPetitionPersonnel.telefon || '',
        meslek_kodu: currentPetitionPersonnel.meslek_kodu || '',
        cinsiyet: currentPetitionPersonnel.cinsiyet || 'erkek'
    };
    
    $.post('<?php echo routeUrl("personel-guncelle"); ?>', formData, function(response) {
        btn.disabled = false;
        btn.innerText = originalText;
        
        if (response.success) {
            showToast({ 
                category: 'success', 
                title: 'Başarılı', 
                description: 'Personel durumu "Dilekçe Alındı" olarak güncellendi.', 
                duration: 2000 
            });
            
            if (window.personnelTable) {
                window.personnelTable.ajax.reload(null, false);
            }
            
            proceedWithPrint();
        } else {
            showToast({ 
                category: 'error', 
                title: 'Hata', 
                description: response.error || 'Durum güncellenirken bir sorun oluştu.', 
                duration: 2500 
            });
        }
    }).fail(function() {
        btn.disabled = false;
        btn.innerText = originalText;
        showToast({ 
            category: 'error', 
            title: 'Hata', 
            description: 'Sunucu ile iletişim kurulurken bir sorun oluştu.', 
            duration: 2500 
        });
    });
}

function savePetitionTemplate() {
    if (!petitionQuill || !currentPetitionPersonnel) return;
    let content = petitionQuill.root.innerHTML;
    const p = currentPetitionPersonnel;
    const baslamaStr = p.goreve_baslama_tarihi 
        ? new Date(p.goreve_baslama_tarihi).toLocaleDateString('tr-TR') 
        : '...................................................';
    const unvanStr = p.unvan || '...................................................';
    const telefonStr = p.telefon || '...................................................';
    const adSoyadStr = p.ad_soyad || '...................................................';
    const tcKimlikStr = p.tc_kimlik || '...................................................';

    const today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const yyyy = today.getFullYear();
    const todayStr = dd + '.' + mm + '.' + yyyy;

    // Replace current details back with tokens
    let savedContent = content
        .replaceAll(unvanStr, '{{UNVAN}}')
        .replaceAll(adSoyadStr, '{{AD_SOYAD}}')
        .replaceAll(tcKimlikStr, '{{TC_NO}}')
        .replaceAll(baslamaStr, '{{GOREVE_BASLAMA}}')
        .replaceAll(telefonStr, '{{TELEFON}}')
        .replaceAll(todayStr, '{{TODAY}}');

    $.ajax({
        url: '<?php echo routeUrl("tanimlamalar"); ?>',
        method: 'POST',
        data: {
            custom_petition_template: savedContent
        },
        success: function() {
            showToast({
                category: 'success',
                title: 'Başarılı',
                description: 'Şablon başarıyla varsayılan olarak kaydedildi.',
                duration: 2500
            });
        },
        error: function() {
            showToast({
                category: 'error',
                title: 'Hata',
                description: 'Kaydedilirken bir sorun oluştu.',
                duration: 2500
            });
        }
    });
}

function printContract(id) {
    closeAllDropdowns();
    showToast({ 
        category: 'info', 
        title: 'Hazırlanıyor', 
        description: 'Sözleşme yazdırma için hazırlanıyor...',
        duration: 1500
    });
    
    $.ajax({
        url: '<?php echo routeUrl("personel-get-preview"); ?>',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                const content = response.content;
                const hasBorder = response.has_border;
                
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html lang="tr">
                        <head>
                            <meta charset="UTF-8">
                            <title>Sözleşme - ${response.personnel_name}</title>
                            <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                            <link rel="stylesheet" href="<?php echo routeUrl('assets/css/contract-document.css'); ?>">
                            <style>${getContractPrintStyles()}</style>
                        </head>
                        <body>
                            <div class="page-container contract-document ${hasBorder ? 'has-border' : ''}">
                                <div class="ql-container ql-snow" style="border:none">
                                    <div class="ql-editor" id="print-content">
                                        ${content}
                                    </div>
                                </div>
                            </div>
                            <script>
                                window.onload = function() {
                                    setTimeout(() => {
                                        window.print();
                                        window.close();
                                    }, 800);
                                };
                            <\/script>
                        </body>
                    </html>
                `);
                printWindow.document.close();
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.error || 'Hata oluştu.' });
            }
        },
        error: function() {
            showToast({ category: 'error', title: 'Hata', description: 'Sunucu hatası oluştu.' });
        }
    });
}

function previewContract(id) {
    const dialog = document.getElementById('dialog-preview-contract');
    const container = document.getElementById('contract-preview-content');
    
    container.innerHTML = `
        <div style="display:flex; align-items:center; justify-content:center; min-height:600px; color:#a1a1aa;">
            <div style="display:flex; flex-direction:column; align-items:center; gap:10px;">
                <svg class="animate-spin" style="width:32px; height:32px; color:#4f46e5;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Sözleşme hazırlanıyor...</span>
            </div>
        </div>
    `;
    
    dialog.showModal();

    $.ajax({
        url: '<?php echo routeUrl("personel-get-preview"); ?>',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                container.innerHTML = `
                    <div class="ql-container ql-snow" style="border:none">
                        <div class="ql-editor">${response.content}</div>
                    </div>
                `;
                if (response.has_border) {
                    container.classList.add('has-border');
                } else {
                    container.classList.remove('has-border');
                }
                document.getElementById('preview-personnel-name').innerText = response.personnel_name;
                // Wait for content to render, then scale
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => scalePreview());
                });
            } else {
                container.innerHTML = `<div style="padding:40px; text-align:center; color:#ef4444;">${response.error || 'Hata oluştu.'}</div>`;
            }
        },
        error: function() {
            container.innerHTML = `<div style="padding:40px; text-align:center; color:#ef4444;">Sunucu hatası oluştu.</div>`;
        }
    });
}

// Re-scale on window resize
window.addEventListener('resize', function() {
    const dialog = document.getElementById('dialog-preview-contract');
    if (dialog && dialog.open) {
        scalePreview();
    }
});

function printPreview() {
    const container = document.getElementById('contract-preview-content');
    if (!container) return;
    const editor = container.querySelector('.ql-editor');
    const content = editor ? editor.innerHTML : container.innerHTML;
    const name = document.getElementById('preview-personnel-name').innerText;
    const hasBorder = container.classList.contains('has-border');
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="tr">
            <head>
                <meta charset="UTF-8">
                <title>Sözleşme - ${name}</title>
                <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                <link rel="stylesheet" href="<?php echo routeUrl('assets/css/contract-document.css'); ?>">
                <style>${getContractPrintStyles()}</style>
            </head>
            <body>
                <div class="page-container contract-document ${hasBorder ? 'has-border' : ''}">
                    <div class="ql-container ql-snow" style="border:none">
                        <div class="ql-editor">
                            ${content}
                        </div>
                    </div>
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(() => {
                            window.print();
                            window.close();
                        }, 800);
                    };
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

function triggerAiScan() { document.getElementById('ai-scan-input').click(); }

function processAiScan(input) {
    if (!input.files || !input.files[0]) return;
    const formData = new FormData();
    formData.append('image', input.files[0]);
    $('#ai-loading').removeClass('hidden');
    $.ajax({
        url: '<?php echo routeUrl("personel-ai-scan"); ?>',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#ai-loading').addClass('hidden');
            if (response.success && response.data) {
                const data = response.data;
                $('#tc_kimlik').val(data.tc_kimlik);
                $('#ad_soyad').val(data.ad_soyad);
                $('#telefon').val(data.telefon);
                showToast({ category: 'success', title: 'AI Tarama Başarılı', description: 'Bilgiler yerleştirildi.' });
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.error || 'Bilgiler çözümlenemedi.' });
            }
        },
        error: function() { $('#ai-loading').addClass('hidden'); }
    });
    input.value = '';
}

function toggleSelectRich(btn) {
    event.stopPropagation();
    var $popover = $(btn).closest('.app-select-rich').find('[data-custom-popover]');
    var isHidden = $popover.attr('aria-hidden') === 'true';
    $('[data-custom-popover]').attr('aria-hidden', 'true');
    if (isHidden) {
        $popover.attr('aria-hidden', 'false');
        setTimeout(() => $popover.find('input').focus(), 50);
    }
}

function selectRichOption(el) {
    var $el = $(el);
    var value = $el.data('value');
    var $select = $el.closest('.app-select-rich');
    
    var unvan = $el.find('.font-bold.text-sm').first().text().trim();
    var ogrenim = $el.find('.text-\\[11px\\]').text().trim();
    var ucret = $el.find('.text-primary').text().trim();
    var kidem = $el.find('.text-\\[10px\\]').text().trim().replace(/\s*KIDEM$/i, '');
    
    $select.find('input[type="hidden"]').val(value);
    $select.find('button span').text(unvan + ' | ' + ogrenim + ' | ' + kidem + ' | ' + ucret);
    
    // Seçili sınıfını yönet
    $select.find('[data-select-option]').removeClass('selected');
    $el.addClass('selected');
    
    $select.find('[data-custom-popover]').attr('aria-hidden', 'true');
    $select.find('button').attr('aria-expanded', 'false');
}

function editPersonnel(id) {
    closeAllDropdowns();
    $.get('<?php echo routeUrl("personel-get"); ?>', { id: id }, function(data) {
        if (data.error) return;
        $('#edit_id').val(data.id);
        $('#edit_tc_kimlik').val(data.tc_kimlik);
        $('#edit_ad_soyad').val(data.ad_soyad);
        
        // Tarih formatını ayarla
        if (data.goreve_baslama_tarihi) {
            const dateInput = document.getElementById('edit_goreve_baslama_tarihi');
            if (dateInput._flatpickr) {
                dateInput._flatpickr.setDate(data.goreve_baslama_tarihi);
            } else {
                $('#edit_goreve_baslama_tarihi').val(data.goreve_baslama_tarihi);
            }
        }

        if (data.ayrilma_tarihi) {
            const dateInputAyrilma = document.getElementById('edit_ayrilma_tarihi');
            if (dateInputAyrilma._flatpickr) {
                dateInputAyrilma._flatpickr.setDate(data.ayrilma_tarihi);
            } else {
                $('#edit_ayrilma_tarihi').val(data.ayrilma_tarihi);
            }
        } else {
            const dateInputAyrilma = document.getElementById('edit_ayrilma_tarihi');
            if (dateInputAyrilma._flatpickr) {
                dateInputAyrilma._flatpickr.clear();
            } else {
                $('#edit_ayrilma_tarihi').val('');
            }
        }
        
        
        const statusMap = {
            'aktif': 'Aktif',
            'pasif': 'Pasif',
            'dilekce_alindi': 'Dilekçe Alındı',
            'kadroya_gecti': 'Kadroya Geçti',
            'kadroya_gecmeyecek': 'Kadroya Geçmeyecek'
        };
        $('#edit-durum-value').val(data.durum).trigger('change');
        $('#edit-durum-trigger span').text(statusMap[data.durum] || 'Aktif');
        $('#edit-durum-listbox [role="option"]').removeClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
        $('#edit-durum-listbox .check-icon').addClass('hidden');
        const $durumOpt = $(`#edit-durum-listbox [data-value="${data.durum}"]`);
        $durumOpt.addClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
        $durumOpt.find('.check-icon').removeClass('hidden');

        let cinsiyet = data.cinsiyet ? data.cinsiyet.toLowerCase() : 'erkek';
        if (cinsiyet !== 'erkek' && cinsiyet !== 'kadin') cinsiyet = 'erkek';
        $('#edit-cinsiyet-value').val(cinsiyet).trigger('change');
        $('#edit-cinsiyet-trigger span').text(cinsiyet === 'erkek' ? 'Erkek' : 'Kadın');
        $('#edit-cinsiyet-listbox [role="option"]').removeClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
        $('#edit-cinsiyet-listbox .check-icon').addClass('hidden');
        const $cinsOpt = $(`#edit-cinsiyet-listbox [data-value="${cinsiyet}"]`);
        $cinsOpt.addClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
        $cinsOpt.find('.check-icon').removeClass('hidden');

        $('#edit_telefon').val(data.telefon);
        $('#edit_meslek_kodu').val(data.meslek_kodu);
        
        // Ücret bilgisini set et
        $('#edit_ucret_id').val(data.ucret_id);
        const $option = $(`#select-edit-ucret-listbox [data-value="${data.ucret_id}"]`);
        if ($option.length) {
            const unvan = $option.find('.font-bold.text-sm').first().text().trim();
            const ogrenim = $option.find('.text-\\[11px\\]').text().trim();
            const ucret = $option.find('.text-primary').text().trim();
            const kidem = $option.find('.text-\\[10px\\]').text().trim().replace(/\s*KIDEM$/i, '');
            $('#select-edit-ucret-trigger span').text(unvan + ' | ' + ogrenim + ' | ' + kidem + ' | ' + ucret);
            
            // Listede seçili hale getir
            $('#select-edit-ucret-listbox [data-select-option]').removeClass('selected');
            $option.addClass('selected');
        } else if (data.unvan && data.ucret) {
            const formattedUcret = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2 }).format(data.ucret);
            $('#select-edit-ucret-trigger span').text(data.unvan + ' | ' + formattedUcret + ' TL');
        } else {
            $('#select-edit-ucret-trigger span').text('Ücret tanımı seçin...');
            $('#select-edit-ucret-listbox [data-select-option]').removeClass('selected');
        }

        document.getElementById('dialog-edit-personnel').showModal();
    });
}

function savePersonnel() {
    const formData = $('#form-edit-personnel').serialize();
    $.post('<?php echo routeUrl("personel-guncelle"); ?>', formData, function(response) {
        if (response.success) {
            showToast({ category: 'success', title: 'Başarılı', description: 'Güncellendi.', duration: 1000, onClose: () => location.reload() });
            document.getElementById('dialog-edit-personnel').close();
            setTimeout(() => location.reload(), 1100);
        }
    });
}

function closeAllDropdowns() {
    $('.app-dropdown-menu').addClass('hidden').removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-[-10px]').removeAttr('style');
    $('.group-dropdown button').attr('aria-expanded', 'false');
}

let deletePersonnelId = null;
function deletePersonnel(id) {
    closeAllDropdowns();
    deletePersonnelId = id;
    document.getElementById('dialog-delete-personnel').showModal();
}

$('#btn-confirm-delete-personnel').on('click', function() {
    if (!deletePersonnelId) return;
    const btn = $(this);
    btn.prop('disabled', true).text('Siliniyor...');

    $.post('<?php echo routeUrl("personel-sil"); ?>', { id: deletePersonnelId }, function(response) {
        document.getElementById('dialog-delete-personnel').close();
        if (response.success) {
            showToast({ category: 'success', title: 'Başarılı', description: 'Silindi.', duration: 1000, onClose: () => location.reload() });
            setTimeout(() => location.reload(), 1100);
        } else {
            btn.prop('disabled', false).text('Sil');
            showToast({ category: 'error', title: 'Hata', description: response.error || 'Silme işlemi başarısız.' });
        }
    }, 'json');
});

// Excel Import Mantığı
let excelData = null;

function handleExcelFile(input) {
    const file = input.files[0];
    if (!file) return;

    document.getElementById('excel-filename').innerText = file.name;
    document.getElementById('excel-filename').classList.add('text-primary');

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            excelData = XLSX.utils.sheet_to_json(worksheet);
            
            if (excelData.length > 0) {
                checkImportReady();
                showToast({ category: 'info', title: 'Dosya Okundu', description: excelData.length + ' satır veri bulundu.' });
            } else {
                showToast({ category: 'error', title: 'Hata', description: 'Dosya boş veya okunamadı.' });
            }
        } catch (err) {
            console.error(err);
            showToast({ category: 'error', title: 'Hata', description: 'Excel dosyası işlenirken bir hata oluştu.' });
        }
    };
    reader.readAsArrayBuffer(file);
}

async function downloadSampleXLSX() {
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('Personel Yükleme');

    // Sütunları tanımla
    worksheet.columns = [
        { header: 'TC Kimlik No*', key: 'tc', width: 15 },
        { header: 'Ad Soyad*', key: 'ad', width: 20 },
        { header: 'Cinsiyet*', key: 'cinsiyet', width: 12 },
        { header: 'Öğrenim Durumu*', key: 'ogrenim', width: 20 },
        { header: 'Kıdem Yılı*', key: 'kidem', width: 20 },
        { header: 'Unvan*', key: 'unvan', width: 25 },
        { header: 'Ücret*', key: 'ucret_val', width: 15 },
        { header: 'Telefon', key: 'telefon', width: 15 },
        { header: 'Göreve Başlama Tarihi', key: 'baslama', width: 20 },
        { header: 'Meslek Kodu', key: 'meslek', width: 15 }
    ];

    // Örnek veri ekle
    worksheet.addRow({
        tc: '12345678901',
        ad: 'Örnek Personel',
        cinsiyet: 'Erkek',
        ogrenim: 'Lisans',
        kidem: '0-5 Yıl (Dahil)',
        unvan: 'Uzman Yazılımcı',
        ucret_val: '45.000,00',
        telefon: '05001234567',
        baslama: '29.04.2026',
        meslek: '1234.56'
    });

    // Başlık stilini ayarla
    worksheet.getRow(1).font = { bold: true };
    worksheet.getRow(1).fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FFE0E0E0' }
    };

    // Veri Doğrulama (Dropdowns)
    const cinsiyetOptions = ['Erkek', 'Kadın'];
    const ogrenimOptions = ['Lisans', 'Yüksek Lisans', 'Doktora', 'Ön Lisans', 'Lise'];
    const kidemOptions = ['0-5 Yıl (Dahil)', '5-10 Yıl (Dahil)', '10-15 Yıl (Dahil)', '15-20 Yıl (Dahil)', '20 Yıl Üzeri'];

    // 2. satırdan 1000. satıra kadar dropdown ekle
    for (let i = 2; i <= 1000; i++) {
        // Cinsiyet (C sütunu)
        worksheet.getCell(`C${i}`).dataValidation = {
            type: 'list',
            allowBlank: true,
            formulae: [`"${cinsiyetOptions.join(',')}"`]
        };
        // Öğrenim Durumu (D sütunu)
        worksheet.getCell(`D${i}`).dataValidation = {
            type: 'list',
            allowBlank: true,
            formulae: [`"${ogrenimOptions.join(',')}"`]
        };
        // Kıdem Yılı (E sütunu)
        worksheet.getCell(`E${i}`).dataValidation = {
            type: 'list',
            allowBlank: true,
            formulae: [`"${kidemOptions.join(',')}"`]
        };
    }

    // Dosyayı oluştur ve indir
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'personel_yukleme_sablonu.xlsx';
    a.click();
    window.URL.revokeObjectURL(url);
}

function checkImportReady() {
    const hasData = excelData && excelData.length > 0;
    document.getElementById('btn-start-import').disabled = !hasData;
}

function startImport() {
    if (!excelData) return;

    const btn = document.getElementById('btn-start-import');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Yükleniyor...';

    const updateWages = document.getElementById('update-wages-toggle').checked;

    $.ajax({
        url: '<?php echo routeUrl("personel-import-excel"); ?>',
        method: 'POST',
        data: JSON.stringify({
            data: excelData,
            update_wages: updateWages
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showToast({ 
                    category: 'success', 
                    title: 'Başarılı', 
                    description: response.count + ' personel başarıyla eklendi.',
                    duration: 3000,
                    onClose: () => location.reload()
                });
                document.getElementById('dialog-import-excel').close();
                setTimeout(() => location.reload(), 3100);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.error || 'Yükleme sırasında bir hata oluştu.' });
                btn.disabled = false;
                btn.innerText = originalText;
            }
        },
        error: function() {
            showToast({ category: 'error', title: 'Hata', description: 'Sunucu hatası oluştu.' });
            btn.disabled = false;
            btn.innerText = originalText;
        }
    });
}

async function exportToExcel() {
    const table = $('#personnelTable').DataTable();
    if (!table) return;

    showToast({ category: 'info', title: 'Bilgi', description: 'Veriler hazırlanıyor, lütfen bekleyin...' });

    // Mevcut filtrelerle tüm verileri sunucudan çek
    const params = table.ajax.params();
    params.length = -1; // Tüm kayıtlar

    $.ajax({
        url: '<?php echo routeUrl("personel-datatable"); ?>',
        method: 'POST',
        data: params,
        success: async function(response) {
            if (!response.data || response.data.length === 0) {
                showToast({ category: 'warning', title: 'Uyarı', description: 'Aktarılacak veri bulunamadı.' });
                return;
            }

            const workbook = new ExcelJS.Workbook();
            const worksheet = workbook.addWorksheet('Personel Listesi');

            worksheet.columns = [
                { header: 'Ad Soyad', key: 'ad_soyad', width: 30 },
                { header: 'TC Kimlik', key: 'tc_kimlik', width: 15 },
                { header: 'Cinsiyet', key: 'cinsiyet', width: 10 },
                { header: 'Unvan', key: 'unvan', width: 25 },
                { header: 'Öğrenim', key: 'ogrenim', width: 15 },
                { header: 'Ücret', key: 'ucret', width: 15 },
                { header: 'Durum', key: 'durum', width: 15 },
                { header: 'G. Başlama', key: 'goreve_baslama', width: 15 },
                { header: 'Kadroya Geçiş', key: 'kadro_gecis', width: 15 },
                { header: 'Ayrılış / Kadro Tarihi', key: 'ayrilma_tarihi', width: 20 },
                { header: 'Telefon', key: 'telefon', width: 15 }
            ];

            worksheet.getRow(1).font = { bold: true };
            worksheet.getRow(1).fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: { argb: 'FFE0E0E0' }
            };

            response.data.forEach(p => {
                const kadroGecis = p.goreve_baslama_tarihi ? new Date(p.goreve_baslama_tarihi) : null;
                if (kadroGecis) kadroGecis.setFullYear(kadroGecis.getFullYear() + 3);

                worksheet.addRow({
                    ad_soyad: p.ad_soyad,
                    tc_kimlik: p.tc_kimlik,
                    cinsiyet: p.cinsiyet,
                    unvan: p.unvan,
                    ogrenim: p.ogrenim,
                    ucret: p.ucret ? parseFloat(p.ucret) : 0,
                    durum: p.durum || 'aktif',
                    goreve_baslama: p.goreve_baslama_tarihi ? new Date(p.goreve_baslama_tarihi) : null,
                    kadro_gecis: kadroGecis,
                    ayrilma_tarihi: p.ayrilma_tarihi ? new Date(p.ayrilma_tarihi) : null,
                    telefon: p.telefon
                });
            });

            worksheet.getColumn('ucret').numFmt = '#,##0.00 "₺"';
            worksheet.getColumn('goreve_baslama').numFmt = 'dd.mm.yyyy';
            worksheet.getColumn('kadro_gecis').numFmt = 'dd.mm.yyyy';
            worksheet.getColumn('ayrilma_tarihi').numFmt = 'dd.mm.yyyy';

            worksheet.eachRow((row) => {
                row.eachCell((cell) => {
                    cell.border = {
                        top: { style: 'thin' },
                        left: { style: 'thin' },
                        bottom: { style: 'thin' },
                        right: { style: 'thin' }
                    };
                });
            });

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const dateStr = new Date().toLocaleDateString('tr-TR').replace(/\./g, '-');
            a.download = `personel_listesi_${dateStr}.xlsx`;
            a.click();
            window.URL.revokeObjectURL(url);
        },
        error: function() {
            showToast({ category: 'error', title: 'Hata', description: 'Veriler alınırken bir hata oluştu.' });
        }
    });
}
</script>

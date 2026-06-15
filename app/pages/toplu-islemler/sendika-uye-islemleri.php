<?php 
$pageTitle = 'Sendika Üye İşlemleri'; 
$pageSubtitle = 'Sistemdeki personellerin sendika üyelik ve temsilcilik tanımları';
?>

<div class="p-6">
    <!-- Actions Bar -->
    <div class="flex items-center justify-between gap-3 mb-6 !overflow-visible">
        <div class="flex items-center gap-4 !overflow-visible">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Sendika Üye İşlemleri</h1>
        </div>
        <div class="flex items-center gap-3 !overflow-visible">
            <!-- Arama Çubuğu -->
            <div class="relative w-full max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="sendikaSearch" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="Personel veya sendika ara...">
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
                        <div role="option" onclick="document.getElementById('dialog-import-sendika').showModal()" class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors rounded-md text-zinc-700 dark:text-zinc-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-70"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span>Excel'den Yükle</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yeni Üyelik Ekle -->
            <button onclick="openAddSendikaModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Yeni Üyelik Ekle
            </button>
        </div>
    </div>

    <!-- Data Grid -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden relative flex flex-col h-[calc(100vh-230px)]">
        <?php echo renderTablePreloader(); ?>

        <div id="table-container" class="flex-1 flex flex-col overflow-hidden" style="display: none;">
            <table id="sendikaTable" class="w-full text-left">
                <thead>
                    <tr>
                        <th>Personel</th>
                        <th>T.C. Kimlik</th>
                        <th>Sendika</th>
                        <th>Aidat Tipi</th>
                        <th>Başvuru Tarihi</th>
                        <th>Üyelik Tarihi</th>
                        <th>Çıkış Tarihi</th>
                        <th>Temsilcilik</th>
                        <th class="text-right no-sort">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yeni Üye Kayıt Modalı -->
<dialog id="dialog-add-sendika" class="dialog w-full sm:max-w-[480px] !overflow-visible" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl !overflow-visible" onclick="event.stopPropagation()">
    <header class="flex items-start justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Yeni Sendika Üyeliği Ekle</h2>
        <p class="text-sm text-zinc-500">Personel için sendika üyelik ve temsilcilik tanımlayın.</p>
      </div>
    </header>

    <form action="<?php echo routeUrl('sendika-uye-ekle'); ?>" method="POST" id="form-add-sendika" class="form grid gap-4 !overflow-visible">
        <!-- Personel Seçimi (Custom Rich Select) -->
        <div class="grid gap-2 !overflow-visible">
            <label class="text-sm font-semibold">Personel*</label>
            <div class="app-select-rich" id="select-add-personel">
              <button type="button" class="btn-outline w-full justify-between px-3 text-sm" onclick="toggleCustomSelect(this, event)">
                <span class="truncate">Personel seçin...</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
              </button>
              <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                <header class="!bg-white !dark:bg-zinc-900 !px-3 !py-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                  <input type="text" placeholder="Personel ara..." autocomplete="off" onkeyup="filterCustomOptions(this)" />
                </header>
                <div role="listbox" class="max-h-[200px] overflow-y-auto custom-scrollbar p-1">
                    <?php foreach ($personeller as $p): ?>
                    <div role="option" data-select-option data-value="<?php echo $p['id']; ?>" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md">
                        <span><?php echo htmlspecialchars($p['ad_soyad'] . ' (' . $p['tc_kimlik'] . ')'); ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <?php endforeach; ?>
                </div>
              </div>
              <input type="hidden" name="personel_id" id="add_personel_id" required />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 !overflow-visible">
            <!-- Sendika Adı (Custom Rich Select with Tags) -->
            <div class="grid gap-2 !overflow-visible">
                <label>Sendika Adı*</label>
                <div class="app-select-rich" id="select-add-sendika" data-tags="true">
                  <button type="button" class="btn-outline w-full justify-between px-3 text-sm" onclick="toggleCustomSelect(this, event)">
                    <span class="truncate text-zinc-400">Sendika seçin veya yazın...</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                  </button>
                  <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                    <header class="!bg-white !dark:bg-zinc-900 !px-3 !py-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                      <input type="text" placeholder="Sendika ara veya yeni yaz..." autocomplete="off" onkeyup="filterCustomOptions(this)" />
                    </header>
                    <div role="listbox" class="max-h-[200px] overflow-y-auto custom-scrollbar p-1">
                        <?php if (isset($sendikalar) && is_array($sendikalar)): ?>
                            <?php foreach ($sendikalar as $s): ?>
                            <div role="option" data-select-option data-value="<?php echo htmlspecialchars($s); ?>" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md">
                                <span><?php echo htmlspecialchars($s); ?></span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                  </div>
                  <input type="hidden" name="sendika" id="add_sendika" required />
                </div>
            </div>

            <!-- Üye Aidat Tipi (Custom Rich Select) -->
            <div class="grid gap-2 !overflow-visible">
                <label>Üye Aidat Tipi*</label>
                <div class="app-select-rich" id="select-add-aidat">
                  <button type="button" class="btn-outline w-full justify-between px-3 text-sm" onclick="toggleCustomSelect(this, event)">
                    <span class="truncate">Normal Üye</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                  </button>
                  <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                    <div role="listbox" class="max-h-[150px] overflow-y-auto custom-scrollbar p-1">
                        <div role="option" data-select-option data-value="Normal Üye" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md selected">
                            <span>Normal Üye</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div role="option" data-select-option data-value="Dayanışma Aidatı" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md">
                            <span>Dayanışma Aidatı</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                    </div>
                  </div>
                  <input type="hidden" name="uye_aidat_tipi" id="add_uye_aidat_tipi" value="Normal Üye" required />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <!-- Başvuru Tarihi -->
            <div class="grid gap-2">
                <label for="basvuru_tarihi">Başvuru Tarihi</label>
                <input type="text" name="basvuru_tarihi" id="basvuru_tarihi" class="datepicker" placeholder="gg.aa.yyyy" autocomplete="off" />
            </div>

            <!-- Üyelik Tarihi -->
            <div class="grid gap-2">
                <label for="uyelik_tarihi">Üyelik Tarihi*</label>
                <input type="text" name="uyelik_tarihi" id="uyelik_tarihi" class="datepicker" required placeholder="gg.aa.yyyy" autocomplete="off" />
            </div>

            <!-- Çıkış Tarihi -->
            <div class="grid gap-2">
                <label for="cikis_tarihi">Çıkış Tarihi</label>
                <input type="text" name="cikis_tarihi" id="cikis_tarihi" class="datepicker" placeholder="gg.aa.yyyy" autocomplete="off" />
            </div>
        </div>

        <!-- Temsilcilik Seçenekleri -->
        <div class="flex gap-6 mt-2">
            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium">
                <input type="checkbox" name="temsilci_mi" value="1" class="rounded border-zinc-300 text-primary focus:ring-primary" />
                İşyeri Temsilcisi
            </label>
            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium">
                <input type="checkbox" name="bas_temsilci_mi" value="1" class="rounded border-zinc-300 text-primary focus:ring-primary" />
                Baş Temsilci
            </label>
        </div>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="submit" form="form-add-sendika" class="btn">Üyeliği Kaydet</button>
    </footer>

    <button type="button" aria-label="Close dialog" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">
        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
      </svg>
    </button>
  </div>
</dialog>

<!-- Üye Düzenleme Modalı -->
<dialog id="dialog-edit-sendika" class="dialog w-full sm:max-w-[480px] !overflow-visible" onclick="if (event.target === this) this.close()">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl !overflow-visible" onclick="event.stopPropagation()">
    <header class="flex items-start justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Sendika Üyeliğini Düzenle</h2>
        <p class="text-sm text-zinc-500">Mevcut sendika üyelik bilgilerini güncelleyin.</p>
      </div>
    </header>

    <form id="form-edit-sendika" class="form grid gap-4 !overflow-visible">
        <input type="hidden" name="id" id="edit_id" />

        <!-- Personel Seçimi (Düzenleme modunda değiştirilemez) -->
        <div class="grid gap-2">
            <label class="text-sm font-semibold">Personel</label>
            <input type="text" id="edit_personel_display" class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 cursor-not-allowed" readonly />
            <input type="hidden" name="personel_id" id="edit_personel_id" />
        </div>

        <div class="grid grid-cols-2 gap-4 !overflow-visible">
            <!-- Sendika Adı (Custom Rich Select with Tags) -->
            <div class="grid gap-2 !overflow-visible">
                <label>Sendika Adı*</label>
                <div class="app-select-rich" id="select-edit-sendika" data-tags="true">
                  <button type="button" class="btn-outline w-full justify-between px-3 text-sm" onclick="toggleCustomSelect(this, event)">
                    <span class="truncate text-zinc-400">Sendika seçin veya yazın...</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                  </button>
                  <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                    <header class="!bg-white !dark:bg-zinc-900 !px-3 !py-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                      <input type="text" placeholder="Sendika ara veya yeni yaz..." autocomplete="off" onkeyup="filterCustomOptions(this)" />
                    </header>
                    <div role="listbox" class="max-h-[200px] overflow-y-auto custom-scrollbar p-1">
                        <?php if (isset($sendikalar) && is_array($sendikalar)): ?>
                            <?php foreach ($sendikalar as $s): ?>
                            <div role="option" data-select-option data-value="<?php echo htmlspecialchars($s); ?>" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md">
                                <span><?php echo htmlspecialchars($s); ?></span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                  </div>
                  <input type="hidden" name="sendika" id="edit_sendika" required />
                </div>
            </div>

            <!-- Üye Aidat Tipi (Custom Rich Select) -->
            <div class="grid gap-2 !overflow-visible">
                <label>Üye Aidat Tipi*</label>
                <div class="app-select-rich" id="select-edit-aidat">
                  <button type="button" class="btn-outline w-full justify-between px-3 text-sm" onclick="toggleCustomSelect(this, event)">
                    <span class="truncate">Normal Üye</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                  </button>
                  <div data-custom-popover aria-hidden="true" class="!z-[1001] !border-zinc-200 !dark:border-zinc-800 !shadow-2xl">
                    <div role="listbox" class="max-h-[150px] overflow-y-auto custom-scrollbar p-1">
                        <div role="option" data-select-option data-value="Normal Üye" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md">
                            <span>Normal Üye</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div role="option" data-select-option data-value="Dayanışma Aidatı" onclick="selectCustomOption(this)" class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md">
                            <span>Dayanışma Aidatı</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                    </div>
                  </div>
                  <input type="hidden" name="uye_aidat_tipi" id="edit_uye_aidat_tipi" required />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <!-- Başvuru Tarihi -->
            <div class="grid gap-2">
                <label for="edit_basvuru_tarihi">Başvuru Tarihi</label>
                <input type="text" name="basvuru_tarihi" id="edit_basvuru_tarihi" class="datepicker" placeholder="gg.aa.yyyy" autocomplete="off" />
            </div>

            <!-- Üyelik Tarihi -->
            <div class="grid gap-2">
                <label for="edit_uyelik_tarihi">Üyelik Tarihi*</label>
                <input type="text" name="uyelik_tarihi" id="edit_uyelik_tarihi" class="datepicker" required placeholder="gg.aa.yyyy" autocomplete="off" />
            </div>

            <!-- Çıkış Tarihi -->
            <div class="grid gap-2">
                <label for="edit_cikis_tarihi">Çıkış Tarihi</label>
                <input type="text" name="cikis_tarihi" id="edit_cikis_tarihi" class="datepicker" placeholder="gg.aa.yyyy" autocomplete="off" />
            </div>
        </div>

        <!-- Temsilcilik Seçenekleri -->
        <div class="flex gap-6 mt-2">
            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium">
                <input type="checkbox" name="temsilci_mi" id="edit_temsilci_mi" value="1" class="rounded border-zinc-300 text-primary focus:ring-primary" />
                İşyeri Temsilcisi
            </label>
            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium">
                <input type="checkbox" name="bas_temsilci_mi" id="edit_bas_temsilci_mi" value="1" class="rounded border-zinc-300 text-primary focus:ring-primary" />
                Baş Temsilci
            </label>
        </div>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" onclick="saveSendika()" class="btn">Değişiklikleri Kaydet</button>
    </footer>

    <button type="button" aria-label="Close dialog" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">
        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
      </svg>
    </button>
  </div>
</dialog>

<!-- Excel'den Yükle Dialog -->
<dialog id="dialog-import-sendika" class="dialog w-full sm:max-w-[500px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Excel'den Sendika Üyeliği Yükle</h2>
      <p class="text-sm text-zinc-500">Üyelikleri toplu olarak yüklemek için Excel (.xlsx, .xls) veya CSV dosyası seçin.</p>
    </header>

    <div class="grid gap-6">
        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700">
            <div class="flex items-center justify-between text-xs mb-3">
                <span class="font-semibold text-zinc-500">Dosya Formatı:</span>
                <button type="button" onclick="downloadSendikaTemplate()" class="text-primary hover:underline flex items-center gap-1 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Örnek Şablon İndir
                </button>
            </div>
            <div class="text-[11px] text-zinc-500 mb-3 space-y-1">
                <ul class="list-disc list-inside">
                    <li>Sütunlar: <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">TC Kimlik No</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Sendika</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Üye Aidat Tipi</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Başvuru Tarihi</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Üyelik Tarihi</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Çıkış Tarihi</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">İşyeri Temsilcisi</span>, <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Baş Temsilci</span></li>
                    <li>Tarih Formatı: <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">gg.aa.yyyy</span></li>
                    <li>Temsilcilik Değerleri: <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Evet</span> veya <span class="font-mono bg-zinc-200 dark:bg-zinc-700 px-1 rounded">Hayır</span> (veya boş)</li>
                </ul>
            </div>
            <input type="file" id="importSendikaFile" accept=".xlsx, .xls, .csv" class="hidden" onchange="handleSendikaFileSelect(this)">
            <button onclick="document.getElementById('importSendikaFile').click()" class="w-full py-8 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col items-center justify-center gap-3 hover:bg-white dark:hover:bg-zinc-900 transition-all group">
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
                            <th class="p-2">TC Kimlik</th>
                            <th class="p-2">Sendika</th>
                            <th class="p-2">Aidat Tipi</th>
                            <th class="p-2">Üyelik Tar.</th>
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
      <button type="button" id="btn-do-import" class="btn hidden" onclick="doSendikaImport()">Yükle</button>
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
        Bu işlem geri alınamaz. Seçilen sendika üyeliğini kalıcı olarak silecek ve sistemden kaldıracaktır.
      </p>
    </header>
    
    <footer class="flex justify-end gap-3">
      <button type="button" class="px-4 py-2 text-sm font-medium border border-zinc-200 dark:border-zinc-800 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors" onclick="document.getElementById('dialog-confirm-delete').close()">İptal</button>
      <button type="button" id="btn-confirm-delete" class="px-4 py-2 text-sm font-medium bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-md hover:opacity-90 transition-opacity">Devam Et</button>
    </footer>
  </div>
</dialog>

<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>

<script>
// Custom Select Fonksiyonları
function toggleCustomSelect(btn, event) {
    if (event) event.stopPropagation();
    
    const popover = btn.nextElementSibling;
    const isHidden = popover.getAttribute('aria-hidden') === 'true';
    
    $('[data-custom-popover]').attr('aria-hidden', 'true');
    $('.app-select-rich button').attr('aria-expanded', 'false');

    if (isHidden) {
        popover.setAttribute('aria-hidden', 'false');
        btn.setAttribute('aria-expanded', 'true');
        const searchInput = popover.querySelector('input');
        if (searchInput) setTimeout(() => searchInput.focus(), 100);
    }
}

function selectCustomOption(el) {
    const value = el.getAttribute('data-value');
    const selectDiv = el.closest('.app-select-rich');
    const btnSpan = selectDiv.querySelector('button span');
    const hiddenInput = selectDiv.querySelector('input[type="hidden"]');
    
    btnSpan.textContent = el.querySelector('span').textContent;
    btnSpan.classList.remove('text-zinc-400');
    hiddenInput.value = value;
    
    // Popover'ı kapat
    const popover = selectDiv.querySelector('[data-custom-popover]');
    popover.setAttribute('aria-hidden', 'true');
    selectDiv.querySelector('button').setAttribute('aria-expanded', 'false');
    
    // Seçili sınıfını ekle
    $(selectDiv).find('[data-select-option]').removeClass('selected');
    $(el).addClass('selected');

    // Reset search query and restore visibility
    const searchInput = popover.querySelector('input');
    if (searchInput) {
        searchInput.value = '';
        const listbox = popover.querySelector('[role="listbox"]');
        const options = listbox.querySelectorAll('[data-select-option]');
        options.forEach(opt => {
            if (!opt.classList.contains('new-tag-temp-option')) {
                opt.style.display = '';
            }
        });
        const tempOpt = listbox.querySelector('.new-tag-temp-option');
        if (tempOpt) tempOpt.remove();
    }

    // Trigger change event manually
    $(hiddenInput).trigger('change');
}

function filterCustomOptions(input) {
    const filter = input.value.trim();
    const filterLower = filter.toLowerCase();
    const selectDiv = input.closest('.app-select-rich');
    const isTags = selectDiv.getAttribute('data-tags') === 'true';
    const popover = input.closest('[data-custom-popover]');
    const listbox = popover.querySelector('[role="listbox"]');
    const options = listbox.querySelectorAll('[data-select-option]');
    
    let exactMatchFound = false;
    options.forEach(opt => {
        if (opt.classList.contains('new-tag-temp-option')) return;
        const spanEl = opt.querySelector('span');
        if (!spanEl) return;
        const text = spanEl.textContent.trim();
        const textLower = text.toLowerCase();
        if (textLower === filterLower) {
            exactMatchFound = true;
        }
        opt.style.display = textLower.includes(filterLower) ? '' : 'none';
    });

    const existingTemp = listbox.querySelector('.new-tag-temp-option');
    if (existingTemp) {
        existingTemp.remove();
    }

    if (isTags && filter && !exactMatchFound) {
        const tempOpt = document.createElement('div');
        tempOpt.className = 'new-tag-temp-option flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md text-primary font-medium';
        tempOpt.setAttribute('role', 'option');
        tempOpt.setAttribute('data-select-option', 'true');
        tempOpt.setAttribute('data-value', filter);
        tempOpt.innerHTML = `
            <span>Ekle: "${filter}"</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
        `;
        tempOpt.addEventListener('click', function() {
            tempOpt.className = 'new-tag-option flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md';
            tempOpt.classList.remove('new-tag-temp-option');
            tempOpt.querySelector('span').textContent = filter;
            selectCustomOption(tempOpt);
        });
        listbox.appendChild(tempOpt);
    }
}

// Dışarı tıklayınca popover kapatma
$(document).on('click', function(e) {
    if (!$(e.target).closest('.app-select-rich').length) {
        $('[data-custom-popover]').attr('aria-hidden', 'true');
        $('.app-select-rich button').attr('aria-expanded', 'false');
    }
});

$(document).ready(function() {
    // Flatpickr datepicker'lar
    $('.datepicker').flatpickr({
        locale: 'tr',
        dateFormat: 'd.m.Y',
        allowInput: true
    });

    if (typeof initDataTable !== 'function') {
        $('#table-preloader').hide();
        $('#table-container').show();
        return;
    }

    const table = initDataTable('#sendikaTable', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo routeUrl("sendika-uye-datatable"); ?>',
            type: 'POST'
        },
        columns: [
            { data: 'ad_soyad' },
            { data: 'tc_kimlik' },
            { data: 'sendika' },
            { data: 'uye_aidat_tipi' },
            { data: 'basvuru_tarihi' },
            { data: 'uyelik_tarihi' },
            { data: 'cikis_tarihi' },
            { 
                data: null,
                render: function(data, type, row) {
                    let tags = [];
                    if (parseInt(row.temsilci_mi) === 1) {
                        tags.push('<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Temsilci</span>');
                    }
                    if (parseInt(row.bas_temsilci_mi) === 1) {
                        tags.push('<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">Baş Temsilci</span>');
                    }
                    return tags.length > 0 ? tags.join(' ') : '<span class="text-zinc-400">-</span>';
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-right',
                render: function(data, type, row) {
                    return `
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editSendika(${row.id}, '${row.ad_soyad}', '${row.personel_id}', '${row.sendika}', '${row.uye_aidat_tipi}', '${row.basvuru_tarihi}', '${row.uyelik_tarihi}', '${row.cikis_tarihi}', ${row.temsilci_mi}, ${row.bas_temsilci_mi})" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-zinc-400 transition-colors" title="Düzenle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                            </button>
                            <button onclick="deleteSendika(${row.id})" class="p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded text-red-400 transition-colors" title="Sil">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'asc']],
        dom: '<"flex-1 overflow-auto"rt><"mt-auto border-t border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row justify-between items-center p-4 gap-4 bg-zinc-50/50 dark:bg-zinc-800/30"lip>',
        preloader: '#table-preloader'
    });

    $('#sendikaSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Kayıt Formu
    $('#form-add-sendika').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.post($(this).attr('action'), formData, function(response) {
            if (response.success) {
                showToast({ category: 'success', title: 'Başarılı', description: 'Sendika üyelik kaydı başarıyla eklendi.', duration: 2500 });
                document.getElementById('dialog-add-sendika').close();
                table.draw();
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.error || 'İşlem gerçekleştirilemedi.' });
            }
        }, 'json');
    });
});

function openAddSendikaModal() {
    $('#form-add-sendika')[0].reset();
    
    // Reset custom select personnel
    const $pSelect = $('#select-add-personel');
    $pSelect.find('button span').text('Personel seçin...');
    $pSelect.find('[data-select-option]').removeClass('selected');
    $('#add_personel_id').val('');

    // Reset Sendika Adı custom-select
    const $sSelect = $('#select-add-sendika');
    $sSelect.find('button span').text('Sendika seçin veya yazın...');
    $sSelect.find('[data-select-option]').removeClass('selected');
    $sSelect.find('.new-tag-option').remove();
    $('#add_sendika').val('');

    // Reset custom select dues
    const $dSelect = $('#select-add-aidat');
    $dSelect.find('button span').text('Normal Üye');
    $dSelect.find('[data-select-option]').removeClass('selected');
    $dSelect.find('[data-value="Normal Üye"]').addClass('selected');
    $('#add_uye_aidat_tipi').val('Normal Üye');

    // Reset dates flatpickr
    document.getElementById('basvuru_tarihi')._flatpickr.clear();
    document.getElementById('uyelik_tarihi')._flatpickr.clear();
    document.getElementById('cikis_tarihi')._flatpickr.clear();

    document.getElementById('dialog-add-sendika').showModal();
}

function editSendika(id, name, p_id, sendika, aidat, basvuru, uyelik, cikis, temsilci, bas_temsilci) {
    $('#edit_id').val(id);
    $('#edit_personel_display').val(name);
    $('#edit_personel_id').val(p_id);

    // Set Sendika Adı custom-select
    const $sSelect = $('#select-edit-sendika');
    $sSelect.find('button span').text(sendika || 'Sendika seçin veya yazın...');
    $sSelect.find('[data-select-option]').removeClass('selected');
    $sSelect.find('.new-tag-option').remove();
    
    let $opt = $sSelect.find(`[data-value="${sendika}"]`);
    if ($opt.length === 0 && sendika) {
        const listbox = $sSelect.find('[role="listbox"]');
        listbox.append(`
            <div role="option" data-select-option data-value="${sendika}" onclick="selectCustomOption(this)" class="new-tag-option flex items-center justify-between px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors mx-1 rounded-md selected">
                <span>${sendika}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
        `);
    } else if (sendika) {
        $opt.addClass('selected');
    }
    $('#edit_sendika').val(sendika);

    // Set custom select dues
    const $dSelect = $('#select-edit-aidat');
    $dSelect.find('button span').text(aidat);
    $dSelect.find('[data-select-option]').removeClass('selected');
    $dSelect.find(`[data-value="${aidat}"]`).addClass('selected');
    $('#edit_uye_aidat_tipi').val(aidat);

    // Set dates
    if (basvuru && basvuru !== '-') {
        document.getElementById('edit_basvuru_tarihi')._flatpickr.setDate(basvuru);
    } else {
        document.getElementById('edit_basvuru_tarihi')._flatpickr.clear();
    }

    if (uyelik) {
        document.getElementById('edit_uyelik_tarihi')._flatpickr.setDate(uyelik);
    } else {
        document.getElementById('edit_uyelik_tarihi')._flatpickr.clear();
    }

    if (cikis && cikis !== '-') {
        document.getElementById('edit_cikis_tarihi')._flatpickr.setDate(cikis);
    } else {
        document.getElementById('edit_cikis_tarihi')._flatpickr.clear();
    }

    // Set checkboxes
    $('#edit_temsilci_mi').prop('checked', parseInt(temsilci) === 1);
    $('#edit_bas_temsilci_mi').prop('checked', parseInt(bas_temsilci) === 1);

    document.getElementById('dialog-edit-sendika').showModal();
}

function saveSendika() {
    const formData = $('#form-edit-sendika').serialize();
    $.post('<?php echo routeUrl("sendika-uye-guncelle"); ?>', formData, function(response) {
        if (response.success) {
            showToast({ category: 'success', title: 'Başarılı', description: 'Sendika üyelik kaydı güncellendi.', duration: 2500 });
            document.getElementById('dialog-edit-sendika').close();
            $('#sendikaTable').DataTable().draw(false);
        } else {
            showToast({ category: 'error', title: 'Hata', description: response.error || 'Güncelleme hatası.' });
        }
    }, 'json');
}

let deleteRecordId = null;
function deleteSendika(id) {
    deleteRecordId = id;
    document.getElementById('dialog-confirm-delete').showModal();
}

$('#btn-confirm-delete').on('click', function() {
    if (!deleteRecordId) return;
    const btn = $(this);
    btn.prop('disabled', true).text('Siliniyor...');

    $.post('<?php echo routeUrl("sendika-uye-sil"); ?>', { id: deleteRecordId }, function(response) {
        document.getElementById('dialog-confirm-delete').close();
        btn.prop('disabled', false).text('Devam Et');
        if (response.success) {
            showToast({ category: 'success', title: 'Başarılı', description: 'Kayıt başarıyla silindi.', duration: 1500 });
            $('#sendikaTable').DataTable().draw(false);
        } else {
            showToast({ category: 'error', title: 'Hata', description: response.error || 'Silme işlemi başarısız.' });
        }
    }, 'json');
});

// Excel drag-drop ve import mantığı
let excelImportData = null;

function handleSendikaFileSelect(input) {
    const file = input.files[0];
    if (!file) return;

    document.getElementById('fileNameDisplay').innerText = file.name;
    document.getElementById('fileNameDisplay').classList.add('text-primary');

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            excelImportData = XLSX.utils.sheet_to_json(worksheet);
            
            if (excelImportData.length > 0) {
                // Önizleme oluştur
                let previewHtml = '';
                excelImportData.slice(0, 5).forEach(row => {
                    const tc = row['TC Kimlik No'] ?? row['tc_kimlik'] ?? row['TC Kimlik'] ?? '';
                    const sendika = row['Sendika'] ?? row['sendika'] ?? '';
                    const aidat = row['Üye Aidat Tipi'] ?? row['uye_aidat_tipi'] ?? '';
                    const uyelik = row['Üyelik Tarihi'] ?? row['uyelik_tarihi'] ?? '';
                    previewHtml += `
                        <tr>
                            <td class="p-2">${tc}</td>
                            <td class="p-2">${sendika}</td>
                            <td class="p-2">${aidat}</td>
                            <td class="p-2">${uyelik}</td>
                        </tr>
                    `;
                });
                
                $('#previewBody').html(previewHtml);
                $('#totalRowsCount').text(`Toplam ${excelImportData.length} satır kayıt tespit edildi.`);
                $('#importPreview').removeClass('hidden');
                $('#btn-do-import').removeClass('hidden').prop('disabled', false);

                showToast({ category: 'info', title: 'Dosya Okundu', description: excelImportData.length + ' satır veri önizlemeye alındı.' });
            } else {
                showToast({ category: 'error', title: 'Hata', description: 'Excel dosyasında veri bulunamadı.' });
            }
        } catch (err) {
            console.error(err);
            showToast({ category: 'error', title: 'Hata', description: 'Excel dosyası okunurken hata oluştu.' });
        }
    };
    reader.readAsArrayBuffer(file);
}

function doSendikaImport() {
    if (!excelImportData || excelImportData.length === 0) return;

    const btn = document.getElementById('btn-do-import');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Yükleniyor...';

    $.ajax({
        url: '<?php echo routeUrl("sendika-uye-import"); ?>',
        method: 'POST',
        data: JSON.stringify({
            data: excelImportData
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                if (response.errors && response.errors.length > 0) {
                    const errorText = response.errors.join('\n');
                    showToast({ 
                        category: 'warning', 
                        title: 'Kısmi Başarı', 
                        description: response.count + ' üyelik işlendi. Hata/Atlananlar:\n' + errorText,
                        duration: 8000
                    });
                } else {
                    showToast({ 
                        category: 'success', 
                        title: 'Başarılı', 
                        description: response.count + ' üyelik kaydı başarıyla eklendi.',
                        duration: 3500
                    });
                }
                document.getElementById('dialog-import-sendika').close();
                $('#sendikaTable').DataTable().draw();
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.error || 'Yükleme başarısız.' });
            }
            btn.disabled = false;
            btn.innerText = originalText;
        },
        error: function() {
            showToast({ category: 'error', title: 'Hata', description: 'Sunucu hatası oluştu.' });
            btn.disabled = false;
            btn.innerText = originalText;
        }
    });
}

async function downloadSendikaTemplate() {
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('Sendika Üyelik');

    worksheet.columns = [
        { header: 'TC Kimlik No*', key: 'tc', width: 18 },
        { header: 'Sendika*', key: 'sendika', width: 20 },
        { header: 'Üye Aidat Tipi*', key: 'aidat', width: 18 },
        { header: 'Başvuru Tarihi', key: 'basvuru', width: 15 },
        { header: 'Üyelik Tarihi*', key: 'uyelik', width: 15 },
        { header: 'Çıkış Tarihi', key: 'cikis', width: 15 },
        { header: 'İşyeri Temsilcisi', key: 'temsilci', width: 15 },
        { header: 'Baş Temsilci', key: 'bas_temsilci', width: 15 }
    ];

    worksheet.addRow({
        tc: '12345678901',
        sendika: 'Tez-Koop-İş',
        aidat: 'Normal Üye',
        basvuru: '01.05.2026',
        uyelik: '10.05.2026',
        cikis: '',
        temsilci: 'Hayır',
        bas_temsilci: 'Hayır'
    });

    worksheet.getRow(1).font = { bold: true };
    worksheet.getRow(1).fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FFE0E0E0' }
    };

    // Dropdowns validation in columns
    const aidatOptions = ['Normal Üye', 'Dayanışma Aidatı'];
    const booleanOptions = ['Evet', 'Hayır'];

    for (let i = 2; i <= 500; i++) {
        worksheet.getCell(`C${i}`).dataValidation = {
            type: 'list',
            allowBlank: true,
            formulae: [`"${aidatOptions.join(',')}"`]
        };
        worksheet.getCell(`G${i}`).dataValidation = {
            type: 'list',
            allowBlank: true,
            formulae: [`"${booleanOptions.join(',')}"`]
        };
        worksheet.getCell(`H${i}`).dataValidation = {
            type: 'list',
            allowBlank: true,
            formulae: [`"${booleanOptions.join(',')}"`]
        };
    }

    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sendika_uyelik_sablonu.xlsx';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

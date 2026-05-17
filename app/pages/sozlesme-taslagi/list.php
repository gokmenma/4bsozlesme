<?php
$pageTitle = 'Sözleşme Taslağı Düzenleyici';
$pageSubtitle = 'Kurumsal sözleşme şablonlarınızı oluşturun ve özelleştirin';
?>

<!-- Quill 2.0 CDN -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<link rel="stylesheet" href="<?php echo routeUrl('assets/css/contract-document.css'); ?>">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

<main class="flex flex-col h-[calc(100vh-120px)] overflow-hidden bg-zinc-50 dark:bg-zinc-950 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
    <!-- Editor Header -->
    <div class="flex items-center justify-between px-6 py-4 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
            </div>
            <div>
                <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Hizmet Sözleşmesi Şablonu</h1>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Times New Roman (13px) formatında düzenleniyor</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="resetToDefault()" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors border border-red-200 dark:border-red-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                Varsayılana Sıfırla
            </button>
            <button id="printBtn" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors border border-zinc-200 dark:border-zinc-800">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                Önizleme / Yazdır
            </button>
            <button id="saveBtn" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 rounded-lg transition-all shadow-sm shadow-zinc-200 dark:shadow-none active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Şablonu Kaydet
            </button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar: Variables -->
        <div class="w-64 flex-shrink-0 bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800 overflow-y-auto p-4 hidden lg:block">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><path d="m15 5 4 4"/><path d="M13 7 8.7 2.7a2.41 2.41 0 0 0-3.4 0L2.7 5.3a2.41 2.41 0 0 0 0 3.4L7 13"/><path d="m8 6 2-2"/><path d="m2 22 7-7"/><path d="M11 20.3 13.1 18.2"/><path d="m15 16.3 2.1-2.1"/><path d="m19 12.3 2.1-2.1"/><path d="M22 22 2 2"/></svg>
                Dinamik Alanlar
            </h3>
            
            <div class="space-y-6">
                <!-- Page Settings -->
                <div class="mb-4 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <h4 class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-3">Sayfa Ayarları</h4>
                    <label class="flex items-center justify-between cursor-pointer group">
                        <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 group-hover:text-indigo-600 transition-colors">Sayfa Çerçevesi</span>
                        <div class="relative">
                            <input type="checkbox" id="page-border-toggle" class="sr-only peer" <?= (isset($template['has_border']) && $template['has_border']) ? 'checked' : '' ?>>
                            <div class="w-8 h-4 bg-zinc-300 dark:bg-zinc-700 rounded-full peer peer-checked:bg-indigo-600 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-4"></div>
                        </div>
                    </label>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2">Kurum Bilgileri</h4>
                    <div class="space-y-1">
                        <button onclick="insertVar('{{KURUM_ADI}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>Kurum Adı</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{KURUM_ADI}}</span>
                        </button>
                        <button onclick="insertVar('{{YETKILI_AD}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>Yetkili Adı</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{YETKILI_AD}}</span>
                        </button>
                        <button onclick="insertVar('{{YETKILI_UNVAN}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>Yetkili Ünvanı</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{YETKILI_UNVAN}}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2">Personel Bilgileri</h4>
                    <div class="space-y-1">
                        <button onclick="insertVar('{{PERSONEL_AD}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>Adı Soyadı</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{PERSONEL_AD}}</span>
                        </button>
                        <button onclick="insertVar('{{TC_NO}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>T.C. Kimlik No</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{TC_NO}}</span>
                        </button>
                        <button onclick="insertVar('{{GOREV}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>Görevi</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{GOREV}}</span>
                        </button>
                        <button onclick="insertVar('{{EGITIM_DURUMU}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>Eğitim Durumu</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{EGITIM_DURUMU}}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2">Sözleşme Detayları</h4>
                    <div class="space-y-1">
                        <button onclick="insertVar('{{BRUT_UCRET}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>Brüt Ücret</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{BRUT_UCRET}}</span>
                        </button>
                        <button onclick="insertVar('{{BASLANGIC_TARIHI}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>Başlangıç Tarihi</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{BASLANGIC_TARIHI}}</span>
                        </button>
                        <button onclick="insertVar('{{BITIS_TARIHI}}')" class="w-full text-left px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md transition-all flex items-center justify-between group">
                            <span>Bitiş Tarihi</span>
                            <span class="text-[10px] font-mono opacity-0 group-hover:opacity-100 bg-zinc-100 dark:bg-zinc-700 px-1 rounded transition-opacity">{{BITIS_TARIHI}}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 min-w-0 flex flex-col overflow-hidden bg-zinc-100 dark:bg-zinc-950">
            <div class="relative z-50 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-100/95 dark:bg-zinc-950/95 backdrop-blur-sm">
                <div id="toolbar-container" class="relative w-full bg-white dark:bg-zinc-900 border-y border-zinc-200 dark:border-zinc-800 rounded-none shadow-none z-50 overflow-visible">
                    <div id="toolbar-scroll" class="px-2 py-1 overflow-visible">
                        <!-- Toolbar will be appended here -->
                    </div>
                </div>
            </div>

            <!-- Main Content: Editor -->
            <!-- Main Content: Editor -->
            <div id="editor-viewport" class="relative z-0 flex-1 overflow-y-auto bg-zinc-100 dark:bg-zinc-950 flex flex-row items-start justify-center py-8 scrollbar-hide">
                <div id="a4-page" class="contract-document w-[210mm] bg-white shadow-[0_20px_50px_rgba(0,0,0,0.08)] border border-zinc-200 relative flex-shrink-0 <?= (isset($template['has_border']) && $template['has_border']) ? 'has-border' : '' ?>" style="padding: 1cm 1.5cm; min-height: 297mm; overflow: visible !important;">
                    <div id="quill-editor" style="overflow: visible !important;"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    #a4-page {
        background-image: linear-gradient(
            to bottom,
            transparent 0,
            transparent calc(297mm - 1px),
            #e2e8f0 calc(297mm - 1px),
            #e2e8f0 297mm,
            transparent 297mm
        ) !important;
        background-size: 100% 297mm !important;
    }
    /* Horizontal Toolbar Styling */
    .ql-toolbar.ql-snow {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        align-items: stretch !important;
        width: auto !important;
    }
    
    .ql-toolbar.ql-snow .ql-formats {
        margin: 0 !important;
        padding: 0 10px !important;
        display: flex !important;
        flex-direction: row !important;
        gap: 6px !important;
        border-bottom: none !important;
        border-right: 1px solid #e4e4e7 !important;
        width: auto !important;
        align-items: center !important;
        flex-shrink: 0 !important;
    }

    .ql-snow .ql-picker.ql-font, .ql-snow .ql-picker.ql-size, .ql-snow .ql-picker.ql-header {
        width: auto !important;
        min-width: 96px !important;
    }

    .ql-snow .ql-picker {
        position: relative !important;
        z-index: 60 !important;
    }

    .ql-snow .ql-picker-label {
        padding: 0 4px !important;
        font-size: 11px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="10px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="10px"]::before { content: '10px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="11px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="11px"]::before { content: '11px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="12px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="12px"]::before { content: '12px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="13px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="13px"]::before { content: '13px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="14px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="14px"]::before { content: '14px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="16px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="16px"]::before { content: '16px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="18px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="18px"]::before { content: '18px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="20px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="20px"]::before { content: '20px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="24px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="24px"]::before { content: '24px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="28px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="28px"]::before { content: '28px'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="32px"]::before,
    .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="32px"]::before { content: '32px'; }
    .ql-snow .ql-picker.ql-header .ql-picker-label::before { content: 'Normal'; }
    .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="false"]::before,
    .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="false"]::before { content: 'Normal'; }
    .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="1"]::before,
    .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="1"]::before { content: 'H1'; }
    .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="2"]::before,
    .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="2"]::before { content: 'H2'; }
    .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="3"]::before,
    .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="3"]::before { content: 'H3'; }

    .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="times"]::before,
    .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="times"]::before { content: 'Times New Roman'; font-family: 'Times New Roman'; }
    .ql-snow .ql-picker.ql-font .ql-picker-label::before { content: 'Yazı Tipi'; }
    .ql-snow .ql-picker.ql-size .ql-picker-label::before { content: 'Boyut'; }

    .ql-toolbar.ql-snow .ql-formats:last-child {
        border-right: none !important;
    }

    .ql-snow .ql-picker.ql-font,
    .ql-snow .ql-picker.ql-size,
    .ql-snow .ql-picker.ql-header { width: auto !important; }
    .ql-snow .ql-picker-label { padding: 0 !important; justify-content: center !important; border: none !important; }
    .ql-snow .ql-picker-options { 
        left: 0 !important; 
        top: calc(100% + 8px) !important; 
        margin-left: 0 !important;
        z-index: 9999 !important;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1) !important;
        border-radius: 8px !important;
        border: 1px solid #e4e4e7 !important;
    }

    .has-border::after {
        content: "";
        position: absolute;
        top: 0.8cm;
        left: 2cm;
        right: 2cm;
        bottom: 1.5cm;
        border: 2px solid #000 !important;
        pointer-events: none;
    }

    .dark .ql-toolbar.ql-snow .ql-formats {
        border-right-color: #27272a !important;
    }

    /* Table borders */
    .contract-document table.no-borders td,
    .contract-document table.no-borders,
    .contract-document table[data-no-borders="true"] td,
    .contract-document table[data-no-borders="true"],
    .contract-document table[style*="border: none"] td,
    .contract-document table[style*="border: none"],
    .contract-document table[style*="border-style: none"] td,
    .contract-document table[style*="border-style: none"],
    .contract-document td[style*="border: none"],
    .contract-document td[style*="border-style: none"],
    table.no-borders td,
    table.no-borders,
    table[data-no-borders="true"] td,
    table[data-no-borders="true"],
    table[style*="border: none"] td,
    table[style*="border: none"],
    table[style*="border-style: none"] td,
    table[style*="border-style: none"],
    td[style*="border: none"],
    td[style*="border-style: none"] {
        border: none !important;
        border-width: 0 !important;
    }

    .contract-document table.has-borders td,
    .contract-document table.has-borders {
        border: 1px solid #000 !important;
    }
</style>

<!-- Dialogs -->
<!-- Notification Dialog -->
<dialog id="notification-dialog" class="dialog" aria-labelledby="notification-title" aria-describedby="notification-description">
  <div>
    <header>
      <h2 id="notification-title"></h2>
      <p id="notification-description"></p>
    </header>

    <footer>
      <button class="btn-primary" onclick="document.getElementById('notification-dialog').close()">Tamam</button>
    </footer>
  </div>
</dialog>

<!-- Confirm Dialog -->
<dialog id="confirm-dialog" class="dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-description">
  <div>
    <header>
      <h2 id="confirm-dialog-title">Emin misiniz?</h2>
      <p id="confirm-dialog-description">Tüm mevcut içeriğiniz silinecek ve varsayılan şablon yüklenecek. Bu işlem geri alınamaz.</p>
    </header>

    <footer>
      <button class="btn-outline" onclick="document.getElementById('confirm-dialog').close()">İptal</button>
      <button id="confirmResetBtn" class="btn-primary" onclick="document.getElementById('confirm-dialog').close()">Evet, Sıfırla</button>
    </footer>
  </div>
</dialog>

<!-- Table Insert Dialog -->
<dialog id="table-insert-dialog" class="dialog">
  <div>
    <header>
      <h2 id="table-dialog-title">Yeni Tablo Ekle</h2>
      <p id="table-dialog-description">Oluşturmak istediğiniz tablonun satır ve sütun sayısını giriniz.</p>
    </header>

    <div class="flex gap-4" style="margin: 1rem 0;">
      <div class="flex-1">
        <label for="table-dialog-rows" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Satır Sayısı</label>
        <input type="number" id="table-dialog-rows" min="1" max="50" value="3" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all" required>
      </div>
      <div class="flex-1">
        <label for="table-dialog-cols" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sütun Sayısı</label>
        <input type="number" id="table-dialog-cols" min="1" max="20" value="3" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all" required>
      </div>
    </div>

    <footer>
      <button class="btn-outline" onclick="document.getElementById('table-insert-dialog').close()">İptal</button>
      <button id="insertTableConfirmBtn" class="btn-primary">Ekle</button>
    </footer>
  </div>
</dialog>

<!-- Table Properties Dialog -->
<dialog id="table-properties-dialog" class="dialog" style="max-width: 450px; border-radius: 12px;">
  <div>
    <header class="mb-4">
      <h2 id="table-props-title" class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Tablo Özellikleri</h2>
      <p class="text-xs text-zinc-500 dark:text-zinc-400">Tüm tablonun veya seçili hücrelerin özelliklerini buradan ayarlayabilirsiniz.</p>
    </header>

    <div class="tabs w-full" id="table-props-tabs">
      <nav role="tablist" aria-orientation="horizontal" class="w-full">
        <button type="button" role="tab" id="table-props-tab-table" onclick="switchTablePropsTab('table')" aria-controls="table-props-panel-table" aria-selected="true" tabindex="0">Tablo</button>

        <button type="button" role="tab" id="table-props-tab-row" onclick="switchTablePropsTab('row')" aria-controls="table-props-panel-row" aria-selected="false" tabindex="0">Satır</button>

        <button type="button" role="tab" id="table-props-tab-col" onclick="switchTablePropsTab('col')" aria-controls="table-props-panel-col" aria-selected="false" tabindex="0">Sütun</button>
      </nav>

      <div role="tabpanel" id="table-props-panel-table" aria-labelledby="table-props-tab-table" tabindex="-1" aria-selected="true">
        <div class="flex gap-4 items-center mb-3 mt-4">
          <div class="flex-1">
            <label for="table-border-toggle" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Tablo Kenarlığı</label>
            <select id="table-border-toggle" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all">
              <option value="show">Göster</option>
              <option value="hide">Gizle</option>
            </select>
          </div>
          <div class="flex-1">
            <label for="table-alignment-select" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Tablo Hizalaması</label>
            <select id="table-alignment-select" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all">
              <option value="center">Orta</option>
              <option value="left">Sol</option>
              <option value="right">Sağ</option>
            </select>
          </div>
        </div>
        <div class="flex gap-4 items-center mb-4">
          <div class="flex-1">
            <label for="table-row-height-input" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Satır Yüksekliği (Tüm Tablo)</label>
            <div class="flex gap-1">
              <input type="number" id="table-row-height-input" value="40" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all">
              <select id="table-row-height-unit" class="w-auto px-2 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none transition-all">
                <option value="px">px</option>
                <option value="cm">cm</option>
                <option value="mm">mm</option>
                <option value="pt">pt</option>
              </select>
            </div>
          </div>
          <div class="flex-1">
            <label for="table-col-width-input" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sütun Genişliği (Tüm Tablo)</label>
            <div class="flex gap-1">
              <input type="number" id="table-col-width-input" value="120" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all">
              <select id="table-col-width-unit" class="w-auto px-2 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none transition-all">
                <option value="px">px</option>
                <option value="cm">cm</option>
                <option value="mm">mm</option>
                <option value="pt">pt</option>
                <option value="%">%</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div role="tabpanel" id="table-props-panel-row" aria-labelledby="table-props-tab-row" tabindex="-1" aria-selected="false" hidden>
        <div class="flex gap-4 items-center mb-4 mt-4">
          <div class="flex-1">
            <label for="row-height-input" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Satır Yüksekliği (Seçili Satır)</label>
            <input type="number" id="row-height-input" value="40" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all">
          </div>
          <div class="flex-1">
            <label for="row-height-unit" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Birimi</label>
            <select id="row-height-unit" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all">
              <option value="px">Piksel (px)</option>
              <option value="cm">Santimetre (cm)</option>
              <option value="mm">Milimetre (mm)</option>
              <option value="pt">Nokta (pt)</option>
            </select>
          </div>
        </div>
      </div>

      <div role="tabpanel" id="table-props-panel-col" aria-labelledby="table-props-tab-col" tabindex="-1" aria-selected="false" hidden>
        <div class="flex gap-4 items-center mb-4 mt-4">
          <div class="flex-1">
            <label for="col-width-input" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sütun Genişliği (Seçili Sütun)</label>
            <input type="number" id="col-width-input" value="120" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all">
          </div>
          <div class="flex-1">
            <label for="col-width-unit" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Birimi</label>
            <select id="col-width-unit" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-all">
              <option value="px">Piksel (px)</option>
              <option value="cm">Santimetre (cm)</option>
              <option value="mm">Milimetre (mm)</option>
              <option value="pt">Nokta (pt)</option>
              <option value="%">Yüzde (%)</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <footer>
      <button class="btn-outline" onclick="document.getElementById('table-properties-dialog').close()">İptal</button>
      <button id="saveTablePropertiesBtn" class="btn-primary">Tamam</button>
    </footer>
  </div>
</dialog>

<!-- Table Actions Dropdown Context Menu -->
<div id="table-context-menu" class="hidden fixed z-[1000] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg py-1 w-52 text-sm text-zinc-700 dark:text-zinc-300 select-none">
    <button id="ctx-add-row-above" class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-3 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7-7-7 7"/></svg>
        Üstüne Satır Ekle
    </button>
    <button id="ctx-add-row-below" class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-3 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7 7-7"/></svg>
        Altına Satır Ekle
    </button>
    <div class="border-t border-zinc-200 dark:border-zinc-800 my-1"></div>
    <button id="ctx-add-col-before" class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-3 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        Sola Sütun Ekle
    </button>
    <button id="ctx-add-col-after" class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-3 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        Sağa Sütun Ekle
    </button>
    <div class="border-t border-zinc-200 dark:border-zinc-800 my-1"></div>
    <button id="ctx-delete-row" class="w-full text-left px-4 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 flex items-center gap-3 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
        Satırı Sil
    </button>
    <button id="ctx-delete-col" class="w-full text-left px-4 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 flex items-center gap-3 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
        Sütunu Sil
    </button>
    <div class="border-t border-zinc-200 dark:border-zinc-800 my-1"></div>
    <button id="ctx-set-table-props" class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-3 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
        Tablo Özellikleri
    </button>
    <div class="border-t border-zinc-200 dark:border-zinc-800 my-1"></div>
    <button id="ctx-delete-table" class="w-full text-left px-4 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 flex items-center gap-3 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
        Tabloyu Sil
    </button>
</div>

<script>
    // Register Fonts and Sizes
    var Font = Quill.import('formats/font');
    Font.whitelist = ['times', 'serif', 'monospace'];
    Quill.register(Font, true);

    var SizeStyle = Quill.import('attributors/style/size');
    SizeStyle.whitelist = ['10px', '11px', '12px', '13px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'];
    Quill.register(SizeStyle, true);

    // Custom style attributors to allow heights and line heights on blocks
    try {
        var Parchment = Quill.import('parchment');
        var StyleAttributor = null;
        try {
            StyleAttributor = Quill.import('attributors/style/size').constructor;
        } catch(err) {
            if (Parchment && Parchment.Style) StyleAttributor = Parchment.Style;
        }

        if (StyleAttributor) {
            var HeightStyle = new StyleAttributor('height', 'height');
            Quill.register(HeightStyle, true);

            var LineHeightStyle = new StyleAttributor('line-height', 'line-height');
            Quill.register(LineHeightStyle, true);

            var MinHeightStyle = new StyleAttributor('min-height', 'min-height');
            Quill.register(MinHeightStyle, true);

            var BorderStyle = new StyleAttributor('border', 'border');
            Quill.register(BorderStyle, true);

            var BorderStyleStyle = new StyleAttributor('border-style', 'border-style');
            Quill.register(BorderStyleStyle, true);

            var BorderWidthStyle = new StyleAttributor('border-width', 'border-width');
            Quill.register(BorderWidthStyle, true);

            var BorderColorStyle = new StyleAttributor('border-color', 'border-color');
            Quill.register(BorderColorStyle, true);

            var BorderImageStyle = new StyleAttributor('border-image', 'border-image');
            Quill.register(BorderImageStyle, true);

            var WidthStyle = new StyleAttributor('width', 'width');
            Quill.register(WidthStyle, true);

            var MinWidthStyle = new StyleAttributor('min-width', 'min-width');
            Quill.register(MinWidthStyle, true);

            var MaxWidthStyle = new StyleAttributor('max-width', 'max-width');
            Quill.register(MaxWidthStyle, true);

            var PaddingStyle = new StyleAttributor('padding', 'padding');
            Quill.register(PaddingStyle, true);

            var PaddingTopStyle = new StyleAttributor('padding-top', 'padding-top');
            Quill.register(PaddingTopStyle, true);

            var PaddingBottomStyle = new StyleAttributor('padding-bottom', 'padding-bottom');
            Quill.register(PaddingBottomStyle, true);

            var FontSizeStyle = new StyleAttributor('font-size', 'font-size');
            Quill.register(FontSizeStyle, true);

            var VerticalAlignStyle = new StyleAttributor('vertical-align', 'vertical-align');
            Quill.register(VerticalAlignStyle, true);
        }
    } catch(e) {
        console.warn('Parchment initialization skipped:', e);
    }



    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            table: true,
            toolbar: [
                [{ 'font': ['times', 'serif', 'monospace'] }],
                [{ 'size': ['10px', '11px', '12px', '13px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'] }],
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'script': 'sub' }, { 'script': 'super' }],
                [{ 'color': [] }, { 'background': [] }],
                ['blockquote', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['table'],
                ['clean']
            ]
        },
        placeholder: 'Sözleşme içeriğini buraya yazın...'
    });

    // Page Border Toggle Logic
    const borderToggle = document.getElementById('page-border-toggle');
    if (borderToggle) {
        borderToggle.addEventListener('change', function() {
            const a4Page = document.getElementById('a4-page');
            if (this.checked) {
                a4Page.classList.add('has-border');
            } else {
                a4Page.classList.remove('has-border');
            }
        });
    }

    // Move toolbar to the sticky container with a small delay to ensure initialization
    setTimeout(() => {
        const toolbar = document.querySelector('.ql-toolbar');
        if (toolbar) {
            document.getElementById('toolbar-scroll').appendChild(toolbar);
            toolbar.style.border = 'none';

            // Add custom table buttons
            const formats = document.createElement('span');
            formats.className = 'ql-formats';
            
            const btnInsert = document.createElement('button');
            btnInsert.type = 'button';
            btnInsert.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" title="Tablo Ekle"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
            `;
            btnInsert.title = 'Tablo Ekle';
            btnInsert.style.width = 'auto';
            btnInsert.style.display = 'inline-flex';
            btnInsert.style.alignItems = 'center';
            btnInsert.style.gap = '4px';
            btnInsert.style.padding = '0 6px';
            
            btnInsert.addEventListener('click', function() {
                document.getElementById('table-insert-dialog').showModal();
            });

            // Handle table dialog confirm
            const confirmBtn = document.getElementById('insertTableConfirmBtn');
            if (confirmBtn) {
                // Remove previous clone if any
                const newConfirmBtn = confirmBtn.cloneNode(true);
                confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
                
                newConfirmBtn.addEventListener('click', function() {
                    const rows = document.getElementById('table-dialog-rows').value;
                    const cols = document.getElementById('table-dialog-cols').value;
                    if (rows && cols) {
                        let tableHTML = '<table class="has-borders" style="width:100%; border-collapse:collapse; margin-bottom:1em;">';
                        for (let i = 0; i < parseInt(rows); i++) {
                            tableHTML += '<tr>';
                            for (let j = 0; j < parseInt(cols); j++) {
                                tableHTML += '<td style="border:1px solid #000; padding:6px 10px;">&nbsp;</td>';
                            }
                            tableHTML += '</tr>';
                        }
                        tableHTML += '</table><p><br></p>';
                        
                        const range = quill.getSelection(true);
                        quill.clipboard.dangerouslyPasteHTML(range.index, tableHTML, 'user');
                    }
                    document.getElementById('table-insert-dialog').close();
                });
            }

            const btnBorder = document.createElement('button');
            btnBorder.type = 'button';
            btnBorder.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" title="Seçili Tablo Kenarlığını Gizle/Göster"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="15" y1="15" x2="21" y2="21"/></svg>
            `;
            btnBorder.title = 'Seçili Tablo Kenarlığını Gizle/Göster';
            btnBorder.style.width = 'auto';
            btnBorder.style.display = 'inline-flex';
            btnBorder.style.alignItems = 'center';
            btnBorder.style.gap = '4px';
            btnBorder.style.padding = '0 6px';
            
            btnBorder.addEventListener('click', function() {
                const range = quill.getSelection();
                if (range) {
                    const [line, offset] = quill.getLine(range.index);
                    if (line && line.domNode) {
                        const table = line.domNode.closest('table');
                        if (table) {
                            if (isTableBorderless(table)) {
                                table.classList.remove('no-borders');
                                table.classList.add('has-borders');
                                table.removeAttribute('data-no-borders');
                                table.style.setProperty('border', '1px solid #000', 'important');
                                table.querySelectorAll('td').forEach(td => {
                                    td.style.setProperty('border', '1px solid #000', 'important');
                                    td.removeAttribute('data-no-borders');
                                    td.style.removeProperty('min-height');
                                });
                            } else {
                                table.classList.add('no-borders');
                                table.classList.remove('has-borders');
                                table.setAttribute('data-no-borders', 'true');
                                table.style.setProperty('border', 'none', 'important');
                                table.querySelectorAll('td').forEach(td => {
                                    td.style.setProperty('border', 'none', 'important');
                                    td.setAttribute('data-no-borders', 'true');
                                });
                            }
                        } else {
                            alert('Lütfen önce bir tablo içine tıklayın!');
                        }
                    }
                }
            });

            formats.appendChild(btnInsert);
            formats.appendChild(btnBorder);
            toolbar.appendChild(formats);
        }
    }, 150);

    <?php if (isset($defaultTemplate) && !empty($defaultTemplate['content'])): ?>
    const defaultTemplate = <?= json_encode($defaultTemplate['content']) ?>;
    <?php else: ?>
    const defaultTemplate = `
        <h1>HİZMET SÖZLEŞMESİ</h1>
        <h2>(657 sayılı Devlet Memurları Kanununun 4/B maddesi uyarınca çalıştırılacak sözleşmeli personel için)</h2>
        <p class="first-paragraph"><span class="variable-tag">{{KURUM_ADI}}</span> adına hareket eden Rektör <strong><span class="variable-tag">{{YETKILI_AD}}</span></strong> ile <span class="variable-tag">{{GOREV}}</span> görevinde istihdam edilecek <strong><span class="variable-tag">{{PERSONEL_AD}}</span></strong> arasında aşağıdaki şartlarda bu hizmet sözleşmesi yapılmıştır.</p>
        <p>Sözleşmede geçen “Kurum” deyimi, <strong><span class="variable-tag">{{KURUM_ADI}}</span></strong>’ni, “ilgili” deyimi <strong><span class="variable-tag">{{PERSONEL_AD}}</span></strong> isimli sözleşmeli personeli tanımlamaktadır.</p>
        <p><strong>Madde 1-</strong> İlgili, Kurumca gösterilecek görev yerlerinde mevzuat ve verilecek emirler çerçevesinde göreviyle ilgili kendisine verilen tüm işleri yapmayı taahhüt eder.</p>
        <p><strong>Madde 2-</strong> İlgili, görevi sırasında edindiği gizli bilgileri, görevinden ayrılsa bile Kurumun izni olmadan açıklayamaz. İlgili, görevi sona erdiği zaman elinde bulunan Kuruma ait araç, gereç ve belgeleri geri vermek zorundadır.</p>
        <p><strong>Madde 3-</strong> İlgilinin çalışma saat ve süreleri, Devlet memurları için saptanan çalışma saat ve sürelerinin aynıdır. Ancak, haftanın belli gün ve saatlerinde kısmi zamanlı olarak çalışanların çalışma saat ve süreleri, devlet memurları için saptanan çalışma saat ve süreleri esas alınarak Kurumca belirlenir.<br>Ayrıca, ilgili kendisine verilen işleri bitirene kadar, normal çalışma saatleri dışında da çalışmak zorundadır. Normal çalışma saatleri dışında veya tatil günlerinde yapacağı çalışmalar karşılığında ilgiliye herhangi bir ek ücret ödenmez.</p>
        <p><strong>Madde 4-</strong> İlgiliye, yapacağı hizmete karşılık sözleşme süresince her ay brüt (<strong><span class="variable-tag">{{EGITIM_DURUMU}}</span></strong>) <strong><span class="variable-tag">{{BRUT_UCRET}}</span> TL</strong> ücret ödenir. Ödemeler her aybaşında peşin olarak yapılır. Devlet Memuru aylıklarında genel bir artış yapılması durumunda, bu sözleşme ücreti yeni bir karar alınmasına gerek bulunmaksızın aynı oran ve/veya miktarda artırılır.<br>Ay sonundan önce ayrılmalarda, 5510 sayılı Sosyal Sigortalar ve Genel Sağlık Sigortası Kanunu hükümlerine göre aylık bağlanması veya ölüm sebebiyle sözleşmeye son verilmesi halleri dışında, kalan günlere düşen ücret tutarı ilgiliden doğrudan geri alınır.</p>
        <p><strong>Madde 5-</strong> Sözleşmeli Personel Çalıştırılmasına İlişkin Esasların 4.maddesinde yer alan hükümler kapsamında ilgili, görev yeri dışında geçici olarak görevlendirildiğinde gündelik ve yol giderleri, her yıl bütçe kanunları ile 6245 Sayılı Harcırah Kanununun 33. Maddesine göre belirlenen aylık kadro derecesi 5-15 olanlar için tespit edilen esaslara göre hesaplanır.</p>
        <p><strong>Madde 6-</strong> İlgili, dışarıda kazanç getirici başka bir iş yapamaz. (Sözleşmeli Personel Çalıştırılmasına İlişkin Esasların 8 nci maddesinde sayılanlar hariç)</p>
        <p><strong>Madde 7-</strong> 217 Sayılı Kanun Hükmünde Kararnamenin 2 nci maddesinde belirtilen kurumlarda geçen hizmet süresi, bir yıldan on yıla kadar olan personele yirmi gün, on yıldan fazla olanlara otuz gün ücretli yıllık izin verilir. Rapor sebebiyle, Sosyal Sigortalar Kurumunca ödenen geçici iş göremezlik tazminatı ilginin ücretinden düşülür.</p>
        <p><strong>Madde 8-</strong> İlgili kanunlar uyarınca resmi kıyafet giymek zorunda bulunan personel için 14/9/1991 tarihli ve 91/2268 sayılı Bakanlar Kurulu kararı ile yürürlüğe konulan Memurlara Giyecek Yardımı Yönetmeliği'nde öngörülmüş olan giyim eşyalarından emsali sözlemeli personel de aynı esas ve usüller çerçevesinde faydalandırılır.</p>
        <p><strong>Madde 9 -</strong>a) İlgilinin sözleşmesi Sözleşmeli Personel Çalıştırılmasına İlişkin Esasların ek 6.maddesinde belirtilen hallerden herhangi birinin gerçekleşmesi halinde sona erer.Bu durum kurumca ilgiliye yazılı olarak tebliğ edilir. Tebliğatta belirtilecek günden geçerli olmak üzere sözleşme sona erer.<br>b) İlgilinin 65 yaşını doldurduğu tarihte hiçbir işleme gerek kalmaksızın sözleşmesi sona erer.<br>c) Taraflar bir ay önce ihbar etmek şartıyla sebeb göstermeksizin sözleşmeyi feshedebilir.</p>
        <p><strong>Madde 10-</strong> İlgilinin disiplin işlemlerinde; 657 Sayılı Devlet Memurları kanununda yer alan disiplin hükümleri uygulanır.</p>
        <p><strong>Madde 11-</strong> Sözleşme düzenlenmesinin gerektirdiği her türlü giderler Kurumca karşılanır.</p>
        <p><strong>Madde 12-</strong> Bu sözleşmeden doğacak uyuşmazlık Düzce Mahkemelerince çözümlenir.</p>
        <p><strong>Madde 13-</strong> İş bu sözleşme <strong><span class="variable-tag">{{BASLANGIC_TARIHI}}</span></strong> tarihinden <strong><span class="variable-tag">{{BITIS_TARIHI}}</span></strong> tarihine kadar geçerlidir.</p>
        <p><strong>Madde 14-</strong> Bu sözleşme, 657 Sayılı Kanunun 4/B maddesi ve Sözleşmeli Personel Çalıştırılmasına İlişkin Esaslar uyarınca akdedilmiştir. İlgilinin kullanabileceği izinler, ilgiliye ödenecek İş Sonu Tazminatı ve bu sözleşmede yer almayan diğer hususlar hakkında anılan Esaslara ilişkin hükümler ve aykırı olmamak kaydıyla diğer kanun hükümleri çerçevesinde işlem yapılır.</p>
        
        <div id="signatures-section" style="margin-top: 40px; width: 100%;">
            <table class="signatures-table" style="width: 100% !important; table-layout: fixed !important; border-collapse: collapse !important; border: none !important;">
                <tr>
                    <td style="width: 50% !important; text-align: center !important; vertical-align: top !important; padding: 0 !important; overflow-wrap: break-word !important; word-break: normal !important; box-sizing: border-box !important;">
                        <span class="table-border-none" style="display:none;"></span>
                        <strong style="display: block !important;">Sözleşmeli Personel</strong>
                        <span class="variable-tag" style="display: block !important;">{{PERSONEL_AD}}</span>
                        <span class="variable-tag" style="display: block !important;">{{GOREV}}</span>
                    </td>
                    <td style="width: 50% !important; text-align: center !important; vertical-align: top !important; padding: 0 !important; overflow-wrap: break-word !important; word-break: normal !important; box-sizing: border-box !important;">
                        <strong style="display: block !important;">Kurum Yetkilisi</strong>
                        <strong style="display: block !important;"><span class="variable-tag">{{YETKILI_AD}}</span></strong>
                        <span class="variable-tag" style="display: block !important;">{{YETKILI_UNVAN}}</span>
                    </td>
                </tr>
            </table>
        </div>
    `;
    <?php endif; ?>

    function loadEditorHtml(html) {
        if (!quill || !quill.root) return;
        quill.root.innerHTML = html || '';
        quill.update('silent');
    }

    <?php if (isset($template) && !empty($template['content'])): ?>
    loadEditorHtml(<?= json_encode($template['content']) ?>);
    <?php else: ?>
    loadEditorHtml(defaultTemplate);
    <?php endif; ?>

    function hasBorderNoneStyle(el) {
        if (!el) return false;
        const inlineStyle = el.getAttribute('style') || '';
        return el.style.border === 'none' ||
               el.style.borderWidth === '0px' ||
               inlineStyle.includes('border: none') ||
               inlineStyle.includes('border-style: none') ||
               inlineStyle.includes('border-width: 0');
    }

    function isBorderlessCell(td) {
        if (!td) return false;
        return td.hasAttribute('data-no-borders') || hasBorderNoneStyle(td);
    }

    function isTableBorderless(table) {
        if (!table) return false;
        const allTds = Array.from(table.querySelectorAll('td'));
        const allCellsBorderless = allTds.length > 0 && allTds.every(isBorderlessCell);

        return table.hasAttribute('data-no-borders') ||
               table.querySelector('[data-no-borders]') ||
               table.classList.contains('no-borders') ||
               hasBorderNoneStyle(table) ||
               allCellsBorderless ||
               allTds.some(isBorderlessCell) ||
               table.classList.contains('signatures-table');
    }

    window.applyTableBordersFromMarkers = function() {
        if (!quill || !quill.root) return;
        quill.root.querySelectorAll('table').forEach(table => {
            const firstTd = table.querySelector('td');
            const hasBorderlessMarker = isTableBorderless(table);

            if (hasBorderlessMarker) {
                table.classList.add('no-borders');
                table.classList.remove('has-borders');
                table.setAttribute('data-no-borders', 'true');
                table.style.setProperty('border', 'none', 'important');
                table.querySelectorAll('td').forEach(td => {
                    td.style.setProperty('border', 'none', 'important');
                    td.setAttribute('data-no-borders', 'true');
                    if (firstTd === td) {
                        td.style.setProperty('min-height', '2.99px', 'important');
                    }
                });
            } else {
                table.classList.remove('no-borders');
                table.classList.add('has-borders');
                table.removeAttribute('data-no-borders');
                table.style.setProperty('border', '1px solid #000', 'important');
                table.querySelectorAll('td').forEach(td => {
                    td.style.setProperty('border', '1px solid #000', 'important');
                    td.removeAttribute('data-no-borders');
                    td.style.removeProperty('min-height');
                });
            }

            // Restore row heights from tagged data-attributes!
            table.querySelectorAll('tr').forEach(tr => {
                const firstTdOfTr = tr.querySelector('td');
                const h = tr.getAttribute('data-row-height') || (firstTdOfTr && firstTdOfTr.getAttribute('data-row-height'));
                if (h && window.setRowHeight) {
                    window.setRowHeight(tr, h);
                }
            });

            // Restore column widths from tagged data-attributes!
            table.querySelectorAll('td').forEach(td => {
                const w = td.getAttribute('data-col-width');
                if (w) {
                    td.setAttribute('width', w);
                    td.style.setProperty('width', w, 'important');
                    td.style.setProperty('min-width', w, 'important');
                    td.style.setProperty('max-width', w, 'important');
                }
            });
        });
    };
    setTimeout(function() {
        window.applyTableBordersFromMarkers();
    }, 150);


    function resetToDefault() {
        document.getElementById('confirm-dialog').showModal();
    }

    const confirmResetBtn = document.getElementById('confirmResetBtn');
    if (confirmResetBtn) {
        confirmResetBtn.addEventListener('click', function() {
            document.getElementById('confirm-dialog').close();
            loadEditorHtml(defaultTemplate);
            if (window.applyTableBordersFromMarkers) window.applyTableBordersFromMarkers();
            showNotification('Bilgi', 'Varsayılan şablon yüklendi. Kaydetmeyi unutmayın.', true);
        });
    }

    function insertVar(val) {
        const range = quill.getSelection(true);
        quill.insertText(range.index, val, 'user');
        quill.formatText(range.index, val.length, {
            'color': '#4338ca',
            'bold': true
        });
        quill.setSelection(range.index + val.length + 1);
    }

    function preserveLeadingWhitespace(root) {
        if (!root) return;
        const blockSelector = 'p, h1, h2, h3, h4, h5, h6, li, blockquote';

        root.querySelectorAll(blockSelector).forEach(block => {
            const firstChild = block.firstChild;
            if (!firstChild || firstChild.nodeType !== Node.TEXT_NODE) return;

            const text = firstChild.textContent || '';
            const leadingMatch = text.match(/^[\t ]+/);
            if (!leadingMatch) return;

            const preserved = leadingMatch[0]
                .replace(/\t/g, '\u00A0\u00A0\u00A0\u00A0')
                .replace(/ /g, '\u00A0');

            firstChild.textContent = preserved + text.slice(leadingMatch[0].length);
        });
    }

    function printContract() {
        preserveLeadingWhitespace(quill.root);
        const content = quill.root.innerHTML;
        const hasBorder = document.getElementById('page-border-toggle').checked;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="tr">
                <head>
                    <meta charset="UTF-8">
                    <title>Hizmet Sözleşmesi - Yazdır</title>
                    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
                    <link rel="stylesheet" href="<?php echo routeUrl('assets/css/contract-document.css'); ?>">
                    <style>
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
                    </style>
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
                        console.log('Print content loaded. Length:', document.getElementById('print-content').innerHTML.length);
                        window.onload = function() {
                            setTimeout(() => {
                                window.print();
                            }, 800);
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }

    function showNotification(title, message, isSuccess = true) {
        const dialog = document.getElementById('notification-dialog');
        const titleEl = document.getElementById('notification-title');
        const descEl = document.getElementById('notification-description');
        
        titleEl.innerText = title;
        descEl.innerText = message;
        
        dialog.showModal();
    }

    document.getElementById('saveBtn').addEventListener('click', function() {
        preserveLeadingWhitespace(quill.root);
        // Tag all tables with persistent data-* attributes
        quill.root.querySelectorAll('table').forEach(table => {
            const hasNoBorder = isTableBorderless(table);
            if (hasNoBorder) {
                table.setAttribute('data-no-borders', 'true');
                table.querySelectorAll('td').forEach(td => {
                    td.setAttribute('data-no-borders', 'true');
                });
            } else {
                table.removeAttribute('data-no-borders');
                table.querySelectorAll('td').forEach(td => {
                    td.removeAttribute('data-no-borders');
                });
            }

            // Tag row heights!
            table.querySelectorAll('tr').forEach(tr => {
                const h = tr.getAttribute('height') || tr.style.height;
                if (h) {
                    tr.setAttribute('data-row-height', h);
                    tr.querySelectorAll('td').forEach(td => {
                        td.setAttribute('data-row-height', h);
                    });
                }
            });

            // Tag column widths!
            table.querySelectorAll('td').forEach(td => {
                const w = td.getAttribute('width') || td.style.width;
                if (w) {
                    td.setAttribute('data-col-width', w);
                }
            });
        });

        const content = quill.root.innerHTML;
        const btn = this;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Kaydediliyor...
        `;

        const formData = new FormData();
        formData.append('content', content);
        formData.append('name', 'Hizmet Sözleşmesi Şablonu');
        formData.append('has_border', document.getElementById('page-border-toggle').checked ? 1 : 0);

        fetch('<?= routeUrl('sozlesme-taslagi-kaydet') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (data.success) {
                    showNotification('Başarılı', data.message, true);
                } else {
                    showNotification('Hata', data.message, false);
                }
            } catch(e) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                showNotification('Sunucu Hatası', 'Sunucudan geçersiz veri döndü: ' + text.substring(0, 150), false);
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            showNotification('Bağlantı Hatası', 'Veritabanına kaydedilirken bir hata oluştu: ' + error.message, false);
        });
    });

    document.getElementById('printBtn').addEventListener('click', printContract);

    // Table context menu logic
    let currentCtxTd = null;
    let currentCtxTr = null;
    let currentCtxTable = null;

    function addRow(tr, table, above = true) {
        const clonedTable = table.cloneNode(true);
        const rowIndex = tr.rowIndex;
        const targetTr = clonedTable.rows[rowIndex];
        
        const newTr = document.createElement('tr');
        const colsCount = targetTr.cells.length;
        for (let i = 0; i < colsCount; i++) {
            const newTd = document.createElement('td');
            newTd.innerHTML = '&nbsp;';
            newTd.style.border = targetTr.cells[0].style.border || '1px solid #000';
            newTd.style.padding = targetTr.cells[0].style.padding || '6px 10px';
            newTr.appendChild(newTd);
        }
        
        if (above) {
            targetTr.parentNode.insertBefore(newTr, targetTr);
        } else {
            targetTr.parentNode.insertBefore(newTr, targetTr.nextSibling);
        }

        if (targetTr.dataset.rowHeight) {
            applyRowHeightToTr(newTr, targetTr.dataset.rowHeight);
        }
        
        table.parentNode.replaceChild(clonedTable, table);
        syncQuillAfterTableMutation();
    }

    function addColumn(td, table, before = true) {
        const clonedTable = table.cloneNode(true);
        const colIndex = td.cellIndex;
        const rows = clonedTable.rows;
        
        for (let i = 0; i < rows.length; i++) {
            const currentTr = rows[i];
            const newTd = document.createElement('td');
            newTd.innerHTML = '&nbsp;';
            newTd.style.border = td.style.border || '1px solid #000';
            newTd.style.padding = td.style.padding || '6px 10px';
            
            const targetTd = currentTr.cells[colIndex];
            if (targetTd) {
                if (before) {
                    currentTr.insertBefore(newTd, targetTd);
                } else {
                    currentTr.insertBefore(newTd, targetTd.nextSibling);
                }
            } else {
                currentTr.appendChild(newTd);
            }
        }
        
        table.parentNode.replaceChild(clonedTable, table);
        syncQuillAfterTableMutation();
    }

    function deleteRow(tr, table) {
        if (table.rows.length > 1) {
            const clonedTable = table.cloneNode(true);
            const rowIndex = tr.rowIndex;
            if (clonedTable.rows[rowIndex]) {
                clonedTable.rows[rowIndex].remove();
            }
            table.parentNode.replaceChild(clonedTable, table);
        } else {
            table.remove();
        }
        syncQuillAfterTableMutation();
    }

    function deleteColumn(td, table) {
        const rows = table.rows;
        const colsCount = rows[0].cells.length;
        if (colsCount > 1) {
            const clonedTable = table.cloneNode(true);
            const colIndex = td.cellIndex;
            for (let i = 0; i < clonedTable.rows.length; i++) {
                if (clonedTable.rows[i].cells[colIndex]) {
                    clonedTable.rows[i].cells[colIndex].remove();
                }
            }
            table.parentNode.replaceChild(clonedTable, table);
        } else {
            table.remove();
        }
        if (typeof quill !== 'undefined') quill.update();
    }

    function deleteTable(table) {
        table.remove();
        if (typeof quill !== 'undefined') quill.update();
    }

    function setRowHeight(tr, heightValue) {
        if (!tr || !heightValue) return;
        tr.setAttribute('height', heightValue);
        tr.style.setProperty('height', heightValue, 'important');
        for (let i = 0; i < tr.cells.length; i++) {
            const td = tr.cells[i];
            td.setAttribute('height', heightValue);
            td.style.setProperty('height', heightValue, 'important');
            td.style.setProperty('min-height', heightValue, 'important');
            td.style.setProperty('line-height', heightValue, 'important');
            td.style.setProperty('font-size', heightValue, 'important');
            td.style.setProperty('vertical-align', 'top', 'important');
            td.style.setProperty('padding-top', '0px', 'important');
            td.style.setProperty('padding-bottom', '0px', 'important');
        }
        if (typeof quill !== 'undefined') quill.update();
    }

    function setColWidth(td, table, widthValue) {
        if (!td || !table || !widthValue) return;
        const colIndex = td.cellIndex;
        for (let i = 0; i < table.rows.length; i++) {
            const targetTd = table.rows[i].cells[colIndex];
            if (targetTd) {
                targetTd.setAttribute('width', widthValue);
                targetTd.style.setProperty('width', widthValue, 'important');
                targetTd.style.setProperty('min-width', widthValue, 'important');
                targetTd.style.setProperty('max-width', widthValue, 'important');
            }
        }
        if (typeof quill !== 'undefined') quill.update();
    }

    // Robust contextmenu listener to show menu on right click
    document.addEventListener('contextmenu', function(e) {
        const menu = document.getElementById('table-context-menu');
        if (!menu) return;

        const td = e.target.closest('td');
        if (td && document.getElementById('quill-editor').contains(td)) {
            e.preventDefault(); // Prevent standard browser context menu inside tables!
            currentCtxTd = td;
            currentCtxTr = td.closest('tr');
            currentCtxTable = currentCtxTr ? currentCtxTr.closest('table') : null;

            if (currentCtxTd && currentCtxTr && currentCtxTable) {
                // Attach references to the menu DOM element directly to prevent scope loss
                menu.currentTd = currentCtxTd;
                menu.currentTr = currentCtxTr;
                menu.currentTable = currentCtxTable;

                menu.classList.remove('hidden');

                menu.style.left = e.clientX + 'px';
                menu.style.top = e.clientY + 'px';
                
                const rect = menu.getBoundingClientRect();
                if (rect.right > window.innerWidth) {
                    menu.style.left = (e.clientX - rect.width) + 'px';
                }
                if (rect.bottom > window.innerHeight) {
                    menu.style.top = (e.clientY - rect.height) + 'px';
                }

                e.stopPropagation();
                return;
            }
        }
        menu.classList.add('hidden');
    });

    // Hide context menu if left clicked elsewhere
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('table-context-menu');
        if (menu && !menu.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    document.getElementById('ctx-add-row-above').addEventListener('click', function() {
        const menu = document.getElementById('table-context-menu');
        const tr = menu.currentTr || currentCtxTr;
        const table = menu.currentTable || currentCtxTable;
        if (tr && table) addRow(tr, table, true);
        menu.classList.add('hidden');
    });

    document.getElementById('ctx-add-row-below').addEventListener('click', function() {
        const menu = document.getElementById('table-context-menu');
        const tr = menu.currentTr || currentCtxTr;
        const table = menu.currentTable || currentCtxTable;
        if (tr && table) addRow(tr, table, false);
        menu.classList.add('hidden');
    });

    document.getElementById('ctx-add-col-before').addEventListener('click', function() {
        const menu = document.getElementById('table-context-menu');
        const td = menu.currentTd || currentCtxTd;
        const table = menu.currentTable || currentCtxTable;
        if (td && table) addColumn(td, table, true);
        menu.classList.add('hidden');
    });

    document.getElementById('ctx-add-col-after').addEventListener('click', function() {
        const menu = document.getElementById('table-context-menu');
        const td = menu.currentTd || currentCtxTd;
        const table = menu.currentTable || currentCtxTable;
        if (td && table) addColumn(td, table, false);
        menu.classList.add('hidden');
    });

    document.getElementById('ctx-delete-row').addEventListener('click', function() {
        const menu = document.getElementById('table-context-menu');
        const tr = menu.currentTr || currentCtxTr;
        const table = menu.currentTable || currentCtxTable;
        if (tr && table) deleteRow(tr, table);
        menu.classList.add('hidden');
    });

    document.getElementById('ctx-delete-col').addEventListener('click', function() {
        const menu = document.getElementById('table-context-menu');
        const td = menu.currentTd || currentCtxTd;
        const table = menu.currentTable || currentCtxTable;
        if (td && table) deleteColumn(td, table);
        menu.classList.add('hidden');
    });

    document.getElementById('ctx-delete-table').addEventListener('click', function() {
        const menu = document.getElementById('table-context-menu');
        const table = menu.currentTable || currentCtxTable;
        if (table) deleteTable(table);
        menu.classList.add('hidden');
    });

    // --- Tablo Sütun Genişliği Sürükleme Özelliği ---
    let isColResizing = false;
    let colResizeTd = null;
    let colResizeStartX = 0;
    let colResizeStartWidth = 0;

    quill.root.addEventListener('mousemove', function(e) {
        if (isColResizing) return;
        
        const td = e.target.closest('td, th');
        if (!td) {
            if (e.target.style) e.target.style.cursor = '';
            return;
        }
        
        const rect = td.getBoundingClientRect();
        if (rect.right - e.clientX <= 8 && rect.right - e.clientX >= -2) {
            td.style.cursor = 'col-resize';
        } else {
            td.style.cursor = '';
        }
    });

    quill.root.addEventListener('mousedown', function(e) {
        const td = e.target.closest('td, th');
        if (!td) return;
        
        const rect = td.getBoundingClientRect();
        if (rect.right - e.clientX <= 8 && rect.right - e.clientX >= -2) {
            isColResizing = true;
            colResizeTd = td;
            colResizeStartX = e.clientX;
            colResizeStartWidth = td.offsetWidth;
            
            e.preventDefault();
            
            document.addEventListener('mousemove', handleColMouseMove);
            document.addEventListener('mouseup', handleColMouseUp);
        }
    });

    function handleColMouseMove(e) {
        if (!isColResizing || !colResizeTd) return;
        
        const diff = e.clientX - colResizeStartX;
        const newWidth = Math.max(30, colResizeStartWidth + diff);
        const widthVal = newWidth + 'px';
        
        const table = colResizeTd.closest('table');
        if (table) {
            const cellIndex = Array.from(colResizeTd.parentNode.children).indexOf(colResizeTd);
            if (cellIndex !== -1) {
                table.querySelectorAll('tr').forEach(tr => {
                    const targetTd = tr.children[cellIndex];
                    if (targetTd) {
                        targetTd.setAttribute('width', widthVal);
                        targetTd.style.cssText = "width: " + widthVal + " !important; min-width: " + widthVal + " !important; max-width: " + widthVal + " !important;";
                    }
                });
            }
        }
    }

    function handleColMouseUp() {
        isColResizing = false;
        colResizeTd = null;
        document.removeEventListener('mousemove', handleColMouseMove);
        document.removeEventListener('mouseup', handleColMouseUp);
    }

    window.switchTablePropsTab = function(tabName) {
        const btnTable = document.getElementById('table-props-tab-table');
        const btnRow = document.getElementById('table-props-tab-row');
        const btnCol = document.getElementById('table-props-tab-col');
        const panelTable = document.getElementById('table-props-panel-table');
        const panelRow = document.getElementById('table-props-panel-row');
        const panelCol = document.getElementById('table-props-panel-col');

        if (tabName === 'table') {
            btnTable.setAttribute('aria-selected', 'true');
            btnRow.setAttribute('aria-selected', 'false');
            btnCol.setAttribute('aria-selected', 'false');
            panelTable.removeAttribute('hidden');
            panelRow.setAttribute('hidden', '');
            panelCol.setAttribute('hidden', '');
        } else if (tabName === 'row') {
            btnTable.setAttribute('aria-selected', 'false');
            btnRow.setAttribute('aria-selected', 'true');
            btnCol.setAttribute('aria-selected', 'false');
            panelTable.setAttribute('hidden', '');
            panelRow.removeAttribute('hidden');
            panelCol.setAttribute('hidden', '');
        } else {
            btnTable.setAttribute('aria-selected', 'false');
            btnRow.setAttribute('aria-selected', 'false');
            btnCol.setAttribute('aria-selected', 'true');
            panelTable.setAttribute('hidden', '');
            panelRow.setAttribute('hidden', '');
            panelCol.removeAttribute('hidden');
        }
    };

    document.getElementById('ctx-set-table-props').addEventListener('click', function() {
        const menu = document.getElementById('table-context-menu');
        menu.classList.add('hidden');
        switchTablePropsTab('table');
        
        // 1. Satır yüksekliği
        const tr = menu.currentTr || currentCtxTr;
        if (tr) {
            const h = tr.style.height || tr.getAttribute('height') || '40';
            const num = parseFloat(h) || 40;
            const unit = (h.match(/[a-z%]+$/i) || ['px'])[0];
            document.getElementById('row-height-input').value = num;
            document.getElementById('row-height-unit').value = unit;
        }
        
        // 2. Sütun genişliği
        const td = menu.currentTd || currentCtxTd;
        if (td) {
            const w = td.style.width || td.getAttribute('width') || '120';
            const num = parseFloat(w) || 120;
            const unit = (w.match(/[a-z%]+$/i) || ['px'])[0];
            document.getElementById('col-width-input').value = num;
            document.getElementById('col-width-unit').value = unit;
        }

        // 3. Tablo özellikleri
        const table = menu.currentTable || currentCtxTable;
        if (table) {
            const isNoBorder = isTableBorderless(table);
            document.getElementById('table-border-toggle').value = isNoBorder ? 'hide' : 'show';
            
            let align = 'center';
            if (table.style.marginLeft === '0px' || table.style.marginLeft === '0') {
                align = 'left';
            } else if (table.style.marginRight === '0px' || table.style.marginRight === '0') {
                align = 'right';
            }
            document.getElementById('table-alignment-select').value = align;

            // Extract row height with multi-fallback (Tr and Td checks)
            const firstTr = table.querySelector('tr');
            const firstTd = table.querySelector('td');
            let h = null;
            if (firstTr) {
                h = firstTr.getAttribute('data-row-height') || 
                    firstTr.getAttribute('height') || 
                    firstTr.style.height;
            }
            if (!h && firstTd) {
                h = firstTd.getAttribute('data-row-height') || 
                    firstTd.getAttribute('height') || 
                    firstTd.style.height;
            }
            if (h) {
                let num = parseFloat(h);
                let unit = h.replace(/[0-9.]/g, '') || 'px';
                if (!isNaN(num)) {
                    document.getElementById('table-row-height-input').value = num;
                    document.getElementById('table-row-height-unit').value = unit;
                }
            }

            // Extract column width
            if (firstTd) {
                let w = firstTd.getAttribute('data-col-width') || 
                        firstTd.getAttribute('width') || 
                        firstTd.style.width;
                if (w) {
                    let num = parseFloat(w);
                    let unit = w.replace(/[0-9.]/g, '') || 'px';
                    if (!isNaN(num)) {
                        document.getElementById('table-col-width-input').value = num;
                        document.getElementById('table-col-width-unit').value = unit;
                    }
                }
            }
        }
        document.getElementById('table-properties-dialog').showModal();
    });

    document.getElementById('saveTablePropertiesBtn').addEventListener('click', function() {
        const menu = document.getElementById('table-context-menu');
        const panelTable = document.getElementById('table-props-panel-table');
        const panelRow = document.getElementById('table-props-panel-row');
        const panelCol = document.getElementById('table-props-panel-col');
        
        if (panelTable && !panelTable.hasAttribute('hidden')) {
            const table = menu.currentTable || currentCtxTable;
            if (table) {
                const borderToggle = document.getElementById('table-border-toggle').value;
                const alignment = document.getElementById('table-alignment-select').value;
                const rowH = document.getElementById('table-row-height-input').value;
                const rowUnit = document.getElementById('table-row-height-unit').value;
                const colW = document.getElementById('table-col-width-input').value;
                const colUnit = document.getElementById('table-col-width-unit').value;

                // 1. Kenarlık
                const firstTd = table.querySelector('td');
                if (borderToggle === 'show') {
                    table.classList.remove('no-borders');
                    table.classList.add('has-borders');
                    table.removeAttribute('data-no-borders');
                    table.style.setProperty('border', '1px solid #000', 'important');
                    table.querySelectorAll('td').forEach(td => {
                        td.style.setProperty('border', '1px solid #000', 'important');
                        td.removeAttribute('data-no-borders');
                        td.style.removeProperty('min-height');
                    });
                    if (firstTd) {
                        firstTd.style.removeProperty('min-height');
                    }
                } else {
                    table.classList.add('no-borders');
                    table.classList.remove('has-borders');
                    table.setAttribute('data-no-borders', 'true');
                    table.style.setProperty('border', 'none', 'important');
                    table.querySelectorAll('td').forEach(td => {
                        td.style.setProperty('border', 'none', 'important');
                        td.setAttribute('data-no-borders', 'true');
                    });
                    if (firstTd) {
                        firstTd.style.setProperty('min-height', '2.99px', 'important');
                    }
                }
                
                // 2. Hizalama
                if (alignment === 'center') {
                    table.style.marginLeft = 'auto';
                    table.style.marginRight = 'auto';
                } else if (alignment === 'left') {
                    table.style.marginLeft = '0';
                    table.style.marginRight = 'auto';
                } else {
                    table.style.marginLeft = 'auto';
                    table.style.marginRight = '0';
                }

                // 3. Satır yüksekliğini tüm tabloya uygula
                if (rowH && rowUnit) {
                    table.querySelectorAll('tr').forEach(tr => {
                        setRowHeight(tr, rowH + rowUnit);
                    });
                }

                // 4. Sütun genişliğini tüm tabloya uygula
                if (colW && colUnit) {
                    const widthValue = colW + colUnit;
                    table.querySelectorAll('td').forEach(td => {
                        td.setAttribute('width', widthValue);
                        td.style.setProperty('width', widthValue, 'important');
                        td.style.setProperty('min-width', widthValue, 'important');
                        td.style.setProperty('max-width', widthValue, 'important');
                    });
                }
            }
        } else if (panelRow && !panelRow.hasAttribute('hidden')) {
            const tr = menu.currentTr || currentCtxTr;
            if (tr) {
                const val = document.getElementById('row-height-input').value;
                const unit = document.getElementById('row-height-unit').value;
                if (val && unit) {
                    setRowHeight(tr, val + unit);
                }
            }
        } else if (panelCol && !panelCol.hasAttribute('hidden')) {
            const td = menu.currentTd || currentCtxTd;
            const table = menu.currentTable || currentCtxTable;
            if (td && table) {
                const val = document.getElementById('col-width-input').value;
                const unit = document.getElementById('col-width-unit').value;
                if (val && unit) {
                    setColWidth(td, table, val + unit);
                }
            }
        }
        document.getElementById('table-properties-dialog').close();
    });
</script>

<!-- Toaster Container for Basecoat UI -->
<div id="toaster"></div>

/**
 * Merkezi DataTables Başlatma Yapılandırması
 */

// Custom DataTables Filter registered exactly once
if (typeof $.fn.dataTable !== 'undefined' && $.fn.dataTable.ext && $.fn.dataTable.ext.search) {
    if (!window.dtCustomFilterRegistered) {
        window.dtCustomFilterRegistered = true;
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const tableId = settings.sTableId;
            const $table = $('#' + tableId);
            if ($table.length === 0) return true;
            
            let columnFilterState = $table.data('columnFilterState');
            if (!columnFilterState) return true;

            for (let colIndex in columnFilterState) {
                let config = columnFilterState[colIndex];
                if (!config.rules || config.rules.length === 0) continue;

                let cellValue = data[colIndex];
                if (cellValue === undefined) continue;

                let results = config.rules.map(rule => {
                    if (!rule.value) return true;

                    const parseDate = (str) => {
                        if (!str) return null;
                        str = str.replace(/<[^>]*>?/gm, '').trim(); // Strip HTML
                        if (!str || str === '-') return null;

                        // Handle DD.MM.YYYY
                        if (str.includes('.')) {
                            let p = str.split('.');
                            if (p.length === 3) return new Date(parseInt(p[2]), parseInt(p[1])-1, parseInt(p[0])).getTime();
                        } 
                        // Handle YYYY-MM-DD
                        else if (str.includes('-')) {
                            let p = str.split('-');
                            if (p.length === 3) {
                                if (p[0].length === 4) return new Date(parseInt(p[0]), parseInt(p[1])-1, parseInt(p[2])).getTime();
                                return new Date(parseInt(p[2]), parseInt(p[1])-1, parseInt(p[0])).getTime();
                            }
                        }
                        
                        let d = new Date(str);
                        return !isNaN(d.getTime()) ? new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime() : null;
                    };

                    if (rule.type === 'numeric') {
                        // Clean Turkish currency format: 45.000,00 ₺ -> 45000.00
                        let val = parseFloat(cellValue.replace(/\./g, '').replace(',', '.').replace(/[^\d.]/g, ''));
                        let filterVal = parseFloat(rule.value.replace(/\./g, '').replace(',', '.').replace(/[^\d.]/g, ''));
                        
                        if (isNaN(val) || isNaN(filterVal)) return false;
                        if (rule.operator === 'gt') return val > filterVal;
                        if (rule.operator === 'lt') return val < filterVal;
                        if (rule.operator === 'gte') return val >= filterVal;
                        if (rule.operator === 'lte') return val <= filterVal;
                        if (rule.operator === 'equals') return Math.abs(val - filterVal) < 0.01;
                    } 
                    else if (rule.type === 'date') {
                        let val = parseDate(cellValue);
                        let filterVal = parseDate(rule.value);

                        if (val === null || filterVal === null) return false;

                        if (rule.operator === 'gt') return val > filterVal;
                        if (rule.operator === 'lt') return val < filterVal;
                        if (rule.operator === 'gte') return val >= filterVal;
                        if (rule.operator === 'lte') return val <= filterVal;
                        if (rule.operator === 'equals') return val === filterVal;
                    }
                    else { // text
                        let val = cellValue.toLowerCase().replace(/<[^>]*>?/gm, '').trim();
                        let fVal = rule.value.toLowerCase().trim();
                        if (rule.operator === 'contains') return val.includes(fVal);
                        if (rule.operator === 'equals') return val === fVal;
                        if (rule.operator === 'starts') return val.startsWith(fVal);
                        if (rule.operator === 'ends') return val.endsWith(fVal);
                    }
                    return true;
                });

                if (config.match === 'all') {
                    if (results.some(r => r === false)) return false;
                } else {
                    if (!results.some(r => r === true)) return false;
                }
            }
            return true;
        });
    }
}

// Add the popover toggling functions globally once
window.toggleSelectRich = function(btn) {
    const $btn = $(btn);
    const $richSelect = $btn.closest('.app-select-rich');
    const $popover = $richSelect.find('[data-custom-popover]');
    const hidden = $popover.attr('aria-hidden') === 'true';
    $('[data-custom-popover]').not($popover).attr('aria-hidden', 'true');
    $popover.attr('aria-hidden', hidden ? 'false' : 'true');
};

function initDataTable(selector, customOptions = {}) {
    const $table = $(selector);
    const preloaderId = 'preloader-' + Math.random().toString(36).substr(2, 9);
    
    // Inject the popover and its styles if they're not on the page
    if (!$('#datatable-filter-styles').length) {
        $('head').append(`
            <style id="datatable-filter-styles">
                .flatpickr-calendar {
                    z-index: 99999 !important;
                }
                .filter-rule-row .datepicker + .flatpickr-calendar {
                    position: absolute !important;
                }
                table.dataTable thead th.sorting,
                table.dataTable thead th.sorting_asc,
                table.dataTable thead th.sorting_desc {
                    background-image: none !important;
                    position: relative;
                    padding-left: 36px !important;
                    padding-right: 8px !important;
                    cursor: pointer;
                }
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
                table.dataTable thead th.sorting::before {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m3 16 4 4 4-4'/%3E%3Cpath d='M7 20V4'/%3E%3Cpath d='m21 8-4-4-4 4'/%3E%3Cpath d='M17 4v16'/%3E%3C/svg%3E") !important;
                }
                table.dataTable thead th.sorting_asc::before {
                    opacity: 1 !important;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2318181b' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m5 12 7-7 7 7'/%3E%3Cpath d='M12 19V5'/%3E%3C/svg%3E") !important;
                    transform: translateY(-50%) scale(1.1);
                }
                table.dataTable thead th.sorting_desc::before {
                    opacity: 1 !important;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2318181b' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m19 12-7 7-7-7'/%3E%3Cpath d='M12 5v14'/%3E%3C/svg%3E") !important;
                    transform: translateY(-50%) scale(1.1);
                }
                table.dataTable thead th.no-sort::before {  
                    display: none !important;
                }
                .dark table.dataTable thead th.sorting_asc::before {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23f4f4f5' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m5 12 7-7 7 7'/%3E%3Cpath d='M12 19V5'/%3E%3C/svg%3E") !important;
                }
                .dark table.dataTable thead th.sorting_desc::before {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23f4f4f5' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m19 12-7 7-7-7'/%3E%3Cpath d='M12 5v14'/%3E%3C/svg%3E") !important;
                }
                .column-filter-popover {
                    position: fixed;
                    z-index: 10000;
                    background: white;
                    border: 1px solid #e4e4e7;
                    border-radius: 12px;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                    width: 300px;
                    padding: 16px;
                    display: none;
                    animation: popover-in 0.2s ease-out;
                    overflow: visible !important;
                }
                .dark .column-filter-popover {
                    background: #18181b;
                    border-color: #27272a;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
                }
                .filter-rule-row {
                    position: relative;
                    padding-bottom: 12px;
                    border-bottom: 1px dashed #e4e4e7;
                }
                .dark .filter-rule-row {
                    border-color: #27272a;
                }
                .filter-rule-row:last-child {
                    border-bottom: 0;
                    padding-bottom: 0;
                }
                .column-filter-popover [data-custom-popover] {
                    z-index: 20000 !important;
                    position: absolute;
                    width: 100%;
                    min-width: 200px;
                    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2);
                }
                @keyframes popover-in {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .column-filter-btn.active {
                    color: #4f46e5;
                    background: #eef2ff;
                }
                .dark .column-filter-btn.active {
                    color: #818cf8;
                    background: #312e81;
                }
                .dataTables_processing {
                    position: absolute !important;
                    top: 50% !important;
                    left: 50% !important;
                    transform: translate(-50%, -50%) !important;
                    width: 0 !important;
                    height: 0 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: none !important;
                    background-image: none !important;
                    border: none !important;
                    z-index: 1000 !important;
                    box-shadow: none !important;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    overflow: visible !important;
                    white-space: nowrap !important;
                }
                /* DataTables göster dediğinde (inline block verdiğinde) biz flex'e çeviriyoruz */
                .dataTables_processing[style*="display: block"] {
                    display: flex !important;
                }
                .dataTables_processing::before,
                .dataTables_processing::after {
                    content: none !important;
                    display: none !important;
                }
                .dataTables_processing > div:not(.custom-processing-content) {
                    display: none !important;
                }
                @keyframes mini-bounce {
                    0%, 100% { transform: translateY(0); opacity: 0.3; }
                    50% { transform: translateY(-4px); opacity: 1; }
                }
                .dot-bounce {
                    animation: mini-bounce 0.8s infinite ease-in-out;
                }
            </style>
        `);
    }

    if (!$('#columnFilterPopover').length) {
        $('body').append(`
            <div class="column-filter-popover" id="columnFilterPopover">
                <div class="flex items-center justify-between mb-4">
                    <span id="filterColumnName" class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider"></span>
                    <button onclick="closeFilterPopover()" class="p-1 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                
                <div id="filterOptionsContainer">
                    <div id="matchLogicContainer" class="hidden">
                        <div class="flex items-center gap-2">
                            <div class="h-[1px] flex-1 bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="app-select-rich no-search shrink-0">
                                <button type="button" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-[9px] font-black text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors uppercase tracking-widest" onclick="toggleSelectRich(this)">
                                    <span class="truncate">VE</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" class="opacity-50"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                                </button>
                                <div data-custom-popover aria-hidden="true" class="w-20">
                                    <div class="p-1">
                                        <div data-select-option data-value="all" onclick="selectMatchLogic(this)" class="p-1.5 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer text-[10px] font-bold flex items-center justify-between group selected">
                                            <span>VE</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon text-primary"><path d="M20 6 9 17l-5-5"/></svg>
                                        </div>
                                        <div data-select-option data-value="any" onclick="selectMatchLogic(this)" class="p-1.5 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer text-[10px] font-bold flex items-center justify-between group">
                                            <span>VEYA</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon opacity-0 text-primary"><path d="M20 6 9 17l-5-5"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="filterMatchLogic" value="all" />
                            </div>
                            <div class="h-[1px] flex-1 bg-zinc-100 dark:bg-zinc-800"></div>
                        </div>
                    </div>

                    <div id="filterRulesList" class="flex flex-col gap-2 mb-4"></div>

                    <button type="button" onclick="addFilterRuleUI()" id="btnAddRule" class="flex items-center gap-2 text-xs font-bold text-primary hover:text-primary/80 transition-colors mb-6 group">
                        <div class="w-5 h-5 rounded-full bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        </div>
                        Kural Ekle
                    </button>
                    
                    <div id="filterDurumOptions" class="hidden flex flex-col gap-2 mb-6 max-h-[250px] overflow-y-auto pr-1">
                        <label class="label gap-3 flex items-center p-2.5 rounded-xl border border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition-all">
                            <input type="checkbox" name="durumFilter" value="aktif" class="input">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Aktif</span>
                        </label>
                        <label class="label gap-3 flex items-center p-2.5 rounded-xl border border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition-all">
                            <input type="checkbox" name="durumFilter" value="pasif" class="input">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Pasif</span>
                        </label>
                        <label class="label gap-3 flex items-center p-2.5 rounded-xl border border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition-all">
                            <input type="checkbox" name="durumFilter" value="dilekçe alındı" class="input">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Dilekçe Alındı</span>
                        </label>
                        <label class="label gap-3 flex items-center p-2.5 rounded-xl border border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition-all">
                            <input type="checkbox" name="durumFilter" value="kadroya geçti" class="input">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Kadroya Geçti</span>
                        </label>
                        <label class="label gap-3 flex items-center p-2.5 rounded-xl border border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition-all">
                            <input type="checkbox" name="durumFilter" value="kadroya geçmeyecek" class="input">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Kadroya Geçmeyecek</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <button onclick="clearCurrentColumnFilter()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-500 hover:bg-red-50 hover:text-red-600 transition-all" title="Temizle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    </button>
                    <button onclick="applyColumnFilter()" class="flex-1 ml-3 h-10 flex items-center justify-center gap-2 rounded-lg bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-all shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Uygula
                    </button>
                </div>
            </div>
        `);
    }

    // Assign data-column to theaders and inject the button if missing
    $table.find('thead th').not('.no-sort').each(function(index) {
        let $th = $(this);
        if ($th.attr('data-column') === undefined) {
            $th.attr('data-column', index);
        }
        if ($th.find('.column-filter-btn').length === 0) {
            const text = $th.text().trim();
            if (text) {
                $th.html(`
                    <div class="flex items-center justify-between gap-2 group/th">
                        <span>${$th.html()}</span>
                        <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        </button>
                    </div>
                `);
            }
        }
    });

    // Varsayılan olarak preloader aktif
    const showPreloader = customOptions.preloader !== false;
    let preloaderSelector = typeof customOptions.preloader === 'string' ? customOptions.preloader : '#table-preloader';

    if (showPreloader && $(preloaderSelector).length === 0) {
        const preloaderHtml = `
            <div id="${preloaderSelector.replace('#', '')}" class="absolute inset-0 z-50 flex items-center justify-center bg-white/80 dark:bg-zinc-950/80 backdrop-blur-[2px] transition-all duration-500">
                <div class="flex flex-col items-center gap-4">
                    <div class="relative flex items-center justify-center">
                        <div class="w-10 h-10 border-2 border-zinc-200 dark:border-zinc-800 rounded-full"></div>
                        <div class="absolute w-10 h-10 border-2 border-zinc-900 dark:border-zinc-100 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    <div class="flex flex-col items-center gap-1">
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-50 tracking-tight">Veriler hazırlanıyor</p>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Lütfen bekleyin...</p>
                    </div>
                </div>
            </div>
        `;
        $table.closest('.relative').prepend(preloaderHtml);
    }

    if (!$('#datatable-empty-styles').length) {
        $('head').append(`
            <style id="datatable-empty-styles">
                table.dataTable td.dataTables_empty {
                    text-align: center !important;
                    padding: 0 !important;
                }
            </style>
        `);
    }

    let emptyTitle = "Henüz Veri Yok";
    let emptyDesc = "Sistemde henüz herhangi bir kayıt bulunmuyor.";
    let buttonsHtml = "";

    if (selector.includes('personnelTable') || selector.includes('personel')) {
        emptyTitle = "Henüz Personel Yok";
        emptyDesc = "Sistemde henüz herhangi bir personel kaydı bulunmuyor. Yeni bir personel ekleyebilir veya toplu olarak yükleyebilirsiniz.";
        buttonsHtml = `
            <button type="button" onclick="document.getElementById('dialog-add-personnel').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">Yeni Personel Ekle</button>
            <button type="button" onclick="document.getElementById('dialog-import-excel').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-sm whitespace-nowrap">Excel'den Yükle</button>
        `;
    } else if (selector.includes('wageTable') || selector.includes('ucret')) {
        emptyTitle = "Henüz Ücret Tanımı Yok";
        emptyDesc = "Sistemde henüz herhangi bir ücret tanımı bulunmuyor. Yeni bir tanım ekleyebilir veya toplu olarak yükleyebilirsiniz.";
        buttonsHtml = `
            <button type="button" onclick="document.getElementById('dialog-add-wage').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">Yeni Ücret Tanımı</button>
            <button type="button" onclick="document.getElementById('dialog-import-wage').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-sm whitespace-nowrap">Excel'den Yükle</button>
        `;
    } else if (selector.includes('userTable') || selector.includes('kullanici')) {
        emptyTitle = "Henüz Kullanıcı Yok";
        emptyDesc = "Sistemde henüz herhangi bir kullanıcı bulunmuyor.";
        buttonsHtml = `
            <button type="button" onclick="document.getElementById('dialog-add-user').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">Yeni Kullanıcı Ekle</button>
        `;
    } else if (selector.includes('tenantTable') || selector.includes('tenant') || selector.includes('kurum')) {
        emptyTitle = "Henüz Kurum Yok";
        emptyDesc = "Sistemde henüz herhangi bir kurum bulunmuyor.";
        buttonsHtml = `
            <button type="button" onclick="document.getElementById('dialog-add-tenant').showModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">Yeni Kurum Ekle</button>
        `;
    } else {
        const pageHeaderBtns = $('.p-6, .container').first().find('button').filter(function() {
            const t = $(this).text().trim();
            return t.includes('Yeni') || t.includes('Ekle') || t.includes('Yükle');
        });

        if (pageHeaderBtns.length > 0) {
            pageHeaderBtns.each(function() {
                const text = $(this).text().trim();
                const clickAttr = $(this).attr('onclick') || '';
                buttonsHtml += `
                    <button type="button" onclick="${clickAttr}" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm whitespace-nowrap">${text}</button>
                `;
            });
        }
    }

    const emptyTableHtml = `<div class="flex min-w-0 flex-1 flex-col items-center justify-center gap-6 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-800 p-6 text-center text-balance md:p-12 text-zinc-800 dark:text-zinc-300 bg-white dark:bg-zinc-900">
  <header class="flex max-w-sm flex-col items-center gap-2 text-center">
    <div class="mb-2 bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 flex size-10 shrink-0 items-center justify-center rounded-lg">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 10.5 8 13l2 2.5" /><path d="m14 10.5 2 2.5-2 2.5" /><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z" /></svg>
    </div>
    <h3 class="text-lg font-medium tracking-tight text-zinc-900 dark:text-zinc-100">${emptyTitle}</h3>
    <p class="text-zinc-500 dark:text-zinc-400 text-sm/relaxed">
      ${emptyDesc}
    </p>
  </header>
  <section class="flex w-full max-w-sm min-w-0 flex-col items-center gap-4 text-sm text-balance">
    <div class="flex gap-2 justify-center">
      ${buttonsHtml}
    </div>
  </section>
</div>`;

    let initialOrder = customOptions.order || [[0, 'asc']];
    let initialSearch = '';
    let initialPageLength = customOptions.pageLength || 25;
    let savedState = {};
    const tableId = $table.attr('id') || 'defaultTable';
    const stateKey = 'dtState_' + tableId;
    try {
        const raw = localStorage.getItem(stateKey);
        if (raw) {
            savedState = JSON.parse(raw);
            if (savedState.order) initialOrder = savedState.order;
            if (savedState.search) initialSearch = savedState.search;
            if (savedState.pageLength) initialPageLength = parseInt(savedState.pageLength, 10);
        }
    } catch (e) {
        console.error('Error loading table state:', e);
    }

    const defaultOptions = {
        language: {
            "emptyTable": emptyTableHtml,
            "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
            "infoEmpty": "Kayıt yok",
            "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
            "infoPostFix": "",
            "thousands": ".",
            "lengthMenu": "Sayfada _MENU_ kayıt göster",
            "loadingRecords": "Yükleniyor...",
            "processing": `
                <div class="custom-processing-content flex items-center gap-3 px-4 py-2.5 bg-white/95 dark:bg-zinc-950/95 border border-zinc-200 dark:border-zinc-800 rounded-md shadow-md backdrop-blur-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-900 dark:bg-zinc-100 dot-bounce" style="animation-delay: 0ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-900 dark:bg-zinc-100 dot-bounce" style="animation-delay: 150ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-900 dark:bg-zinc-100 dot-bounce" style="animation-delay: 300ms"></span>
                    </div>
                    <span class="text-[12px] font-bold text-zinc-900 dark:text-zinc-100 tracking-tight uppercase">Güncelleniyor</span>
                </div>
            `,
            "search": "Ara:",
            "zeroRecords": "Eşleşen kayıt bulunamadı",
            "paginate": {
                "first": "İlk",
                "last": "Son",
                "next": "Sonraki",
                "previous": "Önceki"
            },
            "aria": {
                "sortAscending": ": artan sütun sıralamasını aktifleştir",
                "sortDescending": ": azalan sütun sıralamasını aktifleştir"
            }
        },
        pageLength: initialPageLength,
        processing: true,
        responsive: true,
        dom: '<"flex flex-col sm:flex-row justify-between items-center p-4 gap-4"f><"flex-1 overflow-auto"rt><"mt-auto border-t border-zinc-200 dark:border-zinc-800 flex flex-row justify-between items-center py-0 px-4 bg-zinc-50/50 dark:bg-zinc-800/30"lip>',
        columnDefs: [
            { orderable: false, targets: 'no-sort' }
        ],
        initComplete: function(settings, json) {
            if (showPreloader) {
                $(preloaderSelector).fadeOut(300);
                $('#table-container').fadeIn(300);
                $table.closest('.overflow-x-auto').fadeIn(300);
            }
            $table.closest('.dataTables_wrapper').find('.dataTables_length select').each(function() {
                if (typeof window.convertToCustomSelect === 'function') {
                    window.convertToCustomSelect($(this));
                }
            });
            if (customOptions.initComplete) {
                customOptions.initComplete.call(this, settings, json);
            }
        },
        drawCallback: function() {
            $('.dataTables_paginate .paginate_button').addClass('px-3 py-1 border border-zinc-200 dark:border-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors');
            $table.closest('.dataTables_wrapper').find('.dataTables_length select').each(function() {
                if (typeof window.convertToCustomSelect === 'function') {
                    window.convertToCustomSelect($(this));
                }
            });
            if (customOptions.drawCallback) {
                customOptions.drawCallback.call(this);
            }
        }
    };

    if (showPreloader) {
        setTimeout(function() {
            if ($(preloaderSelector).is(':visible')) {
                $(preloaderSelector).fadeOut(300);
                $('#table-container').fadeIn(300);
                $table.closest('.overflow-x-auto').fadeIn(300);
            }
        }, 1500);
    }

    const options = $.extend(true, {}, defaultOptions, {
        order: initialOrder,
        search: { search: initialSearch }
    }, customOptions);

    const table = $table.DataTable(options);

    // Save initial / changed order or length
    table.on('order', function() {
        saveTableStateForTable($table);
    });
    table.on('length', function() {
        saveTableStateForTable($table);
    });

    // Check active filters on draw
    table.on('draw', function() {
        checkActiveFiltersForTable($table);
    });

    // Automatically bind the custom search input to filter the table
    const searchInput = $('input[id*="Search"], input[id*="search"]').first();
    if (searchInput.length > 0) {
        searchInput.on('keyup', function() {
            table.search(this.value).draw();
            checkActiveFiltersForTable($table);
            saveTableStateForTable($table);
        });
        if (initialSearch) {
            searchInput.val(initialSearch);
            table.search(initialSearch).draw();
        }
    }

    // Restore filter state from localStorage if present
    if (savedState.columnFilterState) {
        $table.data('columnFilterState', savedState.columnFilterState);
        for (let colIndex in savedState.columnFilterState) {
            const config = savedState.columnFilterState[colIndex];
            if (config.rules && config.rules.length > 0) {
                table.column(colIndex).search('.*', true, false);
            }
        }
        table.draw();
        checkActiveFiltersForTable($table);
    }

    if ($table[0]) {
        $table[0].addEventListener('click', function(e) {
            const btn = e.target.closest('.column-filter-btn');
            if (btn) {
                e.stopPropagation();
                e.preventDefault();
                
                const $btn = $(btn);
                const $th = $btn.closest('th');
                window.openColumnFilterPopover($btn, $th, $table);
            }
        }, true);

        $table[0].addEventListener('mousedown', function(e) {
            if (e.target.closest('.column-filter-btn')) {
                e.stopPropagation();
            }
        }, true);
    }

    return table;
}

window.openColumnFilterPopover = function($btn, $th, $table) {
    const colIndex = parseInt($th.attr('data-column'));
    const colName = $th.find('span').first().text().trim();
    
    window.activeFilterTable = $table;
    window.activeColumnIndex = colIndex;
    window.activeFilterBtn = $btn;
    
    const rect = $btn[0].getBoundingClientRect();
    const $popover = $('#columnFilterPopover');
    
    $('#filterColumnName').text(colName);
    $('#filterRulesList').empty();

    let columnFilterState = $table.data('columnFilterState') || {};
    const config = columnFilterState[colIndex] || { match: 'all', rules: [] };
    
    const matchText = config.match === 'all' ? 'VE' : 'VEYA';
    setMatchLogic(config.match, matchText);

    if (colName.includes('Durum')) {
        $('#matchLogicContainer, #btnAddRule, #filterRulesList').addClass('hidden');
        $('#filterDurumOptions').removeClass('hidden');
        $('input[name="durumFilter"]').prop('checked', false);
        if (config && config.rules) {
            config.rules.forEach(rule => {
                $(`input[name="durumFilter"][value="${rule.value}"]`).prop('checked', true);
            });
        }
    } else {
        $('#matchLogicContainer, #btnAddRule, #filterRulesList').removeClass('hidden');
        $('#filterDurumOptions').addClass('hidden');
        
        if (config.rules.length === 0) {
            addFilterRuleUI(); // Add first rule by default
        } else {
            config.rules.forEach(rule => addFilterRuleUI(rule));
        }
    }

    $popover.css({
        top: (rect.bottom + 8) + 'px',
        left: Math.min(rect.left, window.innerWidth - 300) + 'px'
    }).fadeIn(200);
    
    $('.column-filter-btn').not($btn).removeClass('active');
    $btn.addClass('active');
};

window.addFilterRuleUI = function(rule = null) {
    const colName = $('#filterColumnName').text();
    let type = 'text';
    if (colName.includes('Ücret') || colName.includes('Matrah') || colName.includes('Puan') || colName.includes('Tutarı') || colName.includes('Miktar')) type = 'numeric';
    else if (colName.includes('Tarih') || colName.includes('G. Başlama') || colName.includes('Geçiş') || colName.includes('Bitiş') || colName.includes('Oluşturulma') || colName.includes('Güncelleme')) type = 'date';

    const ruleId = 'rule-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5);
    const operator = rule ? rule.operator : (type === 'text' ? 'contains' : 'gt');
    const value = rule ? rule.value : '';
    
    let operatorText = 'İçerir';
    if (operator === 'equals') operatorText = 'Eşittir';
    else if (operator === 'starts') operatorText = 'İle Başlar';
    else if (operator === 'ends') operatorText = 'İle Biter';
    else if (operator === 'gt') operatorText = 'Büyüktür (>)';
    else if (operator === 'lt') operatorText = 'Küçüktür (<)';
    else if (operator === 'gte') operatorText = 'Büyük Eşittir (>=)';
    else if (operator === 'lte') operatorText = 'Küçük Eşittir (<=)';

    const html = `
        <div class="filter-rule-row flex flex-col gap-2" id="${ruleId}" data-type="${type}">
            <div class="flex items-center gap-2">
                <div class="app-select-rich no-search flex-1">
                    <button type="button" class="btn-outline w-full justify-between h-9 text-xs" onclick="toggleSelectRich(this)">
                        <span class="truncate">${operatorText}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-50"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                    </button>
                    <div data-custom-popover aria-hidden="true">
                        <div class="p-1 max-h-[200px] overflow-y-auto">
                            ${getOperatorOptionsHTML(type, operator)}
                        </div>
                    </div>
                    <input type="hidden" class="rule-operator" value="${operator}" />
                </div>
                ${$('#filterRulesList .filter-rule-row').length > 0 ? `
                    <button onclick="removeFilterRuleUI('${ruleId}')" class="p-1.5 rounded-md hover:bg-red-50 text-zinc-400 hover:text-red-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    </button>
                ` : '<div class="w-8"></div>'}
            </div>
            <input type="text" class="rule-value w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-xs placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all h-9" placeholder="Değer girin..." value="${value}" autocomplete="off">
        </div>
    `;
    const $row = $(html);
    $('#filterRulesList').append($row);

    updateMatchLogicPosition();

    if (type === 'date') {
        if (typeof $.fn.flatpickr !== 'undefined') {
            $row.find('.rule-value').flatpickr({
                dateFormat: 'd.m.Y',
                locale: 'tr',
                disableMobile: true,
                allowInput: true,
                static: true
            });
        }
    }
};

window.removeFilterRuleUI = function(id) {
    $('#' + id).remove();
    updateMatchLogicPosition();
};

window.updateMatchLogicPosition = function() {
    const $rules = $('#filterRulesList .filter-rule-row');
    const $container = $('#matchLogicContainer');
    
    if ($rules.length > 1) {
        $container.removeClass('hidden').insertAfter($rules.first());
    } else {
        $container.addClass('hidden').appendTo($('#filterOptionsContainer'));
    }
};

function getOperatorOptionsHTML(type, currentOp) {
    const ops = type === 'text' 
        ? [{v:'contains', t:'İçerir'}, {v:'equals', t:'Eşittir'}, {v:'starts', t:'İle Başlar'}, {v:'ends', t:'İle Biter'}]
        : [{v:'gt', t:'Büyüktür (>)'}, {v:'lt', t:'Küçüktür (<)'}, {v:'gte', t:'Büyük Eşittir (>=)'}, {v:'lte', t:'Küçük Eşittir (<=)'}, {v:'equals', t:'Eşittir'}];
    
    return ops.map(op => `
        <div data-select-option data-value="${op.v}" onclick="selectRuleOperator(this)" class="p-2 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer text-xs flex items-center justify-between group ${op.v === currentOp ? 'selected' : ''}">
            <span>${op.t}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon ${op.v === currentOp ? '' : 'opacity-0'} text-primary"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
    `).join('');
}

window.selectMatchLogic = function(el) {
    const $el = $(el);
    const val = $el.data('value');
    const text = $el.find('span').text();
    setMatchLogic(val, text);
    $el.closest('[data-custom-popover]').attr('aria-hidden', 'true');
};

function setMatchLogic(val, text) {
    const $container = $('#matchLogicContainer');
    $container.find('input[type="hidden"]').val(val);
    $container.find('button span').text(text);
    $container.find('[data-select-option]').removeClass('selected').find('.check-icon').addClass('opacity-0');
    $container.find(`[data-value="${val}"]`).addClass('selected').find('.check-icon').removeClass('opacity-0');
}

window.selectRuleOperator = function(el) {
    const $el = $(el);
    const val = $el.data('value');
    const text = $el.find('span').text();
    const $row = $el.closest('.filter-rule-row');
    $row.find('.rule-operator').val(val);
    $row.find('button span').text(text);
    $el.closest('[data-custom-popover]').attr('aria-hidden', 'true');
    $el.parent().find('[data-select-option]').removeClass('selected').find('.check-icon').addClass('opacity-0');
    $el.addClass('selected').find('.check-icon').removeClass('opacity-0');
};

window.applyColumnFilter = function() {
    if (!window.activeFilterTable || window.activeColumnIndex === undefined) return;
    
    const colName = $('#filterColumnName').text();
    const $table = window.activeFilterTable;
    let columnFilterState = $table.data('columnFilterState') || {};
    
    if (colName.includes('Durum')) {
        const selectedVals = [];
        $('input[name="durumFilter"]:checked').each(function() {
            selectedVals.push($(this).val());
        });

        if (selectedVals.length > 0) {
            columnFilterState[window.activeColumnIndex] = {
                match: 'any',
                rules: selectedVals.map(v => ({ operator: 'equals', value: v, type: 'text' }))
            };
            $table.DataTable().column(window.activeColumnIndex).search('.*', true, false);
        } else {
            delete columnFilterState[window.activeColumnIndex];
            $table.DataTable().column(window.activeColumnIndex).search('');
        }
        $table.data('columnFilterState', columnFilterState);
        $table.DataTable().draw();
    } else {
        const match = $('#filterMatchLogic').val();
        const rules = [];
        $('#filterRulesList .filter-rule-row').each(function() {
            const operator = $(this).find('.rule-operator').val();
            const value = $(this).find('.rule-value').val();
            const type = $(this).data('type');
            if (value) rules.push({ operator, value, type });
        });

        if (rules.length > 0) {
            columnFilterState[window.activeColumnIndex] = { match, rules };
            $table.DataTable().column(window.activeColumnIndex).search('.*', true, false); 
        } else {
            delete columnFilterState[window.activeColumnIndex];
            $table.DataTable().column(window.activeColumnIndex).search('');
        }
        $table.data('columnFilterState', columnFilterState);
        $table.DataTable().draw();
    }
    
    checkActiveFiltersForTable($table);
    closeFilterPopover();
    saveTableStateForTable($table);
};

window.clearCurrentColumnFilter = function() {
    if (!window.activeFilterTable || window.activeColumnIndex === undefined) return;
    const $table = window.activeFilterTable;
    let columnFilterState = $table.data('columnFilterState') || {};
    
    $table.DataTable().column(window.activeColumnIndex).search('').draw();
    delete columnFilterState[window.activeColumnIndex];
    $table.data('columnFilterState', columnFilterState);
    $table.DataTable().draw();
    
    checkActiveFiltersForTable($table);
    closeFilterPopover();
    saveTableStateForTable($table);
};

window.closeFilterPopover = function() {
    $('#columnFilterPopover').fadeOut(150);
    if (window.activeFilterTable) checkActiveFiltersForTable(window.activeFilterTable);
};

window.clearAllFilters = function() {
    if (!window.activeFilterTable) {
        window.activeFilterTable = $('table.dataTable').first();
    }
    const $table = window.activeFilterTable;
    if ($table.length > 0) {
        $table.DataTable().columns().search('').draw();
        $table.data('columnFilterState', {});
        $table.DataTable().draw();
        $table.DataTable().search('').draw();
        
        $('input[id*="Search"], input[id*="search"]').val('');
        $('.column-filter-btn').removeClass('active');
        $('#clearAllFilters, button[id*="clearAllFilters"]').hide();
        saveTableStateForTable($table);
    }
};

$(document).on('click', '#clearAllFilters, button[id*="clearAllFilters"]', window.clearAllFilters);

function checkActiveFiltersForTable($table) {
    let columnFilterState = $table.data('columnFilterState') || {};
    let hasFilter = false;
    
    let searchVal = $table.DataTable().search();
    if (searchVal && typeof searchVal === 'string' && searchVal.trim().length > 0) {
        hasFilter = true;
    }

    for (let key in columnFilterState) {
        if (columnFilterState[key] && columnFilterState[key].rules && columnFilterState[key].rules.length > 0) {
            $table.find(`th[data-column="${key}"] .column-filter-btn`).addClass('active');
            hasFilter = true;
        } else {
            $table.find(`th[data-column="${key}"] .column-filter-btn`).removeClass('active');
        }
    }

    const clearBtn = $('#clearAllFilters, button[id*="clearAllFilters"]');
    if (clearBtn.length > 0) {
        if (!hasFilter) {
            clearBtn.hide();
        } else {
            clearBtn.css('display', 'inline-flex');
        }
    }
}

function saveTableStateForTable($table) {
    const tableId = $table.attr('id') || 'defaultTable';
    const stateKey = 'dtState_' + tableId;
    const columnFilterState = $table.data('columnFilterState') || {};
    const stateToSave = {
        search: $table.DataTable().search(),
        order: $table.DataTable().order(),
        pageLength: $table.DataTable().page.len(),
        columnFilterState: columnFilterState
    };
    try {
        localStorage.setItem(stateKey, JSON.stringify(stateToSave));
    } catch (e) {
        console.error('Error saving table state:', e);
    }
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('#columnFilterPopover, .column-filter-btn').length) {
        if (typeof closeFilterPopover === 'function') closeFilterPopover();
    }
});

$(document).on('keypress', '#columnFilterPopover .rule-value', function(e) {
    if (e.which === 13) {
        if (typeof applyColumnFilter === 'function') applyColumnFilter();
    }
});

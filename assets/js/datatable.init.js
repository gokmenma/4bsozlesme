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
                /* Merkezi Kolon Sürükle-Bırak ve Gizleme Stilleri */
                table.dataTable thead th.dt-col-draggable {
                    cursor: grab;
                    user-select: none;
                }
                table.dataTable thead th.dt-col-draggable:active {
                    cursor: grabbing;
                }
                table.dataTable thead th.sortable-ghost {
                    opacity: 0.35 !important;
                    background-color: #e4e4e7 !important;
                }
                .dark table.dataTable thead th.sortable-ghost {
                    background-color: #27272a !important;
                }
                table.dataTable thead th.sortable-chosen {
                    background-color: #f4f4f5 !important;
                }
                .dark table.dataTable thead th.sortable-chosen {
                    background-color: #27272a !important;
                }
                .dt-drag-hide-hint {
                    position: fixed;
                    z-index: 100000;
                    pointer-events: none;
                    padding: 6px 12px;
                    border-radius: 8px;
                    font-size: 12px;
                    font-weight: 600;
                    background: rgba(24, 24, 27, 0.9);
                    color: #ffffff;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);
                    transition: background-color 0.15s ease, transform 0.15s ease;
                    display: none;
                    align-items: center;
                    gap: 6px;
                }
                .dt-drag-hide-hint.is-hide-zone {
                    background: rgba(220, 38, 38, 0.95);
                    transform: scale(1.03);
                }
                .dt-cols-menu-popover {
                    position: absolute;
                    right: 0;
                    top: calc(100% + 6px);
                    z-index: 60;
                    width: 240px;
                    background: #ffffff;
                    border: 1px solid #e4e4e7;
                    border-radius: 10px;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
                    padding: 8px;
                    display: none;
                }
                .dark .dt-cols-menu-popover {
                    background: #18181b;
                    border-color: #27272a;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
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
                    <div class="flex items-center justify-start gap-2 group/th">
                        <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        </button>
                        <span>${$th.html()}</span>
                    </div>
                `);
            }
        }
    });

    $table.find('thead th .column-filter-btn').each(function() {
        const $btn = $(this);
        const $wrap = $btn.parent();
        if ($wrap.children().first()[0] !== $btn[0]) {
            $wrap.prepend($btn);
        }
        $wrap.removeClass('justify-between').addClass('justify-start');
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

    // Kayıtlı sütun filtresini tablo oluşturulmadan (ve ilk otomatik ajax isteği atılmadan) önce
    // set ediyoruz; aksi halde serverSide tablo önce filtresiz veri çeker, sonra table.draw() ile
    // ikinci bir istek atarak filtreli veriyi getirir (çift istek + kısa süreli filtresiz flaş).
    if (savedState.columnFilterState) {
        $table.data('columnFilterState', savedState.columnFilterState);
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
                $(preloaderSelector).hide();
                $('#table-container').show();
                $table.closest('.overflow-x-auto').show();
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
                $(preloaderSelector).hide();
                $('#table-container').show();
                $table.closest('.overflow-x-auto').show();
            }
        }, 1500);
    }

    const options = $.extend(true, {}, defaultOptions, {
        order: initialOrder,
        search: { search: initialSearch }
    }, customOptions);

    if (window.DTColumnManager) {
        window.DTColumnManager.prepareHeaders($table[0]);
    }

    const table = $table.DataTable(options);
    window.activeFilterTable = $table;

    if (window.DTColumnManager) {
        window.DTColumnManager.init($table[0], table);
    }

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

    // columnFilterState zaten tablo oluşturulmadan önce set edildi (ilk ajax isteği bunu içerdi);
    // burada sadece sütun arama göstergelerini işaretliyor ve filtre ikonlarını güncelliyoruz.
    if (savedState.columnFilterState) {
        for (let colIndex in savedState.columnFilterState) {
            const config = savedState.columnFilterState[colIndex];
            if (config.rules && config.rules.length > 0) {
                table.column(colIndex).search('.*', true, false);
            }
        }
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

window.removeSingleFilter = function(type, colIndex) {
    if (!window.activeFilterTable || window.activeFilterTable.length === 0) {
        window.activeFilterTable = $('table.dataTable').first();
    }
    const $table = window.activeFilterTable;
    if (!$table || $table.length === 0) return;

    if (type === 'search') {
        $('#personnelSearch, input[id*="Search"], input[id*="search"]').val('');
        if ($.fn.DataTable.isDataTable($table)) {
            $table.DataTable().search('').draw();
        }
    } else if (type === 'column' && colIndex !== undefined && colIndex !== null) {
        let columnFilterState = $table.data('columnFilterState') || {};
        delete columnFilterState[colIndex];
        $table.data('columnFilterState', columnFilterState);
        if ($.fn.DataTable.isDataTable($table)) {
            $table.DataTable().column(colIndex).search('');
            $table.DataTable().draw();
        }
    }
    checkActiveFiltersForTable($table);
    saveTableStateForTable($table);
};

window.clearAllFilters = function() {
    if (!window.activeFilterTable || window.activeFilterTable.length === 0) {
        window.activeFilterTable = $('table.dataTable').first();
    }
    const $table = window.activeFilterTable;
    if ($table && $table.length > 0) {
        if ($.fn.DataTable.isDataTable($table)) {
            $table.DataTable().columns().search('');
            $table.data('columnFilterState', {});
            $table.DataTable().search('');
            $table.DataTable().draw();
        }
        $('#personnelSearch, input[id*="Search"], input[id*="search"]').val('');
        $('.column-filter-btn').removeClass('active');
        checkActiveFiltersForTable($table);
        saveTableStateForTable($table);
    }
};

$(document).on('click', '#clearAllFilters, button[id*="clearAllFilters"]', window.clearAllFilters);

function checkActiveFiltersForTable($table) {
    if (!$table || !$table.length) return;
    
    let columnFilterState = $table.data('columnFilterState') || {};
    let activeFilters = [];

    // 1. Search Filter
    let searchVal = '';
    if ($.fn.DataTable.isDataTable($table)) {
        searchVal = $table.DataTable().search();
    }
    if (!searchVal) {
        searchVal = $('#personnelSearch').val() || '';
    }
    if (searchVal && typeof searchVal === 'string' && searchVal.trim().length > 0) {
        activeFilters.push({
            type: 'search',
            colName: 'Arama',
            text: `"${searchVal.trim()}"`
        });
    }

    // 2. Column Filters
    for (let key in columnFilterState) {
        const config = columnFilterState[key];
        if (config && config.rules && config.rules.length > 0) {
            $table.find(`th[data-column="${key}"] .column-filter-btn`).addClass('active');

            // Find column title
            let colName = '';
            const $th = $table.find(`th[data-column="${key}"]`);
            if ($th.length > 0) {
                colName = $th.find('span').first().text().trim();
            }
            if (!colName && $.fn.DataTable.isDataTable($table)) {
                try {
                    colName = $table.DataTable().column(key).header().textContent.trim();
                } catch(e){}
            }
            if (!colName) colName = `Sütun ${key}`;

            // Build filter text representation
            let ruleTexts = [];
            config.rules.forEach(rule => {
                if (!rule.value && rule.value !== 0) return;
                let valStr = String(rule.value).trim();

                // Formatting Durum & Cinsiyet values
                if (colName.includes('Durum') || colName.includes('Cinsiyet')) {
                    const lower = valStr.toLowerCase();
                    if (lower === 'aktif') valStr = 'Aktif';
                    else if (lower === 'pasif') valStr = 'Pasif';
                    else if (lower === 'dilekce_alindi' || lower === 'dilekçe alındı') valStr = 'Dilekçe Alındı';
                    else if (lower === 'kadroya_gecti' || lower === 'kadroya geçti') valStr = 'Kadroya Geçti';
                    else if (lower === 'kadroya_gecmeyecek' || lower === 'kadroya geçmeyecek') valStr = 'Kadroya Geçmeyecek';
                    else if (lower === 'kadin' || lower === 'kadın') valStr = 'Kadın';
                    else if (lower === 'erkek') valStr = 'Erkek';
                }

                let opText = '';
                if (rule.operator === 'contains') opText = (colName.includes('Durum') || colName.includes('Cinsiyet')) ? '' : 'İçerir ';
                else if (rule.operator === 'equals') opText = (colName.includes('Durum') || colName.includes('Cinsiyet')) ? '' : 'Eşittir ';
                else if (rule.operator === 'starts') opText = 'İle Başlar ';
                else if (rule.operator === 'ends') opText = 'İle Biter ';
                else if (rule.operator === 'gt') opText = '> ';
                else if (rule.operator === 'lt') opText = '< ';
                else if (rule.operator === 'gte') opText = '>= ';
                else if (rule.operator === 'lte') opText = '<= ';

                if (colName.includes('Durum') || colName.includes('Cinsiyet')) {
                    ruleTexts.push(valStr);
                } else if (opText.startsWith('>') || opText.startsWith('<') || opText.startsWith('=')) {
                    ruleTexts.push(`${opText}${valStr}`);
                } else {
                    ruleTexts.push(`${opText}"${valStr}"`);
                }
            });

            if (ruleTexts.length > 0) {
                const joinOp = config.match === 'all' ? ' VE ' : ((colName.includes('Durum') || colName.includes('Cinsiyet')) ? ', ' : ' VEYA ');
                activeFilters.push({
                    type: 'column',
                    colIndex: key,
                    colName: colName,
                    text: ruleTexts.join(joinOp)
                });
            }
        } else {
            $table.find(`th[data-column="${key}"] .column-filter-btn`).removeClass('active');
        }
    }

    // Container element
    let $bar = $('#active-filters-bar');
    if ($bar.length === 0) {
        $bar = $(`
            <div id="active-filters-bar" class="hidden border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/80 dark:bg-zinc-800/50 px-4 py-2.5 flex items-center justify-between gap-3 text-xs shrink-0 transition-all">
                <div class="flex items-center gap-2 flex-wrap" id="active-filter-badges"></div>
                <button type="button" id="clearAllFilters" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-800 rounded-lg text-xs font-medium hover:bg-red-50 dark:hover:bg-red-950/30 hover:text-red-600 dark:hover:text-red-400 hover:border-red-200 dark:hover:border-red-900/50 transition-all shadow-sm whitespace-nowrap cursor-pointer ml-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    Filtreleri temizle
                </button>
            </div>
        `);
        $table.before($bar);
    }

    const $badgesContainer = $bar.find('#active-filter-badges');

    if (activeFilters.length === 0) {
        $bar.addClass('hidden').removeClass('flex');
        $badgesContainer.empty();
    } else {
        $bar.removeClass('hidden').addClass('flex');
        
        let html = `
            <span class="inline-flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400 font-semibold text-xs mr-1 select-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Aktif Filtreler:
            </span>
        `;

        activeFilters.forEach(item => {
            const escapedText = item.text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
            const escapedCol = item.colName.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
            const colArg = item.colIndex !== undefined ? `'${item.colIndex}'` : 'null';

            html += `
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/50 shadow-2xs transition-all">
                    <span class="font-semibold text-indigo-900 dark:text-indigo-200">${escapedCol}:</span>
                    <span>${escapedText}</span>
                    <button type="button" onclick="removeSingleFilter('${item.type}', ${colArg})" class="p-0.5 rounded-full hover:bg-indigo-200/60 dark:hover:bg-indigo-800/60 text-indigo-500 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-100 transition-colors cursor-pointer" title="Filtreyi Kaldır">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </span>
            `;
        });

        $badgesContainer.html(html);
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

/**
 * Merkezi DataTables Kolon Sürükle-Bırak Sıralama ve Tablo Üzerine Bırakınca Gizleme Yöneticisi
 */
window.DTColumnManager = {
    getTableId: function(table) {
        return table.id || 'defaultTable';
    },

    getColumnTitle: function(th, fallbackIdx) {
        if (!th) return 'Sütun ' + (fallbackIdx + 1);
        const span = th.querySelector('span');
        const raw = span ? span.textContent : th.textContent;
        const cleaned = (raw || '').replace(/\s+/g, ' ').trim();
        return cleaned || ('Sütun ' + (fallbackIdx + 1));
    },

    isFixedColumn: function(th) {
        if (!th) return true;
        if (th.classList.contains('no-sort') || th.classList.contains('no-reorder') || th.classList.contains('no-hide')) {
            return true;
        }
        if (th.querySelector('input[type="checkbox"]')) {
            return true;
        }
        const title = this.getColumnTitle(th, 0).toLowerCase();
        if (!title || title === 'işlemler' || title === 'islemler') {
            return true;
        }
        return false;
    },

    prepareHeaders: function(table) {
        if (!table) return;
        const headers = Array.from(table.querySelectorAll('thead tr th'));
        const initialOrder = [];

        headers.forEach((th, idx) => {
            const key = th.dataset.colKey || ('col_' + idx);
            th.dataset.origIndex = String(idx);
            th.dataset.colKey = key;
            th.classList.add('col-' + key);
            initialOrder.push(key);

            if (this.isFixedColumn(th)) {
                th.dataset.colFixed = 'true';
            } else {
                th.classList.add('dt-col-draggable');
            }
        });

        table.dataset.initialColOrder = initialOrder.join(',');
    },

    getHiddenKeys: function(tableId) {
        try {
            const parsed = JSON.parse(localStorage.getItem('dtColHidden_' + tableId) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    },

    saveHiddenKeys: function(tableId, keys) {
        try {
            localStorage.setItem('dtColHidden_' + tableId, JSON.stringify(keys));
        } catch (e) {
            console.error('Error saving hidden columns:', e);
        }
    },

    getSavedOrder: function(tableId) {
        try {
            const parsed = JSON.parse(localStorage.getItem('dtColOrder_' + tableId) || 'null');
            return Array.isArray(parsed) ? parsed : null;
        } catch (e) {
            return null;
        }
    },

    saveOrderFromDom: function(table) {
        const tableId = this.getTableId(table);
        const headers = Array.from(table.querySelectorAll('thead tr th'));
        const order = headers
            .filter(th => th.dataset.colFixed !== 'true' && th.dataset.colKey)
            .map(th => th.dataset.colKey);
        try {
            localStorage.setItem('dtColOrder_' + tableId, JSON.stringify(order));
        } catch (e) {
            console.error('Error saving column order:', e);
        }
    },

    getAllManagedColumns: function(table, dtApi) {
        const cols = [];
        dtApi.columns().every((idx) => {
            const th = dtApi.column(idx).header();
            if (!th || th.dataset.colFixed === 'true') return;
            cols.push({
                origIndex: idx,
                key: th.dataset.colKey || ('col_' + idx),
                title: this.getColumnTitle(th, idx),
                visible: dtApi.column(idx).visible()
            });
        });
        return cols;
    },

    applySavedHiddenColumns: function(table, dtApi) {
        const tableId = this.getTableId(table);
        const hiddenKeys = this.getHiddenKeys(tableId);
        if (hiddenKeys.length === 0) return;

        dtApi.columns().every((idx) => {
            const th = dtApi.column(idx).header();
            if (!th || th.dataset.colFixed === 'true') return;
            const key = th.dataset.colKey || ('col_' + idx);
            if (hiddenKeys.includes(key) && dtApi.column(idx).visible()) {
                dtApi.column(idx).visible(false, false);
            }
        });
    },

    applySavedColumnOrder: function(table, dtApi) {
        const tableId = this.getTableId(table);
        const savedOrder = this.getSavedOrder(tableId);
        if (!savedOrder || savedOrder.length === 0) return;

        const theadRow = table.querySelector('thead tr');
        if (!theadRow) return;

        const visibleHeaders = Array.from(theadRow.children);
        const leadingFixed = [];
        const trailingFixed = [];
        const draggableMap = new Map();

        let seenDraggable = false;
        visibleHeaders.forEach(th => {
            if (th.dataset.colFixed === 'true') {
                if (!seenDraggable) {
                    leadingFixed.push(th);
                } else {
                    trailingFixed.push(th);
                }
            } else {
                seenDraggable = true;
                draggableMap.set(th.dataset.colKey, th);
            }
        });

        const frag = document.createDocumentFragment();
        leadingFixed.forEach(th => frag.appendChild(th));

        savedOrder.forEach(key => {
            const th = draggableMap.get(key);
            if (th) {
                frag.appendChild(th);
                draggableMap.delete(key);
            }
        });

        draggableMap.forEach(th => frag.appendChild(th));
        trailingFixed.forEach(th => frag.appendChild(th));

        theadRow.appendChild(frag);
        this.syncRowsToHeaderOrder(table, dtApi);
    },

    syncRowsToHeaderOrder: function(table, dtApi) {
        if (!table || !dtApi) return;
        const theadRow = table.querySelector('thead tr');
        if (!theadRow) return;

        const visibleHeaderOrigIndices = Array.from(theadRow.children)
            .filter(th => !th.classList.contains('sortable-drag'))
            .map(th => th.dataset.origIndex)
            .filter(v => v !== undefined);

        if (visibleHeaderOrigIndices.length === 0) return;

        // DataTables'ın mevcut görünür sütunlarını orijinal indeks sırasıyla al
        const dtVisibleOrigIndices = [];
        dtApi.columns().every(function(idx) {
            if (this.visible()) {
                dtVisibleOrigIndices.push(String(idx));
            }
        });

        table.querySelectorAll('tbody tr, tfoot tr').forEach(row => {
            const cells = Array.from(row.cells);
            if (cells.length === 0) return;
            if (cells.length === 1 && cells[0].colSpan > 1) return;

            const cellMap = new Map();
            const usedCells = new Set();

            cells.forEach((cell, idx) => {
                let origIdx = cell.dataset.origIndex;
                if (origIdx === undefined && dtVisibleOrigIndices[idx] !== undefined) {
                    origIdx = dtVisibleOrigIndices[idx];
                    cell.dataset.origIndex = origIdx;
                }
                if (origIdx !== undefined) {
                    cellMap.set(origIdx, cell);
                }
            });

            const frag = document.createDocumentFragment();
            visibleHeaderOrigIndices.forEach(origIdx => {
                const cell = cellMap.get(origIdx);
                if (cell) {
                    frag.appendChild(cell);
                    usedCells.add(cell);
                }
            });

            cells.forEach(cell => {
                if (!usedCells.has(cell)) {
                    frag.appendChild(cell);
                }
            });

            row.appendChild(frag);
        });
    },

    hideColumnByHeader: function(table, dtApi, th) {
        const tableId = this.getTableId(table);
        const origIdx = parseInt(th.dataset.origIndex, 10);
        const colKey = th.dataset.colKey;
        if (isNaN(origIdx) || !colKey || th.dataset.colFixed === 'true') return;

        // En az 1 sürüklenebilir/veri kolonu görünür kalmalı
        const visibleDraggableCount = Array.from(table.querySelectorAll('thead tr th.dt-col-draggable')).length;
        if (visibleDraggableCount <= 1) {
            if (typeof showToast === 'function') {
                showToast({ category: 'warning', title: 'Uyarı', description: 'En az bir veri sütunu görünür kalmalıdır.' });
            }
            return;
        }

        const hiddenKeys = this.getHiddenKeys(tableId);
        if (!hiddenKeys.includes(colKey)) {
            hiddenKeys.push(colKey);
            this.saveHiddenKeys(tableId, hiddenKeys);
        }

        dtApi.column(origIdx).visible(false, false);

        if (typeof showToast === 'function') {
            const title = this.getColumnTitle(th, origIdx);
            showToast({
                category: 'info',
                title: 'Sütun Gizlendi',
                description: `"${title}" sütunu gizlendi. "Sütunlar" menüsünden tekrar açabilirsiniz.`
            });
        }
    },

    syncVisibilitySideEffects: function(table, dtApi, colIdx, state) {
        const tableId = this.getTableId(table);
        const th = dtApi.column(colIdx).header();
        if (!th || th.dataset.colFixed === 'true') return;
        const colKey = th.dataset.colKey || ('col_' + colIdx);

        // dtColHidden_ senkronize et
        let hiddenKeys = this.getHiddenKeys(tableId);
        if (state) {
            hiddenKeys = hiddenKeys.filter(k => k !== colKey);
        } else if (!hiddenKeys.includes(colKey)) {
            hiddenKeys.push(colKey);
        }
        this.saveHiddenKeys(tableId, hiddenKeys);

        // Sayfaya özel .column-toggle varsa (örn. personel/list.php) senkronize et
        const $pageToggle = $(`.column-toggle[data-column-index="${colIdx}"]`);
        if ($pageToggle.length) {
            $pageToggle.prop('checked', state);
            if (typeof saveColumnPreferences === 'function') {
                saveColumnPreferences();
            }
        }

        // Merkezi sütun menüsü varsa güncelle
        this.refreshColumnsToolbar(table, dtApi);

        if (state && !table.dataset.isResettingCols) {
            this.applySavedColumnOrder(table, dtApi);
        } else {
            this.syncRowsToHeaderOrder(table, dtApi);
        }
    },

    setupColumnsToolbar: function(table, dtApi) {
        const tableId = this.getTableId(table);
        // Eğer sayfada zaten özel sütun toggle menüsü varsa ekstra buton ekleme
        if (document.querySelector('.column-toggle')) return;

        const wrapper = table.closest('.dataTables_wrapper');
        if (!wrapper || wrapper.querySelector('.dt-cols-toolbar-btn')) return;

        const managedCols = this.getAllManagedColumns(table, dtApi);
        if (managedCols.length === 0) return;

        // DataTables üst barını bul veya oluştur
        let topBar = wrapper.querySelector('.dataTables_filter')?.parentElement;
        if (!topBar) return;

        topBar.classList.add('dt-topbar-with-cols');

        const container = document.createElement('div');
        container.className = 'relative ml-auto flex items-center gap-2 dt-cols-toolbar';
        container.innerHTML = `
            <button type="button" class="dt-cols-toolbar-btn inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-2xs cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/></svg>
                <span>Sütunlar</span>
                <span class="dt-cols-hidden-badge hidden px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">0</span>
            </button>
            <div class="dt-cols-menu-popover">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 px-2 py-1.5 border-b border-zinc-100 dark:border-zinc-800 mb-1">
                    Sütunları Göster / Gizle
                </div>
                <div class="dt-cols-menu-list max-h-60 overflow-y-auto flex flex-col gap-0.5"></div>
                <div class="pt-1.5 mt-1.5 border-t border-zinc-100 dark:border-zinc-800">
                    <button type="button" class="dt-cols-reset-btn w-full text-left px-2 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 rounded-md transition-colors cursor-pointer">
                        Varsayılan Sıra ve Görünüme Dön
                    </button>
                </div>
            </div>
        `;

        topBar.appendChild(container);

        const btn = container.querySelector('.dt-cols-toolbar-btn');
        const popover = container.querySelector('.dt-cols-menu-popover');
        const resetBtn = container.querySelector('.dt-cols-reset-btn');

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = popover.style.display === 'block';
            document.querySelectorAll('.dt-cols-menu-popover').forEach(p => { p.style.display = 'none'; });
            popover.style.display = isOpen ? 'none' : 'block';
        });

        popover.addEventListener('click', (e) => e.stopPropagation());

        document.addEventListener('click', () => {
            popover.style.display = 'none';
        });

        resetBtn.addEventListener('click', () => {
            this.resetTableColumns(table, dtApi);
            popover.style.display = 'none';
        });

        this.refreshColumnsToolbar(table, dtApi);
    },

    refreshColumnsToolbar: function(table, dtApi) {
        const wrapper = table.closest('.dataTables_wrapper');
        if (!wrapper) return;
        const container = wrapper.querySelector('.dt-cols-toolbar');
        if (!container) return;

        const list = container.querySelector('.dt-cols-menu-list');
        const badge = container.querySelector('.dt-cols-hidden-badge');
        if (!list) return;

        const cols = this.getAllManagedColumns(table, dtApi);
        const hiddenCount = cols.filter(c => !c.visible).length;

        if (badge) {
            if (hiddenCount > 0) {
                badge.textContent = String(hiddenCount);
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        list.innerHTML = '';
        cols.forEach(col => {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-800/60 cursor-pointer text-xs text-zinc-700 dark:text-zinc-300 select-none';

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'input cursor-pointer';
            input.checked = col.visible;

            input.addEventListener('change', () => {
                if (!input.checked) {
                    const visibleCount = this.getAllManagedColumns(table, dtApi).filter(c => c.visible).length;
                    if (visibleCount <= 1) {
                        input.checked = true;
                        if (typeof showToast === 'function') {
                            showToast({ category: 'warning', title: 'Uyarı', description: 'En az bir veri sütunu görünür kalmalıdır.' });
                        }
                        return;
                    }
                }
                dtApi.column(col.origIndex).visible(input.checked, false);
            });

            const span = document.createElement('span');
            span.className = 'truncate';
            span.textContent = col.title;

            label.appendChild(input);
            label.appendChild(span);
            list.appendChild(label);
        });
    },

    resetTableColumns: function(table, dtApi) {
        const tableId = this.getTableId(table);
        table.dataset.isResettingCols = 'true';
        try {
            localStorage.removeItem('dtColHidden_' + tableId);
            localStorage.removeItem('dtColOrder_' + tableId);

            dtApi.columns().every((idx) => {
                if (!dtApi.column(idx).visible()) {
                    dtApi.column(idx).visible(true, false);
                }
            });

            const theadRow = table.querySelector('thead tr');
            if (theadRow) {
                const headers = Array.from(theadRow.children);
                headers.sort((a, b) => parseInt(a.dataset.origIndex || '0', 10) - parseInt(b.dataset.origIndex || '0', 10));
                const frag = document.createDocumentFragment();
                headers.forEach(th => frag.appendChild(th));
                theadRow.appendChild(frag);
            }

            this.syncRowsToHeaderOrder(table, dtApi);
            this.refreshColumnsToolbar(table, dtApi);
        } finally {
            delete table.dataset.isResettingCols;
        }
    },

    initSortable: function(table, dtApi) {
        if (typeof Sortable === 'undefined') return;
        const theadRow = table.querySelector('thead tr');
        if (!theadRow || theadRow.dataset.dtSortableInit === 'true') return;
        theadRow.dataset.dtSortableInit = 'true';

        // Sürükleme biterken DataTables'ın istem dışı click-to-sort yapmasını engelle
        theadRow.addEventListener('click', function(e) {
            if (table.dataset.isDraggingCol === 'true') {
                e.stopPropagation();
                e.preventDefault();
            }
        }, true);

        let hintEl = document.getElementById('dt-drag-hide-hint');
        if (!hintEl) {
            hintEl = document.createElement('div');
            hintEl.id = 'dt-drag-hide-hint';
            hintEl.className = 'dt-drag-hide-hint';
            document.body.appendChild(hintEl);
        }

        let dragSyncFrame = null;
        let currentDragTable = null;
        let dragOverHandler = null;

        const startDragSync = () => {
            const loop = () => {
                const headers = Array.from(theadRow.children).filter(th => !th.classList.contains('sortable-drag'));
                headers.forEach((th, colIndex) => {
                    const transform = th.style.transform;
                    const transition = th.style.transition;
                    table.querySelectorAll('tbody tr, tfoot tr').forEach(row => {
                        const cell = row.cells[colIndex];
                        if (cell && !(row.cells.length === 1 && cell.colSpan > 1)) {
                            cell.style.transform = transform;
                            cell.style.transition = transition;
                        }
                    });
                });
                dragSyncFrame = requestAnimationFrame(loop);
            };
            dragSyncFrame = requestAnimationFrame(loop);
        };

        const stopDragSync = () => {
            if (dragSyncFrame) {
                cancelAnimationFrame(dragSyncFrame);
                dragSyncFrame = null;
            }
            table.querySelectorAll('tbody td, tfoot td').forEach(cell => {
                cell.style.transform = '';
                cell.style.transition = '';
            });
        };

        const self = this;

        Sortable.create(theadRow, {
            animation: 150,
            draggable: 'th.dt-col-draggable',
            filter: '.column-filter-btn, .column-filter-btn *',
            preventOnFilter: false,
            onStart: function(evt) {
                table.dataset.isDraggingCol = 'true';

                const colIndex = Array.from(theadRow.children).indexOf(evt.item);
                const tempTable = document.createElement('table');
                tempTable.className = table.className;
                tempTable.style.cssText = `position:fixed; top:-9999px; left:-9999px; width:${evt.item.offsetWidth}px; border-collapse:collapse; z-index:99999; background:var(--card, #ffffff); box-shadow:0 8px 30px rgba(0,0,0,0.14); opacity:0.88; pointer-events:none;`;

                const thead = document.createElement('thead');
                const trHead = document.createElement('tr');
                trHead.appendChild(evt.item.cloneNode(true));
                thead.appendChild(trHead);
                tempTable.appendChild(thead);

                const tbody = document.createElement('tbody');
                const rows = Array.from(table.querySelectorAll('tbody tr')).slice(0, 12);
                rows.forEach(row => {
                    const cell = row.cells[colIndex];
                    if (cell && !(row.cells.length === 1 && cell.colSpan > 1)) {
                        const tr = document.createElement('tr');
                        tr.appendChild(cell.cloneNode(true));
                        tbody.appendChild(tr);
                    }
                });
                tempTable.appendChild(tbody);
                document.body.appendChild(tempTable);
                currentDragTable = tempTable;

                if (evt.originalEvent && evt.originalEvent.dataTransfer && typeof evt.originalEvent.dataTransfer.setDragImage === 'function') {
                    evt.originalEvent.dataTransfer.setDragImage(tempTable, evt.item.offsetWidth / 2, 20);
                }

                hintEl.textContent = 'Sıralamak için sürükleyin • Gizlemek için tablo üzerine bırakın';
                hintEl.classList.remove('is-hide-zone');
                hintEl.style.display = 'inline-flex';

                dragOverHandler = (e) => {
                    const rect = theadRow.getBoundingClientRect();
                    const x = e.clientX || 0;
                    const y = e.clientY || 0;
                    hintEl.style.left = (x + 14) + 'px';
                    hintEl.style.top = (y + 18) + 'px';
                    const isOutside = (y > rect.bottom + 15 || y < rect.top - 15 || x < rect.left - 15 || x > rect.right + 15);
                    if (isOutside) {
                        hintEl.textContent = 'Gizlemek için bırakın';
                        hintEl.classList.add('is-hide-zone');
                    } else {
                        hintEl.textContent = 'Sıralamak için sürükleyin';
                        hintEl.classList.remove('is-hide-zone');
                    }
                };
                document.addEventListener('dragover', dragOverHandler);

                startDragSync();
            },
            onChange: function() {
                self.syncRowsToHeaderOrder(table, dtApi);
            },
            onEnd: function(evt) {
                stopDragSync();
                if (currentDragTable) {
                    currentDragTable.remove();
                    currentDragTable = null;
                }
                if (dragOverHandler) {
                    document.removeEventListener('dragover', dragOverHandler);
                    dragOverHandler = null;
                }
                hintEl.style.display = 'none';
                hintEl.classList.remove('is-hide-zone');

                setTimeout(() => {
                    delete table.dataset.isDraggingCol;
                }, 150);

                const rect = theadRow.getBoundingClientRect();
                const originalEvt = evt.originalEvent;
                let x = 0, y = 0;
                if (originalEvt) {
                    if (originalEvt.clientX !== undefined) {
                        x = originalEvt.clientX;
                        y = originalEvt.clientY;
                    } else if (originalEvt.changedTouches && originalEvt.changedTouches[0]) {
                        x = originalEvt.changedTouches[0].clientX;
                        y = originalEvt.changedTouches[0].clientY;
                    }
                }

                const isOutside = (y > rect.bottom + 15 || y < rect.top - 15 || x < rect.left - 15 || x > rect.right + 15);
                if (isOutside) {
                    self.hideColumnByHeader(table, dtApi, evt.item);
                }

                self.saveOrderFromDom(table);
                self.syncRowsToHeaderOrder(table, dtApi);
            }
        });
    },

    init: function(table, dtApi) {
        if (!table || !dtApi || table.dataset.dtColManagerInit === 'true') return;
        table.dataset.dtColManagerInit = 'true';

        this.applySavedHiddenColumns(table, dtApi);
        this.applySavedColumnOrder(table, dtApi);
        this.syncRowsToHeaderOrder(table, dtApi);
        this.setupColumnsToolbar(table, dtApi);
        this.initSortable(table, dtApi);

        $(table).on('draw.dt', () => {
            this.syncRowsToHeaderOrder(table, dtApi);
        });

        $(table).on('column-visibility.dt', (e, settings, colIdx, state) => {
            this.syncVisibilitySideEffects(table, dtApi, colIdx, state);
        });
    }
};

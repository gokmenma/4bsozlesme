<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../bootstrap.php';
global $db;

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$tenant_id = $_SESSION['tenant_id'] ?? 0;

// Fetch all available periods for this tenant to populate a filter dropdown
$periodsStmt = $db->prepare("SELECT DISTINCT donem FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? ORDER BY donem DESC");
$periodsStmt->execute([$tenant_id]);
$allPeriods = $periodsStmt->fetchAll(PDO::FETCH_COLUMN);

// Default to the first available period, or if none, '2026-1'
$selectedPeriod = $_GET['donem'] ?? ($_SESSION['active_wage_period'] ?? ($allPeriods[0] ?? '2026-1'));
$_SESSION['active_wage_period'] = $selectedPeriod;

// Add '2026-1' to allPeriods if empty to ensure dropdown renders
if (empty($allPeriods)) {
    $allPeriods = ['2026-1'];
}

// Fetch wages only for the selected period
$stmt_ucret = $db->prepare("SELECT id, unvan, ucret, ogrenim, kidem_yili, donem FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? AND donem = ? ORDER BY unvan ASC");
$stmt_ucret->execute([$tenant_id, $selectedPeriod]);
$ucretler = $stmt_ucret->fetchAll(PDO::FETCH_ASSOC);

// Extract unique unvanlar for advanced filters
$unvanlar = array_unique(array_filter(array_map(function($u) {
    return $u['unvan'] ?? null;
}, $ucretler)));
sort($unvanlar);

// Extract unique ogrenimler for advanced filters
$ogrenimler = array_unique(array_filter(array_map(function($u) {
    return $u['ogrenim'] ?? null;
}, $ucretler)));
sort($ogrenimler);

// Extract unique kidemler for advanced filters
$kidemler = array_unique(array_filter(array_map(function($u) {
    return $u['kidem_yili'] ?? null;
}, $ucretler)));
sort($kidemler);
?>
<div class="space-y-4 animate-fade-in pb-16">
    <!-- Beautiful Period Selector Header -->
    <div class="flex items-center justify-between bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/60 dark:border-zinc-800/80 px-4 py-3 rounded-xl shadow-sm gap-4">
        <div class="flex flex-col">
            <span class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest leading-none mb-1">Aktif Ücret Dönemi</span>
            <div class="relative flex items-center gap-1">
                <select id="wage-period-select" onchange="switchWagePeriod(this.value)" class="text-xs font-extrabold text-zinc-950 dark:text-white bg-transparent border-0 p-0 pr-4 focus:ring-0 focus:outline-none cursor-pointer uppercase">
                    <?php foreach ($allPeriods as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $p === $selectedPeriod ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (count($allPeriods) > 1): ?>
                <button onclick="confirmDeletePeriod('<?= htmlspecialchars($selectedPeriod) ?>')" class="text-rose-500 hover:text-rose-600 active:scale-90 transition-all p-1 mr-1" title="Bu Dönemi Sil">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2m-9 5v6m4-6v6"/></svg>
                </button>
                <?php else: ?>
                <button disabled class="text-zinc-300 dark:text-zinc-700 opacity-40 cursor-not-allowed p-1 mr-1" title="Sistemde en az bir ücret dönemi bulunmalıdır">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2m-9 5v6m4-6v6"/></svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-right">
            <span class="text-[9px] font-black uppercase tracking-wider text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 px-2 py-1 rounded-md definition-count-text">
                <?= count($ucretler) ?> ÜCRET TANIMI
            </span>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="flex items-center gap-2">
        <div class="relative flex-1 flex items-center">
            <div class="absolute left-4 text-zinc-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input id="definitionSearch" type="text" class="mobile-input pl-icon" placeholder="Unvan veya öğrenim ara..." onkeyup="applyDefinitionFilters()">
        </div>
        <!-- Sort Button -->
        <button id="mobileDefSortBtn" onclick="openDefSortSheet()" class="p-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg text-zinc-600 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m3 16 4 4 4-4M7 20V4M21 8l-4-4-4 4M17 4v16"/></svg>
        </button>
        <!-- Filter Button -->
        <button id="mobileDefFilterToggleBtn" onclick="openDefFilterSheet()" class="p-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg text-zinc-600 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        </button>
    </div>

    <!-- Active Filter Badges Container -->
    <div id="active-def-filters-badges" class="flex flex-wrap gap-1.5 px-0.5 mt-1 hidden"></div>

    <!-- Definitions List -->
    <div id="definitions-list-wrapper" class="space-y-3">
        <?php if (empty($ucretler)): ?>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-10 rounded-xl text-center space-y-2">
                <p class="text-xs font-bold text-zinc-400">Kurumunuzda henüz ücret tanımı bulunmamaktadır.</p>
                <button onclick="openAddDefinitionSheet()" class="px-4 py-2 bg-zinc-50 hover:bg-zinc-200 rounded-md text-xs font-bold mt-2 text-zinc-950">Tanım Ekle</button>
            </div>
        <?php else: ?>
            <?php foreach ($ucretler as $u): ?>
                <div class="swipe-container relative overflow-hidden definition-item-card"
                     data-id="<?= $u['id'] ?>"
                     data-donem="<?= htmlspecialchars($u['donem'] ?? '2026-1') ?>"
                     data-unvan="<?= htmlspecialchars($u['unvan']) ?>"
                     data-ogrenim="<?= htmlspecialchars($u['ogrenim']) ?>"
                     data-kidem="<?= htmlspecialchars($u['kidem_yili']) ?>"
                     data-ucret-raw="<?= $u['ucret'] ?? 0 ?>"
                     data-ucret="<?= number_format($u['ucret'] ?? 0, 2, ',', '.') ?> TL">
                     
                    <!-- Right Background Actions (Sola Kaydırma) - Elegant Float Brand Actions -->
                    <div class="swipe-right-actions absolute inset-y-0 right-0 flex items-stretch z-0">
                        <button onclick="event.stopPropagation(); confirmDeleteDefinition('<?= $u['id'] ?>', '<?= htmlspecialchars($u['unvan']) ?>')" class="w-14 h-full bg-transparent text-rose-600 dark:text-rose-400 flex flex-col items-center justify-center transition-all cursor-pointer gap-1.5 hover:scale-110 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2m-9 5v6m4-6v6"/></svg>
                            <span class="text-[9px] font-bold uppercase tracking-wider leading-none">Sil</span>
                        </button>
                    </div>

                    <!-- Main Swipeable Row Layer -->
                    <div class="swipe-front bg-white dark:bg-zinc-900 py-4 px-4 flex items-center justify-between transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 cursor-grab active:cursor-grabbing" 
                         onclick="openEditDefinitionSheet(this.parentElement)">
                        <div class="space-y-1 select-none pointer-events-none">
                            <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200 leading-tight"><?= htmlspecialchars($u['unvan']) ?></h4>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="text-[9px] bg-zinc-100 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-800 px-2 py-0.5 rounded font-bold uppercase leading-none"><?= htmlspecialchars($u['ogrenim']) ?></span>
                                <span class="text-[9px] bg-zinc-100 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-800 px-2 py-0.5 rounded font-bold uppercase leading-none"><?= htmlspecialchars($u['kidem_yili']) ?></span>
                            </div>
                        </div>
                        <div class="text-right select-none pointer-events-none">
                            <span class="text-xs font-extrabold text-zinc-900 dark:text-zinc-100 block"><?= number_format($u['ucret'] ?? 0, 2, ',', '.') ?> TL</span>
                            <span class="text-[9px] text-zinc-500 font-semibold block mt-0.5">Brüt Matrah</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'])): ?>
    <!-- Floating Action Button for Period Copy/Raise -->
    <button onclick="openWageCopySheet()" class="fixed bottom-40 right-6 w-14 h-14 rounded-full bg-zinc-600 hover:bg-zinc-700 dark:bg-zinc-200 dark:hover:bg-zinc-300 text-white dark:text-zinc-950 shadow-lg border border-zinc-550/20 flex items-center justify-center active:scale-95 transition-all cursor-pointer z-40">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
    </button>
    <?php endif; ?>

    <!-- Floating Action Button for Adding New Definition -->
    <button onclick="openAddDefinitionSheet()" class="fixed bottom-24 right-6 w-14 h-14 rounded-full bg-zinc-900 dark:bg-zinc-50 text-zinc-50 dark:text-zinc-950 shadow-lg border border-zinc-200 dark:border-zinc-800 flex items-center justify-center active:scale-95 transition-all cursor-pointer z-40">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14m-7-7v14"/></svg>
    </button>
</div>

<script>
    // Filtering global state
    window.filterDefOgrenim = [];
    window.filterDefKidem = [];
    window.filterDefUcretVal = null;
    window.filterDefUcretOp = 'equals';
    window.sortDefActive = 'title_asc';

    // Open add sheet
    function openAddDefinitionSheet() {
        const formIdEl = document.getElementById('form-def-id');
        if (!formIdEl) {
            alert("Yeni güncellemelerin aktif olması için lütfen tarayıcınızı yenileyin (F5 / Sayfayı Yenile).");
            window.location.reload();
            return;
        }

        // Reset form
        formIdEl.value = "";
        
        const donemInput = document.getElementById('form-def-donem');
        if (donemInput) {
            donemInput.value = document.getElementById('wage-period-select').value;
        }
        
        document.getElementById('form-def-unvan').value = "";
        document.getElementById('form-def-ogrenim').value = "Lise";
        document.getElementById('form-def-kidem').value = "0-5 Yıl (Dahil)";
        document.getElementById('form-def-ucret').value = "";
        
        document.getElementById('def-form-title').innerText = "Yeni Ücret Tanımı Ekle";
        
        openSheet('def-form-sheet');
        if (typeof syncMobileCustomSelects === 'function') {
            syncMobileCustomSelects();
        }
    }

    // Open edit sheet
    function openEditDefinitionSheet(cardElement) {
        const formIdEl = document.getElementById('form-def-id');
        if (!formIdEl) {
            alert("Yeni güncellemelerin aktif olması için lütfen tarayıcınızı yenileyin (F5 / Sayfayı Yenile).");
            window.location.reload();
            return;
        }

        const id = cardElement.getAttribute('data-id');
        const unvan = cardElement.getAttribute('data-unvan');
        const ogrenim = cardElement.getAttribute('data-ogrenim');
        const kidem = cardElement.getAttribute('data-kidem');
        const ucret = cardElement.getAttribute('data-ucret-raw');
        const donem = cardElement.getAttribute('data-donem') || '2026-1';
        
        formIdEl.value = id;
        
        const donemInput = document.getElementById('form-def-donem');
        if (donemInput) {
            donemInput.value = donem;
        }
        
        document.getElementById('form-def-unvan').value = unvan;
        document.getElementById('form-def-ogrenim').value = ogrenim;
        document.getElementById('form-def-kidem').value = kidem;
        document.getElementById('form-def-ucret').value = typeof formatTurkishCurrency === 'function' ? formatTurkishCurrency(ucret) : ucret;
        
        document.getElementById('def-form-title').innerText = "Ücret Tanımını Düzenle";
        
        openSheet('def-form-sheet');
        if (typeof syncMobileCustomSelects === 'function') {
            syncMobileCustomSelects();
        }
    }

    function openWageCopySheet() {
        const selectEl = document.getElementById('wage-period-select');
        const selected = selectEl ? selectEl.value : '2026-1';
        const copyFromInput = document.getElementById('copy-from-donem');
        if (copyFromInput) {
            copyFromInput.value = selected;
        }
        openSheet('def-copy-sheet');
    }

    function switchWagePeriod(period) {
        switchTab('definitions', null, { donem: period });
    }

    // Open sorting sheet
    function openDefSortSheet() {
        openSheet('def-sort-sheet');
        
        const btns = document.querySelectorAll('.def-sort-option-btn');
        btns.forEach(btn => {
            const sortType = btn.getAttribute('data-sort');
            const checkIcon = btn.querySelector('.check-icon');
            if (sortType === window.sortDefActive) {
                btn.classList.add('bg-zinc-50', 'dark:bg-zinc-800', 'text-zinc-950', 'dark:text-white');
                checkIcon.classList.remove('hidden');
            } else {
                btn.classList.remove('bg-zinc-50', 'dark:bg-zinc-800', 'text-zinc-950', 'dark:text-white');
                checkIcon.classList.add('hidden');
            }
        });
    }

    // Open filtering sheet
    function openDefFilterSheet() {
        openSheet('def-filter-sheet');
    }

    // Apply sorting
    function applyDefSorting(sortType) {
        window.sortDefActive = sortType;
        closeAllSheets();
        sortDefinitionCards();
    }

    // Sort cards in DOM
    function sortDefinitionCards() {
        const container = document.getElementById('definitions-list-wrapper');
        if (!container) return;
        
        const cards = Array.from(container.querySelectorAll('.definition-item-card'));
        
        cards.sort((a, b) => {
            let valA, valB;
            switch (window.sortDefActive) {
                case 'title_asc':
                    valA = a.getAttribute('data-unvan').toLocaleLowerCase('tr-TR');
                    valB = b.getAttribute('data-unvan').toLocaleLowerCase('tr-TR');
                    return valA.localeCompare(valB, 'tr');
                case 'title_desc':
                    valA = a.getAttribute('data-unvan').toLocaleLowerCase('tr-TR');
                    valB = b.getAttribute('data-unvan').toLocaleLowerCase('tr-TR');
                    return valB.localeCompare(valA, 'tr');
                case 'wage_asc':
                    valA = parseFloat(a.getAttribute('data-ucret-raw'));
                    valB = parseFloat(b.getAttribute('data-ucret-raw'));
                    return valA - valB;
                case 'wage_desc':
                    valA = parseFloat(a.getAttribute('data-ucret-raw'));
                    valB = parseFloat(b.getAttribute('data-ucret-raw'));
                    return valB - valA;
                case 'edu_asc':
                    valA = a.getAttribute('data-ogrenim').toLocaleLowerCase('tr-TR');
                    valB = b.getAttribute('data-ogrenim').toLocaleLowerCase('tr-TR');
                    return valA.localeCompare(valB, 'tr');
                case 'edu_desc':
                    valA = a.getAttribute('data-ogrenim').toLocaleLowerCase('tr-TR');
                    valB = b.getAttribute('data-ogrenim').toLocaleLowerCase('tr-TR');
                    return valB.localeCompare(valA, 'tr');
                default:
                    return 0;
            }
        });
        
        cards.forEach(card => container.appendChild(card));
    }

    // Apply filters
    function applyDefinitionFilters() {
        const termInput = document.getElementById('definitionSearch');
        const term = termInput ? termInput.value.toLowerCase().trim() : '';
        
        const ogrenimSelect = document.getElementById('filter-def-ogrenim');
        const kidemSelect = document.getElementById('filter-def-kidem');
        const ucretOpSelect = document.getElementById('filter-def-ucret-op');
        const ucretValInput = document.getElementById('filter-def-ucret-val');
        
        const selectedOgrenimler = ogrenimSelect ? Array.from(ogrenimSelect.selectedOptions).map(o => o.value).filter(val => val !== "") : [];
        const selectedKidemler = kidemSelect ? Array.from(kidemSelect.selectedOptions).map(o => o.value).filter(val => val !== "") : [];
        const ucretOp = ucretOpSelect ? ucretOpSelect.value : 'equals';
        const ucretVal = ucretValInput && ucretValInput.value !== "" ? parseFloat(ucretValInput.value) : null;
        
        window.filterDefOgrenim = selectedOgrenimler;
        window.filterDefKidem = selectedKidemler;
        window.filterDefUcretVal = ucretVal;
        window.filterDefUcretOp = ucretOp;
        
        const cards = document.querySelectorAll('.definition-item-card');
        let countVisible = 0;
        
        cards.forEach(card => {
            const unvan = card.getAttribute('data-unvan').toLowerCase();
            const ogrenim = card.getAttribute('data-ogrenim');
            const kidem = card.getAttribute('data-kidem');
            const ucret = parseFloat(card.getAttribute('data-ucret-raw'));
            
            let matchTerm = true;
            if (term !== "") {
                matchTerm = unvan.includes(term) || ogrenim.toLowerCase().includes(term);
            }
            
            let matchOgrenim = true;
            if (selectedOgrenimler.length > 0) {
                matchOgrenim = selectedOgrenimler.includes(ogrenim);
            }
            
            let matchKidem = true;
            if (selectedKidemler.length > 0) {
                matchKidem = selectedKidemler.includes(kidem);
            }
            
            let matchUcret = true;
            if (ucretVal !== null) {
                switch (ucretOp) {
                    case 'equals': matchUcret = (ucret === ucretVal); break;
                    case 'gt': matchUcret = (ucret > ucretVal); break;
                    case 'lt': matchUcret = (ucret < ucretVal); break;
                    case 'gte': matchUcret = (ucret >= ucretVal); break;
                    case 'lte': matchUcret = (ucret <= ucretVal); break;
                }
            }
            
            if (matchTerm && matchOgrenim && matchKidem && matchUcret) {
                card.classList.remove('hidden');
                countVisible++;
            } else {
                card.classList.add('hidden');
            }
        });
        
        sortDefinitionCards();
        
        const countText = document.querySelector('.definition-count-text');
        if (countText) {
            countText.innerText = `Toplam ${countVisible} Ücret Tanımı`;
        }
        
        const filterBtn = document.getElementById('mobileDefFilterToggleBtn');
        if (filterBtn) {
            const hasActiveFilters = selectedOgrenimler.length > 0 || selectedKidemler.length > 0 || ucretVal !== null;
            if (hasActiveFilters) {
                filterBtn.className = "p-3 bg-zinc-900 dark:bg-zinc-50 text-white dark:text-zinc-950 border border-zinc-900 dark:border-zinc-50 rounded-lg active:scale-95 transition-all cursor-pointer flex items-center justify-center shadow-md shadow-zinc-500/10";
            } else {
                filterBtn.className = "p-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg text-zinc-600 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer flex items-center justify-center";
            }
        }
        
        renderDefActiveBadges();
    }

    // Render active badges
    function renderDefActiveBadges() {
        const badgeContainer = document.getElementById('active-def-filters-badges');
        if (!badgeContainer) return;
        
        const hasActiveFilters = window.filterDefOgrenim.length > 0 || window.filterDefKidem.length > 0 || window.filterDefUcretVal !== null;
        
        if (hasActiveFilters) {
            let badgesHtml = '';
            
            if (window.filterDefOgrenim.length > 0) {
                badgesHtml += `
                    <div class="inline-flex items-center gap-1.5 bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700/50 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                        <span>Öğrenim: ${window.filterDefOgrenim.join(', ')}</span>
                        <button onclick="clearSingleDefFilter('ogrenim')" class="hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors ml-0.5 font-bold cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                `;
            }
            
            if (window.filterDefKidem.length > 0) {
                badgesHtml += `
                    <div class="inline-flex items-center gap-1.5 bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700/50 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                        <span>Kıdem: ${window.filterDefKidem.join(', ')}</span>
                        <button onclick="clearSingleDefFilter('kidem')" class="hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors ml-0.5 font-bold cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                `;
            }
            
            if (window.filterDefUcretVal !== null) {
                let opSign = '=';
                if (window.filterDefUcretOp === 'gt') opSign = '>';
                if (window.filterDefUcretOp === 'lt') opSign = '<';
                if (window.filterDefUcretOp === 'gte') opSign = '≥';
                if (window.filterDefUcretOp === 'lte') opSign = '≤';
                
                badgesHtml += `
                    <div class="inline-flex items-center gap-1.5 bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700/50 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                        <span>Ücret ${opSign} ₺${window.filterDefUcretVal.toLocaleString('tr-TR')}</span>
                        <button onclick="clearSingleDefFilter('ucret')" class="hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors ml-0.5 font-bold cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                `;
            }
            
            badgeContainer.innerHTML = badgesHtml;
            badgeContainer.classList.remove('hidden');
        } else {
            badgeContainer.innerHTML = '';
            badgeContainer.classList.add('hidden');
        }
    }

    // Clear single filter
    function clearSingleDefFilter(type) {
        if (type === 'ogrenim') {
            const ogrenimSelect = document.getElementById('filter-def-ogrenim');
            if (ogrenimSelect) {
                Array.from(ogrenimSelect.options).forEach(o => o.selected = false);
                const placeholder = ogrenimSelect.querySelector('option[value=""]');
                if (placeholder) placeholder.selected = true;
            }
        } else if (type === 'kidem') {
            const kidemSelect = document.getElementById('filter-def-kidem');
            if (kidemSelect) {
                Array.from(kidemSelect.options).forEach(o => o.selected = false);
                const placeholder = kidemSelect.querySelector('option[value=""]');
                if (placeholder) placeholder.selected = true;
            }
        } else if (type === 'ucret') {
            const ucretValInput = document.getElementById('filter-def-ucret-val');
            if (ucretValInput) ucretValInput.value = '';
        }
        
        if (typeof syncMobileCustomSelects === 'function') {
            syncMobileCustomSelects();
        }
        applyDefinitionFilters();
    }

    // Clear all filters
    function clearAllDefFilters() {
        const ogrenimSelect = document.getElementById('filter-def-ogrenim');
        const kidemSelect = document.getElementById('filter-def-kidem');
        const ucretValInput = document.getElementById('filter-def-ucret-val');
        
        if (ogrenimSelect) {
            Array.from(ogrenimSelect.options).forEach(o => o.selected = false);
            const placeholder = ogrenimSelect.querySelector('option[value=""]');
            if (placeholder) placeholder.selected = true;
        }
        if (kidemSelect) {
            Array.from(kidemSelect.options).forEach(o => o.selected = false);
            const placeholder = kidemSelect.querySelector('option[value=""]');
            if (placeholder) placeholder.selected = true;
        }
        if (ucretValInput) ucretValInput.value = '';
        
        if (typeof syncMobileCustomSelects === 'function') {
            syncMobileCustomSelects();
        }
        applyDefinitionFilters();
    }

    // Confirm Delete Definition
    function confirmDeleteDefinition(id, unvan) {
        showConfirmDialog(
            'Ücret Tanımını Sil',
            `"${unvan}" isimli ücret tanımını silmek istediğinize emin misiniz? Bu işlem geri alınamaz.`,
            () => {
                const basePath = '<?= appBasePath(); ?>';
                const formData = new FormData();
                formData.append('id', id);

                fetch(basePath + '/ucret-sil', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Ücret tanımı silindi.');
                        closeAllSheets();
                        setTimeout(() => {
                            switchTab('definitions');
                        }, 1200);
                    } else {
                        showToast(data.error || 'Silme işlemi gerçekleştirilemedi.', 'error');
                    }
                })
                .catch(err => {
                    showToast('Bağlantı hatası oluştu.', 'error');
                });
            }
        );
    }

    // Confirm Delete Period
    function confirmDeletePeriod(period) {
        showConfirmDialog(
            'Dönemi Sil ve Kaldır',
            `"${period}" dönemine ait tüm ücret tanımlarını silmek istediğinize emin misiniz? Bu işlem geri alınamaz.`,
            () => {
                const basePath = '<?= appBasePath(); ?>';
                const formData = new FormData();
                formData.append('donem', period);

                fetch(basePath + '/ucret-donem-sil', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Ücret dönemi silindi.');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1200);
                    } else {
                        showToast(data.error || 'Dönem silme işlemi gerçekleştirilemedi.', 'error');
                    }
                })
                .catch(err => {
                    showToast('Bağlantı hatası oluştu.', 'error');
                });
            }
        );
    }

    // Add submit listener to Form
    (function() {
        const form = document.getElementById('definitionForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const id = document.getElementById('form-def-id').value;
                const basePath = '<?= appBasePath(); ?>';
                const url = id ? (basePath + '/ucret-guncelle') : (basePath + '/ucret-ekle');
                const formData = new FormData(this);
                
                // Parse formatted Turkish currency back to standard decimal float before sending
                let ucretVal = formData.get('ucret') || '';
                ucretVal = ucretVal.replace(/\./g, '').replace(',', '.');
                formData.set('ucret', ucretVal);
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(id ? 'Ücret tanımı güncellendi.' : 'Ücret tanımı eklendi.');
                        closeAllSheets();
                        setTimeout(() => {
                            switchTab('definitions');
                        }, 1200);
                    } else {
                        showToast(data.error || 'Kaydetme sırasında bir hata oluştu.', 'error');
                    }
                })
                .catch(err => {
                    showToast('Sunucuya erişilemedi.', 'error');
                });
            });
        }

        // Initialize custom selects inside subpage
        if (typeof initMobileCustomSelects === 'function') {
            initMobileCustomSelects();
        }
    })();
</script>

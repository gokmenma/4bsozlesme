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

$defModel = new Definition();
$settings = $defModel->getSettings($tenant_id);
$default_period = $settings['default_wage_period'] ?? '2026-1';

$stmt = $db->prepare("
    SELECT p.*, 
           u.unvan, 
           COALESCE(u_def.ucret, u.ucret) as ucret, 
           u.ogrenim, 
           u.kidem_yili
    FROM personeller p 
    LEFT JOIN ucretler u ON p.ucret_id = u.id 
    LEFT JOIN ucretler u_def ON u_def.tenant_id = p.tenant_id 
                            AND u_def.unvan = u.unvan 
                            AND u_def.ogrenim = u.ogrenim 
                            AND u_def.kidem_yili = u.kidem_yili
                            AND u_def.donem = ?
                            AND u_def.deleted_at IS NULL
    WHERE p.deleted_at IS NULL AND p.tenant_id = ? 
    ORDER BY p.ad_soyad ASC
");
$stmt->execute([$default_period, $tenant_id]);
$personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Extract unique unvanlar for advanced filters
$unvanlar = array_unique(array_filter(array_map(function($p) {
    return $p['unvan'] ?? null;
}, $personnels)));
sort($unvanlar);

// Extract unique ogrenimler for advanced filters
$ogrenimler = array_unique(array_filter(array_map(function($p) {
    return $p['ogrenim'] ?? null;
}, $personnels)));
sort($ogrenimler);
?>
<div class="space-y-4 animate-fade-in pb-16">
    <!-- Search & Filter Bar -->
    <div class="flex items-center gap-2">
        <div class="relative flex-1 flex items-center">
            <div class="absolute left-4 text-zinc-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input id="personnelSearch" type="text" class="mobile-input pl-icon" placeholder="Personel ara (İsim, TC...)" onkeyup="applyPersonnelFilters()">
        </div>
        <!-- Sort Button -->
        <button id="mobileSortBtn" onclick="openSortSheet()" class="p-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg text-zinc-600 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m3 16 4 4 4-4M7 20V4M21 8l-4-4-4 4M17 4v16"/></svg>
        </button>
        <!-- Filter Button -->
        <button id="mobileFilterToggleBtn" onclick="openFilterSheet()" class="p-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg text-zinc-600 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        </button>
    </div>

    <!-- Active Filter Badges Container -->
    <div id="active-filters-badges" class="flex flex-wrap gap-1.5 px-0.5 mt-1 hidden"></div>

    <!-- Personnel list single card container (Shadcn 3rd image style) -->
    <?php if (empty($personnels)): ?>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-10 rounded-xl text-center space-y-2">
            <p class="text-xs font-bold text-zinc-400">Henüz personel bulunmamaktadır.</p>
            <button onclick="openEkleSheet()" class="px-4 py-2 bg-zinc-50 hover:bg-zinc-200 rounded-md text-xs font-bold mt-2 text-zinc-950">Personel Ekle</button>
        </div>
    <?php else: ?>
        <div id="personnel-list-wrapper">
            <?php foreach ($personnels as $p): 
                $tcMasked = substr($p['tc_kimlik'], 0, 3) . '******' . substr($p['tc_kimlik'], -2);
                
                // Color-coded status badge matching the custom pill design
                $durumLabel = 'Pasif';
                $durumClass = 'bg-rose-50 text-rose-600 border border-rose-100 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/10';
                
                switch ($p['durum']) {
                    case 'aktif':
                        $durumLabel = 'Aktif';
                        $durumClass = 'bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/10';
                        break;
                    case 'dilekce_alindi':
                        $durumLabel = 'Dilekçe Alındı';
                        $durumClass = 'bg-indigo-50 text-indigo-600 border border-indigo-100 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-900/10';
                        break;
                    case 'kadroya_gecti':
                        $durumLabel = 'Kadroya Geçti';
                        $durumClass = 'bg-sky-50 text-sky-600 border border-sky-100 dark:bg-sky-950/20 dark:text-sky-400 dark:border-sky-900/10';
                        break;
                    case 'kadroya_gecmeyecek':
                        $durumLabel = 'Kadroya Geçmeyecek';
                        $durumClass = 'bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/10';
                        break;
                    case 'pasif':
                    default:
                        $durumLabel = 'Pasif';
                        $durumClass = 'bg-rose-50 text-rose-600 border border-rose-100 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/10';
                        break;
                }

                // Initial character calculator for visual avatar circle
                $words = explode(' ', trim($p['ad_soyad']));
                $initials = '';
                if (count($words) >= 2) {
                    $initials = mb_substr($words[0], 0, 1, 'UTF-8') . mb_substr(end($words), 0, 1, 'UTF-8');
                } else {
                    $initials = mb_substr($p['ad_soyad'], 0, 2, 'UTF-8');
                }
                $initials = mb_strtoupper($initials, 'UTF-8');

                // Kadro hakkı gelme kontrolü (3 yıl dolmuş olması)
                $is_eligible = false;
                if (!empty($p['goreve_baslama_tarihi'])) {
                    $eligible_date = strtotime('+3 years', strtotime($p['goreve_baslama_tarihi']));
                    $is_eligible = ($eligible_date <= time());
                }
            ?>
                <div class="swipe-container relative overflow-hidden personnel-item-card"
                     data-id="<?= $p['id'] ?>"
                     data-name="<?= htmlspecialchars($p['ad_soyad']) ?>"
                     data-tc="<?= $p['tc_kimlik'] ?>"
                     data-masked-tc="<?= $tcMasked ?>"
                     data-telefon="<?= htmlspecialchars($p['telefon']) ?>"
                     data-meslek="<?= htmlspecialchars($p['meslek_kodu']) ?>"
                     data-cinsiyet="<?= htmlspecialchars($p['cinsiyet']) ?>"
                     data-baslama="<?= date('d.m.Y', strtotime($p['goreve_baslama_tarihi'])) ?>"
                     data-unvan="<?= htmlspecialchars($p['unvan'] ?? 'Tanımlanmamış') ?>"
                     data-ucret-id="<?= $p['ucret_id'] ?>"
                     data-ucret="<?= number_format($p['ucret'] ?? 0, 2, ',', '.') ?> TL"
                     data-ogrenim="<?= htmlspecialchars($p['ogrenim'] ?? '-') ?>"
                     data-kidem="<?= htmlspecialchars($p['kidem_yili'] ?? '-') ?>"
                     data-durum="<?= $p['durum'] ?>"
                     data-ucret-raw="<?= $p['ucret'] ?? 0 ?>" data-eligible="<?= $is_eligible ? '1' : '0' ?>">
                     
                    <!-- Left Background Actions (Sağa Kaydırma) - Elegant Float Brand Actions (3.resimdeki gibi) -->
                    <div class="swipe-left-actions absolute inset-y-0 left-0 flex items-stretch gap-4 pl-4 z-0">
                        <button onclick="event.stopPropagation(); previewContract('<?= $p['id'] ?>')" class="w-12 h-full bg-transparent text-zinc-600 dark:text-zinc-400 flex flex-col items-center justify-center transition-all cursor-pointer gap-1.5 hover:scale-110 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span class="text-[9px] font-bold uppercase tracking-wider leading-none">Sözleşme</span>
                        </button>
                        <button onclick="event.stopPropagation(); previewPetition('<?= $p['id'] ?>')" class="w-12 h-full bg-transparent text-zinc-600 dark:text-zinc-400 flex flex-col items-center justify-center transition-all cursor-pointer gap-1.5 hover:scale-110 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            <span class="text-[9px] font-bold uppercase tracking-wider leading-none">Dilekçe</span>
                        </button>
                    </div>

                    <!-- Right Background Actions (Sola Kaydırma) - Elegant Float Brand Actions (3.resimdeki gibi) -->
                    <div class="swipe-right-actions absolute inset-y-0 right-0 flex items-stretch z-0">
                        <button onclick="event.stopPropagation(); confirmDeletePersonnel('<?= $p['id'] ?>', '<?= htmlspecialchars($p['ad_soyad']) ?>')" class="w-14 h-full bg-transparent text-rose-600 dark:text-rose-400 flex flex-col items-center justify-center transition-all cursor-pointer gap-1.5 hover:scale-110 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2m-9 5v6m4-6v6"/></svg>
                            <span class="text-[9px] font-bold uppercase tracking-wider leading-none">Sil</span>
                        </button>
                    </div>

                    <!-- Main Swipeable Row Layer -->
                    <div class="swipe-front bg-white dark:bg-zinc-900 py-4 px-4 flex items-center justify-between transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 cursor-grab active:cursor-grabbing" 
                         onclick="openEditFormSheet(this.parentElement)">
                        <div class="space-y-1 select-none pointer-events-none">
                            <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200 leading-tight"><?= htmlspecialchars($p['ad_soyad']) ?></h4>
                            <div class="space-y-1">
                                <!-- Unvan Badge -->
                                <div class="inline-block">
                                    <span class="text-[9px] bg-zinc-100 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-800 px-2 py-0.5 rounded font-bold uppercase leading-none">
                                        <?= htmlspecialchars($p['unvan'] ?? 'Unvansız') ?>
                                    </span>
                                </div>
                                <!-- Giriş & Kadro Tarihleri -->
                                <div class="flex items-center gap-x-2 flex-wrap text-[10px] font-bold mt-1.5 text-zinc-400 dark:text-zinc-500 leading-none">
                                    <span>Giriş: <?= date('d.m.Y', strtotime($p['goreve_baslama_tarihi'])) ?></span>
                                    <span class="text-zinc-300 dark:text-zinc-800">•</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase leading-none <?= $is_eligible ? 'bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/10' : 'bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/10' ?>">
                                        Kadro: <?= date('d.m.Y', strtotime('+3 years', strtotime($p['goreve_baslama_tarihi']))) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column (Salary & Durum Badge) -->
                        <div class="text-right flex flex-col items-end justify-center select-none pointer-events-none space-y-2">
                            <span class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 block uppercase leading-none mb-2"><?= htmlspecialchars($p['ogrenim'] ?? '-') ?></span>
                            <span class="text-xs font-extrabold text-zinc-900 dark:text-zinc-100 block">₺<?= number_format($p['ucret'] ?? 0, 2, ',', '.') ?></span>
                            <span class="text-[9px] border px-2 py-0.5 rounded font-bold uppercase leading-none inline-block <?= $durumClass ?>">
                                <?= htmlspecialchars($durumLabel) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Floating Action Button -->
    <button onclick="openEkleSheet()" class="fixed bottom-24 right-6 w-14 h-14 rounded-full bg-zinc-900 dark:bg-zinc-50 text-zinc-50 dark:text-zinc-950 shadow-lg border border-zinc-200 dark:border-zinc-800 flex items-center justify-center active:scale-95 transition-all cursor-pointer z-40">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14m-7-7v14"/></svg>
    </button>
</div>

<!-- ADVANCED FILTER BOTTOM SHEET -->
<div id="filter-sheet" class="bottom-sheet flex flex-col max-h-[82%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    
    <div class="overflow-y-auto app-scroll px-6 pb-36 flex-1 space-y-5">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Gelişmiş Filtreleme</h3>
            <button onclick="clearAllFilters()" class="text-xs font-bold text-zinc-900 dark:text-zinc-100 hover:underline">Temizle</button>
        </div>
        
        <div class="space-y-4">
            <!-- Unvan Filtresi (Multiple) -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Unvan (Çoklu Seçim)</label>
                <div class="relative">
                    <select id="filter-unvan" class="mobile-input" multiple>
                        <option value="">Tüm Unvanlar</option>
                        <?php foreach ($unvanlar as $u): ?>
                            <option value="<?= htmlspecialchars($u) ?>"><?= htmlspecialchars($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Durum Filtresi (Multiple) -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Çalışma Durumu (Çoklu Seçim)</label>
                <div class="relative">
                    <select id="filter-durum" class="mobile-input" multiple>
                        <option value="">Tümü</option>
                        <option value="aktif">Aktif</option>
                        <option value="pasif">Pasif</option>
                        <option value="dilekce_alindi">Dilekçe Alındı</option>
                        <option value="kadroya_gecti">Kadroya Geçti</option>
                        <option value="kadroya_gecmeyecek">Kadroya Geçmeyecek</option>
                    </select>
                </div>
            </div>

            <!-- Öğrenim Durumu Filtresi -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Öğrenim Durumu</label>
                <div class="grid grid-cols-5 gap-2">
                    <div class="col-span-2">
                        <select id="filter-ogrenim-op" class="mobile-input">
                            <option value="equals" selected>Eşittir</option>
                            <option value="not_equals">Eşit Değil</option>
                        </select>
                    </div>
                    <div class="col-span-3">
                        <select id="filter-ogrenim" class="mobile-input">
                            <option value="">Tümü</option>
                            <?php foreach ($ogrenimler as $o): ?>
                                <option value="<?= htmlspecialchars($o) ?>"><?= htmlspecialchars($o) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Sözleşme Ücret Filtresi -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Sözleşme Ücreti</label>
                <div class="grid grid-cols-5 gap-2">
                    <div class="col-span-2">
                        <select id="filter-ucret-op" class="mobile-input">
                            <option value="equals" selected>Eşittir (=)</option>
                            <option value="gt">Büyüktür (>)</option>
                            <option value="lt">Küçüktür (<)</option>
                            <option value="gte">Büyük Eşit (>=)</option>
                            <option value="lte">Küçük Eşit (<=)</option>
                        </select>
                    </div>
                    <div class="col-span-3">
                        <input type="number" id="filter-ucret-val" class="mobile-input text-xs font-semibold" placeholder="Ücret girin (₺)">
                    </div>
                </div>
            </div>

            <!-- Göreve Başlama Tarihi Filtresi -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Göreve Başlama Tarihi</label>
                <div class="grid grid-cols-5 gap-2">
                    <div class="col-span-2">
                        <select id="filter-baslama-op" class="mobile-input">
                            <option value="equals" selected>Eşittir (=)</option>
                            <option value="gt">Sonra (>)</option>
                            <option value="lt">Önce (<)</option>
                            <option value="gte">Sonra veya Eşit (>=)</option>
                            <option value="lte">Önce veya Eşit (<=)</option>
                        </select>
                    </div>
                    <div class="col-span-3 text-right">
                        <input type="text" id="filter-baslama-tarih" class="mobile-input text-xs font-semibold py-2.5 px-3 select-none h-[42px] dark:[color-scheme:dark]" placeholder="Tarih Seçin">
                    </div>
                </div>
            </div>
        </div>
        
        <button onclick="applyPersonnelFilters(); closeAllSheets();" class="w-full py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-4 cursor-pointer active:scale-95 transition-all shadow-sm">
            Filtreyi Uygula
        </button>
    </div>
</div>

<!-- ADVANCED SORT BOTTOM SHEET -->
<div id="sort-sheet" class="bottom-sheet flex flex-col max-h-[82%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    
    <div class="overflow-y-auto app-scroll px-6 pb-24 flex-1 space-y-5">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Sıralama Seçenekleri</h3>
        </div>
        
        <div class="space-y-1">
            <button onclick="applySorting('name_asc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="name_asc">
                <span>Ad Soyada göre (A'dan Z'ye)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button onclick="applySorting('name_desc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="name_desc">
                <span>Ad Soyada göre (Z'den A'ya)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            
            <div class="h-px bg-zinc-100 dark:bg-zinc-800 my-2"></div>
            
            <button onclick="applySorting('start_asc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="start_asc">
                <span>Göreve Başlama Tarihi (Eskiden Yeniye)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button onclick="applySorting('start_desc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="start_desc">
                <span>Göreve Başlama Tarihi (Yeniden Eskiye)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            
            <div class="h-px bg-zinc-100 dark:bg-zinc-800 my-2"></div>

            <button onclick="applySorting('tenure_asc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="tenure_asc">
                <span>Kadroya Geçiş Tarihi (Eskiden Yeniye)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button onclick="applySorting('tenure_desc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="tenure_desc">
                <span>Kadroya Geçiş Tarihi (Yeniden Eskiye)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            
            <div class="h-px bg-zinc-100 dark:bg-zinc-800 my-2"></div>

            <button onclick="applySorting('wage_asc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="wage_asc">
                <span>Sözleşme Ücretine göre (Artan)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button onclick="applySorting('wage_desc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="wage_desc">
                <span>Sözleşme Ücretine göre (Azalan)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            
            <div class="h-px bg-zinc-100 dark:bg-zinc-800 my-2"></div>

            <button onclick="applySorting('edu_asc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="edu_asc">
                <span>Öğrenim Durumuna göre (A'dan Z'ye)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button onclick="applySorting('edu_desc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="edu_desc">
                <span>Öğrenim Durumuna göre (Z'den A'ya)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            
            <div class="h-px bg-zinc-100 dark:bg-zinc-800 my-2"></div>

            <button onclick="applySorting('title_asc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="title_asc">
                <span>Unvana göre (A'dan Z'ye)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button onclick="applySorting('title_desc')" class="sort-option-btn w-full px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl cursor-pointer flex items-center justify-between transition-colors font-semibold" data-sort="title_desc">
                <span>Unvana göre (Z'den A'ya)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="check-icon hidden text-zinc-900 dark:text-zinc-100 shrink-0" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
        </div>
    </div>
</div>

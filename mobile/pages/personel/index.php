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

$stmt = $db->prepare("
    SELECT p.*, u.unvan, u.ucret, u.ogrenim, u.kidem_yili
    FROM personeller p 
    LEFT JOIN ucretler u ON p.ucret_id = u.id 
    WHERE p.deleted_at IS NULL AND p.tenant_id = ? 
    ORDER BY p.ad_soyad ASC
");
$stmt->execute([$tenant_id]);
$personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Extract unique unvanlar for advanced filters
$unvanlar = array_unique(array_filter(array_map(function($p) {
    return $p['unvan'] ?? null;
}, $personnels)));
sort($unvanlar);
?>
<div class="space-y-4 animate-fade-in pb-16">
    <!-- Section Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-extrabold text-zinc-950 dark:text-zinc-50">Personel Kadrosu</h2>
            <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider personnel-count-text">Toplam <?= count($personnels) ?> Çalışan</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="flex items-center gap-2">
        <div class="relative flex-1 flex items-center">
            <div class="absolute left-4 text-zinc-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input id="personnelSearch" type="text" class="mobile-input pl-11" placeholder="Personel ara (İsim, TC...)" onkeyup="applyPersonnelFilters()">
        </div>
        <button onclick="openFilterSheet()" class="p-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg text-zinc-600 dark:text-zinc-400 active:scale-95 transition-all cursor-pointer flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        </button>
    </div>

    <!-- Personnel list single card container (Shadcn 3rd image style) -->
    <?php if (empty($personnels)): ?>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-10 rounded-xl text-center space-y-2">
            <p class="text-xs font-bold text-zinc-400">Henüz personel bulunmamaktadır.</p>
            <button onclick="openEkleSheet()" class="px-4 py-2 bg-zinc-50 hover:bg-zinc-200 rounded-md text-xs font-bold mt-2 text-zinc-950">Personel Ekle</button>
        </div>
    <?php else: ?>
        <div id="personnel-list-wrapper" class="border border-zinc-200 dark:border-zinc-800/80 rounded-xl overflow-hidden shadow-sm divide-y divide-zinc-200/60 dark:divide-zinc-800/50 bg-white dark:bg-zinc-950">
            <?php foreach ($personnels as $p): 
                $tcMasked = substr($p['tc_kimlik'], 0, 3) . '******' . substr($p['tc_kimlik'], -2);
                
                // Color-coded premium status badge next to name (exactly like the yellow 'Pending' badge)
                $durumClass = ($p['durum'] === 'aktif') 
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/30 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/10' 
                    : 'bg-amber-50 text-amber-700 border border-amber-200/30 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/10';

                // Initial character calculator for visual avatar circle
                $words = explode(' ', trim($p['ad_soyad']));
                $initials = '';
                if (count($words) >= 2) {
                    $initials = mb_substr($words[0], 0, 1, 'UTF-8') . mb_substr(end($words), 0, 1, 'UTF-8');
                } else {
                    $initials = mb_substr($p['ad_soyad'], 0, 2, 'UTF-8');
                }
                $initials = mb_strtoupper($initials, 'UTF-8');
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
                     data-durum="<?= $p['durum'] ?>">
                     
                    <!-- Left Background Actions (Sağa Kaydırma) - Beautiful Premium Solid Actions -->
                    <div class="absolute inset-y-0 left-0 flex items-stretch z-0">
                        <button onclick="event.stopPropagation(); previewContract('<?= $p['id'] ?>')" class="w-14 h-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white flex flex-col items-center justify-center transition-all cursor-pointer gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span class="text-[7.5px] font-extrabold uppercase tracking-wider leading-none">Sözleşme</span>
                        </button>
                        <button onclick="event.stopPropagation(); previewPetition('<?= $p['id'] ?>')" class="w-14 h-full bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white flex flex-col items-center justify-center transition-all cursor-pointer gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            <span class="text-[7.5px] font-extrabold uppercase tracking-wider leading-none">Dilekçe</span>
                        </button>
                    </div>

                    <!-- Right Background Actions (Sola Kaydırma) - Beautiful Premium Solid Actions -->
                    <div class="absolute inset-y-0 right-0 flex items-stretch z-0">
                        <button onclick="event.stopPropagation(); confirmDeletePersonnel('<?= $p['id'] ?>', '<?= htmlspecialchars($p['ad_soyad']) ?>')" class="w-14 h-full bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white flex flex-col items-center justify-center transition-all cursor-pointer gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2m-9 5v6m4-6v6"/></svg>
                            <span class="text-[7.5px] font-extrabold uppercase tracking-wider leading-none">Sil</span>
                        </button>
                    </div>

                    <!-- Main Swipeable Row Layer (Exact Organization Members Style) -->
                    <div class="swipe-front bg-white dark:bg-zinc-950 py-4 px-4 flex items-center justify-between transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-900/40 cursor-grab active:cursor-grabbing" 
                         onclick="openDetailSheet(this.parentElement)">
                        <div class="flex items-center gap-3.5 select-none pointer-events-none">
                            <!-- Circular Initials Avatar (Exactly like the 'OH' monogram in the reference image) -->
                            <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800/80 flex items-center justify-center font-semibold text-zinc-600 dark:text-zinc-400 text-sm shadow-sm flex-shrink-0">
                                <?= $initials ?>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[13.5px] font-semibold text-zinc-900 dark:text-zinc-50 leading-tight"><?= htmlspecialchars($p['ad_soyad']) ?></span>
                                    <!-- Soft Status Badge (exactly like the 'Pending' tag) -->
                                    <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider <?= $durumClass ?>">
                                        <?= htmlspecialchars($p['durum']) ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-zinc-500 dark:text-zinc-400 font-medium">
                                    <span><?= $tcMasked ?></span>
                                    <span class="text-zinc-300 dark:text-zinc-800">•</span>
                                    <span class="text-zinc-900 dark:text-zinc-100 font-bold"><?= number_format($p['ucret'] ?? 0, 2, ',', '.') ?> TL</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Role Outline Badge on the right (Exactly like Owner/Developer outline pills in reference image) -->
                        <div class="select-none pointer-events-none flex-shrink-0">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-950 shadow-sm leading-none inline-block">
                                <?= htmlspecialchars($p['unvan'] ?? 'Unvansız') ?>
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
<div id="filter-sheet" class="bottom-sheet flex flex-col max-h-[75%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    
    <div class="overflow-y-auto app-scroll px-6 pb-8 flex-1 space-y-5">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Gelişmiş Filtreleme</h3>
            <button onclick="clearAllFilters()" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Temizle</button>
        </div>
        
        <div class="space-y-4">
            <!-- Unvan Filtresi -->
            <div class="space-y-1.5">
                <label class="text-[0.78rem] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Unvan</label>
                <select id="filter-unvan" class="mobile-input">
                    <option value="">Tüm Unvanlar</option>
                    <?php foreach ($unvanlar as $u): ?>
                        <option value="<?= htmlspecialchars($u) ?>"><?= htmlspecialchars($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Durum Filtresi -->
            <div class="space-y-1.5">
                <label class="text-[0.78rem] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Çalışma Durumu</label>
                <select id="filter-durum" class="mobile-input">
                    <option value="">Tümü</option>
                    <option value="aktif">Aktif</option>
                    <option value="pasif">Pasif</option>
                </select>
            </div>

            <!-- Göreve Başlama Yılı Filtresi -->
            <div class="space-y-1.5">
                <label class="text-[0.78rem] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block">Göreve Başlama Yılı</label>
                <select id="filter-baslama-yili" class="mobile-input">
                    <option value="">Tüm Yıllar</option>
                    <?php 
                    $yillar = array_unique(array_filter(array_map(function($p) {
                        return !empty($p['goreve_baslama_tarihi']) ? date('Y', strtotime($p['goreve_baslama_tarihi'])) : null;
                    }, $personnels)));
                    rsort($yillar);
                    foreach ($yillar as $y): 
                    ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <button onclick="applyPersonnelFilters()" class="w-full py-3.5 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 mt-4 cursor-pointer active:scale-95 transition-all shadow-sm">
            Filtreyi Uygula
        </button>
    </div>
</div>

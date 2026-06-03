<?php

/**
 * =========================================================================
 *  CSRF (Cross-Site Request Forgery) Koruması
 * =========================================================================
 *  - csrf_token(): Oturuma bağlı tekil token üretir/döner.
 *  - csrf_meta() : <head> içine eklenen meta etiketi (JS bu tokeni okur).
 *  - csrf_field(): Klasik formlar için gizli input alanı.
 *  - csrf_verify(): Gelen POST isteğinde tokeni doğrular; geçersizse 403 + JSON döner.
 *
 *  İstemci tarafında assets/js/csrf.js tüm fetch / jQuery AJAX isteklerine
 *  otomatik olarak "X-CSRF-Token" başlığını ekler.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_meta(): string
{
    return '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? ($_POST['_csrf'] ?? ($_POST['csrf_token'] ?? ''));

    $stored = $_SESSION['csrf_token'] ?? '';
    $valid = is_string($sent) && $sent !== '' && $stored !== '' && hash_equals($stored, $sent);

    if (!$valid) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'csrf',
            'message' => 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * DataTables için premium preloader HTML şablonunu döner.
 * 
 * @param string $id Preloader ID'si (Varsayılan: table-preloader)
 * @return string
 */
function renderTablePreloader($id = 'table-preloader') {
    return '
    <!-- Premium CSS Animasyonlu Tablo Preloader -->
    <div id="' . $id . '" class="absolute inset-0 z-50 flex items-center justify-center bg-zinc-50 dark:bg-zinc-900 transition-all duration-500">
        <style>
            @keyframes helperCardShimmer {
                100% { transform: translateX(100%); }
            }
            @keyframes helperFloatScan {
                0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
                25% { transform: translate(-40px, -20px) scale(1.08) rotate(-8deg); }
                50% { transform: translate(-80px, -10px) scale(1.15) rotate(8deg); }
                75% { transform: translate(-50px, 15px) scale(1.08) rotate(-4deg); }
            }
            .animate-helper-card-shimmer {
                animation: helperCardShimmer 1.8s infinite linear;
            }
            .animate-helper-float-scan {
                animation: helperFloatScan 6s infinite ease-in-out;
            }
        </style>
        <div class="flex flex-col items-center gap-6">
            <div class="relative w-48 h-32 select-none" draggable="false">
                <!-- Skeleton Card Container -->
                <div class="w-full h-full bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl p-4 flex flex-col justify-between overflow-hidden relative">
                    <!-- Shimmer Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-zinc-100/60 dark:via-zinc-800/40 to-transparent -translate-x-full animate-helper-card-shimmer"></div>
                    
                    <div class="flex items-center gap-3">
                        <!-- Avatar Circle Placeholder -->
                        <div class="w-9 h-9 rounded-full bg-zinc-200 dark:bg-zinc-800/80 shrink-0"></div>
                        <!-- Title & Subtitle Line Placeholders -->
                        <div class="flex-1 space-y-2">
                            <div class="h-3 bg-zinc-200 dark:bg-zinc-800/80 rounded-md w-3/4"></div>
                            <div class="h-2 bg-zinc-150 dark:bg-zinc-800/50 rounded-md w-1/2"></div>
                        </div>
                    </div>
                    <!-- Card Footer Lines -->
                    <div class="space-y-2 pt-3 border-t border-zinc-100 dark:border-zinc-800/40">
                        <div class="h-2 bg-zinc-150 dark:bg-zinc-800/50 rounded-md w-5/6"></div>
                        <div class="h-2 bg-zinc-150 dark:bg-zinc-800/50 rounded-md w-2/3"></div>
                    </div>
                </div>
                
                <!-- Floating Magnifying Glass Scanner -->
                <div class="absolute -right-3 -bottom-3 text-indigo-600 dark:text-indigo-400 filter drop-shadow-xl animate-helper-float-scan">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
            </div>
            
            <div class="flex flex-col items-center gap-1.5 -mt-2">
                <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-50 tracking-tight flex items-center gap-1">
                    Veriler yükleniyor
                    <span class="inline-flex gap-0.5">
                        <span class="w-1 h-1 rounded-full bg-zinc-900 dark:bg-zinc-100 animate-bounce" style="animation-delay: 0s"></span>
                        <span class="w-1 h-1 rounded-full bg-zinc-900 dark:bg-zinc-100 animate-bounce" style="animation-delay: 0.15s"></span>
                        <span class="w-1 h-1 rounded-full bg-zinc-900 dark:bg-zinc-100 animate-bounce" style="animation-delay: 0.3s"></span>
                    </span>
                </h4>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Veriler hazırlanıyor, lütfen bekleyin...</p>
            </div>
        </div>
    </div>';
}

/**
 * Projedeki özel select bileşenini döner.
 * 
 * @param string $id Bileşenin benzersiz ID'si
 * @param string $name Hidden input'un name değeri
 * @param array $options Seçenekler listesi: [['value' => '...', 'label' => '...']]
 * @param string|null $selectedValue Varsayılan seçili değer
 * @param string $buttonClass Tetikleyici butonun ek sınıfları
 * @param string|null $heading Opsiyonel grup başlığı
 * @return string
 */
function renderCustomSelect($id, $name, $options, $selectedValue = null, $buttonClass = 'w-[180px]', $heading = null) {
    $selectedOption = null;
    foreach ($options as $opt) {
        if ($opt['value'] == $selectedValue) {
            $selectedOption = $opt;
            break;
        }
    }
    if (!$selectedOption && !empty($options)) {
        $selectedOption = $options[0];
    }
    $selectedLabel = $selectedOption ? $selectedOption['label'] : '';
    $selectedValue = $selectedOption ? $selectedOption['value'] : '';

    $outerClass = 'app-select custom-select-component relative inline-block';
    if (strpos($buttonClass, 'w-full') !== false) {
        $outerClass = 'app-select custom-select-component relative block w-full';
    }

    $popoverListClass = 'py-1 max-h-60 overflow-y-auto';
    if (strpos($id, 'donem_ay') !== false) {
        $popoverListClass = 'py-1 max-h-none';
    }

    $html = '
    <div id="' . htmlspecialchars($id) . '" class="' . $outerClass . '">
      <button type="button" class="btn-outline justify-between cursor-pointer flex items-center gap-2 px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 ' . htmlspecialchars($buttonClass) . '" id="' . htmlspecialchars($id) . '-trigger" aria-haspopup="listbox" aria-expanded="false" onclick="toggleCustomSelectPopover(\'' . htmlspecialchars($id) . '\')">
        <span class="truncate selected-label">' . htmlspecialchars($selectedLabel) . '</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-50 shrink-0">
          <path d="m7 15 5 5 5-5" />
          <path d="m7 9 5-5 5 5" />
        </svg>
      </button>
      <div id="' . htmlspecialchars($id) . '-popover" class="custom-select-popover absolute top-full left-0 mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg z-50 transition-all opacity-0 translate-y-[-10px] hidden" style="min-width: 100%;">
        <div role="listbox" id="' . htmlspecialchars($id) . '-listbox" class="' . $popoverListClass . '">';

    if ($heading) {
        $html .= '<div class="px-3 py-1 text-xs font-bold text-zinc-400 uppercase tracking-wider select-none">' . htmlspecialchars($heading) . '</div>';
    }

    foreach ($options as $opt) {
        $isSelected = ($opt['value'] == $selectedValue);
        $selectedClass = $isSelected ? 'selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold' : '';
        $html .= '
          <div role="option" data-value="' . htmlspecialchars($opt['value']) . '" onclick="selectCustomOption(\'' . htmlspecialchars($id) . '\', this)" class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer flex items-center justify-between transition-colors ' . $selectedClass . '">
            <span class="option-label">' . htmlspecialchars($opt['label']) . '</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon ' . ($isSelected ? '' : 'hidden') . ' text-primary"><path d="M20 6 9 17l-5-5"/></svg>
          </div>';
    }

    $html .= '
        </div>
      </div>
      <input type="hidden" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '-value" value="' . htmlspecialchars($selectedValue) . '" />
    </div>';

    return $html;
}

/**
 * Standard select dropdown renderer
 */
function renderSelect($id, $name, $options, $selectedValue = null, $class = '') {
    $html = '<select id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" class="' . htmlspecialchars($class) . '">';
    foreach ($options as $opt) {
        $sel = ($opt['value'] == $selectedValue) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($opt['value']) . '" ' . $sel . '>' . htmlspecialchars($opt['label']) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

/**
 * Çoklu seçim yapabilen custom select bileşeni
 */
function renderCustomMultiSelect($id, $name, $options, $selectedValues = [], $buttonClass = 'w-[180px]', $heading = null) {
    if (!is_array($selectedValues)) {
        $selectedValues = $selectedValues ? explode(',', $selectedValues) : [];
    }

    $selectedLabels = [];
    foreach ($options as $opt) {
        if (in_array($opt['value'], $selectedValues)) {
            $selectedLabels[] = $opt['label'];
        }
    }

    if (empty($selectedLabels)) {
        if (in_array('tum', array_column($options, 'value'))) {
            $selectedValues = ['tum'];
            $selectedLabels = ['Tümü'];
        } else {
            $selectedLabel = 'Seçiniz...';
        }
    }

    if (!empty($selectedLabels)) {
        if (count($selectedLabels) === count($options) || (count($selectedLabels) === 1 && $selectedValues[0] === 'tum')) {
            $selectedLabel = 'Tümü';
            $selectedValues = ['tum'];
        } else {
            $selectedLabel = implode(', ', $selectedLabels);
        }
    }

    $outerClass = 'app-select custom-select-component relative inline-block';
    if (strpos($buttonClass, 'w-full') !== false) {
        $outerClass = 'app-select custom-select-component relative block w-full';
    }

    $popoverListClass = 'py-1 max-h-60 overflow-y-auto';

    $html = '
    <div id="' . htmlspecialchars($id) . '" class="' . $outerClass . '" data-multiselect="true">
      <button type="button" class="btn-outline justify-between cursor-pointer flex items-center gap-2 px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 ' . htmlspecialchars($buttonClass) . '" id="' . htmlspecialchars($id) . '-trigger" aria-haspopup="listbox" aria-expanded="false" onclick="toggleCustomSelectPopover(\'' . htmlspecialchars($id) . '\')">
        <span class="truncate selected-label">' . htmlspecialchars($selectedLabel) . '</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-50 shrink-0">
          <path d="m7 15 5 5 5-5" />
          <path d="m7 9 5-5 5 5" />
        </svg>
      </button>
      <div id="' . htmlspecialchars($id) . '-popover" class="custom-select-popover absolute top-full left-0 mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg z-50 transition-all opacity-0 translate-y-[-10px] hidden" style="min-width: 100%;">
        <div role="listbox" id="' . htmlspecialchars($id) . '-listbox" class="' . $popoverListClass . '">';

    if ($heading) {
        $html .= '<div class="px-3 py-1 text-xs font-bold text-zinc-400 uppercase tracking-wider select-none">' . htmlspecialchars($heading) . '</div>';
    }

    foreach ($options as $opt) {
        $isSelected = in_array($opt['value'], $selectedValues);
        $selectedClass = $isSelected ? 'selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold' : '';
        $html .= '
          <div role="option" data-value="' . htmlspecialchars($opt['value']) . '" onclick="toggleCustomMultiSelectOption(\'' . htmlspecialchars($id) . '\', this)" class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer flex items-center justify-between transition-colors ' . $selectedClass . '">
            <span class="option-label">' . htmlspecialchars($opt['label']) . '</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon ' . ($isSelected ? '' : 'hidden') . ' text-primary"><path d="M20 6 9 17l-5-5"/></svg>
          </div>';
    }

    $html .= '
        </div>
      </div>
      <input type="hidden" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '-value" value="' . htmlspecialchars(implode(',', $selectedValues)) . '" />
    </div>';

    return $html;
}


<?php

/**
 * DataTables için premium preloader HTML şablonunu döner.
 * 
 * @param string $id Preloader ID'si (Varsayılan: table-preloader)
 * @return string
 */
function renderTablePreloader($id = 'table-preloader') {
    return '
    <!-- Shadcn Style Table Preloader -->
    <div id="' . $id . '" class="absolute inset-0 z-50 flex items-center justify-center bg-white/80 dark:bg-zinc-950/80 backdrop-blur-[2px] transition-all duration-500">
        <div class="flex flex-col items-center gap-4">
            <div class="relative flex items-center justify-center">
                <div class="w-10 h-10 border-2 border-zinc-200 dark:border-zinc-800 rounded-full"></div>
                <div class="absolute w-10 h-10 border-2 border-zinc-900 dark:border-zinc-100 border-t-transparent rounded-full animate-spin"></div>
            </div>
            <div class="flex flex-col items-center gap-1">
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-50 tracking-tight">Veriler işleniyor</p>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Lütfen bekleyin...</p>
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


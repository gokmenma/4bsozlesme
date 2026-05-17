window.showToast = function(options) {
    const config = {
        category: options.category || 'success',
        title: options.title || 'İşlem Başarılı',
        description: options.description || '',
        duration: options.duration || 5000,
        cancel: {
            label: options.cancelLabel || 'Kapat',
            onclick: options.onClose || null
        }
    };

    let toaster = document.getElementById('toaster');
    if (!toaster) {
        toaster = document.createElement('div');
        toaster.id = 'toaster';
        toaster.className = 'toaster';
        toaster.setAttribute('data-position', 'bottom-right');
        toaster.setAttribute('popover', 'manual');
        document.body.appendChild(toaster);
    }
    if (toaster && toaster.showPopover) {
        console.log("Toaster popover showing...");
        if (toaster.matches(':popover-open')) {
            try { toaster.hidePopover(); } catch (e) {}
        }
        try { toaster.showPopover(); } catch (e) {}
    }

    const event = new CustomEvent('basecoat:toast', {
        bubbles: true,
        cancelable: true,
        detail: {
            config: config
        }
    });

    document.dispatchEvent(event);
    window.dispatchEvent(event);
};

window.toggleCustomSelectPopover = function(id) {
    if (window.event) window.event.stopPropagation();
    const $popover = $('#' + id + '-popover');
    const isHidden = $popover.hasClass('hidden');
    
    // Close all other custom select popovers first
    $('.custom-select-popover').addClass('hidden').removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-[-10px]');
    
    if (isHidden) {
        $popover.removeClass('hidden');
        setTimeout(() => {
            $popover.addClass('opacity-100 translate-y-0').removeClass('opacity-0 translate-y-[-10px]');
        }, 10);
    }
};

window.selectCustomOption = function(id, el) {
    if (window.event) window.event.stopPropagation();
    const $el = $(el);
    const value = $el.data('value');
    const label = $el.find('.option-label').text().trim();
    
    const $component = $('#' + id);
    $component.find('input[type="hidden"]').val(value).trigger('change');
    $component.find('.selected-label').text(label);
    
    // Toggle checkmark icon and selected state classes
    $component.find('[role="option"]').removeClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
    $component.find('.check-icon').addClass('hidden');
    
    $el.addClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
    $el.find('.check-icon').removeClass('hidden');
    
    // Close popover
    $('#' + id + '-popover').addClass('hidden').removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-[-10px]');
};

// Handle closing when clicking outside (capturing phase to bypass stopPropagation in modals)
document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-select-component') && !e.target.closest('.select')) {
        $('.custom-select-popover').addClass('hidden').removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-[-10px]');
        $('[data-popover]').addClass('hidden').attr('aria-hidden', 'true');
        $('[aria-expanded="true"]').attr('aria-expanded', 'false');
    }
}, true);

// jQuery direct click handler on modals/dialogs as extra fallback
$(document).on('click', 'dialog, .dialog-content, .modal-content, .modal', function(e) {
    if (!$(e.target).closest('.custom-select-component').length && !$(e.target).closest('.select').length) {
        $('.custom-select-popover').addClass('hidden').removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-[-10px]');
        $('[data-popover]').addClass('hidden').attr('aria-hidden', 'true');
        $('[aria-expanded="true"]').attr('aria-expanded', 'false');
    }
});

window.convertToCustomSelect = function($select, options = {}) {
    if ($select.data('custom-select-initialized')) return;
    $select.data('custom-select-initialized', true);

    const id = 'custom-select-' + Math.random().toString(36).substr(2, 9);
    const $options = $select.find('option');
    const selectedValue = $select.val();
    let selectedLabel = $select.find('option:selected').text();

    if (!selectedLabel && $options.length > 0) {
        selectedLabel = $($options[0]).text();
    }

    const buttonClass = options.buttonClass || 'w-[80px]';

    let optionsHtml = '';
    $options.each(function() {
        const val = $(this).val();
        const lbl = $(this).text();
        const isSelected = (val == selectedValue);
        const selectedClass = isSelected ? 'selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold' : '';
        optionsHtml += `
          <div role="option" data-value="${val}" class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer flex items-center justify-between transition-colors ${selectedClass}">
            <span class="option-label">${lbl}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon ${isSelected ? '' : 'hidden'} text-primary"><path d="M20 6 9 17l-5-5"/></svg>
          </div>`;
    });

    const customSelectHtml = `
    <div id="${id}" class="app-select custom-select-component relative inline-block mx-2">
      <button type="button" class="btn-outline justify-between cursor-pointer flex items-center gap-2 px-3 py-1.5 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 ${buttonClass}" id="${id}-trigger" aria-haspopup="listbox" aria-expanded="false">
        <span class="truncate selected-label">${selectedLabel}</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-50 shrink-0">
          <path d="m7 15 5 5 5-5" />
          <path d="m7 9 5-5 5 5" />
        </svg>
      </button>
      <div id="${id}-popover" class="custom-select-popover absolute bottom-full left-0 mb-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg z-50 transition-all opacity-0 translate-y-[10px] hidden" style="min-width: 100%;">
        <div role="listbox" id="${id}-listbox" class="py-1 max-h-60 overflow-y-auto">
          ${optionsHtml}
        </div>
      </div>
    </div>`;

    $select.hide();
    $select.after(customSelectHtml);

    const $component = $('#' + id);
    $component.find('button').on('click', function(e) {
        e.stopPropagation();
        const $popover = $component.find('.custom-select-popover');
        const isHidden = $popover.hasClass('hidden');
        $('.custom-select-popover').addClass('hidden').removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-[-10px]');
        if (isHidden) {
            $popover.removeClass('hidden');
            setTimeout(() => {
                $popover.addClass('opacity-100 translate-y-0').removeClass('opacity-0 translate-y-[10px]');
            }, 10);
        }
    });

    $component.on('click', '[role="option"]', function(e) {
        e.stopPropagation();
        const $el = $(this);
        const value = $el.data('value');
        const label = $el.find('.option-label').text().trim();

        $select.val(value).trigger('change');
        $component.find('.selected-label').text(label);

        $component.find('[role="option"]').removeClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
        $component.find('.check-icon').addClass('hidden');

        $el.addClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
        $el.find('.check-icon').removeClass('hidden');

        $component.find('.custom-select-popover').addClass('hidden').removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-[10px]');
    });
};






(function () {
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    window.evSelectHtml = function (id, options, value, onchange, extraClass) {
        const list = Array.isArray(options) ? options : Object.entries(options).map(([v, l]) => ({ value: v, label: l }));
        const secili = list.find((o) => String(o.value) === String(value ?? '')) || list[0] || { value: '', label: '' };
        const items = list.map((o) => {
            const sel = String(o.value) === String(secili.value);
            return `<div role="option" data-value="${esc(o.value)}" onclick="selectCustomOption('${id}', this)" class="px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer flex items-center justify-between gap-3 transition-colors ${sel ? 'selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold' : ''}">
                <span class="option-label">${esc(o.label)}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon ${sel ? '' : 'hidden'} text-primary shrink-0"><path d="M20 6 9 17l-5-5"/></svg>
            </div>`;
        }).join('');
        return `
        <div id="${id}" class="app-select custom-select-component ev-select relative block w-full ${extraClass || ''}">
            <button type="button" class="ev-select-trigger" id="${id}-trigger" aria-haspopup="listbox" aria-expanded="false" onclick="toggleCustomSelectPopover('${id}')">
                <span class="truncate selected-label">${esc(secili.label)}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-50 shrink-0"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
            </button>
            <div id="${id}-popover" class="custom-select-popover absolute top-full left-0 mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg z-50 transition-all opacity-0 translate-y-[-10px] hidden" style="min-width: 100%;">
                <div role="listbox" class="py-1 max-h-64 overflow-y-auto">${items}</div>
            </div>
            <input type="hidden" id="${id}-value" value="${esc(secili.value)}"${onchange ? ` onchange="${esc(onchange)}"` : ''}>
        </div>`;
    };

    window.evSelectValue = function (id) {
        const el = document.getElementById(id + '-value');
        return el ? el.value : '';
    };
})();

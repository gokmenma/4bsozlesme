<?php 
$pageTitle = "Matrah Tablosu Yönetimi";
$pageSubtitle = "Matrah tablosundaki unvan, öğrenim ve puan bilgilerini yönetebilirsiniz.";
?>

<!-- DataTables CSS via CDN -->
<style>
  .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--muted) !important;
    border-color: var(--border) !important;
    color: var(--foreground) !important;
    border-radius: 0.375rem;
  }
</style>

<div class="flex flex-col gap-8">
  <!-- Actions Bar / Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex flex-col gap-1">
      <h1 class="text-2xl font-bold tracking-tight text-foreground"><?php echo $pageTitle; ?></h1>
      <p class="text-muted-foreground"><?php echo $pageSubtitle; ?></p>
    </div>
    <div class="flex items-center gap-3">
      <!-- Clear Filters Button -->
      <button id="clearAllFilters" style="display: none;" class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-sm whitespace-nowrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M7 6v12"/><path d="M11 6v12"/><path d="M15 6v12"/><path d="M19 6v12"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
        Temizle
      </button>

      <!-- General Table Search Input on the Right -->
      <div class="relative w-full max-w-xs">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input type="text" id="matrahSearch" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="Matrah ara...">
      </div>

      <button onclick="openModal('add')" class="inline-flex h-10 items-center justify-center rounded-md bg-zinc-900 dark:bg-white dark:text-zinc-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors whitespace-nowrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Yeni Matrah Ekle
      </button>
    </div>
  </div>

  <!-- Content Card -->
  <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
    <!-- Records Table -->
    <div class="p-6">
      <div class="overflow-x-auto">
        <table id="matrah-table" class="w-full border-collapse text-left text-sm">
          <thead>
            <tr class="bg-muted/40 border-b border-border">
              <th data-column="0" class="p-4">
                <div class="flex items-center justify-between gap-2 group/th min-w-[150px]">
                  <span class="font-semibold text-muted-foreground">Unvan</span>
                  <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  </button>
                </div>
              </th>
              <th data-column="1" class="p-4">
                <div class="flex items-center justify-between gap-2 group/th min-w-[120px]">
                  <span class="font-semibold text-muted-foreground">Öğrenim</span>
                  <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  </button>
                </div>
              </th>
              <th data-column="2" class="p-4">
                <div class="flex items-center justify-between gap-2 group/th min-w-[110px]">
                  <span class="font-semibold text-muted-foreground">Kıdem (Yıl)</span>
                  <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  </button>
                </div>
              </th>
              <th data-column="3" class="p-4">
                <div class="flex items-center justify-between gap-2 group/th min-w-[110px]">
                  <span class="font-semibold text-muted-foreground">Gösterge</span>
                  <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  </button>
                </div>
              </th>
              <th data-column="4" class="p-4">
                <div class="flex items-center justify-between gap-2 group/th min-w-[110px]">
                  <span class="font-semibold text-muted-foreground">Ek Göst.</span>
                  <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  </button>
                </div>
              </th>
              <th data-column="5" class="p-4">
                <div class="flex items-center justify-between gap-2 group/th min-w-[110px]">
                  <span class="font-semibold text-muted-foreground">Yan Ödeme</span>
                  <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  </button>
                </div>
              </th>
              <th data-column="6" class="p-4">
                <div class="flex items-center justify-between gap-2 group/th min-w-[110px]">
                  <span class="font-semibold text-muted-foreground">Özel Hizmet</span>
                  <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  </button>
                </div>
              </th>
              <th data-column="7" class="p-4">
                <div class="flex items-center justify-between gap-2 group/th min-w-[100px]">
                  <span class="font-semibold text-muted-foreground">Derece</span>
                  <button type="button" class="column-filter-btn p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  </button>
                </div>
              </th>
              <th class="p-4 font-semibold text-muted-foreground text-right no-sort">İşlemler</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add / Edit Modal Dialog -->
<dialog id="matrah-modal" class="dialog w-full sm:max-w-[500px]">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="flex items-start justify-between mb-6">
      <div>
        <h2 id="modal-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Yeni Matrah Ekle</h2>
        <p class="text-sm text-zinc-500">Kadro matrahı bilgilerini düzenleyin veya ekleyin.</p>
      </div>
      <button onclick="closeModal()" class="text-zinc-400 hover:text-zinc-600 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </header>

    <form id="matrah-form" class="form grid gap-4">
      <input type="hidden" id="modal-id" name="id">
      <input type="hidden" id="modal-action" name="action" value="create">

      <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-1">
          <label for="modal-unvan" class="text-sm font-medium">Unvan</label>
          <input type="text" id="modal-unvan" name="unvan" required class="px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
        </div>
        <div class="grid gap-1">
          <label for="modal-ogrenim" class="text-sm font-medium">Öğrenim</label>
          <input type="text" id="modal-ogrenim" name="ogrenim" required class="px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" placeholder="Örn: Lisans">
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div class="grid gap-1">
          <label for="modal-hizmet_yili" class="text-sm font-medium">Hizmet Yılı</label>
          <input type="number" id="modal-hizmet_yili" name="hizmet_yili" value="0" min="0" required class="px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
        </div>
        <div class="grid gap-1">
          <label for="modal-derece" class="text-sm font-medium">Derece</label>
          <input type="text" id="modal-derece" name="derece" placeholder="Örn: 9-1" class="px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
        </div>
        <div class="grid gap-1">
          <label for="modal-gosterge_puan" class="text-sm font-medium">Gösterge</label>
          <input type="number" id="modal-gosterge_puan" name="gosterge_puan" value="0" class="px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div class="grid gap-1">
          <label for="modal-ek_gosterge_puan" class="text-sm font-medium">Ek Gösterge</label>
          <input type="number" id="modal-ek_gosterge_puan" name="ek_gosterge_puan" value="0" class="px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
        </div>
        <div class="grid gap-1">
          <label for="modal-yan_odeme_puan" class="text-sm font-medium">Yan Ödeme</label>
          <input type="number" id="modal-yan_odeme_puan" name="yan_odeme_puan" value="0" class="px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
        </div>
        <div class="grid gap-1">
          <label for="modal-ozel_hizmet_puan" class="text-sm font-medium">Özel Hizmet</label>
          <input type="number" id="modal-ozel_hizmet_puan" name="ozel_hizmet_puan" value="0" class="px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
        </div>
      </div>

      <!-- Footer -->
      <footer class="mt-6 flex justify-end gap-3">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">Vazgeç</button>
        <button type="submit" id="modal-submit-btn" class="px-6 py-2 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm">Kaydet</button>
      </footer>
    </form>
  </div>
</dialog>

<!-- Delete Confirm Modal Dialog -->
<dialog id="delete-modal" class="dialog w-full sm:max-w-[400px]">
  <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="flex items-start justify-between mb-4">
      <div>
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Matrah Kaydını Sil</h2>
        <p class="text-sm text-zinc-500">Bu matrah kaydını silmek istediğinize emin misiniz?</p>
      </div>
      <button onclick="closeDeleteModal()" class="text-zinc-400 hover:text-zinc-600 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </header>

    <input type="hidden" id="delete-id">

    <!-- Footer -->
    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">İptal</button>
      <button type="button" id="delete-submit-btn" onclick="confirmDelete()" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">Sil</button>
    </footer>
  </div>
</dialog>

<script>
let tableInstance;

function setupClickOutsideClose(dialogId) {
    const dialog = document.getElementById(dialogId);
    if (!dialog) return;
    let isClickOutside = false;
    dialog.addEventListener('mousedown', (e) => {
        const rect = dialog.getBoundingClientRect();
        isClickOutside = (rect.top > e.clientY || e.clientY > rect.bottom || rect.left > e.clientX || e.clientX > rect.right);
    });
    dialog.addEventListener('mouseup', (e) => {
        const rect = dialog.getBoundingClientRect();
        const isNowOutside = (rect.top > e.clientY || e.clientY > rect.bottom || rect.left > e.clientX || e.clientX > rect.right);
        if (isClickOutside && isNowOutside && e.target === dialog) {
            dialog.close();
        }
    });
}

$(document).ready(function() {
    setupClickOutsideClose('matrah-modal');
    setupClickOutsideClose('delete-modal');

    // Initialize DataTables with server-side processing
    tableInstance = initDataTable('#matrah-table', {
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?php echo routeUrl('matrah-yonetimi'); ?>",
            "type": "POST",
            "data": function(d) {
                d.columnFilters = JSON.stringify($('#matrah-table').data('columnFilterState') || {});
            }
        },
        "order": [[0, 'asc']],
        "dom": '<"flex-1 overflow-auto"rt><"mt-auto border-t border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row justify-between items-center p-4 gap-4 bg-zinc-50/50 dark:bg-zinc-800/30"lip>',
        "columnDefs": [
            { "orderable": false, "targets": 'no-sort' }
        ]
    });

    // Custom general search input on top-right
    $('#matrahSearch').on('keyup', function() {
        tableInstance.search(this.value).draw();
        checkActiveFilters();
    });

    window.clearAllFilters = function() {
        $('#matrah-table').data('columnFilterState', {});
        tableInstance.search('').draw();
        tableInstance.draw();
        $('#matrahSearch').val('');
        $('.column-filter-btn').removeClass('active');
        $('#clearAllFilters').hide();
    };

    $('#clearAllFilters').on('click', clearAllFilters);

    function checkActiveFilters() {
        let hasFilter = false;
        
        let searchVal = $('#matrahSearch').val();
        if (searchVal && searchVal.trim().length > 0) {
            hasFilter = true;
        }

        let columnFilterState = $('#matrah-table').data('columnFilterState');
        if (columnFilterState) {
            for (let key in columnFilterState) {
                if (columnFilterState[key] && columnFilterState[key].rules && columnFilterState[key].rules.length > 0) {
                    $(`th[data-column="${key}"] .column-filter-btn`).addClass('active');
                    hasFilter = true;
                } else {
                    $(`th[data-column="${key}"] .column-filter-btn`).removeClass('active');
                }
            }
        }

        if (!hasFilter) {
            $('#clearAllFilters').hide();
        } else {
            $('#clearAllFilters').css('display', 'inline-flex');
        }
    }
});

function openModal(mode, data = null) {
    const modal = document.getElementById('matrah-modal');
    const form = document.getElementById('matrah-form');
    const title = document.getElementById('modal-title');
    const action = document.getElementById('modal-action');

    form.reset();

    if (mode === 'add') {
        title.innerText = 'Yeni Matrah Ekle';
        action.value = 'create';
    } else if (mode === 'edit' && data) {
        title.innerText = 'Matrah Kaydını Düzenle';
        action.value = 'update';
        document.getElementById('modal-id').value = data.id;
        document.getElementById('modal-unvan').value = data.unvan || '';
        document.getElementById('modal-ogrenim').value = data.ogrenim || '';
        document.getElementById('modal-hizmet_yili').value = data.hizmet_yili || 0;
        document.getElementById('modal-derece').value = data.derece || '';
        document.getElementById('modal-gosterge_puan').value = data.gosterge_puan || 0;
        document.getElementById('modal-ek_gosterge_puan').value = data.ek_gosterge_puan || 0;
        document.getElementById('modal-yan_odeme_puan').value = data.yan_odeme_puan || 0;
        document.getElementById('modal-ozel_hizmet_puan').value = data.ozel_hizmet_puan || 0;
    } else if (mode === 'copy' && data) {
        title.innerText = 'Matrah Kaydını Kopyalayarak Ekle';
        action.value = 'create';
        document.getElementById('modal-id').value = '';
        document.getElementById('modal-unvan').value = data.unvan || '';
        document.getElementById('modal-ogrenim').value = data.ogrenim || '';
        document.getElementById('modal-hizmet_yili').value = data.hizmet_yili || 0;
        document.getElementById('modal-derece').value = data.derece || '';
        document.getElementById('modal-gosterge_puan').value = data.gosterge_puan || 0;
        document.getElementById('modal-ek_gosterge_puan').value = data.ek_gosterge_puan || 0;
        document.getElementById('modal-yan_odeme_puan').value = data.yan_odeme_puan || 0;
        document.getElementById('modal-ozel_hizmet_puan').value = data.ozel_hizmet_puan || 0;
    }

    modal.showModal();
}

function closeModal() {
    const modal = document.getElementById('matrah-modal');
    modal.close();
}

document.getElementById('matrah-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('modal-submit-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin mr-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Kaydediliyor...`;

    const formData = new FormData(this);

    fetch('matrah-yonetimi', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast({
                category: 'success',
                title: 'Başarılı',
                description: data.message
            });
            closeModal();
            tableInstance.draw(false);
            btn.disabled = false;
            btn.innerHTML = originalText;
        } else {
            window.showToast({
                category: 'error',
                title: 'Hata',
                description: data.message
            });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(() => {
        window.showToast({
            category: 'error',
            title: 'Hata',
            description: 'İşlem sırasında bir hata oluştu.'
        });
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

function openDeleteModal(id) {
    document.getElementById('delete-id').value = id;
    document.getElementById('delete-modal').showModal();
}

function closeDeleteModal() {
    document.getElementById('delete-modal').close();
}

function confirmDelete() {
    const id = document.getElementById('delete-id').value;
    if (!id) return;

    const btn = document.getElementById('delete-submit-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin mr-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Siliniyor...`;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    fetch('matrah-yonetimi', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast({
                category: 'success',
                title: 'Başarılı',
                description: data.message
            });
            closeDeleteModal();
            tableInstance.draw(false);
        } else {
            window.showToast({
                category: 'error',
                title: 'Hata',
                description: data.message
            });
        }
        btn.disabled = false;
        btn.innerHTML = originalText;
    })
    .catch(() => {
        window.showToast({
            category: 'error',
            title: 'Hata',
            description: 'Silme işlemi sırasında bir hata oluştu.'
        });
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>

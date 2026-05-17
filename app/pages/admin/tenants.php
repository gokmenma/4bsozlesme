<?php 
$pageTitle = 'Kurum Yönetimi'; 
$pageSubtitle = 'Sistemdeki tüm kurumları ve aktiflik durumlarını yönetin';
?>

<div class="p-6">
    <div class="flex items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Tüm Kurumlar</h1>
        <div class="flex items-center gap-3">
            <div class="relative w-full max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="tenantSearch" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-sm placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="Kurum ara...">
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden relative flex flex-col h-[calc(100vh-230px)]">
        <div id="table-preloader" class="absolute inset-0 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-[1px] flex items-center justify-center z-10">
            <div class="flex flex-col items-center gap-3">
                <div class="relative">
                    <div class="w-10 h-10 border-2 border-zinc-200 dark:border-zinc-800 rounded-full"></div>
                    <div class="w-10 h-10 border-t-2 border-primary rounded-full animate-spin absolute top-0 left-0"></div>
                </div>
                <span class="text-xs font-medium text-zinc-500 animate-pulse">Veriler yükleniyor...</span>
            </div>
        </div>

        <div id="table-container" class="flex-1 flex flex-col overflow-hidden" style="display: none;">
            <table id="tenantTable" class="w-full text-left">
                <thead>
                    <tr>
                        <th>Kurum Adı</th>
                        <th>Slug</th>
                        <th>Kullanıcı Sayısı</th>
                        <th>Durum</th>
                        <th>Oluşturulma</th>
                        <th class="text-right no-sort">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <!-- AJAX ile dolacak -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Kurum Düzenle Dialog -->
<dialog id="dialog-edit-tenant" class="dialog w-full sm:max-w-[450px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Kurum Düzenle</h2>
      <p class="text-sm text-zinc-500">Kurum temel bilgilerini güncelleyin.</p>
    </header>

    <form id="form-edit-tenant" class="form grid gap-4">
        <input type="hidden" name="id" id="edit_tenant_id" />
        <div class="grid gap-2">
            <label for="edit_tenant_name">Kurum Adı</label>
            <input type="text" name="name" id="edit_tenant_name" required />
        </div>
        <div class="flex items-center gap-2 mt-2">
            <input type="checkbox" name="is_active" id="edit_tenant_active" value="1" class="w-4 h-4 text-primary border-zinc-300 rounded focus:ring-primary" />
            <label for="edit_tenant_active" class="text-sm font-medium">Bu kurum aktif mi?</label>
        </div>
    </form>

    <footer class="mt-6 flex justify-end gap-3">
      <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">İptal</button>
      <button type="button" onclick="saveTenant()" class="btn">Değişiklikleri Kaydet</button>
    </footer>

    <button type="button" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600" onclick="this.closest('dialog').close()">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
  </div>
</dialog>

<!-- Silme Onay Dialog -->
<dialog id="alert-dialog-delete" class="dialog" aria-labelledby="alert-dialog-title" aria-describedby="alert-dialog-description" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl max-w-[400px] w-full" onclick="event.stopPropagation()">
    <header class="mb-6">
      <h2 id="alert-dialog-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Emin misiniz?</h2>
      <p id="alert-dialog-description" class="text-sm text-zinc-500 mt-2">Bu işlem geri alınamaz. Bu kurumu ve kuruma bağlı tüm verileri sistemden kalıcı olarak sileceksiniz.</p>
    </header>

    <footer class="flex justify-end gap-3">
      <button class="btn-outline" onclick="document.getElementById('alert-dialog-delete').close()">İptal</button>
      <button class="btn bg-red-600 hover:bg-red-700 text-white border-none" onclick="confirmDelete()">Silmeyi Tamamla</button>
    </footer>
  </div>
</dialog>

<script>
$(document).ready(function() {
    const table = initDataTable('#tenantTable', {
        ajax: '<?php echo routeUrl("admin-kurumlar-list"); ?>',
        createdRow: function(row, data, dataIndex) {
            if (data.is_current) {
                $(row).addClass('bg-zinc-50 dark:bg-zinc-800/50');
            }
        },
        columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                    let html = `<div class="flex items-center gap-3">
                        <div class="flex flex-col">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">${data}</span>
                        </div>`;
                    
                    if (row.is_current) {
                        html += `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 uppercase tracking-wider shadow-sm">
                            <svg class="w-2.5 h-2.5 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                            Şu anki
                        </span>`;
                    } else if (row.is_mine) {
                        html += `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 shadow-sm">
                            <svg class="w-2.5 h-2.5 mr-1 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            Kurumum
                        </span>`;
                    }
                    
                    html += `</div>`;
                    return html;
                }
            },
            { 
                data: 'slug',
                className: 'text-zinc-600 dark:text-zinc-400'
            },
            { 
                data: 'user_count',
                render: function(data, type, row) {
                    return `<a href="<?php echo routeUrl('kullanicilar'); ?>?tenant_id=${row.id}" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-primary/10 hover:text-primary transition-colors font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        ${data}
                    </a>`;
                }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${row.status_class}">
                        ${row.status_label}
                    </span>`;
                }
            },
            { 
                data: 'created_at_formatted',
                className: 'text-zinc-600 dark:text-zinc-400'
            },
            { 
                data: null,
                orderable: false,
                className: 'text-right',
                render: function(data, type, row) {
                    return `
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editTenant(${row.id})" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-zinc-400 transition-colors" title="Düzenle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                            </button>
                            <button onclick="deleteTenant(${row.id})" class="p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded text-red-400 transition-colors" title="Sil">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                            </button>
                        </div>`;
                }
            }
        ],
        order: [[0, 'asc']],
        dom: '<"flex-1 overflow-auto"rt><"mt-auto border-t border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row justify-between items-center p-4 gap-4 bg-zinc-50/50 dark:bg-zinc-800/30"lip>',
        initComplete: function() {
            $('#table-preloader').fadeOut(300, function() {
                $('#table-container').fadeIn(300);
            });
        }
    });

    $('#tenantSearch').on('keyup', function() {
        table.search(this.value).draw();
    });
});

function editTenant(id) {
    $.get('<?php echo routeUrl("admin-kurum-get"); ?>', { id: id }, function(data) {
        data = JSON.parse(data);
        $('#edit_tenant_id').val(data.id);
        $('#edit_tenant_name').val(data.name);
        $('#edit_tenant_active').prop('checked', data.is_active == 1);
        document.getElementById('dialog-edit-tenant').showModal();
    });
}

function saveTenant() {
    $.post('<?php echo routeUrl("admin-kurum-guncelle"); ?>', $('#form-edit-tenant').serialize(), function(response) {
        response = JSON.parse(response);
        if (response.success) {
            showToast({ category: 'success', title: 'Başarılı', description: response.message });
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast({ category: 'error', title: 'Hata', description: response.message });
        }
    });
}

let tenantToDelete = null;

function deleteTenant(id) {
    tenantToDelete = id;
    document.getElementById('alert-dialog-delete').showModal();
}

function confirmDelete() {
    if (!tenantToDelete) return;
    
    $.post('<?php echo routeUrl("admin-kurum-sil"); ?>', { id: tenantToDelete }, function(response) {
        try {
            response = JSON.parse(response);
            if (response.success) {
                showToast({ category: 'success', title: 'Başarılı', description: response.message });
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast({ category: 'error', title: 'Hata', description: response.message });
            }
        } catch (err) {
            showToast({ category: 'error', title: 'Hata', description: 'Sunucudan geçersiz bir yanıt geldi.' });
        } finally {
            document.getElementById('alert-dialog-delete').close();
            tenantToDelete = null;
        }
    });
}
</script>

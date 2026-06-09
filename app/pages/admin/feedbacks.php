<?php
$pageTitle = 'Geri Bildirimler';
$pageSubtitle = 'Kullanıcılardan gelen öneri, hata ve talepleri yönetin';

/** @var array $feedbacks */
/** @var array $counts */
/** @var string $activeStatus */
$feedbacks = $feedbacks ?? [];
$counts = $counts ?? ['all' => 0, 'new' => 0, 'in_progress' => 0, 'resolved' => 0];
$activeStatus = $activeStatus ?? 'all';

$typeMeta = [
    'bug'        => ['label' => 'Hata',  'class' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400 border-red-200 dark:border-red-500/20'],
    'suggestion' => ['label' => 'Öneri', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-500/20'],
    'other'      => ['label' => 'Diğer', 'class' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700'],
];
$statusMeta = [
    'new'         => ['label' => 'Yeni',        'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 border-blue-200 dark:border-blue-500/20'],
    'in_progress' => ['label' => 'İnceleniyor', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-500/20'],
    'resolved'    => ['label' => 'Çözüldü',     'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20'],
];

$tabs = [
    'all'         => 'Tümü',
    'new'         => 'Yeni',
    'in_progress' => 'İnceleniyor',
    'resolved'    => 'Çözüldü',
];

// Custom-select için durum seçenekleri
$statusOptions = [];
foreach ($statusMeta as $sKey => $sVal) {
    $statusOptions[] = ['value' => $sKey, 'label' => $sVal['label']];
}

// Dialog içeriğini JS tarafında doldurmak için kayıtları hazırla
$jsFeedbacks = [];
foreach ($feedbacks as $fb) {
    $jsFeedbacks[(int)$fb['id']] = [
        'id'          => (int)$fb['id'],
        'type'        => $fb['type'],
        'status'      => $fb['status'],
        'subject'     => $fb['subject'],
        'message'     => $fb['message'],
        'admin_note'  => $fb['admin_note'] ?? '',
        'user_name'   => $fb['user_name'] ?? 'Bilinmiyor',
        'user_email'  => $fb['user_email'] ?? '',
        'tenant_name' => $fb['tenant_name'] ?? '',
        'created_at'  => !empty($fb['created_at']) ? date('d.m.Y H:i', strtotime($fb['created_at'])) : '',
        'page_url'    => $fb['page_url'] ?? '',
    ];
}
?>

<div class="p-2 md:p-4">
  <div class="flex items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Geri Bildirimler</h1>
  </div>

  <!-- Durum sekmeleri -->
  <div class="flex flex-wrap items-center gap-2 mb-6">
    <?php foreach ($tabs as $key => $label): ?>
      <a href="<?php echo routeUrl('/geri-bildirimler' . ($key === 'all' ? '' : '?status=' . $key)); ?>"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium border transition-all <?php echo $activeStatus === $key
          ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 border-zinc-900 dark:border-zinc-100'
          : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-300 border-border hover:bg-zinc-50 dark:hover:bg-zinc-800'; ?>">
        <?php echo $label; ?>
        <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full text-xs <?php echo $activeStatus === $key ? 'bg-white/20' : 'bg-zinc-100 dark:bg-zinc-800'; ?>">
          <?php echo (int)($counts[$key] ?? 0); ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($feedbacks)): ?>
    <div class="flex flex-col items-center justify-center py-24 text-center">
      <div class="size-16 flex items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Henüz geri bildirim yok</h3>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Bu filtreye uygun bir geri bildirim bulunmuyor.</p>
    </div>
  <?php else: ?>
    <div class="grid gap-4">
      <?php foreach ($feedbacks as $fb):
        $tMeta = $typeMeta[$fb['type']] ?? $typeMeta['other'];
        $sMeta = $statusMeta[$fb['status']] ?? $statusMeta['new'];
        $createdAt = !empty($fb['created_at']) ? date('d.m.Y H:i', strtotime($fb['created_at'])) : '';
      ?>
        <div class="group bg-white dark:bg-zinc-900 border border-border rounded-2xl shadow-sm p-5 cursor-pointer hover:border-zinc-300 dark:hover:border-zinc-700 hover:shadow-md transition-all"
          data-feedback-id="<?php echo (int)$fb['id']; ?>" onclick="openFeedbackDetail(<?php echo (int)$fb['id']; ?>)">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border <?php echo $tMeta['class']; ?>"><?php echo $tMeta['label']; ?></span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border <?php echo $sMeta['class']; ?>"><?php echo $sMeta['label']; ?></span>
              </div>
              <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 break-words"><?php echo htmlspecialchars($fb['subject'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1.5 break-words line-clamp-2"><?php echo htmlspecialchars($fb['message'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="flex items-center gap-1 shrink-0">
              <button type="button" onclick="event.stopPropagation(); deleteFeedback(<?php echo (int)$fb['id']; ?>)"
                class="size-9 flex items-center justify-center rounded-lg text-zinc-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" title="Sil">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
              </button>
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600 group-hover:text-zinc-400"><path d="m9 18 6-6-6-6"/></svg>
            </div>
          </div>

          <!-- Meta bilgileri -->
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-4 text-xs text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20a6 6 0 0 0-12 0"/><circle cx="12" cy="10" r="4"/></svg>
              <?php echo htmlspecialchars($fb['user_name'] ?? 'Bilinmiyor', ENT_QUOTES, 'UTF-8'); ?>
              <?php if (!empty($fb['user_email'])): ?><span class="text-zinc-400">(<?php echo htmlspecialchars($fb['user_email'], ENT_QUOTES, 'UTF-8'); ?>)</span><?php endif; ?>
            </span>
            <?php if (!empty($fb['tenant_name'])): ?>
              <span class="inline-flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/></svg>
                <?php echo htmlspecialchars($fb['tenant_name'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
            <?php endif; ?>
            <span class="inline-flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
              <?php echo $createdAt; ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Geri Bildirim Detay / Yönetim Dialogu -->
<dialog id="feedback-detail-dialog" class="dialog w-full sm:max-w-[560px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-2xl" onclick="event.stopPropagation()">
    <div class="flex items-start justify-between gap-4 mb-4">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2 mb-2" id="fb-detail-badges"></div>
        <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 break-words" id="fb-detail-subject"></h2>
      </div>
      <button type="button" onclick="document.getElementById('feedback-detail-dialog').close()"
        class="size-8 flex items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-zinc-500 shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
      </button>
    </div>

    <!-- Mesaj -->
    <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-border p-4 text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line break-words max-h-48 overflow-y-auto" id="fb-detail-message"></div>

    <!-- Meta -->
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-4 text-xs text-zinc-500 dark:text-zinc-400" id="fb-detail-meta"></div>

    <!-- Yönetim -->
    <div class="border-t border-border mt-5 pt-5 space-y-4">
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Durum</label>
        <?php echo renderCustomSelect('fb-detail-status', 'status', $statusOptions, 'new', 'w-full'); ?>
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 inline-flex items-center gap-1.5">
          Yanıt
          <span class="inline-flex items-center gap-1 text-[10px] font-medium text-amber-600 dark:text-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            Kullanıcıya görünür
          </span>
        </label>
        <textarea id="fb-detail-note" rows="3"
          class="w-full px-3 py-2 rounded-lg border border-border bg-white dark:bg-zinc-900 text-sm resize-none"
          placeholder="Kullanıcıya iletilecek yanıtı yazın..."></textarea>
      </div>
    </div>

    <div class="flex items-center justify-between gap-3 pt-5">
      <button type="button" onclick="deleteFeedback(getCurrentFeedbackId(), true)"
        class="inline-flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /></svg>
        Sil
      </button>
      <div class="flex items-center gap-3">
        <button type="button" onclick="document.getElementById('feedback-detail-dialog').close()"
          class="px-4 py-2.5 rounded-xl text-sm font-medium border border-border hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">Kapat</button>
        <button type="button" id="fb-detail-save" onclick="saveFeedbackDetail()"
          class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 shadow-lg shadow-zinc-900/20 hover:opacity-90 transition-opacity disabled:opacity-50">Kaydet</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Silme Onay Dialogu -->
<dialog id="feedback-delete-dialog" class="dialog" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-2xl max-w-[400px] w-full" onclick="event.stopPropagation()">
    <div class="flex items-start gap-3 mb-5">
      <div class="size-10 shrink-0 flex items-center justify-center rounded-full bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /></svg>
      </div>
      <div>
        <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Geri bildirimi sil</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Bu işlem geri alınamaz. Geri bildirim kalıcı olarak silinecek.</p>
      </div>
    </div>
    <div class="flex justify-end gap-3">
      <button type="button" onclick="document.getElementById('feedback-delete-dialog').close()"
        class="px-4 py-2.5 rounded-xl text-sm font-medium border border-border hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">İptal</button>
      <button type="button" id="feedback-delete-confirm-btn" onclick="confirmFeedbackDelete()"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-red-600 hover:bg-red-700 text-white transition-colors disabled:opacity-50">Sil</button>
    </div>
  </div>
</dialog>

<script>
  const FB_ROUTES = {
    update: '<?php echo routeUrl('/geri-bildirim-guncelle'); ?>',
    del: '<?php echo routeUrl('/geri-bildirim-sil'); ?>',
  };
  const FB_DATA = <?php echo json_encode($jsFeedbacks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
  const FB_TYPE_META = <?php echo json_encode($typeMeta, JSON_UNESCAPED_UNICODE); ?>;
  const FB_STATUS_META = <?php echo json_encode($statusMeta, JSON_UNESCAPED_UNICODE); ?>;

  let _fbCurrentId = null;
  function getCurrentFeedbackId() { return _fbCurrentId; }

  function fbBadge(meta) {
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ' + meta.class + '">' + meta.label + '</span>';
  }
  function fbEsc(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function openFeedbackDetail(id) {
    const fb = FB_DATA[id];
    if (!fb) return;
    _fbCurrentId = id;

    const tMeta = FB_TYPE_META[fb.type] || FB_TYPE_META.other;
    const sMeta = FB_STATUS_META[fb.status] || FB_STATUS_META.new;
    document.getElementById('fb-detail-badges').innerHTML = fbBadge(tMeta) + fbBadge(sMeta);
    document.getElementById('fb-detail-subject').textContent = fb.subject;
    document.getElementById('fb-detail-message').textContent = fb.message;

    let meta = '';
    meta += '<span class="inline-flex items-center gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20a6 6 0 0 0-12 0"/><circle cx="12" cy="10" r="4"/></svg>' + fbEsc(fb.user_name) + (fb.user_email ? ' <span class="text-zinc-400">(' + fbEsc(fb.user_email) + ')</span>' : '') + '</span>';
    if (fb.tenant_name) {
      meta += '<span class="inline-flex items-center gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/></svg>' + fbEsc(fb.tenant_name) + '</span>';
    }
    meta += '<span class="inline-flex items-center gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>' + fbEsc(fb.created_at) + '</span>';
    if (fb.page_url) {
      meta += '<a href="' + fbEsc(fb.page_url) + '" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 hover:text-zinc-700 dark:hover:text-zinc-200 truncate max-w-[260px]"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg><span class="truncate">' + fbEsc(fb.page_url) + '</span></a>';
    }
    document.getElementById('fb-detail-meta').innerHTML = meta;

    setCustomSelectValue('fb-detail-status', fb.status);
    document.getElementById('fb-detail-note').value = fb.admin_note || '';

    document.getElementById('feedback-detail-dialog').showModal();
  }

  // Custom-select bileşenine JS'ten değer atar (etiket + gizli input + seçili işaret)
  function setCustomSelectValue(id, value) {
    const component = document.getElementById(id);
    if (!component) return;
    const option = component.querySelector('[role="option"][data-value="' + value + '"]');
    if (!option) return;
    const label = option.querySelector('.option-label')?.textContent.trim() || '';
    component.querySelector('input[type="hidden"]').value = value;
    component.querySelector('.selected-label').textContent = label;
    component.querySelectorAll('[role="option"]').forEach((o) => {
      o.classList.remove('selected', 'bg-zinc-50', 'dark:bg-zinc-800', 'text-zinc-900', 'dark:text-white', 'font-bold');
      o.querySelector('.check-icon')?.classList.add('hidden');
    });
    option.classList.add('selected', 'bg-zinc-50', 'dark:bg-zinc-800', 'text-zinc-900', 'dark:text-white', 'font-bold');
    option.querySelector('.check-icon')?.classList.remove('hidden');
  }

  async function fbPost(url, payload) {
    const fd = new FormData();
    Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
    const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    return res.json();
  }

  // Durum + not birlikte kaydedilir
  async function saveFeedbackDetail() {
    const id = _fbCurrentId;
    if (!id) return;
    const status = document.getElementById('fb-detail-status-value').value;
    const note = document.getElementById('fb-detail-note').value;
    const btn = document.getElementById('fb-detail-save');
    btn.disabled = true;
    const original = btn.textContent;
    btn.textContent = 'Kaydediliyor...';
    try {
      const data = await fbPost(FB_ROUTES.update, { id, status, note });
      if (data.success) {
        showToast({ category: 'success', title: 'Kaydedildi', description: data.message || 'Geri bildirim güncellendi.' });
        setTimeout(() => window.location.reload(), 600);
      } else {
        showToast({ category: 'error', title: 'Hata', description: data.error || data.message || 'İşlem başarısız.' });
        btn.disabled = false;
        btn.textContent = original;
      }
    } catch (e) {
      showToast({ category: 'error', title: 'Hata', description: 'İşlem başarısız oldu.' });
      btn.disabled = false;
      btn.textContent = original;
    }
  }

  let _fbDeleteId = null;
  let _fbDeleteFromDialog = false;

  // Onay dialogunu açar (native confirm yerine)
  function deleteFeedback(id, fromDialog) {
    if (!id) return;
    _fbDeleteId = id;
    _fbDeleteFromDialog = !!fromDialog;
    document.getElementById('feedback-delete-dialog').showModal();
  }

  async function confirmFeedbackDelete() {
    const id = _fbDeleteId;
    if (!id) return;
    const btn = document.getElementById('feedback-delete-confirm-btn');
    btn.disabled = true;
    const original = btn.textContent;
    btn.textContent = 'Siliniyor...';
    try {
      const data = await fbPost(FB_ROUTES.del, { id });
      document.getElementById('feedback-delete-dialog').close();
      if (data.success) {
        if (_fbDeleteFromDialog) document.getElementById('feedback-detail-dialog').close();
        const card = document.querySelector('[data-feedback-id="' + id + '"]');
        if (card) card.remove();
        showToast({ category: 'success', title: 'Silindi', description: data.message });
      } else {
        showToast({ category: 'error', title: 'Hata', description: data.error || data.message });
      }
    } catch (e) {
      document.getElementById('feedback-delete-dialog').close();
      showToast({ category: 'error', title: 'Hata', description: 'Silme işlemi başarısız oldu.' });
    } finally {
      btn.disabled = false;
      btn.textContent = original;
      _fbDeleteId = null;
      _fbDeleteFromDialog = false;
    }
  }
</script>

<?php
/**
 * =========================================================================
 *  Geri Bildirim Modalı
 * =========================================================================
 *  Kullanıcı menüsündeki "Geri Bildirim" bağlantısıyla açılır
 *  (openFeedbackDialog() / #feedback-dialog).
 *  Oturum açmış her kullanıcı erişebilir.
 *  Gönderim: POST /geri-bildirim-gonder (CSRF başlığı csrf.js tarafından eklenir).
 */
?>
<!-- Geri Bildirim Modalı -->
<dialog id="feedback-dialog" class="dialog w-full sm:max-w-[460px]" onclick="if (event.target === this) this.close()">
  <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-2xl" onclick="event.stopPropagation()">
    <div class="flex items-start justify-between mb-5">
      <div>
        <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Geri Bildirim Gönder</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Görüş, öneri ve hatalarınızı bizimle paylaşın.</p>
      </div>
      <button type="button" onclick="document.getElementById('feedback-dialog').close()"
        class="size-8 flex items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-zinc-500 shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>
    </div>

    <form id="feedback-form" class="space-y-4">
      <input type="hidden" name="page_url" id="feedback-page-url" value="">

      <!-- Tür seçimi -->
      <div class="space-y-2">
        <label class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Tür</label>
        <div class="grid grid-cols-3 gap-2" id="feedback-type-group">
          <label class="cursor-pointer">
            <input type="radio" name="type" value="suggestion" class="peer sr-only" checked>
            <div class="flex flex-col items-center gap-1 rounded-xl border border-border py-3 text-zinc-600 dark:text-zinc-300 peer-checked:border-zinc-900 dark:peer-checked:border-zinc-100 peer-checked:bg-zinc-900/5 dark:peer-checked:bg-zinc-100/10 peer-checked:text-zinc-900 dark:peer-checked:text-zinc-100 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>
              <span class="text-xs font-medium">Öneri</span>
            </div>
          </label>
          <label class="cursor-pointer">
            <input type="radio" name="type" value="bug" class="peer sr-only">
            <div class="flex flex-col items-center gap-1 rounded-xl border border-border py-3 text-zinc-600 dark:text-zinc-300 peer-checked:border-red-500 peer-checked:bg-red-500/5 peer-checked:text-red-600 dark:peer-checked:text-red-400 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8 2 1.88 1.88"/><path d="M14.12 3.88 16 2"/><path d="M9 7.13v-1a3.003 3.003 0 1 1 6 0v1"/><path d="M12 20c-3.3 0-6-2.7-6-6v-3a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v3c0 3.3-2.7 6-6 6"/><path d="M12 20v-9"/><path d="M6.53 9C4.6 8.8 3 7.1 3 5"/><path d="M6 13H2"/><path d="M3 21c0-2.1 1.7-3.9 3.8-4"/><path d="M20.97 5c0 2.1-1.6 3.8-3.5 4"/><path d="M22 13h-4"/><path d="M17.2 17c2.1.1 3.8 1.9 3.8 4"/></svg>
              <span class="text-xs font-medium">Hata</span>
            </div>
          </label>
          <label class="cursor-pointer">
            <input type="radio" name="type" value="other" class="peer sr-only">
            <div class="flex flex-col items-center gap-1 rounded-xl border border-border py-3 text-zinc-600 dark:text-zinc-300 peer-checked:border-zinc-900 dark:peer-checked:border-zinc-100 peer-checked:bg-zinc-900/5 dark:peer-checked:bg-zinc-100/10 peer-checked:text-zinc-900 dark:peer-checked:text-zinc-100 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
              <span class="text-xs font-medium">Diğer</span>
            </div>
          </label>
        </div>
      </div>

      <div class="space-y-2">
        <label for="feedback-subject" class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Konu</label>
        <input type="text" id="feedback-subject" name="subject" maxlength="255" required
          class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-border rounded-xl text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 outline-none transition-all"
          placeholder="Kısaca özetleyin">
      </div>

      <div class="space-y-2">
        <label for="feedback-message" class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Mesaj</label>
        <textarea id="feedback-message" name="message" rows="4" required
          class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-border rounded-xl text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 outline-none transition-all resize-none"
          placeholder="Düşüncelerinizi detaylıca anlatın..."></textarea>
      </div>

      <div class="flex items-center gap-3 pt-1">
        <button type="button" onclick="document.getElementById('feedback-dialog').close()"
          class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium border border-border hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">Vazgeç</button>
        <button type="submit" id="feedback-submit-btn"
          class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 shadow-lg shadow-zinc-900/20 hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed">
          Gönder
        </button>
      </div>
    </form>
  </div>
</dialog>

<script>
  // Kullanıcı menüsündeki bağlantıdan çağrılır; menüyü kapatıp modalı açar.
  window.openFeedbackDialog = function () {
    document.querySelectorAll('details[data-user-menu]').forEach((d) => d.removeAttribute('open'));
    const dlg = document.getElementById('feedback-dialog');
    if (dlg) dlg.showModal();
  };

  (() => {
    const form = document.getElementById('feedback-form');
    const dialog = document.getElementById('feedback-dialog');
    const submitBtn = document.getElementById('feedback-submit-btn');
    const pageUrlInput = document.getElementById('feedback-page-url');
    if (!form) return;

    dialog.addEventListener('close', () => {
      form.reset();
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      pageUrlInput.value = window.location.href;

      submitBtn.disabled = true;
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Gönderiliyor...';

      try {
        const res = await fetch('<?php echo routeUrl('/geri-bildirim-gonder'); ?>', {
          method: 'POST',
          body: new FormData(form),
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.success) {
          dialog.close();
          showToast({ category: 'success', title: 'Teşekkürler!', description: data.message });
        } else {
          showToast({ category: 'error', title: 'Hata', description: data.error || data.message || 'Bir sorun oluştu.' });
        }
      } catch (err) {
        showToast({ category: 'error', title: 'Hata', description: 'Geri bildirim gönderilemedi. Lütfen tekrar deneyin.' });
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });
  })();
</script>

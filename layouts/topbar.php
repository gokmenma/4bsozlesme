<?php global $pageTitle, $pageSubtitle; ?>
<header class="app-topbar flex items-center justify-between border-b border-border bg-background/95 px-3 backdrop-blur md:px-5">
  <div class="flex min-w-0 items-center gap-2">
    <button
      type="button"
      class="btn-outline inline-flex size-9 shrink-0 items-center justify-center p-0"
      aria-label="Sidebar aç/kapat"
      title="Sidebar aç/kapat"
      onclick="document.dispatchEvent(new CustomEvent('basecoat:sidebar'))"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect width="18" height="18" x="3" y="3" rx="2" />
        <path d="M9 3v18" />
      </svg>
    </button>

    <div class="hidden min-w-0 flex-col leading-tight sm:flex">
      <span class="truncate text-sm font-medium pageTitle"><?php echo $pageTitle ?? "Sözleşme Takip" ?></span>
      <span class="truncate text-xs text-muted-foreground pageSubtitle"><?php echo $pageSubtitle ?? "Hoş geldiniz" ?></span>
    </div>
  </div>

  <div class="flex items-center gap-2">
    <button
      type="button"
      class="btn-outline hidden size-9 shrink-0 items-center justify-center p-0 sm:inline-flex"
      aria-label="Arama"
      title="Arama"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m21 21-4.34-4.34" />
        <circle cx="11" cy="11" r="8" />
      </svg>
    </button>

    <details class="relative" data-theme-menu>
      <summary class="no-marker btn-outline inline-flex size-9 cursor-pointer items-center justify-center p-0" aria-label="Tema seçimi" title="Tema seçimi">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="4" />
          <path d="M12 2v2" />
          <path d="M12 20v2" />
          <path d="m4.93 4.93 1.41 1.41" />
          <path d="m17.66 17.66 1.41 1.41" />
          <path d="M2 12h2" />
          <path d="M20 12h2" />
          <path d="m6.34 17.66-1.41 1.41" />
          <path d="m19.07 4.93-1.41 1.41" />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M20.99 11.65A9 9 0 1 1 12.35 3a7 7 0 0 0 8.64 8.65Z" />
        </svg>
      </summary>

      <div class="absolute right-0 z-50 mt-2 w-40 rounded-md border border-border bg-white dark:bg-zinc-900 p-1 shadow-lg">
        <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-2 text-left text-sm hover:bg-muted" data-theme-value="light">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2" />
            <path d="M12 20v2" />
            <path d="M4 12H2" />
            <path d="M22 12h-2" />
          </svg>
          Açık
        </button>
        <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-2 text-left text-sm hover:bg-muted" data-theme-value="dark">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20.99 11.65A9 9 0 1 1 12.35 3a7 7 0 0 0 8.64 8.65Z" />
          </svg>
          Koyu
        </button>
        <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-2 text-left text-sm hover:bg-muted" data-theme-value="system">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="18" height="12" x="3" y="4" rx="2" />
            <path d="M8 20h8" />
            <path d="M12 16v4" />
          </svg>
          Sistem
        </button>
      </div>
    </details>

    <button
      type="button"
      class="btn-outline inline-flex size-9 shrink-0 items-center justify-center p-0"
      aria-label="Bildirimler"
      title="Bildirimler"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10.27 21a2 2 0 0 0 3.46 0" />
        <path d="M14.5 4.1a5 5 0 0 0-7 4.6c0 1.8-.6 3.4-1.7 4.8L4 16h16l-1.8-2.5a7.8 7.8 0 0 1-1.7-4.8 5 5 0 0 0-2-4.6Z" />
      </svg>
    </button>
  </div>
</header>

<script>
  (() => {
    const media = window.matchMedia('(prefers-color-scheme: dark)');

    const applyTheme = (theme) => {
      const selectedTheme = theme || localStorage.getItem('theme') || 'system';
      const useDark = selectedTheme === 'dark' || (selectedTheme === 'system' && media.matches);

      document.documentElement.classList.toggle('dark', useDark);
      document.documentElement.dataset.theme = selectedTheme;
      localStorage.setItem('theme', selectedTheme);
    };

    document.querySelectorAll('[data-theme-value]').forEach((button) => {
      button.addEventListener('click', () => {
        applyTheme(button.dataset.themeValue);
        button.closest('details')?.removeAttribute('open');
      });
    });

    media.addEventListener('change', () => {
      if ((localStorage.getItem('theme') || 'system') === 'system') {
        applyTheme('system');
      }
    });

    document.addEventListener('click', (event) => {
      document.querySelectorAll('details[data-theme-menu], details[data-user-menu]').forEach((details) => {
        if (!details.contains(event.target)) {
          details.removeAttribute('open');
        }
      });
    });
  })();
</script>

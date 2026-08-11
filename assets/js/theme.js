(() => {
    const media = window.matchMedia('(prefers-color-scheme: dark)');

    const applyTheme = (theme, persist = true) => {
        const selectedTheme = theme || localStorage.getItem('theme') || 'system';
        const useDark = selectedTheme === 'dark' || (selectedTheme === 'system' && media.matches);
        const root = document.documentElement;

        root.classList.add('theme-switching');
        root.classList.toggle('dark', useDark);
        root.dataset.theme = selectedTheme;
        root.style.colorScheme = useDark ? 'dark' : 'light';

        if (persist) {
            localStorage.setItem('theme', selectedTheme);
        }

        requestAnimationFrame(() => requestAnimationFrame(() => {
            root.classList.remove('theme-switching');
        }));
    };

    window.setAppTheme = applyTheme;
    window.toggleAppTheme = () => {
        applyTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
    };

    applyTheme(localStorage.getItem('theme') || 'system', false);

    media.addEventListener?.('change', () => {
        if ((localStorage.getItem('theme') || 'system') === 'system') {
            applyTheme('system', false);
        }
    });
})();

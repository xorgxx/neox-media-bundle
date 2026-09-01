let cssLoaded = false;

export function ensureCss() {
    if (cssLoaded) return Promise.resolve();
    if (document.querySelector('link[data-neox-media-css]')) {
        cssLoaded = true;
        return Promise.resolve();
    }
    return new Promise((resolve) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = '/bundles/neoxmedia/media-manager.css';
        link.dataset.neoxMediaCss = '1';
        link.onload = () => { cssLoaded = true; resolve(); };
        link.onerror = () => { cssLoaded = true; resolve(); };
        document.head.appendChild(link);
    });
}

export function escapeHtml(str) {
    const el = document.createElement('div');
    el.textContent = str ?? '';
    return el.innerHTML;
}

export function formatFileSize(bytes) {
    if (!bytes) return '';
    const units = ['o', 'Ko', 'Mo', 'Go'];
    let size = bytes, index = 0;
    while (size >= 1024 && index < units.length - 1) {
        size /= 1024;
        index++;
    }
    return `${size.toFixed(index ? 1 : 0)} ${units[index]}`;
}

export function isDarkTheme() {
    return localStorage.getItem('neox-media-theme') === 'dark';
}

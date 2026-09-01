let swalPromise = null;
let stylesInjected = false;

const SWAL_CSS = `
.neox-media-swal {
    --bg: #f0f2f5; --surface: #ffffff; --surface-2: #f8f9fb; --surface-hover: #eef1f5;
    --border: #e2e6ec; --border-light: #eef1f5; --text: #0f172a; --text-muted: #64748b;
    --accent: #6366f1; --accent-hover: #4f46e5; --accent-soft: #eef2ff; --accent-contrast: #fff;
    --input-bg: #fff; --input-border: #d8dde5; --input-focus: #6366f1;
    --preview-bg: #f1f3f7; --overlay-bg: rgba(248,249,251,.8);
    --shadow-sm: 0 1px 2px rgba(0,0,0,.04); --shadow-md: 0 4px 12px rgba(0,0,0,.06);
    --shadow-xl: 0 24px 70px rgba(0,0,0,.14);
    --radius-sm: 8px; --radius-md: 10px; --radius-xl: 18px;
    --transition: .18s cubic-bezier(.4,0,.2,1);
    font-family: 'Inter', -apple-system, system-ui, sans-serif; color: var(--text);
}
.neox-media-swal.neox-media-dark {
    --bg: #0b0e14; --surface: #141821; --surface-2: #1a1f2b; --surface-hover: #1e2433;
    --border: #252b38; --border-light: #1e2433; --text: #e2e8f0; --text-muted: #7a8499;
    --accent: #818cf8; --accent-hover: #6366f1; --accent-soft: #1e1b3a; --accent-contrast: #0b0e14;
    --input-bg: #1a1f2b; --input-border: #2d3548; --preview-bg: #1a1f2b;
    --overlay-bg: rgba(11,14,20,.8); --shadow-xl: 0 24px 70px rgba(0,0,0,.5);
}
.neox-media-swal .swal2-popup {
    background: var(--surface) !important; color: var(--text) !important;
    border: 1px solid var(--border) !important; border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-xl) !important; padding: 0 !important;
    font-family: 'Inter', system-ui, sans-serif !important;
}
.neox-media-swal .swal2-title {
    font-size: 18px !important; font-weight: 700 !important; padding: 16px 20px !important;
    margin: 0 !important; text-align: left !important; border-bottom: 1px solid var(--border);
    color: var(--text) !important;
}
.neox-media-swal .swal2-html-container { padding: 0 !important; margin: 0 !important; }
.neox-media-swal .swal2-actions {
    padding: 14px 20px !important; margin: 0 !important; gap: 8px;
    border-top: 1px solid var(--border); justify-content: flex-end !important;
}
.neox-media-swal .swal2-confirm-button {
    background: var(--accent) !important; color: var(--accent-contrast) !important;
    font-size: 13px !important; font-weight: 600 !important; border-radius: var(--radius-sm) !important;
    padding: 8px 22px !important; border: none !important; box-shadow: none !important;
}
.neox-media-swal .swal2-cancel-button {
    background: var(--surface-2) !important; color: var(--text) !important;
    font-size: 13px !important; font-weight: 500 !important; border-radius: var(--radius-sm) !important;
    padding: 8px 18px !important; border: 1px solid var(--border) !important; box-shadow: none !important;
}
.neox-media-swal .swal2-toast {
    background: var(--surface) !important; color: var(--text) !important;
    border: 1px solid var(--border) !important; border-radius: var(--radius-md) !important;
    box-shadow: var(--shadow-md) !important;
}
@keyframes neox-media-spin { to { transform: rotate(360deg); } }
`;

function injectSwalStyles() {
    if (stylesInjected) return;
    if (document.getElementById('neox-media-swal-styles')) { stylesInjected = true; return; }
    const s = document.createElement('style');
    s.id = 'neox-media-swal-styles';
    s.textContent = SWAL_CSS;
    document.head.appendChild(s);
    stylesInjected = true;
}

export function loadSwal() {
    if (swalPromise) return swalPromise;
    if (window.Swal) { injectSwalStyles(); return Promise.resolve(window.Swal); }
    swalPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        script.onload = () => { injectSwalStyles(); resolve(window.Swal); };
        script.onerror = () => { swalPromise = null; reject(new Error('SweetAlert2 load failed')); };
        document.head.appendChild(script);
    });
    return swalPromise;
}

export function swalDarkClass() {
    return localStorage.getItem('neox-media-theme') === 'dark' ? 'neox-media-swal neox-media-dark' : 'neox-media-swal';
}

export async function swalToast(icon, title, timer = 3000) {
    const Swal = await loadSwal();
    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon,
        title,
        showConfirmButton: false,
        timer,
        timerProgressBar: true,
        customClass: { container: swalDarkClass(), popup: 'swal2-toast' },
    });
}

export async function swalConfirm(options = {}) {
    const Swal = await loadSwal();
    const result = await Swal.fire({
        showCancelButton: true,
        confirmButtonText: 'Confirmer',
        cancelButtonText: 'Annuler',
        reverseButtons: true,
        customClass: { container: swalDarkClass() },
        ...options,
    });
    return result.isConfirmed;
}

import { Controller } from '@hotwired/stimulus';
import { loadSwal, swalDarkClass, swalToast } from '../helpers/swal.js';
import { ensureCss, escapeHtml, formatFileSize, isDarkTheme } from '../helpers/utils.js';

const DIALOG_CSS = `
.neox-media-dialog {
    --surface: #ffffff; --surface-2: #f8f9fb; --surface-hover: #eef1f5;
    --border: #e2e6ec; --text: #0f172a; --text-muted: #64748b;
    --accent: #6366f1; --accent-hover: #4f46e5; --accent-soft: #eef2ff; --accent-contrast: #fff;
    --input-bg: #fff; --input-border: #d8dde5; --input-focus: #6366f1;
    --preview-bg: #f1f3f7; --overlay-bg: rgba(248,249,251,.8);
    --shadow-sm: 0 1px 2px rgba(0,0,0,.04); --shadow-md: 0 4px 12px rgba(0,0,0,.06);
    --shadow-xl: 0 24px 70px rgba(0,0,0,.14);
    --radius-sm: 8px; --radius-md: 10px; --radius-xl: 18px;
    --transition: .18s cubic-bezier(.4,0,.2,1);
    font-family: 'Inter', -apple-system, system-ui, sans-serif; color: var(--text);
    border: 1px solid var(--border) !important; border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-xl) !important; padding: 0 !important;
    width: min(900px, 94vw) !important; max-height: 88vh !important;
    background: var(--surface) !important; color: var(--text) !important;
}
.neox-media-dialog.neox-media-dark {
    --surface: #141821; --surface-2: #1a1f2b; --surface-hover: #1e2433;
    --border: #252b38; --text: #e2e8f0; --text-muted: #7a8499;
    --accent: #818cf8; --accent-hover: #6366f1; --accent-soft: #1e1b3a; --accent-contrast: #0b0e14;
    --input-bg: #1a1f2b; --input-border: #2d3548; --preview-bg: #1a1f2b;
    --overlay-bg: rgba(11,14,20,.8); --shadow-xl: 0 24px 70px rgba(0,0,0,.5);
}
.neox-media-dialog::backdrop { background: rgba(0,0,0,.5); backdrop-filter: blur(4px); }
.neox-media-dialog[hidden] { display: none !important; }
.neox-media-dialog-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--border);
}
.neox-media-dialog-header strong { font-size: 18px; font-weight: 700; }
.neox-media-dialog-close {
    width: 32px; height: 32px; border-radius: 50%; background: var(--surface-2);
    color: var(--text-muted); font-size: 20px; line-height: 1; display: grid; place-items: center;
    border: 1px solid var(--border); cursor: pointer; transition: var(--transition);
}
.neox-media-dialog-close:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
.neox-media-dialog-body { padding: 16px 20px; max-height: calc(88vh - 130px); overflow-y: auto; }
.neox-media-dialog-footer {
    display: flex; justify-content: flex-end; gap: 8px;
    padding: 14px 20px; border-top: 1px solid var(--border);
}
.neox-media-dialog-cancel {
    background: var(--surface-2); color: var(--text); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 8px 18px; font-size: 13px; font-weight: 500;
    cursor: pointer; transition: var(--transition);
}
.neox-media-dialog-cancel:hover { background: var(--surface-hover); }
.neox-media-dialog-confirm {
    background: var(--accent); color: var(--accent-contrast); border: none;
    border-radius: var(--radius-sm); padding: 8px 22px; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: var(--transition);
}
.neox-media-dialog-confirm:hover { background: var(--accent-hover); }
.neox-media-dialog .neox-media-picker-modal {
    width: auto !important; max-height: none !important; overflow: visible !important;
    border: 0 !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important;
}
.neox-media-dialog .neox-media-picker-tools {
    display: flex !important; gap: 8px; align-items: center; flex-wrap: wrap;
    padding: 0 0 14px 0 !important; border-bottom: 1px solid var(--border); margin-bottom: 14px;
}
.neox-media-dialog .neox-media-picker-tools input[type="search"] { flex: 1; min-width: 120px; }
.neox-media-dialog .neox-media-picker-tools input,
.neox-media-dialog .neox-media-picker-tools select {
    border: 1px solid var(--input-border); border-radius: var(--radius-sm); padding: 9px 12px;
    background: var(--input-bg); color: var(--text); font-size: 13px; outline: none;
}
.neox-media-dialog .neox-media-picker-tools select {
    cursor: pointer; appearance: none; -webkit-appearance: none; padding-right: 30px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
}
.neox-media-dialog .neox-media-picker-upload-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--accent) !important; color: var(--accent-contrast) !important;
    border: none !important; border-radius: var(--radius-sm) !important;
    padding: 8px 14px !important; font-size: 13px !important; font-weight: 600 !important;
    cursor: pointer; transition: var(--transition); white-space: nowrap;
}
.neox-media-dialog .neox-media-picker-link-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--surface-2) !important; color: var(--text) !important;
    border: 1px solid var(--border) !important; border-radius: var(--radius-sm) !important;
    padding: 8px 14px !important; font-size: 13px !important; font-weight: 500 !important;
    cursor: pointer; transition: var(--transition); white-space: nowrap;
}
.neox-media-dialog .neox-media-picker-link-row {
    display: flex; gap: 8px; padding: 10px 0 0 0; margin-bottom: 10px;
}
.neox-media-dialog .neox-media-picker-link-row[hidden] { display: none !important; }
.neox-media-dialog .neox-media-picker-link-row input {
    flex: 1; border: 1px solid var(--input-border); border-radius: var(--radius-sm);
    padding: 9px 12px; background: var(--input-bg); color: var(--text); font-size: 13px; outline: none;
}
.neox-media-dialog .neox-media-picker-link-row button {
    background: var(--accent) !important; color: var(--accent-contrast) !important;
    border: none !important; border-radius: var(--radius-sm) !important;
    padding: 8px 16px !important; font-size: 13px !important; font-weight: 600 !important;
    cursor: pointer; white-space: nowrap;
}
.neox-media-dialog .neox-media-grid-wrapper { position: relative; min-height: 300px; }
.neox-media-dialog .neox-media-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;
    max-height: 50vh; overflow-y: auto; padding: 4px;
}
.neox-media-dialog .neox-media-card {
    border: 1px solid var(--border); background: var(--surface); border-radius: var(--radius-md);
    padding: 0; overflow: hidden; text-align: left; transition: var(--transition);
    box-shadow: var(--shadow-sm); cursor: pointer;
}
.neox-media-dialog .neox-media-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.neox-media-dialog .neox-media-card.selected { outline: 2px solid var(--accent); box-shadow: 0 0 0 4px var(--accent-soft); }
.neox-media-dialog .neox-media-card-preview { aspect-ratio: 1; background: var(--preview-bg); display: grid; place-items: center; overflow: hidden; }
.neox-media-dialog .neox-media-card-preview img { width: 100%; height: 100%; object-fit: cover; }
.neox-media-dialog .neox-media-icon { font-size: 38px; opacity: .5; }
.neox-media-dialog .neox-media-card-body { padding: 10px 12px; display: grid; gap: 3px; }
.neox-media-dialog .neox-media-card-body strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; font-weight: 600; }
.neox-media-dialog .neox-media-card-body small { color: var(--text-muted); font-size: 11px; }
.neox-media-dialog .neox-media-overlay {
    position: absolute; inset: 0; background: var(--overlay-bg); backdrop-filter: blur(4px);
    display: grid; place-items: center; z-index: 10;
}
.neox-media-dialog .neox-media-overlay[hidden] { display: none !important; }
.neox-media-dialog .neox-media-spinner {
    width: 32px; height: 32px; border: 3px solid var(--border);
    border-top-color: var(--accent); border-radius: 50%; animation: neox-media-spin .65s linear infinite;
}
`;

let dialogStylesInjected = false;

function injectDialogStyles() {
    if (dialogStylesInjected) return;
    if (document.getElementById('neox-media-dialog-styles')) { dialogStylesInjected = true; return; }
    const s = document.createElement('style');
    s.id = 'neox-media-dialog-styles';
    s.textContent = DIALOG_CSS;
    document.head.appendChild(s);
    dialogStylesInjected = true;
}

export default class extends Controller {
    static targets = ['value', 'label', 'preview', 'zone', 'empty', 'loader', 'loaderText', 'fileInput', 'count', 'countText', 'removeAllBtn'];
    static values = {
        url: String,
        uploadUrl: String,
        embedUrl: String,
        multiple: { type: Boolean, default: false },
        mode: { type: String, default: 'widget' }
    };

    connect() {
        const theme = localStorage.getItem('neox-media-theme') || 'light';
        this.element.dataset.theme = theme;
        this._pickerHtml = null;
        this._htmlPromise = null;
        this._gridData = null;
        this._gridPromise = null;

        loadSwal();
        ensureCss();
        injectDialogStyles();
        this._htmlPromise = this._fetchPickerHtml();
        this._gridPromise = this._fetchGridData();

        if (this.valueTarget.value) {
            this._loadPreview(this.valueTarget.value);
        }
    }

    dragOver(event) {
        event.preventDefault();
        if (this.hasZoneTarget) this.zoneTarget.classList.add('dragover');
    }

    dragLeave(event) {
        event.preventDefault();
        if (this.hasZoneTarget) this.zoneTarget.classList.remove('dragover');
    }

    async drop(event) {
        event.preventDefault();
        if (this.hasZoneTarget) this.zoneTarget.classList.remove('dragover');
        const files = event.dataTransfer?.files;
        if (files && files.length > 0) {
            this._showLoader('Upload en cours...');
            await this._uploadFiles(files, null, null);
            this._hideLoader();
            if (this.hasPreviewTarget) {
                this._loadPreview(this.valueTarget.value);
            }
        }
    }

    triggerUpload(event) {
        event?.preventDefault();
        if (this.hasFileInputTarget) {
            this.fileInputTarget.click();
        }
    }

    async onFileInputChange(event) {
        const files = event.target.files;
        if (files && files.length > 0) {
            this._showLoader('Upload en cours...');
            await this._uploadFiles(files, null, null);
            this._hideLoader();
            if (this.hasPreviewTarget) {
                this._loadPreview(this.valueTarget.value);
            }
        }
        event.target.value = '';
    }

    removeAll(event) {
        event?.preventDefault();
        this.valueTarget.value = '';
        this.valueTarget.dispatchEvent(new Event('change', { bubbles: true }));
        if (this.hasPreviewTarget) {
            this._loadPreview('');
        }
        this.dispatch('changed', { detail: { ids: [] } });
    }

    _showLoader(text = 'Chargement...') {
        if (this.hasLoaderTarget) {
            this.loaderTarget.hidden = false;
        }
        if (this.hasLoaderTextTarget) {
            this.loaderTextTarget.textContent = text;
        }
    }

    _hideLoader() {
        if (this.hasLoaderTarget) {
            this.loaderTarget.hidden = true;
        }
    }

    async _fetchPickerHtml() {
        try {
            const response = await fetch(this.urlValue, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            this._pickerHtml = await response.text();
        } catch (err) {
            console.error('neox-media-picker: preload failed', err);
        }
    }

    async _fetchGridData() {
        try {
            const params = new URLSearchParams();
            params.set('limit', '60');
            const response = await fetch(`${this.urlValue.replace('/picker', '/api')}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.ok) {
                this._gridData = await response.json();
            }
        } catch (err) {
            console.error('neox-media-picker: grid preload failed', err);
        }
    }

    async open(event) {
        event?.preventDefault();

        await ensureCss();
        await this._htmlPromise;

        const existing = document.getElementById('neox-media-picker-dialog');
        if (existing) existing.remove();

        const { dialog, body, confirmBtn } = this._buildDialog();
        const { grid, overlay, searchInput, filterSelect } = this._queryPickerElements(body);
        const selectedMedia = { ids: new Set(), items: new Map() };

        this._bindPickerEvents(body, grid, overlay, searchInput, filterSelect, selectedMedia);

        this._gridPromise.then(() => {
            if (this._gridData) {
                this._renderGrid(grid, this._gridData);
                if (overlay) overlay.hidden = true;
            } else {
                this._loadPickerGrid(grid, overlay, '', '');
            }
        });

        confirmBtn.onclick = () => this._confirmSelection(dialog, selectedMedia);

        dialog.addEventListener('close', () => dialog.remove());

        document.body.appendChild(dialog);
        dialog.showModal();

        setTimeout(() => searchInput?.focus(), 50);
    }

    _buildDialog() {
        const dialog = document.createElement('dialog');
        dialog.id = 'neox-media-picker-dialog';
        dialog.className = isDarkTheme() ? 'neox-media-dialog neox-media-dark' : 'neox-media-dialog';

        const header = document.createElement('div');
        header.className = 'neox-media-dialog-header';
        header.innerHTML = '<strong>Choisir un média</strong>';
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'neox-media-dialog-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = () => dialog.close();
        header.appendChild(closeBtn);
        dialog.appendChild(header);

        const body = document.createElement('div');
        body.className = 'neox-media-dialog-body';
        body.innerHTML = this._pickerHtml || '<div class="neox-media-spinner"></div>';
        dialog.appendChild(body);

        const footer = document.createElement('div');
        footer.className = 'neox-media-dialog-footer';
        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'neox-media-dialog-cancel';
        cancelBtn.textContent = 'Annuler';
        cancelBtn.onclick = () => dialog.close();
        const confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.className = 'neox-media-dialog-confirm';
        confirmBtn.textContent = 'Sélectionner';
        footer.appendChild(cancelBtn);
        footer.appendChild(confirmBtn);
        dialog.appendChild(footer);

        return { dialog, body, confirmBtn };
    }

    _queryPickerElements(body) {
        return {
            grid: body.querySelector('[data-neox-media-library-target="grid"]'),
            overlay: body.querySelector('[data-neox-media-library-target="overlay"]'),
            searchInput: body.querySelector('[data-neox-media-library-target="search"]'),
            filterSelect: body.querySelector('select[data-action]'),
        };
    }

    _bindPickerEvents(body, grid, overlay, searchInput, filterSelect, selectedMedia) {
        body.addEventListener('click', (e) => {
            const card = e.target.closest('[data-id]');
            if (!card || !grid?.contains(card)) return;

            const id = parseInt(card.dataset.id);
            const title = card.querySelector('strong')?.textContent || `#${id}`;

            if (this.multipleValue) {
                if (selectedMedia.ids.has(id)) {
                    selectedMedia.ids.delete(id);
                    selectedMedia.items.delete(id);
                    card.classList.remove('selected');
                } else {
                    selectedMedia.ids.add(id);
                    selectedMedia.items.set(id, { id, title });
                    card.classList.add('selected');
                }
            } else {
                selectedMedia.ids.clear();
                selectedMedia.items.clear();
                grid.querySelectorAll('.selected').forEach(c => c.classList.remove('selected'));
                selectedMedia.ids.add(id);
                selectedMedia.items.set(id, { id, title });
                card.classList.add('selected');
            }
        });

        let searchTimer = null;
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => this._loadPickerGrid(grid, overlay, searchInput.value, filterSelect?.value), 200);
            });
        }
        if (filterSelect) {
            filterSelect.addEventListener('change', () => {
                this._loadPickerGrid(grid, overlay, searchInput?.value || '', filterSelect.value);
            });
        }

        const uploadBtn = body.querySelector('[data-picker-upload]');
        const fileInput = body.querySelector('[data-picker-file-input]');
        if (uploadBtn && fileInput) {
            uploadBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) {
                    this._uploadFiles(fileInput.files, grid, overlay);
                    fileInput.value = '';
                }
            });
        }

        const linkBtn = body.querySelector('[data-picker-link]');
        const linkRow = body.querySelector('[data-picker-link-row]');
        const urlInput = body.querySelector('[data-picker-url-input]');
        const urlAddBtn = body.querySelector('[data-picker-url-add]');
        if (linkBtn && linkRow) {
            linkBtn.addEventListener('click', () => {
                linkRow.hidden = !linkRow.hidden;
                if (!linkRow.hidden) urlInput?.focus();
            });
        }
        if (urlAddBtn && urlInput) {
            urlAddBtn.addEventListener('click', () => {
                const url = urlInput.value.trim();
                if (url) {
                    this._addEmbedUrl(url, grid, overlay);
                    urlInput.value = '';
                    linkRow.hidden = true;
                }
            });
            urlInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    urlAddBtn.click();
                }
            });
        }
    }

    _confirmSelection(dialog, selectedMedia) {
        if (selectedMedia.ids.size > 0) {
            const ids = [...selectedMedia.ids];
            const value = this.multipleValue ? ids.join(',') : (ids[0] ?? '');
            const media = selectedMedia.items.get(ids[0]);

            this.valueTarget.value = value;
            this.valueTarget.dispatchEvent(new Event('change', { bubbles: true }));

            if (this.hasLabelTarget) {
                this.labelTarget.textContent = this.multipleValue
                    ? `${ids.length} média(s) sélectionné(s)`
                    : (media?.title || (value ? `Média #${value}` : 'Aucun média'));
            }

            if (this.hasPreviewTarget) {
                this._loadPreview(value);
            }

            this.dispatch('changed', { detail: { ids, media } });
        }
        dialog.close();
    }

    _renderGrid(grid, data) {
        if (!grid) return;
        grid.innerHTML = '';

        for (const media of (data.items || [])) {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'neox-media-card';
            card.dataset.id = media.id;

            const thumb = media.thumbUrl
                ? `<img src="${media.thumbUrl}" alt="" loading="lazy">`
                : (media.source === 'embed' ? `<span class="neox-media-icon">🎬</span>` : `<span class="neox-media-icon">📄</span>`);
            const title = media.title || media.name || `#${media.id}`;

            card.innerHTML = `
                <div class="neox-media-card-preview">${thumb}</div>
                <div class="neox-media-card-body">
                    <strong>${escapeHtml(title)}</strong>
                    <small>${media.type} · ${formatFileSize(media.size)}</small>
                </div>
            `;
            grid.appendChild(card);
        }

        if (data.items?.length === 0) {
            grid.innerHTML = '<small style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)">Aucun média trouvé</small>';
        }
    }

    async _loadPickerGrid(grid, overlay, query, type) {
        if (!grid) return;
        if (overlay) overlay.hidden = false;

        try {
            const params = new URLSearchParams();
            if (query) params.set('q', query);
            if (type) params.set('type', type);
            params.set('limit', '60');

            const response = await fetch(`${this.urlValue.replace('/picker', '/api')}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) return;

            const data = await response.json();
            this._renderGrid(grid, data);
        } catch (err) {
            console.error('neox-media-picker: loadGrid failed', err);
        } finally {
            if (overlay) overlay.hidden = true;
        }
    }

    async _addEmbedUrl(url, grid, overlay) {
        const Swal = await loadSwal();

        const confirmed = await Swal.fire({
            title: 'Ajouter un lien externe',
            html: '<div style="text-align:left;font-size:13px;line-height:1.6;color:var(--text-muted, #64748b)">'
                + '<p style="margin:0 0 10px"><strong style="color:var(--text, #0f172a)">⚠️ Risques liés aux liens externes</strong></p>'
                + '<ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:4px">'
                + '<li>Le lien peut devenir <strong>cassé</strong> si la ressource est déplacée</li>'
                + '<li>Le contenu peut être <strong>supprimé</strong> par la plateforme d\'origine</li>'
                + '<li>L\'accès peut être <strong>restreint</strong> (géoblocage, privé, supprimé)</li>'
                + '<li>Vous dépendez d\'un service <strong>tiers</strong> que vous ne contrôlez pas</li>'
                + '</ul></div>',
            showCancelButton: true,
            cancelButtonText: 'Annuler',
            confirmButtonText: 'Ajouter le lien',
            reverseButtons: true,
            customClass: { container: swalDarkClass() },
        });

        if (!confirmed.isConfirmed) return;

        if (overlay) overlay.hidden = false;
        swalToast('info', 'Ajout du lien...', 2000);

        try {
            const response = await fetch(this.embedUrlValue, {
                method: 'POST',
                body: JSON.stringify({ url }),
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.element.dataset.neoxMediaCsrfValue,
                },
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                swalToast('error', `Erreur: ${err.error || response.status}`);
                return;
            }

            const item = await response.json();
            swalToast('success', 'Lien ajouté');

            this._gridData = null;
            this._loadPickerGrid(grid, overlay, '', '');
        } catch (err) {
            console.error('neox-media-picker: embed failed', err);
            swalToast('error', 'Erreur lors de l\'ajout du lien');
        } finally {
            if (overlay) overlay.hidden = true;
        }
    }

    async _loadPreview(idsStr) {
        if (!this.hasPreviewTarget) return;
        const ids = String(idsStr).split(',').map(id => parseInt(id.trim())).filter(Boolean);

        this._updateCount(ids.length);

        if (ids.length === 0) {
            this.previewTarget.hidden = true;
            this.previewTarget.innerHTML = '';
            if (this.hasEmptyTarget) this.emptyTarget.hidden = false;
            return;
        }

        this._showLoader('Chargement des médias...');
        this.previewTarget.hidden = false;
        this.previewTarget.innerHTML = '';

        if (this.hasEmptyTarget) {
            this.emptyTarget.hidden = true;
        }

        const apiUrl = this.urlValue.replace('/picker', '/api');
        let allItems = [];

        try {
            const params = new URLSearchParams();
            params.set('limit', '100');
            const response = await fetch(`${apiUrl}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.ok) {
                const data = await response.json();
                allItems = data.items || [];
            }
        } catch (err) {
            console.error('neox-media-picker: preview fetch failed', err);
        }

        for (const id of ids) {
            const media = allItems.find(m => m.id === id);
            const card = this._buildPreviewCard(id, media);
            this.previewTarget.appendChild(card);
        }

        this._hideLoader();
    }

    _buildPreviewCard(id, media) {
        const card = document.createElement('div');
        card.className = 'neox-media-form-picker-card';
        card.dataset.id = id;

        const dragHandle = `
            <button type="button" class="neox-media-form-picker-card-drag" title="Déplacer">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;width:12px;height:12px;flex-shrink:0;max-width:none;max-height:none;visibility:visible;opacity:1"><polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/><polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/></svg>
            </button>`;

        const downloadBtn = `
            <button type="button" class="neox-media-form-picker-card-action neox-media-form-picker-card-action-download" title="Télécharger">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;width:12px;height:12px;flex-shrink:0;max-width:none;max-height:none;visibility:visible;opacity:1"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </button>`;
        const viewBtn = `
            <button type="button" class="neox-media-form-picker-card-action neox-media-form-picker-card-action-view" title="Aperçu">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;width:12px;height:12px;flex-shrink:0;max-width:none;max-height:none;visibility:visible;opacity:1"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>`;
        const removeBtn = `
            <button type="button" class="neox-media-form-picker-card-action neox-media-form-picker-card-action-remove" title="Retirer">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;width:12px;height:12px;flex-shrink:0;max-width:none;max-height:none;visibility:visible;opacity:1"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>`;

        if (media) {
            const thumb = media.thumbUrl
                ? `<img src="${media.thumbUrl}" alt="" loading="lazy">`
                : (media.source === 'embed'
                    ? '<div class="neox-media-form-picker-card-icon">🎬</div>'
                    : '<div class="neox-media-form-picker-card-icon">📄</div>');
            const name = media.title || media.name || `Média #${id}`;

            card.innerHTML = `
                ${dragHandle}
                <div class="neox-media-form-picker-card-thumb">
                    ${thumb}
                    <div class="neox-media-form-picker-card-actions">
                        ${media.url ? `<a href="${media.url}" download class="neox-media-form-picker-card-action" title="Télécharger">${downloadBtn.replace(/<button[^>]*>|<\/button>/g, '')}</a>` : ''}
                        ${viewBtn}
                        ${removeBtn}
                    </div>
                </div>
                <div class="neox-media-form-picker-card-info">
                    <div class="neox-media-form-picker-card-name" title="${escapeHtml(name)}">${escapeHtml(name)}</div>
                    <div class="neox-media-form-picker-card-size">${formatFileSize(media.size)}</div>
                </div>
            `;

            const viewButton = card.querySelector('.neox-media-form-picker-card-action-view');
            if (viewButton && (media.thumbUrl || media.url)) {
                viewButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.open(media.url || media.thumbUrl, '_blank');
                });
            }
        } else {
            card.classList.add('neox-media-form-picker-card-error');
            card.innerHTML = `
                ${dragHandle}
                <div class="neox-media-form-picker-card-thumb">
                    <div class="neox-media-form-picker-card-icon">❓</div>
                    <div class="neox-media-form-picker-card-actions">${removeBtn}</div>
                </div>
                <div class="neox-media-form-picker-card-info">
                    <div class="neox-media-form-picker-card-name">Média #${id} introuvable</div>
                    <div class="neox-media-form-picker-card-size">&nbsp;</div>
                </div>
            `;
        }

        const removeButton = card.querySelector('.neox-media-form-picker-card-action-remove');
        if (removeButton) {
            removeButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this._removeFromPreview(id);
            });
        }

        return card;
    }

    _updateCount(count) {
        if (this.hasCountTextTarget) {
            this.countTextTarget.textContent = count === 0
                ? 'Aucun fichier sélectionné'
                : `${count} fichier${count > 1 ? 's' : ''} sélectionné${count > 1 ? 's' : ''}`;
        }
        if (this.hasRemoveAllBtnTarget) {
            this.removeAllBtnTarget.hidden = count === 0;
        }
    }

    _removeFromPreview(id) {
        const currentIds = String(this.valueTarget.value)
            .split(',')
            .map(v => parseInt(v.trim()))
            .filter(v => v !== id);

        this.valueTarget.value = currentIds.join(',');
        this.valueTarget.dispatchEvent(new Event('change', { bubbles: true }));

        const item = this.previewTarget.querySelector(`[data-id="${id}"]`);
        if (item) item.remove();

        this._updateCount(currentIds.length);

        if (currentIds.length === 0) {
            this.previewTarget.hidden = true;
            if (this.hasEmptyTarget) {
                this.emptyTarget.hidden = false;
            }
            if (this.hasLabelTarget) {
                this.labelTarget.textContent = 'Glissez des fichiers ici';
            }
        }

        this.dispatch('changed', { detail: { ids: currentIds } });
    }


    async _uploadFiles(files, grid, overlay) {
        if (overlay) overlay.hidden = false;
        swalToast('info', `Upload de ${files.length} fichier(s)...`, 2000);

        try {
            const body = new FormData();
            [...files].forEach(f => body.append('files[]', f));

            const response = await fetch(this.uploadUrlValue, {
                method: 'POST',
                body,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.element.dataset.neoxMediaCsrfValue,
                },
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                swalToast('error', `Erreur upload: ${err.error || response.status}`);
                return;
            }

            const result = await response.json();
            const items = result.items || [];
            swalToast('success', `${items.length} fichier(s) importé(s)`);

            if (items.length > 0) {
                const ids = items.map(i => i.id);
                const value = this.multipleValue ? ids.join(',') : (ids[0] ?? '');
                const firstName = files[0]?.name?.replace(/\.[^.]+$/, '') || `Média #${ids[0]}`;

                this.valueTarget.value = value;
                this.valueTarget.dispatchEvent(new Event('change', { bubbles: true }));

                if (this.hasLabelTarget) {
                    this.labelTarget.textContent = this.multipleValue
                        ? `${ids.length} média(s) sélectionné(s)`
                        : firstName;
                }

                if (this.hasPreviewTarget) {
                    this._loadPreview(value);
                }

                this.dispatch('changed', { detail: { ids, media: items[0] } });
            }
        } catch (err) {
            console.error('neox-media-picker: upload failed', err);
            swalToast('error', 'Erreur lors de l\'upload');
        } finally {
            if (overlay) overlay.hidden = true;
        }
    }
}

import { Controller } from '@hotwired/stimulus';
import { loadSwal, swalConfirm, swalToast, swalDarkClass } from '../helpers/swal.js';
import { escapeHtml, formatFileSize } from '../helpers/utils.js';

export default class extends Controller {
    static targets = [
        'grid', 'empty', 'search', 'inspector', 'preview',
        'title', 'alt', 'description', 'meta', 'loadMore', 'sortSelect', 'statusBar',
        'overlay', 'saveBtn', 'removeBtn', 'collections', 'bulkActions', 'themeBtn'
    ];

    static values = {
        apiUrl: String,
        updateUrl: String,
        deleteUrl: String,
        bulkDeleteUrl: String,
        collectionCreateUrl: String,
        collectionBulkAddUrl: String,
        collectionRemoveUrl: String,
        collectionBulkRemoveUrl: String,
        uploadUrl: String,
        embedUrl: String,
        csrf: String,
        picker: { type: Boolean, default: false }
    };

    connect() {
        this.type = '';
        this.sort = 'updatedAt';
        this.offset = 0;
        this.limit = 60;
        this.total = 0;
        this.items = [];
        this.selected = null;
        this.selectedIds = new Set();
        this.multiSelectMode = false;
        this.collectionId = null;
        this.sandboxed = false;
        this.initTheme();
        this.load();

        this.element.addEventListener('neox-media-upload:uploaded', () => this.load());
        this.element.addEventListener('neox-media-upload:failed', (e) => this.showUploadError(e));

        if (this.hasCollectionsTarget) {
            this.collectionsTarget.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (e.dataTransfer) {
                    e.dataTransfer.dropEffect = 'copy';
                    try { e.dataTransfer.setData('text', ''); } catch {}
                }
                const btn = e.target.closest('[data-collection-id]');
                this.collectionsTarget.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                if (btn) btn.classList.add('drag-over');
            });

            this.collectionsTarget.addEventListener('dragleave', (e) => {
                if (!e.currentTarget.contains(e.relatedTarget)) {
                    this.collectionsTarget.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                }
            });

            this.collectionsTarget.addEventListener('drop', (e) => {
                e.preventDefault();
                this.collectionsTarget.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));

                const btn = e.target.closest('[data-collection-id]');
                const collectionId = btn?.dataset.collectionId;
                const files = [...(e.dataTransfer?.files || [])];
                if (!files.length || !collectionId) return;

                this.uploadToCollection(files, collectionId);
            });
        }
    }

    goBack(event) {
        const returnUrl = event.currentTarget.dataset.returnUrl;
        if (returnUrl) {
            window.location.href = returnUrl;
            return;
        }

        window.history.back();
    }
    async addLink() {
        const Swal = await loadSwal();

        const result = await Swal.fire({
            title: 'Ajouter un lien',
            input: 'url',
            inputPlaceholder: 'https://www.youtube.com/watch?v=...',
            inputAttributes: { autocapitalize: 'off' },
            showCancelButton: true,
            cancelButtonText: 'Annuler',
            confirmButtonText: 'Ajouter',
            reverseButtons: true,
            customClass: { container: swalDarkClass() },
            showLoaderOnConfirm: true,
            preConfirm: async (url) => {
                if (!url || !url.trim()) {
                    Swal.showValidationMessage('URL requise');
                    return false;
                }
                try {
                    const response = await fetch(this.embedUrlValue, {
                        method: 'POST',
                        body: JSON.stringify({ url: url.trim() }),
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-Token': this.csrfValue,
                        },
                    });
                    if (!response.ok) {
                        const err = await response.json().catch(() => ({}));
                        throw new Error(err.error || response.status);
                    }
                    return await response.json();
                } catch (err) {
                    Swal.showValidationMessage(`Erreur: ${err.message}`);
                }
            },
        });

        if (result.isConfirmed && result.value) {
            swalToast('success', 'Lien ajouté');
            this.offset = 0;
            this.items = [];
            this.load();
        }
    }

    async uploadToCollection(files, collectionId) {
        this.setGridLoading(true);
        swalToast('info', 'Upload vers collection...', 2000);

        try {
            const body = new FormData();
            files.forEach(file => body.append('files[]', file));

            const uploadResponse = await fetch(this.uploadUrlValue, {
                method: 'POST',
                body,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                }
            });

            if (!uploadResponse.ok) {
                const err = await uploadResponse.json().catch(() => ({}));
                swalToast('error', `Erreur upload: ${err.error || uploadResponse.status}`);
                return;
            }

            const result = await uploadResponse.json();
            const ids = (result.items || []).map(i => i.id);
            if (!ids.length) return;

            const url = this.collectionBulkAddUrlValue.replace('/collections/0/', `/collections/${collectionId}/`);
            const addResponse = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                },
                body: JSON.stringify({ ids })
            });

            if (addResponse.ok) {
                const addResult = await addResponse.json();
                swalToast('success', `${addResult.added} fichier(s) ajouté(s) à la collection`);
                this.load();
            }
        } catch (err) {
            console.error('neox-media-library: uploadToCollection failed', err);
        } finally {
            this.setGridLoading(false);
        }
    }

    async load(append = false) {
        if (!append) this.offset = 0;

        const params = new URLSearchParams();
        if (this.searchTarget?.value) params.set('q', this.searchTarget.value);
        if (this.type) params.set('type', this.type);
        if (this.sort) params.set('sort', this.sort);
        if (this.collectionId) params.set('collection', this.collectionId);
        if (this.sandboxed) params.set('sandboxed', '1');
        params.set('limit', this.limit);
        params.set('offset', this.offset);

        this.setGridLoading(true);
        try {
            const response = await fetch(`${this.apiUrlValue}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) {
                console.error('neox-media-library: API error', response.status);
                return;
            }
            const data = await response.json();
            this.total = data.total ?? data.items?.length ?? 0;

            if (append) {
                this.items = [...this.items, ...(data.items || [])];
            } else {
                this.items = data.items || [];
            }
            this.render(this.items);
        } catch (err) {
            console.error('neox-media-library: load failed', err);
        } finally {
            this.setGridLoading(false);
        }
    }

    render(items) {
        this.gridTarget.innerHTML = '';
        if (this.hasEmptyTarget) this.emptyTarget.hidden = items.length !== 0;

        for (const media of items) {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'neox-media-card';
            card.dataset.id = media.id;
            card.addEventListener('click', () => this.select(media, card));

            const thumb = this.thumbnail(media);
            const title = escapeHtml(media.title || media.name || `#${media.id}`);
            const statusBadge = this.statusBadge(media.status);
            const sandboxBadge = media.sandboxed ? '<span class="neox-media-sandbox-card-badge">Sandbox</span>' : '';

            card.innerHTML = `
                <div class="neox-media-card-preview">${thumb}${statusBadge}${sandboxBadge}</div>
                <div class="neox-media-card-body">
                    <strong>${title}</strong>
                    <small>${escapeHtml(media.type)} · ${formatFileSize(media.size)}</small>
                </div>
            `;

            if (this.collectionId) {
                const removeBtn = document.createElement('span');
                removeBtn.className = 'neox-media-card-remove';
                removeBtn.title = 'Retirer de la collection';
                removeBtn.textContent = '×';
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    e.preventDefault();
                    this.removeFromCollection(media.id);
                });
                card.appendChild(removeBtn);
            }

            this.gridTarget.appendChild(card);
        }

        if (this.hasLoadMoreTarget) {
            this.loadMoreTarget.hidden = (this.offset + this.limit) >= this.total;
        }
    }

    statusBadge(status) {
        if (!status || status === 'ready') return '';
        const labels = { pending: '⏳', processing: '⚙️', failed: '❌' };
        const label = labels[status] ?? '';
        return label ? `<span class="neox-media-status-badge" data-status="${status}">${label}</span>` : '';
    }

    thumbnail(media, { full = false } = {}) {
        if (media.source === 'embed') {
            if (full && media.embedUrl) {
                return `<iframe src="${escapeHtml(media.embedUrl)}" frameborder="0" allowfullscreen style="width:100%;aspect-ratio:16/9;border-radius:var(--radius-md)"></iframe>`;
            }
            if (media.thumbUrl) return `<img src="${escapeHtml(media.thumbUrl)}" alt="" loading="lazy">`;
            return `<div class="neox-media-icon">🎬</div>`;
        }
        const thumb = full ? (media.url || media.thumbUrl) : (media.thumbUrl || media.url);
        const url = escapeHtml(thumb || '');
        if (media.type === 'image' && url) return `<img src="${url}" alt="" loading="lazy">`;
        if (media.type === 'video' && url) return `<video src="${url}" muted preload="metadata"></video>`;
        if (media.type === 'audio') return `<div class="neox-media-icon">♫</div>`;
        return `<div class="neox-media-icon">📄</div>`;
    }

    select(media, card) {
        if (this.pickerValue || this.multiSelectMode) {
            if (this.selectedIds.has(media.id)) {
                this.selectedIds.delete(media.id);
                card.classList.remove('selected');
            } else {
                this.selectedIds.add(media.id);
                card.classList.add('selected');
            }
            this.selected = media;
            if (this.multiSelectMode) {
                this.updateMultiSelectButton();
                this.updateBulkActions();
            }
            return;
        }

        this.selected = media;
        this.gridTarget.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));
        card.classList.add('selected');

        if (this.hasInspectorTarget) {
            this.inspectorTarget.hidden = false;
            this.previewTarget.innerHTML = this.thumbnail(media, { full: true });
            this.titleTarget.value = media.title || '';
            this.altTarget.value = media.alt || '';
            this.descriptionTarget.value = media.description || '';
            this.metaTarget.textContent = `${media.mimeType || media.type} · ${formatFileSize(media.size)}${media.width ? ` · ${media.width}×${media.height}` : ''}${media.sandboxed ? ` · Sandbox: ${media.riskReason || media.riskLevel}` : ''}`;
        }
    }

    initTheme() {
        const saved = localStorage.getItem('neox-media-theme') || 'light';
        this.applyTheme(saved);
    }

    toggleTheme() {
        const current = this.element.dataset.theme || 'light';
        this.applyTheme(current === 'dark' ? 'light' : 'dark');
    }

    applyTheme(theme) {
        this.element.dataset.theme = theme;
        localStorage.setItem('neox-media-theme', theme);
        if (this.hasThemeBtnTarget) {
            this.themeBtnTarget.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
    }

    toggleMultiSelect() {
        this.multiSelectMode = !this.multiSelectMode;
        if (!this.multiSelectMode) {
            this.selectedIds.clear();
            this.gridTarget.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));
        }
        this.updateMultiSelectButton();
        this.updateBulkActions();
    }

    updateMultiSelectButton() {
        const btn = this.element.querySelector('[data-action*="toggleMultiSelect"]');
        if (!btn) return;
        if (this.multiSelectMode) {
            const count = this.selectedIds.size;
            btn.textContent = count > 0 ? `Sélection (${count})` : 'Sélection multiple ✓';
            btn.classList.add('active');
        } else {
            btn.textContent = 'Sélection multiple';
            btn.classList.remove('active');
        }
    }

    updateBulkActions() {
        if (this.hasBulkActionsTarget) {
            this.bulkActionsTarget.hidden = !this.multiSelectMode || this.selectedIds.size === 0;
        }
        const removeBtn = this.element.querySelector('.neox-media-bulk-remove');
        if (removeBtn) {
            removeBtn.hidden = !this.collectionId;
        }
    }

    search() {
        clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => this.load(), 180);
    }

    filterType(event) {
        this.type = event.currentTarget.dataset.type || '';
        this.sandboxed = false;
        this.updateSidebarActive(event.currentTarget);
        this.load();
    }

    filterSandbox(event) {
        this.sandboxed = !this.sandboxed;
        this.type = '';
        this.collectionId = null;
        this.updateSidebarActive(this.sandboxed ? event.currentTarget : null);
        this.load();
    }

    filterSelect(event) {
        this.type = event.currentTarget.value || '';
        this.load();
    }

    updateSidebarActive(activeButton) {
        this.element.querySelectorAll('.neox-media-sidebar button.active').forEach(el => el.classList.remove('active'));
        if (activeButton) activeButton.classList.add('active');
    }
    sortChanged() {
        this.sort = this.sortSelectTarget.value;
        this.load();
    }

    async loadMore() {
        this.offset += this.limit;
        if (this.hasLoadMoreTarget) this.setBtnLoading(this.loadMoreTarget, true);
        await this.load(true);
        if (this.hasLoadMoreTarget) this.setBtnLoading(this.loadMoreTarget, false);
    }

    async save() {
        if (!this.selected) return;
        this.setBtnLoading(this.saveBtnTarget, true);
        try {
            await fetch(this.endpoint(this.updateUrlValue, this.selected.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                },
                body: JSON.stringify({
                    title: this.titleTarget.value,
                    alt: this.altTarget.value,
                    description: this.descriptionTarget.value
                })
            });

            this.selected.title = this.titleTarget.value;
            this.selected.alt = this.altTarget.value;
            this.selected.description = this.descriptionTarget.value;

            const card = this.gridTarget.querySelector(`[data-id="${this.selected.id}"]`);
            if (card) {
                const titleEl = card.querySelector('strong');
                if (titleEl) titleEl.textContent = this.selected.title || this.selected.name || `#${this.selected.id}`;
            }
        } finally {
            this.setBtnLoading(this.saveBtnTarget, false);
        }
    }

    async remove() {
        if (!this.selected) return;
        if (!window.confirm('Supprimer définitivement ce média ?')) return;

        this.setBtnLoading(this.removeBtnTarget, true);
        try {
            const response = await fetch(this.endpoint(this.deleteUrlValue, this.selected.id), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                }
            });

            if (response.ok) {
                const card = this.gridTarget.querySelector(`[data-id="${this.selected.id}"]`);
                if (card) card.remove();
                this.items = this.items.filter(i => i.id !== this.selected.id);
                this.selected = null;
                if (this.hasInspectorTarget) this.inspectorTarget.hidden = true;
                if (this.items.length === 0 && this.hasEmptyTarget) this.emptyTarget.hidden = false;
            }
        } finally {
            this.setBtnLoading(this.removeBtnTarget, false);
        }
    }

    copyUrl() {
        if (!this.selected) return;
        const url = this.selected.url || '';
        if (!url) return;
        navigator.clipboard.writeText(url).then(() => {
            this.dispatch('url-copied', { detail: { url } });
        });
    }

    showUploadError(event) {
        swalToast('error', 'Erreur lors de l\'upload');
    }

    filterCollection(event) {
        const id = event.currentTarget.dataset.collectionId;
        this.collectionId = (id && id !== this.collectionId) ? id : null;

        this.collectionsTarget?.querySelectorAll('.active').forEach(el => el.classList.remove('active'));
        if (this.collectionId) {
            event.currentTarget.classList.add('active');
        }
        this.load();
    }

    async createCollection() {
        const name = window.prompt('Nom de la collection :');
        if (!name || !name.trim()) return;

        try {
            const response = await fetch(this.collectionCreateUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                },
                body: JSON.stringify({ name: name.trim() })
            });

            if (response.ok) {
                const collection = await response.json();
                this.refreshCollections();
            }
        } catch (err) {
            console.error('neox-media-library: createCollection failed', err);
        }
    }

    async refreshCollections() {
        if (!this.hasCollectionsTarget) return;
        try {
            const response = await fetch(this.apiUrlValue.replace('/api', '/collections'), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) return;
            const data = await response.json();
            this.collectionsTarget.innerHTML = '';
            if (!data.items || data.items.length === 0) {
                this.collectionsTarget.innerHTML = '<small>Aucune collection</small>';
                return;
            }
            for (const col of data.items) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.draggable = true;
                btn.dataset.id = col.id;
                btn.dataset.action = 'neox-media-library#filterCollection';
                btn.dataset.collectionId = col.id;
                btn.textContent = col.name;
                this.collectionsTarget.appendChild(btn);
            }
        } catch (err) {
            console.error('neox-media-library: refreshCollections failed', err);
        }
    }

    async bulkDelete() {
        if (this.selectedIds.size === 0) return;
        const ok = await swalConfirm({
            title: 'Supprimer la sélection',
            text: `${this.selectedIds.size} média(s) seront supprimés.`,
            icon: 'warning',
            confirmButtonText: 'Supprimer',
            confirmButtonColor: '#dc2626',
        });
        if (!ok) return;

        try {
            const response = await fetch(this.bulkDeleteUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                },
                body: JSON.stringify({ ids: [...this.selectedIds] })
            });

            if (response.ok) {
                for (const id of this.selectedIds) {
                    const card = this.gridTarget.querySelector(`[data-id="${id}"]`);
                    if (card) card.remove();
                }
                this.items = this.items.filter(i => !this.selectedIds.has(i.id));
                this.selectedIds.clear();
                this.updateMultiSelectButton();
                this.updateBulkActions();
                if (this.items.length === 0 && this.hasEmptyTarget) this.emptyTarget.hidden = false;
            }
        } catch (err) {
            console.error('neox-media-library: bulkDelete failed', err);
        }
    }

    async bulkAddToCollection(event) {
        if (this.selectedIds.size === 0) return;
        const collectionId = event.currentTarget.dataset.collectionId;
        if (!collectionId) return;

        try {
            const url = this.collectionBulkAddUrlValue.replace('/collections/0/', `/collections/${collectionId}/`);
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                },
                body: JSON.stringify({ ids: [...this.selectedIds] })
            });

            if (response.ok) {
                const result = await response.json();
                swalToast('success', `${result.added} média(s) ajouté(s) à la collection`);
            }
        } catch (err) {
            console.error('neox-media-library: bulkAddToCollection failed', err);
        }
    }

    async bulkRemoveFromCollection() {
        if (this.selectedIds.size === 0 || !this.collectionId) return;
        const ok = await swalConfirm({
            title: 'Retirer de la collection',
            text: `${this.selectedIds.size} média(s) seront retirés de cette collection.`,
            icon: 'warning',
            confirmButtonText: 'Retirer',
            confirmButtonColor: '#dc2626',
        });
        if (!ok) return;

        try {
            const url = this.collectionBulkRemoveUrlValue.replace('/collections/0/', `/collections/${this.collectionId}/`);
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                },
                body: JSON.stringify({ ids: [...this.selectedIds] })
            });

            if (response.ok) {
                const result = await response.json();
                swalToast('success', `${result.removed} média(s) retiré(s) de la collection`);
                this.selectedIds.clear();
                this.updateMultiSelectButton();
                this.updateBulkActions();
                this.load();
            }
        } catch (err) {
            console.error('neox-media-library: bulkRemoveFromCollection failed', err);
        }
    }

    async removeFromCollection(mediaId) {
        if (!this.collectionId) return;

        try {
            const url = this.collectionBulkRemoveUrlValue.replace('/collections/0/', `/collections/${this.collectionId}/`);
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                },
                body: JSON.stringify({ ids: [mediaId] })
            });

            if (response.ok) {
                const card = this.gridTarget.querySelector(`[data-id="${mediaId}"]`);
                if (card) card.remove();
                this.items = this.items.filter(i => i.id !== mediaId);
                if (this.items.length === 0 && this.hasEmptyTarget) this.emptyTarget.hidden = false;
                swalToast('success', 'Média retiré de la collection', 2000);
            }
        } catch (err) {
            console.error('neox-media-library: removeFromCollection failed', err);
        }
    }

    confirmSelection() {
        const detail = {
            ids: [...this.selectedIds],
            media: this.selected
        };
        this.dispatch('selected', { detail });
    }

    setGridLoading(loading) {
        if (this.hasOverlayTarget) this.overlayTarget.hidden = !loading;
    }

    setBtnLoading(btn, loading) {
        if (!btn) return;
        btn.classList.toggle('neox-media-btn-loading', loading);
        btn.disabled = loading;
    }

    endpoint(template, id) {
        return template.replace(/\/0(?=\/?$)/, `/${id}`);
    }

    fileSize(bytes) {
        return formatFileSize(bytes) || '—';
    }

    escape(value) {
        return escapeHtml(value);
    }
}

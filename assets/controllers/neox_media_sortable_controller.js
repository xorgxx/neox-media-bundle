import { Controller } from '@hotwired/stimulus';

/**
 * Lightweight sortable controller for collection reordering.
 * Uses HTML5 drag & drop API. Dispatches "reordered" event with ordered IDs.
 *
 * Usage:
 *   <div data-controller="neox-media-sortable" data-neox-media-sortable-url-value="/reorder">
 *     <button draggable="true" data-id="1">...</button>
 *     <button draggable="true" data-id="2">...</button>
 *   </div>
 */
export default class extends Controller {
    static values = { url: String, csrf: String };
    dragId = null;

    connect() {
        this.element.querySelectorAll('[draggable="true"]').forEach(item => {
            item.addEventListener('dragstart', (e) => this.dragStart(e));
            item.addEventListener('dragover', (e) => this.dragOver(e));
            item.addEventListener('dragleave', (e) => this.dragLeave(e));
            item.addEventListener('drop', (e) => this.drop(e));
            item.addEventListener('dragend', (e) => this.dragEnd(e));
        });
    }

    dragStart(event) {
        this.dragId = event.currentTarget.dataset.id;
        event.currentTarget.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
    }

    dragOver(event) {
        event.preventDefault();
        event.currentTarget.classList.add('drag-over');
    }

    dragLeave(event) {
        event.currentTarget.classList.remove('drag-over');
    }

    drop(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('drag-over');

        const targetId = event.currentTarget.dataset.id;
        if (!this.dragId || this.dragId === targetId) return;

        const ordered = [...this.element.querySelectorAll('[data-id]')]
            .map(el => el.dataset.id);

        const draggedEl = this.element.querySelector(`[data-id="${this.dragId}"]`);
        const targetEl = event.currentTarget;

        if (this.isBefore(draggedEl, targetEl)) {
            targetEl.parentNode.insertBefore(draggedEl, targetEl);
        } else {
            targetEl.parentNode.insertBefore(draggedEl, targetEl.nextSibling);
        }

        const newOrder = [...this.element.querySelectorAll('[data-id]')]
            .map(el => parseInt(el.dataset.id));

        this.dispatch('reordered', { detail: { ids: newOrder } });

        if (this.urlValue) {
            fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                },
                body: JSON.stringify({ ids: newOrder })
            }).catch(() => {});
        }
    }

    dragEnd(event) {
        event.currentTarget.classList.remove('dragging');
        this.dragId = null;
    }

    isBefore(a, b) {
        let current = a;
        while (current = current.previousSibling) {
            if (current === b) return true;
        }
        return false;
    }
}

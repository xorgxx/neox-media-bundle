import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'progress', 'error', 'dropzone'];
    static values = { url: String, csrf: String };

    choose(event) {
        if (event?.target === this.inputTarget) return;
        event?.preventDefault();
        this.inputTarget.click();
    }

    dragover(event) {
        event.preventDefault();
        event.currentTarget.classList.add('dragging');
    }

    dragleave(event) {
        event.currentTarget.classList.remove('dragging');
    }

    drop(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('dragging');
        this.uploadFiles([...event.dataTransfer.files]);
    }

    inputChanged() {
        this.uploadFiles([...this.inputTarget.files]);
        this.inputTarget.value = '';
    }

    async uploadFiles(files) {
        if (!files.length) return;

        const body = new FormData();
        files.forEach(file => body.append('files[]', file));

        this.dispatch('started', { detail: { count: files.length } });
        this.showProgress(0);
        this.setDropzoneLoading(true);

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfValue
                }
            });

            this.showProgress(100);

            if (!response.ok) {
                let errorMsg = `Upload failed (${response.status})`;
                try {
                    const errorData = await response.json();
                    if (errorData.error) errorMsg = errorData.error;
                    if (errorData.details) errorMsg += ': ' + errorData.details.join(', ');
                } catch {}
                this.showError(errorMsg);
                this.dispatch('failed', { detail: { status: response.status, message: errorMsg } });
                return;
            }

            const result = await response.json();
            this.hideProgress();
            this.hideError();
            this.dispatch('uploaded', { detail: result });

            if (result.warnings && result.warnings.length) {
                this.showError('Some files were rejected: ' + result.warnings.join(', '));
            }
        } catch (err) {
            this.showError('Network error: ' + err.message);
            this.dispatch('failed', { detail: { message: err.message } });
        } finally {
            this.setDropzoneLoading(false);
        }
    }

    setDropzoneLoading(loading) {
        if (this.hasDropzoneTarget) {
            this.dropzoneTarget.classList.toggle('neox-media-dropzone-loading', loading);
            this.dropzoneTarget.style.pointerEvents = loading ? 'none' : '';
        }
    }

    showProgress(percent) {
        if (this.hasProgressTarget) {
            this.progressTarget.hidden = false;
            this.progressTarget.value = percent;
        }
    }

    hideProgress() {
        if (this.hasProgressTarget) {
            this.progressTarget.hidden = true;
        }
    }

    showError(message) {
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = message;
            this.errorTarget.hidden = false;
            setTimeout(() => { this.errorTarget.hidden = true; }, 6000);
        }
    }

    hideError() {
        if (this.hasErrorTarget) {
            this.errorTarget.hidden = true;
        }
    }
}

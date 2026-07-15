/* =========================================================
   Admin Product Management — kept in its own Vite entry so
   pages outside admin/products never load this JS. Registers
   Alpine components via the `alpine:init` event (Alpine's
   documented extension hook) rather than calling Alpine.start()
   itself, since admin.js already owns that — this file's <script>
   tag must be included BEFORE admin.js's in the page so this
   listener is attached before Alpine actually starts.
   ========================================================= */

import Sortable from 'sortablejs';

function djAdminCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/**
 * Drag-to-reorder the gallery. Persists the full ordered id list in one
 * PATCH per drop, replacing the old one-number-box-per-image workflow.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-image-reorder]').forEach((container) => {
        Sortable.create(container, {
            animation: 150,
            onEnd: async () => {
                const ids = Array.from(container.querySelectorAll('[data-image-id]')).map((el) => el.dataset.imageId);

                const response = await fetch(container.dataset.reorderUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': djAdminCsrfToken(),
                    },
                    body: JSON.stringify({ ids }),
                });

                window.djToast?.(
                    response.ok ? container.dataset.toastSuccess : container.dataset.toastError,
                    response.ok ? 'success' : 'error'
                );
            },
        });
    });
});

/**
 * Debounced partial autosave for the product edit form. Additive
 * progressive enhancement — the full "Save Product" submit button stays
 * fully functional if this script fails to load or a request fails.
 * Guards against out-of-order responses via an incrementing request id:
 * a slow request that resolves after a newer one has started is ignored
 * so it can never stomp a more recent "unsaved" state.
 */
class ProductAutosave {
    constructor(form, indicator) {
        this.form = form;
        this.indicator = indicator;
        this.url = form.dataset.autosaveUrl;
        this.pending = {};
        this.timer = null;
        this.requestId = 0;
        this.abortController = null;

        form.addEventListener('input', (event) => this.queue(event.target, 800));
        form.addEventListener('change', (event) => this.queue(event.target, 0));
        indicator?.addEventListener('click', () => {
            if (indicator.dataset.state === 'error') {
                this.flush();
            }
        });
    }

    queue(field, delay) {
        if (! field.name || ! this.form.contains(field)) {
            return;
        }

        this.pending[field.name] = field.type === 'checkbox' ? (field.checked ? '1' : '0') : field.value;
        this.setState('unsaved');
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.flush(), delay);
    }

    async flush() {
        if (Object.keys(this.pending).length === 0) {
            return;
        }

        const payload = { ...this.pending };
        const id = ++this.requestId;

        this.abortController?.abort();
        this.abortController = new AbortController();
        this.setState('saving');

        try {
            const response = await fetch(this.url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': djAdminCsrfToken(),
                },
                body: JSON.stringify(payload),
                signal: this.abortController.signal,
            });

            if (id !== this.requestId) {
                return;
            }

            if (response.ok) {
                this.pending = {};
                this.setState('saved');
            } else {
                this.setState('error');
            }
        } catch (error) {
            if (error.name !== 'AbortError' && id === this.requestId) {
                this.setState('error');
            }
        }
    }

    setState(state) {
        if (! this.indicator) {
            return;
        }

        this.indicator.dataset.state = state;
        this.indicator.textContent = this.indicator.dataset[`${state}Label`] ?? '';
        clearTimeout(this.fadeTimer);

        if (state === 'saved') {
            this.fadeTimer = setTimeout(() => {
                if (this.indicator.dataset.state === 'saved') {
                    this.setState('idle');
                }
            }, 3000);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-autosave-form]').forEach((form) => {
        new ProductAutosave(form, document.querySelector('[data-autosave-indicator]'));
    });
});

/**
 * Keyboard shortcuts, scoped to elements that opt in via data attributes so
 * a shortcut never fires against markup on a page that doesn't expect it.
 * Ctrl+D reaches into the bulk table's Alpine component via the public
 * Alpine.$data() API rather than duplicating its bulkAction() logic here.
 */
document.addEventListener('keydown', (event) => {
    const combo = event.metaKey || event.ctrlKey;
    if (! combo) {
        if (event.key === 'Escape') {
            document.activeElement?.blur();
        }
        return;
    }

    const key = event.key.toLowerCase();

    if (key === 's') {
        event.preventDefault();
        document.querySelectorAll('[data-shortcut-save]').forEach((button) => {
            if (button.offsetParent !== null) button.click();
        });
    }

    if (key === 'd') {
        const table = document.querySelector('[data-shortcut-duplicate-table]');
        if (table && window.Alpine) {
            event.preventDefault();
            window.Alpine.$data(table).bulkAction('duplicate');
        }
    }

    if (key === 'g') {
        const form = document.querySelector('[data-shortcut-generate]');
        if (form && form.offsetParent !== null) {
            event.preventDefault();
            form.requestSubmit();
        }
    }
});

document.addEventListener('alpine:init', () => {
    window.Alpine.data('djBulkTable', () => ({
        selected: [],

        toggleAll(event) {
            const rows = Array.from(this.$el.querySelectorAll('tbody input[type="checkbox"]'));
            this.selected = event.target.checked ? rows.map((el) => el.value) : [];
        },

        async bulkAction(action) {
            if (this.selected.length === 0) {
                return;
            }

            if (action === 'delete' && ! confirm(this.$el.dataset.confirmDelete)) {
                return;
            }

            if (action === 'archive' && ! confirm(this.$el.dataset.confirmArchive)) {
                return;
            }

            const response = await fetch(this.$el.dataset.bulkActionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': djAdminCsrfToken(),
                },
                body: JSON.stringify({ action, ids: this.selected }),
            });

            if (response.ok) {
                const data = await response.json().catch(() => ({}));

                if (action === 'delete' && (data.skipped_count ?? 0) > 0) {
                    const message = (this.$el.dataset.toastBulkDeleteResult ?? '')
                        .replace(':deleted', data.deleted_count ?? 0)
                        .replace(':skipped', data.skipped_count ?? 0);
                    window.djQueueToast?.(message);
                } else {
                    window.djQueueToast?.((this.$el.dataset.toastSuccess ?? '').replace(':count', data.count ?? ''));
                }

                window.location.reload();
            } else {
                window.djToast?.(this.$el.dataset.toastError, 'error');
            }
        },
    }));

    window.Alpine.data('djVariantBulkTable', () => ({
        selected: [],

        toggleAll(event) {
            const rows = Array.from(this.$el.querySelectorAll('tbody input[type="checkbox"]'));
            this.selected = event.target.checked ? rows.map((el) => el.value) : [];
        },

        async bulkAction(action) {
            if (this.selected.length === 0) {
                return;
            }

            const params = {};

            if (action === 'set_stock') {
                const value = prompt(this.$el.dataset.promptStock);
                if (value === null) return;
                params.stock = parseInt(value, 10) || 0;
            }

            if (action === 'adjust_stock') {
                const value = prompt(this.$el.dataset.promptDelta);
                if (value === null) return;
                params.delta = parseInt(value, 10) || 0;
            }

            if (action === 'set_price') {
                const value = prompt(this.$el.dataset.promptPrice);
                if (value === null) return;
                params.price_override = value === '' ? null : parseInt(value, 10);
            }

            if (action === 'set_sale_price') {
                const value = prompt(this.$el.dataset.promptSalePrice);
                if (value === null) return;
                params.sale_price = value === '' ? null : parseInt(value, 10);
            }

            if (action === 'delete' && ! confirm(this.$el.dataset.confirmDelete)) {
                return;
            }

            const response = await fetch(this.$el.dataset.bulkActionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': djAdminCsrfToken(),
                },
                body: JSON.stringify({ action, ids: this.selected, params }),
            });

            if (response.ok) {
                const data = await response.json().catch(() => ({}));
                window.djQueueToast?.((this.$el.dataset.toastSuccess ?? '').replace(':count', data.count ?? ''));
                window.location.reload();
            } else {
                window.djToast?.(this.$el.dataset.toastError, 'error');
            }
        },
    }));

    /**
     * Guided Setup: wraps the same tabs/endpoints from the other phases in
     * a stepped flow (Next/Back) instead of free-form tab-clicking. Purely
     * a presentation layer — every step is the exact same partial/form the
     * classic tab view uses, so nothing here talks to a new endpoint except
     * "Publish Now", which reuses the existing bulk-action endpoint for a
     * single id.
     */
    window.Alpine.data('djProductWizard', (wizardMode, bulkActionUrl, productId, redirectUrl) => ({
        tab: 'basic',
        wizardMode,
        steps: ['basic', 'images', 'options', 'variants', 'seo', 'review'],

        get stepIndex() {
            return this.steps.indexOf(this.tab);
        },

        next() {
            if (this.stepIndex < this.steps.length - 1) {
                this.tab = this.steps[this.stepIndex + 1];
            }
        },

        back() {
            if (this.stepIndex > 0) {
                this.tab = this.steps[this.stepIndex - 1];
            }
        },

        async publishNow() {
            const response = await fetch(bulkActionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': djAdminCsrfToken(),
                },
                body: JSON.stringify({ action: 'publish', ids: [productId] }),
            });

            if (response.ok) {
                window.djQueueToast?.(this.$el.dataset.toastPublished);
                window.location.href = redirectUrl;
            } else {
                window.djToast?.(this.$el.dataset.toastError, 'error');
            }
        },
    }));
});

/**
 * MoonShine Media Manager v4.0
 *
 * Architecture (SOLID):
 * - Store:  Alpine.store('mm') — singleton state (open/close, selection, config)
 * - Browser: Alpine.data('mmBrowser') — file listing, upload, CRUD operations
 * - Picker:  Alpine.data('mmPicker')  — field value binding, preview, trigger
 *
 * Communication: Picker → Store.open() → Browser → Store.confirm() → Picker callback
 */

// =========================================================================
// Shared utilities
// =========================================================================

/** @param {string} url @returns {boolean} */
function mmIsImageUrl(url) {
    return /\.(jpe?g|png|gif|webp|avif|bmp|svg|ico)(\?.*)?$/i.test(url || '');
}

/**
 * HEAD-check a single URL with AbortController support and caching.
 * @param {string} url
 * @param {Object<string,boolean>} cache  path → exists
 * @param {string} path  cache key
 * @param {AbortSignal} [signal]
 * @returns {Promise<boolean>}
 */
async function mmCheckUrlExists(url, cache, path, signal) {
    if (path in cache) {
        return cache[path];
    }

    try {
        const resp = await fetch(url, { method: 'HEAD', cache: 'no-store', signal });
        const exists = resp.ok;
        cache[path] = exists;
        return exists;
    } catch {
        if (signal?.aborted) return false;
        cache[path] = false;
        return false;
    }
}

document.addEventListener('alpine:init', () => {

    // =========================================================================
    // Store — singleton media manager state
    // =========================================================================
    Alpine.store('mm', {
        /** @type {boolean} */
        isOpen: false,

        /** @type {boolean} */
        mmScrolled: false,

        /** @type {boolean} */
        multiple: false,

        /** @type {string[]} */
        allowedTypes: [],

        /** @type {string[]} */
        allowedExtensions: [],

        /** @type {Array<{path: string, url: string, type: string}>} */
        selected: [],

        /** @type {string|null} */
        lastPath: null,

        /** @type {Function|null} */
        _callback: null,

        /** Cache: path → true (exists). Invalidated on refresh. */
        _existsCache: {},

        /**
         * Open the media manager.
         * @param {{multiple?: boolean, allowedTypes?: string[], allowedExtensions?: string[], existingPaths?: string[], baseUrl?: string}} config
         * @param {Function} callback — receives selected file path(s)
         */
        open(config = {}, callback) {
            this.multiple = config.multiple ?? false;
            this.allowedTypes = config.allowedTypes ?? [];
            this.allowedExtensions = config.allowedExtensions ?? [];
            this._callback = typeof callback === 'function' ? callback : null;

            const baseUrl = config.baseUrl ?? '';
            this.selected = (config.existingPaths ?? [])
                .filter(p => typeof p === 'string' && p.length > 0)
                .map(p => ({ path: p, url: baseUrl + '/' + p }));

            this.isOpen = true;

            window.MoonShine?.ui?.toggleOffCanvas('media-manager');
        },

        close() {
            this.isOpen = false;
            this._callback = null;

            window.MoonShine?.ui?.toggleOffCanvas('media-manager');
        },

        /** Confirm selection and call the picker's callback. */
        confirm() {
            if (!this._callback) {
                return;
            }

            if (this.multiple) {
                this._callback(this.selected.map(f => f.path));
            } else {
                this._callback(this.selected[0]?.path ?? null);
            }

            this._callback = null;
            this.isOpen = false;

            window.MoonShine?.ui?.toggleOffCanvas('media-manager');
        },

        /**
         * Toggle a file in the selection.
         * @param {{path: string, url: string, type: string}} file
         */
        toggleFile(file) {
            if (!this.multiple) {
                this.selected = this.isSelected(file.path) ? [] : [file];
                return;
            }

            const index = this.selected.findIndex(f => f.path === file.path);
            if (index >= 0) {
                this.selected.splice(index, 1);
            } else {
                this.selected.push(file);
            }
        },

        /**
         * @param {string} filePath
         * @returns {boolean}
         */
        isSelected(filePath) {
            return this.selected.some(f => f.path === filePath);
        },

        /** @returns {boolean} */
        get hasSelection() {
            return this.selected.length > 0;
        },

        /**
         * Reorder selected by dragging item from one index to another.
         * @param {number} fromIdx
         * @param {number} toIdx
         */
        moveSelected(fromIdx, toIdx) {
            if (fromIdx === toIdx) {
                return;
            }

            const item = this.selected.splice(fromIdx, 1)[0];
            this.selected.splice(toIdx, 0, item);
        },

        /** Invalidate the exists-check cache (call after upload/delete/move). */
        invalidateExistsCache(...paths) {
            if (paths.length === 0) {
                this._existsCache = {};
            } else {
                for (const p of paths) {
                    delete this._existsCache[p];
                }
            }
        },
    });

    // =========================================================================
    // Browser — file browsing component (renders inside OffCanvas)
    // =========================================================================
    Alpine.data('mmBrowser', (urls = {}, modalPrefix = 'mm-') => ({
        files: [],

        path: '/',

        view: 'table',

        navigation: {},

        urls: urls,

        loading: false,

        modalPrefix: modalPrefix,

        /** @type {number|null} Drag source index for selected bar reorder */
        selectedDragIdx: null,

        /** @type {string} */
        jumpPath: '/',

        /** @type {string} Path of file to highlight after loading (set by navigateToFile) */
        highlightPath: '',

        /** @type {number|null} Timeout ID for highlightPath auto-clear */
        _highlightTimeout: null,

        /** @type {number|null} Timeout ID for scrollToHighlighted after loadFiles */
        _scrollTimeout: null,

        /** @type {AbortController|null} For cancelling stale loadFiles requests */
        _loadAbort: null,

        /** @type {AbortController|null} For cancelling stale checkSelectedExist requests */
        _checkAbort: null,

        /** @type {number|null} Debounce timer for checkSelectedExist */
        _checkTimer: null,

        /** @type {string[]} Paths of selected files that are broken (404) */
        brokenSelectedPaths: [],

        // -- Modal form state --
        renamePath: '',
        renameNew: '',
        deleteFiles: [],
        urlToShow: '',
        newFolderName: '',

        init() {
            this.$nextTick(() => this.loadFiles('/'));

            const refreshHandler = () => this.refresh();
            window.addEventListener('mm:refresh', refreshHandler);
            this.$cleanup?.(() => window.removeEventListener('mm:refresh', refreshHandler));

            this.$watch('$store.mm.isOpen', (open) => {
                if (open) {
                    this.files = [];
                    this.brokenSelectedPaths = [];
                    this.$nextTick(() => {
                        this.loadFiles(Alpine.store('mm').lastPath || '/');
                        this._debouncedCheckSelected();
                        this._setupOffcanvasScroll();
                    });
                }
            });

            this.$watch('$store.mm.selected.length', () => {
                this._debouncedCheckSelected();
            });
        },

        _setupOffcanvasScroll() {
            const body = this.$el?.closest('.offcanvas-body');
            if (!body || body._mmScrollBound) return;
            body._mmScrollBound = true;
            const store = Alpine.store('mm');
            body.addEventListener('scroll', () => {
                store.mmScrolled = body.scrollTop > 200;
            });
        },

        /**
         * Debounced wrapper around checkSelectedExist.
         * Waits 300ms before executing; cancels previous pending check.
         */
        _debouncedCheckSelected() {
            if (this._checkTimer) {
                clearTimeout(this._checkTimer);
            }
            this._checkTimer = setTimeout(() => {
                this._checkTimer = null;
                this.checkSelectedExist();
            }, 300);
        },

        /** @returns {string} */
        get csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        /** @returns {Object<string, string>} */
        get ajaxHeaders() {
            return {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            };
        },

        /**
         * Load files for a given path.
         * @param {string} path
         * @param {string|null} view
         */
        async loadFiles(path = '/', view = null) {
            if (this._loadAbort) {
                this._loadAbort.abort();
            }
            this._loadAbort = new AbortController();

            if (this._scrollTimeout) {
                clearTimeout(this._scrollTimeout);
                this._scrollTimeout = null;
            }

            this.loading = true;
            path = path || '/';
            view = view || this.view;

            try {
                const params = new URLSearchParams({ path, view });
                const store = Alpine.store('mm');

                if (store.allowedTypes.length) {
                    store.allowedTypes.forEach(t => params.append('types[]', t));
                }
                if (store.allowedExtensions.length) {
                    store.allowedExtensions.forEach(e => params.append('extensions[]', e));
                }

                const response = await fetch(this.urls.index + '?' + params.toString(), {
                    headers: this.ajaxHeaders,
                    signal: this._loadAbort.signal,
                });
                const data = await response.json();

                if (data.status) {
                    this.files = data.files;
                    this.navigation = data.navigation;
                    this.urls = { ...this.urls, ...data.urls };
                    this.path = data.path;
                    this.view = data.view;
                    this.jumpPath = data.path;
                    Alpine.store('mm').lastPath = data.path;
                } else {
                    this.toast(data.message || 'Error', 'error');
                }
            } catch (e) {
                if (e.name === 'AbortError') {
                    return;
                }
                this.toast(e.message || 'Request failed', 'error');
            }

            this.loading = false;

            if (this.highlightPath) {
                this.$nextTick(() => {
                    this._scrollTimeout = setTimeout(() => {
                        this._scrollTimeout = null;
                        this.scrollToHighlighted();
                    }, 150);
                });
            }
        },

        /**
         * Navigate into a directory.
         * @param {Object} item
         */
        navigate(item) {
            if (!item.isDir) {
                return;
            }
            this.loadFiles(this.extractPathFromUrl(item.link));
        },

        /** Reload current directory. */
        refresh() {
            this.loadFiles(this.path);
        },

        /**
         * Switch between table/list view.
         * @param {string} viewType
         */
        switchView(viewType) {
            this.view = viewType;
            this.loadFiles(this.path, viewType);
        },

        /** Navigate to the path in the quick-jump input. */
        quickJump() {
            const p = this.jumpPath.trim();
            if (p) {
                this.loadFiles(p);
            }
        },

        /**
         * Extract the path parameter from a URL.
         * @param {string} url
         * @returns {string}
         */
        extractPathFromUrl(url) {
            try {
                const u = new URL(url, window.location.origin);
                return u.searchParams.get('path') || '/';
            } catch {
                return '/';
            }
        },

        navigateToFile(filePath) {
            if (this._highlightTimeout) {
                clearTimeout(this._highlightTimeout);
                this._highlightTimeout = null;
            }
            this.highlightPath = filePath;
            const parts = filePath.split('/');
            parts.pop();
            this.loadFiles('/' + parts.join('/').replace(/^\//, ''));
        },

        scrollToHighlighted() {
            const hp = this.highlightPath;

            if (!hp) {
                return;
            }

            const id = hp.replace(/[^a-zA-Z0-9]/g, '_');
            const el = document.getElementById('oc-file-' + id)
                || document.getElementById('file-' + id);

            if (el) {
                const container = el.closest('.offcanvas-body') || el.closest('[style*="overflow"]');
                if (container) {
                    const containerRect = container.getBoundingClientRect();
                    const elRect = el.getBoundingClientRect();
                    const offset = elRect.top - containerRect.top + container.scrollTop - container.clientHeight / 2 + el.clientHeight / 2;
                    container.scrollTo({ top: offset, behavior: 'smooth' });
                } else {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            if (this._highlightTimeout) {
                clearTimeout(this._highlightTimeout);
            }
            this._highlightTimeout = setTimeout(() => {
                this.highlightPath = '';
                this._highlightTimeout = null;
            }, 3000);
        },

        dragSelectedStart(idx, event) {
            this.selectedDragIdx = idx;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(idx));
        },

        dropSelectedTo(idx) {
            if (this.selectedDragIdx === null || this.selectedDragIdx === idx) {
                this.selectedDragIdx = null;
                return;
            }

            Alpine.store('mm').moveSelected(this.selectedDragIdx, idx);
            this.selectedDragIdx = null;
        },

        dragSelectedEnd() {
            this.selectedDragIdx = null;
        },

        /**
         * Get the basename of a file path.
         * @param {string} path
         * @returns {string}
         */
        basename(path) {
            return path.split('/').filter(Boolean).pop() || path;
        },

        isImageUrl: mmIsImageUrl,

        /**
         * Check which selected files are broken (404).
         * Uses debounce, AbortController, and a shared cache to avoid redundant HEAD requests.
         */
        async checkSelectedExist() {
            if (this._checkAbort) {
                this._checkAbort.abort();
            }
            this._checkAbort = new AbortController();
            const signal = this._checkAbort.signal;

            const store = Alpine.store('mm');
            const cache = store._existsCache;
            const selectedPaths = store.selected.map(f => f.path);

            this.brokenSelectedPaths = this.brokenSelectedPaths.filter(p => selectedPaths.includes(p));

            const checks = store.selected.map(async (file) => {
                if (signal.aborted) return;
                if (this.brokenSelectedPaths.includes(file.path)) return;
                if (cache[file.path] === true) return;

                const exists = await mmCheckUrlExists(file.url, cache, file.path, signal);

                if (signal.aborted) return;

                if (!exists && !this.brokenSelectedPaths.includes(file.path)) {
                    this.brokenSelectedPaths.push(file.path);
                }
            });

            await Promise.all(checks);
        },

        /**
         * Download a file.
         * @param {Object} file
         */
        download(file) {
            window.open(file.download, '_blank');
        },

        // -- Modal triggers --

        openUploadModal() {
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'upload');
        },

        openNewFolderModal() {
            this.newFolderName = '';
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'new-folder');
        },

        openRenameModal(file) {
            this.renamePath = file.path;
            this.renameNew = file.path;
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'rename');
        },

        openDeleteModal(file) {
            this.deleteFiles = [file.path];
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'delete');
        },

        openUrlModal(file) {
            this.urlToShow = file.url || '';
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'url');
        },

        // -- Submit handlers --

        async submitUpload() {
            const input = document.getElementById(this.modalPrefix + 'upload-input');
            if (!input?.files.length) {
                return;
            }

            const formData = new FormData();
            for (const f of input.files) {
                formData.append('files[]', f);
            }
            formData.append('dir', this.path);

            try {
                const response = await fetch(this.urls.upload, {
                    method: 'POST',
                    body: formData,
                    headers: this.ajaxHeaders,
                });
                const data = await response.json();

                if (data.status) {
                    this.toast(data.message || 'Uploaded', 'success');
                    window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'upload');
                    input.value = '';
                    this.refresh();
                } else {
                    this.toast(data.message || 'Upload failed', 'error');
                }
            } catch (e) {
                this.toast(e.message || 'Upload failed', 'error');
            }
        },

        async submitDelete() {
            if (!this.deleteFiles.length) {
                return;
            }

            try {
                const params = new URLSearchParams();
                this.deleteFiles.forEach(f => params.append('files[]', f));

                const response = await fetch(this.urls.delete, {
                    method: 'POST',
                    body: params,
                    headers: {
                        ...this.ajaxHeaders,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                });
                const data = await response.json();

                if (data.status) {
                    Alpine.store('mm').invalidateExistsCache(...this.deleteFiles);
                    this.toast(data.message || 'Deleted', 'success');
                    window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'delete');
                    this.deleteFiles = [];
                    this.refresh();
                } else {
                    this.toast(data.message || 'Delete failed', 'error');
                }
            } catch (e) {
                this.toast(e.message || 'Delete failed', 'error');
            }
        },

        async submitRename() {
            if (!this.renameNew.trim()) {
                return;
            }

            try {
                const params = new URLSearchParams({
                    path: this.renamePath,
                    new: this.renameNew,
                });

                const response = await fetch(this.urls.move, {
                    method: 'POST',
                    body: params,
                    headers: {
                        ...this.ajaxHeaders,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                });
                const data = await response.json();

                if (data.status) {
                    Alpine.store('mm').invalidateExistsCache(this.renamePath, this.renameNew);
                    this.toast(data.message || 'Renamed', 'success');
                    window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'rename');
                    this.refresh();
                } else {
                    this.toast(data.message || 'Rename failed', 'error');
                }
            } catch (e) {
                this.toast(e.message || 'Rename failed', 'error');
            }
        },

        async submitNewFolder() {
            if (!this.newFolderName.trim()) {
                return;
            }

            try {
                const params = new URLSearchParams({
                    dir: this.path,
                    name: this.newFolderName,
                });

                const response = await fetch(this.urls['new-folder'], {
                    method: 'POST',
                    body: params,
                    headers: {
                        ...this.ajaxHeaders,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                });
                const data = await response.json();

                if (data.status) {
                    this.toast(data.message || 'Folder created', 'success');
                    window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'new-folder');
                    this.newFolderName = '';
                    this.refresh();
                } else {
                    this.toast(data.message || 'Failed to create folder', 'error');
                }
            } catch (e) {
                this.toast(e.message || 'Failed to create folder', 'error');
            }
        },

        /**
         * Show a toast notification via MoonShine UI.
         * @param {string} message
         * @param {string} type
         */
        toast(message, type) {
            window.MoonShine?.ui?.toast?.(message, type);
        },
    }));

    // =========================================================================
    // Picker — field component for selecting files via the manager
    // =========================================================================
    Alpine.data('mmPicker', (config = {}) => ({
        rawValue: config.value ?? (config.multiple ? '[]' : ''),

        multiple: config.multiple ?? false,

        baseUrl: config.baseUrl ?? '',

        broken: [],

        singleBroken: false,

        /** @type {AbortController|null} */
        _checkAbort: null,

        /** @type {number|null} */
        _checkTimer: null,

        init() {
            this.$nextTick(() => this._debouncedCheckFiles());

            this.$cleanup?.(() => {
                if (this._checkTimer) clearTimeout(this._checkTimer);
                if (this._checkAbort) this._checkAbort.abort();
            });
        },

        _debouncedCheckFiles() {
            if (this._checkTimer) clearTimeout(this._checkTimer);
            this._checkTimer = setTimeout(() => {
                this._checkTimer = null;
                this.checkFilesExist();
            }, 200);
        },

        async checkFilesExist() {
            const list = this.paths;
            if (!list.length) {
                return;
            }

            if (this._checkAbort) this._checkAbort.abort();
            this._checkAbort = new AbortController();
            const signal = this._checkAbort.signal;

            const cache = Alpine.store('mm')._existsCache;

            const checks = list.map(async (p, idx) => {
                if (signal.aborted) return;

                const url = this.multiple ? this.baseUrl + '/' + p : this.previewUrl;
                if (!url) return;

                if (cache[p] === true) return;

                const exists = await mmCheckUrlExists(url, cache, p, signal);

                if (signal.aborted) return;

                if (!exists) {
                    this.markBroken(idx);
                }
            });

            await Promise.all(checks);
        },

        get paths() {
            if (!this.rawValue) {
                return [];
            }

            if (this.multiple) {
                try {
                    return JSON.parse(this.rawValue);
                } catch {
                    return [];
                }
            }

            return [this.rawValue];
        },

        get previewUrl() {
            if (!this.rawValue || this.multiple) {
                return '';
            }

            return this.baseUrl + '/' + this.rawValue;
        },

        get hasValue() {
            if (this.multiple) {
                return this.paths.length > 0;
            }

            return !!this.rawValue;
        },

        markBroken(idx) {
            if (this.multiple) {
                if (!this.broken.includes(idx)) {
                    this.broken.push(idx);
                }
            } else {
                this.singleBroken = true;
            }
        },

        pick() {
            Alpine.store('mm').open(
                {
                    multiple: this.multiple,
                    allowedTypes: config.allowedTypes ?? [],
                    allowedExtensions: config.allowedExtensions ?? [],
                    existingPaths: this.paths,
                    baseUrl: this.baseUrl,
                },
                (result) => this.onSelected(result),
            );
        },

        clear() {
            this.rawValue = this.multiple ? '[]' : '';
            this.broken = [];
            this.singleBroken = false;
            this.syncInput();
        },

        removeAt(idx) {
            if (!this.multiple) {
                return;
            }

            const paths = [...this.paths];
            paths.splice(idx, 1);
            this.rawValue = JSON.stringify(paths);
            this.broken = this.broken.filter(i => i !== idx).map(i => (i > idx ? i - 1 : i));
            this.syncInput();
        },

        dragIdx: null,

        dragStart(idx, event) {
            this.dragIdx = idx;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(idx));
        },

        dropTo(idx) {
            if (this.dragIdx === null || this.dragIdx === idx) {
                this.dragIdx = null;
                return;
            }

            const paths = [...this.paths];
            const [moved] = paths.splice(this.dragIdx, 1);
            paths.splice(idx, 0, moved);
            this.rawValue = JSON.stringify(paths);

            // Remap broken indices so they follow their files, not positions
            this.broken = this.broken.map(i => {
                if (i === this.dragIdx) return idx;
                if (this.dragIdx < idx) {
                    // Moved forward: items in (dragIdx, idx] shift left by 1
                    return (i > this.dragIdx && i <= idx) ? i - 1 : i;
                }
                // Moved backward: items in [idx, dragIdx) shift right by 1
                return (i >= idx && i < this.dragIdx) ? i + 1 : i;
            });

            this.syncInput();
            this.dragIdx = null;
        },

        dragEnd() {
            this.dragIdx = null;
        },

        /**
         * Handle selection from the media manager.
         * @param {string|string[]|null} result
         */
        onSelected(result) {
            if (this.multiple) {
                this.rawValue = JSON.stringify(result ?? []);
            } else {
                this.rawValue = result ?? '';
            }

            this.syncInput();
        },

        /** Sync the hidden input value so the form submits correctly. */
        syncInput() {
            const input = this.$el?.querySelector('input[type="hidden"]');
            if (input) {
                input.value = this.rawValue;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },

        isImageUrl: mmIsImageUrl,

        /**
         * Get file extension from a path.
         * @param {string} path
         * @returns {string}
         */
        fileExt(path) {
            if (!path) return '';
            const parts = path.split('.');
            return parts.length > 1 ? parts.pop().toLowerCase() : '';
        },
    }));
});

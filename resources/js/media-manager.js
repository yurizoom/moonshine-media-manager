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
        mmCacheSet(cache, path, exists);
        return exists;
    } catch {
        if (signal?.aborted) return false;
        mmCacheSet(cache, path, false);
        return false;
    }
}

const MM_EXISTS_CACHE_LIMIT = 200;

function mmCacheSet(cache, key, value) {
    const keys = Object.keys(cache);
    if (keys.length >= MM_EXISTS_CACHE_LIMIT) {
        // Evict oldest ~25% of entries to amortise the cost over many insertions.
        const evictCount = Math.max(1, Math.floor(MM_EXISTS_CACHE_LIMIT * 0.25));
        for (let i = 0; i < evictCount; i++) {
            delete cache[keys[i]];
        }
    }
    cache[key] = value;
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
        imagePreviewSrc: '',

        replacePath: '',
        replaceFileName: '',
        replacePreview: '',
        pendingReplace: null,

        movePath: '',
        moveBrowserPath: '/',
        moveBrowserFolders: [],
        moveBrowserLoading: false,

        formError: '',

        pendingUploads: [],

        // -- Search / filter / sort --
        searchQuery: '',
        typeFilter: 'all',
        sortField: 'name',
        sortDir: 'asc',

        isDragOver: false,

        isSubmitting: false,

        init() {
            this.$nextTick(() => this.loadFiles('/'));

            const refreshHandler = () => this.refresh();
            window.addEventListener('mm:refresh', refreshHandler);
            this.$cleanup?.(() => window.removeEventListener('mm:refresh', refreshHandler));

            // Window-level handlers so files dropped anywhere trigger upload (not only inside our root).
            // Gated on Files type so we don't break drag-drop of text/images between other elements.
            this._onDragOver = (e) => {
                if (e.dataTransfer?.types?.includes('Files')) {
                    e.preventDefault();
                }
            };
            this._onDragEnter = (e) => {
                if (! e.dataTransfer?.types?.includes('Files')) return;
                this.dragCounter++;
                this.isDragOver = true;
            };
            this._onDragLeave = () => {
                if (this.dragCounter <= 0) return;
                this.dragCounter--;
                if (this.dragCounter <= 0) {
                    this.isDragOver = false;
                    this.dragCounter = 0;
                }
            };
            this._onDrop = async (e) => {
                if (! e.dataTransfer?.types?.includes('Files')) return;
                e.preventDefault();
                this.dragCounter = 0;
                this.isDragOver = false;
                const files = Array.from(e.dataTransfer?.files ?? []);
                if (! files.length) return;
                await this.submitUpload(files);
            };

            window.addEventListener('dragover', this._onDragOver, false);
            window.addEventListener('dragenter', this._onDragEnter, false);
            window.addEventListener('dragleave', this._onDragLeave, false);
            window.addEventListener('drop', this._onDrop, false);

            this.$cleanup?.(() => {
                window.removeEventListener('dragover', this._onDragOver);
                window.removeEventListener('dragenter', this._onDragEnter);
                window.removeEventListener('dragleave', this._onDragLeave);
                window.removeEventListener('drop', this._onDrop);
            });

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
         * Parse JSON or throw on non-JSON responses (avoids cryptic "Unexpected token" on HTML error pages).
         * @param {Response} response
         * @returns {Promise<any>}
         */
        async _parseJsonResponse(response) {
            const text = await response.text();

            try {
                return JSON.parse(text);
            } catch (e) {
                if (response.status === 401 || response.status === 419) {
                    throw new Error('Session expired. Please reload the page.');
                }
                throw new Error(`Request failed (HTTP ${response.status})`);
            }
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
                const data = await this._parseJsonResponse(response);

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
         * Files after search + type filter + sort are applied.
         * Folders always pass the type filter (so navigation works during search).
         * @returns {Array}
         */
        get displayedFiles() {
            const q = this.searchQuery.trim().toLowerCase();
            const typeMap = {
                images: ['image'],
                documents: ['word', 'excel', 'ppt', 'pdf', 'text', 'code'],
                video: ['video'],
                audio: ['audio'],
                archives: ['archive'],
            };
            const allowedTypes = this.typeFilter !== 'all' ? (typeMap[this.typeFilter] || []) : null;

            let list = this.files.filter((f) => {
                if (q && ! this.basename(f.path).toLowerCase().includes(q)) {
                    return false;
                }
                if (allowedTypes && ! f.isDir && ! allowedTypes.includes(f.type)) {
                    return false;
                }
                return true;
            });

            const dir = this.sortDir === 'desc' ? -1 : 1;
            const field = this.sortField;

            return list.slice().sort((a, b) => {
                if (a.isDir !== b.isDir) {
                    return a.isDir ? -1 : 1;
                }
                let av;
                let bv;
                if (field === 'name') {
                    av = this.basename(a.path).toLowerCase();
                    bv = this.basename(b.path).toLowerCase();
                } else if (field === 'date') {
                    av = a.timeRaw ?? 0;
                    bv = b.timeRaw ?? 0;
                } else if (field === 'size') {
                    av = a.sizeBytes ?? 0;
                    bv = b.sizeBytes ?? 0;
                }
                if (av < bv) return -1 * dir;
                if (av > bv) return 1 * dir;
                return 0;
            });
        },

        clearFilters() {
            this.searchQuery = '';
            this.typeFilter = 'all';
            this.sortField = 'name';
            this.sortDir = 'asc';
        },

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
            window.open(file.download, '_blank', 'noopener,noreferrer');
        },

        // -- Modal triggers --

        openUploadModal() {
            this.formError = '';
            this.pendingUploads.forEach((p) => {
                if (p.preview) URL.revokeObjectURL(p.preview);
            });
            this.pendingUploads = [];
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'upload');
        },

        openNewFolderModal() {
            this.formError = '';
            this.newFolderName = '';
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'new-folder');
        },

        openRenameModal(file) {
            this.formError = '';
            this.renamePath = file.path;
            this.renameNew = file.path;
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'rename');
        },

        openDeleteModal(file) {
            this.formError = '';
            this.deleteFiles = [file.path];
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'delete');
        },

        bulkDelete() {
            const paths = Alpine.store('mm').selected.map(f => f.path);
            if (! paths.length) {
                return;
            }
            this.formError = '';
            this.deleteFiles = paths;
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'delete');
        },

        openUrlModal(file) {
            this.urlToShow = file.url || '';
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'url');
        },

        openImagePreview(file) {
            this.imagePreviewSrc = file.url || '';
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'image-preview');
        },

        openReplaceModal(file) {
            this.formError = '';
            this.replacePath = file.path;
            this.replaceFileName = this.basename(file.path);
            this.replacePreview = file.type === 'image' ? file.url : '';
            if (this.pendingReplace?.preview) {
                URL.revokeObjectURL(this.pendingReplace.preview);
            }
            this.pendingReplace = null;
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'replace');
        },

        openMoveModal(file) {
            this.formError = '';
            this.movePath = file.path;
            this.moveBrowserPath = '/';
            this.moveBrowserFolders = [];
            window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'move');
            this.loadMoveFolders('/');
        },

        moveBrowserUp() {
            const parts = this.moveBrowserPath.split('/').filter(Boolean);
            parts.pop();
            this.loadMoveFolders('/' + parts.join('/'));
        },

        async loadMoveFolders(path) {
            path = path || '/';
            this.moveBrowserLoading = true;
            try {
                const params = new URLSearchParams({ path, view: this.view });
                const response = await fetch(this.urls.index + '?' + params.toString(), {
                    headers: this.ajaxHeaders,
                });
                const data = await this._parseJsonResponse(response);
                if (data.status) {
                    this.moveBrowserFolders = (data.files || []).filter((f) => f.isDir);
                    this.moveBrowserPath = data.path;
                }
            } catch (e) {
            }
            this.moveBrowserLoading = false;
        },

        get moveDestinationPath() {
            const filename = this.basename(this.movePath);
            const base = this.moveBrowserPath === '/' ? '' : this.moveBrowserPath;
            return base + '/' + filename;
        },

        async submitMove() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            try {
                const params = new URLSearchParams({
                    path: this.movePath,
                    new: this.moveDestinationPath,
                });
                const response = await fetch(this.urls.move, {
                    method: 'POST',
                    body: params,
                    headers: {
                        ...this.ajaxHeaders,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                });
                const data = await this._parseJsonResponse(response);
                if (data.status) {
                    Alpine.store('mm').invalidateExistsCache(this.movePath, this.moveDestinationPath);
                    const newVersion = Date.now();
                    Alpine.store('mm').selected.forEach((item) => {
                        if (item.path === this.movePath) {
                            item.path = this.moveDestinationPath;
                            const cleanUrl = (item.url || '').split('?')[0];
                            const cleanUrlNoCache = cleanUrl.replace(/\/[^/]+$/, '/' + this.basename(this.moveDestinationPath));
                            item.url = cleanUrlNoCache + '?v=' + newVersion;
                        }
                    });
                    window.dispatchEvent(new CustomEvent('mm:replaced', { detail: { path: this.moveDestinationPath } }));
                    this.toast(data.message || 'Moved', 'success');
                    this.formError = '';
                    window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'move');
                    this.refresh();
                } else {
                    this.formError = data.message || 'Move failed';
                }
            } catch (e) {
                this.formError = e.message || 'Move failed';
            } finally {
                this.isSubmitting = false;
            }
        },

        addReplaceFile(fileList) {
            const file = Array.from(fileList ?? [])[0];
            if (! file) return;
            if (this.pendingReplace?.preview) {
                URL.revokeObjectURL(this.pendingReplace.preview);
            }
            this.pendingReplace = {
                file,
                name: file.name,
                size: file.size,
                isImage: mmIsImageUrl(file.name),
                preview: mmIsImageUrl(file.name) ? URL.createObjectURL(file) : '',
            };
        },

        clearReplaceFile() {
            if (this.pendingReplace?.preview) {
                URL.revokeObjectURL(this.pendingReplace.preview);
            }
            this.pendingReplace = null;
        },

        async submitReplace() {
            if (this.isSubmitting) return;
            if (! this.pendingReplace) {
                return;
            }
            this.isSubmitting = true;
            try {
                const formData = new FormData();
                formData.append('path', this.replacePath);
                formData.append('file', this.pendingReplace.file);

                const response = await fetch(this.urls.replace, {
                    method: 'POST',
                    body: formData,
                    headers: this.ajaxHeaders,
                });
                const data = await this._parseJsonResponse(response);

                if (data.status) {
                    Alpine.store('mm').invalidateExistsCache(this.replacePath);
                    const newVersion = Date.now();
                    Alpine.store('mm').selected.forEach((item) => {
                        if (item.path === this.replacePath) {
                            const cleanUrl = (item.url || '').split('?')[0];
                            item.url = cleanUrl + '?v=' + newVersion;
                        }
                    });
                    window.dispatchEvent(new CustomEvent('mm:replaced', { detail: { path: this.replacePath } }));
                    this.toast(data.message || 'Replaced', 'success');
                    this.clearReplaceFile();
                    this.formError = '';
                    window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'replace');
                    this.refresh();
                } else {
                    this.formError = data.message || 'Replace failed';
                }
            } catch (e) {
                this.formError = e.message || 'Replace failed';
            } finally {
                this.isSubmitting = false;
            }
        },

        // -- Submit handlers --

        async submitUpload(fileList = null) {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            try {
                await this._doUpload(fileList);
            } finally {
                this.isSubmitting = false;
            }
        },

        async _doUpload(fileList) {
            const isDirectDrop = fileList !== null;
            const input = isDirectDrop ? null : document.getElementById(this.modalPrefix + 'upload-input');
            const files = isDirectDrop ? fileList : this.pendingUploads.map((p) => p.file);

            const formData = new FormData();
            for (const f of files) {
                formData.append('files[]', f);
            }
            formData.append('dir', this.path);

            try {
                const response = await fetch(this.urls.upload, {
                    method: 'POST',
                    body: formData,
                    headers: this.ajaxHeaders,
                });
                const data = await this._parseJsonResponse(response);

                if (data.status) {
                    this.toast(data.message || 'Uploaded', 'success');
                    this.formError = '';
                    if (! isDirectDrop) {
                        this.pendingUploads.forEach((p) => {
                            if (p.preview) URL.revokeObjectURL(p.preview);
                        });
                        this.pendingUploads = [];
                        if (input) input.value = '';
                        window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'upload');
                    }
                    this.refresh();
                } else {
                    this.formError = data.message || 'Upload failed';
                    if (isDirectDrop) {
                        this.toast(data.message || 'Upload failed', 'error');
                    }
                }
            } catch (e) {
                this.formError = e.message || 'Upload failed';
                if (isDirectDrop) {
                    this.toast(e.message || 'Upload failed', 'error');
                }
            }
        },

        addPendingFiles(fileList) {
            for (const f of Array.from(fileList ?? [])) {
                const isImage = mmIsImageUrl(f.name);
                this.pendingUploads.push({
                    id: Math.random().toString(36).slice(2),
                    file: f,
                    name: f.name,
                    size: f.size,
                    isImage,
                    preview: isImage ? URL.createObjectURL(f) : '',
                });
            }
        },

        removePendingUpload(id) {
            const item = this.pendingUploads.find((p) => p.id === id);
            if (item?.preview) {
                URL.revokeObjectURL(item.preview);
            }
            this.pendingUploads = this.pendingUploads.filter((p) => p.id !== id);
        },

        formatBytes(bytes) {
            if (! bytes) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
        },

        isDragOver: false,
        dragCounter: 0,

        async handleDrop(event) {
            this.dragCounter = 0;
            this.isDragOver = false;
            const files = Array.from(event?.dataTransfer?.files ?? []);
            if (! files.length) {
                return;
            }
            await this.submitUpload(files);
        },

        async submitDelete() {
            if (this.isSubmitting) return;
            if (! this.deleteFiles.length) {
                return;
            }
            this.isSubmitting = true;
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
                const data = await this._parseJsonResponse(response);

                if (data.status) {
                    Alpine.store('mm').invalidateExistsCache(...this.deleteFiles);
                    Alpine.store('mm').selected = Alpine.store('mm').selected.filter(
                        f => ! this.deleteFiles.includes(f.path)
                    );
                    this.toast(data.message || 'Deleted', 'success');
                    this.formError = '';
                    window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'delete');
                    this.deleteFiles = [];
                    this.refresh();
                } else {
                    this.formError = data.message || 'Delete failed';
                }
            } catch (e) {
                this.formError = e.message || 'Delete failed';
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitRename() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
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
                const data = await this._parseJsonResponse(response);

                if (data.status) {
                    Alpine.store('mm').invalidateExistsCache(this.renamePath, this.renameNew);
                    this.toast(data.message || 'Renamed', 'success');
                    this.formError = '';
                    window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'rename');
                    this.refresh();
                } else {
                    this.formError = data.message || 'Rename failed';
                }
            } catch (e) {
                this.formError = e.message || 'Rename failed';
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitNewFolder() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
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
                const data = await this._parseJsonResponse(response);

                if (data.status) {
                    this.toast(data.message || 'Folder created', 'success');
                    this.formError = '';
                    window.MoonShine?.ui?.toggleModal(this.modalPrefix + 'new-folder');
                    this.newFolderName = '';
                    this.refresh();
                } else {
                    this.formError = data.message || 'Failed to create folder';
                }
            } catch (e) {
                this.formError = e.message || 'Failed to create folder';
            } finally {
                this.isSubmitting = false;
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

        previewVersion: 0,

        /** @type {AbortController|null} */
        _checkAbort: null,

        /** @type {number|null} */
        _checkTimer: null,

        init() {
            this.$nextTick(() => this._debouncedCheckFiles());

            // Bump preview cache-buster when a file we care about is replaced elsewhere.
            this._onReplaced = (e) => {
                const replacedPath = e?.detail?.path;
                const myPaths = this.paths;
                if (! replacedPath || ! myPaths.includes(replacedPath)) {
                    return;
                }
                this.previewVersion++;
            };
            window.addEventListener('mm:replaced', this._onReplaced);

            this.$cleanup?.(() => {
                if (this._checkTimer) clearTimeout(this._checkTimer);
                if (this._checkAbort) this._checkAbort.abort();
                window.removeEventListener('mm:replaced', this._onReplaced);
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
            if (! this.rawValue || this.multiple) {
                return '';
            }

            return this.urlForPath(this.rawValue);
        },

        urlForPath(path) {
            const base = this.baseUrl + '/' + path;
            return this.previewVersion > 0 ? base + '?v=' + this.previewVersion : base;
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

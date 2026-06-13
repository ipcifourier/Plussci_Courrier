<x-filament-panels::page>
    @once
        <style>
            .afm-root {
                --afm-ink: #0f172a;
                --afm-muted: #475569;
                --afm-card: #ffffff;
                --afm-line: #dbe4ef;
                --afm-soft: #f8fafc;
                --afm-accent: #0f766e;
                --afm-accent-soft: #ecfeff;
                --afm-radius: 16px;
                --afm-gap: 1rem;
            }

            .afm-root.afm-style-minimal {
                --afm-soft: #f8fafc;
                --afm-line: #dbe4ef;
                --afm-accent-soft: #f8fafc;
            }

            .afm-root.afm-style-visual {
                --afm-soft: #eef2ff;
                --afm-line: #c7d2fe;
                --afm-accent-soft: #f0f9ff;
            }

            .afm-root select,
            .afm-root input,
            .afm-root textarea,
            .afm-root th,
            .afm-root td,
            .afm-root .afm-strong {
                color: var(--afm-ink) !important;
            }

            .afm-root .fi-section-header-heading,
            .afm-root .fi-section-header-description,
            .afm-root .fi-section-content,
            .afm-root .fi-section-content p,
            .afm-root .fi-section-content span,
            .afm-root .fi-section-content div,
            .afm-root .fi-input-wrp,
            .afm-root .fi-input-wrp * {
                color: var(--afm-ink) !important;
            }

            .afm-root .afm-muted {
                color: var(--afm-muted) !important;
            }

            .afm-root .afm-link {
                color: #1d4ed8 !important;
            }

            .afm-root .afm-badge-pending {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                border-radius: 999px;
                background: #fff7ed;
                border: 1px solid #fdba74;
                color: #9a3412 !important;
                font-size: 11px;
                font-weight: 700;
                padding: 0.15rem 0.5rem;
            }

            .afm-root .fi-btn.fi-color-gray {
                color: #111827 !important;
            }

            .afm-root .fi-section {
                border: 1px solid var(--afm-line);
                background: var(--afm-card);
                border-radius: var(--afm-radius);
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
            }

            .afm-root .afm-hero {
                border: 1px solid #99f6e4;
                border-radius: calc(var(--afm-radius) + 2px);
                padding: 1rem 1.1rem;
                background: linear-gradient(135deg, #ecfeff 0%, #f0fdfa 50%, #f8fafc 100%);
            }

            .afm-root.afm-style-visual .afm-hero {
                border-color: #a5b4fc;
                background:
                    radial-gradient(circle at 8% 12%, rgba(56, 189, 248, 0.16) 0, rgba(56, 189, 248, 0) 44%),
                    radial-gradient(circle at 92% 0%, rgba(99, 102, 241, 0.2) 0, rgba(99, 102, 241, 0) 46%),
                    linear-gradient(130deg, #eff6ff 0%, #eef2ff 45%, #f5f3ff 100%);
            }

            .afm-root .afm-kpi {
                border-radius: 999px;
                border: 1px solid #a7f3d0;
                background: #f0fdf4;
                color: #065f46 !important;
                font-size: 12px;
                font-weight: 700;
                padding: 0.25rem 0.65rem;
            }

            .afm-root.afm-style-visual .afm-kpi {
                border-color: #93c5fd;
                background: #e0f2fe;
                color: #0c4a6e !important;
            }

            .afm-root .afm-toolbar {
                border: 1px solid var(--afm-line);
                border-radius: calc(var(--afm-radius) - 2px);
                background: var(--afm-soft);
                padding: 0.75rem;
            }

            .afm-root .afm-dropzone {
                border-radius: calc(var(--afm-radius) - 2px);
                border: 1px dashed #14b8a6;
                background: #f0fdfa;
            }

            .afm-root .afm-dropzone-shell {
                border-radius: calc(var(--afm-radius) - 2px);
                border: 1.5px dashed #14b8a6;
                background: linear-gradient(145deg, #ecfeff 0%, #f8fafc 100%);
                padding: 0.95rem;
                transition: all 0.18s ease;
            }

            .afm-root .afm-dropzone-shell.afm-drop-active {
                border-color: #0f766e;
                background: linear-gradient(145deg, #ccfbf1 0%, #eef2ff 100%);
                box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.16);
            }

            .afm-root .afm-dropzone-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                margin-bottom: 0.55rem;
            }

            .afm-root .afm-dropzone-title {
                font-size: 13px;
                font-weight: 700;
                color: #0f172a !important;
            }

            .afm-root .afm-dropzone-sub {
                font-size: 12px;
                color: #475569 !important;
            }

            .afm-root .afm-file-preview-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
                gap: 0.65rem;
                margin-top: 0.8rem;
            }

            .afm-root .afm-file-preview-card {
                display: flex;
                align-items: center;
                gap: 0.55rem;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                padding: 0.45rem;
                background: #ffffff;
            }

            .afm-root .afm-file-icon {
                width: 34px;
                height: 34px;
                border-radius: 8px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 0.02em;
                color: #0f172a !important;
                border: 1px solid #cbd5e1;
                background: #f8fafc;
                text-transform: uppercase;
                flex-shrink: 0;
            }

            .afm-root .afm-file-icon.pdf { background: #fee2e2; border-color: #fca5a5; color: #7f1d1d !important; }
            .afm-root .afm-file-icon.doc { background: #dbeafe; border-color: #93c5fd; color: #1e3a8a !important; }
            .afm-root .afm-file-icon.xls { background: #dcfce7; border-color: #86efac; color: #14532d !important; }
            .afm-root .afm-file-icon.img { background: #fae8ff; border-color: #e9d5ff; color: #581c87 !important; }
            .afm-root .afm-file-icon.zip { background: #ede9fe; border-color: #c4b5fd; color: #4c1d95 !important; }

            .afm-root .afm-file-thumb {
                width: 34px;
                height: 34px;
                border-radius: 8px;
                object-fit: cover;
                border: 1px solid #cbd5e1;
                flex-shrink: 0;
            }

            .afm-root .afm-file-preview-name {
                font-size: 12px;
                font-weight: 700;
                line-height: 1.25;
                color: #0f172a !important;
                display: -webkit-box;
                line-clamp: 2;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .afm-root .afm-file-preview-meta {
                font-size: 11px;
                color: #64748b !important;
            }

            .afm-root .afm-upload-grid {
                gap: 1rem;
            }

            .afm-root .afm-upload-grid > div {
                min-width: 0;
            }

            .afm-root .afm-inline-grid {
                gap: 0.9rem;
                align-items: end;
            }

            .afm-root .afm-field-label {
                display: block;
                margin: 0 0 0.3rem;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.02em;
                text-transform: uppercase;
                color: #334155 !important;
            }

            .afm-root .afm-folder-tile {
                border-radius: calc(var(--afm-radius) - 4px);
                border: 1px solid var(--afm-line);
                background: #ffffff;
                transition: all 0.2s ease;
            }

            .afm-root .afm-folder-tile:hover {
                border-color: #14b8a6;
                background: var(--afm-accent-soft);
                transform: translateY(-1px);
            }

            .afm-root.afm-style-visual .afm-folder-tile:hover {
                border-color: #6366f1;
                background: #eef2ff;
            }

            .afm-root .afm-file-card {
                border-radius: calc(var(--afm-radius) - 2px);
                background: #ffffff;
                transition: all 0.18s ease;
            }

            .afm-root .afm-file-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
            }

            .afm-root .afm-file-card-selected {
                border-color: #334155 !important;
                background: #ffffff !important;
                box-shadow: 0 0 0 2px rgba(51, 65, 85, 0.14);
            }

            .afm-root .afm-select-btn {
                border-radius: 999px;
                border: 1px solid #94a3b8;
                background: #ffffff;
                color: #334155 !important;
                font-size: 11px;
                font-weight: 700;
                padding: 0.2rem 0.6rem;
            }

            .afm-root .afm-row-selected {
                background: #f8fafc !important;
                box-shadow: inset 3px 0 0 #334155;
            }

            .afm-root .afm-info-title {
                text-align: left;
                font-weight: 700;
                line-height: 1.25;
                min-height: 2.5rem;
                display: -webkit-box;
                line-clamp: 2;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .afm-root .afm-info-sub {
                text-align: left;
                font-size: 12px;
                color: #475569 !important;
                min-height: 2rem;
                display: -webkit-box;
                line-clamp: 2;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .afm-root .afm-action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.25rem;
                border-radius: 10px;
                border: 1px solid #cbd5e1;
                padding: 0.3rem 0.65rem;
                font-size: 12px;
                font-weight: 700;
                text-decoration: none;
                transition: all 0.15s ease;
            }

            .afm-root .afm-action-open {
                background: #ffffff;
                border-color: #93c5fd;
                color: #1d4ed8 !important;
            }

            .afm-root .afm-action-view {
                background: #ffffff;
                border-color: #c4b5fd;
                color: #6d28d9 !important;
            }

            .afm-root .afm-action-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
            }

            .afm-root.afm-style-visual .afm-file-card {
                background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
                border-color: #dbeafe;
            }

            .afm-root .afm-table thead th {
                background: #f8fafc;
                color: #334155 !important;
                font-weight: 700;
            }

            .afm-root .afm-table tbody tr:nth-child(even) {
                background: #fcfdff;
            }

            .afm-root .afm-table tbody tr:hover {
                background: #f0fdfa;
            }

            .afm-root .afm-pill {
                border-radius: 999px;
                border: 1px solid #cbd5e1;
                padding: 0.25rem 0.6rem;
                font-size: 12px;
                color: #334155 !important;
                background: #ffffff;
            }

            .afm-root.afm-density-compact {
                --afm-radius: 12px;
                --afm-gap: 0.7rem;
            }

            .afm-root.afm-density-compact .fi-section {
                box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04);
            }

            .afm-root.afm-density-compact .afm-hero {
                padding: 0.8rem 0.9rem;
            }

            .afm-root.afm-density-compact .afm-toolbar {
                padding: 0.55rem;
            }

            .afm-root.afm-density-compact .afm-file-card {
                padding: 0.6rem;
            }

            .afm-root.afm-density-compact .afm-upload-grid {
                gap: 0.6rem;
            }

            .afm-root.afm-density-compact .afm-inline-grid {
                gap: 0.55rem;
            }

            .afm-root.afm-density-compact .afm-table th,
            .afm-root.afm-density-compact .afm-table td {
                padding-top: 0.45rem;
                padding-bottom: 0.45rem;
            }

            @media (max-width: 768px) {
                .afm-root {
                    --afm-radius: 12px;
                }

                .afm-root .afm-hero {
                    padding: 0.8rem;
                }

                .afm-root .afm-toolbar {
                    padding: 0.6rem;
                }
            }
        </style>
    @endonce

    <div
        class="afm-root space-y-4"
        x-bind:class="{
            'afm-density-compact': density === 'compact',
            'afm-density-comfort': density !== 'compact',
            'afm-style-visual': uiStyle === 'visual',
            'afm-style-minimal': uiStyle !== 'visual'
        }"
        x-on:dragover.window.prevent
        x-on:drop.window.prevent
        x-data="{
            offlineTasks: JSON.parse(localStorage.getItem('pluss_offline_tasks') || '[]'),
            offlineTaskOps: JSON.parse(localStorage.getItem('pluss_offline_task_ops') || '[]'),
            selectedIds: @entangle('selectedDocumentIds').live,
            newTask: '',
            showFilters: true,
            density: localStorage.getItem('pluss_afm_density') || 'comfort',
            uiStyle: localStorage.getItem('pluss_afm_style') || 'minimal',
            dossierTargetQuery: '',
            showAllDropTargets: false,
            droppedFiles: [],
            dropActive: false,
            dragDocumentId: null,
            queueDb: null,
            queuedOpsCount: 0,
            pendingDeleteIds: [],
            syncStatus: 'Pret',
            syncRoute: @js(route('file-manager.sync-ops')),
            taskRoutes: {
                index: @js(route('file-manager.offline-tasks.index')),
                sync: @js(route('file-manager.offline-tasks.sync')),
            },
            csrfToken: document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '',
            saveTasks() { localStorage.setItem('pluss_offline_tasks', JSON.stringify(this.offlineTasks)); },
            saveTaskOps() { localStorage.setItem('pluss_offline_task_ops', JSON.stringify(this.offlineTaskOps)); },
            normalizeTask(task) {
                const fallbackUuid = this.generateTaskUuid();
                return {
                    id: task?.id ?? null,
                    client_uuid: String(task?.client_uuid || fallbackUuid),
                    label: String(task?.label || ''),
                    done: Boolean(task?.done),
                    createdAt: task?.created_at || task?.createdAt || new Date().toISOString(),
                    updatedAt: task?.updated_at || task?.updatedAt || new Date().toISOString(),
                };
            },
            hydrateTasks(tasks) {
                this.offlineTasks = (tasks || []).map((task) => this.normalizeTask(task));
                this.saveTasks();
            },
            generateTaskUuid() {
                if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                    return window.crypto.randomUUID();
                }

                return `task-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`;
            },
            queueTaskOp(op) {
                this.offlineTaskOps.push({
                    ...op,
                    queued_at: new Date().toISOString(),
                });
                this.saveTaskOps();
            },
            compactTaskOps(operations) {
                const byUuid = new Map();

                for (const op of operations) {
                    const clientUuid = String(op?.client_uuid || '');
                    if (!clientUuid) continue;

                    if (op.type === 'delete') {
                        byUuid.set(clientUuid, { type: 'delete', client_uuid: clientUuid });
                        continue;
                    }

                    if (op.type === 'upsert') {
                        byUuid.set(clientUuid, {
                            type: 'upsert',
                            client_uuid: clientUuid,
                            label: String(op.label || ''),
                            done: Boolean(op.done),
                        });
                    }
                }

                return [...byUuid.values()];
            },
            async refreshTasksFromServer() {
                if (!navigator.onLine) {
                    return;
                }

                const response = await fetch(this.taskRoutes.index, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                this.hydrateTasks(payload?.tasks || []);
            },
            async syncTaskOps() {
                if (!navigator.onLine) {
                    return;
                }

                const operations = this.compactTaskOps(this.offlineTaskOps || []);

                if (!operations.length) {
                    await this.refreshTasksFromServer();
                    return;
                }

                const response = await fetch(this.taskRoutes.sync, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({ operations }),
                });

                if (!response.ok) {
                    this.syncStatus = 'Erreur de synchronisation des taches';
                    return;
                }

                const payload = await response.json();
                this.offlineTaskOps = [];
                this.saveTaskOps();
                this.hydrateTasks(payload?.tasks || []);
            },
            formatBytes(bytes) {
                if (!bytes || bytes < 1) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB'];
                let i = 0;
                let value = bytes;
                while (value >= 1024 && i < units.length - 1) {
                    value /= 1024;
                    i++;
                }
                return `${value.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
            },
            iconClass(file) {
                const name = (file?.name || '').toLowerCase();
                const type = (file?.type || '').toLowerCase();
                if (type.startsWith('image/')) return 'img';
                if (name.endsWith('.pdf')) return 'pdf';
                if (name.endsWith('.doc') || name.endsWith('.docx')) return 'doc';
                if (name.endsWith('.xls') || name.endsWith('.xlsx') || name.endsWith('.csv')) return 'xls';
                if (name.endsWith('.zip') || name.endsWith('.rar') || name.endsWith('.7z')) return 'zip';
                return 'file';
            },
            iconText(file) {
                const name = (file?.name || '').toLowerCase();
                const type = (file?.type || '').toLowerCase();
                if (type.startsWith('image/')) return 'IMG';
                if (name.endsWith('.pdf')) return 'PDF';
                if (name.endsWith('.doc') || name.endsWith('.docx')) return 'DOC';
                if (name.endsWith('.xls') || name.endsWith('.xlsx') || name.endsWith('.csv')) return 'XLS';
                if (name.endsWith('.zip') || name.endsWith('.rar') || name.endsWith('.7z')) return 'ZIP';
                return 'FILE';
            },
            onFilesChanged(event) {
                const files = Array.from(event?.target?.files || []);
                this.droppedFiles = files.map((file) => ({
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    iconClass: this.iconClass(file),
                    iconText: this.iconText(file),
                    previewUrl: file.type && file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
                }));
            },
            applyDroppedFiles(fileList) {
                const files = Array.from(fileList || []);

                if (!files.length) {
                    return;
                }

                const input = this.$refs.uploadInput;
                if (!input) {
                    return;
                }

                const dt = new DataTransfer();
                for (const file of files) {
                    dt.items.add(file);
                }

                input.files = dt.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            },
            handleDropUpload(event) {
                event.preventDefault();
                event.stopPropagation();
                this.dropActive = false;

                const files = event?.dataTransfer?.files;
                if (!files || !files.length) {
                    return;
                }

                this.applyDroppedFiles(files);
            },
            setDensity(mode) {
                this.density = mode === 'compact' ? 'compact' : 'comfort';
                localStorage.setItem('pluss_afm_density', this.density);
            },
            setUiStyle(style) {
                this.uiStyle = style === 'visual' ? 'visual' : 'minimal';
                localStorage.setItem('pluss_afm_style', this.uiStyle);
            },
            async init() {
                await this.initQueue();
                await this.refreshQueueIndicators();
                if (navigator.onLine) {
                    await this.flushQueue();
                    await this.syncTaskOps();
                } else {
                    this.hydrateTasks(this.offlineTasks || []);
                }
                window.addEventListener('online', async () => {
                    this.syncStatus = 'Connexion retablie, synchronisation...';
                    await this.flushQueue();
                    await this.syncTaskOps();
                });
                window.addEventListener('dragleave', () => {
                    this.dropActive = false;
                });
            },
            async initQueue() {
                this.queueDb = await new Promise((resolve, reject) => {
                    const req = indexedDB.open('pluss_file_manager', 1);
                    req.onupgradeneeded = (event) => {
                        const db = event.target.result;
                        if (!db.objectStoreNames.contains('ops')) {
                            db.createObjectStore('ops', { keyPath: 'id', autoIncrement: true });
                        }
                    };
                    req.onsuccess = () => resolve(req.result);
                    req.onerror = () => reject(req.error);
                });
            },
            async addOperation(op) {
                if (!this.queueDb) return;
                await new Promise((resolve, reject) => {
                    const tx = this.queueDb.transaction('ops', 'readwrite');
                    tx.objectStore('ops').add(op);
                    tx.oncomplete = () => resolve(true);
                    tx.onerror = () => reject(tx.error);
                });
                await this.refreshQueueIndicators();
            },
            async getOperations() {
                if (!this.queueDb) return [];
                return await new Promise((resolve, reject) => {
                    const tx = this.queueDb.transaction('ops', 'readonly');
                    const req = tx.objectStore('ops').getAll();
                    req.onsuccess = () => resolve(req.result || []);
                    req.onerror = () => reject(req.error);
                });
            },
            async clearOperations() {
                if (!this.queueDb) return;
                await new Promise((resolve, reject) => {
                    const tx = this.queueDb.transaction('ops', 'readwrite');
                    tx.objectStore('ops').clear();
                    tx.oncomplete = () => resolve(true);
                    tx.onerror = () => reject(tx.error);
                });
                await this.refreshQueueIndicators();
            },
            async refreshQueueCount() {
                this.queuedOpsCount = (await this.getOperations()).length;
            },
            async refreshPendingDeleteIds() {
                const ops = await this.getOperations();
                const ids = [];

                for (const op of ops) {
                    if (op?.type === 'delete' && Array.isArray(op.document_ids)) {
                        for (const id of op.document_ids) {
                            const parsed = Number(id);
                            if (parsed) ids.push(parsed);
                        }
                    }
                }

                this.pendingDeleteIds = [...new Set(ids)];
            },
            async refreshQueueIndicators() {
                await this.refreshQueueCount();
                await this.refreshPendingDeleteIds();
            },
            handleDragStart(documentId) {
                this.dragDocumentId = Number(documentId);
            },
            async handleDropToDossier(dossierId) {
                const docId = Number(this.dragDocumentId);
                this.dragDocumentId = null;

                if (!docId || !dossierId) {
                    return;
                }

                if (!navigator.onLine) {
                    await this.addOperation({
                        type: 'move',
                        document_ids: [docId],
                        dossier_id: Number(dossierId),
                        queued_at: new Date().toISOString(),
                    });
                    this.syncStatus = 'Hors ligne: deplacement ajoute a la file';
                    return;
                }

                this.syncStatus = 'Application du deplacement...';
                await $wire.moveDocumentsToDossier(Number(dossierId), [docId]);
                this.syncStatus = 'Deplacement applique';
            },
            async deleteSelectionSmart() {
                if (!this.selectedIds?.length) {
                    this.syncStatus = 'Aucune selection a supprimer';
                    return;
                }

                const ids = [...this.selectedIds].map((id) => Number(id)).filter(Boolean);

                if (!navigator.onLine) {
                    await this.addOperation({
                        type: 'delete',
                        document_ids: ids,
                        queued_at: new Date().toISOString(),
                    });
                    this.selectedIds = [];
                    this.syncStatus = 'Hors ligne: suppression en lot mise en file';
                    return;
                }

                this.syncStatus = 'Suppression en cours...';
                await $wire.deleteSelection();
                this.selectedIds = [];
                this.syncStatus = 'Suppression appliquee';
            },
            async flushQueue() {
                if (!navigator.onLine) {
                    this.syncStatus = 'Mode hors ligne';
                    return;
                }

                const operations = await this.getOperations();
                if (!operations.length) {
                    this.syncStatus = 'Aucune operation en attente';
                    return;
                }

                this.syncStatus = `Synchronisation de ${operations.length} operation(s)...`;

                const response = await fetch(this.syncRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({
                        operations: operations.map((item) => ({
                            type: item.type,
                            document_ids: item.document_ids || [],
                            dossier_id: item.dossier_id ?? null,
                        })),
                    }),
                });

                if (!response.ok) {
                    this.syncStatus = 'Erreur de synchronisation';
                    return;
                }

                const payload = await response.json();
                await this.clearOperations();
                this.syncStatus = `Synchro terminee: ${payload.applied || 0} appliquee(s), ${payload.failed || 0} en echec`;
                await $wire.$refresh();
            },
            async addTask() {
                const label = this.newTask.trim();
                if (!label) return;

                const task = this.normalizeTask({
                    client_uuid: this.generateTaskUuid(),
                    label,
                    done: false,
                });

                this.offlineTasks.unshift(task);
                this.queueTaskOp({
                    type: 'upsert',
                    client_uuid: task.client_uuid,
                    label: task.label,
                    done: false,
                });
                this.newTask = '';
                this.saveTasks();

                if (navigator.onLine) {
                    await this.syncTaskOps();
                } else {
                    this.syncStatus = 'Tache ajoutee hors ligne';
                }
            },
            async removeTask(clientUuid) {
                const id = String(clientUuid || '');
                this.offlineTasks = this.offlineTasks.filter((task) => String(task.client_uuid) !== id);
                this.queueTaskOp({
                    type: 'delete',
                    client_uuid: id,
                });
                this.saveTasks();

                if (navigator.onLine) {
                    await this.syncTaskOps();
                } else {
                    this.syncStatus = 'Suppression de tache mise en attente';
                }
            },
            async toggleTask(clientUuid) {
                const id = String(clientUuid || '');
                let changedTask = null;

                this.offlineTasks = this.offlineTasks.map((task) => {
                    if (String(task.client_uuid) !== id) {
                        return task;
                    }

                    changedTask = { ...task, done: !task.done, updatedAt: new Date().toISOString() };

                    return changedTask;
                });

                if (changedTask) {
                    this.queueTaskOp({
                        type: 'upsert',
                        client_uuid: changedTask.client_uuid,
                        label: changedTask.label,
                        done: Boolean(changedTask.done),
                    });
                }

                this.saveTasks();

                if (navigator.onLine) {
                    await this.syncTaskOps();
                } else {
                    this.syncStatus = 'Mise a jour de tache mise en attente';
                }
            }
        }"
        x-init="init()"
        wire:poll.20s
    >
        <div class="afm-hero">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-slate-900">Gestionnaire de fichiers avancé</h2>
                    <p class="afm-muted text-sm">Interface optimisée pour trier, déplacer, supprimer et synchroniser vos documents même hors ligne.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="afm-kpi">{{ $this->documentsTotal }} fichiers filtrés</span>
                    <span class="afm-kpi">Page {{ $this->currentPage }}/{{ $this->lastPage }}</span>
                    <span class="afm-kpi" x-text="queuedOpsCount + ' opérations en file'"></span>
                    <span class="afm-kpi" x-text="pendingDeleteIds.length + ' suppressions en attente'"></span>
                    <button
                        type="button"
                        class="afm-pill"
                        x-bind:class="{ 'bg-teal-50 border-teal-300 text-teal-900': uiStyle === 'minimal' }"
                        x-on:click.prevent="setUiStyle('minimal')"
                    >
                        Epuree
                    </button>
                    <button
                        type="button"
                        class="afm-pill"
                        x-bind:class="{ 'bg-teal-50 border-teal-300 text-teal-900': uiStyle === 'visual' }"
                        x-on:click.prevent="setUiStyle('visual')"
                    >
                        Visuelle
                    </button>
                    <button
                        type="button"
                        class="afm-pill"
                        x-bind:class="{ 'bg-teal-50 border-teal-300 text-teal-900': density === 'comfort' }"
                        x-on:click.prevent="setDensity('comfort')"
                    >
                        Confort
                    </button>
                    <button
                        type="button"
                        class="afm-pill"
                        x-bind:class="{ 'bg-teal-50 border-teal-300 text-teal-900': density === 'compact' }"
                        x-on:click.prevent="setDensity('compact')"
                    >
                        Compact
                    </button>
                    <span class="afm-pill afm-muted">
                        Style: <strong class="afm-strong" x-text="uiStyle"></strong> | Densite: <strong class="afm-strong" x-text="density"></strong>
                    </span>
                </div>
            </div>
        </div>

        <x-filament::section>
            <x-slot name="heading">Pilotage rapide</x-slot>

            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="setQuickView('all')" @class(['afm-pill', 'bg-teal-50 border-teal-300 text-teal-900' => $quickView === 'all'])>Tous</button>
                    <button type="button" wire:click="setQuickView('unclassified')" @class(['afm-pill', 'bg-teal-50 border-teal-300 text-teal-900' => $quickView === 'unclassified'])>Non classés</button>
                    <button type="button" wire:click="setQuickView('ocr_pending')" @class(['afm-pill', 'bg-teal-50 border-teal-300 text-teal-900' => $quickView === 'ocr_pending'])>OCR à traiter</button>
                    <button type="button" wire:click="setQuickView('ocr_ready')" @class(['afm-pill', 'bg-teal-50 border-teal-300 text-teal-900' => $quickView === 'ocr_ready'])>OCR prêt</button>
                    <button type="button" wire:click="setQuickView('linked_to_courrier')" @class(['afm-pill', 'bg-teal-50 border-teal-300 text-teal-900' => $quickView === 'linked_to_courrier'])>Liés à un courrier</button>
                    <button type="button" wire:click="setQuickView('sensitive')" @class(['afm-pill', 'bg-teal-50 border-teal-300 text-teal-900' => $quickView === 'sensitive'])>Sensibles</button>
                </div>

                <button type="button" class="afm-pill" x-on:click="showFilters = !showFilters" x-text="showFilters ? 'Masquer les filtres' : 'Afficher les filtres'"></button>
            </div>

            <div class="afm-toolbar afm-inline-grid grid grid-cols-1 lg:grid-cols-12" x-show="showFilters" x-transition>
                <div class="lg:col-span-3">
                    <label class="afm-field-label">Recherche</label>
                    <x-filament::input.wrapper>
                        <x-filament::input wire:model.live.debounce.300ms="search" type="text" class="afm-strong" placeholder="Recherche (titre, référence, mots-clés, OCR, dossier)" />
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-2">
                    <label class="afm-field-label">Dossier</label>
                    <x-filament::input.wrapper>
                        <select wire:model.live="dossierFilter" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Tous les dossiers</option>
                            @foreach ($this->dossiers as $dossier)
                                <option value="{{ $dossier->id }}">{{ $dossier->display_label ?? $dossier->libelle }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-2">
                    <label class="afm-field-label">Type</label>
                    <x-filament::input.wrapper>
                        <select wire:model.live="typeFilter" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Tous les types</option>
                            @foreach ($this->availableTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-1">
                    <label class="afm-field-label">Confid.</label>
                    <x-filament::input.wrapper>
                        <select wire:model.live="confidentialityFilter" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Toutes</option>
                            @foreach ($this->availableConfidentialityLevels as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-1">
                    <label class="afm-field-label">Cycle</label>
                    <x-filament::input.wrapper>
                        <select wire:model.live="lifecycleFilter" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Tous</option>
                            @foreach ($this->availableLifecycleStates as $state)
                                <option value="{{ $state }}">{{ $state }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-1">
                    <label class="afm-field-label">OCR</label>
                    <x-filament::input.wrapper>
                        <select wire:model.live="ocrStatusFilter" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Tous</option>
                            @foreach ($this->availableOcrStatuses as $status => $label)
                                <option value="{{ $status }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-1">
                    <label class="afm-field-label">Classement</label>
                    <x-filament::input.wrapper>
                        <select wire:model.live="dossierStateFilter" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Tous</option>
                            <option value="with">Classés</option>
                            <option value="without">Sans dossier</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-1">
                    <label class="afm-field-label">Courrier</label>
                    <x-filament::input.wrapper>
                        <select wire:model.live="courrierLinkFilter" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Tous</option>
                            <option value="with">Lié</option>
                            <option value="without">Non lié</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-1">
                    <label class="afm-field-label">Tri</label>
                    <x-filament::input.wrapper>
                        <select wire:change="setSorting($event.target.value, '{{ $sortDirection }}')" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="created_at" @selected($sortBy === 'created_at')>Date</option>
                            <option value="titre" @selected($sortBy === 'titre')>Titre</option>
                            <option value="reference_doc" @selected($sortBy === 'reference_doc')>Référence</option>
                            <option value="type_document" @selected($sortBy === 'type_document')>Type</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-1">
                    <label class="afm-field-label">Par page</label>
                    <x-filament::input.wrapper>
                        <select wire:model.live="perPage" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-1">
                    <label class="afm-field-label">Affichage</label>
                    <div class="flex items-center gap-2">
                    <button type="button" wire:click="setViewMode('list')" @class([
                        'afm-pill',
                        'bg-teal-50 border-teal-300 text-teal-900' => $viewMode === 'list',
                    ])>
                        Liste
                    </button>
                    <button type="button" wire:click="setViewMode('gallery')" @class([
                        'afm-pill',
                        'bg-teal-50 border-teal-300 text-teal-900' => $viewMode === 'gallery',
                    ])>
                        Galerie
                    </button>
                    </div>
                </div>

                <div class="lg:col-span-12 flex justify-end">
                    <button
                        type="button"
                        class="afm-pill"
                        wire:click="resetFilters"
                    >
                        Réinitialiser filtres
                    </button>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-600">
                <span class="afm-pill">Recherche élargie: titre, référence, mots-clés, OCR et dossier</span>
                <span class="afm-pill">Destination explicite disponible pour les déplacements</span>
                <span class="afm-pill">Pont direct vers Acquisition & OCR</span>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Glisser-déposer</x-slot>

            <div class="afm-upload-grid afm-inline-grid grid grid-cols-1 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <label class="afm-field-label">Dossier cible</label>
                    <x-filament::input.wrapper>
                        <select wire:model="uploadDossierId" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Dossier cible (optionnel)</option>
                            @foreach ($this->dossiers as $dossier)
                                <option value="{{ $dossier->id }}">{{ $dossier->display_label ?? $dossier->libelle }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-4">
                    <label class="afm-field-label">Type de document</label>
                    <x-filament::input.wrapper>
                        <select wire:model="uploadTypeDocument" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            @forelse ($this->availableTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @empty
                                <option value="Document">Document</option>
                            @endforelse
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-4">
                    <label class="afm-field-label">Niveau de confidentialité</label>
                    <x-filament::input.wrapper>
                        <select wire:model="uploadConfidentiality" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="Standard">Standard</option>
                            <option value="Confidentiel">Confidentiel</option>
                            <option value="Personnel">Personnel</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-12 flex flex-wrap gap-4 text-sm">
                    <label class="inline-flex items-center gap-2 text-slate-700">
                        <input type="checkbox" wire:model.live="uploadAutoDetectType" />
                        Détection automatique du type
                    </label>
                    <label class="inline-flex items-center gap-2 text-slate-700">
                        <input type="checkbox" wire:model.live="uploadAutoSuggestDossier" />
                        Suggestion automatique du dossier GED
                    </label>
                </div>
            </div>

            @if ($this->uploadTargetContext)
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm">
                    <div class="font-semibold text-slate-900">Cible GED active</div>
                    <div class="mt-1 text-slate-700">{{ $this->uploadTargetContext['path'] }}</div>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <span class="afm-pill">{{ $this->uploadTargetContext['documents'] }} doc. cumulés</span>
                        <span class="afm-pill">{{ $this->uploadTargetContext['children'] }} sous-dossiers cumulés</span>
                        <a href="{{ $this->uploadAcquisitionUrl }}" class="afm-action-btn afm-action-open">Basculer vers Acquisition & OCR</a>
                    </div>
                </div>
            @endif

            <div
                class="afm-dropzone-shell mt-4"
                :class="dropActive ? 'afm-drop-active' : ''"
                x-on:dragenter.prevent="dropActive = true"
                x-on:dragover.prevent="dropActive = true"
                x-on:dragleave.prevent="dropActive = false"
                x-on:drop.prevent.stop="handleDropUpload($event)"
            >
                <div class="afm-dropzone-head">
                    <div>
                        <div class="afm-dropzone-title">Déposez vos fichiers ici</div>
                        <div class="afm-dropzone-sub">Les fichiers sélectionnés apparaissent ci-dessous avec leur icône de type.</div>
                    </div>
                    <span class="afm-pill" x-text="droppedFiles.length + ' fichier(s)'"></span>
                </div>

                <input
                    x-ref="uploadInput"
                    wire:model="uploadFiles"
                    type="file"
                    multiple
                    class="block w-full text-sm"
                    x-on:change="onFilesChanged($event)"
                />

                <div class="afm-muted mt-2 text-xs">
                    Déposez vos fichiers ou sélectionnez-les ici. Le gestionnaire calcule maintenant le type détecté, la disponibilité OCR et le dossier cible suggéré avant import.
                </div>

                <div class="afm-file-preview-grid" x-show="droppedFiles.length > 0">
                    <template x-for="file in droppedFiles" :key="file.name + file.size">
                        <div class="afm-file-preview-card">
                            <template x-if="file.previewUrl">
                                <img :src="file.previewUrl" alt="Aperçu" class="afm-file-thumb" />
                            </template>
                            <template x-if="!file.previewUrl">
                                <span class="afm-file-icon" :class="file.iconClass" x-text="file.iconText"></span>
                            </template>
                            <div class="min-w-0">
                                <div class="afm-file-preview-name" x-text="file.name"></div>
                                <div class="afm-file-preview-meta" x-text="formatBytes(file.size)"></div>
                            </div>
                        </div>
                    </template>
                </div>

                @if (count($this->uploadPreviewItems) > 0)
                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th class="px-3 py-2 font-semibold">Fichier</th>
                                    <th class="px-3 py-2 font-semibold">Type détecté</th>
                                    <th class="px-3 py-2 font-semibold">OCR</th>
                                    <th class="px-3 py-2 font-semibold">Dossier suggéré</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($this->uploadPreviewItems as $item)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-900">{{ $item['name'] }}</div>
                                            <div class="text-xs text-slate-500">{{ $item['size_human'] }} • {{ $item['mime_type'] }}</div>
                                        </td>
                                        <td class="px-3 py-2">{{ $item['detected_type'] }}</td>
                                        <td class="px-3 py-2">
                                            @if ($item['ocr_ready'])
                                                <span class="afm-pill">Disponible</span>
                                            @else
                                                <span class="afm-pill">Limité</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            @if ($item['target_dossier'])
                                                <div class="font-medium text-slate-900">{{ $item['target_dossier'] }}</div>
                                                <div class="text-xs text-slate-500">{{ $item['target_dossier_path'] }}</div>
                                            @else
                                                <span class="text-slate-500">Aucune suggestion</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-3 flex justify-end">
                    <x-filament::button icon="heroicon-o-arrow-up-tray" wire:click="uploadDroppedFiles" color="primary">
                        Importer les fichiers
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Gestionnaire de fenetres: dossiers cibles (drop zone)</x-slot>

            <div class="afm-muted mb-2 flex items-center justify-between gap-3 text-xs">
                <span>Glissez un document depuis la liste/galerie vers un dossier pour le deplacer.</span>
                <span>
                    File hors ligne: <strong x-text="queuedOpsCount"></strong>
                    • Suppressions en attente: <strong x-text="pendingDeleteIds.length"></strong>
                    <button type="button" class="ml-2 underline" x-on:click="flushQueue()">Synchroniser</button>
                </span>
            </div>

            <div class="mb-3 grid grid-cols-1 gap-2 lg:grid-cols-12">
                <div class="lg:col-span-6">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            x-model="dossierTargetQuery"
                            class="afm-strong"
                            placeholder="Rechercher un dossier cible (nom du dossier)"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-2">
                    <x-filament::input.wrapper>
                        <select wire:model.live="dropTargetYear" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Annee</option>
                            @foreach ($this->availableYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-2">
                    <x-filament::input.wrapper>
                        <select wire:model.live="dropTargetMonth" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Mois</option>
                            @foreach ($this->availableMonths as $monthNumber => $monthLabel)
                                <option value="{{ $monthNumber }}">{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                <div class="lg:col-span-2 flex items-center justify-start lg:justify-end">
                    @if ($this->dropTargetDossiers->count() <= 3)
                        <button
                            type="button"
                            class="afm-pill opacity-60 cursor-not-allowed"
                            disabled
                        >Tous les dossiers affiches ({{ $this->dropTargetDossiers->count() }})</button>
                    @else
                        <button
                            type="button"
                            class="afm-pill"
                            x-on:click="showAllDropTargets = !showAllDropTargets"
                            x-text="showAllDropTargets ? @js('Afficher seulement les 3 rapides') : @js('Voir tous les dossiers (' . $this->dropTargetDossiers->count() . ')')"
                        >Voir tous les dossiers ({{ $this->dropTargetDossiers->count() }})</button>
                    @endif
                </div>
            </div>

            @if ($this->dropTargetDossiers->count() > 3)
                <div class="afm-muted mb-2 text-xs" x-show="!showAllDropTargets && dossierTargetQuery.trim() === ''">
                    Mode rapide actif: 3 dossiers cibles affiches.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-4">
                @forelse ($this->dropTargetDossiers as $index => $dossier)
                    <button
                        type="button"
                        wire:click="$set('dossierFilter', {{ (int) $dossier->id }})"
                        x-on:dragover.prevent
                        x-on:drop.prevent="handleDropToDossier({{ (int) $dossier->id }})"
                        x-show="(showAllDropTargets || {{ $index }} < 3 || dossierTargetQuery.trim() !== '') && (dossierTargetQuery.trim() === '' || @js(\Illuminate\Support\Str::lower((string) ($dossier->display_label ?? $dossier->libelle))).includes(dossierTargetQuery.trim().toLowerCase()))"
                        class="afm-folder-tile px-3 py-2 text-left text-sm"
                    >
                        <div class="font-semibold text-gray-900">{{ $dossier->display_label ?? $dossier->libelle }}</div>
                        <div class="text-xs text-gray-500">
                            Deposer ici
                            @if (! empty($dossier->documents_count))
                                • {{ (int) $dossier->documents_count }} doc
                            @endif
                        </div>
                    </button>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-700">
                        Aucun dossier cible pour ce filtre annee/mois.
                    </div>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Gestionnaire de tâches hors ligne</x-slot>

            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::input.wrapper class="min-w-[16rem] flex-1">
                        <x-filament::input type="text" x-model="newTask" class="afm-strong" placeholder="Ajouter une tâche hors ligne (ex: classer dossier RH)" />
                    </x-filament::input.wrapper>
                    <button type="button" class="afm-pill" x-on:click="addTask()">Ajouter</button>
                    <span class="afm-muted text-xs" x-text="navigator.onLine ? 'En ligne' : 'Hors ligne'"></span>
                    <span class="afm-muted text-xs" x-text="syncStatus"></span>
                </div>

                <div class="space-y-2">
                    <template x-for="task in offlineTasks" :key="task.client_uuid">
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" :checked="task.done" x-on:change="toggleTask(task.client_uuid)" />
                                <span :class="task.done ? 'line-through text-gray-500' : 'text-gray-800'" x-text="task.label"></span>
                            </div>
                            <button type="button" class="text-xs text-danger-600 underline" x-on:click="removeTask(task.client_uuid)">Retirer</button>
                        </div>
                    </template>
                    <div x-show="offlineTasks.length === 0" class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-600">
                        Aucune tâche hors ligne enregistrée.
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Presse-papiers intelligent</x-slot>

            <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-end">
                <div>
                    <label class="afm-field-label">Destination explicite</label>
                    <x-filament::input.wrapper>
                        <select wire:model="moveTargetDossierId" class="fi-input afm-strong block w-full rounded-lg border-none bg-transparent text-sm">
                            <option value="">Choisir un dossier cible</option>
                            @foreach ($this->dossiers as $dossier)
                                <option value="{{ $dossier->id }}">{{ $dossier->display_label ?? $dossier->libelle }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <a href="{{ \App\Filament\Pages\AcquisitionPage::getUrl(array_filter(['dossier_id' => $moveTargetDossierId ? (string) $moveTargetDossierId : null])) }}" class="afm-action-btn afm-action-open">Vers Acquisition & OCR</a>
                </div>
                <div class="text-xs text-slate-500">Le déplacement peut désormais se faire vers une cible explicite, sans dépendre uniquement du filtre dossier.</div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-filament::button color="info" wire:click="copySelection">Copier la sélection</x-filament::button>
                <x-filament::button color="warning" wire:click="cutSelection">Couper la sélection</x-filament::button>
                <x-filament::button color="success" wire:click="pasteClipboard">Coller dans le dossier filtré</x-filament::button>
                <x-filament::button color="success" wire:click="moveSelectionToTarget">Déplacer vers la cible</x-filament::button>
                <x-filament::button color="danger" x-on:click="deleteSelectionSmart()" :disabled="!count($selectedDocumentIds)">Supprimer la sélection</x-filament::button>
                <x-filament::button color="gray" wire:click="clearSelection">Vider sélection</x-filament::button>
                <div class="afm-muted text-sm">
                    Sélection: <strong>{{ count($selectedDocumentIds) }}</strong>
                    • Presse-papiers: <strong>{{ count($clipboardDocumentIds) }}</strong> ({{ $clipboardMode }})
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Fichiers ({{ $this->documentsTotal }})</x-slot>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.65fr)_360px]">
                <div>
            @if ($viewMode === 'gallery')
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($this->documents as $document)
                        @php
                            $media = $document->currentVersion?->media;
                            $mime = $media?->mime_type;
                            $isImage = is_string($mime) && str_starts_with($mime, 'image/');
                            $isPdf = $mime === 'application/pdf';
                            $selected = in_array($document->id, $selectedDocumentIds, true);
                        @endphp

                        <div
                            draggable="true"
                            x-on:dragstart="handleDragStart({{ (int) $document->id }})"
                            @class([
                            'afm-file-card rounded-xl border p-3',
                            'afm-file-card-selected' => $selected,
                            'border-gray-200' => ! $selected,
                        ])
                        >
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <button type="button" wire:click="toggleSelection({{ $document->id }})" class="afm-select-btn">
                                    {{ $selected ? 'Désélectionner' : 'Sélectionner' }}
                                </button>
                                <span class="text-[11px] text-gray-500">{{ $document->reference_doc }}</span>
                            </div>

                            <div class="mb-2" x-show="pendingDeleteIds.includes({{ (int) $document->id }})">
                                <span class="afm-badge-pending">Suppression en attente (offline)</span>
                            </div>

                            <div class="mb-2 h-28 overflow-hidden rounded bg-gray-100">
                                @if ($isImage)
                                    <img src="{{ $media?->getUrl() }}" alt="Aperçu" class="h-full w-full object-cover" />
                                @elseif ($isPdf)
                                    <div class="flex h-full items-center justify-center text-xs font-medium text-gray-600">PDF</div>
                                @else
                                    <div class="flex h-full items-center justify-center text-xs font-medium text-gray-600">{{ strtoupper(pathinfo((string) $media?->file_name, PATHINFO_EXTENSION) ?: 'FICHIER') }}</div>
                                @endif
                            </div>

                            <div class="afm-info-title text-sm">{{ $document->titre }}</div>
                            <div class="afm-info-sub mt-1">{{ $document->dossier?->selectionLabel() ?? 'Sans dossier' }}</div>

                            <div class="mt-3 flex justify-start gap-2">
                                <a href="{{ route('filament.admin.resources.documents.view', $document) }}" class="afm-action-btn afm-action-open">Ouvrir</a>
                                @if ($media)
                                    <a href="{{ $media->getUrl() }}" target="_blank" class="afm-action-btn afm-action-view">Visionner</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                            <div class="text-sm font-semibold text-slate-700">Aucun fichier à afficher</div>
                            <div class="afm-muted mt-1 text-xs">Essayez de modifier les filtres ou la recherche.</div>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="overflow-auto rounded-lg border border-slate-200">
                    <table class="afm-table min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-600">
                                <th class="px-3 py-2">Sel.</th>
                                <th class="px-3 py-2">Référence</th>
                                <th class="px-3 py-2">Titre</th>
                                <th class="px-3 py-2">Etat offline</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Dossier</th>
                                <th class="px-3 py-2">Auteur</th>
                                <th class="px-3 py-2">Créé le</th>
                                <th class="px-3 py-2">Visionneuse</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($this->documents as $document)
                                @php
                                    $media = $document->currentVersion?->media;
                                    $selected = in_array($document->id, $selectedDocumentIds, true);
                                @endphp
                                <tr
                                    draggable="true"
                                    x-on:dragstart="handleDragStart({{ (int) $document->id }})"
                                    @class(['afm-row-selected' => $selected])
                                >
                                    <td class="px-3 py-2">
                                        <input type="checkbox" @checked(in_array($document->id, $selectedDocumentIds, true)) wire:click="toggleSelection({{ $document->id }})" />
                                    </td>
                                    <td class="px-3 py-2">{{ $document->reference_doc }}</td>
                                    <td class="px-3 py-2">
                                        <div class="max-w-[20rem] leading-5">{{ $document->titre }}</div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <template x-if="pendingDeleteIds.includes({{ (int) $document->id }})">
                                            <span class="afm-badge-pending">Suppression en attente (offline)</span>
                                        </template>
                                        <template x-if="!pendingDeleteIds.includes({{ (int) $document->id }})">
                                            <span class="afm-muted">-</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">{{ $document->type_document }}</td>
                                    <td class="px-3 py-2">{{ $document->dossier?->selectionLabel() ?? 'Sans dossier' }}</td>
                                    <td class="px-3 py-2">{{ $document->auteur?->name ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ optional($document->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap justify-center gap-2">
                                            <a href="{{ route('filament.admin.resources.documents.view', $document) }}" class="afm-action-btn afm-action-open">Ouvrir</a>
                                            @if ($media)
                                                <a href="{{ $media->getUrl() }}" target="_blank" class="afm-action-btn afm-action-view">Visionner</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-10 text-center">
                                        <div class="text-sm font-semibold text-slate-700">Aucun fichier à afficher</div>
                                        <div class="afm-muted mt-1 text-xs">Ajustez les filtres, la recherche, ou ajoutez des fichiers via Glisser-déposer.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

                <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div class="afm-muted text-xs">
                        Affichage
                        {{ $this->documentsTotal > 0 ? (($this->currentPage - 1) * $this->perPage + 1) : 0 }}
                        -
                        {{ min($this->currentPage * $this->perPage, $this->documentsTotal) }}
                        sur {{ $this->documentsTotal }}
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="afm-pill" wire:click="goToPage(1)" @disabled($this->currentPage === 1)>
                            <<
                        </button>
                        <button class="afm-pill" wire:click="previousPage" @disabled($this->currentPage === 1)>
                            Precedent
                        </button>
                        <span class="afm-pill">{{ $this->currentPage }} / {{ $this->lastPage }}</span>
                        <button class="afm-pill" wire:click="nextPage" @disabled($this->currentPage >= $this->lastPage)>
                            Suivant
                        </button>
                        <button class="afm-pill" wire:click="goToPage({{ $this->lastPage }})" @disabled($this->currentPage >= $this->lastPage)>
                            >>
                        </button>
                    </div>
                </div>
                </div>

                <aside class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Détail de la sélection</h3>

                    @if ($this->selectedDocumentPreview)
                        <div class="mt-4 space-y-4 text-sm">
                            @if ($this->selectedDocumentPreview['media_url'] && $this->selectedDocumentPreview['is_image'])
                                <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                    <img src="{{ $this->selectedDocumentPreview['media_url'] }}" alt="Aperçu" class="h-48 w-full object-cover" />
                                </div>
                            @else
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-center font-semibold text-slate-700">
                                    {{ $this->selectedDocumentPreview['extension'] }}
                                </div>
                            @endif

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Titre</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ $this->selectedDocumentPreview['titre'] }}</div>
                            </div>

                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Référence</div>
                                    <div class="mt-1 text-slate-700">{{ $this->selectedDocumentPreview['reference'] ?: '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Dossier GED</div>
                                    <div class="mt-1 text-slate-700">{{ $this->selectedDocumentPreview['dossier_path'] ?: 'Sans dossier' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">OCR</div>
                                    <div class="mt-1 text-slate-700">{{ $this->selectedDocumentPreview['ocr_status'] }}</div>
                                </div>
                                @if($this->selectedDocumentPreview['ocr_text_excerpt'])
                                <div class="col-span-2">
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Texte OCR extrait</div>
                                    <div class="mt-1 max-h-28 overflow-y-auto rounded-lg bg-slate-50 p-2 text-xs leading-5 text-slate-700 font-mono whitespace-pre-wrap border border-slate-200">{{ $this->selectedDocumentPreview['ocr_text_excerpt'] }}{{ mb_strlen($this->selectedDocumentPreview['ocr_text_excerpt'] ?? '') >= 500 ? ' …' : '' }}</div>
                                </div>
                                @endif
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Auteur</div>
                                    <div class="mt-1 text-slate-700">{{ $this->selectedDocumentPreview['auteur'] ?: '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Créé le</div>
                                    <div class="mt-1 text-slate-700">{{ $this->selectedDocumentPreview['created_at'] ?: '-' }}</div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ $this->selectedDocumentPreview['view_url'] }}" class="afm-action-btn afm-action-open">Ouvrir</a>
                                <a href="{{ $this->selectedDocumentPreview['acquisition_url'] }}" class="afm-action-btn afm-action-view">Acquisition & OCR</a>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                            Sélectionnez un document dans la liste ou la galerie pour afficher ses détails, son statut OCR et son rattachement GED.
                        </div>
                    @endif
                </aside>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>

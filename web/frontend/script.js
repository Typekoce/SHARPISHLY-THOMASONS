// ================================================
// SHARPISHLY-THOMASONS SPA – Neural Pipeline v3
// ================================================

const Model = {
    queue: [],               // { name, status, file?, jobId?, overallProgress, completedStages, currentStage }
    isUploading: false,
    currentPage: 'home',

    pipelineStages: [
        { id: 'upload',  label: 'Uploading',      icon: '↑' },
        { id: 'chunk',   label: 'Chunking',       icon: '✂' },
        { id: 'embed',   label: 'Embedding',      icon: '🧠' },
        { id: 'index',   label: 'Vector Storage', icon: '💾' }
    ]
};

const View = {
    home: () => `
        <div class="text-center py-8 fade-in">
            <h1 class="display-4 fw-bold mb-4">Thomasons v3</h1>
            <p class="lead text-muted mx-auto mb-5" style="max-width: 680px;">
                Professional neural pipeline: from raw CSV/TXT → clean chunks → embeddings → vector search.
            </p>
            <button class="btn btn-primary btn-lg px-5 shadow" data-page="llm">
                Start Processing
            </button>
        </div>`,

    llm: () => `
        <div class="fade-in">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Neural Pipeline</h2>
                    <p class="text-muted mb-0">Upload files → watch them transform in real time.</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Sidebar stats -->
                <div class="col-lg-3">
                    <div class="card shadow-sm bg-dark text-white p-4 sticky-top" style="top: 1rem;">
                        <h6 class="text-uppercase small fw-bold text-secondary mb-3">Pipeline Status</h6>
                        <div id="statsSummary" class="fs-5 fw-bold">Ready • 0 files</div>
                        <hr class="border-secondary my-3">
                        <small class="text-secondary">Connected to: <strong>sharpishly.vm</strong></small>
                    </div>
                </div>

                <!-- Main area -->
                <div class="col-lg-9">
                    <div class="card shadow-sm p-5 text-center mb-4 dropzone" id="dropzone">
                        <h4 class="mb-3">Drop CSV / TXT files here</h4>
                        <p class="text-muted mb-4">or click to select • max 50 MB per file</p>
                        <input type="file" id="fileInput" multiple hidden accept=".csv,.txt">
                        <button class="btn btn-primary px-5 py-3">Select Files</button>
                    </div>

                    <div id="uploadQueue" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>`,

    renderQueueItem: (item) => {
        const { name, status, overallProgress = 0, completedStages = [], currentStage } = item;
        const isActive = status === 'Processing';
        const isDone   = status === 'Complete' || status === 'Failed';
        const stage    = Model.pipelineStages.find(s => s.id === currentStage) || {};

        return `
            <div class="card shadow-sm queue-item ${isActive ? 'border-primary border-2' : isDone ? 'opacity-75' : ''}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold text-truncate">${name}</h6>
                            <small class="text-${isDone ? (status === 'Complete' ? 'success' : 'danger') : 'primary'} fw-bold">
                                ${status}${currentStage ? ` • ${stage.label}` : ''}
                            </small>
                        </div>
                        <span class="badge ${isDone ? (status === 'Complete' ? 'bg-success' : 'bg-danger') : 'bg-secondary'}">
                            ${status}
                        </span>
                    </div>

                    <div class="progress mb-3" style="height: 10px; background: #e9ecef;">
                        <div class="progress-bar ${isActive ? 'progress-bar-striped progress-bar-animated' : ''} ${isDone ? 'bg-success' : 'bg-info'}"
                             style="width: ${overallProgress}%"></div>
                    </div>

                    <div class="d-flex justify-content-between small fw-bold text-uppercase" style="font-size: 0.7rem;">
                        ${Model.pipelineStages.map(s => {
                            const done = completedStages.includes(s.id);
                            const curr = currentStage === s.id;
                            return `
                                <span style="opacity: ${done || curr ? 1 : 0.35}">
                                    ${curr ? '●' : (done ? '✓' : '○')} ${s.icon} ${s.label}
                                </span>`;
                        }).join('')}
                    </div>

                    <!-- Live logs per file (optional – can be toggled) -->
                    <div id="logs-${name.replace(/\W/g,'')}" class="mt-3 small text-muted"></div>
                </div>
            </div>`;
    }
};

const Controller = {
    init() {
        document.addEventListener('click', e => {
            const btn = e.target.closest('[data-page]');
            if (btn) {
                e.preventDefault();
                this.navigate(btn.dataset.page);
            }
        });
        this.navigate('home');
    },

    navigate(page) {
        Model.currentPage = page;
        document.getElementById('app').innerHTML = View[page]?.() ?? View.home();
        if (page === 'llm') this.initUploadArea();
    },

    initUploadArea() {
        const dropzone = document.getElementById('dropzone');
        const input    = document.getElementById('fileInput');

        if (!dropzone || !input) return;

        dropzone.addEventListener('click',     () => input.click());
        input.addEventListener('change',       e  => this.addFiles(e.target.files));
        dropzone.addEventListener('dragover',  e  => { e.preventDefault(); dropzone.classList.add('bg-primary-subtle'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('bg-primary-subtle'));
        dropzone.addEventListener('drop',      e  => {
            e.preventDefault();
            dropzone.classList.remove('bg-primary-subtle');
            this.addFiles(e.dataTransfer.files);
        });
    },

    addFiles(fileList) {
        if (!fileList?.length) return;

        Array.from(fileList).forEach(file => {
            if (!['text/csv', 'text/plain'].includes(file.type)) {
                alert(`Skipping ${file.name} – only CSV/TXT supported`);
                return;
            }
            Model.queue.push({
                name: file.name,
                file,                    // keep reference for upload
                status: 'Queued',
                overallProgress: 0,
                completedStages: [],
                currentStage: null,
                jobId: null
            });
        });

        this.refreshQueue();
        this.processQueue();
    },

    refreshQueue() {
        const container = document.getElementById('uploadQueue');
        if (container) {
            container.innerHTML = Model.queue.map(View.renderQueueItem).join('');
        }

        const stats = {
            total: Model.queue.length,
            queued: Model.queue.filter(f => f.status === 'Queued').length,
            active: Model.queue.filter(f => f.status === 'Processing').length,
            done:   Model.queue.filter(f => ['Complete','Failed'].includes(f.status)).length
        };

        const summary = document.getElementById('statsSummary');
        if (summary) {
            if (stats.active > 0) {
                summary.textContent = `Processing ${stats.active} file${stats.active > 1 ? 's' : ''}...`;
            } else if (stats.done > 0) {
                summary.textContent = `${stats.done}/${stats.total} complete`;
            } else {
                summary.textContent = `${stats.total} file${stats.total !== 1 ? 's' : ''} queued`;
            }
        }
    },

    async processQueue() {
        if (Model.isUploading) return;
        Model.isUploading = true;

        while (true) {
            const item = Model.queue.find(f => f.status === 'Queued');
            if (!item) break;

            item.status = 'Processing';
            item.currentStage = 'upload';
            this.refreshQueue();

            try {
                const formData = new FormData();
                formData.append('csv_data', item.file);

                const res = await fetch('/php/upload', {  // ← your real endpoint
                    method: 'POST',
                    body: formData
                });

                if (!res.ok) throw new Error(`Upload failed: ${res.status}`);

                const data = await res.json();

                if (data.status !== 'accepted' || !data.job_id) {
                    throw new Error(data.message || 'No job ID received');
                }

                item.jobId = data.job_id;
                this.startProgressPolling(item);

            } catch (err) {
                console.error(err);
                item.status = 'Failed';
                item.currentStage = null;
                // Optional: show error in UI
                const logEl = document.getElementById(`logs-${item.name.replace(/\W/g,'')}`);
                if (logEl) logEl.innerHTML += `<div class="text-danger">Error: ${err.message}</div>`;
            }

            this.refreshQueue();
        }

        Model.isUploading = false;
    },

    startProgressPolling(item) {
        if (!item.jobId) return;

        const logContainer = document.getElementById(`logs-${item.name.replace(/\W/g,'')}`);
        if (!logContainer) return;

        const poll = async () => {
            try {
                const res = await fetch(`/php/job-status/${item.jobId}`);
                if (!res.ok) throw new Error('Status fetch failed');

                const data = await res.json();

                // Update progress from backend steps
                const stepCount = data.history?.length || 0;
                item.overallProgress = Math.min(100, stepCount * 8); // rough estimate
                item.currentStage = data.current?.includes('Embedding') ? 'embed' :
                                   data.current?.includes('Chunk') ? 'chunk' : item.currentStage;

                if (logContainer) {
                    logContainer.innerHTML = data.history
                        .slice(-5) // last 5 steps only
                        .map(s => `<div class="small">[${s.t}] ${s.m}</div>`)
                        .join('');
                }

                this.refreshQueue();

                if (['completed','failed'].includes(data.status)) {
                    clearInterval(interval);
                    item.status = data.status === 'completed' ? 'Complete' : 'Failed';
                    item.currentStage = null;
                    item.overallProgress = 100;
                    this.refreshQueue();
                }
            } catch (err) {
                console.warn('Polling error:', err);
            }
        };

        const interval = setInterval(poll, 1800);
        poll(); // immediate first poll
    }
};

// ────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => Controller.init());
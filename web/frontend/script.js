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
                <div class="col-lg-3">
                    <div class="card shadow-sm bg-dark text-white p-4 sticky-top" style="top: 1rem;">
                        <h6 class="text-uppercase small fw-bold text-secondary mb-3">Pipeline Status</h6>
                        <div id="statsSummary" class="fs-5 fw-bold">Ready • 0 files</div>
                        <hr class="border-secondary my-3">
                        <small class="text-secondary">Connected to: <strong>sharpishly-php</strong></small>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card shadow-sm p-5 text-center mb-4 dropzone" id="dropzone" style="cursor: pointer; border: 2px dashed #dee2e6;">
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
                            <small class="text-${status === 'Complete' ? 'success' : (status === 'Failed' ? 'danger' : 'primary')} fw-bold">
                                ${status}${currentStage ? ` • ${stage.label}` : ''}
                            </small>
                        </div>
                        <span class="badge ${status === 'Complete' ? 'bg-success' : (status === 'Failed' ? 'bg-danger' : 'bg-secondary')}">
                            ${status}
                        </span>
                    </div>

                    <div class="progress mb-3" style="height: 10px; background: #e9ecef;">
                        <div class="progress-bar ${isActive ? 'progress-bar-striped progress-bar-animated' : ''} ${status === 'Complete' ? 'bg-success' : (status === 'Failed' ? 'bg-danger' : 'bg-info')}"
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

                    <div id="logs-${name.replace(/\W/g,'')}" class="mt-3 p-2 bg-light rounded small text-muted border" style="min-height: 40px; font-family: monospace;">
                        Waiting for pipeline to start...
                    </div>
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
            Model.queue.push({
                name: file.name,
                file,
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
            active: Model.queue.filter(f => f.status === 'Processing').length,
            done:   Model.queue.filter(f => ['Complete','Failed'].includes(f.status)).length
        };

        const summary = document.getElementById('statsSummary');
        if (summary) {
            summary.textContent = stats.active > 0 ? `Processing ${stats.active} file(s)...` : `${stats.done}/${stats.total} complete`;
        }
    },

    async processQueue() {
        if (Model.isUploading) return;

        const item = Model.queue.find(f => f.status === 'Queued');
        if (!item) {
            Model.isUploading = false;
            return;
        }

        Model.isUploading = true;
        item.status = 'Processing';
        item.currentStage = 'upload';
        this.refreshQueue();

        try {
            const formData = new FormData();
            formData.append('csv_data', item.file); // Matches BaseController / UploadController lookup

            const res = await fetch('/php/upload', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (!res.ok || data.status === 'error') {
                throw new Error(data.message || 'Server upload rejected');
            }

            item.jobId = data.job_id;
            item.completedStages.push('upload');
            item.overallProgress = 20;
            
            this.startProgressPolling(item);

        } catch (err) {
            item.status = 'Failed';
            item.currentStage = null;
            const logEl = document.getElementById(`logs-${item.name.replace(/\W/g,'')}`);
            if (logEl) logEl.innerHTML = `<span class="text-danger">✖ ${err.message}</span>`;
        }

        this.refreshQueue();
        Model.isUploading = false;
        this.processQueue(); // Move to next item
    },

    startProgressPolling(item) {
        if (!item.jobId) return;

        const logId = `logs-${item.name.replace(/\W/g,'')}`;
        const logContainer = document.getElementById(logId);

        const poll = async () => {
            try {
                // Hits the /php/job-status/{id} alias defined in index.php
                const res = await fetch(`/php/job-status/${item.jobId}`);
                if (!res.ok) return;

                const data = await res.json();
                
                // 1. Sync Stages
                if (data.current_step?.toLowerCase().includes('chunk')) item.currentStage = 'chunk';
                if (data.current_step?.toLowerCase().includes('vector')) item.currentStage = 'embed';
                
                // 2. Sync Progress
                const steps = data.steps_json ? JSON.parse(data.steps_json) : [];
                item.overallProgress = Math.min(95, 20 + (steps.length * 10));

                // 3. Update Log View
                if (logContainer && steps.length > 0) {
                    logContainer.innerHTML = steps.slice(-3).map(s => `<div>[${s.t}] ${s.m}</div>`).join('');
                }

                // 4. Handle End States
                if (['completed', 'failed'].includes(data.status)) {
                    clearInterval(interval);
                    item.status = data.status === 'completed' ? 'Complete' : 'Failed';
                    item.overallProgress = data.status === 'completed' ? 100 : item.overallProgress;
                    item.currentStage = null;
                    if (data.status === 'completed') item.completedStages = ['upload', 'chunk', 'embed', 'index'];
                }

                this.refreshQueue();
            } catch (err) {
                console.warn('Polling error:', err);
            }
        };

        const interval = setInterval(poll, 2000);
        poll();
    }
};

window.addEventListener('DOMContentLoaded', () => Controller.init());
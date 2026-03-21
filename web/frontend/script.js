/**
 * SHARPISHLY-THOMASONS SPA
 * Minimal MVC + Neural Pipeline Controller
 */

const Model = {
    queue: [],
    isProcessing: false,
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
        <div class="text-center py-6 fade-in">
            <h1 class="display-4 fw-bold">Thomasons v2</h1>
            <p class="lead text-muted mx-auto" style="max-width: 600px;">
                A professional-grade neural pipeline for transforming raw data into searchable vector embeddings.
            </p>
            <button class="btn btn-primary btn-lg mt-3 shadow" data-page="llm">Get Started</button>
        </div>`,

    llm: () => `
        <div class="fade-in">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-bold">Neural Pipeline</h2>
                    <p class="text-muted mb-0">Drag and drop files to begin the chunking and embedding process.</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-3">
                    <div class="card shadow-sm border-0 bg-dark text-white p-4 sticky-top" style="top: 2rem;">
                        <h6 class="text-uppercase small fw-bold text-secondary">Pipeline Metrics</h6>
                        <div id="statsSummary" class="mt-3 fs-5 fw-bold">Ready</div>
                        <hr class="border-secondary">
                        <small class="text-secondary">Connected to: <strong>sharpishly.vm</strong></small>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card shadow-sm p-5 text-center mb-4 dropzone" id="dropzone">
                        <h4 class="mb-3">Drop CSV or TXT files here</h4>
                        <p class="text-muted mb-4">Files are processed locally through your neural stack.</p>
                        <input type="file" id="fileInput" multiple hidden accept=".csv,.txt">
                        <button class="btn btn-primary px-5">Select Files</button>
                    </div>

                    <div id="uploadQueue" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>`,

    renderQueueItem: (file) => {
        const progress = file.overallProgress ?? 0;
        const isActive = file.status === 'Processing';
        const isDone = file.status === 'Complete';
        const stageInfo = Model.pipelineStages.find(s => s.id === file.currentStage) || {};

        return `
            <div class="card shadow-sm queue-item fade-in ${isActive ? 'border-primary' : ''}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-1 fw-bold">${file.name}</h6>
                            <small class="text-${isDone ? 'success' : 'primary'} text-uppercase fw-bold" style="font-size: 0.7rem;">
                                ${isDone ? 'Indexed' : (isActive ? `Stage: ${stageInfo.label}` : 'Pending')}
                            </small>
                        </div>
                        <span class="badge ${isDone ? 'bg-success' : 'bg-light text-dark'} border">
                            ${file.status}
                        </span>
                    </div>

                    <div class="progress mb-3 shadow-sm" style="height: 8px;">
                        <div class="progress-bar progress-bar-striped ${isActive ? 'progress-bar-animated' : ''} ${isDone ? 'bg-success' : ''}"
                             style="width: ${progress}%"></div>
                    </div>

                    <div class="d-flex justify-content-between small text-uppercase fw-bold" style="font-size: 0.65rem;">
                        ${Model.pipelineStages.map(stage => {
                            const completed = file.completedStages?.includes(stage.id);
                            const current = file.currentStage === stage.id;
                            return `
                                <span class="${completed || current ? 'text-dark' : 'text-muted'}" style="opacity: ${completed || current ? '1' : '0.3'}">
                                    ${current ? '●' : (completed ? '✓' : '○')} ${stage.icon} ${stage.label}
                                </span>`;
                        }).join('')}
                    </div>
                </div>
            </div>`;
    }
};

const Controller = {
    init() {
        document.addEventListener('click', e => {
            const page = e.target.closest('[data-page]')?.dataset.page;
            if (page) {
                e.preventDefault();
                this.navigate(page);
            }
        });
        this.navigate('home');
    },

    navigate(page) {
        Model.currentPage = page;
        const app = document.getElementById('app');
        if (!app) return;

        app.innerHTML = View[page]?.() ?? View.home();
        if (page === 'llm') this.initUploadArea();
    },

    initUploadArea() {
        const dropzone = document.getElementById('dropzone');
        const input = document.getElementById('fileInput');

        dropzone.addEventListener('click', () => input.click());
        input.addEventListener('change', e => this.addFiles(e.target.files));

        dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
        dropzone.addEventListener('drop', e => {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            this.addFiles(e.dataTransfer.files);
        });
    },

    addFiles(fileList) {
        Array.from(fileList).forEach(file => {
            Model.queue.push({
                name: file.name,
                status: 'Queued',
                currentStage: null,
                completedStages: [],
                overallProgress: 0
            });
        });
        this.refreshQueue();
        this.startProcessing();
    },

    refreshQueue() {
        const container = document.getElementById('uploadQueue');
        if (!container) return;

        container.innerHTML = Model.queue.map(View.renderQueueItem).join('');
        
        const stats = {
            total: Model.queue.length,
            done: Model.queue.filter(f => f.status === 'Complete').length,
            active: Model.queue.filter(f => f.status === 'Processing').length
        };
        
        const summary = document.getElementById('statsSummary');
        if (summary) {
            summary.innerText = stats.active > 0 ? `Processing ${stats.active}...` : `${stats.done} / ${stats.total} Complete`;
        }
    },

    async startProcessing() {
        if (Model.isProcessing) return;
        Model.isProcessing = true;

        while (true) {
            const file = Model.queue.find(f => f.status === 'Queued');
            if (!file) break;

            file.status = 'Processing';
            this.refreshQueue();
            await this.runPipeline(file);
        }

        Model.isProcessing = false;
    },

    async runPipeline(file) {
        for (const stage of Model.pipelineStages) {
            file.currentStage = stage.id;
            this.refreshQueue();

            // Simulation of /api/scaffold hit
            await new Promise(r => setTimeout(r, 1200));

            file.completedStages.push(stage.id);
            file.overallProgress = Math.round((file.completedStages.length / Model.pipelineStages.length) * 100);
            this.refreshQueue();
        }

        file.status = 'Complete';
        file.currentStage = null;
        this.refreshQueue();
    }
};

window.addEventListener('DOMContentLoaded', () => Controller.init());

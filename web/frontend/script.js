// ================================================
// SHARPISHLY-THOMASONS SPA – Neural Pipeline v3.5
// ================================================

const Model = {
    queue: [],
    chatHistory: [],
    isUploading: false,
    uploadProgress: 0,
    currentPage: 'home',
    healthStatus: null,
    showRawHealth: false,
    pipelineStages: [
        { id: 'upload', label: 'Uploading', icon: '↑' },
        { id: 'chunk',  label: 'Chunking',  icon: '✂' },
        { id: 'embed',  label: 'Embedding', icon: '🧠' },
        { id: 'index',  label: 'Vector Storage', icon: '💾' }
    ]
};

const View = {
    render: (content) => `<div class="fade-in">${content}</div>`,

    home: () => `
        <div class="py-6 text-center">
            <h1 class="display-3 fw-bold mb-3">Thomasons <span class="text-primary">V3</span></h1>
            <p class="lead text-muted mb-5">High-Performance Neural Document Intelligence</p>
            <div class="row g-4 justify-content-center">
                <div class="col-md-4">
                    <div class="card p-5 border-0 shadow-sm dropzone" data-page="llm" style="cursor:pointer">
                        <h5 class="fw-bold mb-2">Neural Intake</h5>
                        <p class="text-muted small">Process documents</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-5 border-0 shadow-sm dropzone" data-page="health" style="cursor:pointer">
                        <h5 class="fw-bold mb-2">System Health</h5>
                        <p class="text-muted small">Infrastructure Monitor</p>
                    </div>
                </div>
            </div>
        </div>`,

    llm: () => `
        <div class="row">
            <div class="col-lg-7">
                <h2 class="fw-bold mb-4">Neural Intake</h2>
                <div id="dropzone" class="dropzone py-6 text-center shadow-sm">
                    <input type="file" id="fileInput" accept=".txt,.csv,.pdf" multiple class="d-none">
                    <div class="mb-4"><i class="display-1 text-primary">↑</i></div>
                    <h5>Click or Drop Files Here</h5>
                    <p class="text-muted small">TXT • CSV • PDF supported</p>
                    ${Model.isUploading ? `
                        <div class="mt-4 px-5">
                            <div class="progress rounded-pill" style="height: 12px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                     style="width: ${Model.uploadProgress}%"></div>
                            </div>
                            <span class="d-block mt-2 small fw-bold text-primary">
                                Uploading: ${Model.uploadProgress}%
                            </span>
                        </div>` : ''}
                </div>
            </div>
            <div class="col-lg-5">
                <h2 class="fw-bold mb-4">Live Queue</h2>
                <div id="queueList" class="queue-container">
                    ${Model.queue.length 
                        ? Model.queue.map(item => View.renderQueueItem(item)).join('') 
                        : '<p class="text-muted text-center py-4">No active jobs yet.</p>'}
                </div>
            </div>
        </div>`,

    health: () => {
        const h = Model.healthStatus;
        const badge = (val) => val 
            ? '<span class="badge bg-success">ONLINE</span>' 
            : '<span class="badge bg-danger">OFFLINE</span>';

        const ollamaActive = h && h.ollama ? h.ollama.active : false;
        const models = h && h.ollama && h.ollama.models ? h.ollama.models : [];
        const redisActive = h ? h.redis : false;
        const queueInfo = h && h.queue_info ? h.queue_info : { count: 0, keys: [] };

        return `
        <div class="max-width-800 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold m-0">Infrastructure Monitor</h2>
                    ${h?.status === 'degraded' ? '<small class="text-warning fw-bold">⚠️ SYSTEM DEGRADED</small>' : ''}
                </div>
                <button class="btn btn-sm btn-outline-secondary" id="toggleRawHealth">
                    ${Model.showRawHealth ? 'Show Dashboard' : 'View Raw JSON'}
                </button>
            </div>

            ${Model.showRawHealth && h ? `
                <div class="card border-0 shadow-sm bg-dark text-light p-3">
                    <pre class="m-0" style="font-size:11px"><code>${JSON.stringify(h, null, 4)}</code></pre>
                </div>
            ` : `
                <div class="card border-0 shadow-sm">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between p-4">
                            <strong>MySQL Database</strong> 
                            ${h ? badge(h.database) : '<span class="spinner-border spinner-border-sm"></span>'}
                        </li>
                        <li class="list-group-item p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Redis Service</strong> 
                                ${h ? badge(redisActive) : '<span class="spinner-border spinner-border-sm"></span>'}
                            </div>
                            ${redisActive ? `
                                <div class="mt-2 p-2 bg-light rounded border-start border-primary border-4">
                                    <div class="d-flex justify-content-between small">
                                        <span>Pending Neural Jobs:</span>
                                        <span class="fw-bold">${queueInfo.count}</span>
                                    </div>
                                    ${queueInfo.keys.length ? `
                                        <div class="mt-2 pt-2 border-top small text-muted font-monospace" style="font-size: 10px;">
                                            Keys: ${queueInfo.keys.slice(0, 5).join(', ')}${queueInfo.keys.length > 5 ? '...' : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}
                        </li>
                        <li class="list-group-item p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Ollama LLM Engine</strong> 
                                ${h ? badge(ollamaActive) : '<span class="spinner-border spinner-border-sm"></span>'}
                            </div>
                            ${models.length ? `
                                <div class="mt-3 p-3 bg-light rounded shadow-inner">
                                    <small class="text-uppercase fw-bold text-muted d-block mb-2" style="font-size:10px">Downloaded Models</small>
                                    ${models.map(m => `
                                        <div class="d-flex justify-content-between align-items-center small py-1">
                                            <span class="font-monospace text-primary">${m.name}</span>
                                            <span class="badge bg-light text-dark border">${(m.size / 1e6).toFixed(0)}MB</span>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : ollamaActive ? '<small class="text-muted">No models found.</small>' : ''}
                        </li>
                    </ul>
                </div>
                <div class="mt-3 text-center text-muted small">
                    Last Handshake: ${h ? new Date(h.timestamp * 1000).toLocaleTimeString() : 'Awaiting signal...'}
                </div>
            `}
        </div>`;
    },

    renderQueueItem: (item) => `
        <div class="card shadow-sm border-0 mb-3 queue-item ${item.status === 'processing' || item.status === 'uploading' ? 'border-primary' : ''}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-truncate fw-bold" style="max-width:70%">${item.name}</span>
                    <small class="text-uppercase fw-bold ${item.status === 'failed' ? 'text-danger' : 'text-primary'}">
                        ${item.status}
                    </small>
                </div>
                <div class="progress rounded-pill" style="height: 8px;">
                    <div class="progress-bar ${item.status === 'failed' ? 'bg-danger' : 'bg-primary'}" 
                         style="width: ${item.overallProgress || 0}%"></div>
                </div>
            </div>
        </div>`
};

const Controller = {
    init() {
        document.addEventListener('click', (e) => {
            if (e.target.id === 'toggleRawHealth') {
                Model.showRawHealth = !Model.showRawHealth;
                this.render();
                return;
            }

            const link = e.target.closest('[data-page]');
            if (link) {
                e.preventDefault();
                this.navigate(link.dataset.page);
            }
        });

        this.navigate('home');
        setInterval(() => this.pollQueue(), 3000);
        setInterval(() => {
            if (Model.currentPage === 'health') this.fetchHealth();
        }, 5000);
    },

    navigate(page) {
        Model.currentPage = page;
        Model.showRawHealth = false;
        this.render();

        if (page === 'llm') this.initDropzone();
        if (page === 'health') this.fetchHealth();
    },

    render() {
        const content = View[Model.currentPage] ? View[Model.currentPage]() : View.home();
        document.getElementById('app').innerHTML = View.render(content);

        document.querySelectorAll('.nav-link').forEach(el => {
            el.classList.toggle('active', el.dataset.page === Model.currentPage);
        });
    },

    initDropzone() {
        const zone = document.getElementById('dropzone');
        const input = document.getElementById('fileInput');
        if (!zone || !input) return;

        zone.onclick = () => input.click();
        zone.ondragover = (e) => { e.preventDefault(); zone.classList.add('drag-over'); };
        zone.ondragleave = () => zone.classList.remove('drag-over');
        zone.ondrop = (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            this.handleFiles(e.dataTransfer.files);
        };
        input.onchange = (e) => this.handleFiles(e.target.files);
    },

    handleFiles(fileList) {
        Array.from(fileList).forEach(file => {
            Model.queue.unshift({
                name: file.name,
                file: file,
                status: 'queued',
                overallProgress: 0,
                jobId: null
            });
        });
        this.render();
        this.processUploads();
    },

    async processUploads() {
        if (Model.isUploading) return;
        const item = Model.queue.find(i => i.status === 'queued');
        if (!item) return;

        Model.isUploading = true;
        item.status = 'uploading';
        this.render();

        const formData = new FormData();
        formData.append('file', item.file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/php/upload');

        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                Model.uploadProgress = Math.round((e.loaded / e.total) * 100);
                this.render();
            }
        };

        xhr.onload = () => {
            try {
                const res = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && res.job_id) {
                    item.jobId = res.job_id;
                    item.status = 'processing';
                } else {
                    item.status = 'failed';
                }
            } catch (e) {
                item.status = 'failed';
            }

            Model.isUploading = false;
            Model.uploadProgress = 0;
            this.render();
            this.processUploads();
        };

        xhr.onerror = () => {
            item.status = 'failed';
            Model.isUploading = false;
            this.render();
        };

        xhr.send(formData);
    },

    async fetchHealth() {
        try {
            const res = await fetch('/php/health');
            if (!res.ok) throw new Error('Health check failed');
            Model.healthStatus = await res.json();
        } catch (e) {
            console.error("Health fetch failed", e);
            Model.healthStatus = { 
                database: false, 
                redis: false, 
                ollama: { active: false, models: [] }, 
                timestamp: Date.now()/1000 
            };
        }
        if (Model.currentPage === 'health') this.render();
    },

    pollQueue() {
        if (Model.currentPage === 'llm') this.render();
    }
};

document.addEventListener('DOMContentLoaded', () => Controller.init());
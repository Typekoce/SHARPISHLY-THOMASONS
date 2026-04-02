// ================================================
// SHARPISHLY-THOMASONS SPA – Neural Pipeline v3.4
// ================================================

const Model = {
    queue: [],
    chatHistory: [],
    isUploading: false,
    uploadProgress: 0,
    currentPage: 'home',
    healthStatus: null,
    pipelineStages: [
        { id: 'upload', label: 'Uploading', icon: '↑' },
        { id: 'chunk',  label: 'Chunking',   icon: '✂' },
        { id: 'embed',  label: 'Embedding',  icon: '🧠' },
        { id: 'index',  label: 'Vector Storage', icon: '💾' }
    ]
};

const View = {
    // Dynamic Layout Wrapper (Injects into <main id="app">)
    render: (content) => `
        <div class="fade-in">
            ${content}
        </div>`,

    home: () => `
        <div class="py-6 text-center">
            <h1 class="display-3 fw-bold mb-3">Thomasons <span class="text-primary">V3</span></h1>
            <p class="lead text-muted mb-5">High-Performance Neural Document Intelligence</p>
            <div class="row g-4 justify-content-center">
                <div class="col-md-3">
                    <div class="card p-4 border-0 shadow-sm dropzone" data-page="llm">
                        <h5 class="fw-bold">Intake</h5>
                        <p class="small text-muted">Process new documents</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-4 border-0 shadow-sm dropzone" data-page="health">
                        <h5 class="fw-bold">Health</h5>
                        <p class="small text-muted">System Infrastructure</p>
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
                    <div class="mb-3"><i class="display-1 text-primary">↑</i></div>
                    <h5>Click or Drop Multiple Files</h5>
                    <p class="text-muted small">Supports PDF, CSV, and Text</p>
                    ${Model.isUploading ? `
                        <div class="mt-4 px-5">
                            <div class="progress rounded-pill" style="height: 12px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     style="width: ${Model.uploadProgress}%"></div>
                            </div>
                            <span class="d-block mt-2 small fw-bold text-primary">Uploading: ${Model.uploadProgress}%</span>
                        </div>` : ''}
                </div>
            </div>
            <div class="col-lg-5">
                <h2 class="fw-bold mb-4">Live Queue</h2>
                <div id="queueList">
                    ${Model.queue.length ? Model.queue.map(item => View.renderQueueItem(item)).join('') : '<p class="text-muted">No active jobs.</p>'}
                </div>
            </div>
        </div>`,

    health: () => {
        const h = Model.healthStatus;
        const badge = (val) => val ? '<span class="badge bg-success">ONLINE</span>' : '<span class="badge bg-danger">OFFLINE</span>';
        return `
        <div class="max-width-800 mx-auto">
            <h2 class="fw-bold mb-4">Infrastructure Monitor</h2>
            <div class="card border-0 shadow-sm">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between p-4">
                        <strong>MySQL Database</strong> ${h ? badge(h.database) : '...'}
                    </li>
                    <li class="list-group-item d-flex justify-content-between p-4">
                        <strong>Redis CNS</strong> ${h ? badge(h.redis) : '...'}
                    </li>
                    <li class="list-group-item d-flex justify-content-between p-4">
                        <strong>Ollama (Local LLM)</strong> ${h ? badge(h.ollama) : '...'}
                    </li>
                </ul>
            </div>
        </div>`;
    },

    renderQueueItem: (item) => `
        <div class="card shadow-sm border-0 mb-3 queue-item ${item.status === 'processing' ? 'border-primary' : ''}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-truncate fw-bold" style="max-width:70%">${item.name}</span>
                    <small class="text-uppercase fw-bold text-primary">${item.status}</small>
                </div>
                <div class="progress rounded-pill" style="height: 6px;">
                    <div class="progress-bar ${item.status === 'failed' ? 'bg-danger' : 'bg-primary'}" style="width: ${item.overallProgress}%"></div>
                </div>
            </div>
        </div>`
};

const Controller = {
    init() {
        // Handle Navbar Clicks
        document.addEventListener('click', (e) => {
            const link = e.target.closest('[data-page]');
            if (link) {
                e.preventDefault();
                this.navigate(link.dataset.page);
                
                // Close burger menu on mobile after click
                const navbar = document.getElementById('navbarNav');
                if (navbar.classList.contains('show')) {
                    navbar.classList.remove('show');
                }
            }
        });

        // Initial Route
        this.navigate('home');
        setInterval(() => this.pollQueue(), 3000);
    },

    navigate(page) {
        Model.currentPage = page;
        this.render();
        if (page === 'llm') this.initDropzone();
        if (page === 'health') this.fetchHealth();
    },

    render() {
        const content = View[Model.currentPage] ? View[Model.currentPage]() : View.home();
        document.getElementById('app').innerHTML = View.render(content);
        
        // Update Active States in Nav
        document.querySelectorAll('.nav-link').forEach(el => {
            el.classList.toggle('active', el.dataset.page === Model.currentPage);
        });
    },

    initDropzone() {
        const zone = document.getElementById('dropzone');
        const input = document.getElementById('fileInput');
        if (!zone) return;

        zone.onclick = () => input.click();
        
        // Visual feedback for drag and drop
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
        const files = Array.from(fileList);
        files.forEach(file => {
            const item = {
                name: file.name,
                file: file,
                status: 'queued',
                overallProgress: 0,
                jobId: null
            };
            Model.queue.unshift(item);
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
            const res = JSON.parse(xhr.responseText);
            if (xhr.status === 200 && res.job_id) {
                item.jobId = res.job_id;
                item.status = 'processing';
            } else {
                item.status = 'failed';
            }
            Model.isUploading = false;
            Model.uploadProgress = 0;
            this.render();
            this.processUploads();
        };

        xhr.send(formData);
    },

    async fetchHealth() {
        try {
            const res = await fetch('/health/check');
            Model.healthStatus = await res.json();
            this.render();
        } catch (e) {
            console.error("Health probe failed");
        }
    },

    pollQueue() {
        // Refresh job percentages from server
        if (Model.currentPage === 'llm' || Model.currentPage === 'queue') {
            // fetch('/php/queue-sync')... logic goes here
        }
    }
};

document.addEventListener('DOMContentLoaded', () => Controller.init());
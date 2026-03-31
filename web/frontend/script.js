// ================================================
// SHARPISHLY-THOMASONS SPA – Neural Pipeline v3
// ================================================

const Model = {
    queue: [],               
    chatHistory: [],         // Stores { role, content }
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
    layout: (content) => `
        <div class="container-fluid">
            <div class="row">
                <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse border-end" style="min-height: 100vh;">
                    <div class="position-sticky pt-4 px-3">
                        <h5 class="fw-bold mb-4 px-2">Thomasons AI</h5>
                        <ul class="nav flex-column gap-2">
                            <li class="nav-item">
                                <a class="nav-link btn btn-light text-start ${Model.currentPage === 'home' ? 'active shadow-sm' : ''}" data-page="home">🏠 Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn btn-light text-start ${Model.currentPage === 'llm' ? 'active shadow-sm' : ''}" data-page="llm">🚀 Pipeline</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn btn-light text-start ${Model.currentPage === 'chat' ? 'active shadow-sm' : ''}" data-page="chat">💬 Neural Chat</a>
                            </li>
                        </ul>
                    </div>
                </nav>
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-4">
                    ${content}
                </main>
            </div>
        </div>`,

    home: () => `
        <div class="text-center py-5 fade-in">
            <h1 class="display-4 fw-bold mb-4">Neural Intelligence v3</h1>
            <p class="lead text-muted mx-auto mb-5" style="max-width: 680px;">
                Upload raw data to the pipeline to enable semantic search and AI chat capabilities.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <button class="btn btn-primary btn-lg px-5 shadow" data-page="llm">Start Ingestion</button>
                <button class="btn btn-outline-secondary btn-lg px-5" data-page="chat">Try Chat</button>
            </div>
        </div>`,

    llm: () => `
        <div class="fade-in">
            <h2 class="fw-bold mb-4">Neural Pipeline</h2>
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="card shadow-sm p-5 text-center mb-4 dropzone" id="dropzone" style="cursor: pointer; border: 2px dashed #dee2e6;">
                        <h4 class="mb-3">Drop CSV / TXT files here</h4>
                        <input type="file" id="fileInput" multiple hidden accept=".csv,.txt">
                        <button class="btn btn-primary px-5 py-3">Select Files</button>
                    </div>
                    <div id="uploadQueue" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>`,

    chat: () => `
        <div class="fade-in">
            <h2 class="fw-bold mb-4">Neural Chat</h2>
            <div class="card shadow-sm">
                <div id="chatWindow" class="card-body overflow-auto p-4" style="height: 500px; background: #fdfdfd;">
                    ${Model.chatHistory.length === 0 ? 
                        `<div class="text-center text-muted mt-5"><h5>The brain is ready.</h5><p>Ask a question about your uploaded documents.</p></div>` : 
                        Model.chatHistory.map(msg => `
                            <div class="mb-4 ${msg.role === 'user' ? 'text-end' : 'text-start'}">
                                <div class="d-inline-block p-3 rounded-3 shadow-sm ${msg.role === 'user' ? 'bg-primary text-white' : 'bg-light border text-dark'}" style="max-width: 80%; white-space: pre-wrap;">
                                    ${msg.content}
                                </div>
                            </div>
                        `).join('')
                    }
                </div>
                <div class="card-footer bg-white p-3 border-top">
                    <form id="chatForm" class="input-group">
                        <input type="text" id="chatInput" class="form-control form-control-lg" placeholder="Ask a question..." autocomplete="off">
                        <button class="btn btn-primary px-4" type="submit" id="sendBtn">Ask AI</button>
                    </form>
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
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar ${isActive ? 'progress-bar-striped progress-bar-animated' : ''} ${status === 'Complete' ? 'bg-success' : (status === 'Failed' ? 'bg-danger' : 'bg-info')}"
                             style="width: ${overallProgress}%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-uppercase" style="font-size: 0.65rem;">
                        ${Model.pipelineStages.map(s => {
                            const done = completedStages.includes(s.id);
                            const curr = currentStage === s.id;
                            return `<span style="opacity: ${done || curr ? 1 : 0.3}">
                                ${curr ? '●' : (done ? '✓' : '○')} ${s.label}
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
        const content = View[page]?.() ?? View.home();
        document.getElementById('app').innerHTML = View.layout(content);
        
        if (page === 'llm') this.initUploadArea();
        if (page === 'chat') this.initChatArea();
        
        // Ensure stats summary stays updated if it's on the page
        this.refreshQueue();
    },

    initChatArea() {
        const form = document.getElementById('chatForm');
        const win = document.getElementById('chatWindow');
        if (!form) return;

        win.scrollTop = win.scrollHeight;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('chatInput');
            const btn = document.getElementById('sendBtn');
            const msg = input.value.trim();

            if (!msg || btn.disabled) return;

            // Update UI
            Model.chatHistory.push({ role: 'user', content: msg });
            input.value = '';
            btn.disabled = true;
            this.navigate('chat');

            try {
                const res = await fetch('/php/chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: msg })
                });
                const data = await res.json();
                
                Model.chatHistory.push({ role: 'ai', content: data.answer || data.error });
            } catch (err) {
                Model.chatHistory.push({ role: 'ai', content: "Connection to Neural Engine lost." });
            } finally {
                btn.disabled = false;
                this.navigate('chat');
            }
        });
    },

    initUploadArea() {
        const dropzone = document.getElementById('dropzone');
        const input    = document.getElementById('fileInput');
        if (!dropzone || !input) return;

        dropzone.onclick = () => input.click();
        input.onchange = (e) => this.addFiles(e.target.files);
        dropzone.ondragover = (e) => { e.preventDefault(); dropzone.classList.add('bg-light'); };
        dropzone.ondrop = (e) => { e.preventDefault(); this.addFiles(e.dataTransfer.files); };
    },

    addFiles(fileList) {
        if (!fileList?.length) return;
        Array.from(fileList).forEach(file => {
            Model.queue.push({ name: file.name, file, status: 'Queued', overallProgress: 0, completedStages: [], currentStage: null, jobId: null });
        });
        this.refreshQueue();
        this.processQueue();
    },

    refreshQueue() {
        const container = document.getElementById('uploadQueue');
        if (container) container.innerHTML = Model.queue.map(View.renderQueueItem).join('');
    },

    async processQueue() {
        if (Model.isUploading) return;
        const item = Model.queue.find(f => f.status === 'Queued');
        if (!item) return;

        Model.isUploading = true;
        item.status = 'Processing';
        item.currentStage = 'upload';
        this.refreshQueue();

        try {
            const formData = new FormData();
            formData.append('csv_data', item.file);
            const res = await fetch('/php/upload', { method: 'POST', body: formData });
            const data = await res.json();

            if (!res.ok || data.status === 'error') throw new Error(data.message);
            item.jobId = data.job_id;
            item.completedStages.push('upload');
            this.startProgressPolling(item);
        } catch (err) {
            item.status = 'Failed';
        }
        Model.isUploading = false;
        this.processQueue();
    },

    startProgressPolling(item) {
        const poll = async () => {
            try {
                const res = await fetch(`/php/job-status/${item.jobId}`);
                const data = await res.json();
                
                if (data.current_step?.includes('chunk')) item.currentStage = 'chunk';
                if (data.current_step?.includes('vector')) item.currentStage = 'embed';
                
                const steps = data.steps_json ? JSON.parse(data.steps_json) : [];
                item.overallProgress = Math.min(95, 20 + (steps.length * 10));

                if (['completed', 'failed'].includes(data.status)) {
                    clearInterval(interval);
                    item.status = data.status === 'completed' ? 'Complete' : 'Failed';
                    item.overallProgress = data.status === 'completed' ? 100 : item.overallProgress;
                    if (data.status === 'completed') item.completedStages = ['upload', 'chunk', 'embed', 'index'];
                }
                this.refreshQueue();
            } catch (e) {}
        };
        const interval = setInterval(poll, 2000);
    }
};

window.addEventListener('DOMContentLoaded', () => Controller.init());
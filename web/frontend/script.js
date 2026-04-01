// ================================================
// SHARPISHLY-THOMASONS SPA – Neural Pipeline v3.2
// ================================================

const Model = {
    queue: [],
    chatHistory: [],
    isUploading: false,
    currentPage: window.location.hash.replace('#', '') || 'home',
    pipelineStages: [
        { id: 'upload', label: 'Uploading', icon: '↑' },
        { id: 'chunk',  label: 'Chunking',   icon: '✂' },
        { id: 'embed',  label: 'Embedding',  icon: '🧠' },
        { id: 'index',  label: 'Vector Storage', icon: '💾' }
    ]
};

const View = {
    layout: (content) => `
        <div class="container py-4">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 shadow-sm rounded">
                <div class="container-fluid">
                    <a class="navbar-brand fw-bold" href="#">Sharpishly</a>
                    <div class="navbar-nav">
                        <a class="nav-link ${Model.currentPage === 'home' ? 'active' : ''}" href="#home">Home</a>
                        <a class="nav-link ${Model.currentPage === 'llm' ? 'active' : ''}" href="#llm">Upload</a>
                        <a class="nav-link ${Model.currentPage === 'chat' ? 'active' : ''}" href="#chat">Neural Chat</a>
                        <a class="nav-link ${Model.currentPage === 'queue' ? 'active' : ''}" href="#queue">Queue</a>
                    </div>
                </div>
            </nav>
            <div id="app-content">${content}</div>
        </div>`,

    home: () => `
        <div class="text-center py-5">
            <h1 class="display-4 fw-bold">Welcome to Sharpishly</h1>
            <p class="lead text-muted">Your Neural Document Intelligence Platform</p>
        </div>`,

    llm: () => `
        <div class="fade-in">
            <h2 class="fw-bold mb-4">Neural Document Intake</h2>
            <div id="uploadArea" class="border border-2 border-dashed rounded-4 p-5 text-center">
                <input type="file" id="fileInput" accept=".txt,.csv,.pdf" class="d-none">
                <div class="mb-3">
                    <i class="display-1 text-muted">↑</i>
                </div>
                <h5>Drop files here or click to upload</h5>
                <small class="text-muted">Supported: TXT, CSV, PDF</small>
            </div>
        </div>`,

    chat: () => `
        <div class="fade-in">
            <h2 class="fw-bold mb-4">Neural Chat</h2>
            <div class="card shadow-sm border-0">
                <div id="chatWindow" class="card-body overflow-auto p-4" style="height: 550px; background: #f8f9fa;">
                    ${Model.chatHistory.length === 0 
                        ? `<div class="text-center text-muted mt-5">
                             <h5>Neural Engine Online</h5>
                             <p>Ask questions about your indexed documents.</p>
                           </div>`
                        : Model.chatHistory.map(msg => `
                            <div class="mb-4 ${msg.role === 'user' ? 'text-end' : 'text-start'}">
                                <div class="d-inline-block p-3 rounded-4 shadow-sm ${msg.role === 'user' 
                                    ? 'bg-primary text-white' 
                                    : 'bg-white border text-dark'}" 
                                     style="max-width: 80%;">
                                    ${msg.content}
                                </div>
                            </div>
                        `).join('')}
                    <div id="typingIndicator" class="d-none text-muted small px-3 py-2">
                        🧠 Neural Worker is thinking...
                    </div>
                </div>
                <div class="card-footer bg-white p-3 border-0">
                    <form id="chatForm" class="input-group shadow-sm rounded-pill overflow-hidden">
                        <input type="text" id="chatInput" 
                               class="form-control border-0 px-4 py-3" 
                               placeholder="Query your vector space..." 
                               autocomplete="off">
                        <button class="btn btn-primary px-5" type="submit" id="sendBtn">
                            Ask AI
                        </button>
                    </form>
                </div>
            </div>
        </div>`,

    queue: () => `
        <div class="fade-in">
            <h2 class="fw-bold mb-4">Processing Queue</h2>
            <div id="queueContainer">
                ${Model.queue.length === 0 
                    ? `<div class="text-center text-muted py-5">
                         <p>No active jobs. Upload a document to begin.</p>
                       </div>`
                    : Model.queue.map(item => View.renderQueueItem(item)).join('')}
            </div>
        </div>`,

    renderQueueItem: (item) => {
        const stage = Model.pipelineStages.find(s => s.id === item.currentStage) || { label: 'Waiting' };
        const isError = item.status === 'failed';

        return `
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-truncate" style="max-width: 65%;">${item.name || 'Unnamed Document'}</strong>
                        <span class="badge ${isError ? 'bg-danger' : item.status === 'completed' ? 'bg-success' : 'bg-primary'}">
                            ${item.status.toUpperCase()}
                        </span>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated ${isError ? 'bg-danger' : ''}"
                             style="width: ${item.overallProgress || 0}%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>${stage.label}</span>
                        <span>${item.overallProgress || 0}%</span>
                    </div>
                </div>
            </div>`;
    }
};

const Controller = {
    init() {
        window.addEventListener('hashchange', () => {
            Model.currentPage = window.location.hash.replace('#', '') || 'home';
            this.render();
        });

        // Global click handler for navigation
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-page]');
            if (btn) {
                window.location.hash = btn.dataset.page;
            }
        });

        this.render();
        // Start queue polling globally
        setInterval(() => this.refreshQueue(), 3000);
    },

    render() {
        const content = View[Model.currentPage]?.() ?? View.home();
        document.getElementById('app').innerHTML = View.layout(content);

        if (Model.currentPage === 'llm') this.initUploadArea();
        if (Model.currentPage === 'chat') this.initChatArea();
    },

    refreshQueue() {
        fetch('/php/queue-status')
            .then(res => res.json())
            .then(data => {
                Model.queue = data.jobs || [];
                if (Model.currentPage === 'queue') {
                    const container = document.getElementById('queueContainer');
                    if (container) {
                        container.innerHTML = Model.queue.length 
                            ? Model.queue.map(item => View.renderQueueItem(item)).join('')
                            : `<div class="text-center text-muted py-5"><p>No active jobs.</p></div>`;
                    }
                }
            })
            .catch(() => console.warn("Queue status poll failed"));
    },

    // ======================
    // Upload & Progress Handling
    // ======================
    initUploadArea() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');

        if (!uploadArea || !fileInput) return;

        uploadArea.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const queueItem = {
                name: file.name,
                file: file,
                status: 'Queued',
                overallProgress: 0,
                currentStage: 'upload'
            };

            Model.queue.unshift(queueItem);
            this.processQueue();
            window.location.hash = 'queue'; // Switch to queue view
        });
    },

    async processQueue() {
        if (Model.isUploading) return;
        const item = Model.queue.find(f => f.status === 'Queued');
        if (!item) return;

        Model.isUploading = true;
        item.status = 'processing';
        item.currentStage = 'upload';
        this.refreshQueue();

        const formData = new FormData();
        formData.append('csv_data', item.file);   // Adjust field name to match your PHP UploadController

        try {
            const res = await fetch('/php/upload', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (data.status === 'success' && data.job_id) {
                item.jobId = data.job_id;
                this.startProgressPolling(item);
            } else {
                throw new Error(data.message || 'Upload failed');
            }
        } catch (err) {
            console.error(err);
            item.status = 'failed';
            this.refreshQueue();
        } finally {
            Model.isUploading = false;
        }
    },

    startProgressPolling(item) {
        const interval = setInterval(async () => {
            try {
                const res = await fetch(`/php/job-status/${item.jobId}`);
                const data = await res.json();

                item.overallProgress = data.progress || 0;
                item.status = data.status || 'processing';
                item.currentStage = this.mapStepToStage(data.current_step);

                if (['completed', 'failed'].includes(data.status)) {
                    clearInterval(interval);
                }

                this.refreshQueue();
            } catch (e) {
                console.error("Progress poll failed", e);
            }
        }, 1800); // Poll every 1.8 seconds
    },

    mapStepToStage(step) {
        if (!step) return 'upload';
        if (step.includes('chunk')) return 'chunk';
        if (step.includes('embed')) return 'embed';
        if (step.includes('index')) return 'index';
        return 'upload';
    },

    // ======================
    // Chat Logic
    // ======================
    initChatArea() {
        const chatForm = document.getElementById('chatForm');
        const chatInput = document.getElementById('chatInput');
        const typingIndicator = document.getElementById('typingIndicator');

        if (!chatForm) return;

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = chatInput.value.trim();
            if (!message) return;

            // Add user message immediately
            Model.chatHistory.push({ role: 'user', content: message });
            chatInput.value = '';
            this.render(); // Show user message instantly

            typingIndicator.classList.remove('d-none');

            try {
                const res = await fetch('/php/chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });

                const data = await res.json();
                Model.chatHistory.push({ 
                    role: 'ai', 
                    content: data.answer || data.response || "Sorry, I couldn't process that." 
                });
            } catch (err) {
                Model.chatHistory.push({ 
                    role: 'ai', 
                    content: "⚠️ Neural Worker is currently unavailable." 
                });
            } finally {
                typingIndicator.classList.add('d-none');
                this.render();
                const chatWindow = document.getElementById('chatWindow');
                if (chatWindow) chatWindow.scrollTop = chatWindow.scrollHeight;
            }
        });
    }
};

// ======================
// Bootstrap the SPA
// ======================
document.addEventListener('DOMContentLoaded', () => {
    Controller.init();
});
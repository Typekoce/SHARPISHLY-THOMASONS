// ================================================
// SHARPISHLY-THOMASONS SPA – Neural Pipeline v3.5
// ================================================

const App = {
    async loadTemplate(url, data = {}) {
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`Failed to load template: ${url}`);
            let template = await res.text();
            template = template.replace(/{{\s*([\w.]+)\s*}}/g, (_, path) => {
                return path.split('.').reduce((obj, key) => obj?.[key], data) ?? '';
            });
            return template;
        } catch (error) {
            console.error(`❌ Template error:`, error.message);
            throw error;
        }
    },

    async render(url, id, data = {}) {
        try {
            const html = await this.loadTemplate(url, data);
            const container = document.getElementById(id);
            if (container) container.innerHTML = html;
        } catch (error) {
            console.error("Render failed:", error);
        }
    },

    async header() {
        await this.render('views/layouts/header.html', 'header', {
            h1: 'Thomasons V3',
            message: 'Neural Intelligence Gateway',
            timestamp: new Date().toLocaleString()
        });
    }
};

const Model = {
    queue: [],
    healthStatus: null,
    showRawHealth: false,
    currentPage: 'home',
    isUploading: false,
    uploadProgress: 0
};

const View = {
    renderWrap: (content) => `<div class="fade-in">${content}</div>`,

    home: () => `
        <div class="py-6 text-center">
            <h1 class="display-3 fw-bold mb-3">Thomasons <span class="text-primary">V3</span></h1>
            <p class="lead text-muted mb-5">High-Performance Neural Document Intelligence</p>
            <div class="row g-4 justify-content-center">
                <div class="col-md-4" data-page="llm" style="cursor:pointer">
                    <div class="card p-5 border-0 shadow-sm hover-lift">
                        <h5 class="fw-bold mb-2">Neural Intake</h5>
                        <p class="text-muted small">Process & Vectorize</p>
                    </div>
                </div>
                <div class="col-md-4" data-page="health" style="cursor:pointer">
                    <div class="card p-5 border-0 shadow-sm hover-lift">
                        <h5 class="fw-bold mb-2">System Health</h5>
                        <p class="text-muted small">Infrastructure Monitor</p>
                    </div>
                </div>
            </div>
        </div>`,

    health: () => {
        const h = Model.healthStatus;
        const badge = (val) => val 
            ? '<span class="badge bg-success">ONLINE</span>' 
            : '<span class="badge bg-danger">OFFLINE</span>';

        const ollama = h?.ollama || { active: false, models: {} };
        const jobs = h?.latest_job || []; // Fixed key name to match Controller

        return `
        <div class="max-width-800 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Infrastructure Monitor</h2>
                <button class="btn btn-sm btn-outline-secondary" id="toggleRawHealth">
                    ${Model.showRawHealth ? 'Dashboard' : 'Raw JSON'}
                </button>
            </div>

            ${Model.showRawHealth ? `
                <div class="card border-0 shadow-sm bg-dark text-light p-3">
                    <pre class="m-0" style="font-size:11px"><code>${JSON.stringify(h, null, 4)}</code></pre>
                </div>
            ` : `
                <div class="card border-0 shadow-sm mb-4">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between p-4">
                            <strong>MySQL Database</strong> ${h ? badge(h.database) : '...'}
                        </li>
                        <li class="list-group-item d-flex justify-content-between p-4">
                            <strong>Redis Signal Bus</strong> ${h ? badge(h.redis) : '...'}
                        </li>
                    </ul>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong>Ollama Neural Engine</strong> 
                            ${badge(ollama.active)}
                        </div>
                        <div class="bg-light rounded p-3">
                            ${Object.keys(ollama.models || {}).length ? Object.entries(ollama.models).map(([name, m]) => `
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="font-monospace fw-bold">${name}</span>
                                        <span class="text-muted">${m.size}</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar ${m.state === 'Ready' ? 'bg-primary' : 'bg-warning'}" 
                                             style="width: ${m.progress}"></div>
                                    </div>
                                    <small style="font-size: 10px;" class="text-uppercase ${m.state === 'Ready' ? 'text-success' : 'text-danger'}">
                                        Status: ${m.state} (${m.progress})
                                    </small>
                                </div>
                            `).join('') : '<p class="text-muted small m-0">No neural models detected on disk.</p>'}
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-muted small text-uppercase mb-3">Recent Neural Jobs</h6>
                <div class="card border-0 shadow-sm overflow-hidden">
                    <table class="table mb-0 small">
                        <thead class="table-light">
                            <tr><th class="px-4">ID</th><th>Status</th><th class="text-end px-4">Updated</th></tr>
                        </thead>
                        <tbody>
                            ${jobs.length ? jobs.map(j => `
                                <tr>
                                    <td class="px-4 font-monospace">#${j.id}</td>
                                    <td><span class="badge bg-light text-primary border border-primary">${j.status}</span></td>
                                    <td class="text-end px-4 text-muted">${j.updated_at || 'n/a'}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="3" class="text-center py-4">Queue Empty</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `}
        </div>`;
    },

    llm: () => `
        <div class="row">
            <div class="col-lg-7">
                <h2 class="fw-bold mb-4">Neural Intake</h2>
                <div id="dropzone" class="dropzone p-5 text-center shadow-sm rounded-4 border-2 border-dashed">
                    <input type="file" id="fileInput" multiple class="d-none">
                    <div class="display-4 text-primary mb-3">↑</div>
                    <h5>Drop Documents Here</h5>
                    <p class="text-muted small">Thomasons V3 accepts PDF, TXT, and CSV</p>
                </div>
            </div>
            <div class="col-lg-5" id="queueList">
                <h2 class="fw-bold mb-4">Live Pipeline</h2>
                ${Model.queue.map(item => `
                    <div class="card border-0 shadow-sm mb-2 p-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-truncate" style="max-width:150px">${item.name}</span>
                            <span class="text-primary fw-bold">${item.status.toUpperCase()}</span>
                        </div>
                        <div class="progress" style="height:4px"><div class="progress-bar" style="width:${item.progress}%"></div></div>
                    </div>
                `).join('')}
            </div>
        </div>`
};

const Controller = {
    init() {
        document.addEventListener('click', (e) => {
            const page = e.target.closest('[data-page]')?.dataset.page;
            if (page) this.navigate(page);
            if (e.target.id === 'toggleRawHealth') {
                Model.showRawHealth = !Model.showRawHealth;
                this.render(true);
            }
        });

        App.header();
        this.navigate('home');
        setInterval(() => this.fetchHealth(), 5000);
    },

    // NEW: Handle dynamic interactions for the current view
    bindEvents() {
        if (Model.currentPage === 'llm') {
            const dropzone = document.getElementById('dropzone');
            const fileInput = document.getElementById('fileInput');

            if (dropzone && fileInput) {
                // Click to open window
                dropzone.onclick = () => fileInput.click();

                // Handle file selection
                fileInput.onchange = (e) => this.handleUpload(e.target.files);

                // Drag & Drop logic
                dropzone.ondragover = (e) => { e.preventDefault(); dropzone.classList.add('border-primary'); };
                dropzone.ondragleave = () => dropzone.classList.remove('border-primary');
                dropzone.ondrop = (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('border-primary');
                    this.handleUpload(e.dataTransfer.files);
                };
            }
        }
    },




    //

    async fetchHealth() {
        try {
            const res = await fetch('/php/health');
            Model.healthStatus = await res.json();
            if (Model.currentPage === 'health') this.render();
        } catch (e) { console.error("Health fetch failed"); }
    },

    navigate(page) {
        Model.currentPage = page;
        this.render(true);
    },

    render(force = false) {
        const container = document.getElementById('app');
        if (!container) return;
        const html = View.renderWrap(View[Model.currentPage]());
        if (force || container.innerHTML !== html) {
            container.innerHTML = html;
        }
        this.bindEvents();
    }
};

document.addEventListener('DOMContentLoaded', () => Controller.init());
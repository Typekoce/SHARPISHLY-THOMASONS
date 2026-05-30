/**
 * App: Global Utilities & Template Engine
 */
const App = {
    async loadTemplate(url, data = {}) {
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`Missing: ${url}`);
            let template = await res.text();
            
            // Recursive path resolver for {{ object.key }}
            return template.replace(/{{\s*([\w.]+)\s*}}/g, (_, path) => {
                const value = path.split('.').reduce((obj, key) => obj?.[key], data);
                if (typeof value === 'boolean') return value ? '<span class="text-success">ONLINE</span>' : '<span class="text-danger">OFFLINE</span>';
                return value ?? '';
            });
        } catch (e) {
            return `<div class="p-4 text-danger">Template Error: ${e.message}</div>`;
        }
    },
    crm() { Model.currentPage = 'home'; Controller.render(); },
    cyberdeck() { Model.currentPage = 'llm'; Controller.render(); },
    url(url) { return window.location.href + '/php/' + url; },
    getApp() { return document.getElementById('app'); },
    item(e) { return document.createElement(e); },
    flash(msg) {
        const flash = document.getElementById('flash');
        if (!flash) return;
        const alert = App.item('div');
        alert.style.cssText = 'width:100%;border-radius:16px;margin-bottom:10px;padding-top:10px;padding-bottom:10px;background-color:#ccc;text-align:center';
        alert.innerHTML = msg;
        flash.appendChild(alert);
    }
};

/**
 * Model: Global App State
 */
const Model = {
    queue: [],
    healthStatus: null,
    currentPage: 'home'
};

/**
 * Registry: Maps page IDs to their specific initialization functions
 */
const PageRegistry = {
    'rag': () => Controller.bindRag(),
    'emails': () => Controller.bindEmails(),
    'agent': () => Controller.bindAgents(),
    'docs': () => Controller.docs('docs')
};

/**
 * Controller: Orchestrates UI logic, data fetching, and event binding
 */
const Controller = {
    async autoFill() {
        const input = document.getElementById('autocomplete');
        if (!input) return;
        const ul = document.createElement('ul');
        const li = document.createElement('li');
        li.innerHTML = "test";
        ul.appendChild(li);
        input.appendChild(ul);
    },

    async bindRag() {
        const btn = document.getElementById('rag-send');
        const input = document.getElementById('rag-input');
        const history = document.getElementById('chat-history');
        if (!btn) return;

        btn.onclick = async () => {
            const query = input.value.trim();
            if (!query) return;
            history.innerHTML += `<p><strong>You:</strong> ${query}</p>`;
            input.value = '';
            try {
                const res = await fetch(App.url('rag/chat/'), {
                    method: 'POST',
                    body: JSON.stringify({ query })
                });
                const data = await res.json();
                history.innerHTML += `<p><strong>Bot:</strong> ${data.answer || data.message}</p>`;
            } catch (e) { history.innerHTML += `<p class="text-danger">Error: Service unavailable</p>`; }
            history.scrollTop = history.scrollHeight;
        };
    },

    eFields(fields) {
        let post = {};
        for (let field in fields) {
            const el = document.getElementById(field);
            post[field] = el ? el.value : '';
        }
        return post;
    },

    eForm(form, fields) {
        for (let field in fields) {
            const input = App.item('input');
            input.setAttribute('id', field);
            const label = App.item('label');
            label.innerHTML = field;
            const row = App.item('div');
            row.appendChild(label);
            row.appendChild(input);
            form.appendChild(row);
        }
    },

    async bindEmails() {
        const fields = { 'email': {}, 'message': {}, 'subject': {} };
        const form = document.getElementById('form');
        if (!form) return;
        this.eForm(form, fields);

        const btn = App.item('div');
        btn.style.cssText = 'cursor:pointer;border:1px dashed #ccc;';
        btn.innerHTML = "save";
        form.appendChild(btn);

        btn.onclick = async () => {
            const postData = this.eFields(fields);
            try {
                const res = await fetch(App.url('emails/test/'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(postData)
                });
                const data = await res.json();
                App.flash('Success id:' + data.id + ' created');
            } catch (e) { App.flash('Error'); }
        };
    },

    async bindAgents() {
        const fields = { 'create': {}, 'read': {}, 'update': {}, 'delete': {} };
        const menu = document.getElementById('menu');
        if (menu) this.menuFields(fields, menu);
    },

    async menuFields(fields, menu) {
        for (let field in fields) {
            let item = App.item('div');
            item.innerHTML = field;
            item.setAttribute('id', field);
            item.setAttribute('style', 'cursor:pointer;padding:10px;text-align:center;float:left;border:1px dashed #ccc;');
            item.onclick = async () => {
                let form = document.getElementById('form');

		// New condition to trigger createAgent
                if (form && field === 'create') {
                    this.createAgent(form);
                }

                if (form && field === 'delete') {
                    let agent = App.item('div');
                    agent.innerHTML = "Create a new agent to do the tasks you avoid";
                    form.appendChild(agent);
                }
            };
            menu.appendChild(item);
        }
    },
    // Triggered by the 'create' menu item
    createAgent(form) {
        // 1. Clear existing form content
        form.innerHTML = '';
        
        // 2. Define fields for the agent creation
        const fields = { 
            'agent_name': {}, 
            'description': {}, 
            'role': {} 
        };
        
        // 3. Populate form using your existing helper
        this.createAgentForm(form, fields);
        
        // 4. Add a submit button
        const btn = App.item('div');
        btn.style.cssText = 'cursor:pointer;border:1px dashed #ccc; margin-top: 10px; padding: 5px;';
        btn.innerHTML = "Save Agent";
        btn.onclick = () => alert("Submitting agent...");
        form.appendChild(btn);
    },

    // Handles looping through fields and injecting them into the form
    createAgentForm(form, fields) {
        // Reuse your existing logic, or use eForm if it matches perfectly
        this.eForm(form, fields);
    },    
    async init() {
        const host = window.location.hostname;
        const sub = host.split('.')[0];
        try {
            const res = await fetch('views/layouts/header.html');
            if (res.ok) {
                let headerTemplate = await res.text();
                const initialH1 = (sub === 'cyberdeck') ? 'Cyberdeck (LLM)' : 'Thomasons V3';
                document.getElementById('header').innerHTML = headerTemplate.replace(/{{\s*h1\s*}}/g, initialH1);
            }
        } catch (e) { console.error("Layout Engine init failed:", e); }

        if (sub === 'crm') App.crm();
        else if (sub === 'cyberdeck') App.cyberdeck();
        else this.navigate('home');

        document.addEventListener('click', (e) => {
            const page = e.target.closest('[data-page]')?.dataset.page;
            if (page) { e.preventDefault(); this.navigate(page); }
        });
    },

    async render() {
        const target = document.getElementById('app');
        if (!target) return;

        const templatePath = Model.currentPage === 'home' 
            ? 'views/home/index.html' 
            : `views/pages/${Model.currentPage}.html`;

        let html = await App.loadTemplate(templatePath, {
            health: Model.healthStatus,
            queue: Model.queue
        });

        const headerH1 = document.querySelector('#header h1');
        if (headerH1) headerH1.textContent = Model.currentPage === 'home' ? 'Thomasons V3' : Model.currentPage.toUpperCase();
        
        document.querySelectorAll('.navbar .nav-link').forEach(link => {
            link.classList.toggle('active', link.dataset.page === Model.currentPage);
        });

        if (Model.currentPage === 'llm') {
            const itemsHtml = Model.queue.map(item => `
                <div class="card mb-2 p-2 small border-0 shadow-sm">
                    <div class="d-flex justify-content-between">
                        <strong>${item.name}</strong>
                        <span class="text-primary">${item.status}</span>
                    </div>
                </div>
            `).join('');
            html = html.replace('{{ queueHtml }}', itemsHtml || '<p class="text-muted small">No active jobs.</p>');
        }

        target.innerHTML = `<div class="fade-in">${html}</div>`;
        this.bindEvents();
        if (PageRegistry[Model.currentPage]) PageRegistry[Model.currentPage]();
    },

    navigate(page) { Model.currentPage = page; this.render(); },

    bindEvents() {
        const drop = document.getElementById('dropzone');
        const input = document.getElementById('fileInput');
        if (!drop || !input) return;

        drop.onclick = () => input.click();
        input.onchange = (e) => this.handleUpload(e.target.files);
        drop.ondragover = (e) => { e.preventDefault(); drop.style.backgroundColor = '#f0f7ff'; };
        drop.ondragleave = () => drop.style.backgroundColor = '';
        drop.ondrop = (e) => {
            e.preventDefault();
            drop.style.backgroundColor = '';
            this.handleUpload(e.dataTransfer.files);
        };
    },

    async handleUpload(files) {
        if (!files.length) return;
        const formData = new FormData();
        Array.from(files).forEach(f => {
            formData.append('documents[]', f);
            Model.queue.push({ name: f.name, status: 'processing', progress: 25 });
        });
        this.render();
        try {
            const res = await fetch(App.url('job/create'), { method: 'POST', body: formData });
            Model.queue.forEach(item => { item.status = 'queued'; item.progress = 50; });
            this.render();
        } catch (e) { console.error("Upload failed"); }
    },

    docsBindMessage(record, ul) {
        let li = document.createElement('li');
        li.innerHTML = '<b>You</b>:&nbsp;' + record.message;
        ul.appendChild(li);
    },
    docsBindAnswer(record, ul) {
        let li = document.createElement('li');
        li.innerHTML = '<b>Sharpishly</b>:&nbsp;' + record.answer;
        ul.appendChild(li);
    },
    async docs(page) {
        try {
            const res = await fetch(App.url('docs'));
            const data = await res.json();
            const target = document.getElementById('app');
            let ul = document.createElement('ul');
            for (let id in data.records) {
                this.docsBindMessage(data.records[id], ul);
                this.docsBindAnswer(data.records[id], ul);
            }
            if (target) target.appendChild(ul);
        } catch (e) { }
    }
};

document.addEventListener('DOMContentLoaded', () => Controller.init());
// Add this to your init() or at the bottom of script.js
document.addEventListener('click', (e) => {
    // Handle Navbar Toggler
    if (e.target.matches('.navbar-toggler')) {
        document.querySelector('.nav-menu').classList.toggle('show');
    }
});

/**
 * Controller: Logic & State Coordination
 */
const Controller = {
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
        const templatePath = Model.currentPage === 'home' ? 'views/home/index.html' : `views/pages/${Model.currentPage}.html`;
        let html = await App.loadTemplate(templatePath, { health: Model.healthStatus, queue: Model.queue });
        const headerH1 = document.querySelector('#header h1');
        if (headerH1) headerH1.textContent = Model.currentPage === 'home' ? 'Thomasons V3' : Model.currentPage.toUpperCase();
        
        document.querySelectorAll('.navbar .nav-link').forEach(link => {
            link.classList.toggle('active', link.dataset.page === Model.currentPage);
        });

        target.innerHTML = `<div class="fade-in">${html}</div>`;
        this.bindEvents();
        Render.renderQueue(); 
        if (PageRegistry[Model.currentPage]) PageRegistry[Model.currentPage]();
    },

    navigate(page) { Model.currentPage = page; this.render(); },

    async handleUpload(files) {
        if (!files.length) return;
        const formData = new FormData();
        Array.from(files).forEach(f => {
            formData.append('documents[]', f);
            Model.queue.push({ name: f.name, status: 'processing', progress: 25 });
        });
        Render.renderQueue();
        try {
            await fetch(App.url('job/create'), { method: 'POST', body: formData });
            Model.queue.forEach(item => { if (item.status === 'processing') item.status = 'queued'; });
            Render.renderQueue();
        } catch (e) { App.flash("Upload failed: " + e.message); }
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
                const res = await fetch(App.url('rag/chat/'), { method: 'POST', body: JSON.stringify({ query }) });
                const data = await res.json();
                history.innerHTML += `<p><strong>Bot:</strong> ${data.answer || data.message}</p>`;
            } catch (e) { history.innerHTML += `<p class="text-danger">Error: Service unavailable</p>`; }
            history.scrollTop = history.scrollHeight;
        };
    },

    async bindEmails() {
        const fields = { 'email': {}, 'message': {}, 'subject': {} };
        const form = document.getElementById('form');
        if (!form) return;
        Render.eForm(form, fields);
        const btn = App.item('div');
        btn.style.cssText = 'cursor:pointer;border:1px dashed #ccc;';
        btn.innerHTML = "save";
        form.appendChild(btn);
        btn.onclick = async () => {
            const postData = this.eFields(fields);
            try {
                const res = await fetch(App.url('emails/test/'), {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(postData)
                });
                const data = await res.json();
                App.flash('Success id:' + data.id + ' created');
            } catch (e) { App.flash('Error'); }
        };
    },

    async bindAgents() {
        const fields = { 'create': {}, 'read': {}};
        const menu = document.getElementById('menu');
        if (menu) this.menuFields(fields, menu);
    },

    async displayAgentRecords(form) {
        form.innerHTML = '<h3>Active Agents</h3>';
        try {
            const res = await fetch(App.url('agent/index'));
            const agents = await res.json();
            const table = App.item('table');
            table.className = 'agent-table';
            table.innerHTML = `<thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>`;
            const tbody = App.item('tbody');
            agents.forEach(agent => {
                const tr = App.item('tr');
                const actions = [{id: 'start', name: 'Start'}, {id: 'stop', name: 'Stop'}, {id: 'edit', name: 'Edit'}, {id: 'delete',name: 'Delete'}];
                tr.innerHTML = `<td>${agent.agent_name}</td><td>${agent.role}</td><td><span class="badge ${agent.status}">${agent.status}</span></td>`;
                const actionTd = document.createElement('td');
                const select = Render.selectList(actions, (actionId) => console.log(`Triggering ${actionId} for agent ${agent.id}`));
                actionTd.appendChild(select);
                tr.appendChild(actionTd);
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            form.appendChild(table);
        } catch (e) { form.innerHTML += '<p>No agents found or error loading.</p>'; }
    },

    menuFields(fields, menu) {
        for (let field in fields) {
            let item = App.item('div');
            item.innerHTML = field;
            item.id = field;
            item.classList.add('menu-item');
            item.onclick = async () => {
                let form = document.getElementById('form');
                if (!form) return;
                this.handleMenuClick(field, form);
            };
            menu.appendChild(item);
        }
    },

    handleMenuClick(field, form) {
        switch(field) {
            case 'create': this.createAgent(form); break;
            case 'read': this.displayAgentRecords(form); break;
            case 'delete':
                let msg = App.item('div');
                msg.innerHTML = "Create a new agent to do the tasks you avoid";
                form.appendChild(msg);
                break;
        }
    },

    createAgent(form) {
        form.innerHTML = '';
        const fields = { 'agent_name': {}, 'description': {}, 'role': {} };
        Render.eForm(form, fields);
        const btn = App.item('div');
        btn.style.cssText = 'cursor:pointer;border:1px dashed #ccc; margin-top: 10px; padding: 5px;';
        btn.innerHTML = "Save Agent";
        btn.onclick = async () => {
            const postData = this.eFields(fields);
            try {
                const res = await fetch(App.url('agent/test/'), {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(postData)
                });
                const data = await res.json();
                App.flash('Success id:' + data.id + ' created');
            } catch (e) { App.flash('Error'); }
        };
        form.appendChild(btn);
    },

    eFields(fields) {
        let post = {};
        for (let field in fields) {
            const el = document.getElementById(field);
            post[field] = el ? el.value : '';
        }
        return post;
    },

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

    docs(page) {
        fetch(App.url('docs')).then(res => res.json()).then(data => {
            const target = document.getElementById('app');
            let ul = App.item('ul');
            ul.setAttribute('id','docs');
            for (let id in data.records) {
                let li = App.item('li');
                li.innerHTML = `<b>You</b>: ${data.records[id].message}<br><b>Sharpishly</b>: ${data.records[id].answer}`;
                ul.appendChild(li);
            }
            if (target) target.appendChild(ul);
        }).catch(e => console.error(e));
    }
};

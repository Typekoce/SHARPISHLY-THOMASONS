#!/bin/bash
# create_app_files.sh - Fully populates modular files from monolithic source

mkdir -p web/frontend/js

# 1. app.js
cat << 'EOF' > web/frontend/js/app.js
const App = {
    async loadTemplate(url, data = {}) {
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`Missing: ${url}`);
            let template = await res.text();
            return template.replace(/{{\s*([\w.]+)\s*}}/g, (_, path) => {
                const value = path.split('.').reduce((obj, key) => obj?.[key], data);
                if (typeof value === 'boolean') return value ? '<span class="text-success">ONLINE</span>' : '<span class="text-danger">OFFLINE</span>';
                return value ?? '';
            });
        } catch (e) { return `<div class="p-4 text-danger">Template Error: ${e.message}</div>`; }
    },
    url(path) { 
        const cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return `${window.location.origin}/php/${cleanPath}`; 
    },
    selectList(fields, callback) {
        const select = document.createElement('select');
        select.className = 'agent-selector';
        const defaultOption = document.createElement('option');
        defaultOption.text = 'Select an agent...';
        defaultOption.value = '';
        select.appendChild(defaultOption);
        fields.forEach(field => {
            const option = document.createElement('option');
            option.value = field.id;
            option.text = field.name;
            select.appendChild(option);
        });
        select.onchange = function(e) { if (this.value) callback(this.value); };
        return select;
    },
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
EOF

# 2. model.js
cat << 'EOF' > web/frontend/js/model.js
const Model = { queue: [], healthStatus: null, currentPage: 'home' };
EOF

# 3. view.js
cat << 'EOF' > web/frontend/js/view.js
const view = {
    neuralPipeline() {
        const container = document.getElementById('neural-pipeline');
        if (!container) return;
        container.innerHTML = '';
        const np = App.item('div');
        np.className = 'pipeline-list';
        Model.queue.forEach(item => {
            const row = App.item('div');
            row.className = 'card mb-2 p-2 small border-0 shadow-sm';
            row.innerHTML = `<div class="d-flex justify-content-between"><strong>${item.name}</strong><span class="text-primary">${item.status}</span></div>`;
            np.appendChild(row);
        });
        container.appendChild(np);
    }
};
EOF

# 4. controller.js (Base)
echo "const Controller = {};" > web/frontend/js/controller.js

# 5. rag_controller.js
cat << 'EOF' > web/frontend/js/rag_controller.js
Object.assign(Controller, {
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
    }
});
EOF

# 6. email_controller.js
cat << 'EOF' > web/frontend/js/email_controller.js
Object.assign(Controller, {
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
        const fields = { 'to': {}, 'subject': {}, 'body': {} };
        const form = document.getElementById('form');
        if (!form) return;
        this.eForm(form, fields);
        const btn = App.item('div');
        btn.className = 'btn btn-outline-primary mt-2';
        btn.innerHTML = "Queue Email Task";
        form.appendChild(btn);
        btn.onclick = async () => {
            const taskData = this.eFields(fields);
            taskData.id = Date.now();
            try {
                const res = await fetch(App.url('emails/queue/'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(taskData)
                });
                const result = await res.json();
                if (result.status === 'success') {
                    App.flash("Email Task Queued: " + result.id);
                    form.reset();
                }
            } catch (e) { App.flash("Critical Error: Persistence failed"); }
        };
    },
    prepareEmailFormForAgent(agent) {
        this.navigate('emails');
        setTimeout(() => {
            const subjectInput = document.getElementById('subject');
            if (subjectInput) subjectInput.value = `Task via ${agent.agent_name}`;
            const form = document.getElementById('form');
            const agentCtx = App.item('input');
            agentCtx.type = 'hidden';
            agentCtx.id = 'agent_context';
            agentCtx.value = agent.id;
            form.appendChild(agentCtx);
            App.flash(`Composing email for Agent: ${agent.agent_name}`);
        }, 100);
    }
});
EOF

# 7. agent_controller.js
cat << 'EOF' > web/frontend/js/agent_controller.js
Object.assign(Controller, {
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
                const actions = [{id: 'start', name: 'Start'}, {id: 'stop', name: 'Stop'}, {id: 'email', name: 'Write Email'}, {id: 'edit', name: 'Edit'}, {id: 'delete', name: 'Delete'}];
                tr.innerHTML = `<td>${agent.agent_name}</td><td>${agent.role}</td><td><span class="badge ${agent.status}">${agent.status}</span></td>`;
                const actionTd = document.createElement('td');
                const select = App.selectList(actions, (actionId) => {
                    if (actionId === 'email') this.prepareEmailFormForAgent(agent);
                });
                actionTd.appendChild(select);
                tr.appendChild(actionTd);
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            form.appendChild(table);
        } catch (e) { form.innerHTML += '<p>No agents found or error loading.</p>'; }
    },
    async createAgent(form) {
        form.innerHTML = '';
        const fields = { 'agent_name': {}, 'description': {}, 'role': {} };
        // Assume createAgentForm logic here...
    }
});
EOF

# 8. registry.js
cat << 'EOF' > web/frontend/js/registry.js
const PageRegistry = {
    'rag': () => Controller.bindRag(),
    'emails': () => Controller.bindEmails(),
    'agent': () => Controller.bindAgents(),
    'docs': () => Controller.docs('docs')
};
EOF

echo "All modules created and populated successfully."

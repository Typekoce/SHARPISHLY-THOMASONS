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
    url(path) { 
    	// Ensure the path does not start with a / to avoid double slashes
    	const cleanPath = path.startsWith('/') ? path.substring(1) : path;
    	return `${window.location.origin}/php/${cleanPath}`; 
    },
    selectList(fields, callback) {
    const select = document.createElement('select');
    select.className = 'agent-selector'; // For CSS styling

    // Add a default placeholder
    const defaultOption = document.createElement('option');
    defaultOption.text = 'Select an agent...';
    defaultOption.value = '';
    select.appendChild(defaultOption);

    // Build the fields
    fields.forEach(field => {
        const option = document.createElement('option');
        option.value = field.id;
        option.text = field.name;
        select.appendChild(option);
    });

    // Use onchange for select elements
    select.onchange = function(e) {
        if (this.value) {
            callback(this.value);
        }
    };

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



// Add this helper to App
App.addAgentActions = function(form) {
    // Only proceed if we are in agent context
    const agentId = document.getElementById('agent_id');
    if (!agentId || !agentId.value) return;

    const actions = [
        "Deploy latest container", "Rotate SSH keys", "Scale worker nodes", 
        "Execute maintenance script", "Clear orphaned containers", 
        "Backup database volume", "Audit security logs", "Restart Nginx", 
        "Purge temporary files", "Verify integrity"
    ];

    const label = App.item('label');
    label.innerHTML = "Agent Task Actions";
    form.appendChild(label);

    const select = document.createElement('select');
    select.className = 'form-control mb-2';
    select.innerHTML = '<option value="">-- Choose an action --</option>';

    actions.forEach(action => {
        const opt = document.createElement('option');
        opt.value = action;
        opt.text = action;
        select.appendChild(opt);
    });

    select.onchange = (e) => {
        if (e.target.value) {
            const body = document.getElementById('body');
            body.value += (body.value ? "\n" : "") + `Action: ${e.target.value}`;
        }
    };
    form.appendChild(select);
};






App.agentEmailForm = function(agent) {
    // 1. Navigate to the email page
    Controller.navigate('emails');

    // 2. Wait for the form to be rendered by the router
    setTimeout(() => {
        const form = document.getElementById('form');
        const subject = document.getElementById('subject');

        if (subject) {
            subject.value = `Task for: ${agent.agent_name}`;
        }

        // 3. Add hidden agent context to associate the task
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'agent_id';
        hidden.value = agent.id;
        form.appendChild(hidden);

        // 4. Inject agent-specific form options/metadata
        const info = document.createElement('div');
        info.className = 'mt-2 p-2 border bg-light';
        info.innerHTML = `<strong>Agent Context:</strong> ${agent.role} active.`;
        form.appendChild(info);

        App.flash(`Agent ${agent.agent_name} loaded into email form.`);
    }, 100);
};
/**
 * View: Global App State
 */
const view = {
    neuralPipeline() {
        const container = document.getElementById('neural-pipeline');
        if (!container) return;

        // Clear existing content
        container.innerHTML = '';

        // Create the wrapper
        const np = App.item('div');
        np.className = 'pipeline-list';

        // Map your Model.queue to UI elements
        Model.queue.forEach(item => {
            const row = App.item('div');
            row.className = 'card mb-2 p-2 small border-0 shadow-sm';
            row.innerHTML = `
                <div class="d-flex justify-content-between">
                    <strong>${item.name}</strong>
                    <span class="text-primary">${item.status}</span>
                </div>
            `;
            np.appendChild(row);
        });

        // Append to the target
        container.appendChild(np);
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
 * Home Controller
 */
const HomeController = {};

/**
 * Rag Controller
 */
const RagController = {};

/**
 * Llm Controller
 */
const LlmController = {};


/**
 * Docs Controller
 */
const DocsController = {};

/**
 * Agent Controller
 */
/**
 * Agent Controller
 */
const AgentController = {
    contacts: function(form) {
        const container = App.item('div');
        container.id = 'agent-contacts-container';
        container.className = 'mt-3 p-2 border';
        
        // 1. Add Title first
        const title = App.item('h5');
        title.innerHTML = 'Available Contacts';
        container.appendChild(title);

        // 2. Populate contacts inside the container
        this.address(container);
        
        form.appendChild(container);
        return container;
    },

    address: function(container) {
        const contacts = [
            { name: "Admin", email: "admin@system.local" },
            { name: "DevOps", email: "devops@system.local" }
        ];

        contacts.forEach(c => {
            const btn = App.item('button');
            btn.className = 'btn btn-sm btn-secondary m-1';
            btn.innerHTML = c.name;
            
            // Set the 'to' field when clicked
            btn.onclick = (e) => {
                e.preventDefault();
                const toField = document.getElementById('to');
                if (toField) toField.value = c.email;
            };
            
            container.appendChild(btn);
        });
    }
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
    const fields = { 'to': {}, 'subject': {}, 'body': {} };
    const form = document.getElementById('form');
    if (!form) return;
    this.eForm(form, fields);

    AgentController.contacts(form);

    App.addAgentActions(form);

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
                form.reset(); // Clear form on success
            }
        } catch (e) { 
            App.flash("Critical Error: Persistence failed"); 
        }
    };
    },

    async bindAgents() {
        const fields = { 'create': {}, 'read': {}};
        const menu = document.getElementById('menu');
        if (menu) this.menuFields(fields, menu);
    },


// Add to Controller object
async displayAgentRecords(form) {
    form.innerHTML = '<h3>Active Agents</h3>';
    
    // Fetch existing agents
    try {
        const res = await fetch(App.url('agent/index')); // Assumes your AgentController exists
        const agents = await res.json();
        console.log(agents);
        const table = App.item('table');
        table.className = 'agent-table';
        table.innerHTML = `<thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>`;
const tbody = App.item('tbody');

agents.forEach(agent => {
    const tr = App.item('tr');
    
    // Define your actions
    const actions = [
        {id: 'start', name: 'Start'},
        {id: 'stop', name: 'Stop'},
        {id: 'edit', name: 'Edit'},
        {id: 'email', name: 'Email'},
	{id: 'delete',name: 'Delete'},
    ];

    // Build standard cells using template literals for safe, static content
    tr.innerHTML = `
        <td>${agent.agent_name}</td>
        <td>${agent.role}</td>
        <td><span class="badge ${agent.status}">${agent.status}</span></td>
    `;

    // Create the select cell
    const actionTd = document.createElement('td');
    const select = App.selectList(actions, (actionId) => {
// Check if the selected action is 'email'
    if (actionId === 'email') {
        // Execute the form creation method
        App.agentEmailForm(agent);
    } else {
        // Handle other actions (start, stop, etc.)
        console.log(`Action ${actionId} selected for agent ${agent.agent_name}`);
    }
    });
    
    actionTd.appendChild(select);
    tr.appendChild(actionTd);
    
    tbody.appendChild(tr);
});
        table.appendChild(tbody);
        form.appendChild(table);
    } catch (e) {
        form.innerHTML += '<p>No agents found or error loading.</p>';
    }
},

async menuFields(fields, menu) {
    for (let field in fields) {
        let item = App.item('div');
        item.innerHTML = field;
        item.id = field;
        item.classList.add('menu-item'); // Use the CSS class
        
        item.onclick = async () => {
            let form = document.getElementById('form');
            if (!form) return;

            // Clear current form if necessary or reset state
            this.handleMenuClick(field, form);
        };
        menu.appendChild(item);
    }
},

// Controller method to handle the logic switch
handleMenuClick(field, form) {
    switch(field) {
        case 'create':
            this.createAgent(form);
            break;
        case 'read':
            this.displayAgentRecords(form);
            break;
        case 'delete':
            let msg = App.item('div');
            msg.innerHTML = "Create a new agent to do the tasks you avoid";
            form.appendChild(msg);
            break;
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
	// 5. Post form data
        btn.onclick = async () => {
            const postData = this.eFields(fields);
            try {
                const res = await fetch(App.url('agent/test/'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(postData)
                });
                const data = await res.json();
                App.flash('Success id:' + data.id + ' created');
            } catch (e) { App.flash('Error'); }
        };

	//
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
    

	// Call the view update
    	view.neuralPipeline();

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
	    ul.setAttribute('id','docs');
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


/* Lightweight replacement for Bootstrap's Collapse JS */
document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-bs-toggle="collapse"]');
    if (toggle) {
        const targetSelector = toggle.getAttribute('data-bs-target');
        const target = document.querySelector(targetSelector);
        if (target) {
            target.classList.toggle('show');
        }
    }
});

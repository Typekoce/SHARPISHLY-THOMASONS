
/**
 * Agent Controller
 */
const AgentController = {
    // Add to your existing AgentController object
    tiktokPost: function(content) {
    if (!content.trim()) {
        App.flash('Please enter some content first.');
        return;
    }

    const dialog = App.item('div');
    dialog.className = 'border p-3 mt-3 bg-warning';
    dialog.innerHTML = `
        <h5>Confirm TikTok Post</h5>
        <p>Posting: <em>${content}</em></p>
        <button id="tiktok-confirm" type="button" class="btn btn-dark">Confirm & Post</button>
    `;
    
    // Mount within the app container for consistent styling
    const host = document.getElementById('app') || document.body;
    host.appendChild(dialog);

    document.getElementById('tiktok-confirm').onclick = () => {
        // Dummy API Response Logic
        const mockResponse = { status: 'success', id: 'tt_' + Math.random().toString(36).substr(2, 9) };
        
        App.flash('TikTok post queued: ' + mockResponse.id);
        dialog.remove();
        
        // Clear input after success
        const contentInput = document.getElementById('tiktok-content');
        if (contentInput) contentInput.value = '';
    };
},
// Note the 'async' keyword here
async bindAgentsDefault(){
    const form = App.getItem('form');
    
    // Await the result so it resolves to the actual HTML string
    const html = await App.loadTemplate('views/pages/welcome.html', {'foo':'bar'});
    
    // Now you have the string, not the Promise
    form.innerHTML = html;
},
    async bindAgents() {
        this.bindAgentsDefault();
        const fields = { 'create': {}, 'read': {}};
        const menu = document.getElementById('menu');
        if (menu) Controller.menuFields(fields, menu);
    },
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
            {id: 'autoform', name: 'Automatic Form Completion'},
            {id: 'snapshot', name: 'Take Snapshot'},
            {id: 'tiktok', name: 'Tiktok'},
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

        if (actionId === 'snapshot') {
            // Trigger the creation form, perhaps passing the agent context
            Controller.navigate('snapshot');
            SnapshotsController.createForm(agent); 
        } else if (actionId === 'autoform') {

            Controller.navigate('autoform');

        } else  if (actionId === 'tiktok') {

            Controller.navigate('tiktok');

        } else if (actionId === 'email') {

            Controller.navigate('agentemail');

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
};

AgentController.bindAgentEmail = function() {
    const container = document.getElementById('email-fields');
    const sendRow = document.getElementById('send-button');
    if (!container || !sendRow) return;

    // Schema: Keys are labels, Values contain technical attributes
    const schema = { 
        'email': { 'id': 'to', 'placeholder': 'recipient@example.com' }, 
        'title':  { 'id': 'subject', 'placeholder': 'Subject line' }, 
        'content': { 'id': 'message', 'type': 'textarea', 'placeholder': 'Email body...' } 
    };

    App.eForm(container, schema);

    if (!document.getElementById('send-btn')) {
        const btn = App.item('button');
        btn.id = 'send-btn';
        btn.type = 'button';
        btn.className = 'btn btn-primary';
        btn.innerHTML = 'Queue Email Task';
        
        btn.onclick = () => {
            // Correctly targeting the IDs generated by App.eForm
            AgentController.emailQueue(
                document.getElementById('to'),
                document.getElementById('subject'),
                document.getElementById('message')
            );
        };
        sendRow.appendChild(btn);
    }
};

AgentController.emailQueue = function(toInput, subjectInput, bodyInput) {
    // 1. Basic validation
    if (!toInput.value || !toInput.value.includes('@')) {
        App.flash('Please enter a valid email address.');
        return;
    }

    const btn = document.getElementById('send-btn');
    if (btn) btn.disabled = true; // Disable button to prevent double-submit

    const payload = {
        email: toInput.value.trim(),
        title: subjectInput.value.trim(),
        content: bodyInput.value.trim(),
        status: 'waiting'
    };

    const uniqueId = 'email_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 5);

    fetch(App.url(`emails/queue/${uniqueId}`), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(result => {
        if (result.status === 'success') {
            App.flash('Successfully queued: ' + result.id);
            toInput.value = '';
            subjectInput.value = '';
            bodyInput.value = '';
        } else {
            App.flash('Error: ' + (result.message || 'Persistence failure'));
        }
    })
    .catch(err => {
        App.flash('Connection error: ' + err.message);
    })
    .finally(() => {
        if (btn) btn.disabled = false; // Re-enable button
    });
};

AgentController.tiktokPost = function(content) {
    if (!content.trim()) {
        App.flash('The feed is empty! Give us something viral.');
        return;
    }

    const dialog = App.item('div');
    dialog.className = 'card mt-4 p-4 border-2 border-danger'; // High-visibility border
    dialog.innerHTML = `
        <h5 style="color: #dc3545;">⚠️ Broadcast Authorization</h5>
        <p>You are about to launch this content to TikTok:</p>
        <div class="p-3 bg-light border mb-3"><em>"${content}"</em></div>
        <button id="tiktok-confirm" class="btn btn-outline-primary mt-2" style="width: 100%;">
            CONFIRM & LAUNCH
        </button>
    `;
    
    const host = document.getElementById('app');
    host.appendChild(dialog);

    document.getElementById('tiktok-confirm').onclick = () => {
        const mockResponse = { status: 'success', id: 'viral_' + Math.random().toString(36).substr(2, 5) };
        
        App.flash('🚀 Posted to TikTok: ' + mockResponse.id);
        dialog.remove();
        document.getElementById('tiktok-content').value = '';
    };
};

AgentController.autoForm = function() {
    const container = App.getItem('autoform-container');
    container.innerHTML = '<h3>Automatic Form Completion</h3>';

    // Create a form container
    const form = App.item('div');
    form.id = 'autoform-container';
    container.appendChild(form);

    // Schema for target platforms
    const schema = {
        'Job Source': { 'id': 'source', 'placeholder': 'e.g., jobs.com' },
        'Role': { 'id': 'role', 'placeholder': 'Software Developer' },
        'Experience': { 'id': 'exp', 'placeholder': 'Years of experience' }
    };

    // Use App.eForm to build the interface
    App.eForm(form, schema);

    // Add Submit Action
    const btn = App.item('button');
    btn.className = 'btn btn-primary mt-3';
    btn.innerHTML = 'Execute Auto-Fill';
    btn.onclick = () => {
        const data = {
            source: document.getElementById('source').value,
            role: document.getElementById('role').value,
            exp: document.getElementById('exp').value
        };
        App.flash('Initiating auto-form completion for ' + data.source);
        console.log('Payload:', data);
    };
    
    form.appendChild(btn);
    const url = 'ingestion/save/?url=https://www.applybe.com/?a=145F80311.0';
    AgentController.formPreview(url);
};

// Ensure 'async' is present to use 'await'
AgentController.formPreview = async function(url) {
    const preview = document.getElementById('preview');
    if (!preview) return; // Guard clause

    app.spinner();
    try {
        const response = await fetch(App.url(url));
        if (!response.ok) throw new Error('Failed to fetch preview');
        
        const html = await response.text();
        preview.innerHTML = html;
    } catch (e) {
        // Corrected from e.getMessage() to e.message
        App.flash('Error: ' + e.message);
    } finally {
        app.clearSpinner();
    }
};
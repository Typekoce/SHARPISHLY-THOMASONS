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
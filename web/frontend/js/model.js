
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
    'agent': () => AgentController.bindAgents(),
    'docs': () => Controller.docs('docs'),
    'tiktok': () => TiktokController.bindPosts(),
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
 * TikTok Controller: UX Enhanced
 */
const TiktokController = {
    async bindPosts() {
        // App.loadView (if needed) - assumes HTML is already injected
        const btn = document.getElementById('tiktok-submit');
        const contentInput = document.getElementById('tiktok-content');

        if (!btn || !contentInput) return;

        btn.onclick = (e) => {
            // Visual feedback: brief pulse
            btn.style.opacity = '0.5';
            setTimeout(() => btn.style.opacity = '1', 200);

            AgentController.tiktokPost(contentInput.value);
        };
    }
};

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
    async bindAgents() {
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

emailQueue: function(toInput, subjectInput, bodyInput) {
    const data = {
        to: toInput.value,
        subject: subjectInput.value,
        body: bodyInput.value
    };

    const dialog = App.item('div');
    dialog.id = 'agent-dialog';
    dialog.className = 'border p-3 mt-3 bg-light';
    dialog.innerHTML = `
        <h5>Agent Action Required</h5>
        <p>Review email to: <strong>${data.to}</strong></p>
        <button id="agent-confirm" class="btn btn-outline-primary mt-2">Approve & Send</button>
    `;

    const form = document.getElementById('form');
    form.appendChild(dialog);

    document.getElementById('agent-confirm').onclick = (e) => {
        e.preventDefault();
        dialog.remove();

        const formData = new FormData();
        formData.append('to', data.to);
        formData.append('subject', data.subject);
        formData.append('body', data.body);

        fetch('/php/emails/queue/', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(result => {
            if (result.status === 'success') {
                App.flash('Agent-approved email queued: ' + result.id);
                toInput.value = '';
                subjectInput.value = '';
                bodyInput.value = '';
            } else {
                App.flash('Queue failed: ' + result.message);
            }
        })
        .catch(err => {
            App.flash('Queue error: ' + err.message);
        });
    };
}
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


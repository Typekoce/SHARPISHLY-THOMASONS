/** 
 * HealthController: Orchestrates the Health Center UI
 * Pattern: Defensive DOM initialization + Robust Payload Handling
 */
const HealthController = {
    elements: {},
    pollTimer: null,

    // Ensure DOM elements are mapped once, when they exist
    init() {
        this.elements = {
            rag: App.getItem('status-rag'),
            worker: App.getItem('status-worker'),
            ollama: App.getItem('status-ollama'),
            models: App.getItem('status-models')
        };
    },

    // Main fetch with spinner
    async get() {
        if (Object.keys(this.elements || {}).length === 0) this.init();
        
        App.spinner();
        try {
            const res = await fetch(App.url('health'));
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            
            const data = await res.json();

            // Normalize data from response
            this.rag(data.rag_service);
            this.ollama(data.ollama);
            this.worker(data.worker);
            this.models(data.models);

        } catch (e) {
            console.error("Health Center unreachable:", e);
            this.rag('unreachable'); 
            App.flash(`Health Center Error: Service unreachable`);
        } finally {
            App.clearSpinner();
        }
    },

    // Silent background poll without global spinner
    async getSilent() {
        if (Object.keys(this.elements || {}).length === 0) this.init();
        try {
            const res = await fetch(App.url('health'));
            if (!res.ok) return;
            
            const data = await res.json();
            this.rag(data.rag_service);
            this.ollama(data.ollama);
            this.worker(data.worker);
            this.models(data.models);
        } catch (e) {
            console.error('Health poll error:', e);
        }
    },

    // Isolated polling lifecycle tied to DOM state
    startPolling(intervalMs = 10000) {
        this.stopPolling();
        this.get();

        this.pollTimer = setInterval(() => {
            const el = document.getElementById('health');
            if (!el || !document.body.contains(el)) {
                this.stopPolling();
                return;
            }
            this.getSilent();
        }, intervalMs);
    },

    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    },

    async testSuite() {
        console.log('HealthController.testSuite: Starting suite execution...');

        const healthContainer = document.getElementById('health');
        if (!healthContainer) {
            console.warn('HealthController.testSuite: #health container missing from DOM.');
            return;
        }

        let suiteContainer = document.getElementById('health-test-suite');
        if (!suiteContainer) {
            suiteContainer = document.createElement('div');
            suiteContainer.id = 'health-test-suite';
            suiteContainer.className = 'card shadow-sm border-0 mt-4 p-4';
            healthContainer.appendChild(suiteContainer);
        }

        // 1. Render immediate placeholders for every response key in TestController
        const expectedKeys = [
            'id',
            'class',
            'function',
            'google_api',
            'hotmail_api',
            'azure_api',
            'aws_api',
            'recent_work',
            'orm',
            'llm'
        ];

        const placeholderItems = expectedKeys.map(key => 
            `<li><strong class="tree-key">${this.escapeHtml(key)}:</strong> <span class="tree-val text-muted">[CHECKING...]</span></li>`
        ).join('');

        suiteContainer.innerHTML = `
            <h3 class="fw-bold mb-3 fs-5">Test Suite Output</h3>
            <ul class="tree-node tree-object">${placeholderItems}</ul>
        `;

        console.log('HealthController.testSuite: Placeholders rendered. Requesting test data...');
        App.spinner();

        try {
            const res = await fetch(App.url('test/health'));
            console.log(`HealthController.testSuite: Response received with HTTP status ${res.status}`);

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();
            console.log('HealthController.testSuite: Payload decoded successfully:', data);

            // 2. Overwrite placeholders with recursive data tree
            suiteContainer.innerHTML = `
                <h3 class="fw-bold mb-3 fs-5">Test Suite Output</h3>
                ${this.renderTree(data)}
            `;
        } catch (e) {
            console.error('HealthController.testSuite: Execution failed with error:', e);
            App.flash('Health Center Error: Test suite unreachable');
        } finally {
            App.clearSpinner();
            console.log('HealthController.testSuite: Execution finished.');
        }
    },

    // Recursive object tree renderer
    renderTree(nodes) {
        if (nodes === null || nodes === undefined) return '<span class="tree-null">null</span>';
        if (typeof nodes !== 'object') return `<span class="tree-val">${this.escapeHtml(String(nodes))}</span>`;

        const isArray = Array.isArray(nodes);
        let html = `<ul class="tree-node ${isArray ? 'tree-array' : 'tree-object'}">`;

        for (const [key, val] of Object.entries(nodes)) {
            html += '<li>';
            if (!isArray) html += `<strong class="tree-key">${this.escapeHtml(key)}:</strong> `;
            html += (typeof val === 'object' && val !== null) 
                ? this.renderTree(val) 
                : `<span class="tree-val">${this.escapeHtml(String(val))}</span>`;
            html += '</li>';
        }

        return html + '</ul>';
    },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    // Core Status Updaters
    rag(data) {
        if (!this.elements.rag) return;

        let statusStr = 'OFFLINE';
        if (typeof data === 'string') {
            statusStr = data;
        } else if (data && typeof data === 'object') {
            statusStr = (data.status === 'online' || data.rag_service === 'online') ? 'online' : (data.status || 'offline');
        }

        const isOnline = (statusStr.toLowerCase() === 'online' || statusStr.toLowerCase() === 'success');
        this.elements.rag.innerText = isOnline ? 'ONLINE' : statusStr.toUpperCase();
        this.elements.rag.style.color = isOnline ? '#059669' : '#dc2626';
    },

    ollama(data) {
        if (!this.elements.ollama) return;

        let isOnline = false;
        if (data && typeof data === 'object') {
            isOnline = data.active === true || data.status === 'online';
        } else if (typeof data === 'string') {
            isOnline = data.toLowerCase() === 'online';
        }

        this.elements.ollama.innerText = isOnline ? 'ONLINE' : 'OFFLINE';
        this.elements.ollama.style.color = isOnline ? '#059669' : '#dc2626';
    },

    worker(data) {
        if (!this.elements.worker) return;

        let isOnline = false;
        if (data && typeof data === 'object') {
            isOnline = data.status === 'online' || data.active === true;
        } else if (typeof data === 'string') {
            isOnline = data.toLowerCase() === 'online';
        }

        this.elements.worker.innerText = isOnline ? 'ONLINE' : 'OFFLINE';
        this.elements.worker.style.color = isOnline ? '#059669' : '#dc2626';
    },

    models(models) {
        if (!this.elements.models) return;
        this.elements.models.innerHTML = (models && Array.isArray(models) && models.length > 0) 
            ? models.map(m => `<li>${this.escapeHtml(m.name || m)}</li>`).join('') 
            : '<li>No models detected</li>';
    },

    emails(status) { /* Placeholder for email queue status */ },
    async chat(userQuery) { /* Placeholder for chat logic */ }
};
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
            this.rag(data.rag_service || 'offline');
            this.ollama(data.ollama);
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
            this.rag(data.rag_service || 'offline');
            this.ollama(data.ollama);
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
            'orm'
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
            const res = await fetch(App.url('test/test'));
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

    // Test Suite integration
    async old_testSuite() {
        App.spinner();
        try {
            const res = await fetch(App.url('test/test'));
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            
            const data = await res.json();
            const healthContainer = document.getElementById('health');
            if (!healthContainer) return;

            let suiteContainer = document.getElementById('health-test-suite');
            if (!suiteContainer) {
                suiteContainer = document.createElement('div');
                suiteContainer.id = 'health-test-suite';
                suiteContainer.className = 'card shadow-sm border-0 mt-4 p-4';
                healthContainer.appendChild(suiteContainer);
            }

            suiteContainer.innerHTML = `
                <h3 class="fw-bold mb-3 fs-5">Test Suite Output</h3>
                ${this.renderTree(data)}
            `;
        } catch (e) {
            console.error("Test suite execution failed:", e);
            App.flash(`Health Center Error: Test suite unreachable`);
        } finally {
            App.clearSpinner();
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
    async rag(statusOverride) {
        if (!this.elements.rag) return;

        if (statusOverride && statusOverride !== 'check') {
            const isOnline = (statusOverride === 'online' || statusOverride === 'success');
            this.elements.rag.innerText = isOnline ? 'ONLINE' : statusOverride.toUpperCase();
            this.elements.rag.style.color = isOnline ? '#28a745' : '#dc3545';
            return;
        }

        try {
            const response = await fetch(App.url('rag/check'));
            const data = await response.json();
            const isOnline = (data.rag_service === 'online');

            this.elements.rag.innerText = isOnline ? 'ONLINE' : 'UNREACHABLE';
            this.elements.rag.style.color = isOnline ? '#28a745' : '#dc3545';
        } catch (e) {
            console.error("RAG status check failed:", e);
            this.elements.rag.innerText = 'UNREACHABLE';
            this.elements.rag.style.color = '#dc3545';
            App.flash('RAG Service: Unreachable');
        }
    },

    ollama(data) {
        if (!this.elements.ollama) return;
        const active = data?.active === true;
        this.elements.ollama.innerText = active ? 'ONLINE' : 'OFFLINE';
        this.elements.ollama.style.color = active ? '#28a745' : '#dc3545';
    },

    models(models) {
        if (!this.elements.models) return;
        this.elements.models.innerHTML = (models && models.length > 0) 
            ? models.map(m => `<li>${this.escapeHtml(m.name || m)}</li>`).join('') 
            : '<li>No models detected</li>';
    },

    worker(status) { /* Placeholder for worker status */ },
    emails(status) { /* Placeholder for email queue status */ },
    async chat(userQuery) { /* Placeholder for chat logic */ }
};
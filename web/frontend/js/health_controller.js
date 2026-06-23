/** * HealthController: Orchestrates the Health Center UI
 * Pattern: Defensive DOM initialization + Robust Payload Handling
 */
const HealthController = {
    elements: {},

    // Ensure DOM elements are only mapped once, when they exist
    init() {
        this.elements = {
            rag: app.getItem('status-rag'),
            worker: app.getItem('status-worker'),
            ollama: app.getItem('status-ollama'),
            models: app.getItem('status-models')
        };
    },

    async get() {
        // Defensive check: If elements aren't mapped, do it now
        if (Object.keys(this.elements).length === 0) this.init();
        
        app.spinner();
        try {
            const res = await fetch(App.url('health'));
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            
            const data = await res.json();

            // Safe property access using optional chaining (?.) and fallbacks
            this.rag(data.rag_service);
            this.ollama(data.ollama);
            this.models(data.ollama?.models || []);
            this.worker(data.worker_status || 'unknown');
            this.emails(data.email_status || 'idle');

        } catch (e) {
            console.error("Health Center unreachable:", e);
            App.flash(`Health Center Error: ${e.message}`);
        } finally {
            app.clearSpinner();
        }
    },

    rag(status) {
        if (!this.elements.rag) return;
        this.elements.rag.className = (status === 'online') ? 'text-success' : 'text-danger';
        this.elements.rag.innerText = status.toUpperCase();
    },

    ollama(data) {
        if (!this.elements.ollama) return;
        // Handle cases where data might be undefined
        const active = data?.active === true;
        this.elements.ollama.innerText = active ? 'ONLINE' : 'OFFLINE';
        this.elements.ollama.style.color = active ? '#28a745' : '#dc3545';
    },

    models(models) {
        if (!this.elements.models) return;
        this.elements.models.innerHTML = (models && models.length > 0) 
            ? models.map(m => `<li>${m.name}</li>`).join('') 
            : '<li>No models detected</li>';
    },

    worker(status) { /* Placeholder for worker-specific logic */ },
    emails(status) { /* Placeholder for email-queue logic */ }
};
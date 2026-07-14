/** * HealthController: Orchestrates the Health Center UI
 * Pattern: Defensive DOM initialization + Robust Payload Handling
 */
const HealthController = {
    elements: {},

    // Ensure DOM elements are only mApped once, when they exist
    init() {
        this.elements = {
            rag: App.getItem('status-rag'),
            worker: App.getItem('status-worker'),
            ollama: App.getItem('status-ollama'),
            models: App.getItem('status-models')
        };
    },

// Inside HealthController.get()
async get() {
    if (Object.keys(this.elements).length === 0) this.init();
    
    App.spinner();
    try {
        const res = await fetch(App.url('health'));
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        
        const data = await res.json();

        // Normalizing data from the successful response
        this.rag(data.rag_service || 'offline');
        // ... (rest of your calls)

    } catch (e) {
        console.error("Health Center unreachable:", e);
        // Explicitly update the UI to show the service is unreachable
        this.rag('unreachable'); 
        App.flash(`Health Center Error: Service unreachable`);
    } finally {
        App.clearSpinner();
    }
},

    async rag() {
        if (!this.elements.rag) return;

        try {
            // Fetch the status from the new RagController endpoint
            const response = await fetch(App.url('rag/check'));
            const data = await response.json();

            // Determine if the service is online based on the API response
            const isOnline = (data.rag_service === 'online');

            // Update DOM
            this.elements.rag.innerText = isOnline ? 'ONLINE' : 'UNREACHABLE';
            this.elements.rag.style.color = isOnline ? '#28a745' : '#dc3545';
            
        } catch (e) {
            // Handle network or parsing errors
            console.error("RAG status check failed:", e);
            this.elements.rag.innerText = 'UNREACHABLE';
            this.elements.rag.style.color = '#dc3545';
            App.flash('RAG Service: Unreachable');
        }
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

    emails(status) { /* Placeholder for email-queue logic */ },

    async chat(userQuery) { /* Placeholder for chat logic */}

};
/**
 * Registry: Maps page IDs to their specific initialization functions
 */
const PageRegistry = {
    'rag': () => RagController.bindRag(),
    'emails': () => Controller.bindEmails(),
    'agent': () => AgentController.bindAgents(),
    'docs': () => Controller.docs('docs'),
    'tiktok': () => TiktokController.bindPosts(),
    'agentemail': () => AgentController.bindAgentEmail(), // New entry
    'autoform': () => AgentController.autoForm(), // New entry
    'sales': () => SalesController.bindSales(), // Add this
    'health': () => {
        HealthController.init();
        HealthController.get();
    }
};
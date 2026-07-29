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
    'snapshot': () => SnapshotController.displayForm(), // Not being called from the Agent
    'health': () => {
        HealthController.init();
        HealthController.get();
        HealthController.chat('Hello');
    },
    'pentest': () => PentestController.scan(),
    //'pentest-examine': (data) => PentestController.examine(data)
    'agentic' : AgenticController.init()
};
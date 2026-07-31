const PageRegistry = {
    'rag': () => RagController.bindRag(),
    'emails': () => Controller.bindEmails(),
    'agent': () => AgentController.bindAgents(),
    'docs': () => Controller.docs('docs'),
    'tiktok': () => TiktokController.bindPosts(),
    'agentemail': () => AgentController.bindAgentEmail(),
    'autoform': () => AgentController.autoForm(),
    'sales': () => SalesController.bindSales(),
    'snapshot': () => SnapshotController.displayForm(),
    'health': () => {
        HealthController.init();
        HealthController.get();
        HealthController.chat('Hello');
    },
    'pentest': () => PentestController.scan(),
    'agentic': () => AgenticController.init(),
    'mobile': () => MobileController.init()
};
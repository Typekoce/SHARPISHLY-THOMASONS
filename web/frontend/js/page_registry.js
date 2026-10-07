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
        HealthController.testSuite();
        JobApplyController.hello();
    },
    'pentest': () => PentestController.scan(),
    'agentic': () => AgenticController.init(),
    'mobile': () => MobileController.init(),
    'todo': () => TodoController.init(),
    'notes': () => NotesController.init(),
    'login': () => LoginController.init(),
    'projects': () => ProjectsController.init()
};
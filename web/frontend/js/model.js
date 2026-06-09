/** model.js */

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
    'agentemail': () => AgentController.bindAgentEmail(), // New entry
};



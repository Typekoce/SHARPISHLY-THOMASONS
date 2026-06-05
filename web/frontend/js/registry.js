const PageRegistry = {
    'home': () => Controller.home(), // Or your default landing logic
    'rag': () => Controller.bindRag(),
    'emails': () => Controller.bindEmails(),
    'agent': () => Controller.bindAgents(),
    'docs': () => Controller.docs('docs')
};

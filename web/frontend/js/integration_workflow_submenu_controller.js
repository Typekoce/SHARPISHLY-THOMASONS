IntegrationWorkflowController.submenu = function() {
    const App = window.App;
    if (!App) {
        console.warn('IntegrationWorkflowController: App namespace not available');
        return;
    }

    const menuItems = [
        {
            label: 'All Workflows',
            action: () => IntegrationWorkflowController.read()
        },
        {
            label: 'Pending Ingests',
            action: () => IntegrationWorkflowController.read('', { status: 'pending' })
        },
        {
            label: 'Completed Tasks',
            action: () => IntegrationWorkflowController.read('', { status: 'completed' })
        },
        {
            label: 'Refresh System',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => IntegrationWorkflowController.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};

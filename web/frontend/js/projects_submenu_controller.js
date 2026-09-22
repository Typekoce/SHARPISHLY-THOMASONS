ProjectsController.submenu = function() {
    const menuItems = [
        {
            label: 'All Items',
            action: () => ProjectsController.read()
        },
        {
            label: 'Pending Items',
            action: () => ProjectsController.read('', { status: 'pending' })
        },
        {
            label: 'Completed Items',
            action: () => ProjectsController.read('', { status: 'completed' })
        },
        {
            label: 'Refresh',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => ProjectsController.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};
